# QUESTION BANK FULL REMOVAL REPORT

Date: 18/11/2026

## Kết quả

Đã gỡ module Ngân hàng câu hỏi khỏi UI và API đang chạy. Không xóa bảng, migration lịch sử, nội dung câu hỏi, bài tập, đáp án, bài nộp hoặc điểm. Các chức năng câu hỏi thủ công, import vào bài tập, trộn đề Phase 4 và hai hình thức thi vẫn được giữ.

## Files đã xóa

- `fe/src/components/GiangVien/NganHangCauHoi/index.vue`
- `be/app/Http/Controllers/GiangVienNganHangCauHoiController.php`
- `be/app/Models/NganHangCauHoi.php`
- `be/app/Models/NganHangDapAn.php`
- `be/app/Http/Requests/SaveNganHangCauHoiRequest.php`
- `be/tests/Feature/QuestionBankTest.php` (test CRUD/import riêng của module bị gỡ; thay bằng kiểm thử route 404 trong test tương thích)

## Files đã sửa

- `fe/src/layout/wrapper/lecturer.vue`: xóa menu Ngân hàng câu hỏi.
- `fe/src/router/index.js`: xóa route và lazy import module; URL cũ không còn route và fallback về trang gốc.
- `be/routes/api.php`: xóa import controller và các endpoint CRUD/duplicate/deactivate/import của ngân hàng.
- `be/app/Http/Controllers/GiangVienNhapCauHoiController.php`: xóa hàm preview chỉ dùng cho import vào ngân hàng; giữ nguyên preview/import Word, Excel, PDF gắn với bài tập.
- `be/tests/Feature/RetiredQuestionBankExamWorkflowTest.php`: kiểm tra các endpoint ngân hàng trả 404; giữ các test luồng tạo câu hỏi thủ công, import và làm bài không phụ thuộc Phase 3.

## Dependency và tương thích dữ liệu

Không còn tham chiếu executable trong `fe/src`, `be/app` hoặc `be/routes` tới component/controller/model/request của module, route `/ngan-hang-cau-hoi` hoặc hàm `previewQuestionBank`.

Giữ có chủ đích:

- Các migration lịch sử Phase 2, 3 và 4. Không sửa migration đã chạy. Phase 3 migration tạo schema lịch sử; Phase 4 migration đọc metadata `ngan_hang_de_tao` để chuyển các cờ trộn cũ sang bảng cấu hình chuẩn. Xóa/sửa các migration này sẽ làm database mới hoặc môi trường dựng lại không nhất quán.
- Các kiểm tra `Schema::hasTable('ngan_hang_de_tao')` read-only trong `BaiTapController`, `GiangVienApiController`, `SinhVienKiemTraController` và `GiangVienCauHinhTronDeController`. Chúng giữ tương thích với metadata đề Phase 3 nếu môi trường khác còn bản ghi cũ: ẩn bản nháp khỏi Sinh viên, hiện trạng thái/thời lượng lịch sử và khóa cấu hình trộn của đề đã công bố. Đây không phải API tạo hay quản lý ngân hàng; guard kiểm tra bảng trước khi query để không gây lỗi khi bảng không tồn tại.
- `ExamShuffleConfigTest` kiểm tra cờ trộn của đề Phase 3 được chuyển sang cấu hình Phase 4. `ExamAttemptOrder.php` và hai bảng thứ tự theo lượt làm thuộc Phase 4, không phụ thuộc model ngân hàng lúc runtime.

### Database đã kiểm tra (chỉ SELECT/metadata)

`.env` hiện trỏ tới database `smart_classroom_monitor`; chỉ đọc, không chạy migration hoặc ghi dữ liệu. `php artisan migrate:status` cho thấy migration Phase 2 (`2026_11_17_000001`), Phase 3 (`000002`, `000003`), Phase 4 (`000004`) và các migration hiện hành đều ở trạng thái Ran.

Số dòng tại thời điểm kiểm tra:

| Bảng | Số dòng |
|---|---:|
| `ngan_hang_cau_hoi` | 0 |
| `ngan_hang_dap_an` | 0 |
| `ngan_hang_de_preview` | 0 |
| `ngan_hang_de_tao` | 0 |
| `cau_hoi_ngan_hang_snapshot` | 0 |
| `bai_tap_cau_hinh_tron` | 2 |
| `bai_kiem_tra_luot_lam_cau_hoi` | 60 |
| `bai_kiem_tra_luot_lam_dap_an` | 240 |

Metadata khóa ngoại cho thấy snapshot gắn với `cau_hoi` và `ngan_hang_cau_hoi`; đáp án ngân hàng gắn với câu hỏi ngân hàng. Bảng cấu hình trộn Phase 4 gắn với `bai_tap`; hai bảng thứ tự gắn với attempt, câu hỏi và đáp án hiện hành. Không phát hiện bản ghi nội dung ngân hàng hoặc đề tạo từ ngân hàng trong database này, nhưng vẫn giữ schema để không phá lịch sử/migration.

## Database cleanup

Không có bảng nào bị drop. Dù hiện tại các bảng nội dung ngân hàng đang rỗng, migration của chúng đã chạy và có quan hệ khóa ngoại. Cleanup schema cần là một công việc riêng: backup database, kiểm tra lại dữ liệu/foreign keys ở từng môi trường, lập migration mới có kế hoạch khôi phục và phê duyệt rõ ràng. Không dùng `migrate:fresh`, `db:wipe` hoặc rollback migration lịch sử trên database thật. Không thể xóa riêng metadata Phase 3 mà sửa migration Phase 4 cũ; nếu cần dọn sau này, phải thiết kế migration tương thích có bước chuyển/giữ cờ trộn.

## Kiểm thử

- `RetiredQuestionBankExamWorkflowTest`, `ExamShuffleConfigTest`, `ExamModeTest`, `ExamReportTest`: **16 tests, 207 assertions PASS** trên cấu hình PHPUnit SQLite in-memory. Trong đó có kiểm tra tất cả API ngân hàng trả 404, API Phase 3 đã bị gỡ, tạo câu hỏi thủ công/import và bắt đầu thi không cần bảng Phase 3, trộn theo lượt, hai hình thức thi và xuất báo cáo.
- `php artisan route:list --path=ngan-hang`: không còn route phù hợp.
- `npm run test:unit`: **9/9 PASS**.
- Vite production build: **PASS**, 178 modules. Chạy `vite --configLoader runner build --outDir <TEMP>` do cách bundle config mặc định từng gặp giới hạn quyền ghi `.vite-temp` trên Windows.
- PHP syntax cho controller import và `routes/api.php`: PASS.
- `git diff --check`: PASS (chỉ có cảnh báo chuyển LF/CRLF ở một file đã được sửa từ trước).
- Full Laravel suite: **50 PASS, 5 FAIL**. Các lỗi hiện hữu ngoài phạm vi removal: rate-limit login kỳ vọng 429 nhận 401; seed test kỳ vọng 51 cảnh báo nhận 31; 2 test ảnh cần GD chưa cài; test rời tab kỳ vọng số lần vi phạm 1 nhận 0. Không sửa test để che các lỗi này.
- Các bài test dùng `RefreshDatabase` chạy migrations trên SQLite in-memory theo `be/phpunit.xml`; không chạy migration trên MySQL.

## Rủi ro và giới hạn

- Các bảng ngân hàng vẫn tồn tại trong MySQL và hiện rỗng theo lần kiểm tra; đây là chủ ý bảo toàn dữ liệu/schema, không phải module UI/API còn hoạt động.
- Nếu môi trường triển khai khác có bản ghi Phase 3, các guard tương thích sẽ tiếp tục đọc metadata đó. Không xác minh các database khác.
- Chưa chạy thử browser thủ công sau khi xóa route/menu. Build xác nhận bundle không còn import tới component đã xóa.
- Không thay đổi `.env`, dữ liệu, migrations; không commit hoặc push.
