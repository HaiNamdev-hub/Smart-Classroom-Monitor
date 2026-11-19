# Kịch bản hiệu năng cô lập

`exam-flow.k6.js` chạy một luồng API cho mỗi Sinh viên fixture: đăng nhập, xác nhận bài thi xuất hiện trong danh sách được giao, đọc đề, bắt đầu, lưu các snapshot tuần tự và nộp bài. Nó dừng trước khi ghi nháp/nộp nếu bài thi fixture không hiện trong danh sách của chính Sinh viên. Có think time và khoảng autosave giả lập; `PERF_DRAFT_WRITES` và `PERF_READ_REPEATS` có thể đặt 1–10. Đây là tải Backend/API; các cờ `fullscreen` và `cameraReady` chỉ thỏa hợp đồng API, **không** mô phỏng webcam, MediaPipe, WebRTC hoặc Reverb. Kịch bản có custom counter cho số lần lưu nháp và nộp bài được Backend xác nhận.

## Rào chắn

- Script chỉ chấp nhận `http://127.0.0.1:8001`, `localhost:8001` hoặc `[::1]:8001`.
- Cần khai báo rõ `PERF_ISOLATED_DATASET=YES`. Cổng 8000 (local app đang dùng DB cấu hình thật) bị từ chối.
- Mỗi VU cần một tài khoản Sinh viên khác nhau và một bài kiểm tra đã công bố trong DB disposable. Script **ghi nháp và nộp bài**, không tái sử dụng được lượt thi đã tiêu thụ.
- k6 script không tạo/xóa tài khoản hoặc migrate DB và không chứa credential. Seeder riêng chỉ tạo dữ liệu test tổng hợp trong DB cô lập. Fixture mật khẩu nằm ngoài Git.
- `Prepare-PerformanceDb.ps1` mặc định chỉ in dry-run. Nếu được người vận hành cho phép, script tạo SQLite đúng đường dẫn `be/database/performance-test/template.sqlite`, migrate **chỉ** file này và gọi `PerformanceTestSeeder` tạo 100 user giả, 1 lớp, 1 đề 10 điểm, 4 lựa chọn và 100 lượt đang làm. Seeder từ chối môi trường khác `APP_ENV=performance`, connection khác SQLite, path khác file chuẩn, hoặc bảng fixture đã có dữ liệu. Script còn đặt `DB_URL` rỗng và chặn cấu hình MySQL dự phòng ở loopback port 1 (đồng thời từ chối nếu port 1 đang listen).
- Seeder tạo marker; `verify-sqlite.php` mở DB read-only, giới hạn đường dẫn trong thư mục test và kiểm tra số lượng fixture. Không có lệnh xóa/reset.
- `Invoke-PerformanceStages.ps1` mặc định cũng dry-run. Khi được gọi với cờ xác nhận, nó tạo clone mới từ template cho từng bậc, xác minh marker, chạy server PHP loopback và benchmark. Sau mỗi bậc, script kiểm tra số bài nộp, câu trả lời và điểm bằng số VU; nếu sai sẽ chặn bậc tiếp. Nếu đạt, script chờ người vận hành gõ `CONTINUE` sau khi xem summary và CPU/RAM. Database/log/summary được giữ lại; không xóa file cũ. File đích đã tồn tại khiến script dừng.

Fixture JSON ngoài repo, ví dụ:

```json
[
  { "email": "student-001@example.test", "password": "<test-only>", "baiTapId": 9001, "answers": { "12345": "67890" } }
]
```

Mỗi bản ghi phải thuộc fixture test riêng, bài chưa nộp, còn hạn, có câu hỏi và đáp án hợp lệ theo bài đó. Chuẩn bị schema/dữ liệu bằng seed chỉ trên DB disposable; không dùng tài khoản hoặc dữ liệu thật.

## Cài k6 trên F:

`k6 version` hiện chưa nhận diện được trên máy. Ưu tiên bản portable để không đổi PATH toàn máy:

1. Xem [hướng dẫn cài k6 chính thức](https://grafana.com/docs/k6/latest/set-up/install-k6/) và tải Windows ZIP từ [trang phát hành Grafana k6](https://github.com/grafana/k6/releases), cùng checksum của đúng phiên bản.
2. Giải nén `k6.exe` tới `F:\Smart Classroom Monitor\performance-tests\tools\k6\k6.exe`.
3. So sánh SHA-256 của ZIP hoặc binary với checksum công bố trước khi chạy:

```powershell
Get-FileHash 'F:\Downloads\k6-v<VERSION>-windows-amd64.zip' -Algorithm SHA256
& 'F:\Smart Classroom Monitor\performance-tests\tools\k6\k6.exe' version
```

Thư mục `tools/k6/` được ignore để không thêm binary vào Git. Tài liệu Grafana cũng liệt kê gói Windows qua winget; cách đó có thể cài ngoài F nên không phải lựa chọn ưu tiên ở đây.

## Chuẩn bị (không tự chạy)

Dry-run chỉ mô tả thao tác:

```powershell
cd 'F:\Smart Classroom Monitor'
.\performance-tests\Prepare-PerformanceDb.ps1
```

Việc tạo file SQLite test, chạy migrations trên file test đó và seed fixture là thao tác ghi. Chỉ sau khi cho phép, chạy:

```powershell
.\performance-tests\Prepare-PerformanceDb.ps1 -InitializeIsolatedDatabase -ConfirmDisposableDatabaseCreation
```

Sau khi fixture đã có, chạy preflight HTTP trên **clone mới** (không sửa template) để xác minh login → lớp/bài được giao → chi tiết → start → draft/restore → submit:

```powershell
.\performance-tests\Invoke-PerformancePreflight.ps1
```

Script bind Laravel chỉ vào `127.0.0.1:8001`, từ chối cổng đang bận và Laravel config cache, dùng env process-only trỏ SQLite, không in token và kiểm tra clone sau submit. Đây là functional smoke test, không phải benchmark.

## Chạy theo bậc (mỗi bậc nộp bài trên clone riêng)

Sau khi có template SQLite/fixture pass kiểm tra, cài k6 ở đường dẫn F nêu trên và đã cho phép chạy tải, thực hiện dry-run:

```powershell
.\performance-tests\Invoke-PerformanceStages.ps1
```

Để chạy 1, 5, 10, 20, 30, 50, 75, 100 VU theo thứ tự, cần xác nhận vận hành bằng cờ:

```powershell
.\performance-tests\Invoke-PerformanceStages.ps1 -MaxVus 100 -RunConfirmedIsolatedLoad
```

Không truyền cờ này trước khi kiểm tra riêng template/fixture và xác nhận không có dịch vụ dùng cổng 8001. Mỗi lần VU submit sẽ tiêu thụ attempt trong bản clone riêng; các clone và kết quả được giữ lại, không tự xóa.

Script chỉ thử API student path. Cần kịch bản đọc-only riêng cho Giảng viên nếu cần tải dashboard; không ghép vào test này vì khác quyền và cần fixture phiên/lớp.

SQLite và PHP built-in server trên Windows chỉ phù hợp smoke/load profile local, không đại diện khả năng MySQL row locks hoặc PHP-FPM nhiều worker. Trong kịch bản hiện tại server xử lý bằng một tiến trình PHP; kết quả 100 VU có thể chủ yếu phản ánh hàng đợi của built-in server. Để tuyên bố năng lực triển khai, cần chạy lại trên staging cô lập dùng cùng MySQL version/config, PHP SAPI/worker pool, Reverb và proxy như production.

## Camera và Reverb

Không thể dùng k6 để khẳng định tải camera AI/WebRTC. Chạy riêng trình duyệt thật trên thiết bị test; lấy WebRTC `getStats()` (FPS, bitrate, RTT, packet loss), CPU/RAM thiết bị, số RTCPeerConnection trên dashboard và thời gian nhận cảnh báo Reverb. Tăng số thiết bị theo cùng các bậc, trong mạng cô lập; không dùng camera giả để tuyên bố tương đương MediaPipe/WebRTC thật.
