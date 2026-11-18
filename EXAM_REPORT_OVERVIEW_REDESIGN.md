# Báo cáo thiết kế lại sheet Tổng quan

## Phạm vi và file thay đổi

- `be/app/Services/ExamReportExportService.php` — thiết kế lại riêng sheet **Tổng quan**, các helper định dạng dashboard và thiết lập in riêng cho sheet này.
- `be/tests/Feature/ExamReportTest.php` — kiểm tra bố cục, KPI, biểu đồ và workbook đọc lại được.
- `EXAM_REPORT_OVERVIEW_REDESIGN.md` — báo cáo này.

Không sửa `ExamReportService`, API, quyền Giảng viên, nguồn dữ liệu, database, hoặc logic của năm sheet còn lại. Không thêm migration.

## Bố cục dashboard

- **A1:N3 — Header:** tiêu đề báo cáo lớn và dải thương hiệu SMART CLASSROOM MONITOR, nền navy, chữ trắng.
- **A4:N7 — Thông tin kỳ thi:** lớp, bài kiểm tra, giảng viên, thời gian bắt đầu/kết thúc, khoảng lọc và thời điểm xuất. Thời điểm được lưu dưới dạng Excel date serial và hiển thị theo `dd/mm/yyyy hh:mm:ss` trong timezone cấu hình ứng dụng.
- **A9:N17 — Sáu KPI:** tổng Sinh viên đủ điều kiện, đã tham gia, chưa tham gia, đã nộp, điểm trung bình và tổng sự kiện cảnh báo. Mỗi KPI có nhãn, giá trị lớn, màu theo ngữ nghĩa và viền nhẹ. Điểm chưa có dữ liệu hiển thị `—`.
- **A19:N35 — Biểu đồ:** Doughnut cho đã tham gia/chưa tham gia; biểu đồ cột ngang cho số lượng cảnh báo theo loại. Biểu đồ dùng đúng các giá trị `summary` từ `ExamReportService`; dữ liệu hỗ trợ được đặt ở cột P:Q ẩn, ngoài vùng in. Khi không có loại cảnh báo, khu vực biểu đồ hiển thị thông báo rỗng.
- **A36:N40 — Thống kê chi tiết:** đang làm/chưa nộp, đã chấm, chưa chấm, Sinh viên có cảnh báo, số loại cảnh báo, điểm trung bình, thời gian tạo báo cáo và chú thích: “Cảnh báo tự động là tín hiệu cần xem xét, không phải kết luận gian lận.”
- **A1:N43 — Vùng in:** A4 Landscape, vừa một trang theo chiều rộng và chiều cao, lề thu gọn, tiêu đề được lặp khi in. Sheet Tổng quan không có AutoFilter.

Màu navy/xanh dương/xanh lá/cam, Arial Unicode, wrap text, căn chỉnh theo nội dung và ẩn gridlines. Sheet khác giữ nguyên thiết kế hiện tại.

## Dữ liệu và tính toàn vẹn

KPI và phân bố tham gia lấy trực tiếp từ `summary` của `ExamReportService`. Tổng cảnh báo, số Sinh viên có cảnh báo và số lượng từng loại dùng dữ liệu đã khử trùng sẵn từ cùng service; không tính lại từ JOIN khác. Các bảng chi tiết, khóa ngoại và chấm điểm không thay đổi. Cơ chế ghi chuỗi an toàn chống Excel Formula Injection vẫn được dùng cho toàn bộ text của workbook.

## Kiểm thử

- `php artisan test --filter=ExamReportTest --compact` — **PASS**, 3 tests, 84 assertions.
- XLSX có dữ liệu: đọc lại thành công, giữ sáu sheet; kiểm tra giá trị sáu KPI, thời gian, vùng in A1:N43, hai biểu đồ, loại Doughnut và tham chiếu dữ liệu đúng; đối chiếu số cảnh báo với sheet chi tiết/thống kê.
- XLSX không có cảnh báo: đọc lại thành công, giữ thông báo “Chưa có dữ liệu cảnh báo” và không dựng biểu đồ cảnh báo rỗng.
- Test hiện có tiếp tục xác nhận formula injection, quyền lớp, dữ liệu cảnh báo và không xuất đường dẫn/URL ảnh riêng tư.
- PHP syntax và `git diff --check` được chạy riêng sau thay đổi.
- File xuất có kích thước lớn hơn 1 KB trong test. Thời lượng quan sát của cả focused suite gần 1,1 giây trong môi trường hiện tại; đây không phải benchmark hiệu năng production.

## Giới hạn

Chưa mở file thủ công bằng Microsoft Excel hoặc LibreOffice và chưa in thử trên máy in/PDF; số đo co giãn cuối cùng có thể khác theo phiên bản Excel, font và driver máy in. Chưa benchmark với dữ liệu lớn. Kiểm tra hiện tại xác minh writer tạo được XLSX và PhpSpreadsheet đọc lại được charts/dữ liệu tham chiếu, nhưng không thay thế kiểm thử hiển thị trên ứng dụng bảng tính thật.
