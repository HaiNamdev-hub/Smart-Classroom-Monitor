# Báo cáo sửa Critical — Smart Classroom Monitor

**Phạm vi:** Chỉ thực hiện Giai đoạn 1 trong `PROJECT_AUDIT_REPORT.md`; không bắt đầu các nhóm High, Medium hoặc Low. Không chạy migration, không thay đổi dữ liệu MySQL, không commit/push/reset.

## Trạng thái

| Mã | Trạng thái | Kết quả xác minh |
|---|---|---|
| CRIT-01 | Fixed | Khóa định danh dùng trong API bài nộp được thống nhất với foreign key thực tế (`nguoi_dung.id`); có feature test dùng hai ID khác nhau và nộp file thành công. |
| CRIT-02 | Fixed / Verified trên DB hiện tại | `migrate:status` báo migration bổ sung đã chạy; schema MySQL có đủ 4 cột metadata. Không cần migration mới và không chạy migration. |
| CRIT-03 | Fixed | Kiểm tra tổng điểm ở cả API Giảng viên bắt đầu phiên và API Sinh viên bắt đầu làm bài. Kiểm tra chỉ đọc, không tự phân bổ lại điểm. |

## Chi tiết thay đổi

### CRIT-01 — Nhầm `sinh_vien.id` với `nguoi_dung.id`

**Nguyên nhân xác nhận:** Hai migration tạo `thanh_vien_lop.sinh_vien_id` và `bai_nop.sinh_vien_id` khai báo foreign key đến `nguoi_dung.id`, nhưng `BaiNopController` lấy `sinh_vien.id` rồi dùng giá trị đó để kiểm tra thành viên lớp, lọc bài nộp và tạo bài nộp. Khi hai ID khác nhau, Sinh viên hợp lệ bị từ chối hoặc có thể bị gán nhầm chủ sở hữu. Mô hình `SinhVien` và liên kết từ `ThanhVienLop` cũng mặc định so khớp với khóa hồ sơ thay vì `nguoi_dung_id`.

**Đã sửa:**

- `be/app/Http/Controllers/BaiNopController.php`: kiểm tra tài khoản có hồ sơ Sinh viên nhưng sử dụng ID Sanctum `nguoi_dung.id` cho các foreign key bài nộp/thành viên; đổi validation foreign key sang `nguoi_dung`; khớp tên cột đường dẫn với schema/model (`duong_dan`).
- `be/app/Models/SinhVien.php`: khai báo `nguoi_dung_id` là local key cho các quan hệ có foreign key đến người dùng.
- `be/app/Models/ThanhVienLop.php`: liên kết hồ sơ Sinh viên qua owner key `nguoi_dung_id`.
- `be/tests/Feature/BaiNopStudentIdentityTest.php`: test đăng nhập bằng Sanctum, tạo hồ sơ có ID khác user ID, nộp file, kiểm tra membership, bản ghi bài nộp và các quan hệ Eloquent.

**Vị trí:** `BaiNopController.php:29-45, 73-98, 109, 162, 343`; `SinhVien.php:37-80`; `ThanhVienLop.php:16-23`; `BaiNopStudentIdentityTest.php:17-100`.

### CRIT-02 — Thiếu `thong_tin_tin_hieu`

**Kết quả xác minh:** `php artisan migrate:status --no-ansi` báo `2026_11_10_000003_add_review_and_signal_to_exam_protection_events` là `Ran`. Truy vấn schema chỉ đọc bằng `php artisan db:table su_kien_bao_ve_bai_kiem_tra --json` xác nhận MySQL hiện tại có `thong_tin_tin_hieu`, `da_xem`, `xem_luc`, `xem_boi_id`; riêng `thong_tin_tin_hieu` là cột JSON nullable (driver MySQL hiển thị kiểu vật lý `longtext`).

**Xử lý:** Không sửa code/migration cho lỗi này vì migration phù hợp đã tồn tại và DB hiện tại đã áp dụng. Không chạy lệnh thay đổi schema. Cần kiểm tra migration status riêng ở môi trường triển khai khác trước khi phát hành vì kết quả này chỉ chứng minh DB hiện tại.

### CRIT-03 — Có thể bắt đầu bài thi có tổng điểm sai

**Nguyên nhân xác nhận:** Lịch thi có gọi `ExamScoreAllocator::assertPublishable()`, nhưng thao tác bắt đầu phiên Giảng viên và bắt đầu làm bài của Sinh viên chưa kiểm tra điểm đã lưu. Guard khi nộp bài là quá muộn. `assertPublishable()` hiện gọi `rebalance()` nên không phù hợp để dùng như một phép kiểm tra tại thời điểm bắt đầu.

**Đã sửa:**

- `be/app/Support/ExamScoreAllocator.php`: thêm `assertStoredTotalIsTen()` để kiểm tra có câu hỏi, loại câu hỏi, điểm dương với tối đa hai chữ số thập phân và tổng đúng 10. Hàm không cập nhật điểm câu hỏi.
- `be/app/Http/Controllers/GiangVienApiController.php`: từ chối mở phiên khi cấu hình điểm không hợp lệ; phiên vẫn ở trạng thái chờ.
- `be/app/Http/Controllers/SinhVienKiemTraController.php`: từ chối tạo attempt khi điểm không hợp lệ; câu trả lời và điểm hiện có không bị sửa.
- `be/tests/Feature/ExamProtectionAlertTest.php`: thêm kiểm thử điểm sai bị chặn ở cả hai API, điểm không bị tự thay đổi và trường hợp tổng 10 vẫn mở phiên/làm bài được.

**Vị trí:** `ExamScoreAllocator.php:105-143`; `GiangVienApiController.php:982`; `SinhVienKiemTraController.php:479`; `ExamProtectionAlertTest.php:18-64`.

Guard cuối tại API nộp bài vẫn giữ nguyên như kiểm tra an toàn. Vì vậy nếu đã có attempt đang diễn ra từ trước khi triển khai bản sửa và đề có tổng điểm sai, API nộp vẫn có thể từ chối; cần Giảng viên rà soát các attempt cũ riêng. Không có attempt hoặc dữ liệu MySQL nào được cập nhật bởi lần kiểm tra này.

## Kiểm thử

- PASS — `php artisan test --filter='BaiNopStudentIdentityTest|ExamProtectionAlertTest|ExamScoreAllocationTest'`: 12 tests, 115 assertions.
- PASS — PHP syntax (`php -l`) trên các Controller, Model, helper và test đã chỉnh.
- PASS — `php artisan migrate:status --no-ansi`; các migration hiện có đều `Ran`.
- PASS — Đọc schema trực tiếp bằng `php artisan db:table ... --json`; đủ 14 cột, gồm các cột review/signal.
- PASS — `php artisan route:list --path=phien-giam-sat --no-ansi`; route bắt đầu phiên hiện diện dưới prefix Giảng viên.
- PASS — `npm run build` (Vite 6.4.3).
- PASS — `git diff --check`.
- PARTIALLY PASS — `php artisan test` chạy toàn bộ suite trên SQLite in-memory: 30 passed, 5 failed. Các lỗi còn lại không thuộc ba Critical đang sửa: `DangNhapTest` kỳ vọng 429 nhưng nhận 401; `DatabaseSeederTest` kỳ vọng 51 cảnh báo nhưng seed tạo 31; hai `ExamEvidenceTest` không chạy được vì PHP GD extension không cài; `ReportTabLeaveTest` kỳ vọng số vi phạm 1 nhưng nhận 0. Không sửa các nhóm ngoài phạm vi Giai đoạn 1.
- NOT RUN — Kiểm thử hai trình duyệt, camera/WebRTC thực tế và gọi API trên MySQL sản xuất. Không thực hiện migration hoặc ghi dữ liệu DB thật.

## Rủi ro và việc tiếp theo

- Trước khi triển khai lên DB khác, kiểm tra `migrate:status` và schema trên chính môi trường đó. Không cần chạy migration trên DB hiện tại.
- Rà soát các bài thi/attempt đã bắt đầu trước bản sửa nếu tổng điểm của đề không bằng 10; guard nộp bài hiện hữu vẫn từ chối tổng sai để tránh lưu/chấm theo thang điểm không hợp lệ.
- Giai đoạn 2–4 chưa được thực hiện theo yêu cầu dừng sau Giai đoạn 1.

## GIAI ĐOẠN 2 — HIGH PRIORITY BUG FIXES (14/11/2026)

### Trạng thái tổng hợp

| Mã | Trạng thái | Kết quả |
|---|---|---|
| HIGH-01 | Partially Fixed | Event camera, điện thoại, rời tab và nghi vấn chụp màn hình có eventId, broadcast sau commit tới kênh Sinh viên và kênh phiên. Trang Cảnh báo nghe event bảo vệ và event vi phạm; trang Phiên nhận event per-student. Có dedupe và API lịch sử. Chưa xác minh bằng hai trình duyệt/Reverb đang chạy. |
| HIGH-02 | Fixed | Autosave tuần tự, gộp các lần sửa trong lúc request chạy, bắt lỗi Promise và cho thử lại khi online. Backend dùng revision SHA-256 để từ chối ghi đè nháp mới hơn. Đã sửa lỗi INSERT nháp lần đầu thiếu bai_tap_id và sinh_vien_id. |
| HIGH-03 | Fixed | IndexedDB backup tách theo người dùng/bài/lần thi, whitelist câu trả lời, giới hạn 128 KiB và TTL 24 giờ; kiểm tra attempt/trạng thái trước khi gợi ý phục hồi; xóa sau xác nhận nộp thành công. |
| HIGH-04 | Fixed | beforeunload cảnh báo trình duyệt và onBeforeRouteLeave bảo vệ điều hướng Vue khi IN_PROGRESS. Lưu dự phòng trước khi cho rời trang; không tự nộp bài. Thoát camera có chủ đích do rời trang/nộp bài không phát camera_ngat giả. |
| HIGH-05 | Partially Fixed | Endpoint file trả binary response có Content-Length và bảo vệ Sanctum/quyền lớp; proxy có xử lý lỗi socket an toàn; retry thumbnail đã giới hạn. Kiểm thử response Laravel và proxy với upstream HTTP cục bộ đều pass. Không thể đối chiếu request live Laravel ↔ Vite vì cổng 8000/5173 không có tiến trình lắng nghe. |

### Nguyên nhân gốc được xác minh

- HIGH-02: Hàng đợi cũ có nguy cơ để Promise rejection lan ra caller và cho phép snapshot cũ cạnh tranh với snapshot mới. Kiểm thử còn phát hiện lỗi thật ở nhánh tạo nháp đầu tiên: INSERT thiếu hai khóa bài thi/Sinh viên. Backend nay khóa ghi theo bài thi, so sánh revision hiện hành rồi chỉ cập nhật nếu revision khớp; conflict trả 409 mà không sửa dữ liệu mới hơn.
- HIGH-03: Trước thay đổi không có bản nháp cục bộ. Dữ liệu mới chỉ lưu câu trả lời; không chứa token hay đáp án đúng. Phục hồi không tự chạy và không tự ghi đè nháp Backend khi revision khác.
- HIGH-01: CanhBaoExamEvent trước đây chỉ broadcast tới kênh exam-monitor của từng Sinh viên; trang Cảnh báo chỉ nghe exam-session cho event bảo vệ bài thi. Do lệch kênh, camera/rời tab không đến trung tâm Cảnh báo. Các event camera/tab cũng thiếu eventId. Nay event gửi sau commit tới cả hai private channel, dùng ID bản ghi đã lưu, và FE bỏ qua ID đã nhận.
- HIGH-04: Khi component camera bị unmount do rời route/nộp bài, callback dừng camera có thể bị báo như camera ngắt ngoài ý muốn. Cờ rời trang/nộp bài chặn báo giả; route guard và browser unload warning chỉ hoạt động khi IN_PROGRESS.
- HIGH-05: Controller hiện tại không dùng StreamedResponse; body đọc thành bytes và trả bằng response() với Content-Length. Điều này được xác nhận qua route test có Sanctum. Proxy test xác nhận Vite chuyển body nhị phân khi upstream gửi Connection: close. Tuy nhiên giả thuyết audit rằng PHP built-in server tự gửi thêm CRLF chưa được kiểm chứng qua response HTTP live; không xem đó là nguyên nhân đã chứng minh. Handler mới chỉ xử lý lỗi kết nối, không thay thế/bỏ qua HTTP status Laravel.

### File thay đổi trong Phase 2

- fe/src/components/SinhVien/LamBaiKiemTra/index.vue — trạng thái autosave, backup cục bộ, xử lý route leave/unload, tránh cảnh báo camera giả.
- fe/src/services/draftSaveQueue.js, fe/src/services/examDraftBackup.js — hàng đợi lưu tuần tự và IndexedDB backup.
- fe/src/components/GiangVien/CanhBao/index.vue — nhận .exam.violation, lọc theo phiên, dedupe event ID, giữ event realtime khi API refresh.
- be/app/Events/CanhBaoExamEvent.php — payload event có event ID/metadata, gửi tới private student và session channels.
- be/app/Http/Controllers/SinhVienCanhBaoCameraController.php, be/app/Http/Controllers/SinhVienKiemTraController.php — broadcast camera/tab sau DB commit; bổ sung metadata event; lưu nháp optimistic revision.
- be/app/Http/Controllers/GiangVienApiController.php — API lịch sử tab có event ID để dedupe sau reload.
- fe/vite.config.js — xử lý lỗi proxy với kiểm tra response đã đóng/đã gửi header.
- fe/package.json, fe/tests/exam-draft.test.js, fe/tests/vite-proxy.test.js, be/tests/Feature/ExamEvidenceTest.php, be/tests/Feature/ExamProtectionAlertTest.php — test mới và script unit test.

Không tạo/chạy migration; không đụng dữ liệu MySQL; không thay đổi WebRTC signaling, MediaPipe, điểm hoặc ngưỡng cảnh báo.

### Kiểm thử Phase 2

- PASS — npm run build (Vite 6.4.3, 171 modules). Lần chạy trong sandbox bị Windows từ chối quyền ghi file tạm; chạy lại trong quyền thực thi được duyệt và build hoàn tất.
- PASS — npm run test:unit: 8/8, gồm offline/reconnect, lưu tuần tự/coalesce, IndexedDB scoping/TTL/whitelist, SDP regression và binary proxy qua upstream đóng kết nối.
- PASS — Laravel tests tập trung: 16 tests, 144 assertions; gồm Critical regressions, bảo vệ cảnh báo, draft revision, identity/private channel và endpoint ảnh.
- PASS — PHP syntax trên Event, Controllers và Feature tests đã đổi.
- PASS — HTTP route test Laravel có Sanctum: ảnh nhị phân giữ nguyên body, Content-Type, Content-Length và private/no-store. PASS — Vite proxy local TCP test nhận đúng bytes/headers từ upstream HTTP/1.1 Connection: close.
- NOT VERIFIED — Kết nối hai trình duyệt tới Laravel/Reverb thật và tải ảnh qua cổng dự án đang chạy; hiện không có listener trên 8000 hoặc 5173.
- Không chạy migration và không kết nối/ghi database thật. Feature tests dùng SQLite :memory: theo phpunit.xml.

### 5 Laravel failures còn lại — phân loại mới nhất

Full suite: 33 passed, 5 failed. Không sửa expectation chỉ để làm xanh:

1. DangNhapTest::test_login_is_rate_limited — lỗi sản phẩm ngoài Phase 2: request lần 6 vẫn trả 401; route đăng nhập hiện chưa áp throttle. Đây là thiếu rate limit thật, audit xếp MED; không sửa vì yêu cầu chỉ làm Phase 2.
2. DatabaseSeederTest — expectation/dữ liệu test không đồng bộ: test kỳ vọng 51 dòng canh_bao, seeder hiện sinh 31. Không sửa seed/test.
3. ExamEvidenceTest::test_accepted_alert_evidence_upload_is_private_and_idempotent — thiếu môi trường: PHP chưa cài GD; UploadedFile::fake()->image() dừng trước khi gọi API.
4. ExamEvidenceTest::test_camera_disconnect_alert_cannot_accept_a_stale_image — thiếu môi trường: cùng nguyên nhân GD; không phải kết quả kiểm thử endpoint.
5. ReportTabLeaveTest — fixture thiếu dữ liệu: test tạo bài nhưng không tạo bai_kiem_tra_luot_lam; contract chỉ tăng vi phạm sau khi attempt bắt đầu nên API trả 0 là đúng. Không nới điều kiện Backend để thỏa fixture sai.

### Rủi ro/chưa kiểm chứng

- IndexedDB không được mã hóa riêng bởi ứng dụng; dữ liệu giới hạn theo origin, người dùng, bài và lần thi, hết hạn sau 24 giờ, xóa khi nộp thành công. Người dùng cùng hồ sơ trình duyệt có thể truy cập dữ liệu; UI cảnh báo không dùng thiết bị chung và đăng xuất/xóa dữ liệu trình duyệt sau thi.
- Chưa có kiểm thử live hai trình duyệt/Reverb hoặc tải ảnh qua Laravel/Vite đang hoạt động; HIGH-01 và HIGH-05 vì vậy là Partially Fixed.
- Không sửa rate limit đăng nhập, seeder, GD hoặc fixture test vì nằm ngoài phạm vi Phase 2.


### Vị trí code chính

- LamBaiKiemTra/index.vue:877-1018 (IndexedDB sync, serial queue callbacks, trạng thái), 1475-1524 (saveDraft/retry), 1616-1648 (beforeunload và onBeforeRouteLeave).
- draftSaveQueue.js:2-67 (serial/coalesced writes và xử lý rejection); examDraftBackup.js:1-150 (key scope, validation, TTL, IndexedDB).
- SinhVienKiemTraController.php:623-740 (saveDraft revision và INSERT có đủ khóa), 1130-1172 (tab event sau commit).
- SinhVienCanhBaoCameraController.php:350-410 (camera event có ID/metadata, broadcast sau commit).
- CanhBaoExamEvent.php:12-95 (event ID, payload và hai private channel).
- GiangVienApiController.php:1112-1245 và 1600-1740 (lịch sử camera/tab/protection có eventId); CanhBao/index.vue:1128-1180 (nhận, dedupe và subscribe event).
- vite.config.js:4-30 (proxy transport error); ExamEvidenceFileController.php:105-135 giữ binary response hiện có, không cần đổi.
- ExamProtectionAlertTest.php:22-44, 115-151; ExamEvidenceTest.php:17-50; vite-proxy.test.js:13-55 kiểm chứng các nhánh chính.


## XÁC MINH MÔI TRƯỜNG LIVE — HIGH-01 / HIGH-05 (14/11/2026)

### Trạng thái dịch vụ quan sát được

- Laravel API: đang chạy tại 127.0.0.1:8000 (PHP 8.5.12).
- MySQL: đang chạy tại 0.0.0.0:3306. php artisan db:show đã kết nối MariaDB 10.11.16, database smart_classroom_monitor, 77 bảng. Lệnh kết thúc exit 1 ở bước format vì PHP thiếu intl; Laravel cũng không ghi được storage/logs/laravel.log. Không chạy lệnh ghi DB hoặc migration.
- PHPUnit test DB: be/phpunit.xml dùng SQLite :memory:, APP_ENV=testing và broadcast null; không dùng MySQL. Các test tập trung đã chạy trên DB này.
- Reverb: cổng 8080 đang listen, khớp REVERB_PORT; HTTP GET / trả 404 (đường dẫn không phải WebSocket handshake). Chỉ xác nhận listener TCP/HTTP, chưa xác minh subscription/broadcast WebSocket.
- Vite: không listener lúc đầu. Khởi động Vite tạm trên 5173 để kiểm tra proxy live, sau đó dừng; hiện không còn LISTENING. Không thay đổi dữ liệu ứng dụng.

### HIGH-01 — kết quả và giới hạn

Đã xác minh bằng test SQLite: ba test liên quan pass gồm camera_and_tab_alert_event_targets_student_and_teacher_session_channels, screenshot_shortcut_is_recorded_broadcast_and_scoped_to_its_exam_session và route policy/channel test trước đó. Các test chứng minh event ID, private channels, idempotency screenshot và quyền/identity trong test framework.

Chưa xác minh end-to-end live: không mở được hai trình duyệt đã đăng nhập và không tạo test Sinh viên/Giảng viên, attempt, cảnh báo hoặc token trên MySQL thật. Vì không tạo dữ liệu thật và không có công cụ browser login trong phiên này, điện thoại, rời tab, PrintScreen, cập nhật hai trang realtime, reload và cross-class isolation phải chạy theo checklist dưới đây. HIGH-01 vẫn Partially Fixed.

### HIGH-05 — HTTP trực tiếp và qua Vite Proxy

Đã gọi cùng endpoint ảnh bằng bearer token giả và Accept: application/json:

| Đường gọi | HTTP | Content-Type | Ý nghĩa |
|---|---:|---|---|
| Laravel 127.0.0.1:8000/api/giang-vien/anh-man-hinh/124/file | 401 | application/json | Sanctum từ chối token không hợp lệ |
| Vite 127.0.0.1:5173/api/giang-vien/anh-man-hinh/124/file | 401 | application/json | Proxy chuyển kết quả xác thực Laravel |

- Request sau khi client hủy response ngay khi nhận headers cũng nhận response 401 tiếp theo; Vite vẫn phục vụ request sau đó. Đây là kiểm tra client abort với Laravel thật, nhưng body là JSON 401, không phải JPEG.
- Không có bearer token Sanctum hợp lệ trong phiên này, do đó không tải/đối chiếu JPEG #124/#125. Không đọc token từ trình duyệt, không tạo token mới và không ghi MySQL.
- Không chủ động dừng Laravel để thử upstream disconnect vì ảnh hưởng tiến trình đang chạy. Unit test upstream giả không được xem là bằng chứng Laravel thực tế.
- Chưa xác minh 200 JPEG (signature FF D8 FF, Content-Length chính xác), 403/404 với token hợp lệ hoặc upstream disconnect; HIGH-05 vẫn Partially Fixed.

Lỗi quan sát thêm: request không gửi Accept: application/json vào route Sanctum trả 500 vì middleware redirect tới route login chưa khai báo. Log ghi Route [login] not defined. Khi gửi Accept JSON như Axios, Laravel trả 401. Đây là lỗi redirect khi request không khai báo JSON, không phải lỗi tải ảnh có xác thực.

### Checklist kiểm thử thủ công còn lại

Chỉ chạy trên staging/database test riêng; không dùng bản ghi MySQL production. Mở Chrome Giảng viên và Chrome hoặc cửa sổ ẩn danh Sinh viên bằng tài khoản lớp test.

1. Bắt đầu phiên giám sát test. Mở cùng phiên ở Giảng viên → Phiên giám sát và Cảnh báo vi phạm. DevTools Network bật Preserve log, Console giữ mở.
2. Tạo từng sự kiện riêng: AI phát hiện điện thoại; rời tab; nhấn PrintScreen khi trình duyệt phát keyboard event. Xác nhận request Sinh viên tương ứng; PrintScreen phải ghi là nghi vấn, không khẳng định ảnh đã chụp.
3. Trong Network kiểm tra event request trả 201/200 theo hợp đồng và có eventId duy nhất. Trong Reverb WS Frames kiểm tra private exam-session.{id} và exam-monitor.{id}.student.{id} đã subscribed; không subscribe lớp/phiên khác.
4. Hai trang Giảng viên phải thêm cùng cảnh báo trong vài giây, cùng eventId, đúng Sinh viên/phiên, không nhân đôi. Reload hai trang và xác nhận API history có đúng một dòng cho eventId.
5. Dùng Giảng viên không phụ trách lớp test: private-channel auth bị từ chối; API lịch sử không trả dữ liệu lớp/phiên đó.
6. HIGH-05 dùng ảnh có sẵn trong staging. Gọi endpoint một lần qua Laravel 8000 và một lần qua Vite 5173 bằng token hợp lệ, không chép token vào báo cáo. Kỳ vọng 200, Content-Type image/jpeg, Content-Length bằng số byte nhận, đầu JPEG FF D8 FF, cuối thông thường FF D9, Cache-Control private/no-store. Xác nhận Blob thumbnail hiển thị, không có URL public.
7. Thử thiếu token → 401; Giảng viên ngoài lớp → 403/404 theo policy; ảnh không tồn tại/hết hạn → 404. Không retry 401/403/404; lỗi mạng tối đa số lần retry UI quy định (2). Sau khi hủy request, kiểm tra Vite vẫn trả request kế tiếp và không log ERR_STREAM_WRITE_AFTER_END.
8. Kiểm thử upstream disconnect trong instance staging Laravel/Vite riêng bằng cách đóng upstream giữa response; xác nhận Vite vẫn sống. Không dừng tiến trình 8000/8080 dùng chung.

Không sửa code trong lượt xác minh này. Không thay đổi dữ liệu thật, không chạy migration, không commit/push.

