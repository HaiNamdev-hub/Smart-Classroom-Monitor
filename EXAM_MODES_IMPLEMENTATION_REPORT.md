# Báo cáo triển khai hai hình thức kiểm tra

Ngày kiểm tra: 18/11/2026

## Phạm vi

Đã triển khai hai hình thức:

- `ONLINE_CAMERA_REQUIRED`: giữ hành vi cũ; camera AI là điều kiện bắt đầu và camera warnings tiếp tục hoạt động.
- `LAB_NO_CAMERA`: không yêu cầu hoặc khởi tạo camera giám sát; vẫn giữ fullscreen, bảo vệ bài thi, lưu nháp, nộp bài và cảnh báo rời tab.

Bài thi chưa có cấu hình luôn được diễn giải là `ONLINE_CAMERA_REQUIRED` để tương thích ngược.

## Thay đổi mã nguồn

Backend:

- Thêm `be/app/Support/ExamMode.php` làm nguồn quy tắc mặc định và phân loại mode.
- Thêm `be/database/migrations/2026_11_18_000001_create_bai_tap_hinh_thuc_kiem_tra_table.php` (migration additive; chưa chạy).
- Thêm `be/app/Http/Controllers/GiangVienExamModeController.php` và hai route GET/PUT Sanctum tại `/api/giang-vien/bai-tap/{id}/hinh-thuc-kiem-tra`. Chỉ chủ sở hữu bài kiểm tra sửa được; khóa sau giờ bắt đầu theo lịch, khi có lượt làm hoặc bài nộp; cập nhật nằm trong transaction và khóa bản ghi bài tập.
- Sửa `SinhVienKiemTraController.php` để trả mode, chỉ yêu cầu `cameraReady` với mode online và kiểm tra lại cấu hình trong lúc bắt đầu thi.
- Sửa `SinhVienCanhBaoCameraController.php` để từ chối API cảnh báo/upload ảnh camera ở mode phòng máy, không tạo vi phạm camera giả.
- Sửa `SinhVienBaoVeBaiKiemTraController.php` để camera alerts không làm tăng khóa bài phòng máy, trong khi các sự kiện bảo vệ như rời tab vẫn được ghi.
- Sửa `GiangVienApiController.php` để phiên giám sát trả mode và trạng thái camera `not_required`; danh sách sinh viên/lịch sử bảo vệ vẫn có thể hiển thị khi không có camera.
- Sửa `ExamReportService.php` và `ExamReportExportService.php` để metadata báo cáo và sheet Tổng quan ghi hình thức thi, chính sách camera. Không đổi thống kê điểm hoặc sáu sheet hiện có.
- Thêm và mở rộng `be/tests/Feature/ExamModeTest.php`, `be/tests/Feature/ExamReportTest.php`.

Frontend:

- Thêm `fe/src/services/examMode.js`; chế độ thiếu hoặc không nhận diện được mặc định yêu cầu camera.
- Thêm giao diện cấu hình `fe/src/components/GiangVien/BaiTap/HinhThucKiemTra.vue`, gắn vào `TaoBaiKiemTra.vue` phía trên cấu hình lịch; không thay luồng tạo câu hỏi.
- Sửa `LamBaiKiemTra/index.vue` để chỉ render camera/AI trong mode online và chỉ yêu cầu cameraReady ở mode đó. Autosave, bảo vệ thi, câu trả lời và submit giữ nguyên.
- Sửa `GiamSatThi/index.vue` để mode phòng máy hiển thị danh sách Sinh viên/trạng thái bảo vệ thay cho lưới webcam, không tạo lỗi offline giả.
- Sửa `BaoCaoKyThi/index.vue` để hiển thị mode và chính sách camera.
- Thêm `fe/tests/exam-mode.test.js`.

## API và dữ liệu

- `GET /api/giang-vien/bai-tap/{id}/hinh-thuc-kiem-tra`: trả mode, nhãn, yêu cầu camera, có thể chỉnh sửa và lý do khóa.
- `PUT /api/giang-vien/bai-tap/{id}/hinh-thuc-kiem-tra`: nhận `hinh_thuc`, chỉ chấp nhận đúng hai mode. Định danh giảng viên lấy từ Sanctum, không tin ID người tạo từ client.
- API chi tiết bài kiểm tra và trạng thái của Sinh viên có thêm mode/policy.
- Bảng mới chỉ lưu cấu hình theo `bai_tap_id`; không sửa bảng bài nộp, câu hỏi, đáp án, điểm hoặc lịch sử cảnh báo.

Migration `2026_11_18_000001_create_bai_tap_hinh_thuc_kiem_tra_table.php` chưa được áp dụng. Khi triển khai, cần backup và chạy migration trên staging riêng trước. Nếu bảng chưa có, backend bảo thủ trả bài cũ theo yêu cầu camera và API cấu hình báo chưa kích hoạt; không tự suy diễn mode phòng máy.

## Kiểm thử

PASS:

- `php artisan test --filter=ExamModeTest --compact`: 5 tests, 31 assertions.
- `php artisan test --filter=ExamReportTest --compact`: 3 tests, 90 assertions.
- `npm run test:unit`: 9 tests, 9 passed.
- PHP syntax: tất cả 11 file Backend liên quan đều hợp lệ.
- Frontend production build: Vite 6.4.3 build thành công, 180 modules transformed. Lệnh đã dùng: `vite --configLoader runner build --outDir <TEMP>`.
- `git diff --check`: không báo lỗi whitespace/conflict marker.

Build thường `npm run build` từng bị Vite lỗi quyền (`EPERM`) khi ghi file config tạm vào `node_modules/.vite-temp`. Dùng config loader runner và output tạm ngoài repository đã hoàn tất build thành công; không sửa cấu hình Vite để né vấn đề môi trường.

Full Laravel suite: 55 passed, 5 failed. Không sửa hoặc bỏ các test đó:

1. `DangNhapTest`: kỳ vọng 429 nhưng thực tế 401 ở lần đăng nhập thứ sáu — lỗi rate-limit/test hoặc cấu hình throttle hiện tại cần điều tra riêng.
2. `DatabaseSeederTest`: kỳ vọng 51 dòng `canh_bao`, nhận 31 — fixture/seed count không khớp kỳ vọng test.
3. Hai `ExamEvidenceTest`: môi trường PHP không có GD extension; thất bại khi tạo ảnh test, chưa đánh giá được logic upload bằng hai case này.
4. `ReportTabLeaveTest`: lần báo rời tab đầu nhận `soLanViPham = 0` thay vì 1; test/fixture trạng thái attempt hoặc logic hiện hành cần điều tra riêng. Đây không thuộc thay đổi mode ngoài việc test LAB mode mới xác nhận sự kiện tab vẫn được lưu.

## Chưa được kiểm chứng

- Chưa chạy migration trên MySQL staging và chưa thực hiện trình duyệt thực tế.
- Chưa kiểm tra quyền camera/MediaPipe/WebRTC bằng hai tài khoản và phiên Reverb đang chạy.
- Không thể khẳng định tương thích production cho tới khi staging xác nhận schema, cấu hình lịch/giờ máy chủ và kiểm thử browser.
- Bản test mode chạy trên SQLite in-memory qua cấu hình PHPUnit; không đại diện cho khóa hàng/concurrency của MySQL.

## Hướng dẫn staging an toàn

1. Tạo database staging mới, tên riêng biệt; xác minh `DB_DATABASE` khác `smart_classroom_monitor` trước khi migrate.
2. Sao lưu staging và trỏ cấu hình môi trường staging riêng (không sửa `.env` vận hành).
3. Chạy `php artisan migrate:status` để kiểm tra trạng thái staging.
4. Chạy migration mới trên staging bằng `php artisan migrate --path=database/migrations/2026_11_18_000001_create_bai_tap_hinh_thuc_kiem_tra_table.php`.
5. Tạo bài thi giả lập. Kiểm tra bài cũ không có setting vẫn bắt buộc camera; mode online yêu cầu camera; mode phòng máy bắt đầu không camera, không phát sinh cảnh báo camera nhưng vẫn ghi nhận rời tab.
6. Xác nhận Giảng viên ngoài quyền sở hữu không đọc/ghi cấu hình; thử sửa cấu hình trước và sau khi bắt đầu/lượt làm.
7. Chạy lại báo cáo Excel và xác nhận mode/policy hiển thị đúng. Chỉ sau khi staging và trình duyệt PASS mới lập kế hoạch triển khai production.

Không có dữ liệu thật bị sửa, không migration nào được chạy, và không có commit/push.
