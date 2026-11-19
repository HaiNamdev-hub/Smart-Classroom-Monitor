# Báo cáo chuyển cấu hình trộn đề sang Quản lý câu hỏi

## Kết quả

Đã chuyển cấu hình trộn khỏi luồng tạo đề từ ngân hàng sang trang quản lý câu hỏi của từng bài kiểm tra. Cấu hình dùng chung cho đề thủ công và đề tạo từ ngân hàng; mặc định cả hai tùy chọn đều tắt. Backend khi bắt đầu thi đọc cấu hình dùng chung và tiếp tục lưu thứ tự theo từng attempt như Phase 4.

## File thay đổi trong phạm vi công việc này

- `be/database/migrations/2026_11_17_000004_create_exam_shuffle_configuration_table.php` — tạo bảng cấu hình chuẩn `bai_tap_cau_hinh_tron`, mặc định tắt; chuyển các cờ trộn của đề ngân hàng Phase 3 hiện có sang bảng mới.
- `be/app/Http/Controllers/GiangVienCauHinhTronDeController.php` — API đọc/cập nhật, kiểm tra chủ sở hữu, xác thực boolean và khóa cập nhật.
- `be/routes/api.php` — khai báo API mới trong nhóm route Giảng viên đã được bảo vệ.
- `be/app/Http/Controllers/SinhVienKiemTraController.php` — đọc cấu hình chuẩn khi tạo attempt; đề không có cấu hình vẫn giữ thứ tự gốc.
- `be/app/Http/Controllers/GiangVienNganHangDeController.php` — endpoint tạo mới cũ trả HTTP 410 với hướng dẫn dùng quy trình tạo bài tập hiện tại; endpoint đọc/lên lịch/công bố bản nháp cũ vẫn được giữ.
- `fe/src/components/GiangVien/BaiTap/CauHinhTronDe.vue` — panel cấu hình hai tùy chọn trộn.
- `fe/src/components/GiangVien/BaiTap/TaoBaiKiemTra.vue` — đặt panel dưới lịch tự động, trước thống kê câu hỏi.
- `fe/src/components/GiangVien/BaiTap/index.vue` — bỏ lối vào tạo đề ngân hàng mới; giữ thao tác tiếp tục lên lịch các bản nháp cũ.
- `fe/src/router/index.js` — chặn route tạo mới trực tiếp; cho phép route legacy khi có `draftId` số để tiếp tục bản nháp cũ.
- `be/tests/Feature/ExamShuffleConfigTest.php` — test API và tích hợp cấu hình với thứ tự attempt/chấm theo ID.
- `be/tests/Feature/QuestionBankExamTest.php` — xác nhận endpoint tạo đề cũ đã nghỉ không ghi dữ liệu.
- `EXAM_SHUFFLE_INTEGRATION_REPORT.md` — báo cáo này.

Các thay đổi trước đó trong repository thuộc Phase 2–4 vẫn hiện diện; danh sách trên chỉ nêu thay đổi liên quan tới yêu cầu chuyển vị trí cấu hình ở lượt này.

## API và quy tắc khóa

- `GET /api/giang-vien/bai-tap/{baiTapId}/cau-hinh-tron`
- `PUT /api/giang-vien/bai-tap/{baiTapId}/cau-hinh-tron` với `tron_cau_hoi` và `tron_dap_an` dạng boolean.
- Chỉ chủ sở hữu bài tập đọc/cập nhật được; ID không thuộc Giảng viên trả 404. Cập nhật không hợp lệ trả 422.
- Backend khóa khi đã có attempt, bài nộp, phiên giám sát đang diễn ra, đề ngân hàng đã có trạng thái `da_cong_bo`, hoặc thời điểm mở bài thủ công đã đến. Cập nhật chạy trong transaction và khóa hàng bài tập; cấu hình legacy của đề Phase 3 được mirror để tương thích worker cũ.
- Bảng chuẩn có một hàng trên mỗi `bai_tap_id`, hai cờ mặc định `false`. Migration additive và sao chép cờ legacy, không sửa đề/câu hỏi/điểm lịch sử.

### Giới hạn về trạng thái công bố

Schema bài tập thủ công hiện tại không có cờ/trạng thái công bố riêng. Vì vậy code khóa cấu hình của bài thủ công khi đã đến giờ mở hoặc khi có attempt/bài nộp/phiên giám sát, thay vì suy diễn “đã công bố” từ việc tạo lịch trong tương lai. Đề từ ngân hàng có trạng thái công bố riêng nên bị khóa ngay khi `da_cong_bo`. Nếu sản phẩm cần khóa ngay từ lúc lên lịch công bố bài thủ công, cần bổ sung một trạng thái công bố có thẩm quyền trong một thay đổi schema riêng.

## Luồng trộn và tương thích

- Hai checkbox hiện trên trang quản lý câu hỏi, độc lập nhau và mặc định tắt.
- Khi bắt đầu, `SinhVienKiemTraController` dùng cấu hình chuẩn để sinh/lưu thứ tự theo attempt qua `ExamAttemptOrder`; frontend chỉ hiển thị thứ tự backend cung cấp.
- Request bắt đầu lại dùng mapping đã lưu; lưu nháp/nộp bài tiếp tục theo ID câu hỏi/đáp án; chấm trắc nghiệm theo ID đáp án đúng.
- Attempt lịch sử chưa có mapping và đề có cấu hình tắt hiển thị thứ tự gốc.
- Các endpoint `availability`, `preview`, `confirm` cũ giữ nguyên URL nhưng trả 410 để không âm thầm tạo đề qua quy trình đã ngưng. Bản nháp cũ vẫn có thể được lên lịch/công bố từ luồng hiện hành.

## Kiểm thử

- `php artisan test --filter='ExamShuffleConfigTest|QuestionBankExamTest'`: **PASS**, 5 tests / 44 assertions. Bao gồm mặc định tắt, quyền sở hữu, khóa đề đã công bố/đã bắt đầu, tính ổn định khi start lại, cấu hình tắt/thứ tự cũ, chấm theo ID đáp án và endpoint tạo đề cũ không ghi dữ liệu.
- `npm run build`: **PASS**, Vite chuyển đổi 177 modules, build hoàn tất trong 2.50 giây. Lần chạy sandbox đầu tiên bị `EPERM` khi ghi cache tạm; chạy lại được cấp quyền ghi thư mục build và thành công.
- `npm run test:unit`: **PASS**, 8 tests (kết quả trong lượt kiểm thử Phase 4).
- PHP syntax cho controller cấu hình, controller liên quan, controller sinh viên, migration và hai feature test: **PASS**.
- `git diff --check`: **PASS**.
- Toàn bộ Laravel suite gần nhất trong lượt kiểm thử Phase 4: **45 PASS, 5 FAIL**; các lỗi còn lại được phân loại ở báo cáo Phase 4: rate limit đăng nhập/test nhận 401 thay vì 429; fixture seed kỳ vọng 51 nhưng tạo 31 cảnh báo; hai test bằng chứng ảnh thiếu PHP GD; fixture test rời tab thiếu attempt. Đây không phải test cấu hình trộn. Các test tập trung của yêu cầu này đều PASS.

## Cơ sở dữ liệu và kích hoạt an toàn

Không chạy migration trong lượt này. `.env` của môi trường hiện tại trỏ tới database thật `smart_classroom_monitor`, nên không dùng Artisan migrate trên cấu hình đó. Test Laravel chạy trên SQLite cô lập của bộ test.

Trước khi kích hoạt trên staging:

1. Tạo và xác nhận database staging riêng; kiểm tra `DB_DATABASE` khác `smart_classroom_monitor` trước mọi migration.
2. Sao lưu staging và rà soát migration pending.
3. Chạy migration additive `2026_11_17_000004_create_exam_shuffle_configuration_table.php` trên staging. Migration Phase 2–4 pending khác phải được xem xét và triển khai theo kế hoạch staging tương ứng.
4. Kiểm tra các cờ legacy đã được sao chép, đề thủ công mặc định tắt, quyền đọc/ghi, khóa sau công bố/bắt đầu và start/reload của hai tài khoản thử nghiệm.
5. Chỉ phát hành sau khi kiểm thử trên MySQL staging và trình duyệt thực tế.

Không thay đổi dữ liệu database thật, không chạy `migrate:fresh`, không commit hoặc push. Chưa xác nhận production-ready vì chưa có MySQL staging và kiểm thử trình duyệt thực tế.
