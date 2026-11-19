# Báo cáo triển khai Excel báo cáo kỳ thi

## Phạm vi và thiết kế

Đã kiểm tra cấu trúc database và API hiện có trước khi triển khai. Không có `EXAM_REPORT_INTEGRATION_PLAN.md` trong workspace nên triển khai theo schema migration đang có. PhpSpreadsheet đã được khai báo trong `be/composer.json` và có trong vendor, không cài thêm package.

Module mới chỉ đọc dữ liệu. API nằm trong nhóm `auth:sanctum` và middleware `GiangVien`; báo cáo chỉ truy vấn lớp do Giảng viên hiện tại phụ trách, sau đó kiểm tra bài kiểm tra thuộc lớp đó. Không tạo migration, không thay đổi dữ liệu hoặc luật nộp/chấm bài.

## Schema và ánh xạ danh tính

- `thanh_vien_lop.sinh_vien_id` tham chiếu `nguoi_dung.id`.
- `bai_nop.sinh_vien_id` tham chiếu `nguoi_dung.id`.
- `sinh_vien.id` là ID hồ sơ; `sinh_vien.nguoi_dung_id` nối tới tài khoản.
- `bai_kiem_tra_luot_lam.sinh_vien_id`, `canh_bao_camera.sinh_vien_id`, `su_kien_bao_ve_bai_kiem_tra.sinh_vien_id`, `vi_pham_kiem_tra.sinh_vien_id` tham chiếu `sinh_vien.id`.
- Cảnh báo phiên trong `canh_bao.sinh_vien_id` tham chiếu `nguoi_dung.id`.

Truy vấn nối đúng từng khóa ngoại. Cảnh báo được tải riêng theo nguồn, không join trực tiếp nhiều bảng quan hệ một-nhiều vào danh sách kết quả. Sự kiện có cùng `event_id` giữa nguồn được khử trùng; sự kiện không có `event_id` dùng khóa nguồn + ID bản ghi. Tham chiếu bằng chứng chỉ có dạng `Ảnh #<id>`; không xuất đường dẫn lưu trữ hoặc URL ảnh riêng tư.

## Thành phần được thêm/thay đổi

- `be/app/Services/ExamReportService.php`: truy vấn chỉ đọc, kiểm tra quyền lớp/bài/phiên, tổng hợp sinh viên, lượt làm, bài nộp, điểm, cảnh báo và thống kê.
- `be/app/Services/ExamReportExportService.php`: tạo XLSX với sáu sheet Tổng quan, Sinh viên, Kết quả, Cảnh báo, Rà soát, Thống kê. Có tiêu đề định dạng, freeze pane, filter, wrap, cỡ cột giới hạn, trang in ngang, ngày giờ theo timezone ứng dụng và chống formula injection bằng cách ghi text dưới dạng chuỗi.
- `be/app/Http/Controllers/GiangVienExamReportController.php`: validate bộ lọc và gọi service.
- `be/routes/api.php`: thêm API trong nhóm Sanctum + Giảng viên.
- `fe/src/components/GiangVien/BaoCaoKyThi/index.vue`: bộ lọc lớp/bài/phiên/ngày/trạng thái, thẻ thống kê, bảng preview, chọn sheet và tải Blob bằng Axios.
- `fe/src/router/index.js`, `fe/src/layout/wrapper/lecturer.vue`: route `/giang-vien/bao-cao-ky-thi` và mục điều hướng.
- `be/tests/Feature/ExamReportTest.php`: kiểm tra ID hồ sơ khác ID tài khoản, giới hạn quyền lớp, khử trùng sự kiện, response XLSX và tên sáu sheet.

## Định nghĩa chỉ số

- Sinh viên đủ điều kiện: thành viên đang học của lớp tại thời điểm báo cáo.
- Đã tham gia: có lượt làm; chưa tham gia: không có lượt làm.
- Đang làm/chưa nộp: có lượt làm và không có bài nộp.
- Đã nộp/chưa nộp: căn cứ bản ghi `bai_nop`.
- Đã chấm: bài nộp có bản ghi `diem` với điểm; chưa chấm: bài nộp chưa có điểm.
- Điểm trung bình: trung bình cộng các bài nộp có điểm trong tập kết quả đang lọc.
- Số sinh viên cảnh báo: đếm ID tài khoản duy nhất sau khi khử trùng sự kiện.
- Thời gian lọc áp dụng cho hoạt động lượt làm/bài nộp và cảnh báo; trạng thái được giữ theo bản ghi thực tế, không diễn giải bài nộp ngoài khoảng thời gian thành “chưa nộp”.
- Trạng thái “đã xem/đã xử lý” không được diễn giải là xác minh vi phạm. Cột kết luận luôn ghi “Chưa có kết luận” vì schema hiện tại không có kết luận xác minh.

## API

- `GET /api/giang-vien/bao-cao-ky-thi?class_id=…&exam_id=…&session_id=…&from=YYYY-MM-DD&to=YYYY-MM-DD&participation=…&submission=…`: preview JSON.
- `POST /api/giang-vien/bao-cao-ky-thi/xuat-excel`: cùng bộ lọc trong JSON body, tùy chọn `sheets` trong `overview`, `students`, `results`, `alerts`, `review`, `statistics`; trả workbook XLSX.

Không có migration mới. API từ chối lớp/bài/phiên ngoài quyền Giảng viên; không nhận ID người tạo từ client.

## Kiểm thử

- PHP syntax cho service, controller, route và feature test: **PASS**.
- `php artisan route:list --path=giang-vien/bao-cao-ky-thi`: **PASS**, hiển thị đúng hai endpoint.
- `php artisan test --filter=ExamReportTest` trên SQLite `:memory:` từ `phpunit.xml`: **PASS**, 2 tests / 18 assertions.
  - Fixture đảm bảo `nguoi_dung.id` khác `sinh_vien.id`; API trả đúng tài khoản, hồ sơ, attempt, bài nộp và điểm.
  - Giảng viên khác không đọc được lớp; cùng event ID ở hai nguồn không bị đếm hai lần.
  - Tên sinh viên bắt đầu bằng `=` được ghi thành text an toàn; workbook response có MIME XLSX, chữ ký ZIP `PK` và mở được với sáu sheet đã định danh.
- Frontend production build: **PASS** bằng Vite 6.4.3 với output đặt trong thư mục tạm (`vite build --configLoader runner --outDir <TEMP>`; 177 modules). `npm run build` mặc định không thể dọn `fe/dist/.gitkeep` do Windows trả `EPERM`; artifact hiện hữu được giữ nguyên. Build tạm hoàn tất thành công.
- `npm run test:unit`: **PASS**, 8 tests.
- `git diff --check`: **PASS**, không phát hiện whitespace error trong diff.
- Không kiểm thử kết nối tới MySQL thật, Reverb hoặc trình duyệt đang chạy. Không chạy migration và không thay đổi database thật.

## Giới hạn cần biết

- Danh sách đủ điều kiện là roster đang có trạng thái `dang_hoc`; schema không lưu lịch sử thời điểm thêm/rời lớp nên không tái dựng được roster lịch sử tại ngày thi.
- Một số nguồn cảnh báo không có `event_id` hoặc metadata xử lý thống nhất; nguồn đó chống trùng theo ID bản ghi và chỉ xuất metadata sẵn có.
- Tạo và tải báo cáo trên môi trường staging với dữ liệu lớn cần đo bộ nhớ/thời gian thực tế trước khi dùng ở lớp có quy mô lớn.
- Cần kiểm tra thủ công trên trình duyệt (đăng nhập Giảng viên, lọc và mở file bằng Excel/LibreOffice) trước khi công bố production.
