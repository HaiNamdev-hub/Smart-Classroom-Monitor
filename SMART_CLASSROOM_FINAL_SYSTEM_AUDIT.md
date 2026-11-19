# Smart Classroom Monitor — Final Full System Audit & Regression Verification

**Ngày kiểm tra:** 19/11/2026  
**Phạm vi:** kiểm tra source, routes, migrations, tests và build hiện có. Chỉ tạo báo cáo này; không sửa source, test, cấu hình hoặc dữ liệu.

## 1. Tóm tắt điều hành

Kiểm tra tự động chạy trong môi trường cô lập đã xác nhận: Laravel dùng SQLite `:memory:` theo `be/phpunit.xml`; Frontend dùng Node test runner. Kết quả hiện tại: **Laravel 63/63 PASS, 677 assertions; Frontend 12/12 PASS; PHP syntax 85 file PASS; Vite production build 180 modules PASS vào thư mục tạm; `git diff --check` PASS**.

Các luồng quan trọng có test cho định danh Sinh viên, phân bổ điểm, hai chế độ thi, lưu nháp/revision, submit, trộn đề theo attempt, private evidence, API ICE, alert events, Excel export, xóa API Question Bank và WebRTC identity. Đây là xác minh tự động với SQLite/mock; không chứng minh lock behavior trên MySQL, kết nối Reverb/WebRTC thật hoặc giao diện qua browser.

**Verdict: CHƯA XÁC NHẬN SẴN SÀNG PRODUCTION.** Rủi ro source đáng chú ý nhất là signaling WebRTC chưa có rate limit riêng và `data` chưa có giới hạn cấu trúc/kích thước. Ngoài ra file bài nộp lịch sử có thể còn public, TURN chưa được triển khai/xác minh, MediaPipe chạy đồng bộ trên main thread, và MySQL/browser/load testing chưa diễn ra.

Không có Critical issue đã tái hiện trong lần audit này. Các finding bên dưới phân biệt rõ rủi ro được chứng minh qua source với lỗi runtime đã tái hiện; không khẳng định các kịch bản availability/security đã bị khai thác.

## 2. Kiến trúc và inventory

| Hạng mục | Inventory hiện tại |
|---|---:|
| Laravel controllers | 34 PHP files |
| Laravel models | 20 PHP files |
| Laravel services | 2 PHP files |
| Migrations | 38 files |
| Backend route files | 4 files |
| API/web route declarations | 86 declarations trong `routes/api.php`, `routes/web.php` |
| Vue router path entries | 35 |
| Frontend target files (Sinh viên, Giảng viên, WebRTC) | 32 |
| Laravel tests | 17 test files (2 Unit + 15 Feature) |
| Frontend tests | 5 Node test files |

Stack khai báo trong manifests: Laravel 12, PHP `^8.2`, Sanctum 4, Reverb 1, PhpSpreadsheet 5, PhpWord 1, PDFParser 2; Vue 3.5, Vite 6.4, Axios, Laravel Echo/Pusher và MediaPipe Tasks Vision. `fe/package.json` khai báo `build`, `test:unit`, `dev`, `preview`; không có script chung tên `test`. `be/phpunit.xml` đặt `APP_ENV=testing`, SQLite `:memory:`, cache/session array, broadcasting null và queue sync.

Các module nghiệp vụ hiện có: auth/role, lớp và thành viên, bài tập/câu hỏi/import, lịch và mode thi, lưu nháp/submit/chấm điểm, giám sát/cảnh báo/evidence, WebRTC signaling, báo cáo Excel. Route Vue `/giang-vien/ngan-hang-cau-hoi` và API Question Bank không còn. Một số migration/bảng Phase 2–3 Question Bank và đoạn `Schema::hasTable('ngan_hang_de_tao')` vẫn tồn tại để tương thích dữ liệu lịch sử; đây là schema/code tương thích còn sót, không phải UI/API Question Bank đang được expose. Không sửa migration lịch sử.

## 3. Phương pháp và giới hạn

- Đọc source, route definitions, migration definitions, PHPUnit/Vite/package configuration và các báo cáo audit trước đó; kết luận trong báo cáo này được đối chiếu lại với source hiện tại, không lấy báo cáo cũ làm bằng chứng duy nhất.
- Chạy test Laravel vì `phpunit.xml` cưỡng chế SQLite `:memory:`. Test suite tự khởi tạo schema trên DB memory; không kết nối MySQL thật.
- Không chạy `migrate:status` trên cấu hình `.env`: lệnh đó có thể mở kết nối tới DB runtime, không cần thiết để audit read-only này. Vì vậy tình trạng migration trên máy chủ/staging là **NOT TESTED**.
- Không đọc/ghi `.env`, không start Laravel/Vite/Reverb server, không tạo dữ liệu staging, không chạy seeder/migration, benchmark hay browser automation.
- `npm run build` mặc định trước đó đã vướng `EPERM` ở cache Vite và thao tác Vite muốn dọn `fe/dist/.gitkeep`. Lần audit này build thành công bằng `npx vite build --configLoader runner --outDir %TEMP%\scm-final-audit-dist`; không xóa hoặc thay đổi `dist`.
- Không chạy request qua API đang chạy hoặc hai browser; không có xác thực runtime, Network trace, TURN relay pair hay CPU/RAM measurement.

## 4. Authentication, authorization và security

### Đã xác minh

- `routes/api.php`: API nghiệp vụ nằm trong `auth:sanctum`; nhóm `/giang-vien` thêm middleware `GiangVien`; admin thêm `QuanTriVien`. Login ở ngoài nhóm nhưng có `throttle:login`.
- `NguoiDungController::dangNhap()` chuẩn hóa email chữ thường, so sánh hash mật khẩu, trả cùng 401 cho credentials sai và tài khoản không hoạt động; tạo personal token có `expires_at` sau một ngày. Logout thu hồi token hiện hành; đổi password/trạng thái/role và xóa tài khoản thu hồi token.
- `AppServiceProvider` đặt limiter theo email canonical và IP cho login; exam-start, alert, evidence, exam-event và ICE endpoint có quota theo user cùng trần IP cao. Feature test mô phỏng nhiều account dùng chung NAT xác nhận key per-account riêng.
- Broadcast auth được đăng ký ở `bootstrap/app.php` với `api` + `auth:sanctum`. `routes/channels.php` giới hạn `exam-session.{phienId}` cho Giảng viên phụ trách lớp; kênh theo từng Sinh viên kiểm tra phiên đang chạy, thành viên lớp, và chỉ cho chính Sinh viên hoặc Giảng viên lớp subscribe.
- `WebRtcSignalController` xác thực người gửi bằng Sanctum, chuyển đúng `sinh_vien.id` sang `nguoi_dung.id` để kiểm tra membership, giới hạn role/type signal và không tin role client.
- Upload bài nộp mới allowlist định dạng, size 10 MB, so extension với MIME đọc từ nội dung, lưu disk `local`; download yêu cầu Sanctum, xác minh chủ bài/Giảng viên lớp, ngăn path traversal và trả attachment/nosniff. Feature test xác nhận owner/teacher access và từ chối truy cập trái quyền.
- Workbook xuất string bằng explicit `TYPE_STRING` qua `safeText()` để chống formula injection; evidence không được nhúng vào file Excel.

### Findings security

| ID | Mức | Finding và bằng chứng | Ảnh hưởng/xác minh | Khuyến nghị | Trạng thái |
|---|---|---|---|---|---|
| SEC-01 | HIGH | WebRTC signal route (`be/routes/api.php` gần dòng 152) nằm trong `auth:sanctum` nhưng không gắn limiter. `WebRtcSignalController.php:33–47` chỉ kiểm `data` là `nullable`, không validate shape hoặc size. | Account hợp lệ trong phiên có thể tạo lượng lớn broadcast hoặc gửi payload lớn làm tăng CPU/băng thông/queue. Đây là rủi ro từ cấu hình/code, **chưa tái hiện DoS**. | Thêm quota theo user/session, giới hạn byte payload và schema riêng cho offer/answer/candidate; test quota và payload lớn. | CODE REVIEW ONLY |
| SEC-02 | MEDIUM | `config/reverb.php:85` mặc định `allowed_origins` là `['*']`. | Origin wildcard làm giảm defense-in-depth; không tự nó vượt qua Sanctum/private-channel authorization. Chưa thử deployment override hoặc browser origin policy. | Cấu hình allowlist origin production qua deployment config; xác nhận websocket auth thực tế. | CODE REVIEW ONLY |
| SEC-03 | MEDIUM | Download vẫn fallback sang `Storage::disk('public')` cho bản ghi cũ trong `BaiNopController.php` (khoảng dòng 410–414). | Endpoint mới có quyền kiểm tra, nhưng file lịch sử có thể còn truy cập qua URL public cũ nếu đã được publish. Chưa kiểm kê disk/runtime. | Inventory đường dẫn cũ, chuẩn bị kế hoạch chuyển private được duyệt và gỡ public exposure theo quy trình riêng. | PARTIALLY VERIFIED |
| SEC-04 | LOW | Không có project-specific `config/cors.php`; CORS/trusted proxy phụ thuộc framework/deployment. | Không thể xác nhận policy đang áp dụng trên máy chủ, reverse proxy hoặc browser từ source test. | Kiểm tra deployment allow-origin, credentials, trusted proxies và HTTPS. | NOT TESTED |

Không có bằng chứng khai thác SQL injection/XSS/IDOR trong phạm vi test đã chạy; không coi điều đó là chứng minh toàn bộ 86 API không có lỗi authorization. Những API nhạy cảm cần tiếp tục được rà từng controller và test IDOR theo role/lớp.

## 5. Database, identity và concurrency

### Identity mapping theo migration

| Bảng/cột | FK thực tế |
|---|---|
| `sinh_vien.id` | Profile ID riêng; `sinh_vien.nguoi_dung_id` → `nguoi_dung.id` (unique) |
| `thanh_vien_lop.sinh_vien_id` | `nguoi_dung.id` |
| `bai_nop.sinh_vien_id` | `nguoi_dung.id`; unique `(bai_tap_id, sinh_vien_id)` |
| `bai_kiem_tra_luot_lam.sinh_vien_id` | `sinh_vien.id`; unique `(bai_tap_id, sinh_vien_id)` |
| `bai_lam_nhap.sinh_vien_id` | `sinh_vien.id`; unique `(bai_tap_id, sinh_vien_id)` |
| `canh_bao_camera.sinh_vien_id` | Code/report join theo `sinh_vien.id`; migration ban đầu chỉ khai báo unsigned ID, không khai báo FK |
| `vi_pham_kiem_tra.sinh_vien_id` | `sinh_vien.id` |
| `su_kien_bao_ve_bai_kiem_tra.sinh_vien_id` | `sinh_vien.id` |
| `canh_bao.sinh_vien_id`, `anh_man_hinh.sinh_vien_id` | `nguoi_dung.id` theo migration/report joins |

`ExamReportService` join membership/submission sang `nguoi_dung`, còn attempt/protection/violation/camera-alert sang `sinh_vien` rồi nối `nguoi_dung` bằng `sv.nguoi_dung_id`. Tests report và `WebRtcStudentIdentityTest` dùng ID khác nhau để bắt lỗi nhầm mapping và PASS.

### Locking và exam integrity

- `startExam` tạo attempt trong transaction với short `bai_tap` row lock (controller khoảng dòng 497–562) để xử lý race khi attempt chưa tồn tại; unique key vẫn là bảo vệ cuối.
- `saveDraft`, `submit`, `reportTabLeave` và camera alert khóa attempt cụ thể `(bai_tap_id, sinh_vien_id)`; save và submit cùng thứ tự khóa attempt trước draft. Draft kiểm `baseDraftRevision`, rồi trả revision SHA-256 từ dữ liệu đọc lại. Backend yêu cầu `answers` present-array và cho phép mảng rỗng.
- `submit` khóa attempt, kiểm tra đã submit, giới hạn câu trả lời vào câu thuộc đề, tự chấm theo ID đáp án đúng và transactionally tạo submission/answers/score; draft bị xóa sau submit thành công. Feature tests cover empty answers, stale revision, idempotent alert và score allocation.
- `ExamAttemptOrder` dùng Fisher–Yates với `random_int`, persist order câu hỏi/đáp án mỗi attempt bằng batch 100; truy vấn câu trả lời theo IDs và reload đọc mapping đã lưu. Test kiểm tra reload stability, independent attempt ordering semantics, chấm theo ID và số INSERT mapping.
- `bai_nop`, attempt, draft và event IDs có unique constraints ở các migration liên quan. `canh_bao_camera` migration không khai báo FK cho `bai_tap_id`/`sinh_vien_id`; đây là thiếu bảo đảm toàn vẹn ở schema (migration lịch sử), dù controller kiểm tra ownership. Không xác nhận schema production thực tế.

### Database findings

| ID | Mức | Finding | Ảnh hưởng và phương án | Trạng thái |
|---|---|---|---|---|
| DB-01 | HIGH | Race/locking chưa được kiểm thử bằng MySQL/MariaDB. `startExam` vẫn lock hàng assignment trước khi attempt tồn tại; SQLite không mô phỏng row locks cùng semantics. | Chưa thể khẳng định không deadlock/lock timeout hoặc hành vi concurrent start/save/submit dưới tải. Chạy test concurrent trên MySQL staging riêng trước kỳ thi. | NOT TESTED |
| DB-02 | MEDIUM | Migration `canh_bao_camera` thiếu FK và migrations/bảng workflow Question Bank cũ vẫn còn. | Có nguy cơ orphan rows và schema dư; không có bằng chứng hỏng dữ liệu hiện tại. Rà schema live và lập migration additive/retirement riêng nếu được duyệt; không sửa migration lịch sử. | CODE REVIEW ONLY |
| DB-03 | LOW | `migrate:status` production/staging chưa chạy, và migration execution chỉ được kiểm tra gián tiếp bằng test suite in-memory. | Chưa biết deployment có migration pending hay schema drift. Kiểm tra trên staging snapshot/DB riêng. | NOT TESTED |

## 6. Exam workflow regression

| Luồng | Kết luận | Bằng chứng hiện có |
|---|---|---|
| Tạo/chỉnh câu hỏi thủ công, phân bổ điểm | PARTIALLY VERIFIED | Allocator Unit/Feature tests; build Vue. Không thực hiện browser thao tác import/edit toàn diện. |
| Import Word/Excel/PDF | PARTIALLY VERIFIED | Có parser PhpSpreadsheet/PhpWord/PDFParser; retired bank workflow test xác nhận DOCX preview. Không test bộ file thật/corrupt/large. |
| Hai chế độ `ONLINE_CAMERA_REQUIRED` / `LAB_NO_CAMERA` | VERIFIED trong API test | `ExamModeTest` kiểm tra quyền, mặc định legacy, khóa mode sau khi bắt đầu, LAB không đòi camera và ONLINE yêu cầu camera. UI/browser chưa chạy. |
| Bắt đầu, lưu nháp, revision, phục hồi offline | VERIFIED trong unit/Feature | 12 Frontend unit tests gồm mạng rớt/reconnect, serialized saves, backup scope/TTL/payload; Backend test empty draft/revision/attempt. IndexedDB browser engine chưa kiểm tra. |
| Refresh, per-attempt shuffle, điểm | VERIFIED trong Feature tests | `ExamShuffleConfigTest`, `ExamScoreAllocationTest`; kiểm tra ID đáp án và cấu hình legacy off. Hai browser thật chưa dùng. |
| Submit và chấm tự luận | PARTIALLY VERIFIED | API tests cover empty answer submit, auto MC and allocator; không có luồng browser thực và không test manual essay grading end-to-end. |
| Lock/unlock do vi phạm | PARTIALLY VERIFIED | Protection, thresholds và report-tab tests hiện có; chưa mô phỏng camera AI hoặc Reverb/browser. |
| Excel report 6 sheets | VERIFIED trong Feature tests | Workbook trả MIME/signature hợp lệ; data joins, event dedupe và role/class restriction tests PASS. Không mở workbook trong Excel/LibreOffice. |
| Question Bank đã gỡ khỏi UI/API | VERIFIED ở route/UI test | Vue router không có route bank; Feature test xác nhận bank endpoints/Phase 3 endpoints không tồn tại; legacy tables/conditional compatibility code còn trong source. |

Không phát hiện lỗi hồi quy được tái hiện trong suite hiện tại. Không thể suy ra các luồng browser/file import/grade đã được xác minh hoàn toàn từ test API.

## 7. WebRTC, realtime và camera pagination

### Đã xác minh từ source/test

- Student camera stream được truyền từ `GiamSatCamera` sang `StudentSender`; không gọi `getUserMedia()` lần hai cho WebRTC. Sender báo `camera_ready` khi private channel subscribe; camera lifecycle không phụ thuộc vào việc AI tạo alert.
- `sessionBootstrap.js` poll context ngay lập tức và mỗi 5 giây, ngăn poll chồng lấn, dừng khi có session/student identity; ba Node tests cover missing session, no overlap/late stop và camera mode không cần signaling.
- Signaling dùng API Sanctum + broadcast private channels. API ICE trả STUN mặc định, TURN credentials HMAC có thời hạn khi cấu hình; shared secret không gửi client. `WebRtcIceConfigTest` cover auth/STUN fallback và credential expiry/HMAC.
- Giảng viên hiển thị tối đa 9 `StudentCamera` bằng `cameraPageSize = 9`; unmount tile gọi `stopWatching()` để đóng receiver PC. Alert stream dùng session channel riêng; roster vẫn có đủ thành viên, chi tiết phiên refresh mỗi 15 giây. `StudentCamera` phát `not_viewed` khi unmount để tránh trạng thái offline giả.
- Cấu hình TURN thực không có trong môi trường được xác minh. Chỉ STUN fallback được test; **không tuyên bố TURN hoạt động**.

### Findings WebRTC

| ID | Mức | Finding | Tái hiện/ảnh hưởng | Trạng thái |
|---|---|---|---|---|
| RTC-01 | MEDIUM | Khi tile bị unmount, receiver `stopWatching()` đóng PC nhưng sender Sinh viên vẫn có stream và `studentSender.scheduleRecovery()` thử dựng offer tối đa 3 lần khi peer disconnected/failed (`studentSender.js:171–201`, `StudentCamera.vue:393–399`). | Khi đổi trang camera có thể có signaling/offer retry thừa từ Sinh viên đang ẩn. Code không cho thấy cleanup/pause signal ở sender gắn với trang Giảng viên. Cần browser test đo request/PC và cân nhắc signaling pause; chưa tái hiện thực tế. | CODE REVIEW ONLY |
| RTC-02 | HIGH | STUN-only nếu không cấu hình TURN; source và UI báo TURN chưa cấu hình. | Mạng NAT đối xứng/firewall có thể thất bại P2P. Chưa có TURN server, relay candidate hay kiểm thử từ hai mạng; tỷ lệ 15–30% trong báo cáo cũ là ước lượng, không phải benchmark. | PARTIALLY VERIFIED |
| RTC-03 | MEDIUM | Node tests kiểm tra helper/proxy/session bootstrap, nhưng không có hai browser, Reverb server hoặc actual camera/ICE run trong lượt này. | Chưa xác minh offer/answer/ICE, reconnect, unsubscribe/reconnect, page-switch races và camera thực. | NOT TESTED |
| RTC-04 | LOW | Event channels yêu cầu session `dang_dien_ra`; quyền channel được kiểm tra source nhưng chưa gọi endpoint `broadcasting/auth` trên Reverb runtime. | Có thể có khác biệt cấu hình auth/prefix/cookie/token giữa dev và deployment. | CODE REVIEW ONLY |

Không có xác minh thông lượng mesh ở 10/30/50 Sinh viên. 9 video tile là giới hạn số receiver đang mount trong UI, không phải chứng minh được hiệu năng/CPU hay stream success rate.

## 8. MediaPipe / AI

- `GiamSatCamera.vue` tạo FaceDetector/ObjectDetector sau khi mở một camera stream; cả `detectForVideo(video, ...)` đều được gọi đồng bộ trên main thread. Face detection lặp theo timer, object/phone detection được giãn theo interval riêng. Camera track `ended` được lắng nghe; unmount gọi cleanup, dừng tracks/timer và đóng detector.
- Model assets lấy từ Google Storage và WASM từ jsDelivr (external CDN). Đây là phụ thuộc mạng/CDN và main-thread CPU, chưa đo lag/frame drop/RAM/CPU. Không có Worker/self-host trong lần này.
- Chế độ `LAB_NO_CAMERA` ẩn các camera monitor/video statistics và `cameraRequired` kiểm tra tại flow Sinh viên/Giảng viên; mode behavior có Feature test. Không kiểm tra thiết bị vật lý/browser.
- Ngưỡng AI hiện giữ nguyên trong source. Cảnh báo là tín hiệu giám sát; không có căn cứ từ audit này để coi output AI là kết luận gian lận.

**Finding AI-01 — MEDIUM, CODE REVIEW ONLY:** detector đồng bộ trên main thread cộng external model/WASM CDN có thể làm giật UI và phụ thuộc CDN trên thiết bị yếu/mạng hạn chế. Chưa đo được tác động thực tế. Roadmap: profile bằng browser trên máy cấu hình thấp; sau đó đánh giá Worker/ImageBitmap và self-host với license/version/cache kiểm soát.

## 9. Báo cáo Excel

- `ExamReportService` giới hạn theo Giảng viên/lớp/bài tập, ghép đúng user/profile IDs và tổng hợp cảnh báo từ `canh_bao`, `canh_bao_camera`, `su_kien_bao_ve_bai_kiem_tra`, `vi_pham_kiem_tra`; event ID dùng khử trùng, còn record không có event ID dùng source+record ID.
- Sheet export giữ sáu sheet, có overview dashboard/charts, bảng Sinh viên/Kết quả/Cảnh báo/Rà soát/Thống kê; formatting có freeze panes, filter, wrap/landscape/A4; text dùng explicit string và phòng formula injection.
- Feature tests xác nhận XLSX signature/content type, joins khác ID, nguồn cảnh báo/dedupe và quyền lớp. Không mở file trong Microsoft Excel, không in A4, không so sánh charts trên ứng dụng Excel versions; compatibility hiển thị là PARTIALLY VERIFIED.
- Không xuất URL ảnh công khai hoặc binary evidence theo logic report đã review; không đo export hiệu năng với lớp lớn.

## 10. Tests, syntax và build

| Kiểm tra lần này | Kết quả | Môi trường/ghi chú |
|---|---|---|
| `php artisan test` | **PASS — 63 tests, 677 assertions, 0 fail/skip** | Laravel 12 / PHPUnit theo config dự án, SQLite `:memory:`; 4.06s. |
| `npm run test:unit` | **PASS — 12 tests, 0 fail/skip** | Node built-in `node --test tests/*.test.js`; 2.11s. Bao gồm proxy test dùng local test servers; không phải Vite+Laravel runtime test. |
| `php -l` cho `app` + `tests` | **PASS — 85 PHP files** | PHP CLI hiện có. |
| Vite production build | **PASS — 180 modules** | `npx vite build --configLoader runner --outDir %TEMP%\scm-final-audit-dist`, 3.20s. |
| `npm run build` mặc định | **PARTIALLY VERIFIED** | Báo cáo lần trước ghi EPERM ở cache/output `dist/.gitkeep`; không retry theo output mặc định để tránh thao tác xóa file. Alternate outDir build PASS. |
| `git diff --check` | **PASS** | Gọi với `safe.directory` chỉ ở command, không thay git config. |
| MySQL migration status / MySQL tests | **NOT TESTED** | Không mở kết nối DB runtime. |
| Browser/realtime/benchmark | **NOT TESTED** | Không khởi động dịch vụ hay tạo dữ liệu. |

Không chạy `npm run test --if-present`; đúng script được dùng là `npm run test:unit`.

## 11. Performance/load readiness

### Cải tiến có bằng chứng

- Attempt row locking thay khóa hàng assignment cho thao tác thường xuyên save/submit/tab/camera; start vẫn khóa assignment ngắn lúc tạo attempt mới.
- `ExamAttemptOrder` batch mapping insert chunks 100 thay insert từng record; unit/feature test kiểm số query insert mapping.
- `chiTietPhien` gom lookup evidence theo `whereIn`, loại một nhóm N+1 đã nhận diện.
- Giảng viên chỉ mount 9 camera cards/page và alert subscription của phiên sống độc lập với video tiles.

### Chưa đo

Không có RPS, p50/p95/p99, error rate tải, DB lock wait/deadlock, connection pool saturation, Reverb CPU, browser CPU/RAM, ICE success rate, camera latency hay Excel export duration. Các con số dự báo tải ở audit cũ không được xem như kết quả benchmark.

Kịch bản staging đề xuất: 10 → 30 → 50 tài khoản giả; mở đồng thời start, autosave định kỳ, tab/camera alert và submit. Ghi request p50/p95/p99, error rate, draft/submit success, lock wait/deadlock, MySQL connections/throughput, Reverb CPU/RAM, browser CPU/RAM, receiver peer count, ICE selected candidate type và thời gian camera-to-teacher. Tạo DB staging riêng và dữ liệu giả; kiểm tra danh tính database trước mọi write.

## 12. Findings theo mức độ

| ID | Mức | Tên | File/dòng | Bằng chứng, ảnh hưởng | Xác minh và ưu tiên |
|---|---|---|---|---|---|
| SEC-01 | HIGH | Signaling API không có limiter/size bound riêng | `be/routes/api.php:152`; `be/app/Http/Controllers/WebRtcSignalController.php:33–47` | Authenticated session participant có thể gửi nhiều hoặc payload `data` lớn; rủi ro Reverb/API resource usage, chưa khai thác. | CODE REVIEW ONLY; P0 trước production thi online. |
| DB-01 | HIGH | Concurrency chưa xác minh trên MySQL | `SinhVienKiemTraController.php:497–562, 742–807, 870–...` | SQLite không chứng minh row-lock semantics và concurrent start/save/submit. | NOT TESTED; P0, staging concurrency. |
| RTC-02 | HIGH | Không có xác nhận TURN relay thực tế | `WebRtcIceConfigController.php`, `config/services.php`, `iceConfig.js` | Khi không cấu hình TURN, chỉ có STUN; một số NAT/firewall có thể chặn P2P. | PARTIALLY VERIFIED; P0 trước thi online diện rộng. |
| SEC-03 | MEDIUM | Legacy submission files có thể còn public | `BaiNopController.php:410–414` | Authorized endpoint hỗ trợ file cũ nhưng public URL gốc có thể vẫn dùng được. | PARTIALLY VERIFIED; P1 inventory/migration plan. |
| RTC-01 | MEDIUM | Sender recovery có thể phát retry khi tile đã bị đóng do pagination | `studentSender.js:171–201`; `StudentCamera.vue:393–399` | Có thể tạo tín hiệu offer thừa khi camera nằm trang khác. | CODE REVIEW ONLY; P1 browser trace. |
| AI-01 | MEDIUM | MediaPipe đồng bộ main thread và phụ thuộc CDN | `GiamSatCamera.vue:264–270, 341–377, 405–409` | Rủi ro giật UI/CDN unavailable trên thiết bị/mạng yếu; chưa đo. | CODE REVIEW ONLY; P1 profile/browser. |
| SEC-02 | MEDIUM | Reverb allowed origins wildcard mặc định | `be/config/reverb.php:85` | Defense-in-depth origin allowlist chưa có ở config source; private channel auth vẫn tồn tại. | CODE REVIEW ONLY; P1 deployment review. |
| DB-02 | MEDIUM | `canh_bao_camera` migration thiếu FK | `2026_10_28_000002_create_canh_bao_camera_table.php` | DB có thể không ngăn orphan FK; controllers kiểm tra nghiệp vụ. | CODE REVIEW ONLY; P2 schema review, không sửa lịch sử trực tiếp. |
| TEST-01 | MEDIUM | Không có browser end-to-end suite | `fe/package.json`, tests hiện có | Unit/API coverage không chứng minh fullscreen, camera permission, Reverb/WebRTC, IndexedDB browser behavior. | VERIFIED inventory; P1/P2 tùy lịch thi. |
| DEP-01 | LOW | Question Bank migrations và compatibility guards còn | migrations `2026_11_17_000001..000003`; `BaiTapController`, `GiangVienApiController`, `SinhVienKiemTraController`, shuffle controller | UI/API mới đã retire nhưng schema/compat code còn; tăng chi phí bảo trì, không phải route exposure hiện tại. | CODE REVIEW ONLY; P3, chỉ retire sau inventory legacy data. |
| DEP-02 | LOW | Project-specific CORS/trusted proxy deployment chưa xác minh | `config/` không có `cors.php`; runtime hosting config ngoài repository | Không thể khẳng định browser origin/proxy headers production. | NOT TESTED; P1 deployment checklist. |

Không ghi finding Critical vì chưa tái hiện lỗi Critical hoặc chứng minh khai thác từ source trong lượt audit. Không có finding xác nhận rằng dữ liệu lịch sử đã sai; DB thật không được truy vấn.

## 13. Top 5 rủi ro còn lại

1. Không có MySQL staging concurrency proof cho start/save/submit và deadlock/lock wait.
2. WebRTC signaling không có limiter riêng và `data` không có giới hạn payload/schema.
3. Chưa có TURN relay thực; STUN-only không đảm bảo kết nối qua NAT/firewall.
4. File bài nộp cũ có thể còn public qua URL lịch sử.
5. Chưa đo browser/MediaPipe và sender recovery churn khi phân trang; chưa có browser test thật.

## 14. Top 5 cải tiến đã xác minh

1. Sanctum auth/role middleware, expiring one-day login token, token revocation, case-insensitive email và login throttle.
2. File bài nộp mới private, MIME/extension/size validation, protected download authorization và ID tests.
3. Student/profile FK mapping được làm rõ trong controllers/report service; test dùng IDs khác nhau.
4. Save/submit/violation serialization theo attempt, optimistic draft revision, unique constraints và batch attempt-order inserts.
5. Camera teacher page giới hạn 9 tile, alert channel toàn phiên độc lập; ICE API/STUN fallback, cùng test Laravel + Node/build.

## 15. Roadmap đề xuất

### P0 — trước kỳ thi online có camera

1. Giới hạn kích thước/schema + per-user/session rate limit cho `/webrtc/signal`; thêm Feature tests.
2. Tạo MySQL/MariaDB staging đã xác nhận tách khỏi DB thật; chạy concurrent start/save/submit, retry/idempotency, lock-wait/deadlock tests.
3. Cấu hình TURN trên staging, xác minh relay candidate từ mạng trường và mạng ngoài; fallback/STUN status rõ ràng.
4. Xác minh quyền download legacy files và thống kê public objects; phê duyệt lộ trình chuyển private.

### P1 — trước rollout lớp đông

1. Browser E2E hai Sinh viên + Giảng viên: mode online/lab, camera permissions, page switch, Reverb reconnect, alerts, fullscreen, draft reload/IndexedDB, submit/grade.
2. Profile MediaPipe và Vue trên thiết bị thấp; kiểm tra sender recovery khi camera tile bị tháo.
3. Benchmark staging 10/30/50 Sinh viên, thu p95/errors/DB locks/CPU/RAM/ICE success.
4. Rà CORS, trusted proxy, HTTPS, Reverb origins và secret management trên deployment.

### P2 — đảm bảo dữ liệu và vận hành

1. Rà schema staging so với 38 migration, đặc biệt FK/index/unique constraints; không sửa migration lịch sử đang dùng.
2. Rà IDOR theo toàn bộ API teacher/admin và import file edge cases.
3. Mở XLSX ở Excel/LibreOffice, kiểm tra chart, in A4, dữ liệu lớn và formula injection fixtures.

### P3 — debt và scale dài hạn

1. Retire an toàn Question Bank tables/guards sau khi inventory dữ liệu legacy và thống nhất kế hoạch schema.
2. Đánh giá MediaPipe worker/self-host và kiến trúc SFU nếu cần quy mô lớn hơn P2P.
3. Chuẩn hóa naming FK giữa `nguoi_dung.id` và `sinh_vien.id` theo migration tương thích, chỉ khi có kế hoạch data migration và rollback.

## 16. Production readiness verdict

**NOT READY / CHƯA ĐỦ BẰNG CHỨNG ĐỂ ĐƯA VÀO KỲ THI PRODUCTION.** Automated regression hiện xanh trên SQLite in-memory và Node; PHP syntax/build cũng xanh. Tuy nhiên còn HIGH availability/security review items và các kiểm chứng bắt buộc chưa chạy: MySQL concurrency, TURN relay thật, hai browser, Reverb runtime, deployment CORS/proxy, legacy public file inventory và benchmark. Chỉ chuyển trạng thái sau khi các mục P0 hoàn tất trên staging cô lập và browser test có log/bằng chứng.

## Terminal summary

- **Top 5 risks:** MySQL concurrency chưa test; WebRTC signal payload/limiting; thiếu TURN thực; legacy files public; browser/MediaPipe/pagination recovery chưa đo.
- **Top 5 verified improvements:** Sanctum/rate limits login; private upload mới; identity mapping tests; attempt locking + revision + batch insert; 9 camera tiles và session alert feed độc lập.
- **Tests:** Laravel 63 PASS / 0 FAIL / 0 SKIP, 677 assertions. Frontend 12 PASS / 0 FAIL / 0 SKIP. PHP syntax 85 files PASS. Vite build PASS, 180 modules. `git diff --check` PASS.
- **MySQL staging còn phải kiểm tra:** migration status/schema drift, concurrent starts/saves/submits, unique constraint races, locks/deadlocks, indexes/query plans và số connection.
- **Browser thực tế còn phải kiểm tra:** hai Sinh viên + Giảng viên, camera/AI, Reverb auth/reconnect, SDP/ICE/TURN relay, pagination/offscreen recovery, LAB_NO_CAMERA, fullscreen, IndexedDB, submit/grade và file download.
- **Thứ tự đề xuất:** giới hạn signaling → dựng staging DB cô lập và concurrency tests → TURN relay → browser E2E → legacy file review → benchmark 10/30/50 → mới quyết định production.

