# Kiểm toán hiệu năng Smart Classroom Monitor

**Ngày kiểm tra:** 14-11-2026  
**Phạm vi:** đọc luồng ứng dụng và cấu hình; kiểm tra trạng thái listener/HTTP cục bộ; chuẩn bị kịch bản API có rào chắn. Không chạy tải lên môi trường đang hoạt động, không tạo người dùng, không thay đổi schema/dữ liệu/cấu hình sản phẩm, không commit/push.

## Kết luận

- **Số Sinh viên đồng thời cao nhất đã được chứng minh đạt SLO: chưa xác định.** Chưa chạy bất kỳ mức tải k6 nào. Không thể suy luận năng lực production từ máy phát triển.
- **Mức tải đầu tiên thất bại: chưa xác định.** Không có benchmark an toàn đang chạy trong lượt này.
- **Kịch bản camera/WebRTC/MediaPipe/Reverb thực tế: chưa chạy.** Kịch bản k6 là backend-only và không giả lập các thành phần này.
- Local service đang lắng nghe tại cổng MySQL 3306, Laravel 8000, Reverb 8080 và Vite 5173. Kiểm tra HTTP nhỏ: Vite trả `200` (524 byte), Reverb trả `404` tại `/` (listener/HTTP có phản hồi, chưa xác minh WebSocket handshake), Laravel trả `500` tại `/`.
- Log Laravel hiện có ghi nhận `Route [login] not defined` và lỗi `SQLSTATE[42S02]` do bảng `sessions` không tồn tại; không gắn chắc chắn từng entry với request root vừa kiểm tra. Đây là lỗi route/session cần xử lý riêng trước khi dùng instance local làm staging benchmark, **không phải phép đo hiệu năng**. API tải chưa được benchmark.

## Kiến trúc và tải thực tế trong mã

### Sinh viên

- `fe/src/components/SinhVien/LamBaiKiemTra/GiamSatCamera.vue` gọi `getUserMedia()` cho webcam và dùng MediaPipe Tasks-Vision ở cùng máy Sinh viên. `checkCamera()` chạy mỗi 500 ms; phát hiện khuôn mặt ở mỗi khung hình mới, bộ nhận diện điện thoại được giới hạn tối thiểu mỗi 300 ms. Vì vậy CPU/GPU và bộ nhớ MediaPipe chủ yếu tiêu thụ trên thiết bị Sinh viên; k6 không đo phần này.
- `fe/src/services/webrtc/studentSender.js` giữ một `RTCPeerConnection` phía Sinh viên cho kết nối gửi webcam. Phía Sinh viên cũng mở private channel signaling phù hợp phiên/Sinh viên.
- Lưu nháp có debounce 700 ms, queue tuần tự và gộp thay đổi đang chờ (xem `draftSaveQueue.js`). Khi người dùng liên tục thay đổi nội dung, đây là tải ghi có thể xuất hiện trên Backend. `examDraftBackup.js` dùng IndexedDB cục bộ; không tạo tải MySQL.

### Giảng viên và kết nối thời gian thực

- `fe/src/components/GiangVien/GiamSatThi/index.vue` dựng `StudentCamera` cho danh sách thành viên; `teacherReceiver.js` tạo một `RTCPeerConnection` cho mỗi Sinh viên cần nhận video. Với một Giảng viên xem N luồng, trình duyệt Giảng viên xử lý xấp xỉ N PC/luồng nhận. Nếu có nhiều người xem cùng phiên thì tải tăng theo số người xem × số camera.
- Một kết nối WebSocket Reverb có thể subscribe nhiều channel; mã có private channel `exam-monitor.{phienId}.student.{sinhVienId}` và feed `exam-session.{phienId}`. Không nên tính mỗi channel thành một TCP WebSocket riêng.
- `be/routes/channels.php` kiểm tra trạng thái phiên, membership và quyền Giảng viên phụ trách lớp trước khi cho phép private channel. Cấu hình Reverb trong source tắt scaling mặc định (`REVERB_SCALING_ENABLED=false`); production đa instance cần kiểm tra cấu hình scaling/Redis thực tế. Không đọc/ghi lộ secret.

### API, database và các điểm cần đo

- `SinhVienKiemTraController::startExam`, `saveDraft`, `submit` và `reportTabLeave` dùng transaction; nhiều luồng khóa hàng `bai_tap` bằng `lockForUpdate()`. Do khóa cùng một hàng bài tập, các thao tác trong cùng bài có khả năng tranh chấp/tuần tự hóa ở MySQL. Đây là hotspot từ code cần benchmark, chưa phải lỗi đo được.
- Trong `GiangVienApiController::chiTietPhien`, danh sách cảnh báo camera giới hạn 30 dòng nhưng callback map mỗi dòng thực hiện truy vấn `anh_man_hinh` theo `event_id`. Đây là N+1 tối đa 30 truy vấn bổ sung mỗi lần tải chi tiết phiên; nên đo query count/độ trễ trước khi quyết định tối ưu.
- `CanhBao/index.vue` gọi lại API và kiểm tra subscribe mỗi 10 giây; lượng polling tăng theo số phiên Giảng viên đang mở. Trang ảnh có tải ảnh đồng thời giới hạn ở frontend; benchmark cần bao gồm response JPEG, nhưng chỉ trên fixture riêng.
- Các event bảo vệ bài thi có UUID `event_id` unique trong migration; bảng lượt làm có unique `(bai_tap_id, sinh_vien_id)`. Những ràng buộc hỗ trợ idempotency nhưng không thay thế đo transaction lock/latency.
- Endpoint nộp bài ghi dữ liệu kết quả; test tải nộp bài bắt buộc dùng các user/attempt disposable và một lượt chạy mới. Không dùng lại fixture đã nộp.
- `vite.config.js` là dev proxy, không phải cấu hình benchmark production. Không dùng Vite development server để công bố năng lực tải của bản production.

## Môi trường quan sát được

| Hạng mục | Kết quả quan sát | Giới hạn |
|---|---|---|
| OS / CPU / RAM tổng và khả dụng | Chưa lấy được; CIM/systeminfo không trả thông tin trong sandbox | Không suy đoán phần cứng |
| PHP | PHP CLI 8.5.12, ZTS, Windows x64 | Chưa xác nhận PHP-FPM/production SAPI |
| Laravel | 12.62.0 | Từ `php artisan --version` |
| Node / npm | Node 22.14.0 / npm 10.9.0 | Local development |
| MySQL listener | `mysqld` PID 23400 lắng nghe 3306 | Không benchmark hoặc tạo query tải; MariaDB 10.11.16 từng được đọc qua `db:show` trong lượt chẩn đoán trước, không phải production xác nhận |
| PHP processes | 3 tiến trình PHP thấy tại snapshot | Windows local process count không tương đương worker pool; listener 8000 gắn PID 27968, 8080 gắn PID 33524 |
| Node processes | 2 tiến trình Node thấy; Vite listener trên IPv6 loopback 5173 | Không phải năng lực production |
| Reverb | Listener 8080; GET `/` trả 404 | Chưa thực hiện WebSocket/Pusher handshake |
| Laravel | Listener 8000; GET `/` trả 500; log có lỗi route login và bảng session thiếu | API tải chưa được kiểm tra; không được xem service là health-pass |
| Vite | Listener `::1:5173`; GET `/` trả 200, 524 byte | Chỉ xác nhận dev server phục vụ trang |
| PHPUnit DB | `be/phpunit.xml` đặt `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, broadcast null, queue sync | Đây là cấu hình test riêng; không chạy suite trong lượt audit này |
| k6 / MySQL CLI | k6, `mysql`, `mariadb` không cài trong PATH; Docker CLI có | Chưa có load runner khả dụng; không tự cài phần mềm |
| Staging / production | Không có endpoint/credential được cung cấp hoặc kiểm chứng | Chưa đo |

Snapshot tiến trình local: mysqld ~26.8 MB working set; node ~142.6 MB và ~28.3 MB; php ~20.9 MB, ~61.2 MB, ~28.7 MB. Đây chỉ là working set tức thời, không phải idle baseline, peak hoặc capacity. Không đọc mật khẩu/token/API key.

## Ma trận mức tải

Chưa có số đo nên không điền số liệu giả. Chạy từng mức trên DB disposable theo `PERFORMANCE_TEST_PLAN.md`; giữ nguyên `NOT RUN` cho đến khi có kết quả k6 + tài nguyên máy chủ.

| Sinh viên | API no-camera | API + autosave | Camera + AI + WebRTC + Reverb thực | CPU/RAM | p95/p99 | Error rate | SLO |
|---:|---|---|---|---|---|---|---|
| 1 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 5 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 10 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 20 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 30 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 50 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 75 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 100 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 150 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |
| 200 | NOT RUN | NOT RUN | NOT RUN | — | — | — | chưa xác nhận |

SLO đề xuất trong script: API đọc p95 <500 ms, lưu nháp p95 <1 s, nộp bài p95 <2 s, lỗi HTTP <1%. Đây là tiêu chí mục tiêu, không phải kết quả đạt. Cần bổ sung p50, mean, p99, RPS, CPU, RAM, DB connections, lock waits, queue depth và dữ liệu kiểm tra toàn vẹn cho từng mức.

## Rủi ro kiến trúc cần ưu tiên kiểm chứng

1. **Khóa bài tập dùng chung:** `lockForUpdate()` trên cùng hàng `bai_tap` trong start/draft/submit/cảnh báo có thể tạo hàng đợi khi lớp đông; tách theo trace MySQL lock wait và mức concurrency trước khi đổi thiết kế.
2. **N+1 ảnh trong chi tiết phiên:** tối đa 30 lookup ảnh theo event cho mỗi lần gọi; đo query count và p95 endpoint.
3. **Tải nhận webcam ở Giảng viên:** mỗi video là một PC/track tại trình duyệt Giảng viên; CPU, decoder, GPU, RAM và downlink của máy Giảng viên có thể chạm giới hạn trước API.
4. **MediaPipe theo thiết bị:** vòng kiểm tra 500 ms và phone detection tối thiểu 300 ms tạo mức công việc đáng kể trên từng máy Sinh viên; benchmark phải dùng phần cứng trình duyệt đại diện.
5. **Reverb scaling:** mặc định source tắt scale; đo số socket, số subscriptions, memory/connection và độ trễ broadcast trên deployment giống production.
6. **Polling dashboard:** 10 giây/lượt/trang tạo tải API tăng tuyến tính theo số Giảng viên và tab mở.
7. **Lưu nháp và đồng thời nộp:** ngoài response time phải xác minh revision, answers và kết quả nộp ở DB disposable; bất kỳ mất/ghi đè dữ liệu nào là tiêu chí dừng.

## Đề xuất vận hành và triển khai

- Tách benchmark generator, Laravel app, MySQL và Reverb để không để load generator chiếm CPU/RAM server đang đo; dùng database clone disposable và fixture chuẩn hóa.
- Đo trên runtime gần production (PHP SAPI/worker, OPcache, TLS/proxy, Redis/queue nếu production dùng), không suy từ `artisan serve` hoặc Vite dev server.
- Thu thập baseline idle và theo từng bậc; chỉ tăng khi response, lỗi, DB lock, memory và dữ liệu còn ổn định. Dừng ngay khi xuất hiện lỗi lưu/nộp, lỗi kết nối tăng, hoặc tài nguyên sát giới hạn.
- Không thể đề xuất số CPU/RAM production hoặc số Sinh viên/server đáng tin cậy trước khi biết workload, bitrate/resolution webcam, số Giảng viên cùng xem, DB dataset, SLO và benchmark tương ứng. Thiết kế production nên tách app/DB/Reverb và scale theo bottleneck đo được, có headroom cho peak.

## Trạng thái chuẩn bị thực tế (14-11-2026)

- `k6 version`: **FAIL / not installed** (PowerShell báo không nhận diện command). Không tự cài package hoặc thay đổi PATH máy.
- PHP `pdo_sqlite`: **PASS**, SQLite library 3.53.4. Laravel config cache không tồn tại ở thời điểm kiểm tra.
- `PerformanceTestSeeder.php` và `verify-sqlite.php`: PHP syntax **PASS**. PowerShell parser cho hai script **PASS**.
- Chạy hai lệnh script mặc định ở dry-run: **PASS**, chỉ in kế hoạch; xác nhận không tạo DB/fixture, không migrate/seed, không khởi chạy server.
- **Database/fixture chưa tạo**. Vì vậy môi trường benchmark **chưa sẵn sàng** và chưa thể chạy bậc 1.
- Tool portable, DB, credential fixture và kết quả được chuẩn bị trên ổ F và ignore khỏi Git; không thay đổi PATH toàn máy.
- Runner xác minh marker/count, config cache, port 8001 và API health `401` trước tải; mỗi VU stage có SQLite clone mới. Sau mỗi stage, nó xác minh số submissions/answers/scores bằng số VU và chờ người vận hành duyệt `CONTINUE` trước khi tăng tải. Kết quả được giữ lại, không cleanup tự động.

**Cần người vận hành cho phép trước khi tiếp tục:** tải/extract k6 portable; chạy `Prepare-PerformanceDb.ps1` với hai cờ xác nhận (tạo file SQLite test mới, migrate file đó và seed 100 user tổng hợp); kiểm tra marker/count PASS; sau đó chạy `Invoke-PerformanceStages.ps1 -RunConfirmedIsolatedLoad`. Chưa thực hiện thao tác nào trong danh sách này.

## Tệp tạo/cập nhật trong phạm vi chuẩn bị

- `be/database/seeders/PerformanceTestSeeder.php`: tạo synthetic fixtures chỉ trên exact SQLite test path; chưa chạy.
- `performance-tests/verify-sqlite.php`: read-only marker/row count validator, path constrained; kiểm tra dữ liệu sau stage.
- `performance-tests/Prepare-PerformanceDb.ps1`: dry-run mặc định; có hai cờ xác nhận để tạo DB/migrate/seed; chưa chạy thao tác ghi.
- `performance-tests/Invoke-PerformanceStages.ps1`: dry-run mặc định; tạo clone từng bậc và thu CPU/RAM khi được xác nhận; chưa chạy tải.
- `performance-tests/exam-flow.k6.js`: API flow và custom counter lưu nháp/nộp bài.
- `.gitignore` root/backend, README và Test Plan: loại trừ binary/fixture/DB/results, hướng dẫn setup.

Không sửa source ứng dụng, migration, schema, cấu hình runtime hoặc dữ liệu MySQL.
