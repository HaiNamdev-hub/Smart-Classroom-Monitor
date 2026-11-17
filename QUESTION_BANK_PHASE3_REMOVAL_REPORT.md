# Báo cáo gỡ bộ tạo đề từ Ngân hàng câu hỏi Phase 3

## Tóm tắt

Đã gỡ giao diện, route Frontend, API và controller của quy trình kiểm tra kho → preview → tạo đề nháp → công bố Phase 3. Ngân hàng câu hỏi Phase 2 và chức năng trộn theo attempt Phase 4 tiếp tục được giữ. Các bài tập/câu hỏi/đáp án/lượt làm/điểm không bị sửa.

## Dependency đã kiểm tra và xử lý

| Thành phần | Phân loại | Xử lý |
|---|---|---|
| `TaoDeTuNganHang.vue`, route `GiangVienTaoDeTuNganHang` | Chỉ thuộc luồng tạo/lập lịch Phase 3 | Đã xóa component và route. |
| `GiangVienNganHangDeController` và `/ngan-hang-de/*` | Preview, confirm, draft và publish Phase 3 | Đã xóa controller và toàn bộ API routes. Các URL cũ hiện trả 404. |
| Các bảng `ngan_hang_de_preview`, `ngan_hang_de_tao`, `cau_hoi_ngan_hang_snapshot` | Workflow Phase 3; metadata có thể giúp nhận diện đề/bản nháp lịch sử | Không xóa migration/bảng. Các truy vấn cần thiết còn lại là read-only và được bảo vệ bằng `Schema::hasTable`. Không còn runtime query preview/snapshot. |
| `bai_kiem_tra_luot_lam_cau_hoi`, `bai_kiem_tra_luot_lam_dap_an`, `ExamAttemptOrder.php` | Thứ tự đề riêng theo attempt Phase 4 | Giữ nguyên. Luồng bắt đầu thi tiếp tục lưu/đọc mapping theo ID. |
| `bai_tap_cau_hinh_tron` và API cấu hình shuffle | Cấu hình trộn độc lập theo bài tập | Giữ nguyên, không còn ghi mirror ngược vào metadata Phase 3. Nếu migration chưa chạy, bài thi chạy thứ tự gốc; API GET trả mặc định tắt và khóa chỉnh sửa với lý do rõ, PUT trả 503. |
| Ngân hàng câu hỏi Phase 2 | CRUD, tìm kiếm, import, nhân bản, vô hiệu hóa | Giữ nguyên. |

Các tham chiếu runtime còn lại tới `ngan_hang_de_tao` chỉ để đọc tương thích lịch sử: ẩn bản nháp cũ với Sinh viên, hiển thị trạng thái/thời lượng cũ cho Giảng viên và khóa cấu hình trộn cho đề đã công bố. Tất cả đều có kiểm tra tồn tại bảng trước khi truy vấn. Điều này tránh 500 nếu bảng Phase 3 không có, mà không làm lộ bản nháp cũ. Những migration Phase 3 đã chạy không bị sửa vì cần giữ lịch sử migration.

## File đã xóa

- `fe/src/components/GiangVien/BaiTap/TaoDeTuNganHang.vue`
- `be/app/Http/Controllers/GiangVienNganHangDeController.php`
- `be/tests/Feature/QuestionBankExamTest.php` (được thay bằng kiểm thử route nghỉ và tương thích schema)

## File đã sửa

- `be/routes/api.php` — bỏ import controller và các routes availability/preview/confirm/draft/publish.
- `be/app/Http/Controllers/BaiTapController.php` — chỉ lọc bản nháp legacy khi bảng metadata hiện diện.
- `be/app/Http/Controllers/SinhVienKiemTraController.php` — giữ chặn truy cập bản nháp cũ nếu metadata có, không phụ thuộc bảng đó để bắt đầu đề thường; fallback trộn tắt nếu migration cấu hình chưa có.
- `be/app/Http/Controllers/GiangVienApiController.php` — LEFT JOIN metadata cũ chỉ khi bảng hiện diện; nếu không, danh sách bài tập dùng `bai_tap` bình thường.
- `be/app/Http/Controllers/GiangVienCauHinhTronDeController.php` — cấu hình không mirror sang bảng Phase 3; bảng shuffle thiếu thì trả trạng thái mặc định tắt, khóa sửa và thông báo migration cần thiết.
- `fe/src/router/index.js` — bỏ route tạo/lập lịch đề từ ngân hàng.
- `fe/src/components/GiangVien/BaiTap/index.vue` — bỏ CTA Phase 3; bản nháp cũ chỉ còn nhãn giải thích, không có luồng công bố cũ.
- `be/tests/Feature/RetiredQuestionBankExamWorkflowTest.php` — xác minh URL cũ 404 và luồng danh sách/bắt đầu đề không lỗi khi thiếu các bảng Phase 3 cùng bảng shuffle.
- `QUESTION_BANK_PHASE3_REMOVAL_REPORT.md` — báo cáo này.

## Trạng thái DB thực tế (chỉ đọc)

Đã chạy `php artisan migrate:status` và `php artisan db:show`; kết nối hiện tại trỏ tới `smart_classroom_monitor` (MariaDB 10.11.16, localhost). Phase 2, Phase 3 và migration mapping Phase 4 đều ở trạng thái **Ran**. Migration `2026_11_17_000004_create_exam_shuffle_configuration_table.php` ở trạng thái **Pending**.

`migrate:status` hoàn tất. `db:show` đã in thông tin kết nối/schema nhưng tiến trình kết thúc lỗi do PHP CLI thiếu extension `intl` và Laravel không ghi được `storage/logs/laravel.log`; vì vậy không coi lệnh này là PASS. Số lượng hàng và khóa ngoại được xác minh riêng bằng các truy vấn PDO SELECT trực tiếp, không ghi dữ liệu.

Các truy vấn SELECT chỉ đếm số bản ghi cho thấy:

- `ngan_hang_de_preview`: 0
- `ngan_hang_de_tao`: 0
- `cau_hoi_ngan_hang_snapshot`: 0
- Mapping attempt câu hỏi: 0
- Mapping attempt đáp án: 0
- Khóa ngoại tham chiếu vào ba bảng Phase 3: không có

Schema thực tế có các khóa ngoại đi ra từ Phase 3: `ngan_hang_de_preview` → `nguoi_dung`, `lop_hoc`, `bai_tap`; `ngan_hang_de_tao` → `bai_tap`, `nguoi_dung`; `cau_hoi_ngan_hang_snapshot` → `cau_hoi`, `ngan_hang_cau_hoi`. Không có bảng nào khác tham chiếu ngược vào ba bảng Phase 3. Đây là bằng chứng cho việc giữ schema hiện tại; nếu lập cleanup riêng sau này, phải xử lý các FK đi ra và thứ tự phụ thuộc, không drop tùy tiện.

Không chạy migration, không chạy lệnh ghi, không xóa hay sửa dữ liệu thật. Không thể kết luận DB staging tồn tại từ các thông tin hiện có; DB được xác nhận ở đây là database cấu hình hiện hành. Vì vậy **không tạo hoặc chạy cleanup migration**. Các migration lịch sử được giữ nguyên; việc xóa bảng Phase 3 chỉ nên xem xét trong một kế hoạch triển khai riêng có backup và phê duyệt.

## Kiểm thử

- `php artisan test --filter='ExamShuffleConfigTest|RetiredQuestionBankExamWorkflowTest|QuestionBankTest|ExamScoreAllocationTest'`: **PASS**, 16 tests / 104 assertions. Test mới tạo câu hỏi thủ công và import XLSX qua API theo đề cũ, sau đó bắt đầu bài khi không có bảng Phase 3.
- `npm run test:unit`: **PASS**, 8 tests.
- `npm run build`: **PASS**, Vite 175 modules, build hoàn tất.
- PHP syntax cho controller, routes và test mới: **PASS**.
- `php artisan route:list --path=ngan-hang-de`: không còn route phù hợp. `--path=ngan-hang-cau-hoi` vẫn liệt kê đủ 8 routes Phase 2.
- Tìm toàn repo: không còn source Vue/controller/routes của bộ tạo đề; references còn lại được giới hạn ở test, migration lịch sử và read-only compatibility có kiểm tra schema.
- Full `php artisan test`: **46 PASS, 5 FAIL**. Các lỗi không thuộc thay đổi này: đăng nhập rate-limit kỳ vọng 429 nhưng nhận 401; fixture seeder kỳ vọng 51 cảnh báo nhưng sinh 31; hai test ảnh thiếu PHP GD; fixture test rời tab thiếu attempt. Không sửa các test này để làm xanh giả.
- Chưa chạy kiểm thử trình duyệt thật. CRUD/import ngân hàng được kiểm chứng bằng `QuestionBankTest`; đề thủ công và pipeline import đề cũ được kiểm tra bằng XLSX. Chưa thao tác thủ công DOCX/PDF trong trình duyệt.

## Hạn chế và kích hoạt tiếp theo

Migration shuffle cấu hình đang Pending trên database thật. Không chạy migration trong công việc này. Khi cần bật tùy chọn trộn trên môi trường đó, hãy sao lưu và áp dụng migration trên staging trước; khi triển khai migration chuẩn, trang cấu hình mới cho phép ghi. Đề thường vẫn fallback về thứ tự gốc trong lúc thiếu bảng.

Các bảng Phase 3 đã triển khai được giữ lại để tôn trọng lịch sử schema; hiện code không còn chức năng tạo/công bố đề mới từ ngân hàng. Các bản nháp Phase 3 cũ vẫn bị ẩn khỏi Sinh viên nhưng luồng công bố cũ đã bị gỡ. Không có dữ liệu Phase 3 trong database đã kiểm tra.

Rollback source code có thể thực hiện bằng Git khi cần, sau khi xem xét riêng các thay đổi khác đang có trong worktree. Không có migration cleanup để rollback và không có thay đổi schema/dữ liệu thật trong lượt này.
