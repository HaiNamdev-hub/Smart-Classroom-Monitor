# BÁO CÁO TRIỂN KHAI TỐI ƯU HIỆU NĂNG & KIỂM CHỨNG TOÀN VẸN
# SMART CLASSROOM MONITOR — PERFORMANCE OPTIMIZATION IMPLEMENTATION

**Ngày thực hiện:** 20/11/2026  
**Chuyên gia thực hiện:** Senior Laravel Developer, PostgreSQL Concurrency Engineer, DevOps Engineer & QA Lead  
**Nguyên tắc cốt lõi:** Tuyệt đối không đánh đổi tính toàn vẹn bài thi để lấy tốc độ; giữ nguyên mã nguồn sạch sẽ, không sửa đổi file `.env` production, không commit/push lên Git.

---

## 1. DANH SÁCH CÁC FILE ĐÃ CHỈNH SỬA & TẠO MỚI

### 1.1. File mã nguồn đã chỉnh sửa:
1. `be/app/Http/Controllers/SinhVienKiemTraController.php`:
   - Tối ưu hóa hàm `submit()`: Chuyển đổi từ vòng lặp `foreach` ghi từng câu trả lời riêng biệt sang **Batch INSERT có phân đoạn (chunked by 100)**.
2. `be/app/Http/Controllers/WebRtcSignalController.php`:
   - Bổ sung xác thực cấu trúc và ràng buộc kích thước payload chặt chẽ (`sdp` max 65,536 ký tự, `candidate` max 4,096 ký tự, `restart` boolean).
3. `be/app/Providers/AppServiceProvider.php`:
   - Bổ sung Rate Limiter chuyên dụng `webrtc-signal` (1,200 req/phút/IP và 180 req/phút/user).
4. `be/routes/api.php`:
   - Gắn middleware `throttle:webrtc-signal` vào route `POST /api/webrtc/signal`.

### 1.2. File kiểm thử & cấu hình tạo mới:
1. `be/tests/Feature/OnlineExamSubmitBatchTest.php`:
   - Kiểm chứng thực nghiệm số câu lệnh `INSERT` vào bảng `cau_tra_loi` (giảm xuống đúng 1 batch INSERT), kiểm tra tính toàn vẹn điểm số, tự luận, trắc nghiệm và chống nộp trùng.
2. `be/tests/Feature/WebRtcSignalSecurityTest.php`:
   - Kiểm chứng xác thực bảo mật WebRTC signaling (chặn SDP quá khổ, chặn candidate quá khổ, xác thực vai trò).
3. `be/docker/php-opcache.ini`:
   - Cấu hình OPcache chuẩn production cho môi trường Docker Alpine (`validate_timestamps=0`, 64MB RAM).
4. `be/docker/php-fpm-staging-prod.conf`:
   - Cấu hình phân bổ worker an toàn theo tài nguyên (4 workers cho 512MB RAM, 8 workers cho 1GB RAM).
5. `performance-tests/deployed/staging-stepped-benchmark.k6.js`:
   - Kịch bản benchmark 6 cấp độ (1 -> 5 -> 10 -> 20 -> 30 -> 50 VUs) kèm Circuit Breakers.

---

## 2. LÝ DO THAY ĐỔI & PHÂN TÍCH KỸ THUẬT

### 2.1. Tối ưu hóa hàm `submit()` (Phase 1)
- **Vấn đề cũ:** Khi sinh viên nộp bài thi có 40 câu hỏi, controller thực hiện 40 câu lệnh `INSERT INTO cau_tra_loi` tuần tự. Khi kết nối Database qua Internet (Render US-West $\leftrightarrow$ Neon US-East, RTT ~50ms), chỉ riêng việc ghi câu trả lời đã làm giữ transaction trong hơn **2.0 giây**, chiếm dụng kết nối database pool.
- **Giải pháp triển khai:** Thu thập toàn bộ câu trả lời hợp lệ vào mảng `$answerRecords`, lọc bỏ các giá trị `null` và chuỗi rỗng `''`, sau đó dùng `array_chunk($answerRecords, 100)` để thực hiện **Batch INSERT**.
- **Bảo đảm toàn vẹn:**
  - Không thay đổi thứ tự câu hỏi và đáp án đã lưu trong `ExamAttemptOrder`.
  - Giữ nguyên câu trả lời tự luận (`noi_dung_tra_loi`) và trắc nghiệm (`dap_an_id`).
  - Điểm số được tính toán trước khi ghi dữ liệu dựa trên thang điểm 10 đã được bảo chứng bởi `ExamScoreAllocator`.
  - Transaction đảm bảo nếu có bất kỳ lỗi nào xảy ra thì toàn bộ bài nộp được rollback sạch sẽ.

### 2.2. Bảo mật WebRTC Signaling (Phase 3)
- **Vấn đề cũ:** Route `/api/webrtc/signal` nhận trường `data` dạng `['nullable']` mà không có giới hạn kích thước hay định dạng cấu trúc, đồng thời chưa có rate limit. Kẻ tấn công hoặc client gặp lỗi lặp vô tận có thể gửi payload hàng chục MB hoặc flood hàng trăm request/giây làm nghẽn tiến trình Reverb WebSocket.
- **Giải pháp triển khai:**
  - Giới hạn độ dài chuỗi SDP tối đa 64KB (`max:65536`).
  - Giới hạn chuỗi candidate tối đa 4KB (`max:4096`).
  - Gắn rate limit 180 req/phút/user (đáp ứng thoải mái cho quá trình ICE trickle 10–25 candidates ban đầu nhưng chặn đứng các cuộc tấn công flooding).

---

## 3. KẾT QUẢ TESTS TRƯỚC VÀ SAU KHI TỐI ƯU

| Bộ Test Suite | Số test trước | Kết quả trước | Số test sau | Kết quả sau | Trạng thái |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Feature: Submit & Batch Insert** | 0 | Chưa có | 1 test (12 assertions) | **PASS (0.29s)** | ✅ Mới bổ sung |
| **Feature: WebRTC Signal Security**| 0 | Chưa có | 5 tests (12 assertions)| **PASS (0.84s)** | ✅ Mới bổ sung |
| **Toàn bộ Backend Test Suite** | 66 tests | 66 PASS | **72 tests (750 assertions)** | **PASS 100% (25.06s)** | ✅ Không regression |
| **Frontend Unit Tests (`fe`)** | 12 tests | 12 PASS | **12 tests (12 assertions)** | **PASS 100% (1.33s)** | ✅ Không regression |
| **Vite Production Build (`fe`)** | — | Thành công | 180 modules transformed | **Built in 3.35s (0 errors)** | ✅ Hoàn hảo |
| **Kiểm tra cú pháp (`git diff --check`)**| — | Không lỗi | 0 whitespace errors | **Clean 100%** | ✅ Hoàn hảo |

---

## 4. SỐ LƯỢNG CÂU LỆNH INSERT TRƯỚC VÀ SAU KHI TỐI ƯU

Kiểm chứng thực nghiệm bằng cơ chế `DB::enableQueryLog()` và `DB::getQueryLog()` trong test case `OnlineExamSubmitBatchTest`:

* **Kịch bản kiểm thử:** Đề thi gồm 5 câu hỏi (4 câu trắc nghiệm + 1 câu tự luận). Sinh viên trả lời 3 câu hợp lệ (1 câu bỏ trống null, 1 câu chuỗi rỗng).
* **Trước khi tối ưu:**
  - Thực hiện **3 câu lệnh INSERT riêng lẻ** vào bảng `cau_tra_loi`.
  - Với đề 40 câu hỏi: Thực hiện **40 câu lệnh INSERT**.
* **Sau khi tối ưu:**
  - Thực hiện **đúng 1 câu lệnh BATCH INSERT** duy nhất:
    ```sql
    insert into "cau_tra_loi" ("bai_nop_id", "cau_hoi_id", "dap_an_id", "noi_dung_tra_loi", "created_at", "updated_at") values (?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?)
    ```
* **Đo đạc giảm số lượng truy vấn I/O:**
  - Đối với đề thi 40 câu: Số lệnh INSERT giảm từ **40 queries xuống còn 1 query** (giảm 97.5% số roundtrip mạng cơ sở dữ liệu).

---

## 5. TRẠNG THÁI KHÓA DỮ LIỆU TẠI `startExam()` (PHASE 2)

* **Phân tích lý do tồn tại khóa `bai_tap`:**
  - Tại `SinhVienKiemTraController::startExam()` (dòng 512): Có lệnh `DB::table('bai_tap')->where('id', $baiTapId)->lockForUpdate()->first()`.
  - Khóa này được đặt để bảo vệ cuộc đua tạo lượt làm bài mới (absent-attempt creation race). Khi một sinh viên chưa có bản ghi trong `bai_kiem_tra_luot_lam`, không thể khóa một dòng chưa tồn tại bằng `lockForUpdate()`.
* **Phân tích rủi ro Concurrency trên PostgreSQL:**
  - Bảng `bai_kiem_tra_luot_lam` đã có Unique Constraint: `UNIQUE(bai_tap_id, sinh_vien_id)`.
  - Nếu chỉ đơn thuần bỏ lệnh khóa `bai_tap` mà 2 request cùng của 1 sinh viên gửi tới đồng thời: Request thứ hai sẽ đụng lỗi PostgreSQL SQLSTATE `23505` (unique_violation).
  - Trong PostgreSQL, khi một truy vấn văng lỗi `23505`, toàn bộ Transaction hiện tại sẽ bị chuyển sang trạng thái **Aborted** (`current transaction is aborted, commands ignored until end of transaction block`). Không thể catch ngoại lệ rồi tiếp tục query trong cùng transaction đó mà bắt buộc phải Rollback toàn bộ transaction và thử lại từ đầu.
* **Quyết định tuân thủ chỉ thị an toàn:**
  > Theo đúng yêu cầu của bạn: *"Chỉ áp dụng thay đổi concurrency sau khi có kiểm thử PostgreSQL staging riêng. Nếu chưa có staging, giữ nguyên khóa hiện tại và báo BLOCKED."*  
  > Hiện tại máy trạm cục bộ chỉ có SQLite memory cho unit test và MySQL local, **chưa có cụm PostgreSQL staging riêng độc lập**. Do đó, **khóa `lockForUpdate()` trên `bai_tap` được GIỮ NGUYÊN 100% để bảo đảm an toàn dữ liệu tuyệt đối**, và trạng thái mục này được ghi nhận là **BLOCKED (Chờ hạ tầng Staging)**.

---

## 6. TRẠNG THÁI BẢO MẬT WEBRTC SIGNALING (PHASE 3)

Đã hoàn thiện và kiểm chứng tự động:
1. **Rate Limiting:** Gắn `throttle:webrtc-signal` (180 req/phút/user và 1,200 req/phút/IP), chống spam tín hiệu hoặc vòng lặp lỗi client.
2. **Payload Size Bound:**
   - Offer / Answer: Bắt buộc cấu trúc mảng, type hợp lệ, trường `sdp` tối đa 65,536 ký tự.
   - Candidate: Bắt buộc trường `candidate` tối đa 4,096 ký tự, `sdpMid` tối đa 255 ký tự, `sdpMLineIndex` là số nguyên trong khoảng 0–65535.
   - Request Offer: Chỉ cho phép trường `restart` kiểu boolean.
3. **Authorization & RBAC:**
   - Chỉ giảng viên phụ trách lớp của phiên giám sát mới được gửi `answer`, `request_offer`.
   - Sinh viên chỉ được gửi tín hiệu cho chính hồ sơ của mình (`offer`, `camera_ready`, `camera_stopped`).
   - Người dùng ngoài lớp bị từ chối với mã lỗi HTTP 403.

---

## 7. CẤU HÌNH PHP-FPM & OPCACHE ĐÃ CHUẨN HÓA (PHASE 4)

Đã tạo sẵn 2 file cấu hình chuyên dụng trong `be/docker/`:
1. `be/docker/php-opcache.ini`:
   - `opcache.memory_consumption = 64M`
   - `opcache.max_accelerated_files = 10000`
   - `opcache.validate_timestamps = 0` (Tăng tốc I/O trong Docker immutable)
2. `be/docker/php-fpm-staging-prod.conf`:
   - Khuyến nghị cấu hình theo mức tài nguyên phần cứng:
     - **Gói 512MB RAM (Render Free):** Tối đa `pm.max_children = 4` (Tổng RAM ~295MB, không bị OOM Killer).
     - **Gói 1GB RAM (Staging / Starter $7):** `pm.max_children = 8`, `pm.max_requests = 1000` (xử lý mượt mà cho lớp 30–50 sinh viên).
3. **Database Connection Pooling:**
   - Để tránh cạn kiệt kết nối Neon PostgreSQL khi có 50 sinh viên kết nối đồng thời, cấu hình chuỗi kết nối sử dụng endpoint PgBouncer pooling (có hậu tố `-pooler.us-east-1.aws.neon.tech`). Tuyệt đối không thay đổi secret môi trường production hiện tại.

---

## 8. KỊCH BẢN KIỂM THỬ TẢI TRÊN POSTGRESQL STAGING (PHASE 6)

Đã tạo kịch bản k6 tự động tại:  
`performance-tests/deployed/staging-stepped-benchmark.k6.js`

* **Tiến trình tăng tải có kiểm soát (Stepped Stages):**
  - Stage 1 (30s): 1 VU (Smoke test & kiểm tra tính đúng đắn)
  - Stage 2 (45s): 5 VUs (Tải cơ sở)
  - Stage 3 (1m): 10 VUs (Ngưỡng 1 worker)
  - Stage 4 (1m30s): 20 VUs (Lớp học nhỏ)
  - Stage 5 (2m): 30 VUs (Mục tiêu lớp học chuẩn)
  - Stage 6 (2m): 50 VUs (Mục tiêu kỳ thi mở rộng)
* **Cơ chế ngắt an toàn (Circuit Breakers):**
  - Tự động dừng khẩn cấp nếu tỷ lệ lỗi HTTP `http_req_failed >= 5%`.
  - Tự động dừng khẩn cấp nếu độ trễ p95 vượt quá 10,000ms.
  - Kịch bản được thiết kế riêng cho Staging, không chạy lên cụm Production Render/Neon.

---

## 9. CÁC VẤN ĐỀ CÒN LẠI VÀ KHUYẾN NGHỊ TIẾP THEO

1. **Khóa `startExam()` trên PostgreSQL:** Cần dựng môi trường PostgreSQL Staging riêng (ví dụ Docker PostgreSQL 16 cục bộ) để chạy benchmark song song và kiểm chứng an toàn cơ chế retry `23505` trước khi gỡ bỏ `lockForUpdate` trên `bai_tap`.
2. **Cấu hình TURN Server:** Cần bổ sung biến môi trường TURN trên Render Dashboard khi sẵn sàng tổ chức thi chính thức để đảm bảo camera qua mạng 4G/CGNAT.
3. **Tự lưu trữ Model AI MediaPipe:** Tải các file `.tflite` từ Google Cloud về thư mục `fe/public/models/` trong giai đoạn chuẩn bị offline phòng máy.

---
*(Ghi chú: Toàn bộ quá trình thực hiện không thay đổi file .env production, không commit và không push Git. Dự án đạt 72/72 tests Backend và 12/12 tests Frontend pass hoàn toàn).*
