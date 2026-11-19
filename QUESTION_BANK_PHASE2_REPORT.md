# Báo cáo Giai đoạn 2 — Ngân hàng câu hỏi

## Trạng thái

Đã triển khai module ngân hàng câu hỏi riêng cho Giảng viên. Các bảng/runtime câu hỏi theo đề `cau_hoi`, `dap_an`, `bai_tap`, `bai_nop` không bị sửa. Không triển khai chức năng chọn câu hỏi tạo đề hoặc trộn đề theo Sinh viên; đó là các giai đoạn tiếp theo.

## Thiết kế và bảo vệ dữ liệu

- Migration additive tạo `ngan_hang_cau_hoi` và `ngan_hang_dap_an`, gồm chủ sở hữu, nội dung, loại câu, chủ đề, độ khó, điểm mặc định/hướng dẫn chấm tự luận, trạng thái và version.
- Câu hỏi ngân hàng độc lập với bản ghi câu hỏi đã nằm trong đề. Sửa hoặc vô hiệu hóa trong ngân hàng không cập nhật/xóa câu hỏi của đề cũ.
- Không có thao tác xóa câu hỏi ngân hàng: thao tác tương ứng là đổi trạng thái `inactive`.
- Endpoint đặt trong nhóm `auth:sanctum` + middleware Giảng viên. Danh sách, chi tiết, sửa, nhân bản và vô hiệu hóa đều lọc theo `nguoi_tao_id` của tài khoản đăng nhập; ID chủ sở hữu không được nhận từ payload.
- APIs ngân hàng hiện chỉ dành cho Giảng viên, nên đáp án đúng và hướng dẫn tự luận không được trả qua API Sinh viên hiện tại.

## File đã tạo/sửa

**Backend tạo mới:**

- `be/database/migrations/2026_11_17_000001_create_question_bank_tables.php`
- `be/app/Models/NganHangCauHoi.php`
- `be/app/Models/NganHangDapAn.php`
- `be/app/Http/Requests/SaveNganHangCauHoiRequest.php`
- `be/app/Http/Controllers/GiangVienNganHangCauHoiController.php`
- `be/tests/Feature/QuestionBankTest.php`

**Frontend tạo mới:**

- `fe/src/components/GiangVien/NganHangCauHoi/index.vue`

**Tích hợp vào file có sẵn:**

- `be/routes/api.php` — routes mới, không đổi routes câu hỏi cũ.
- `be/app/Http/Controllers/GiangVienNhapCauHoiController.php` — thêm action preview cho ngân hàng, tái sử dụng parser hiện hành; các action import theo đề không đổi.
- `fe/src/router/index.js` — route trang ngân hàng.
- `fe/src/layout/wrapper/lecturer.vue` — mục điều hướng Giảng viên.

## APIs mới

Tất cả nằm dưới `/api/giang-vien` và yêu cầu Sanctum + quyền Giảng viên:

| Method | Endpoint | Mục đích |
|---|---|---|
| GET | `/ngan-hang-cau-hoi` | Tìm kiếm/phân trang; lọc `q`, `chu_de`, `loai_cau_hoi`, `do_kho`, `trang_thai`. |
| POST | `/ngan-hang-cau-hoi` | Tạo câu hỏi trắc nghiệm hoặc tự luận. |
| GET | `/ngan-hang-cau-hoi/{id}` | Xem chi tiết câu hỏi thuộc Giảng viên hiện tại. |
| PUT | `/ngan-hang-cau-hoi/{id}` | Cập nhật, tăng version. |
| POST | `/ngan-hang-cau-hoi/{id}/duplicate` | Nhân bản nội dung/đáp án thành ID mới. |
| PATCH | `/ngan-hang-cau-hoi/{id}/deactivate` | Vô hiệu hóa mềm; không xóa dữ liệu. |
| POST | `/ngan-hang-cau-hoi/import-preview` | Preview `.xlsx`, `.docx`, `.pdf` bằng parser đang dùng; chưa ghi DB. |
| POST | `/ngan-hang-cau-hoi/import-save` | Validate và lưu preview thành lô trong một transaction. |

Trắc nghiệm yêu cầu 2–6 phương án và index của một phương án đúng. Tự luận yêu cầu điểm mặc định lớn hơn 0, tối đa 10, chính xác đến 0,01 và có hướng dẫn chấm. Chủ đề tối đa 120 ký tự; độ khó giới hạn `de`, `trung_binh`, `kho`.

## Giao diện

Trang `/giang-vien/ngan-hang-cau-hoi` hỗ trợ thêm/sửa, xem lựa chọn và đáp án mẫu phía Giảng viên, tìm kiếm, lọc chủ đề/loại/độ khó/trạng thái, phân trang, nhân bản, vô hiệu hóa và preview/import Excel/Word/PDF. Nút tạo câu hỏi thủ công và import theo đề cũ vẫn giữ nguyên.

## Kết quả kiểm thử

- PHP syntax check cho migration, Models, FormRequest, controller ngân hàng, importer, routes và Feature Test: **PASS**.
- `php artisan test --filter=QuestionBankTest`: **PASS — 6 tests, 32 assertions**. Có kiểm tra tạo/sửa/version/filter/duplicate/deactivate, quyền sở hữu chéo, loại bỏ `nguoi_tao_id` giả mạo, Sinh viên bị từ chối, validation, import XLSX qua parser hiện hành và import invalid không tạo bản ghi một phần.
- `php artisan test --filter=ExamScoreAllocationTest`: **PASS — 4 tests, 10 assertions**. Các trường hợp chia điểm trắc nghiệm, tự luận và import câu trắc nghiệm cũ vẫn qua.
- Frontend build: **PASS** bằng `npx vite build --configLoader runner --outDir .codex-build-test` (173 modules transformed). Thư mục build kiểm thử sau đó đã được dọn.
- Lệnh build mặc định ban đầu bị Windows trả `EPERM` khi Vite ghi file cấu hình tạm và dọn `fe/dist/.gitkeep`; build riêng thành công khi dùng config loader runner và output directory riêng. Không thay đổi quyền hoặc xóa nội dung `dist` hiện có.
- Test chạy với PHPUnit `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` và `RefreshDatabase`; migration mới được xác minh trong SQLite test. Không chạy migration trên MySQL `smart_classroom_monitor`, không tạo/sửa dữ liệu thật.

## Kích hoạt an toàn

1. Sao lưu và xác nhận DB đích trước khi triển khai.
2. Chạy migration mới trước trên môi trường staging/test tách biệt, xác nhận hai bảng và khóa ngoại.
3. Chỉ khi staging đạt yêu cầu và có phê duyệt triển khai mới chạy migration trên môi trường thật: `php artisan migrate --path=database/migrations/2026_11_17_000001_create_question_bank_tables.php`.
4. Triển khai Backend và Frontend cùng phiên bản; đăng nhập Giảng viên rồi mở mục **Ngân hàng câu hỏi**.
5. Không dùng `migrate:fresh`, `db:wipe` hay xóa bảng/dữ liệu hiện hữu.

Migration mới **chưa chạy trên MySQL thật**. Giai đoạn 3 (tạo đề từ ngân hàng), provenance/snapshot vào đề và Giai đoạn 4 (trộn theo attempt) chưa được triển khai.

Kiểm thử import thực tế trong suite hiện xác minh file XLSX; DOCX/PDF dùng lại parser preview hiện có nhưng chưa có fixture kiểm thử riêng trong lần này. Chưa kiểm thử tương tác trình duyệt thủ công.
