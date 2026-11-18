# Báo cáo nâng cấp thiết kế Excel báo cáo kỳ thi

## Phạm vi

Đã chỉnh phần trình bày workbook trong `ExamReportExportService`; không thay đổi `ExamReportService`, API, phân quyền, truy vấn dữ liệu, database, cảnh báo hoặc luồng làm bài/chấm điểm. Workbook vẫn có sáu sheet: Tổng quan, Sinh viên, Kết quả, Cảnh báo, Rà soát và Thống kê.

## Thay đổi

- **Tổng quan:** tiêu đề và mô tả báo cáo, lớp, mã lớp, bài kiểm tra, giảng viên, khoảng thời gian và thời điểm xuất; tám ô KPI; bảng số lượng cảnh báo theo loại và biểu đồ cột ngang khi có dữ liệu.
- **Sinh viên:** bảng có MSSV, tên, email, trạng thái tham gia/nộp/chấm, thời gian, điểm tổng, tổng cảnh báo, cảnh báo Camera AI, cảnh báo bảo vệ bài thi và trạng thái rà soát.
- **Kết quả:** làm nổi bật điểm tổng và thời điểm nộp/chấm. Nguồn báo cáo hiện tại chỉ cung cấp điểm tổng nên hai cột điểm trắc nghiệm/tự luận ghi rõ “Chưa được phân tách”; không suy diễn điểm thành phần.
- **Cảnh báo:** giữ từng sự kiện sau quy tắc khử trùng hiện có; hiển thị Sinh viên, MSSV, loại/nguồn, thời gian, trạng thái rà soát và mã sự kiện. Chỉ xuất mã tham chiếu bằng chứng, không xuất đường dẫn lưu trữ hay URL truy cập ảnh.
- **Rà soát:** trình bày trạng thái đã xem/đã xử lý riêng với kết luận xác minh; không biến trạng thái đã xử lý thành kết luận gian lận.
- **Thống kê:** giữ các chỉ số kỳ thi, bổ sung phân bố theo điểm tổng thực tế và số sự kiện theo loại. Bộ lọc áp dụng riêng cho bảng loại cảnh báo.
- Tất cả sheet dùng Arial Unicode, màu tiêu đề đồng nhất, hàng tiêu đề rõ, wrap text, freeze pane, AutoFilter phù hợp, độ rộng cột, chiều cao hàng, định dạng ngày giờ `dd/mm/yyyy hh:mm:ss`, khổ in A4 ngang và lặp tiêu đề bảng khi in.
- Dữ liệu văn bản vẫn được ghi thành kiểu chuỗi tường minh và đi qua `safeText()` để chống Excel Formula Injection.

## File thay đổi

- `be/app/Services/ExamReportExportService.php` — định dạng sáu sheet, KPI, biểu đồ, bố cục in và các dải lọc.
- `be/tests/Feature/ExamReportTest.php` — cập nhật vị trí ô sau khi thiết kế lại và xác nhận workbook, biểu đồ, số liệu, formula injection và dữ liệu riêng tư.
- `EXAM_REPORT_EXCEL_DESIGN_REPORT.md` — báo cáo này.

## Kiểm thử

- `php artisan test --filter=ExamReportTest --compact` — **PASS**, 3 tests, 65 assertions.
- Test kiểm tra xuất đủ sáu sheet, XLSX signature/content type, biểu đồ khi có dữ liệu cảnh báo, các số tổng quan/chi tiết thống nhất, mapping ID, quyền lớp, chống Formula Injection và không lộ storage path/URL ảnh.
- `php -l app/Services/ExamReportExportService.php` — **PASS**.
- `php -l tests/Feature/ExamReportTest.php` — **PASS**.
- `git diff --check` trên hai file mã nguồn — **PASS**.
- Không chạy Frontend build vì không có file Frontend nào thuộc phạm vi thay đổi.

## Ảnh hưởng hiệu năng và giới hạn

Việc tạo biểu đồ và áp kiểu hàng tăng nhẹ thời gian/ bộ nhớ khi xuất; xử lý vẫn tỷ lệ tuyến tính theo số dòng báo cáo. Không thêm truy vấn chi tiết theo từng sự kiện ngoài truy vấn tên giảng viên phục vụ metadata của sheet Tổng quan.

Chưa mở workbook thủ công bằng Microsoft Excel/LibreOffice để kiểm tra khác biệt hiển thị giữa các ứng dụng. Dữ liệu hiện tại không tách điểm trắc nghiệm và tự luận nên workbook không thể báo hai thành phần đó cho đến khi nguồn báo cáo có dữ liệu thực. Kiểm thử chạy trên môi trường Laravel test cô lập; không thay đổi hay kiểm tra dữ liệu MySQL thật.
