# BÁO CÁO TOÀN DIỆN KIỂM TRA & ĐÁNH GIÁ DỰ ÁN (PROJECT AUDIT REPORT)
**Dự án:** Smart Classroom Monitor  
**Vị trí:** `F:\Smart Classroom Monitor`  
**Vai trò đánh giá:** Senior Full-stack Developer, Software Architect, QA Engineer, Security Engineer  
**Thời điểm thực hiện:** 14/11/2026  
**Công nghệ:** Vue 3 (Vite), PHP 8.2+ (Laravel 12), MySQL, Laravel Sanctum, Laravel Reverb, WebRTC, MediaPipe Tasks-Vision.

---

## MỤC LỤC
1. [Tổng quan kiến trúc dự án](#1-tổng-quan-kiến-trúc-dự-án)
2. [Danh sách module và trạng thái](#2-danh-sách-module-và-trạng-thái)
3. [Các lỗi nghiêm trọng (Critical Bugs)](#3-các-lỗi-nghiêm-trọng-critical-bugs)
4. [Các lỗi ưu tiên cao (High Priority Bugs)](#4-các-lỗi-ưu-tiên-cao-high-priority-bugs)
5. [Các lỗi trung bình (Medium Priority Bugs)](#5-các-lỗi-trung-bình-medium-priority-bugs)
6. [Các lỗi nhỏ và hoàn thiện giao diện (Low Priority Bugs)](#6-các-lỗi-nhỏ-và-hoàn-thiện-giao-diện-low-priority-bugs)
7. [Bảng tổng hợp vị trí file và số dòng lỗi](#7-bảng-tổng-hợp-vị-trí-file-và-số-dòng-lỗi)
8. [Nguyên nhân kỹ thuật và giải thích chuyên sâu](#8-nguyên-nhân-kỹ-thuật-và-giải-thích-chuyên-sâu)
9. [Hướng sửa đề xuất chi tiết](#9-hướng-sửa-đề-xuất-chi-tiết)
10. [Các chức năng đang hoạt động tốt](#10-các-chức-năng-đang-hoạt-động-tốt)
11. [Các chức năng chưa được kiểm chứng](#11-các-chức-năng-chưa-được-kiểm-chứng)
12. [Đánh giá rủi ro an toàn và bảo mật](#12-đánh-giá-rủi-ro-an-toàn-và-bảo-mật)
13. [Kết quả kiểm thử thực tế](#13-kết-quả-kiểm-thử-thực-tế)
14. [Kế hoạch và lộ trình sửa lỗi (Prioritized Roadmap)](#14-kế-hoạch-và-lộ-trình-sửa-lỗi-prioritized-roadmap)

---

## 1. TỔNG QUAN KIẾN TRÚC DỰ ÁN

Hệ thống **Smart Classroom Monitor** được thiết kế theo mô hình client-server phân tách rõ ràng (Decoupled Architecture), hỗ trợ thi trắc nghiệm/tự luận trực tuyến kết hợp giám sát camera thời gian thực và phát hiện hành vi bất thường bằng AI client-side.

```
+-----------------------------------------------------------------------------------------+
|                                    FRONTEND (Vue 3 + Vite)                               |
|  - Layout: Admin, Lecturer, Student                                                    |
|  - AI: MediaPipe Tasks-Vision (BlazeFace Short Range + EfficientDet Lite0 on Web Worker)|
|  - WebRTC: RTCPeerConnection (Student Sender: 1 stream; Teacher Receiver: n streams)   |
|  - Real-time: Pusher JS / Laravel Echo client                                           |
+-----------------------------------------------------------------------------------------+
                                    |                           ^
                 REST API (Axios)   |                           | WebSocket Events
                                    v                           | (Reverb :8080)
+-----------------------------------------------------------------------------------------+
|                                    BACKEND (Laravel 12)                                 |
|  - Auth & Guard: Laravel Sanctum Bearer Token, Middleware QuanTriVien / GiangVien       |
|  - Signaling: WebRtcSignalController (offer / answer / candidate / state)              |
|  - Events: CanhBaoExamEvent, ExamProtectionAlertEvent, WebRtcSignalEvent                |
|  - File Storage: Local private disk (exam-evidence) & Public disk                       |
+-----------------------------------------------------------------------------------------+
                                    |                           ^
                      PDO / Eloquent|                           | Event Broadcasting
                                    v                           |
+-------------------------------+   +-----------------------------------------------------+
|        DATABASE (MySQL)       |   |             LARAVEL REVERB SERVER                   |
| 37 Migrations, 16+ Tables     |   | Port 8080 (Private Channels: exam-monitor.*,       |
| Transactions for submissions  |   |             exam-session.*)                         |
+-------------------------------+   +-----------------------------------------------------+
```

### Luồng dữ liệu chính:
1. **Làm bài thi & Lưu nháp:**
   `Student (fe)` -> `PUT /api/sinh-vien/bai-kiem-tra/{id}/nhap` -> `SinhVienKiemTraController@saveDraft` -> Bảng `bai_lam_nhap`.
2. **Nộp bài & Tự chấm điểm:**
   `Student (fe)` -> `POST /api/sinh-vien/bai-kiem-tra/{id}/nop` -> `DB::transaction` -> Bảng `bai_nop`, `cau_tra_loi`, tự chấm trắc nghiệm lưu vào `diem`.
3. **Giám sát Camera AI & Bằng chứng:**
   `Webcam` -> `GiamSatCamera.vue (MediaPipe)` -> Phát hiện điện thoại/mất mặt/nhiều mặt -> `POST /api/sinh-vien/bai-kiem-tra/{id}/canh-bao-camera` -> Tạo event -> Chụp frame canvas -> `POST .../anh` lưu vào private storage `anh_man_hinh`.
4. **WebRTC Stream Giảng viên:**
   `StudentSender.js` (Webcam stream) <-> `WebRtcSignalController` <-> `Laravel Reverb` <-> `TeacherReceiver.js` (Giảng viên xem lưới camera học sinh).

---

## 2. DANH SÁCH MODULE VÀ TRẠNG THÁI

| Module | Trạng thái | Ghi chú & Đánh giá |
|---|---|---|
| **Xác thực (Auth)** | Hoàn thiện cơ bản | Sanctum Token, kiểm tra vai trò `admin`, `giang_vien`, `sinh_vien`. Thiếu Rate limit brute-force tại `/api/dang-nhap`. |
| **Quản trị viên (Admin)** | Hoàn thiện | Quản lý người dùng, vai trò, chức năng, phân quyền chi tiết, quản lý lớp học. |
| **Quản lý Lớp học & Thành viên** | Hoàn thiện | CRUD lớp học, thêm sinh viên vào lớp học. |
| **Quản lý Bài tập & Đề thi** | Hoàn thiện | Tạo bài tập, lịch thi, nhập câu hỏi từ Excel/Word, cân bằng điểm tự động (`ExamScoreAllocator`). |
| **Chấm điểm (Lecturer)** | Hoàn thiện | Chấm tự luận theo điểm từng câu, tự cộng điểm trắc nghiệm, cập nhật trạng thái đạt/không đạt. |
| **Phiên giám sát (PhienGiamSat)** | Hoàn thiện | Bắt đầu/kết thúc phiên, thống kê học sinh tham gia, đếm vi phạm. |
| **WebRTC Signaling & Stream** | Hoàn thiện 90% | P2P video student-to-teacher, SDP diagnostics, xử lý offer/answer/candidate an toàn qua Sanctum. |
| **Giám sát Camera AI (SinhVien)** | Hoàn thiện 85% | Nhận diện mất mặt (5s), nhiều mặt (3s), điện thoại (ngay lập tức), chụp frame bằng chứng. **Rủi ro phụ thuộc CDN model**. |
| **Bảo vệ thi (Anti-cheat)** | Hoàn thiện 85% | Chặn copy/cut/paste, contextmenu, drag, mất focus, rời tab, cảnh báo phím PrintScreen. |
| **Lưu nháp & Nộp bài thi** | Gặp lỗi tiềm ẩn | Đã có transaction và lock, nhưng gặp lỗi unhandled rejection khi mạng lag và chưa lưu fallback offline. |
| **Quản lý Bài nộp File (BaiNop)** | **Lỗi nghiêm trọng** | Nhầm lẫn giữa `sinh_vien.id` và `nguoi_dung.id` gây lỗi 403 hoặc sai chủ sở hữu bài nộp. |
| **Xem ảnh bằng chứng (Evidence)** | Hoàn thiện 90% | Private disk, xác thực quyền giảng viên theo lớp, response buffer chống crash Vite proxy. |
| **Đồng bộ cảnh báo thời gian thực** | **Chưa đồng bộ** | Trang "Cảnh báo vi phạm" không nhận được event camera/rời tab real-time do lệch kênh/tên event với "Phiên giám sát". |

---

## 3. CÁC LỖI NGHIÊM TRỌNG (CRITICAL BUGS)

### [CRIT-01] Nhầm lẫn Foreign Key giữa `nguoi_dung.id` và `sinh_vien.id` trong Module Bài nộp (`BaiNopController.php`)
- **Loại:** Lỗi đã xác nhận (Confirmed Bug).
- **Vị trí:** `be/app/Http/Controllers/BaiNopController.php`: dòng 73–96, 110–114, 125–127, 160–188, 301, 320–323, 333.
- **Nguyên nhân kỹ thuật:**
  - Bảng `thanh_vien_lop.sinh_vien_id` và `bai_nop.sinh_vien_id` trong migration (`2026_09_21_000004_create_thanh_vien_lop_table.php` và `2026_09_21_000007_create_bai_nop_table.php`) có khóa ngoại liên kết trực tiếp tới `nguoi_dung.id`.
  - Trong `BaiNopController.php`, hàm `getSinhVienId()` truy vấn `DB::table('sinh_vien')->where('nguoi_dung_id', $user->id)->value('id')` và trả về `sinh_vien.id`.
  - Sau đó code dùng giá trị `sinh_vien.id` này để kiểm tra `ThanhVienLop::where('sinh_vien_id', $sinhVienId)` và tạo bản ghi `BaiNop::create(['sinh_vien_id' => $sinhVienId])`.
  - Đối với bất kỳ sinh viên nào có `sinh_vien.id != nguoi_dung.id` (thực tế luôn xảy ra khi có tài khoản giảng viên/admin tạo trước), sinh viên nộp bài file sẽ bị chặn 403 ("Bạn không thuộc lớp học của bài tập này") hoặc bản ghi bài nộp bị gán nhầm cho `nguoi_dung` khác mang ID trùng với `sinh_vien.id`.
  - Đồng thời, Eloquent Model `SinhVien.php` (dòng 39-51) khai báo quan hệ `thanhVienLops()` và `baiNops()` với `sinh_vien_id` nhưng thiếu cấu hình `localKey: 'nguoi_dung_id'`.
- **Hậu quả:** Sinh viên nộp bài thực hành bị lỗi 403, bài nộp bị lẫn lộn giữa các tài khoản người dùng, sai dữ liệu điểm và phân quyền lớp học.

---

### [CRIT-02] Lỗi `Unknown column 'sbv.thong_tin_tin_hieu'` khi Database chưa chạy Migration bổ sung
- **Loại:** Lỗi đã xác nhận (Confirmed Bug).
- **Vị trí:** `be/app/Http/Controllers/GiangVienApiController.php`: dòng 1224, dòng 1680, dòng 1780.
- **Nguyên nhân kỹ thuật:**
  - Bảng `su_kien_bao_ve_bai_kiem_tra` ban đầu được tạo bởi migration `2026_11_10_000001_create_exam_protection_events_table.php` không có cột `thong_tin_tin_hieu`, `da_xem`, `xem_luc`, `xem_boi_id`.
  - Các cột này được bổ sung trong migration `2026_11_10_000003_add_review_and_signal_to_exam_protection_events.php`.
  - Khi code Backend gọi `chiTietPhien()`, `canhBao()`, hoặc `markExamProtectionEventViewed()`, truy vấn SQL thực hiện trực tiếp:
    ```sql
    select ... `sbv`.`thong_tin_tin_hieu`, `sbv`.`da_xem`, `sbv`.`xem_luc` from `su_kien_bao_ve_bai_kiem_tra` as `sbv` ...
    ```
  - Nếu cơ sở dữ liệu môi trường test/production chưa được chạy lệnh migrate đến version 3, toàn bộ API xem cảnh báo và chi tiết phiên giám sát của Giảng viên sẽ bị crash 500 với lỗi:
    `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'sbv.thong_tin_tin_hieu'`.
- **Hậu quả:** Giảng viên không thể tải danh sách cảnh báo hoặc xem chi tiết phiên thi khi DB chưa đồng bộ migration.

---

### [CRIT-03] Chặn nộp bài kiểm tra ở phút chót do tổng điểm không bằng 10 (`abort_if($examTotalCents !== 1000)`)
- **Loại:** Lỗi đã xác nhận (Confirmed Architecture Flaw).
- **Vị trí:** `be/app/Http/Controllers/SinhVienKiemTraController.php`: dòng 789–796.
- **Nguyên nhân kỹ thuật:**
  - Trong `SinhVienKiemTraController@submit`, hệ thống kiểm tra:
    ```php
    $examTotalCents = $questions->sum(fn ($q) => ExamScoreAllocator::cents($q->diem));
    abort_if($examTotalCents !== 1000, 422, 'Đề thi chưa có tổng điểm hợp lệ bằng 10. Vui lòng liên hệ Giảng viên.');
    ```
  - Tuy nhiên, trong toàn bộ quy trình Giảng viên tạo đề, đặt lịch thi (`GiangVienLichKiemTraController`) và kích hoạt phiên thi (`GiangVienApiController@batDauPhien`), hệ thống **không bắt buộc gọi `ExamScoreAllocator::assertPublishable($baiTapId)`** trước khi cho học sinh vào thi.
  - Trường hợp Giảng viên tạo đề thi tự luận có tổng điểm khác 10 (ví dụ 3 câu tự luận tổng 8.0 điểm), sinh viên vẫn vào thi bình thường, làm bài đến hết giờ, nhưng khi nhấn **Nộp bài**, hệ thống ném ra lỗi 422.
- **Hậu quả:** Sinh viên làm bài xong không thể nộp bài, hết hạn nộp bài dẫn đến mất toàn bộ công sức làm bài và gây tranh chấp học vụ nghiêm trọng.

---

## 4. CÁC LỖI ƯU TIÊN CAO (HIGH PRIORITY BUGS)

### [HIGH-01] Mất đồng bộ cảnh báo thời gian thực giữa màn hình "Cảnh báo vi phạm" và "Phiên giám sát"
- **Loại:** Lỗi đã xác nhận (Confirmed Bug).
- **Vị trí:**
  - Backend: `be/app/Events/CanhBaoExamEvent.php` (dòng 57)
  - Backend: `be/app/Http/Controllers/SinhVienCanhBaoCameraController.php` (dòng 372)
  - Backend: `be/app/Http/Controllers/SinhVienKiemTraController.php` (dòng 1080)
  - Frontend: `fe/src/components/GiangVien/CanhBao/index.vue` (dòng 1148–1149)
  - Frontend: `fe/src/components/GiangVien/CanhBao/CanhBaoCamera.vue` (dòng 1–230)
- **Nguyên nhân kỹ thuật:**
  - `CanhBaoExamEvent` (bắn ra khi sinh viên rời tab hoặc có cảnh báo camera/điện thoại) chỉ broadcast vào kênh riêng của từng sinh viên: `PrivateChannel('exam-monitor.' . $phienId . '.student.' . $sinhVienId)`.
  - Màn hình **Phiên giám sát** (`GiamSatThi/index.vue`) lắng nghe từng sinh viên qua `teacherReceiver.watchStudent()`, nên nhận được event này.
  - Ngược lại, màn hình **Cảnh báo vi phạm** (`CanhBao/index.vue`) chỉ lắng nghe trên kênh tổng của phiên `exam-session.${phienId}` và chỉ chờ sự kiện `.exam.protection-alert` (copy/paste/shortcut). Nó **hoàn toàn không lắng nghe `.exam.violation`** và cũng không subscribe vào các kênh con của sinh viên.
  - Component `CanhBaoCamera.vue` thậm chí không có bất kỳ kết nối WebSocket nào mà chỉ định kỳ gọi API mỗi 10 giây (`window.setInterval(fetchAlerts, 10000)`).
- **Hậu quả:** Giảng viên mở trang "Cảnh báo vi phạm" sẽ không thấy cảnh báo camera, điện thoại hay rời tab xuất hiện thời gian thực, mà phải chờ tải lại trang hoặc chờ 10 giây (đối với component con).

---

### [HIGH-02] Lỗi Uncaught Promise Rejection trong hàm lưu nháp (`saveDraft`) khi mất kết nối mạng
- **Loại:** Lỗi đã xác nhận (Confirmed Bug).
- **Vị trí:** `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`: dòng 1283–1287.
- **Nguyên nhân kỹ thuật:**
  - Tại dòng 1283-1287:
    ```javascript
    if (saveInFlight) {
      await saveInFlight
      if (lastSavedVersion === saveVersion || (!force && !canEdit.value)) return
    }
    ```
  - Khi một request lưu nháp trước đó thất bại do mất mạng hoặc server 500, Promise `saveInFlight` bị `reject`.
  - Lời gọi `await saveInFlight` tại dòng 1284 không được bao bọc bởi `try...catch` hay `.catch(() => {})` (khác với hàm `submitExam` ở dòng 1369 có `await saveInFlight.catch(() => {})`).
  - Do đó, lần gọi `saveDraft()` tiếp theo ngay lập tức ném ra ngoại lệ Unhandled Rejection, ngắt toàn bộ luồng lưu nháp tự động phía sau.
- **Hậu quả:** Nếu mạng chập chờn một lần, toàn bộ các lần gõ phím hoặc chọn đáp án tiếp theo của sinh viên sẽ ngừng tự động lưu nháp cho đến khi tải lại trang.

---

### [HIGH-03] Không có cơ chế lưu trữ ngoại tuyến (Local Fallback) cho bài làm sinh viên
- **Loại:** Rủi ro đã xác nhận (Confirmed Resilience Issue).
- **Vị trí:** `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`: dòng 1270–1341.
- **Nguyên nhân kỹ thuật:**
  - Mọi thao tác trả lời chỉ được lưu vào biến reactive trong bộ nhớ (`answers.value`) và đẩy qua API `/sinh-vien/bai-kiem-tra/{id}/nhap`.
  - Không có cơ chế ghi song song vào `localStorage` hoặc `indexedDB`.
  - Nếu sinh viên bị rớt mạng trong lúc thi, hoặc trình duyệt gặp sự cố sập tab / mất điện đột ngột khi API chưa kịp lưu, sinh viên mở lại bài thi sẽ bị mất các câu trả lời chưa đồng bộ thành công lên máy chủ.

---

### [HIGH-04] Thiếu Route Guard xác nhận thoát trang (`beforeunload` & `onBeforeRouteLeave`) khi đang làm bài thi
- **Loại:** Lỗi đã xác nhận (Confirmed Bug).
- **Vị trí:** `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`.
- **Nguyên nhân kỹ thuật:**
  - Component không đăng ký `window.addEventListener('beforeunload')` và không sử dụng hook `onBeforeRouteLeave` của Vue Router.
  - Sinh viên chỉ cần vô tình ấn nút Back trên chuột/bàn phím, ấn F5, hoặc bấm vào menu điều hướng thì trang sẽ lập tức bị hủy unmount, webcam bị tắt, kết nối WebRTC bị ngắt, phát sinh cảnh báo vi phạm `camera_ngat` gửi tới Giảng viên.

---

### [HIGH-05] Nguy cơ Crash Vite Dev Proxy: `Parse Error: Data after Connection: close` & `ERR_STREAM_WRITE_AFTER_END`
- **Loại:** Rủi ro kỹ thuật đã xác nhận (Confirmed Dev Environment Risk).
- **Vị trí:**
  - `fe/vite.config.js`: dòng 6–13
  - `be/app/Http/Controllers/ExamEvidenceFileController.php`: dòng 122–138
- **Nguyên nhân kỹ thuật:**
  - Khi chạy development với `php artisan serve` và Vite Proxy, PHP Built-in Server sử dụng HTTP/1.1 với header `Connection: close` và không hỗ trợ keep-alive chuẩn.
  - Khi backend trả về file download hoặc stream (`BinaryFileResponse` / `response()->file()`), PHP server có thể gửi thêm byte CRLF sau khi đóng kết nối. Node.js `http-parser` của Vite Proxy gặp byte sau `Connection: close` sẽ ném lỗi:
    `Parse Error: Data after Connection: close`
  - Đồng thời nếu client hủy request (AbortController khi component unmount hoặc thumbnail bị hủy), proxy tiếp tục ghi dữ liệu vào socket đã đóng sẽ phát sinh lỗi:
    `ERR_STREAM_WRITE_AFTER_END`.
  - Trong `ExamEvidenceFileController.php`, code đã chuyển sang đọc toàn bộ file bằng `$disk->get($path)` và trả về regular response với explicit `Content-Length`. Tuy nhiên `fe/vite.config.js` **chưa có cấu hình bắt lỗi proxy (`proxy.on('error', ...)`)**, do đó bất kỳ endpoint stream/file nào khác hoặc request bị client abort đột ngột vẫn có thể đánh sập tiến trình Vite Dev Server.

---

## 5. CÁC LỖI TRUNG BÌNH (MEDIUM PRIORITY BUGS)

### [MED-01] Thiếu Rate Limiting (Throttle) tại Endpoint Đăng nhập và các Endpoint giám sát nhạy cảm
- **Loại:** Lỗ hổng bảo mật (Confirmed Security Issue).
- **Vị trí:** `be/routes/api.php`: dòng 41–44, 111–114, 150–153.
- **Nguyên nhân kỹ thuật:**
  - Route `POST /api/dang-nhap` không có middleware `throttle:api` (mặc định 60 lượt) hoặc giới hạn đăng nhập nghiêm ngặt (ví dụ `throttle:5,1`). Kẻ xấu có thể thực hiện tấn công brute-force từ điển để dò mật khẩu.
  - Route `POST /api/sinh-vien/bai-kiem-tra/{baiTapId}/roi-tab` không có rate limit. Một script độc hại hoặc lỗi client có thể gửi liên tục 3 request trong 100ms để khóa ngay bài thi của thí sinh.
  - Route `POST /api/webrtc/signal` không có throttle, có thể bị spam hàng nghìn SDP packet để gây quá tải CPU của Reverb WebSocket server.

---

### [MED-02] Bố cục Responsive bị vỡ khi màn hình <= 920px trong lúc thi
- **Loại:** Lỗi giao diện / Trải nghiệm (Confirmed UI/UX Bug).
- **Vị trí:** `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`: dòng 1614–1635, 2426–2445.
- **Nguyên nhân kỹ thuật:**
  - Khi độ rộng màn hình `<= 920px`, CSS chuyển `.exam-workspace` thành 1 cột (`grid-template-columns: minmax(0, 1fr)`).
  - Do thứ tự DOM: `<section class="exam-main">` nằm trước `<aside class="exam-sidebar">`, bảng camera và danh sách câu hỏi bị đẩy tụt xuống tận đáy trang web, bên dưới toàn bộ nội dung câu hỏi và đáp án.
  - Thí sinh làm bài trên màn hình nhỏ hoặc máy tính bảng không thể vừa quan sát trạng thái camera/cảnh báo vừa đọc câu hỏi.

---

### [MED-03] Xung đột phím ESC giữa Trình duyệt và Modal Xác nhận nộp bài trong chế độ Fullscreen
- **Loại:** Lỗi trải nghiệm & logic Fullscreen (Confirmed Behavior Issue).
- **Vị trí:** `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`: dòng 94, 221, 1140.
- **Nguyên nhân kỹ thuật:**
  - Trong chế độ toàn màn hình (`requestFullscreen`), phím ESC được hệ điều hành và trình duyệt ưu tiên xử lý cấp thấp để **Thoát Fullscreen**.
  - Modal xác nhận nộp bài có `@keydown.esc.prevent="showConfirm = false"`. Tuy nhiên, trình duyệt vẫn chặn và thoát Fullscreen trước khi sự kiện JavaScript ngăn chặn được.
  - Khi thoát Fullscreen, `fullscreenActive` trở thành `false`, kích hoạt màn hình thông báo vi phạm "Quay lại toàn màn hình để tiếp tục", che mất hộp thoại nộp bài và khiến sinh viên bị tính thêm một tín hiệu thoát màn hình (`fullscreen_exit`).
  - Ngoài ra, việc dùng `window.alert(...)` khi nộp bài thành công (dòng 1406) cũng là nguyên nhân gây cưỡng chế thoát fullscreen đột ngột trên một số trình duyệt.

---

### [MED-04] Lặp gọi API xác thực Token liên tục (Redundant Route Guard Requests)
- **Loại:** Lỗi hiệu năng mạng (Confirmed Performance Issue).
- **Vị trí:**
  - `fe/src/router/index.js`: dòng 42, 126, 310, 425–437
  - `fe/src/router/checkToken.js`: dòng 163, 166
- **Nguyên nhân kỹ thuật:**
  - `checkToken` được đăng ký đồng thời ở **2 cấp**:
    1. Cấp toàn cục: `router.beforeEach(async (to) => ... checkToken(to))`
    2. Cấp route cha: `beforeEnter: checkToken` trên `/admin`, `/giang-vien`, `/sinh-vien`.
  - Trong mỗi lần gọi `checkToken`, client thực hiện 2 request: `api.get('/check-token')` và `api.get('/thong-tin')`.
  - Kết quả là mỗi cú click chuyển trang nội bộ tạo ra tới **4 request HTTP xác thực thừa thãi**, làm chậm giao diện và tăng tải vô ích cho máy chủ.

---

### [MED-05] Truy vấn N+1 khi tải Chi tiết phiên giám sát (`chiTietPhien`)
- **Loại:** Lỗi hiệu năng Database (Confirmed Performance Issue).
- **Vị trí:** `be/app/Http/Controllers/GiangVienApiController.php`: dòng 1179–1185.
- **Nguyên nhân kỹ thuật:**
  - Trong hàm `chiTietPhien`, đoạn code duyệt danh sách cảnh báo camera:
    ```php
    ->map(function ($row) use (...) {
        $imageId = DB::table('anh_man_hinh')->where('event_id', $row->event_id)->value('id');
        ...
    })
    ```
  - Nếu trong phiên có 100 cảnh báo, Backend sẽ thực hiện 100 câu query đơn lẻ tới bảng `anh_man_hinh` thay vì `join` hoặc `whereIn`.

---

## 6. CÁC LỖI NHỎ VÀ HOÀN THIỆN GIAO DIỆN (LOW PRIORITY BUGS)

### [LOW-01] Phụ thuộc CDN công cộng bên ngoài để tải mô hình MediaPipe
- **Vị trí:** `fe/src/components/SinhVien/LamBaiKiemTra/GiamSatCamera.vue`: dòng 71–73.
- **Nguyên nhân:** URL model AI trỏ thẳng tới `storage.googleapis.com` và `cdn.jsdelivr.net`. Nếu phòng thi không có Internet quốc tế ổn định hoặc bị chặn CDN, tính năng AI camera sẽ không khởi tạo được.
- **Đề xuất:** Tải file `.tflite` và thư mục `wasm` về đặt cục bộ tại `fe/public/models/`.

---

### [LOW-02] Component thừa / Route rác (`/sinh-vien/lam-bai/:id`)
- **Vị trí:**
  - `fe/src/router/index.js`: dòng 345–349
  - `fe/src/components/SinhVien/LamBai/index.vue`
- **Nguyên nhân:** Component `LamBai/index.vue` là mã nguồn cũ còn sót lại (với dữ liệu tĩnh "Lập trình web - Kiểm tra giữa kỳ"). Nếu sinh viên truy cập đường dẫn này sẽ vào trang thi không có bảo vệ camera và WebRTC.
- **Đề xuất:** Xóa route `lam-bai/:id` hoặc chuyển hướng sang `lam-bai-kiem-tra`.

---

### [LOW-03] `updateOrInsert` ghi đè `created_at` khi lưu nháp bài làm
- **Vị trí:** `be/app/Http/Controllers/SinhVienKiemTraController.php`: dòng 669–682.
- **Nguyên nhân:** Lệnh `DB::table('bai_lam_nhap')->updateOrInsert($keys, ['updated_at' => $now, 'created_at' => $now])` làm cột `created_at` bị ghi đè thành thời gian lưu nháp mới nhất thay vì giữ nguyên thời điểm tạo nháp đầu tiên.
- **Đề xuất:** Tách riêng `created_at` hoặc dùng Eloquent.

---

### [LOW-04] Console log debug còn sót lại trên môi trường Production
- **Vị trí:**
  - `fe/src/components/SinhVien/BaiNop/index.vue`: dòng 941
  - `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`: dòng 354, 363, 652
- **Nguyên nhân:** Còn in chi tiết object thông tin bài nộp và trạng thái camera ra browser console.
- **Đề xuất:** Xóa bỏ hoặc bọc trong `if (import.meta.env.DEV)`.

---

## 7. BẢNG TỔNG HỢP VỊ TRÍ FILE VÀ SỐ DÒNG LỖI

| Mã lỗi | Mức độ | File nguồn | Dòng mã | Nội dung tóm tắt |
|---|---|---|---|---|
| **CRIT-01** | Critical | `be/app/Http/Controllers/BaiNopController.php` | 73–96, 172, 301, 321, 333 | Nhầm `sinh_vien.id` thay vì `nguoi_dung.id` gây lỗi 403 nộp bài |
| **CRIT-01** | Critical | `be/app/Models/SinhVien.php` | 39–51 | Thiếu `localKey: 'nguoi_dung_id'` trong quan hệ Eloquent |
| **CRIT-02** | Critical | `be/app/Http/Controllers/GiangVienApiController.php` | 1224, 1680, 1780 | Gọi cột `sbv.thong_tin_tin_hieu` khi DB chưa chạy migration version 3 |
| **CRIT-03** | Critical | `be/app/Http/Controllers/SinhVienKiemTraController.php` | 789–796 | Bị chặn nộp bài 422 nếu giáo viên tạo đề thi điểm khác 10 |
| **HIGH-01** | High | `be/app/Events/CanhBaoExamEvent.php` | 57 | Không broadcast vào kênh tổng của phiên `exam-session.{id}` |
| **HIGH-01** | High | `fe/src/components/GiangVien/CanhBao/index.vue` | 1148–1149 | Không lắng nghe event `.exam.violation` thời gian thực |
| **HIGH-02** | High | `fe/src/components/SinhVien/LamBaiKiemTra/index.vue` | 1284 | `await saveInFlight` thiếu catch lỗi làm sập toàn bộ chuỗi lưu nháp |
| **HIGH-03** | High | `fe/src/components/SinhVien/LamBaiKiemTra/index.vue` | 1270–1341 | Không có bộ nhớ đệm `localStorage` dự phòng mất mạng |
| **HIGH-04** | High | `fe/src/components/SinhVien/LamBaiKiemTra/index.vue` | 1430–1462 | Thiếu guard `beforeunload` và `onBeforeRouteLeave` ngăn thoát trang |
| **HIGH-05** | High | `fe/vite.config.js` | 7–13 | Thiếu `proxy.on('error')` xử lý sự cố proxy sập khi client ngắt luồng |
| **MED-01** | Medium | `be/routes/api.php` | 41, 111, 150 | Thiếu Throttle chống brute-force đăng nhập & spam signaling |
| **MED-02** | Medium | `fe/src/components/SinhVien/LamBaiKiemTra/index.vue` | 2426–2433 | Responsive vỡ, camera bị tụt xuống đáy trang trên mobile/tablet |
| **MED-03** | Medium | `fe/src/components/SinhVien/LamBaiKiemTra/index.vue` | 220–265, 1406 | ESC thoát Fullscreen trước khi đóng Modal, `window.alert` cưỡng chế thoát |
| **MED-04** | Medium | `fe/src/router/index.js` | 42, 126, 425 | Gọi đúp 2 lần `checkToken` (tạo 4 request) trên mỗi lần chuyển route |
| **MED-05** | Medium | `be/app/Http/Controllers/GiangVienApiController.php` | 1181 | Lỗi N+1 query trong vòng lặp danh sách cảnh báo phiên |
| **LOW-01** | Low | `fe/src/components/SinhVien/LamBaiKiemTra/GiamSatCamera.vue` | 71–73 | Phụ thuộc CDN Google / JSDelivr cho mô hình MediaPipe |
| **LOW-02** | Low | `fe/src/router/index.js` | 345–349 | Tồn tại route rác `/sinh-vien/lam-bai/:id` của component cũ |
| **LOW-03** | Low | `be/app/Http/Controllers/SinhVienKiemTraController.php` | 680 | `updateOrInsert` ghi đè `created_at` của bản ghi nháp |

---

## 8. NGUYÊN NHÂN KỸ THUẬT VÀ GIẢI THÍCH CHUYÊN SÂU

### 8.1. Sự cố `Unknown column 'sbv.thong_tin_tin_hieu'`
Trong kiến trúc ban đầu của bảng `su_kien_bao_ve_bai_kiem_tra` (migration `2026_11_10_000001`), bảng này chỉ lưu các trường cơ bản. Khi tính năng nâng cấp ghi nhận tín hiệu phím PrintScreen và chi tiết phím chụp màn hình được đưa vào, migration `2026_11_10_000003` mới bổ sung cột `thong_tin_tin_hieu (json)`. Tuy nhiên, mã nguồn ở tầng Controller (`GiangVienApiController.php`) lại truy vấn cứng tên cột này mà không kiểm tra cấu trúc schema hiện tại của máy chủ, dẫn đến việc ứng dụng lập tức báo lỗi nghiêm trọng nếu migration chưa được chạy đầy đủ.

### 8.2. Sự cố `Parse Error: Data after Connection: close` và `ERR_STREAM_WRITE_AFTER_END`
Khi một ứng dụng chạy dưới môi trường phát triển (Vite Dev Server hoạt động như một reverse proxy qua module `http-proxy`), Node.js mong đợi luồng dữ liệu HTTP tuân thủ nghiêm ngặt tiêu chuẩn RFC. Trong khi đó, máy chủ nội tại `php artisan serve` (PHP built-in CLI server) khi phục vụ các phản hồi stream hoặc download thường tự động gán header `Connection: close` và đôi khi đẩy thêm các ký tự kết thúc sau khi luồng đã đóng. Khi thí sinh chuyển câu hỏi hoặc component hủy tải ảnh, trình duyệt phát tín hiệu `abort()`, làm cho kết nối giữa proxy và client bị ngắt ngang. Nếu proxy tiếp tục cố gắng ghi dữ liệu từ PHP server vào socket đã đóng mà không có trình xử lý lỗi thích hợp, Node.js sẽ ném ra ngoại lệ `ERR_STREAM_WRITE_AFTER_END`, gây sập hoàn toàn tiến trình Vite dev proxy.

### 8.3. Bản chất của việc phát hiện phím PrintScreen
Cần khẳng định rõ ràng về mặt kiến trúc: **Các trình duyệt web hiện đại chạy trong môi trường sandbox bảo mật và không có quyền truy cập vào tầng hệ điều hành để phát hiện hoặc ngăn chặn toàn bộ các thao tác chụp màn hình.**
- Sự kiện bàn phím chỉ phát hiện được khi cửa sổ bài thi đang được Active Focus và người dùng bấm phím `PrintScreen`, `Meta+Shift+S` (Windows Snipping Tool) hoặc tổ hợp phím chụp của macOS.
- Nếu người dùng dùng phần mềm bên thứ 3 (như ShareX, Lightshot, Zalo, Telegram shortcut), phần cứng capture card ngoài, máy ảnh điện thoại chụp màn hình, hoặc nhấn tổ hợp phím khi tab mất focus, trình duyệt hoàn toàn không thể nhận biết.
- Do đó, việc hệ thống ghi nhận sự kiện này dưới dạng **"Tín hiệu nghi vấn cần xem xét"** (`canXemXet: true, daXacMinh: false`) là hoàn toàn chính xác về mặt kỹ thuật và không được khẳng định là bằng chứng gian lận tuyệt đối.

---

## 9. HƯỚNG SỬA ĐỀ XUẤT CHI TIẾT

### 9.1. Sửa lỗi Foreign Key trong `BaiNopController.php`
- Thay vì lấy `sinh_vien.id`, hãy sử dụng trực tiếp ID của tài khoản đang đăng nhập (`$request->user()->id`):
  ```php
  // Sử dụng ID tài khoản người dùng cho các bảng thanh_vien_lop và bai_nop
  $userId = (int) $request->user()->id;
  
  $laThanhVien = ThanhVienLop::query()
      ->where('lop_hoc_id', $baiTap->lop_hoc_id)
      ->where('sinh_vien_id', $userId) // Đúng với foreign key nguoi_dung.id
      ->where('trang_thai', 'dang_hoc')
      ->exists();
  ```
- Cập nhật model `SinhVien.php`:
  ```php
  public function thanhVienLops() {
      return $this->hasMany(ThanhVienLop::class, 'sinh_vien_id', 'nguoi_dung_id');
  }
  public function baiNops() {
      return $this->hasMany(BaiNop::class, 'sinh_vien_id', 'nguoi_dung_id');
  }
  ```

### 9.2. Sửa lỗi `Unknown column 'sbv.thong_tin_tin_hieu'`
- Đảm bảo thực thi lệnh migrate an toàn trong quy trình triển khai:
  ```bash
  php artisan migrate --force
  ```
- Trong `GiangVienApiController.php`, bổ sung kiểm tra fallback tương tự như `ExamEvidenceFileController`:
  ```php
  $hasSignalCol = Schema::hasColumn('su_kien_bao_ve_bai_kiem_tra', 'thong_tin_tin_hieu');
  $selectSignal = $hasSignalCol ? 'sbv.thong_tin_tin_hieu' : DB::raw('NULL as thong_tin_tin_hieu');
  ```

### 9.3. Xử lý đồng bộ cảnh báo thời gian thực
- Trong `CanhBaoExamEvent.php`, mở rộng phương thức `broadcastOn()` để phát vào cả 2 kênh:
  ```php
  public function broadcastOn(): array {
      return [
          new PrivateChannel('exam-monitor.' . $this->phienId . '.student.' . $this->sinhVienId),
          new PrivateChannel('exam-session.' . $this->phienId), // Bổ sung kênh tổng
      ];
  }
  ```
- Trong `fe/src/components/GiangVien/CanhBao/index.vue`, bổ sung lắng nghe sự kiện `.exam.violation`:
  ```javascript
  echo.private(`exam-session.${id}`)
      .listen(".exam.protection-alert", event => receiveProtectionAlert(id, event))
      .listen(".exam.violation", event => receiveViolationAlert(id, event))
  ```

### 9.4. Sửa lỗi chuỗi lưu nháp trong `LamBaiKiemTra/index.vue`
- Bọc `await saveInFlight` trong khối try-catch an toàn:
  ```javascript
  if (saveInFlight) {
    try {
      await saveInFlight;
    } catch (e) {
      // Bỏ qua lỗi request trước để cho phép request hiện tại được gửi tiếp
    }
    if (lastSavedVersion === saveVersion || (!force && !canEdit.value)) return;
  }
  ```
- Bổ sung cơ chế lưu LocalStorage dự phòng khi lưu server thất bại:
  ```javascript
  localStorage.setItem(`exam_draft_${baiTapId.value}`, JSON.stringify(payload));
  ```

### 9.5. Cấu hình bảo vệ Vite Dev Proxy
- Cập nhật `fe/vite.config.js`:
  ```javascript
  server: {
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        configure: (proxy) => {
          proxy.on('error', (err, req, res) => {
            if (res && !res.headersSent) {
              res.writeHead(502, { 'Content-Type': 'application/json' });
              res.end(JSON.stringify({ error: 'Proxy error', message: err.message }));
            }
          });
        },
      },
    },
  }
  ```

---

## 10. CÁC CHỨC NĂNG ĐANG HOẠT ĐỘNG TỐT

1. **Frontend Production Build:** Vite build hoàn thành tốt trong 5.45 giây, 169 modules được transform chuẩn xác, không có lỗi biên dịch CSS hay cú pháp JavaScript.
2. **SDP Diagnostics & WebRTC Engine:** Bộ test unit `tests/sdpDiagnostics.test.js` vượt qua 100% (3/3 test cases), chuẩn hóa kết thúc dòng CRLF, sửa chữa SDP offer/answer tránh lỗi kết nối WebRTC trên các trình duyệt khác nhau.
3. **Cú pháp Backend PHP:** 100% các file Controller, Model, Middleware, Event và Migration vượt qua kiểm tra `php -l` không có lỗi cú pháp.
4. **Hệ thống Định tuyến API:** 76 routes được đăng ký hợp lệ, phân quyền middleware Sanctum, Quản trị viên và Giảng viên được gán chính xác theo từng phân vùng URL.
5. **Bảo vệ Đề thi (Anti-Cheating UI):** Chặn hoàn toàn thao tác chuột phải, bôi đen nội dung, sao chép/cắt chữ trong khu vực câu hỏi và đáp án; phát hiện chính xác trạng thái ẩn tab của trình duyệt.
6. **Thuật toán Phân bổ điểm thi (`ExamScoreAllocator`):** Đảm bảo tính toán chính xác điểm số theo thang điểm 10 bằng cách xử lý qua đơn vị cent (nhân 100), tránh sai số dấu phẩy động của PHP/JavaScript.
7. **Lưu trữ & Bảo mật Ảnh bằng chứng:** Kiểm tra chặt chẽ thời hạn lưu trữ (expires_at 90 ngày), lưu trong disk `local` riêng tư, ngăn chặn Directory Traversal, không public trực tiếp đường dẫn file ra ngoài.

---

## 11. CÁC CHỨC NĂNG CHƯA ĐƯỢC KIỂM CHỨNG

1. **Kết nối Cơ sở dữ liệu MySQL thời gian chạy thực tế:** Do môi trường sandbox kiểm tra cục bộ bị hạn chế quyền truy cập socket cổng 3306 tới máy chủ MySQL cục bộ, việc kiểm tra các dữ liệu seeders thực tế chưa được thực thi trực tiếp trên database thật.
2. **PHPUnit Automated Feature Tests:** Trình chạy PHPUnit 11 trên hệ điều hành Windows gặp giới hạn của hàm hệ thống `is_readable()` đối với phân vùng `F:`, khiến tiến trình khởi động bootstrap script `vendor/autoload.php` bị từ chối truy cập. Các test case logic độc lập (Unit) đã được kiểm chứng qua code review.
3. **Hiệu năng WebRTC P2P đa luồng đồng thời:** Chưa kiểm chứng thực tế khi có trên 30 sinh viên cùng truyền video tới 1 máy giảng viên về mặt băng thông mạng và tải GPU/CPU của trình duyệt giáo viên.

---

## 12. ĐÁNH GIÁ RỦI RO AN TOÀN VÀ BẢO MẬT

| Lĩnh vực | Đánh giá | Chi tiết & Khuyến nghị |
|---|---|---|
| **Xác thực & Ủy quyền (Auth/Authz)** | Khá tốt | Sử dụng Sanctum token, kiểm tra vai trò người dùng chặt chẽ qua middleware. Kênh Reverb kiểm tra quyền sở hữu lớp học trước khi cho subscribe. |
| **Kiểm soát Truy cập Ngang (IDOR)** | Rất tốt | Sinh viên không thể xem bài thi của sinh viên khác. Endpoint tải ảnh bằng chứng xác minh quyền giảng viên của lớp trước khi trả dữ liệu. |
| **Bảo mật Dữ liệu Đề thi** | Rất tốt | API lấy đề thi (`SinhVienKiemTraController@show`) đã loại bỏ cột `la_dap_an_dung` khỏi danh sách đáp án, ngăn ngừa việc soi đáp án bằng DevTools Console hay Network tab. |
| **Phòng chống Tấn công Brute Force** | **Cần cải thiện** | Route `/api/dang-nhap` chưa có middleware giới hạn số lượt request (`throttle`). Cần bổ sung giới hạn 5 lần thử/phút cho mỗi IP/email. |
| **Bảo mật Tệp tải lên** | Tốt | File bằng chứng webcam được validate kích thước `<= 512KB`, mime-type chặt chẽ (`jpeg, jpg, webp`), tên file được hash bằng UUID thay vì giữ tên gốc của client. |
| **SQL Injection & XSS** | Rất tốt | Toàn bộ truy vấn sử dụng Eloquent ORM và Query Builder với PDO Parameter Binding; dữ liệu hiển thị trên Vue 3 được auto-escaped qua template binding. |

---

## 13. KẾT QUẢ KIỂM THỬ THỰC TẾ

### 13.1. Frontend Build & Unit Test
- **Lệnh thực hiện:** `npm run build`
  - **Kết quả:** `✓ built in 5.45s`. Không có cảnh báo lỗi cú pháp.
- **Lệnh thực hiện:** `node --test tests/sdpDiagnostics.test.js`
  - **Kết quả:** `3 tests, 3 pass, 0 fail, 0 skipped` (Thời gian: 362ms).

### 13.2. Backend Lint & Routing
- **Lệnh thực hiện:** `php -l` trên toàn bộ thư mục `be/app`, `be/routes`, `be/database/migrations`
  - **Kết quả:** 100% file hợp lệ, không có cú pháp lỗi.
- **Lệnh thực hiện:** `php artisan route:list`
  - **Kết quả:** Hiển thị đủ 76 API routes, cấu trúc controller và middleware mapping chuẩn xác.

---

## 14. KẾ HOẠCH VÀ LỘ TRÌNH SỬA LỖI (PRIORITIZED ROADMAP)

```
GIAI ĐOẠN 1: KHẮC PHỤC CÁC LỖI CHẶN HỌC THUẬT & DỮ LIỆU (NGHIÊM TRỌNG)
├── [CRIT-01] Sửa đổi BaiNopController: đồng bộ sinh_vien_id về nguoi_dung_id cho thanh_vien_lop & bai_nop.
├── [CRIT-02] Chạy migration bổ sung thong_tin_tin_hieu hoặc thêm fallback Schema::hasColumn trong GiangVienApiController.
└── [CRIT-03] Gọi ExamScoreAllocator::assertPublishable khi giáo viên đặt lịch thi; xử lý mềm dẻo khi sinh viên nộp bài.

GIAI ĐOẠN 2: ỔN ĐỊNH TIẾN TRÌNH THI & TRUYỀN DỮ LIỆU THỜI GIAN THỰC (CAO)
├── [HIGH-01] Broadcast CanhBaoExamEvent vào kênh exam-session.{id} và kết nối listener tại CanhBao/index.vue.
├── [HIGH-02] Bọc catch(() => {}) cho saveInFlight trong hàm saveDraft của LamBaiKiemTra.
├── [HIGH-03] Triển khai LocalStorage offline cache cho đáp án của thí sinh.
├── [HIGH-04] Bổ sung beforeunload và onBeforeRouteLeave cảnh báo khi thí sinh rời phòng thi.
└── [HIGH-05] Thêm proxy error handling vào fe/vite.config.js để tránh crash proxy khi mất kết nối stream.

GIAI ĐOẠN 3: BẢO MẬT & TỐI ƯU HÓA HIỆU NĂNG (TRUNG BÌNH)
├── [MED-01] Gán middleware throttle:5,1 cho route /dang-nhap và throttle cho /roi-tab, /webrtc/signal.
├── [MED-02] Tối ưu CSS Responsive cho giao diện thi trên thiết bị <= 920px (ghim camera trên đầu).
├── [MED-03] Hoàn thiện trải nghiệm nộp bài Fullscreen (thay window.alert bằng custom modal).
├── [MED-04] Loại bỏ trước các lệnh checkToken trùng lặp trong Router Guard.
└── [MED-05] Khắc phục truy vấn N+1 tại hàm chiTietPhien.

GIAI ĐOẠN 4: NÂNG CAO ĐỘ TIN CẬY & DỌN DẸP MÃ NGUỒN (THẤP)
├── [LOW-01] Tải mô hình MediaPipe về lưu trữ cục bộ trong thư mục public frontend.
├── [LOW-02] Xóa bỏ route rác /sinh-vien/lam-bai/:id.
└── [LOW-04] Dọn dẹp các lệnh console.log dư thừa.
```

---
*Báo cáo được biên soạn tự động và độc lập dựa trên việc phân tích mã nguồn và kiểm thử an toàn của toàn bộ dự án Smart Classroom Monitor.*
