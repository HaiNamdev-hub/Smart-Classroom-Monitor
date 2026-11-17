# Báo cáo Giai đoạn 3 — Tạo đề từ Ngân hàng câu hỏi

## Trạng thái

Đã triển khai luồng kiểm tra kho → preview có snapshot → xác nhận tạo đề nháp → Giảng viên chủ động lập lịch/công bố. Không triển khai trộn theo từng Sinh viên (Giai đoạn 4). Luồng tạo câu hỏi thủ công cũ và `TaoBaiKiemTra.vue` không bị thay thế.

## File tạo và sửa

**Tạo mới:**

- `be/database/migrations/2026_11_17_000002_create_question_bank_exam_workflow_tables.php`
- `be/app/Http/Controllers/GiangVienNganHangDeController.php`
- `be/tests/Feature/QuestionBankExamTest.php`
- `fe/src/components/GiangVien/BaiTap/TaoDeTuNganHang.vue`
- `QUESTION_BANK_PHASE3_REPORT.md`

**Đã sửa:**

- `be/routes/api.php` — thêm routes cho kiểm tra số lượng, preview, confirm và lập lịch.
- `be/app/Http/Controllers/GiangVienApiController.php` — danh sách Giảng viên nhận trạng thái nháp; ngày lịch placeholder không hiển thị như lịch thật.
- `be/app/Http/Controllers/BaiTapController.php` — ẩn đề nháp khỏi danh sách Sinh viên.
- `be/app/Http/Controllers/SinhVienKiemTraController.php` — chặn truy cập trực tiếp đề đang nháp.
- `fe/src/router/index.js` — route màn hình tạo đề từ ngân hàng.
- `fe/src/components/GiangVien/BaiTap/index.vue` — lựa chọn tạo đề mới và thao tác tiếp tục lập lịch đề nháp.

Không sửa các bảng `cau_hoi`, `dap_an`, `bai_nop` hoặc logic lưu nháp/nộp/chấm điểm. Không sửa `TaoBaiKiemTra.vue`.

## Migration additive

Migration mới tạo:

- `ngan_hang_de_preview`: cấu hình, snapshot question/answer IDs + version, token hash hết hạn sau 15 phút, idempotency key và assignment đã tạo.
- `ngan_hang_de_tao`: metadata đề tạo từ ngân hàng, thời lượng, trạng thái `nhap`/`da_cong_bo`.
- `cau_hoi_ngan_hang_snapshot`: liên kết câu hỏi trong đề với câu hỏi nguồn và version nguồn. FK ngăn xóa câu hỏi nguồn còn được tham chiếu.

Các bảng câu hỏi/đáp án hiện hành được giữ làm snapshot riêng của đề. Metadata nháp giúp đề không xuất hiện với Sinh viên và chặn API làm bài đến khi Giảng viên lập lịch. Các bài cũ không có row metadata nên tiếp tục giữ hành vi cũ.

## APIs mới

Các endpoint nằm dưới `/api/giang-vien`, bảo vệ bằng Sanctum và middleware Giảng viên; quyền lớp/câu hỏi được xác định từ user đăng nhập:

| Method | Endpoint | Chức năng |
|---|---|---|
| POST | `/ngan-hang-de/availability` | Thống kê kho theo chủ đề, loại và độ khó; trả quota chính xác, không tự giảm tiêu chí. |
| POST | `/ngan-hang-de/preview` | Chọn câu phía Backend, lưu snapshot, trả preview token và idempotency key. |
| POST | `/ngan-hang-de/confirm` | Xác minh token/key, chủ sở hữu, trạng thái active và version; tạo đề nháp một lần. |
| GET | `/ngan-hang-de/{baiTapId}/draft` | Lấy metadata đề nháp của Giảng viên sở hữu. |
| POST | `/ngan-hang-de/{baiTapId}/publish` | Nhận thời điểm bắt đầu; kết thúc/hạn nộp tự tính theo thời lượng rồi công bố. Gọi lặp lại không đổi lịch đã công bố. |

Token preview lưu dạng SHA-256, gắn với Giảng viên, dùng kèm idempotency key và hết hạn sau 15 phút. Confirm lặp lại trả về cùng `baiTapId`; không tạo bài thứ hai. Nếu question bị đổi version, nội dung, đáp án hoặc inactive sau preview, confirm từ chối và yêu cầu preview lại.

## Thuật toán chọn và phân bổ điểm

- Chỉ lấy câu `active`, do đúng Giảng viên sở hữu, lọc chính xác theo chủ đề/loại/độ khó.
- Backend tính quota độ khó bằng phương pháp phần dư lớn nhất; tổng quota luôn đúng tổng số câu và phần trăm phải cộng đủ 100.
- Backend tìm phân bổ giao giữa loại câu và độ khó để bảo đảm đồng thời tổng trắc nghiệm, tổng tự luận và quota từng độ khó. Nếu kho không đáp ứng được thì trả lỗi, không tự giảm số lượng/tỷ lệ.
- Câu được lấy ngẫu nhiên, không trùng. Preview lưu cả nội dung, thứ tự câu, thứ tự đáp án và đáp án đúng; confirm không chọn ngẫu nhiên lại.
- Khi confirm, trong một transaction tạo `bai_tap`, sao chép câu hỏi/đáp án vào bảng hiện tại, ghi provenance, phân điểm tự luận theo cent và gọi `ExamScoreAllocator` cho phần trắc nghiệm. Sau đó xác nhận tổng điểm là 10. Lỗi ở bước nào đều rollback.
- Đề tạo ban đầu ở trạng thái nháp, không hiển thị với Sinh viên. Chỉ thao tác lập lịch riêng mới chuyển trạng thái sang công bố.

## Kiểm thử

- `php artisan test --filter='QuestionBank.*Test|ExamScoreAllocationTest|BaiNopStudentIdentityTest|ExamProtectionAlertTest'`: **PASS — 29 tests, 236 assertions**, SQLite in-memory.
- Bao gồm MC-only, essay-only, đề hỗn hợp, tổng điểm 10, preview/confirm giữ nguyên câu và thứ tự đáp án, retry idempotent, thiếu câu, lọc inactive/khác chủ, đổi version, snapshot không đổi khi sửa ngân hàng, Sinh viên không mở được đề nháp và rollback lỗi.
- Các test nộp file/trực tuyến/xem điểm cũ, phân bổ điểm cũ và cảnh báo bài thi cũng PASS.
- PHP syntax cho controller, migration, routes và các controller được chạm: **PASS**.
- Vite production build: **PASS**, 175 modules transformed bằng `npx vite build --configLoader runner --outDir .codex-build-test`. Output riêng đã được dọn. Build mặc định trước đây bị Windows `EPERM` khi ghi file tạm/dọn `dist`, nên build kiểm thử dùng config loader và output directory riêng; không xóa hoặc thay đổi `dist` hiện hữu.

## Chưa kiểm chứng và kích hoạt staging

- Cả migration Giai đoạn 2 và migration Giai đoạn 3 được chạy trong test SQLite in-memory qua `RefreshDatabase`; **chưa chạy trên MySQL staging hoặc MySQL thật**.
- Chưa kiểm thử thao tác giao diện trên trình duyệt thật.
- Vì vậy chưa thể kết luận sẵn sàng production.

Trước khi bật staging:

1. Tạo/backup một database staging tách biệt; xác nhận cấu hình Laravel đang trỏ đúng DB staging.
2. Chạy `php artisan migrate --path=database/migrations/2026_11_17_000001_create_question_bank_tables.php` nếu migration Giai đoạn 2 chưa có trong staging.
3. Chạy `php artisan migrate --path=database/migrations/2026_11_17_000002_create_question_bank_exam_workflow_tables.php` trên staging.
4. Kiểm tra indexes, JSON, foreign keys và transaction/row locking trên đúng phiên bản MySQL staging; chạy Feature Tests với MySQL staging riêng nếu môi trường CI có cấu hình đó.
5. Đăng nhập Giảng viên staging, tạo MC-only/essay-only/hỗn hợp, thử preview hết hạn/đổi version/confirm lặp, xác nhận đề nháp không xuất hiện và không mở được với Sinh viên, rồi lập lịch để kiểm tra đề mới xuất hiện.
6. Chỉ triển khai thật sau khi MySQL staging và browser test PASS, có backup và phê duyệt. Không chạy `migrate:fresh`, `db:wipe` hoặc migration vào MySQL thật trong công việc này.
