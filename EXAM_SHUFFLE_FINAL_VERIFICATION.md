# Kiểm chứng cuối — cấu hình và trộn đề

Ngày kiểm tra: 17-11-2026

## Kết quả theo yêu cầu

| Hạng mục | Kết quả | Bằng chứng / giới hạn |
|---|---|---|
| Gỡ UI/API tạo đề từ ngân hàng Phase 3 | **PASS** | Không còn route Frontend, component hay controller Phase 3. `php artisan route:list --path=ngan-hang-de` không tìm thấy route; feature test xác nhận URL cũ trả 404. |
| Ngân hàng câu hỏi Phase 2 | **PASS** | 8 API routes ngân hàng vẫn được liệt kê; `QuestionBankTest` CRUD, phân quyền và import vẫn PASS. |
| Hai tùy chọn tại trang quản lý câu hỏi | **PASS (source/build)** | `CauHinhTronDe.vue` được render trong `TaoBaiKiemTra.vue` tại route `/giang-vien/bai-tap/:id/cau-hoi`; test API mặc định tắt và lưu được cả hai cờ. Chưa kiểm tra tương tác bằng trình duyệt thật. |
| Migration cấu hình shuffle | **PASS (schema), STAGING NOT VERIFIED** | Migration additive tạo `bai_tap_cau_hinh_tron`, PK/FK theo `bai_tap_id`, hai cờ boolean mặc định false và chuyển cờ legacy. SQLite test migrations PASS; schema MariaDB hiện tại có đủ cột. Không chạy migration trong lượt này. |
| DB staging riêng | **FAIL / chưa có cấu hình xác nhận** | `be/.env` là file cấu hình duy nhất tìm thấy và trỏ tới `smart_classroom_monitor`; PHPUnit dùng SQLite `:memory:`. Không có MySQL staging được xác nhận, nên không chạy migration staging hay thử live trên hai tài khoản. |
| Lưu/đọc cấu hình theo bài | **PASS trên SQLite test** | GET mặc định false, PUT lưu hai cờ, quyền chủ sở hữu và khóa sau khi thi bắt đầu được test. |
| Hai Sinh viên nhận thứ tự câu hỏi và đáp án riêng | **PASS mô phỏng Feature Test** | Test dùng hai tài khoản độc lập và random index xác định; so sánh thứ tự cho từng câu, cả câu hỏi và phương án đều khác nhau. Đây là test tích hợp Laravel/SQLite, không phải hai trình duyệt. |
| Refresh giữ nguyên đề đã giao | **PASS mô phỏng Feature Test** | Start lần nữa và GET đề cho cùng attempt giữ thứ tự câu hỏi lẫn đáp án đã lưu. |
| Chấm theo ID đáp án | **PASS mô phỏng Feature Test** | Sinh viên thứ hai nộp ID đáp án đúng sau khi trộn; điểm đạt 10/10. |
| Đề thủ công và import | **PASS một phần** | Tạo câu hỏi thủ công, import XLSX, preview DOCX và các test phân bổ điểm đều PASS. Nhánh PDF trong controller gọi `Smalot\\PdfParser`, nhưng chưa có PDF fixture chạy qua API nên **PDF chưa được xác minh thực thi**. |
| Tên, MSSV, bài nộp, thời gian và điểm | **PASS mô phỏng Feature Test** | API danh sách bài nộp trả đúng tên sinh viên thử nghiệm, MSSV, tiêu đề đề, thời gian nộp và 10 điểm. |
| API không phụ thuộc bảng Phase 3 | **PASS mô phỏng Feature Test** | Test bỏ các bảng Phase 3 và bảng cấu hình khỏi SQLite rồi gọi API danh sách Giảng viên, danh sách Sinh viên, GET config và bắt đầu đề; không phát sinh 500. Config fallback false; PUT trả 503 khi bảng config thiếu. |
| Build và unit test Frontend | **PASS** | `npm run build`: 175 modules, hoàn tất. `npm run test:unit`: 8/8 PASS. |
| Full Laravel suite | **PARTIAL** | 47 PASS, 5 FAIL; các lỗi được phân loại dưới đây. Các test liên quan shuffle/question-bank đều PASS. |

## Tình trạng database hiện tại (chỉ đọc)

`DB_DATABASE` trong `.env` là `smart_classroom_monitor`; không có file `.env.staging` hoặc cấu hình MySQL staging riêng. PHPUnit cấu hình `sqlite` với `:memory:`.

Trong lần kiểm tra trước, báo cáo ghi migration `2026_11_17_000004_create_exam_shuffle_configuration_table` Pending. Lần đọc hiện tại bằng `php artisan migrate:status` và PDO SELECT cho thấy migration đã có trong bảng `migrations`, bảng `bai_tap_cau_hinh_tron` tồn tại và có 1 bản ghi. Không thể xác định ai hoặc thời điểm đã áp dụng thay đổi này; **Codex không chạy migration trong lượt kiểm tra cuối này**. Không có thao tác ghi/xóa database thật.

`php artisan migrate:status` hiện báo Phase 2, Phase 3, mapping Phase 4 và migration 000004 đều Ran. Do database đang được cấu hình là `smart_classroom_monitor` chứ không phải staging, không thực hiện bất kỳ migration nào. Trước khi dùng thật, cần chủ hệ thống xác nhận lịch sử migration của môi trường này và chuẩn bị một MySQL staging riêng để chạy lại kịch bản.

## Kết quả kiểm thử chi tiết

- `php artisan test --filter='ExamShuffleConfigTest|RetiredQuestionBankExamWorkflowTest|QuestionBankTest|ExamScoreAllocationTest'`: **17 tests, 120 assertions PASS**.
- `php artisan test`: **47 PASS, 5 FAIL**:
  - `DangNhapTest::login_is_rate_limited`: test kỳ vọng 429 nhưng nhận 401.
  - `DatabaseSeederTest`: kỳ vọng 51 cảnh báo, fixture tạo 31.
  - Hai test upload trong `ExamEvidenceTest`: môi trường PHP thiếu GD.
  - `ReportTabLeaveTest`: fixture không tạo attempt cần cho endpoint.
- `npm run test:unit`: **8 PASS**.
- `npm run build`: **PASS**, Vite 175 modules.
- PHP syntax cho `ExamAttemptOrder`, controllers cấu hình/thi, migration và feature tests: **PASS**.
- Route scan: không còn route `ngan-hang-de`; 8 route `ngan-hang-cau-hoi` Phase 2 còn hoạt động.

## Import file

- Excel: preview và lưu qua API import cũ của đề — **PASS**.
- Word DOCX: preview qua API import cũ — **PASS**.
- PDF: code có nhánh đọc `pdf` bằng `Smalot\\PdfParser`, nhưng repository không có fixture PDF và môi trường không có PDF writer; **chưa xác minh API với file PDF thực tế**.
- Chưa thử giao diện upload bằng trình duyệt.

## Những bước còn thiếu trước khi dùng thực tế

1. Tạo/xác nhận MySQL staging riêng và cấu hình kết nối tách biệt với `smart_classroom_monitor`.
2. Trên staging, xác minh migration 000004 đã chạy đúng một lần, dữ liệu cờ legacy được chuyển đúng, và GET/PUT theo từng `bai_tap_id` hoạt động. Không chạy lại migration trên DB hiện tại.
3. Chạy kiểm thử bằng hai trình duyệt/session thật: hai Sinh viên, refresh, autosave/nộp; Giảng viên kiểm tra thứ tự, tên, MSSV và điểm.
4. Tạo PDF fixture hợp lệ rồi kiểm tra API preview PDF thực tế; kiểm tra DOCX/XLSX qua giao diện trình duyệt.
5. Phân loại và xử lý riêng 5 Laravel test đang FAIL trước khi yêu cầu toàn bộ suite xanh; không sửa test chỉ để đổi kết quả.
6. Xác nhận các dịch vụ chạy thực tế và kiểm tra API/Reverb trong staging nếu cần giám sát realtime.

Vì chưa có kiểm thử MySQL staging, hai trình duyệt và PDF thực tế, tính năng **chưa được xác nhận production-ready**. Không commit/push và không thay đổi dữ liệu thật trong lượt kiểm tra này.
