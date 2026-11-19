# Nâng cấp chi tiết cảnh báo trong báo cáo kỳ thi

## Kết quả

Đã mở rộng module báo cáo kỳ thi hiện tại. Tổng cảnh báo trước đây vẫn được tính theo từng sự kiện; thay đổi này làm rõ từng sự kiện, sinh viên, loại cảnh báo, nguồn, phiên, thời gian và trạng thái rà soát. Sheet Tổng quan có thêm bảng đếm động theo loại; sheet Sinh viên có số cảnh báo tổng và theo nhóm nguồn.

Không sửa luồng ghi cảnh báo, không thay đổi database, điểm số, làm/nộp bài, camera AI, WebRTC hoặc Reverb.

## Mapping nguồn cảnh báo

| Nguồn | Khóa `sinh_vien_id` thực tế | Mapping tới danh tính | Dữ liệu xem xét |
|---|---|---|---|
| `canh_bao_camera` | `sinh_vien.id` theo luồng ghi cảnh báo; migration khai báo cột số nhưng không FK | `canh_bao_camera.sinh_vien_id = sinh_vien.id` → `sinh_vien.nguoi_dung_id = nguoi_dung.id` | Không có trường xem xét trong bảng; không suy diễn từ `evidence_status` |
| `canh_bao` | `nguoi_dung.id` theo FK migration | `canh_bao.sinh_vien_id = nguoi_dung.id`; nối profile bằng `sinh_vien.nguoi_dung_id` | `da_xu_ly`; không đồng nghĩa kết luận vi phạm |
| `su_kien_bao_ve_bai_kiem_tra` | `sinh_vien.id` theo FK | `sbv.sinh_vien_id = sinh_vien.id` → `nguoi_dung.id` | `da_xem`, `xem_boi_id`, `xem_luc`; schema không có kết luận xác minh |
| `vi_pham_kiem_tra` | `sinh_vien.id` theo FK | `vp.sinh_vien_id = sinh_vien.id` → `nguoi_dung.id` | Không có trạng thái xem/kết luận trong schema |

Các nguồn được truy vấn riêng rồi hợp nhất; không join nhiều bảng sự kiện cùng lúc với danh sách Sinh viên. `canh_bao` chỉ được đưa vào báo cáo khi phiên thuộc đúng lớp và có đúng `bai_tap_id`. Cảnh báo phiên hoặc vi phạm không có liên kết session trực tiếp sẽ không được gán vào phiên tùy ý. Cảnh báo camera chỉ được lọc theo phiên khi bản ghi bằng chứng liên kết cùng `event_id` có `phien_id`.

Đã phát hiện quan hệ `CanhBao::sinhVien()` hiện trỏ `sinh_vien_id` sang khóa mặc định `sinh_vien.id`, trong khi migration của `canh_bao` khai báo khóa này tham chiếu `nguoi_dung.id`. Module báo cáo không dùng quan hệ Eloquent đó mà nối trực tiếp theo FK thật. Không sửa model ngoài phạm vi báo cáo; đây là rủi ro nếu module khác gọi quan hệ này.

## Quy tắc khử trùng

- Có `event_id`: dùng event ID làm khóa khử trùng xuyên các nguồn; nếu cùng event ID xuất hiện nhiều bảng, workbook có một dòng và giữ lại nguồn, mô tả, bằng chứng cùng metadata xem xét hiện có.
- Không có event ID: dùng cặp nguồn + ID bản ghi. Không suy đoán hai dòng độc lập là cùng một sự kiện.
- Tổng cảnh báo và đếm theo loại được tính sau khử trùng; mỗi sinh viên cũng đếm từ cùng danh sách sự kiện chuẩn hóa.
- Các số đếm Camera AI và bảo vệ bài thi là số sự kiện duy nhất có nguồn tương ứng; một sự kiện được ghi ở hai nguồn có thể xuất hiện trong cả hai số nhóm, trong khi tổng vẫn chỉ tính một lần.

## Cột đã bổ sung

- **Tổng quan:** bảng `Loại cảnh báo` / `Số sự kiện`, dịch các loại phổ biến sang tiếng Việt; loại chưa biết hiển thị dạng “Cảnh báo khác”. Giữ tổng sự kiện và số sinh viên có cảnh báo.
- **Sinh viên:** tổng cảnh báo, cảnh báo Camera AI, cảnh báo bảo vệ bài thi và trạng thái rà soát.
- **Cảnh báo:** STT, MSSV, họ tên, lớp, bài kiểm tra, phiên giám sát, loại cảnh báo tiếng Việt, nguồn tiếng Việt, thời gian xảy ra, mô tả có trong dữ liệu, trạng thái đã xem/đã xử lý, thời gian xem, mã tham chiếu ảnh an toàn và event ID.
- **Rà soát:** MSSV, họ tên, loại cảnh báo, thời gian cảnh báo, trạng thái xem/xử lý, người xem, thời gian xem và kết quả xác minh.
- Khi sheet Cảnh báo/Rà soát không có dòng dữ liệu, hàng dữ liệu hiển thị “Chưa có dữ liệu”, không tạo bản ghi cảnh báo giả.

Tên và MSSV lấy từ `nguoi_dung`/`sinh_vien` theo các khóa nêu trên. Loại và mô tả không có trong bảng thì để trống/chưa có dữ liệu; tín hiệu JSON chỉ lấy các thuộc tính `signal` và `source` đang được backend ghi nhận. Tham chiếu ảnh chỉ dạng `Ảnh #<id>`; workbook không đọc hoặc ghi đường dẫn storage, URL ảnh hay token.

Không có trường kết luận xác minh trong bốn schema cảnh báo được khảo sát. Sheet Rà soát ghi “Chưa có trường kết luận”; “Đã xem” và “Đã xử lý” không được diễn giải thành xác nhận gian lận.

## Định dạng và chống injection

Workbook vẫn có sáu sheet mặc định. Đã đặt font Arial, header tương phản, AutoFilter, Freeze Pane, Wrap Text, độ rộng cột giới hạn và trang in ngang. Các ô thời gian là giá trị ngày Excel có number format `dd/mm/yyyy hh:mm:ss` theo timezone ứng dụng. Text bắt đầu bằng ký tự công thức được ghi explicit dạng string và escape an toàn.

## Files thay đổi

- `be/app/Services/ExamReportService.php`
- `be/app/Services/ExamReportExportService.php`
- `be/tests/Feature/ExamReportTest.php`
- `EXAM_REPORT_ALERT_DETAILS_UPGRADE.md` (báo cáo này)

Không cần migration mới; không thay đổi controller/API hay quyền hiện có.

## Kiểm thử

- `php artisan test --filter=ExamReportTest` trên SQLite in-memory: **PASS**, 3 tests / 60 assertions.
  - Bảy cảnh báo Camera AI xuất thành bảy dòng sau khi một event ID trùng ở nguồn bảo vệ được gộp; overview, sheet sinh viên và sheet cảnh báo có số đếm khớp.
  - Fixture cố ý tạo `nguoi_dung.id` khác `sinh_vien.id`; tên/MSSV, camera, phiên, sự kiện bảo vệ và vi phạm được ánh xạ đúng.
  - Event ID khác nhau không bị gộp; bốn nguồn được đọc; Giảng viên lớp khác không preview/export được.
  - Đã xem/đã xử lý không thành kết luận; người xem và thời gian xem được lấy khi có dữ liệu.
  - Workbook có sáu sheet, định dạng ngày, trạng thái rỗng, chống formula injection, không chứa private path/URL và export không đổi số lượng bản ghi.
- PHP syntax các service/controller/routes/test: **PASS**.
- Frontend unit tests: **PASS**, 8 tests.
- Vite production build: **PASS**, Vite 6.4.3, 177 modules, output đặt trong thư mục tạm. Build mặc định vẫn gặp Windows `EPERM` khi Vite cố dọn `fe/dist/.gitkeep`; build tạm thành công và không thay đổi file đó.
- Không chạy migration, không truy cập/ghi database thật. Chưa thực hiện mở workbook thủ công bằng Excel/LibreOffice.

## Giới hạn dữ liệu

- `canh_bao_camera` không có cột mô tả hoặc trạng thái xem xét; báo cáo không tự tạo mô tả chi tiết.
- `vi_pham_kiem_tra` không có người xem, thời gian xem hoặc kết luận.
- `canh_bao` có `da_xu_ly` nhưng không có người xử lý/kết luận xác minh.
- Nguồn không lưu `phien_id` không thể lọc chính xác theo phiên; báo cáo không gán phiên suy đoán.
