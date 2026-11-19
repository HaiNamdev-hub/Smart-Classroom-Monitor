# Smart Classroom Monitor — Production Hardening Report

**Ngày:** 19/11/2026  
**Phạm vi:** bốn phase trong `SMART_CLASSROOM_FULL_AUDIT_REPORT.md`. Không chạy migration, không ghi vào MySQL thật, không triển khai, không commit/push.

## Tóm tắt

Đã sửa các rủi ro có thể xác minh trực tiếp trong source: upload bài nộp không giới hạn loại file và lưu public; login thiếu throttle; limiter theo IP có thể chặn cả lớp dùng chung NAT; một số thao tác thi khóa cùng hàng bài tập; tạo thứ tự attempt phát sinh nhiều câu INSERT; session monitor tạo PeerConnection cho toàn bộ lớp; và một truy vấn N+1 ảnh bằng chứng.

Kiểm thử Laravel chạy bằng SQLite `:memory:` theo `be/phpunit.xml`: **63 tests, 677 assertions, tất cả PASS**. PHP syntax PASS. Vite build thành công khi xuất sang thư mục tạm. Lệnh `npm run build` mặc định không thể dọn `fe/dist/.gitkeep` do quyền file hiện tại; chi tiết ở mục kiểm thử.

Chưa có kiểm thử đồng thời trên MySQL/MariaDB staging, hai trình duyệt thật, TURN server thật, Reverb đang chạy hoặc benchmark. Vì vậy dự án **chưa được xác nhận production-ready**.

## Trạng thái theo phase

| Phase | Trạng thái | Kết quả |
|---|---|---|
| Phase 1 — Security | **TESTED** | Private upload mới, API tải có kiểm tra quyền, allowlist MIME/extension/size, chống path traversal; login chuẩn hóa email và limiter theo tài khoản; limiter cảnh báo theo user để tránh NAT lockout. Feature tests PASS. |
| Phase 2 — Concurrency & exam integrity | **PARTIALLY VERIFIED** | Save/submit/tab/camera ghi nhận khóa hàng attempt của đúng Sinh viên; start attempt còn khóa ngắn trên bài tập để xử lý trường hợp attempt chưa tồn tại; unique constraints tiếp tục bảo vệ attempt/submission/draft/event; order insert đã batch. Functional SQLite tests PASS, nhưng concurrency lock phải xác minh trên MySQL staging. |
| Phase 3 — WebRTC & camera AI | **PARTIALLY VERIFIED** | Thêm ICE config dùng chung, API Sanctum cấp TURN credential HMAC có hạn dùng nếu backend được cấu hình; retry recovery giới hạn; trang giám sát chỉ mount tối đa 9 video trong khi giữ subscription cảnh báo cho toàn bộ danh sách. Build PASS; chưa có TURN/network/browser test. MediaPipe vẫn chạy đồng bộ trên main thread và model/WASM còn tải từ CDN. |
| Phase 4 — Tests, performance & quality | **PARTIALLY VERIFIED** | Các lỗi test đã đối chiếu và sửa, ảnh truy vấn N+1 được batch, build và full Laravel suite PASS. Chưa chạy load test hoặc kiểm tra UI/browser thật; chưa có số liệu p50/p95, lock wait, CPU/RAM. |

## Findings đã xác minh và thay đổi

### Phase 1 — Bảo mật

- `BaiNopController::nopBai()` trước đây chỉ validate kích thước và lưu file vào disk `public`. Hiện allowlist PDF/Word/Excel/PowerPoint/ZIP/TXT; kiểm tra extension lẫn MIME lấy từ nội dung bằng `finfo`; giới hạn vẫn 10 MB; file mới lưu trên disk private `local`.
- Thêm `BaiNopController::download()` tại `/api/bai-nop/{id}/file`, trong nhóm `auth:sanctum`. Chỉ Sinh viên sở hữu hoặc Giảng viên phụ trách lớp được tải. Từ chối path tuyệt đối, `.`/`..`, bài thi trực tuyến và file không tồn tại; trả attachment với `nosniff` và cache private. Giao diện chấm điểm lấy Blob qua Axios Bearer token.
- File cũ trên public disk được phục vụ qua endpoint có authorization để không làm hỏng liên kết hiện hữu; **chúng vẫn có thể truy cập bằng URL public cũ**. Không di chuyển hoặc xóa dữ liệu thật. Cần kế hoạch migration file riêng trước khi có thể khẳng định toàn bộ kho cũ là private.
- Login dùng `LOWER(email)` và email chuẩn hóa làm khóa limiter; một tài khoản bị giới hạn 5 request/phút, trần IP 120/phút. Tài khoản bị khóa trả cùng 401 với credentials sai, tránh lộ trạng thái tài khoản.
- Các limiter bắt đầu thi, alert, evidence và exam event dùng khóa theo Sanctum user cùng trần IP cao. Test chính sách mô phỏng 50 tài khoản sau cùng một IP: quota user khác nhau, quota IP dùng chung. Đây là kiểm thử policy; không phải benchmark HTTP 50 trình duyệt.

### Phase 2 — Toàn vẹn bài thi và DB

- `saveDraft`, `submit`, `reportTabLeave` và cảnh báo camera khóa hàng `bai_kiem_tra_luot_lam` theo `(bai_tap_id, sinh_vien_id)` thay vì serialize mọi Sinh viên trên cùng `bai_tap`. `saveDraft` và `submit` cùng dùng attempt lock; draft tiếp tục được khóa và revision token cũ bị từ chối.
- `startExam` vẫn giữ khóa ngắn trên hàng bài tập khi tạo attempt chưa có; bảng có unique `(bai_tap_id, sinh_vien_id)`. Không bỏ khóa tạo mới vì chưa kiểm chứng concurrent insert trên MySQL/MariaDB. `bai_nop` cũng giữ unique `(bai_tap_id, sinh_vien_id)`; upload file không còn khóa toàn assignment, và xử lý duplicate insert bằng constraint hiện tại.
- `ExamAttemptOrder::persist()` tải đáp án theo batch và ghi mapping câu hỏi/đáp án theo batch tối đa 100 row, giữ nguyên shuffle Fisher–Yates và thứ tự đã lưu. Test 30 câu kiểm tra đúng mapping và chỉ có 2 INSERT mapping.
- Seeder thành viên ghi nhầm `sinh_vien.id` vào FK thực tế tham chiếu `nguoi_dung.id`; sửa `ThanhVienLopSeeder` sang `nguoi_dung_id`. Không chạy seeder trên database đang dùng; chỉ test RefreshDatabase SQLite.

### Phase 3 — WebRTC và camera monitor

- `GET /api/webrtc/ice-config` ở trong `auth:sanctum`. STUN/TURN cấu hình server-side bằng `WEBRTC_STUN_URLS`, `WEBRTC_TURN_URLS`, `WEBRTC_TURN_SHARED_SECRET`, `WEBRTC_TURN_TTL_MINUTES`. TURN credential theo chuẩn shared-secret có username hết hạn, ràng buộc account ID, không đưa shared secret xuống client. Khi thiếu TURN, API chỉ trả STUN và giao diện báo rõ “TURN chưa được cấu hình”. Không chỉnh `.env`; **hiện không có TURN service/credential thực nên TURN đang tắt**.
- `studentSender` và `teacherReceiver` dùng cùng `iceConfig.js`; cấu hình được cache trong bộ nhớ theo user, không ghi credential vào localStorage. Phục hồi `disconnected`/`failed` có timer và tối đa 3 retry; PeerConnection cũ được đóng, late response bị bỏ.
- `GiamSatThi` chỉ mount tối đa 9 `StudentCamera` theo trang. Subscription alert Reverb theo từng Sinh viên sống độc lập với video page và tham chiếu channel được đếm để component rời trang không làm unsubscribe cảnh báo của cả phiên. Danh sách cảnh báo vẫn tải từ API khi vào/reload.
- Chưa kiểm tra browser thật/Reverb reconnect. Cấu hình TURN HMAC chỉ được xác minh bằng Feature Test, chưa xác minh Coturn/NAT traversal.
- `GiamSatCamera.vue` gọi `FaceDetector.detectForVideo(video, ...)` và `ObjectDetector.detectForVideo(video, ...)` đồng bộ trên main thread; model lấy từ `storage.googleapis.com`, WASM từ jsDelivr. Chưa chuyển Worker hay self-host vì luồng hiện truyền HTMLVideoElement trực tiếp; cần triển khai/kiểm tra `VideoFrame`/`ImageBitmap`/OffscreenCanvas và xác minh license, phiên bản, cache model trước khi đổi. Ngưỡng AI giữ nguyên.

### Phase 4 — Tests, query và benchmark

- `chiTietPhien()` trước đây query `anh_man_hinh` một lần cho mỗi alert camera (tối đa 30). Hiện lấy các `event_id` trong một truy vấn `whereIn` và ánh xạ tại memory.
- 5 test fail trước đó được phân loại/sửa:
  1. `DangNhapTest`: lỗi sản phẩm — route login chưa throttle; đã thêm limiter và test 429/case-insensitive email.
  2. `DatabaseSeederTest`: expectation cũ sai (seeder tạo 1 cảnh báo chung + tối đa 30 ở 5 lớp, không phải 51); thêm nữa seeder ghi sai loại ID thành viên. Đã sửa fixture/expectation theo source và FK thực tế.
  3–4. `ExamEvidenceTest`: thiếu GD trong runtime khiến `UploadedFile::fake()->image()` ném `LogicException`; đổi sang fixture JPEG 1×1 hợp lệ có sẵn bytes, vẫn kiểm tra validation ảnh thật.
  5. `ReportTabLeaveTest`: fixture gọi endpoint trước khi có attempt `IN_PROGRESS`; thêm attempt bắt đầu vào fixture, không nới điều kiện backend.
- Không chạy tải 10/30/50 Sinh viên. Không chạy MySQL staging concurrency vì môi trường staging chưa được xác nhận; không dùng database thật.

## Files đã tạo/sửa

**Backend**

- `be/app/Http/Controllers/BaiNopController.php` — validate, private upload, authorized download.
- `be/app/Http/Controllers/NguoiDungController.php`, `be/app/Providers/AppServiceProvider.php`, `be/routes/api.php` — login normalization và named rate limits.
- `be/app/Http/Controllers/SinhVienKiemTraController.php`, `be/app/Http/Controllers/SinhVienCanhBaoCameraController.php` — khóa theo attempt của Sinh viên.
- `be/app/Support/ExamAttemptOrder.php` — batch mapping inserts.
- `be/app/Http/Controllers/WebRtcIceConfigController.php` — API ICE/TURN credentials mới.
- `be/config/services.php` — cấu hình server-side STUN/TURN.
- `be/app/Http/Controllers/GiangVienApiController.php` — loại bỏ N+1 ảnh bằng chứng.
- `be/database/seeders/ThanhVienLopSeeder.php` — dùng đúng `nguoi_dung_id`.
- Tests: `BaiNopStudentIdentityTest`, `ClassroomRateLimitPolicyTest`, `DangNhapTest`, `DatabaseSeederTest`, `ExamEvidenceTest`, `ExamShuffleConfigTest`, `ReportTabLeaveTest`, `WebRtcIceConfigTest`.

**Frontend**

- `fe/src/services/webrtc/iceConfig.js` — cấu hình ICE dùng chung và cache memory.
- `fe/src/services/webrtc/studentSender.js`, `teacherReceiver.js` — ICE config và recovery giới hạn; TeacherReceiver giữ alert subscription theo roster.
- `fe/src/components/GiangVien/GiamSatThi/index.vue`, `StudentCamera.vue` — tối đa 9 video/page, cảnh báo mọi Sinh viên, trạng thái TURN.
- `fe/src/components/GiangVien/BaiNop/index.vue` — tải bài nộp qua Axios Blob có Sanctum.

## Kiểm thử thực tế

| Kiểm tra | Kết quả |
|---|---|
| `php artisan test` | **PASS** — 63 tests, 677 assertions; SQLite `:memory:` theo `phpunit.xml`. |
| PHP syntax trên các file backend/test đã sửa | **PASS** — không có syntax error. |
| `php artisan route:list --path=webrtc/ice-config` | **PASS** — một route GET API được đăng ký. |
| `git diff --check` | **PASS**. |
| `npm run build` mặc định | **Không chạy xong** — Vite gặp `EPERM` khi mở `fe/node_modules/.vite-temp/...`; lần chạy tiếp theo chạm `EPERM` khi Vite muốn unlink `fe/dist/.gitkeep`. Không sửa/xóa file này. |
| Vite production build tới thư mục tạm | **PASS** — `npx vite build --configLoader runner --outDir %TEMP%\scm-hardening-verified-dist`, 180 modules, build 2.45s. |
| Frontend unit test | **Không có script test** trong package hiện tại; `npm run test --if-present` không chạy suite. |
| MySQL concurrent test / benchmark | **Chưa chạy** — không có staging được xác nhận. |
| Browser WebRTC hai mạng / Reverb | **Chưa chạy**. |

`phpunit.xml` đặt `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`. Không có migration production nào được chạy; `.env`, dữ liệu MySQL và file thật không bị sửa. Không chạy load trên cổng 8000.

## Hướng dẫn cấu hình staging TURN

1. Chỉ trên backend staging, đặt `WEBRTC_STUN_URLS` (tuỳ chọn), `WEBRTC_TURN_URLS`, `WEBRTC_TURN_SHARED_SECRET` dưới secret manager của server và `WEBRTC_TURN_TTL_MINUTES` (2–60 phút). Không dùng prefix `VITE_` cho shared secret.
2. Rebuild/restart backend staging; đăng nhập Sinh viên/Giảng viên và kiểm tra `GET /api/webrtc/ice-config` trả `turnConfigured: true`, credential hết hạn, `Cache-Control: private, no-store`.
3. Thử một client qua mạng trường và một qua mạng ngoài; quan sát `iceConnectionState`, selected candidate pair và video track. Chỉ báo TURN hoạt động nếu candidate pair thật là relay và video nhận được.
4. Trên MySQL staging riêng, chạy migrations hiện có nếu staging thiếu bảng; sau đó thực hiện concurrent start/save/submit và 50 tài khoản NAT simulation. Không trỏ tới `smart_classroom_monitor`.

## Rủi ro còn lại và kết luận

- Các file bài nộp lịch sử có thể vẫn truy cập được qua public storage URL cũ. Cần inventory và quy trình chuyển file được phê duyệt; không tự động di chuyển/xóa.
- Chưa kiểm tra trusted proxy config trên hosting; limiter dùng `$request->ip()` chuẩn Laravel. Khi có reverse proxy, chỉ khai báo đúng proxy tin cậy ở cấu hình deployment; không tin `X-Forwarded-For` tùy tiện.
- Khóa start attempt vẫn dùng hàng assignment để loại race trước khi attempt tồn tại; tình huống này và tất cả row locks cần MySQL/MariaDB staging concurrency test.
- Không có TURN thực, browser test, MediaPipe worker/self-host hay load benchmark. Browser limits, codec/NAT behavior và CPU/RAM chưa đo.
- Lệnh build mặc định phụ thuộc quyền xóa `fe/dist/.gitkeep`; đã xác minh mã biên dịch bằng output directory tạm.

**Production readiness: NOT READY / CHƯA XÁC NHẬN.** Phần bảo mật upload, rate limit, logic attempt và build đã có kiểm thử tự động; cần hoàn tất kiểm thử MySQL staging, browser/Reverb/WebRTC qua hai mạng và benchmark trước khi tổ chức kỳ thi thực tế.
