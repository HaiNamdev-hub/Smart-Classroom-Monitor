# Báo cáo sửa lỗi identity danh sách bài tập Sinh viên

Ngày: 19/11/2026

## Nguyên nhân gốc

Schema `thanh_vien_lop.sinh_vien_id` tham chiếu `nguoi_dung.id` (migration `2026_09_21_000004_create_thanh_vien_lop_table.php`). `bai_nop.sinh_vien_id` cũng lưu account ID. Ngược lại, `bai_kiem_tra_luot_lam.sinh_vien_id`, draft, camera/vi phạm và các sự kiện bảo vệ bài thi dùng `sinh_vien.id`.

`BaiTapController::getBaiTapSinhVien()` trước đó tìm đúng hồ sơ `sinh_vien.id` nhưng dùng nhầm ID này cho hai truy vấn có FK account:

1. Lọc `thanh_vien_lop.sinh_vien_id`, làm danh sách bài được giao rỗng hoặc có thể khớp nhầm thành viên khi các ID trùng tình cờ.
2. Lọc quan hệ `baiNops`, làm cờ `da_nop`/metadata bài nộp không đáng tin cậy.

Đã đổi hai truy vấn trên sang account ID lấy trực tiếp từ `$request->user()->id`. Profile ID vẫn được xác nhận tồn tại và không bị thay thế trong dữ liệu attempt/draft/camera.

## Rà soát các truy vấn identity liên quan

- `LopHocController::getLopHocSinhVien()` dùng authenticated `nguoi_dung.id` để lọc membership: đúng schema.
- `SinhVienController::getTrangChu()` dùng authenticated `nguoi_dung.id` cho `thanh_vien_lop`, `bai_nop` và quan hệ điểm: đúng schema theo FK/model.
- `SinhVienKiemTraController` chuyển từ authenticated account sang profile ID cho attempt, draft, camera/vi phạm; chuyển ngược sang account ID khi ghi/kiểm tra `bai_nop`: đúng loại ID.
- `BaiNopController::nopBai()` dùng account ID cho membership và file submission: đúng.
- `SinhVienCanhBaoCameraController` và `SinhVienBaoVeBaiKiemTraController` dùng profile ID cho các bảng attempt/alert và account ID cho membership/submission: đúng theo schema.
- Không thấy thêm truy vấn cùng kiểu sai ở các controller/service được rà trong phạm vi này. Không sửa các module trên.

## File thay đổi

- `be/app/Http/Controllers/BaiTapController.php`: membership và eager-load submission lọc theo authenticated `nguoi_dung.id`.
- `be/tests/Feature/StudentAssignmentIdentityRegressionTest.php`: ba test cho ID khác, quyền ngoài lớp và ID trùng.
- `performance-tests/Invoke-PerformancePreflight.ps1`: smoke test thực trên clone SQLite, không chạm template hoặc MySQL vận hành.
- `performance-tests/exam-flow.k6.js`: assignment-list correctness gate trước khi start/save/submit (đã được thêm ở bước chuẩn bị performance).
- `SMART_CLASSROOM_PERFORMANCE_TEST_REPORT.md`: cập nhật trạng thái và kết quả thực tế.

## Regression tests mới

`StudentAssignmentIdentityRegressionTest` tạo chính xác tình huống quan trọng:

- Giảng viên `nguoi_dung.id=1`.
- Sinh viên `nguoi_dung.id=2`.
- Hồ sơ Sinh viên `sinh_vien.id=1`.
- Membership lưu `thanh_vien_lop.sinh_vien_id=2`.

Test dùng login API thật trong Laravel Feature Test rồi kiểm tra: lớp, danh sách bài, chi tiết, start attempt, save draft, restore draft, submit, câu trả lời và điểm đúng sinh viên. Test ngoài lớp không thấy lớp/bài và nhận 404 khi mở trực tiếp. Test tương thích bổ sung xác nhận account ID và profile ID trùng vẫn hoạt động.

Kết quả riêng: **3 tests, 49 assertions PASS**.

## API smoke trước/sau

Được chạy trên PHP built-in Laravel server bind `127.0.0.1:8001`, với process-only env trỏ tới clone mới từ template SQLite; bearer token không ghi ra console/log.

| Thao tác | Trước sửa | Sau sửa |
|---|---|---|
| Login fixture | HTTP 200 | PASS HTTP 200 |
| Danh sách lớp | Chưa được kiểm tra ở smoke trước | PASS, 1 lớp |
| Danh sách bài thi | HTTP 200 nhưng `data=[]` | PASS, có exam fixture ID 1 |
| Chi tiết bài | HTTP 200, 1 câu hỏi | PASS, 1 câu hỏi |
| Start | Chưa gọi do assignment-list gate fail | PASS, `IN_PROGRESS`, dùng attempt fixture hiện có (`duplicate=true`) |
| Draft save/restore | Chưa gọi | PASS, revision và câu trả lời khôi phục đúng |
| Submit | Chưa gọi | PASS, `daNop=true`, điểm 10 |
| Trạng thái sau submit | Chưa gọi | PASS, assignment `da_nop=true` |
| Hậu kiểm clone | Không có ghi | PASS: submissions=1, answers=1, scores=1 |

Template SQLite gốc được giữ nguyên; preflight làm việc trên clone mới. Không chạy migration trên MySQL, không sửa `.env`, không xóa dữ liệu.

## Kiểm thử hồi quy đầy đủ

- Laravel: **66 tests, 726 assertions PASS** (`php artisan test`, SQLite in-memory).
- Frontend unit: **12/12 PASS** (`npm run test:unit`).
- Vite: **PASS**, 180 modules, output tạm tại `performance-tests/results/identity-fix-build`; output tạm tránh EPERM trong thư mục `fe/dist`/Vite config temp.
- PHP lint: `BaiTapController.php` và test mới không lỗi cú pháp.
- `git diff --check`: PASS.

Không có thay đổi điểm, schema, quyền ngoài lớp, API làm bài, lưu nháp hoặc nộp bài. Chỉ danh sách bài và cờ bài nộp được sửa đúng FK.

## Trạng thái benchmark/k6

Preflight chức năng đã **PASS**, nhưng k6 chưa có trong PATH hoặc `performance-tests/tools/k6/k6.exe`; do đó không chạy benchmark 1/10/30/50 VU và không có số liệu hiệu năng. Đây là **BLOCKED (tool chưa cài)**, không phải test thất bại.

Hướng dẫn portable:

1. Tải Windows standalone ZIP từ trang [Grafana k6 Releases](https://github.com/grafana/k6/releases), dựa theo hướng dẫn [Install k6 chính thức](https://grafana.com/docs/k6/latest/set-up/install-k6/).
2. Chọn asset Windows phù hợp kiến trúc máy; kiểm tra SHA-256 của archive với checksum được công bố cho release trước khi giải nén.
3. Đặt `k6.exe` tại `F:\Smart Classroom Monitor\performance-tests\tools\k6\k6.exe` (thư mục này đã ignore trong Git), không cần sửa PATH toàn máy.
4. Chạy `& '.\performance-tests\tools\k6\k6.exe' version`, sau đó dry-run runner và chỉ bắt đầu `-MaxVus 1`. Tăng tải từng bậc sau khi kiểm tra summary và integrity.

SQLite/PHP built-in server không chứng minh lock/deadlock MySQL. Cần MySQL staging cô lập để đánh giá 30/50 VU và concurrency thực. Không dùng `smart_classroom_monitor`.
