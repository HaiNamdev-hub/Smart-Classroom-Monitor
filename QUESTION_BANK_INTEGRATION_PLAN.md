# Kế hoạch tích hợp Ngân hàng câu hỏi và Trộn đề

**Phạm vi tài liệu:** Giai đoạn 1 — khảo sát mã nguồn và đối chiếu schema hiện tại. Đây là kế hoạch thiết kế; chưa triển khai tính năng hay thay đổi schema.

## 1. Kết quả khảo sát hiện trạng

### 1.1. Cấu trúc dữ liệu đã xác minh

Đã đối chiếu migrations và metadata `information_schema` của MySQL hiện tại (`smart_classroom_monitor`) bằng truy vấn chỉ đọc. Không truy vấn nội dung/đáp án bài thi, không chỉnh sửa dữ liệu. Các bảng liên quan đang có cấu trúc:

| Bảng | Vai trò và khóa liên quan |
|---|---|
| `bai_tap` | Bài tập/đề thi; `lop_hoc_id`, `nguoi_tao_id` tới lớp và `nguoi_dung`. |
| `cau_hoi` | Câu hỏi hiện được lưu trực tiếp theo đề; `bai_tap_id` là `NOT NULL` FK tới `bai_tap`, nội dung, loại `trac_nghiem`/`tu_luan`, `diem DECIMAL(5,2)`, `thu_tu`, và `dap_an_mau LONGTEXT NULL`. Schema hiện tại không có chủ đề, độ khó, trạng thái sử dụng hay bản ghi câu hỏi độc lập. |
| `dap_an` | Các đáp án thuộc một `cau_hoi_id`; có nội dung, `la_dap_an_dung`, thứ tự. |
| `bai_kiem_tra_luot_lam` | Một lượt theo cặp đề và Sinh viên; `sinh_vien_id` FK tới `sinh_vien.id`, unique theo `(bai_tap_id, sinh_vien_id)`, có thời gian bắt đầu. |
| `bai_nop` | Bản nộp; `bai_tap_id` tới đề, nhưng `sinh_vien_id` FK tới **`nguoi_dung.id`**. Đây không phải cùng loại ID với `bai_kiem_tra_luot_lam.sinh_vien_id`. |
| `cau_tra_loi` | Câu trả lời tham chiếu `bai_nop_id`, `cau_hoi_id`, `dap_an_id` (nullable), nội dung tự luận; unique theo `(bai_nop_id,cau_hoi_id)`. Migration sau bổ sung `diem` để lưu điểm từng câu. |
| `diem` | Điểm tổng theo `bai_nop_id` (unique), `nguoi_cham_id` FK tới `nguoi_dung.id`, tổng điểm và nhận xét. |

Không có bảng ngân hàng câu hỏi độc lập trong các migrations/schema được khảo sát. Vì `cau_hoi.bai_tap_id` bắt buộc và mỗi câu trả lời lưu FK tới `cau_hoi.id`, câu hỏi hiện hữu là một phần của đề, không phải thực thể có thể gắn nguyên trạng vào nhiều đề.

### 1.2. Luồng tạo và nhập câu hỏi

- `GiangVienCauHoiController` cung cấp đọc/thêm/sửa/xóa câu hỏi theo `/bai-tap/{baiTapId}/cau-hoi`. Các thao tác kiểm tra người tạo đề, chạy trong transaction và gọi `ExamScoreAllocator::rebalance()`.
- Trước khi sửa/xóa, controller chặn nếu đề đã đến thời gian bắt đầu hoặc đã có `bai_nop`. Đây là hàng rào bảo vệ đề đã dùng; cần giữ nguyên và mở rộng cùng nguyên tắc cho luồng ngân hàng.
- UI hiện nằm trong `fe/src/components/GiangVien/BaiTap/TaoBaiKiemTra.vue`. Có nhập thủ công câu trắc nghiệm/tự luận và tab import; không tìm thấy component/ngân hàng độc lập hiện tại.
- `GiangVienNhapCauHoiController::preview()` đọc `.xlsx`, `.docx`, `.pdf`, trả tối đa 200 câu xem trước và chưa ghi DB. `.doc` không nằm trong danh sách hỗ trợ hiện tại. Excel cần các cột `loai_cau_hoi`, `noi_dung`, `diem`; Word/PDF được phân tích theo mẫu văn bản.
- `save()` validate cả danh sách rồi tạo câu hỏi/đáp án trong một transaction, không sửa hành vi import hiện có. Sau đó gọi phân bổ điểm.
- Routes API tạo/sửa/xóa câu hỏi và import nằm trong nhóm middleware `auth:sanctum` + nhóm phân quyền Giảng viên; controller còn kiểm tra `bai_tap.nguoi_tao_id` với user đăng nhập.

### 1.3. Điểm, đáp án và làm bài

- `ExamScoreAllocator` dùng đơn vị cent (0,01 điểm). Điểm tự luận giữ theo điểm giảng viên nhập; tổng tự luận không quá 10. Điểm trắc nghiệm nhận phần còn lại, chia nguyên cent và phân bổ phần dư theo thứ tự ổn định (`thu_tu`, sau đó `id`). `assertPublishable()` yêu cầu có câu và tổng 10; `assertStoredTotalIsTen()` xác minh tổng đã lưu mà không tự phân bổ lại.
- `GiangVienLichKiemTraController::update()` gọi `assertPublishable()` trước khi công bố lịch. Luồng bắt đầu giám sát và Sinh viên bắt đầu làm gọi `assertStoredTotalIsTen()`.
- `SinhVienKiemTraController::questions()` lấy câu hỏi theo `bai_tap_id`, sắp theo thứ tự; chỉ trả ID/nội dung đáp án, không trả `la_dap_an_dung` hoặc `dap_an_mau`. Sinh viên chọn đáp án theo ID ổn định, không theo nhãn A/B/C/D.
- `startExam()` tạo duy nhất một attempt cho `(bai_tap_id,sinh_vien_id)` và lần tải lại dùng lại attempt. Tuy nhiên dữ liệu hiện tại chưa lưu thứ tự câu hỏi/đáp án riêng cho từng Sinh viên; đáp án dựa trên `cau_hoi_id`/`dap_an_id` toàn đề.
- `submit()` chấm trắc nghiệm từ ID đáp án và cờ đúng trong `dap_an`, đồng thời lưu `cau_tra_loi`; tự luận được lưu và chấm riêng. Giảng viên xem chi tiết/lưu điểm thông qua `GiangVienApiController::chiTietBaiNop()` và `GiangVienChamDiemController::update()`.

## 2. Kết luận tương thích ngược

Không nên biến `cau_hoi` hiện tại thành ngân hàng bằng cách đổi `bai_tap_id` thành nullable hay đổi quan hệ hiện có: việc đó tác động nhiều controller/runtime và có nguy cơ làm hỏng truy vấn cũ. Cũng không nên dùng chung một `cau_hoi.id` cho nhiều đề vì đáp án, điểm, thứ tự và câu trả lời hiện phụ thuộc trực tiếp vào câu hỏi thuộc một đề.

Phương án ít rủi ro nhất là bổ sung thực thể ngân hàng riêng, giữ nguyên `cau_hoi`/`dap_an` làm **bản chụp câu hỏi trong đề**. Khi Giảng viên xác nhận tạo đề từ ngân hàng, hệ thống sao chép nội dung và đáp án đã chọn vào các bảng hiện hành trong một transaction; runtime của Sinh viên, lưu bài, nộp và chấm vẫn đọc đúng các bảng hiện có. Đây là bản chụp có chủ đích để lịch sử đề không đổi khi ngân hàng được cập nhật.

Đây không phải tạo một bảng trùng với bảng đã có: sau khảo sát, database không có thực thể ngân hàng. `cau_hoi` và `dap_an` vẫn là bản ghi đề/đáp án theo đề; thực thể mới quản lý nội dung tái sử dụng, chủ đề, độ khó và trạng thái ngân hàng.

## 3. Thiết kế đề xuất cho Giai đoạn 2 — Ngân hàng câu hỏi

Sau khi chủ thiết kế được duyệt và trước khi triển khai cần xác nhận đầy đủ trạng thái DB/migrations, dự kiến dùng migration additive, không chạy trên DB thật trong giai đoạn phân tích:

1. `ngan_hang_cau_hoi`: chủ sở hữu Giảng viên (`nguoi_dung.id`), nội dung, loại câu hỏi, chủ đề (ban đầu là trường văn bản có thể lọc; có thể chuẩn hóa taxonomy sau), độ khó enum `de|trung_binh|kho`, trạng thái active/inactive, version, timestamps.
2. `ngan_hang_dap_an`: FK tới câu hỏi ngân hàng, nội dung, cờ đáp án đúng và thứ tự. Với câu tự luận lưu đáp án mẫu/hướng dẫn chấm chỉ phía Giảng viên; không trả trong API Sinh viên.
3. Khóa ngoại/index tối thiểu cho chủ sở hữu, loại, độ khó, chủ đề, trạng thái và quan hệ đáp án. Quy tắc xóa nên ưu tiên soft disable/archival; không cascade xóa nội dung đang được tham chiếu bởi bản chụp đề.
4. Thêm provenance nullable vào bản chụp đề (ví dụ bảng liên kết `cau_hoi_ngan_hang_snapshot` từ `cau_hoi.id` tới ID/version ngân hàng), hoặc cột nguồn tương đương sau khi kiểm tra quy ước schema. Đây là phần additive để truy dấu, không bắt buộc cho runtime hiện tại.

API mới nằm trong nhóm Sanctum/Giảng viên; mọi truy vấn, nhân bản, cập nhật, vô hiệu hóa và dùng câu hỏi phải giới hạn theo `nguoi_tao_id` từ tài khoản đăng nhập. Không nhận quyền sở hữu từ ID do client tự khai. Với import, tái sử dụng parser và màn hình preview hiện có bằng một đích lưu mới (`đề hiện tại` hoặc `ngân hàng`); mặc định không đổi endpoint/import behavior cũ.

UI ngân hàng là module riêng. Có thể tái sử dụng các trường/validation UI từ `TaoBaiKiemTra.vue`, nhưng không refactor lớn component đang chạy; ưu tiên component editor mới dùng chung sau khi xác định props/events tối thiểu, hoặc wrapper độc lập nếu tái cấu trúc gây rủi ro. Câu trắc nghiệm phải có 2–6 lựa chọn và đúng một lựa chọn đúng; câu tự luận cần nội dung và hướng dẫn chấm. Nhân bản tạo ID ngân hàng mới; vô hiệu hóa không xóa câu hỏi đã dùng.

## 4. Thiết kế đề xuất cho Giai đoạn 3 — Tạo đề từ ngân hàng

- Bổ sung lựa chọn riêng “Tạo đề từ ngân hàng” trong khu vực bài tập; giữ nguyên nút và API tạo đề hiện tại.
- Giao diện cấu hình chọn lớp/môn theo quan hệ hiện có, chủ đề, số lượng, tỷ lệ độ khó, số trắc nghiệm/tự luận, điểm tự luận, trộn câu và trộn đáp án.
- Backend truy vấn ngân hàng thuộc đúng Giảng viên, active và đủ số lượng ở từng nhóm. Chọn câu ngẫu nhiên ở server; kiểm tra không trùng và phân bổ theo quota trước khi trả preview.
- Preview không ghi đề/câu hỏi. Khi xác nhận, transaction tạo `bai_tap` nháp (theo khả năng trạng thái hiện có), sao chép câu hỏi và đáp án thành `cau_hoi`/`dap_an`, ghi provenance, rồi áp dụng `ExamScoreAllocator` cho điểm trắc nghiệm. Nếu thiếu câu, validation lỗi, hoặc tổng điểm không hợp lệ thì rollback toàn bộ; không tạo đề một phần.
- Câu tự luận giữ điểm người dạy nhập (0–10, tối đa 2 chữ số thập phân); tổng tự luận <=10. MC nhận phần còn lại theo allocator hiện có. Nếu chỉ tự luận, tổng phải đúng 10 khi công bố. Nếu chỉ MC, tự chia 10.
- Không cho xác nhận nếu không đủ ngân hàng; không làm thay đổi đề đã công bố/đang thi/đã có bài nộp. Giữ nguyên hàng rào `checkCanEdit` và kiểm tra quyền ở cả API preview lẫn confirm.

## 5. Thiết kế Giai đoạn 4 — Trộn đề theo lượt (chưa triển khai)

Không dùng thao tác shuffle chỉ ở Frontend. Cần một snapshot server-side cố định cho mỗi attempt, tạo một lần khi bắt đầu và trả lại như cũ sau reload. Snapshot cần giữ thứ tự câu hỏi và thứ tự đáp án cùng ID thật; không lưu nhãn A/B/C/D làm định danh. Submission/answer vẫn tham chiếu ID ổn định, và chấm MC tiếp tục dựa trên `dap_an_id` + `la_dap_an_dung`; câu tự luận vẫn theo `cau_hoi_id` và chấm thủ công.

Thiết kế nên là các bảng mapping additive theo attempt (ví dụ `bai_kiem_tra_luot_lam_cau_hoi` và mapping đáp án), chứa attempt ID, question/answer ID, vị trí; có unique constraints để ngăn duplicate. Bắt đầu từ bản chụp đề đã xác nhận, không đọc nội dung ngân hàng động khi reload. Trước khi phát triển phải rà soát uniqueness/foreign keys hiện có và tương thích với attempt hiện tại. Không cập nhật mapping cho bài đang diễn ra hoặc attempt lịch sử.

## 6. Trình tự triển khai và tiêu chí an toàn

1. Đóng băng hợp đồng hiện tại qua test characterization cho CRUD thủ công, import, điểm, API Sinh viên và nộp/chấm đề cũ.
2. Phê duyệt schema ngân hàng additive và chính sách chủ đề/archival/version trước migration. Chỉ chạy migration trên DB test riêng; không chạy migration DB thật nếu chưa được chấp thuận.
3. Triển khai API + UI ngân hàng độc lập; giữ nguyên route cũ và thêm tests quyền sở hữu/không lộ đáp án.
4. Triển khai preview/confirm tạo đề bằng snapshot trong transaction; test rollback, thiếu ngân hàng, bài hỗn hợp và tổng điểm.
5. Chỉ sau khi Giai đoạn 2–3 ổn định mới thiết kế/triển khai mapping trộn per-attempt. Không bật trên bài đang hoạt động; rollout chỉ cho đề mới.

Kiểm thử cần có: câu hỏi thủ công cũ và import Excel/Word/PDF; đề cũ đọc/nộp/chấm; chủ sở hữu đúng/sai; Sinh viên không nhận đáp án đúng/đáp án mẫu; CRUD và disable ngân hàng; preview không mutation; insufficient pool rollback; chỉ MC/chỉ essay/hỗn hợp; điểm giữ đúng đến 0,01; câu/đáp án được snapshot độc lập khỏi sửa bank; trộn ổn định sau reload; auto-grade đúng theo ID; và bất biến số câu/điểm của dữ liệu lịch sử.

## 7. Giới hạn của giai đoạn này

- Đã đọc source và đối chiếu schema hiện tại chỉ bằng metadata read-only. Không đọc nội dung câu hỏi thật.
- Chưa tạo migration, chưa sửa code, chưa thêm API/UI, chưa chạy build hoặc Laravel tests vì giai đoạn được yêu cầu chỉ là phân tích và lập kế hoạch.
- Việc tạo mới thực thể ngân hàng và bảng snapshot/provenance cần được kiểm tra với toàn bộ trạng thái migration trên DB test khi bước triển khai được cho phép. Không chạy migration trên DB thật trong bước này.
