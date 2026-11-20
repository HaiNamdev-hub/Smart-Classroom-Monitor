# BÁO CÁO PHÂN TÍCH VÀ KHẮC PHỤC SỰ CỐ WEBSOCKET REVERB, WEBRTC CAMERA VÀ MỜ MÀN HÌNH KHI RỜI TAB
**Dự án:** Smart Classroom Monitor  
**Môi trường:** Frontend Vercel (`smart-classroom-monitor-sandy.vercel.app`) & Backend Render (`smart-classroom-monitor.onrender.com`)  
**Ngày thực hiện:** 20/11/2026  

---

## 1. PHÂN TÍCH CÁC LỖI TRONG 3 HÌNH ẢNH THỰC TẾ

### 1.1. Lỗi Console Hình 1: `WebSocket connection to 'wss://localhost:8080/app/...' failed`
* **Hiện tượng:** Màn hình Giảng viên hiển thị thông báo *"Chưa nhận được tín hiệu. WebRTC timeout hoặc chưa phản hồi"* và console báo đỏ kết nối tới `wss://localhost:8080`.
* **Nguyên nhân:**
  * Giảng viên mở website trên trình duyệt từ link Vercel (`smart-classroom-monitor-sandy.vercel.app`).
  * Tuy nhiên, gói build Frontend hoặc biến môi trường `VITE_REVERB_HOST` chưa được cung cấp hoặc mặc định fallback về `localhost` / `127.0.0.1`.
  * Trình duyệt của Giảng viên cố gắng mở WebSocket tới chính máy cá nhân của Giảng viên (`localhost:8080`), nơi không có Reverb server nào đang chạy $\rightarrow$ Kết nối thất bại ngay lập tức.
  * Vì WebSocket không kết nối được, Giảng viên không thể nhận hoặc gửi bất kỳ tín hiệu Signaling nào tới Sinh viên.

---

### 1.2. Lỗi Console Hình 3: `WebSocket connection to 'wss://smart_classroom_monitor.onrender.com:8080/app/...' failed`
* **Hiện tượng:** Sau khi cấu hình biến môi trường, console vẫn tiếp tục báo lỗi đỏ khi cố gắng kết nối tới `wss://smart_classroom_monitor.onrender.com:8080`.
* **Nguyên nhân gốc rễ (2 sai lệch chí mạng):**
  1. **Sai lệch tên miền (Dấu gạch dưới `_` thay vì gạch nối `-`):**
     * Trong ảnh: `smart_classroom_monitor.onrender.com`.
     * Tên miền thực tế trên Render: `smart-classroom-monitor.onrender.com`.
     * Render router không định tuyến được tên miền có dấu gạch dưới $\rightarrow$ Máy chủ Render trả về HTTP 404.
  2. **Sai lệch Port trên Cloud Render (Port 8080 vs Port 443):**
     * Nền tảng Cloud Render (cũng như Heroku/Vercel) **chỉ mở cổng 443 (HTTPS/WSS)** ra ngoài Internet. Cổng 8080 là cổng nội bộ (internal) bên trong container Docker phía sau Nginx.
     * Khi client từ trình duyệt bên ngoài gọi tới `wss://...:8080`, Cloudflare / firewall của Render lập tức từ chối hoặc timeout kết nối.
     * Cổng WebSocket an toàn (WSS) ra ngoài Internet **bắt buộc phải là 443** (hoặc để trống, trình duyệt tự hiểu 443 cho giao thức `wss://`).

---

### 1.3. Thông báo Hình 2: *"TURN chưa được cấu hình; kết nối hiện chỉ có STUN."*
* **Bản chất:** Đây là **thông báo trạng thái (Notice)**, không phải lỗi cú pháp hay crash hệ thống.
* **Ý nghĩa thực tế:**
  * WebRTC sử dụng STUN của Google (`stun:stun.l.google.com:19302`) để xác định địa chỉ IP công khai của hai thiết bị và đục lỗ NAT P2P.
  * Khi Sinh viên và Giảng viên ở trong cùng mạng LAN, hoặc mạng gia đình có NAT thông thường, STUN hoạt động tốt.
  * Tuy nhiên, nếu một trong hai bên dùng mạng 4G, mạng trường học, cơ quan có Symmetric NAT (tường lửa chặn kết nối P2P trực tiếp), hai bên không thể bắt tay P2P được nếu không có máy chủ trung chuyển **TURN Relay Server**.
  * Để xóa cảnh báo này, cần cấu hình TURN server (như Metered Video TURN, Xirsys, hoặc coturn tự host) vào backend `.env`.

---

## 2. VẤN ĐỀ 1: CHUYỂN TAB LẦN ĐẦU BỊ MỜ / TỐI MÀN HÌNH

### 2.1. Nguyên nhân gốc rễ
* Trong file `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`:
  * Khi Sinh viên rời tab lần đầu, sự kiện `visibilitychange` kích hoạt và đặt `showWarning = true`.
  * Popup cảnh báo được render với cấu trúc HTML:
    ```html
    <div v-if="showWarning && !locked" class="overlay">
      <div class="modal warning-modal" role="alertdialog">
        ...
        <button @click="showWarning = false">Tôi đã hiểu</button>
      </div>
    </div>
    ```
* **Xung đột CSS với Bootstrap 5:**
  * Dự án import Bootstrap 5 toàn cục (`node_modules/bootstrap/dist/css/bootstrap.css`).
  * Trong Bootstrap: `.modal { display: none; position: fixed; ... }`.
  * Trong CSS scoped của `index.vue`, thẻ con `.modal` không khai báo `display: block` hoặc `display: flex`. Do đó, thuộc tính `display: none` của Bootstrap đã **ẨN TOÀN BỘ HỘP THOẠI TRẮNG VÀ NÚT BẤM "TÔI ĐÃ HIỂU"**!
  * Lớp phủ nền `.overlay` (`background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(3px); z-index: 1000`) vẫn hiển thị và chiếm toàn màn hình, chặn mọi thao tác chuột (`pointer-events`).
  * Kết quả: Sinh viên bị kẹt vĩnh viễn sau một màn hình tối mờ, không có nút bấm để đóng và không thể tiếp tục làm bài.

### 2.2. Giải pháp đã triển khai
1. Đổi tên class tránh hoàn toàn xung đột với Bootstrap:
   * `.overlay` $\rightarrow$ `.exam-warning-overlay` (`z-index: 2147483000; display: grid; place-items: center; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px);`).
   * `.modal warning-modal` $\rightarrow$ `.exam-warning-modal` (`display: block; position: relative; z-index: 1; ...`).
2. Bổ sung hàm xử lý đóng cảnh báo mượt mà:
   ```javascript
   const dismissWarning = () => {
     showWarning.value = false
     if (!fullscreenActive.value && officialExamActive.value) {
       void requestExamFullscreen()
     }
   }
   ```
   * Cho phép click bên ngoài overlay hoặc bấm nút "Tôi đã hiểu" để tắt cảnh báo.
   * Tự động yêu cầu Fullscreen trở lại ngay lập tức nếu trình duyệt vừa thoát toàn màn hình, đảm bảo luồng thi liền mạch mà không làm mất tính năng phát hiện gian lận.

---

## 3. VẤN ĐỀ 2: CAMERA CHỈ HIỂN THỊ SAU KHI CÓ CẢNH BÁO

### 3.1. Nguyên nhân gốc rễ (Race Condition trong Reverb Subscription)
1. **Tại `fe/src/services/webrtc/teacherReceiver.js`:**
   * Khi Giảng viên mở trang giám sát, hàm `watchSessionAlerts` được gọi đầu tiên để đăng ký lắng nghe cảnh báo cho tất cả sinh viên.
   * `watchSessionAlerts` gọi `retainChannel(channelName)` để `echo.private(channelName)`.
   * Khoảng 50ms sau, Reverb bắt tay thành công và sự kiện `pusher:subscription_succeeded` được kích hoạt trên kênh.
   * Sau đó, Vue mới mount các component camera sinh viên (`StudentCamera`) và gọi `teacherReceiver.watchStudent(sId)`.
   * Trong `watchStudent`, code gọi:
     ```javascript
     conn.channel.subscribed(() => {
       conn.subscribed = true;
       this.sendRequestOfferWithRetry(sId);
     });
     ```
   * **Vấn đề:** Trong thư viện Pusher-JS / Laravel Echo, hàm `.subscribed(callback)` chỉ lắng nghe sự kiện `pusher:subscription_succeeded`. Do sự kiện này **ĐÃ XẢY RA TRƯỚC ĐÓ**, callback truyền vào sẽ **KHÔNG BAO GIỜ ĐƯỢC GỌI**!
   * Kết quả: `sendRequestOfferWithRetry` không bao giờ chạy, Giảng viên không gửi yêu cầu kết nối video, camera mãi ở trạng thái chờ tín hiệu.
2. **Tại sao khi có cảnh báo camera lại bất ngờ hiện lên?**
   * Khi Sinh viên phạm lỗi (quay mặt đi, rời tab), hệ thống gọi API cảnh báo. Sau đó, Sinh viên cập nhật trạng thái và phát tín hiệu `camera_ready`.
   * Khi Giảng viên nhận được tín hiệu `camera_ready`, hàm `handleStudentSignal` kích hoạt `sendRequestOfferWithRetry`, lúc này video mới bắt đầu được đàm phán!
3. **Tại Backend `SinhVienKiemTraController::startExam()`:**
   * API `POST /bat-dau` không trả về `phienGiamSat` và `sinhVienId`, khiến Sinh viên khi bắt đầu làm bài không có ID phiên để khởi tạo kết nối Signaling ngay, phải đợi đến khi phát sinh cảnh báo vi phạm.

### 3.2. Giải pháp đã triển khai
1. **Sửa `fe/src/services/webrtc/teacherReceiver.js`:**
   * Trong `retainChannel`: Lưu cờ `isSubscribed` khi kênh xác thực thành công.
   * Trong `watchStudent`: Kiểm tra nếu kênh đã subscribed từ trước (`channelEntry?.isSubscribed || conn.channel?.subscribed || conn.channel?.subscription?.subscribed`), lập tức kích hoạt `onSubscribed()` và gửi `request_offer` mà không cần đợi.
2. **Sửa `fe/src/services/webrtc/studentSender.js`:**
   * Kiểm tra tương tự: Nếu kênh signaling đã subscribed, gửi ngay `camera_ready`.
3. **Sửa `be/app/Http/Controllers/SinhVienKiemTraController.php`:**
   * Trong `startExam()`, truy vấn `$activeSession` và trả về `phienGiamSat` cùng `sinhVienId` ngay trong response bắt đầu làm bài.
   * Trong `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`, nhận diện ngay ID phiên và kích hoạt kết nối WebRTC tức thì.

---

## 4. KHẮC PHỤC TRIỆT ĐỂ CẤU HÌNH WEBSOCKET REVERB TRÊN VERCEL & RENDER

### 4.1. Cập nhật `fe/src/services/reverb.js`
* Sửa lỗi build-time của Vite: Không dùng `typeof window !== 'undefined'` đơn lẻ vì lúc build trên server Node của Vercel biến này là `false`, làm Vite gán cứng `scheme = 'http'` và `enabledTransports = ['ws']`.
* Thêm kiểm tra `import.meta.env.PROD` và `window.location.protocol === 'https:'`.
* Bắt buộc sử dụng cổng `443`, giao thức `https` và `wss` trên môi trường Production.
* Tự động sửa lỗi gõ nhầm: Nếu tên miền chứa `onrender.com` có dấu gạch dưới `_`, tự động thay thế bằng gạch nối `-`.

### 4.2. Hướng dẫn thiết lập biến môi trường trên Vercel
Vào **Vercel Dashboard** $\rightarrow$ Chọn dự án `smart-classroom-monitor` $\rightarrow$ **Settings** $\rightarrow$ **Environment Variables**, đảm bảo các biến sau được thiết lập chính xác (chọn cả 3 môi trường: Production, Preview, Development):

| Tên biến | Giá trị chính xác | Chú thích |
| :--- | :--- | :--- |
| `VITE_API_BASE_URL` | `https://smart-classroom-monitor.onrender.com` | Không có dấu gạch chéo `/` ở cuối |
| `VITE_REVERB_APP_KEY` | `wb5jfofeqpobmdzyoz0i` | Key ứng dụng Reverb |
| `VITE_REVERB_HOST` | `smart-classroom-monitor.onrender.com` | Dấu gạch nối `-`, không dùng dấu gạch dưới `_` |
| `VITE_REVERB_PORT` | `443` | Cổng WSS ngoài Internet (KHÔNG DÙNG 8080) |
| `VITE_REVERB_SCHEME` | `https` | Bảo mật TLS |

> **Lưu ý quan trọng sau khi đổi biến trên Vercel:**  
> Vì Vite đóng gói các biến môi trường `VITE_*` vào mã JavaScript tĩnh tại thời điểm Build (`build time`), sau khi lưu biến môi trường mới trên Vercel, bạn cần vào tab **Deployments** $\rightarrow$ Bấm vào dấu `...` ở bản deploy mới nhất $\rightarrow$ Chọn **Redeploy** (không chọn Use existing Build Cache).

---

## 5. KẾT QUẢ KIỂM THỬ HỆ THỐNG
* **Backend PHPUnit / Feature Tests:** `72 passed (750 assertions)` — 100% kiểm thử bảo mật, batch insert, signaling WebRTC và chống gian lận đều vượt qua.
* **Frontend Unit Tests:** `12 passed (0 failed)`.
* **Vite Build Production:** Build thành công 100% không có cảnh báo cú pháp.
* **Git Status:** Sạch sẽ, tuân thủ tiêu chuẩn mã nguồn.
