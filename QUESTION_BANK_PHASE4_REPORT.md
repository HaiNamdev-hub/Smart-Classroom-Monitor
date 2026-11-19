# QUESTION BANK PHASE 4 — BÁO CÁO TRỘN ĐỀ THEO LƯỢT LÀM

## Trạng thái

Phase 4 đã được triển khai theo hướng additive. Chức năng mới dùng cấu hình đề từ ngân hàng và thứ tự lưu theo từng `bai_kiem_tra_luot_lam`. Đề thủ công/đề cũ không có mapping tiếp tục dùng thứ tự `thu_tu` như trước.

Chưa chạy migration trên database được cấu hình ngoài môi trường test. Chưa kiểm thử MySQL staging hoặc hai trình duyệt thật; do đó chưa xác nhận production-ready.

## Thay đổi

- Thêm migration `2026_11_17_000003_add_per_attempt_exam_order.php`:
  - Thêm `tron_cau_hoi`, `tron_dap_an` vào `ngan_hang_de_tao`, mặc định `false`.
  - Tạo `bai_kiem_tra_luot_lam_cau_hoi` để lưu thứ tự câu theo attempt.
  - Tạo `bai_kiem_tra_luot_lam_dap_an` để lưu thứ tự đáp án theo attempt/câu hỏi.
  - FK của mapping trỏ tới `bai_kiem_tra_luot_lam.id`, `cau_hoi.id`, `dap_an.id`; dữ liệu câu hỏi cũ không bị thay thế.
- Thêm `be/app/Support/ExamAttemptOrder.php`: xáo trộn Fisher–Yates bằng `random_int()` và lưu mapping.
- Sửa `SinhVienKiemTraController`:
  - Khi start lần đầu, tạo attempt và mapping trong cùng transaction.
  - Start lại dùng mapping hiện có, không trộn lại.
  - GET đề và response start trả câu/đáp án theo thứ tự đã lưu, không gửi đáp án đúng/mẫu cho Sinh viên.
  - Attempt lịch sử chưa có mapping dùng thứ tự gốc.
- Sửa `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`: dùng thứ tự Backend trả ngay sau start; không xáo trộn ở Vue và không tải lại exam, khởi động lại camera hoặc thay ID câu trả lời.
- Sửa `GiangVienApiController::chiTietBaiNop()`: hiển thị câu hỏi và đáp án theo thứ tự attempt. Đúng ánh xạ ID: `bai_nop.sinh_vien_id` là `nguoi_dung.id`, còn attempt dùng `sinh_vien.id`; controller resolve qua `sinh_vien.nguoi_dung_id`.
- Sửa `GiangVienNganHangDeController`: lưu cấu hình trộn khi xác nhận tạo đề. Mặc định backend là tắt.
- Sửa `TaoDeTuNganHang.vue`: cả hai checkbox trộn mặc định tắt.
- Sửa `GiangVienCauHoiController` và `GiangVienNhapCauHoiController`: từ chối sửa/thêm/xóa câu hỏi khi đã có attempt, tránh thay nội dung của đề đã giao.
- Mở rộng `QuestionBankExamTest.php` với test cho thứ tự hai Sinh viên, reload/duplicate start, chấm theo answer ID, bài hỗn hợp/tự luận, lưu nháp, thứ tự tắt và attempt lịch sử.

## Cách hoạt động và bảo vệ dữ liệu

1. Sinh viên được xác thực, kiểm tra quyền lớp, lịch thi, trạng thái khóa và tổng điểm như luồng hiện tại.
2. Transaction khóa hàng bài tập; unique hiện có trên `(bai_tap_id, sinh_vien_id)` cùng transaction ngăn tạo attempt lặp. Attempt mới lưu danh sách câu và đáp án đã sắp xếp trước khi commit.
3. Nếu đã có attempt thì không tạo mapping mới và không xáo trộn lại. Nếu mapping không tồn tại (attempt lịch sử), API trả thứ tự gốc.
4. Lưu nháp và nộp vẫn dùng `cau_hoi_id`/`dap_an_id`; chấm điểm hiện có đối chiếu `la_dap_an_dung` theo answer ID, không dựa vào nhãn A/B/C/D.
5. Việc chỉnh sửa nội dung câu hỏi/đáp án qua API thủ công hoặc import bị chặn sau khi có attempt. Thay đổi ngân hàng nguồn không làm thay đổi bản sao trong đề.

## Kiểm thử

- `npm run build`: **PASS** (Vite 6.4.3, 175 modules).
- `npm run test:unit`: **PASS**, 8 tests.
- `php artisan test --filter=QuestionBankExamTest`: **PASS**, 11 tests / 95 assertions.
- `php artisan test --filter='BaiNopStudentIdentityTest|ExamScoreAllocationTest'`: **PASS**, 6 tests / 45 assertions.
- PHP syntax cho migration, service, các controller đã sửa và feature test: **PASS**.
- Toàn bộ `php artisan test`: **51 PASS, 5 FAIL**. Các lỗi ngoài phạm vi Phase 4:
  - `DangNhapTest::login_is_rate_limited`: test kỳ vọng 429 nhưng nhận 401; cần kiểm tra limiter/test setup riêng.
  - `DatabaseSeederTest`: fixture kỳ vọng 51 `canh_bao`, DB test có 31; mismatch dữ liệu seed/test.
  - Hai test upload ảnh trong `ExamEvidenceTest`: môi trường PHP thiếu GD (`imagecreatetruecolor`), không phải lỗi của Phase 4.
  - `ReportTabLeaveTest`: fixture không tạo bản ghi `bai_kiem_tra_luot_lam`, trong khi endpoint chỉ tính sự kiện sau khi attempt tồn tại; lỗi fixture/điều kiện test, không liên quan thứ tự đề.
- `php artisan migrate:status` chỉ đọc trạng thái: các migration question bank Phase 2, Phase 3 và Phase 4 đang Pending trên connection Artisan hiện tại. Không chạy migration và không thay đổi dữ liệu.

## Những nội dung chưa được kiểm chứng

- Chưa chạy đồng thời hai HTTP request thật trên MySQL để kiểm chứng tranh chấp khóa; test thực hiện start lặp tuần tự và xác nhận trả cùng attempt/thứ tự. Cơ chế khóa hàng đề và unique constraint cần được xác minh thêm trên MySQL staging.
- Chưa kiểm thử MySQL staging, browser UI hai người dùng, mất mạng/reload trong trình duyệt hoặc giao diện chấm bài thực tế.
- Không chạy migration trên database thật. Trước khi bật Phase 4, cần backup và chạy migration trên staging trước.

## Hướng dẫn kiểm thử staging an toàn

1. Tạo database staging riêng; xác nhận `DB_DATABASE` không phải `smart_classroom_monitor`.
2. Backup staging, rồi chạy `php artisan migrate --force` trên đúng cấu hình staging để áp dụng migrations Phase 2–4. Không dùng `migrate:fresh`.
3. Tạo đề ngân hàng với hai checkbox tắt và kiểm tra thứ tự cũ; sau đó tạo đề bật cả hai checkbox.
4. Cho hai tài khoản Sinh viên cùng lớp bắt đầu: xác nhận mỗi attempt có mapping riêng; refresh và đăng nhập lại phải giữ nguyên thứ tự.
5. Lưu nháp, nộp và chọn đúng phương án theo ID; xác nhận điểm 10 và trang chi tiết Giảng viên theo đúng thứ tự từng Sinh viên.
6. Gửi nhiều request start đồng thời cho cùng Sinh viên và xác nhận chỉ có một attempt/mapping.
7. Thử sửa/import câu hỏi sau khi một attempt đã bắt đầu; API cần trả 409 và không thay dữ liệu.

Không commit hoặc push Git.
