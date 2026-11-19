# Smart Classroom Monitor — Báo cáo kiểm thử hiệu năng cục bộ

Ngày kiểm tra: 19/11/2026  
Phạm vi: chuẩn bị bộ đo tải an toàn, xác minh API trên bản sao SQLite cô lập và chạy kiểm tra hồi quy tự động. Không sửa logic sản phẩm, không truy cập ghi vào MySQL vận hành, không commit/push.

## Kết luận

**Lỗi identity assignment đã được sửa và preflight HTTP end-to-end trên clone SQLite đã PASS.** Template SQLite và fixture giả được xác minh; Laravel chạy được trên loopback `127.0.0.1:8001`. Trước sửa, danh sách bài giao trả rỗng; sau sửa, lớp và bài thi hiển thị, lượt làm/draft/restore/submit hoạt động và xác minh đúng 10 điểm.

k6 chưa được cài hoặc phát hiện trong PATH. Vì vậy benchmark 1 VU bằng k6 và các bậc 10/30/50 vẫn BLOCKED, không có số liệu tải, p50/p95/p99, RPS, error rate hay CPU/RAM dưới tải. Không chạy các bậc tải và không dùng kết quả HTTP smoke như benchmark.

## Môi trường đã xác minh

| Thành phần | Kết quả |
|---|---|
| Windows shell | PowerShell |
| PHP CLI | 8.5.12 (`C:\xampp\php\php.exe`) |
| Composer | 2.8.12 |
| Node.js / npm | 22.14.0 / 10.9.2 |
| Laravel | 12, theo dự án |
| Vite | 6.4.3 |
| k6 | `k6 version` không nhận diện; chưa cài |
| MySQL/MariaDB CLI | `mysql`/`mariadb` không có trong PATH; PDO MySQL và PDO SQLite có |
| CPU | 16 logical processors theo `[Environment]::ProcessorCount` |
| RAM/CPU counters | Không đọc được qua CIM/performance counters do quyền môi trường; không suy đoán số liệu |
| Dịch vụ lúc kiểm tra | Không thấy listener trên 8000, 8001, 5173, 3306 hoặc 6379 trong lần kiểm tra; kết quả cổng có thể bị giới hạn quyền. Smoke server 8001 sau đó được khởi động riêng và đã dừng |

`.env` đang khai báo MySQL `smart_classroom_monitor`; file không được sửa. Các thao tác ứng dụng/test dùng cấu hình process-only trỏ SQLite. Không chạy migration trên MySQL và không tạo dữ liệu trong DB vận hành.

## Database và dữ liệu thử

Đã dùng script có guard để tạo `be/database/performance-test/template.sqlite` và fixture credential tổng hợp bị Git ignore trong `performance-tests/data/students.json`. Seeder tạo 100 tài khoản Sinh viên giả, 1 Giảng viên, 1 lớp, 1 đề 10 điểm, một câu trắc nghiệm, bốn phương án và 100 lượt làm. Không có tài khoản/dữ liệu thật.

`verify-sqlite.php` mở file theo chế độ chỉ đọc và xác nhận marker `SCM_PERF_TEST_DATASET_V1` cùng số lượng bảng fixture: users=101, student_profiles=100, class_members=100, classes=1, exams=1, questions=1, options=4, attempts=100; submissions=0, submitted_answers=0, full_scores=0. Kết quả: **PASS**. Kịch bản API dùng thêm bản sao SQLite riêng trong `be/database/performance-test/runs/`; không ghi vào template. Fixture có mật khẩu thử nghiệm, không được chia sẻ hoặc commit.

Không có MySQL staging được xác minh. SQLite chỉ phù hợp smoke/API local và không chứng minh locking/deadlock MySQL.

## API smoke test thực tế trên Laravel

Laravel được khởi động bằng PHP built-in server bind vào `127.0.0.1:8001`, với DB clone SQLite, `APP_ENV=performance`, `DB_URL`/`DATABASE_URL` rỗng, broadcast `null`, và MySQL fallback bị khóa về loopback port 1. Request unauthenticated tới `/api/check-token` gửi `Accept: application/json` trả **401** như dự kiến. Lần chạy trước thiếu `Accept` và Laravel cố redirect đến route `login` không có; đó là lỗi cách gọi smoke check, không phải lỗi API khi client khai báo JSON.

Trên một clone dùng fixture giả:

| Bước | Kết quả thực tế |
|---|---|
| POST `/api/dang-nhap` bằng tài khoản giả | HTTP 200, nhận Bearer token trong bộ nhớ test; không ghi token ra log |
| GET `/api/sinh-vien/bai-tap` trước sửa | HTTP 200, `status=true`, nhưng `data` rỗng |
| GET `/api/sinh-vien/bai-kiem-tra/1` trước sửa | HTTP 200, `status=true`, có 1 câu hỏi |
| Clone SQLite mới sau sửa | Fixture verifier PASS, template gốc không bị dùng để ghi |
| GET `/api/sinh-vien/lop-hoc` sau sửa | PASS, 1 lớp |
| GET `/api/sinh-vien/bai-tap` sau sửa | PASS, có bài thi fixture ID 1 |
| GET bài thi / POST bắt đầu | PASS, có 1 câu hỏi; `IN_PROGRESS`, attempt fixture được dùng lại (`duplicate=true`) |
| PUT draft / GET restore | PASS, revision 64 ký tự và câu trả lời được khôi phục |
| POST submit | PASS, `daNop=true`, điểm 10 |
| Danh sách sau nộp + verifier clone | PASS, `da_nop=true`; submissions=1, submitted_answers=1, full_scores=1 |

**Root cause:** `BaiTapController::getBaiTapSinhVien()` tra hồ sơ để xác nhận tài khoản Sinh viên rồi lấy `sinh_vien.id`, sau đó dùng ID hồ sơ để lọc `thanh_vien_lop.sinh_vien_id`, dù migration `2026_09_21_000004_create_thanh_vien_lop_table.php` khai báo FK này tham chiếu `nguoi_dung.id`. Việc lọc danh sách `baiNops` trong cùng API cũng dùng nhầm profile ID, có thể làm trạng thái đã nộp hiển thị sai. Đã sửa controller để dùng account ID cho membership và bài nộp; giữ ID hồ sơ cho nghiệp vụ attempt. Regression tests xác nhận trường hợp `nguoi_dung.id=2`, `sinh_vien.id=1`, đồng thời xác nhận tài khoản ngoài lớp không được xem bài. Chi tiết ở [STUDENT_ASSIGNMENT_IDENTITY_FIX_REPORT.md](STUDENT_ASSIGNMENT_IDENTITY_FIX_REPORT.md).

## Công cụ và thay đổi trong đợt này

Các tài sản chuẩn bị trước đó được rà soát: `Prepare-PerformanceDb.ps1`, `verify-sqlite.php`, `Invoke-PerformanceStages.ps1`, `exam-flow.k6.js`, `PerformanceTestSeeder.php` và hướng dẫn Windows. Thêm `Invoke-PerformancePreflight.ps1` để tự động clone SQLite, mở Laravel trên 8001, gọi login/classes/assignments/detail/start/draft/restore/submit, kiểm tra trạng thái nộp và xác minh clone sau khi hoàn tất; token không được log. Script từ chối cổng đang dùng và cấu hình cache, giữ template không đổi.

Đã cập nhật:

- `performance-tests/exam-flow.k6.js`: sau login, gọi danh sách bài được giao và yêu cầu fixture exam xuất hiện, không có bài ngoài fixture; nếu không khớp thì dừng **trước start/save/submit**. Thêm think time có dao động giữa các thao tác để tránh bắn luồng tức thời.
- `performance-tests/README.md`: mô tả assertion danh sách bài và think time.
- `performance-tests/Invoke-PerformancePreflight.ps1`: smoke test tích hợp chỉ trên clone SQLite.
- `be/app/Http/Controllers/BaiTapController.php` và `be/tests/Feature/StudentAssignmentIdentityRegressionTest.php`: identity fix cùng regressions được ghi trong báo cáo chuyên biệt.
- `SMART_CLASSROOM_PERFORMANCE_TEST_REPORT.md`: báo cáo này.

Script runner đã có safety gate: chỉ loopback port 8001, marker SQLite, k6 binary tồn tại, clone riêng theo từng bậc, yêu cầu xem xét và gõ `CONTINUE` giữa bậc. Script tải hiện tại dùng PHP built-in server đơn tiến trình, nên các số liệu (khi chạy được) chỉ phản ánh profile local, không đại diện PHP-FPM nhiều worker.

**Phạm vi còn thiếu trong k6:** luồng hiện tại bao phủ login, assignment list, exam detail, start, draft save và submit. Chưa có kịch bản tải riêng cho khôi phục draft sau reconnect, rời tab/protection alert, thông tin phiên giám sát, API giáo viên (danh sách Sinh viên/cảnh báo/kết quả) hoặc xuất Excel. Vì chưa có phiên giám sát fixture và tài khoản giáo viên trong fixture JSON, các API đó không được gọi trong smoke test. Cần thêm fixture/định nghĩa API và kịch bản read-only/write phù hợp trước khi tuyên bố đã kiểm thử toàn bộ API nghiệp vụ.

## Kiểm thử đã chạy

| Kiểm thử | Kết quả |
|---|---|
| SQLite template marker/count verifier | PASS |
| Laravel synthetic login và API smoke sau sửa | PASS: class list, assignment list, detail, start, save, restore, submit, score=10 và final submitted flag |
| Regression identity | **3 tests / 49 assertions PASS**; covers account/profile IDs khác nhau, ngoài lớp bị chặn, và ID trùng |
| PHPUnit/Laravel đầy đủ | **66 tests, 726 assertions PASS**, `php artisan test`; test dùng SQLite in-memory (`be/phpunit.xml`) |
| Frontend unit tests | **12 PASS, 0 FAIL** (`npm run test:unit`) |
| Vite production build | PASS khi chạy `vite build --configLoader=runner --outDir ../performance-tests/results/frontend-build` (180 modules). Build mặc định bị EPERM khi Vite ghi `.vite-temp` rồi xóa `fe/dist/.gitkeep`; alternate output dir tránh tác động vào thư mục build hiện tại |
| `git diff --check` | PASS |
| k6 run / 1, 10, 30, 50 VU | BLOCKED: k6 chưa cài; correctness preflight đã PASS |
| PHPUnit trên MySQL staging | NOT RUN: không có staging MySQL cô lập được xác nhận |
| WebRTC/Reverb thật, MediaPipe/camera, trình duyệt nhiều người | NOT TESTED; HTTP VU không mô phỏng video hoặc inference |

Build mặc định không được lặp lại bằng cách xóa `dist`; không có file dự án hoặc dữ liệu bị xóa. Output build thay thế nằm dưới `performance-tests/results/` (được ignore).

## Số liệu hiệu năng

Không có benchmark tải thực tế. Do đó RPS, p50/p95/p99, error rate dưới tải, autosave/submit success rate dưới tải, CPU/RAM khi tải, MySQL connections, lock waits/deadlocks, WebSocket connections, ICE success rate, FPS và inference latency đều **chưa đo/không có số liệu**. Không dùng các số liệu trước đây hoặc thời gian smoke request để suy ra hiệu năng.

## Các bước cần hoàn thành trước benchmark

1. Cài k6 portable vào `performance-tests/tools/k6/`; hiện chưa tải/cài binary. Hướng dẫn chính thức của Grafana: [Install k6](https://grafana.com/docs/k6/latest/set-up/install-k6/) có standalone binary cho Windows và trang [GitHub Releases](https://github.com/grafana/k6/releases). Tải ZIP Windows tương ứng, xác minh SHA-256 theo release asset/checksum, giải nén `k6.exe` vào thư mục ignore đã định sẵn rồi xác minh `k6.exe version`.
2. Sau khi cài, chạy `Invoke-PerformanceStages.ps1 -MaxVus 1 -RunConfirmedIsolatedLoad`; chỉ qua 1 VU nếu assertion và hậu kiểm DB pass. Tăng tải từng bậc có review.
3. Không chuyển sang 30/50 cho đến khi có MySQL staging riêng cùng phiên bản/cấu hình gần môi trường đích, seed data tổng hợp mới, quan sát lock/deadlock và có resource headroom. Không trỏ runner sang port 8000.
4. Thực hiện riêng bài test browser/WebRTC/Reverb/MediaPipe bằng thiết bị/trình duyệt thật và thu thập `RTCPeerConnection.getStats()`, FPS, latency, số receiver và CPU/RAM. Không gọi đó là k6 test.

## Mức độ sẵn sàng

- **Bộ fixture SQLite cô lập:** sẵn sàng cho smoke và phát triển script; dữ liệu giả được marker kiểm tra.
- **API functional preflight:** PASS sau sửa trên SQLite clone.
- **Benchmark API 1/10/30/50:** k6 binary còn thiếu; chưa có stage tải nào chạy. MySQL staging vẫn cần thiết để kết luận về concurrency/locking trên MySQL, nhất là ở 30/50 VU.
- **Kết luận MySQL production capacity hoặc 10–50 camera:** chưa thể đưa ra. Cần MySQL staging và kiểm thử browser/video thực tế.
