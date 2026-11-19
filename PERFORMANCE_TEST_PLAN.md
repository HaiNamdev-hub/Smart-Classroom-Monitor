# Kế hoạch kiểm thử hiệu năng

## 0. Điều kiện trước khi chạy

1. k6 hiện chưa cài. Cài bản portable vào `performance-tests/tools/k6/` theo README; không cần đổi PATH toàn máy.
2. Tạo template SQLite bằng `Prepare-PerformanceDb.ps1 -InitializeIsolatedDatabase -ConfirmDisposableDatabaseCreation`. Lệnh này tạo file mới, chạy migrations chỉ với `DB_CONNECTION=sqlite` và đường dẫn `be/database/performance-test/template.sqlite`, sau đó seed 100 dữ liệu giả. Không dùng DB local hiện tại.
3. Script từ chối ghi đè template/fixture đã có; seeder chỉ chạy nếu `APP_ENV=performance`, connection SQLite và canonical DB path khớp chính xác. Nó còn từ chối seed nếu các bảng liên quan không rỗng.
4. Chạy `verify-sqlite.php` để đọc-only kiểm tra marker, 100 user giả, 100 membership/attempt, một lớp, một đề và 0 bài nộp trong template.
5. Chỉ sau khi bước trên PASS và người vận hành xác nhận, gọi `Invoke-PerformanceStages.ps1 -MaxVus 100 -RunConfirmedIsolatedLoad`. Runner clone template riêng cho từng bậc, đặt environment của process thành SQLite path clone, xác minh clone, abort nếu config cache tồn tại hoặc cổng 8001 đang bị chiếm.
6. Runner phục vụ bằng PHP built-in server trên `127.0.0.1:8001`; health endpoint cần trả 401 JSON trước khi k6 chạy. Bất kỳ status khác đều làm dừng mà không gửi tải.
7. Sau mỗi stage runner chạy kiểm tra read-only: marker, 100 users/membership/attempts, và số bài nộp/câu trả lời/điểm đúng bằng VU hiện tại. Nếu sai sẽ không chạy stage tiếp.
8. Sau mỗi stage đạt, runner yêu cầu người vận hành gõ `CONTINUE` sau khi xem summary và CPU/RAM; Enter dừng trước bậc cao hơn.
9. Dùng bản DB/fixture mới cho mỗi lượt có submit. Không chạy trên phiên thi thật. Script không có `migrate:fresh`, xóa/reset hoặc cleanup tự động.

## 1. Ma trận workload

Chạy lần lượt số Sinh viên `1, 5, 10, 20, 30, 50, 75, 100, 150, 200`; không bắt buộc đến 200. Mỗi bậc chỉ bắt đầu khi bậc trước không có lỗi dữ liệu, error rate trong mục tiêu và tài nguyên còn headroom.

| Profile | Hoạt động | Công cụ | Chứng minh được |
|---|---|---|---|
| API no-camera | login → GET đề → start → draft → submit | `performance-tests/exam-flow.k6.js` | Backend HTTP/DB flow; không chứng minh webcam |
| API + autosave | Luồng trên với nhiều bản draft tuần tự theo nhịp người dùng, riêng cùng tài khoản/lượt thi; cần mở rộng kịch bản fixture trước khi chạy profile này | k6 trên disposable | API ghi nháp và lock contention; không MediaPipe |
| Dashboard Giảng viên | GET danh sách phiên/cảnh báo/chi tiết phiên, ảnh metadata; tải ảnh blob có giới hạn đồng thời | k6 read-only phụ trợ hoặc browser Network | API query và metadata; file phải dùng fixture riêng |
| Camera + AI | Webcam thật, nhiều phiên bản thiết bị; MediaPipe chạy ở máy client | trình duyệt/device farm | CPU/RAM/FPS của client; không thể giả bằng HTTP VU |
| WebRTC + Reverb | Sinh viên và Giảng viên thật, private-channel auth, SDP/ICE, video track, cảnh báo | browser farm + Reverb metrics + `RTCPeerConnection.getStats()` | socket/PC/bitrate/packet loss/RTT/alert latency |

API no-camera k6 script thực hiện một lượt thi destructive cho mỗi tài khoản. Không dùng cùng fixture cho profile autosave hoặc vòng load tiếp theo sau khi đã submit.

## 2. Chỉ số cần ghi

### Load generator và HTTP

- VU thực tế, iterations, RPS và throughput.
- Request count/error rate theo endpoint và mã HTTP.
- Mean, p50, p95, p99 response time theo endpoint: login, GET đề, start, draft, submit, dashboard, evidence file.
- K6 dropped iterations, timeout, connect/TLS time.

### Laravel / MySQL / Reverb

- PHP worker/process utilization, CPU, RSS, queue/backlog, timeout/5xx.
- MySQL connection count, active/idle, query latency, lock waits/deadlocks, slow queries, buffer pool/cache.
- Số socket và subscription Reverb, memory/socket, publish-to-receive latency, disconnect/reconnect.
- Kiểm tra sau mỗi lượt: mỗi tài khoản chỉ có một attempt; draft revision/answers trùng snapshot cuối; trạng thái submit đúng; không tạo duplicate alerts/event IDs.

### Trình duyệt thật

- CPU/RAM của từng máy Sinh viên và máy Giảng viên; decode FPS, rendered FPS, resolution, bitrate, RTT, packet loss, jitter, ICE failures.
- Số `RTCPeerConnection`, media tracks và websocket connections thực tế.
- Thời gian từ cảnh báo được lưu đến khi Giảng viên nhận Reverb.
- Chạy ít nhất hai cấu hình thiết bị đại diện và mạng giới hạn; ghi rõ browser/version/resolution/bitrate.

## 3. Tiêu chí SLO đề xuất và điều kiện dừng

Ngưỡng đề xuất, chưa đo/đạt: lỗi request <1%; API đề p95 <500 ms; lưu nháp p95 <1 s; nộp p95 <2 s. Thêm p99 mục tiêu theo yêu cầu nghiệp vụ trước benchmark chính thức.

Dừng ngay, không tăng bậc nếu có một trong các điều kiện: mất/ghi đè đáp án, submit sai trạng thái hoặc duplicate, dữ liệu test chéo user/lớp, 5xx/timeouts tăng rõ, deadlock/lock wait kéo dài, WebRTC mất kết nối hàng loạt, CPU/RAM/DB connections gần giới hạn, hoặc load generator không còn đủ tài nguyên.

## 4. Cách chạy API k6

Chi tiết fixture và PowerShell command ở `performance-tests/README.md`. Bắt đầu `PERF_VUS=1`, lưu summary JSON/CSV và monitoring theo timestamp; dùng dataset clone mới trước bậc tiếp theo. Không chạy đồng thời với benchmark khác trên cùng máy chủ.

## 5. Browser/WebRTC thực tế

1. Dùng staging cô lập, tài khoản thiết bị test, phiên thi riêng và không chứa dữ liệu sinh viên thật.
2. Bắt đầu một máy Sinh viên + một máy Giảng viên, xác nhận private channel, ICE connected, video track `live`, và nhận cảnh báo. Thu baseline.
3. Tăng browser thật theo cùng các bậc. Không tạo nhiều tab camera trên một laptop rồi coi như nhiều thiết bị vật lý; kết quả cần phân loại rõ.
4. Lấy `getStats()` định kỳ trên mỗi PC: inbound/outbound bitrate, framesPerSecond, packetsLost, jitter, roundTripTime, candidate pair state. Thu CPU/RAM/network trên client và server.
5. Kiểm tra Reverb broadcast latency, reconnect, lịch sử API sau reload và đúng session/student. Lặp lại khi ngắt mạng có kiểm soát trong staging.
6. Chạy profile camera tắt và camera bật riêng để đối chiếu Backend API load; AI/MediaPipe chỉ được gọi là test thực nếu detector xử lý hình ảnh camera thật.

## 6. Báo cáo từng lượt

Ghi tối thiểu:

```text
Ngày/commit hash không sửa: 
Environment + OS/CPU/RAM:
App/PHP SAPI/worker, PHP, Laravel, DB/version/limits, Reverb config:
Load generator + vị trí:
VU / unique accounts / exam fixture:
Profile (no-camera / autosave / browser real):
Warmup / duration / iterations:
RPS; mean/p50/p95/p99; errors/timeouts/dropped iterations:
Laravel CPU/RSS; MySQL conn/locks/slow queries; Reverb sockets/memory:
Browser CPU/RAM; PC count; FPS/bitrate/RTT/loss/jitter:
Answer integrity / attempt count / duplicate event check:
Pass/fail, first bottleneck, stop reason:
Highest tier passing all SLOs (only if actually measured):
```

Không suy ra tối đa production từ laptop, simulator, backend-only test hoặc một browser nhận nhiều luồng.

## 7. Tình trạng hiện tại

- k6: **NOT INSTALLED**; `k6 version` trả “not recognized”.
- Fixture/database bootstrap scripts: tạo xong; PHP syntax **PASS**; PowerShell parser **PASS**; cả hai script ở chế độ dry-run chạy **PASS** và xác nhận chưa tạo file/database/migrate/seed/start server.
- PHP có `pdo_sqlite`; Laravel config cache hiện không tồn tại. Đây chưa phải DB disposable vì template và user fixture chưa được tạo.
- Tải API k6: **NOT RUN**; Laravel cổng 8000 không được dùng (script từ chối target khác loopback cổng 8001).
- PHPUnit DB: cấu hình có SQLite `:memory:`; không chạy lại trong lượt chuẩn bị này.
- Hai browser/device farm: chưa có.

**Giới hạn của harness local:** SQLite không đại diện InnoDB row lock/connection limits. PHP built-in server do runner khởi chạy là một tiến trình; không phải PHP-FPM worker pool. Kết quả local hữu ích cho an toàn API, regression và smoke/load profile, nhưng không dùng làm số lượng Sinh viên tối đa trên production. Muốn kiểm chứng năng lực lớp đông phải lặp lại trên staging cô lập có cùng MySQL/PHP worker/Reverb/proxy với kế hoạch deploy.
- Mức cao nhất đã chứng minh: **chưa có**; mức đầu tiên thất bại: **chưa biết**.
