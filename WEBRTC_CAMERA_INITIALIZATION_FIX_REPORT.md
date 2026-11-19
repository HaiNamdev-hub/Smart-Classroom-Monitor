# WEBRTC CAMERA INITIALIZATION FIX REPORT

Date: 19/11/2026

## Root cause

The initialization path required three values at once: camera stream, `phienGiamSatId`, and `sinhVienId`. `fetchViolationState()` and the exam detail response supplied the IDs only when an active monitoring session existed at the time of the request. If the student page loaded/started before the active session became visible, `initWebRtcIfReady()` returned without initializing the sender. A later `stream-ready` callback called the same function once and also returned without scheduling another lookup. The sender therefore had no private channel and could not receive the teacher's offer request.

The camera-alert path can make the failure appear alert-dependent: after an accepted AI warning, `handleCameraWarning()` flushes the queue and calls `resumeMonitoring()`, which fetches `/trang-thai` again. If a session has become active by then, this request supplies `phienGiamSatId`; `initWebRtcIfReady()` finally subscribes the sender and the existing `camera_ready`/`request_offer` handshake begins. The alert itself is not used to create the PeerConnection.

There was a second lifecycle delay: `GiamSatCamera.vue` emitted `stream-ready` only after WASM and both MediaPipe models had initialized. Video transport therefore waited on AI setup despite already having a playable `MediaStream`.

## Changes

- `fe/src/components/SinhVien/LamBaiKiemTra/GiamSatCamera.vue`: emits the usable camera stream immediately after `video.play()`. AI readiness still controls `camera-state` and the Backend start requirement. Stream stop is emitted only if a stream was announced.
- `fe/src/components/SinhVien/LamBaiKiemTra/index.vue`: initializes signaling only after the Backend confirms `IN_PROGRESS`; when the camera is ready but session identity is absent, it starts an independent low-rate status lookup. The lookup stops when a valid session/student identity is found, camera stops, or the component unmounts. It does not depend on alerts and does not request camera access itself.
- `fe/src/services/webrtc/sessionBootstrap.js`: added a single-flight, stoppable session-context poller. Concurrent status requests are suppressed; API errors retry at a 5-second interval; no identifiers are logged.
- `fe/src/components/GiangVien/GiamSatThi/index.vue`: silently refreshes session/student status every 15 seconds so the roster reflects students who begin after the lecturer opened the page. Existing `StudentCamera` components remain keyed by student ID, so refresh does not create additional tiles or PeerConnections.
- `fe/tests/webrtc-session-bootstrap.test.js`: covers delayed session availability, non-overlapping requests, stop behavior, and no-camera mode.

No Laravel controller, Reverb channel, signaling authorization, alert endpoint, migration, database record, AI threshold, autosave, submission, or grading logic was changed.

## Signaling flow after the fix

1. Student grants camera permission and the browser stream becomes playable.
2. The stream is handed to the page for transport independently of MediaPipe model initialization.
3. MediaPipe initializes and emits camera-ready; only then can the existing start-exam API transition to `IN_PROGRESS`.
4. After that Backend confirmation, the student sender initializes once the current session and student profile IDs are available. If the session was created later, the page polls the existing status API until it appears.
5. Student subscribes to its authorized private channel and sends `camera_ready`. Teacher tiles subscribe for roster members; channel subscription requests an offer. If either side arrives first, the existing request/ready handshake covers both orders.
6. The student creates an offer; teacher sets the remote description, sends an answer, and ICE candidates are exchanged. AI/protection alerts continue on their existing independent events.

`LAB_NO_CAMERA` does not render `GiamSatCamera`, and the session bootstrap's `shouldRun` predicate requires camera mode and an active exam.

## Verification

- Frontend unit tests: **12/12 PASS**.
- Laravel `WebRtcStudentIdentityTest` and `ExamModeTest`: **6 tests, 38 assertions PASS** on the isolated PHPUnit SQLite database.
- PHP syntax for `WebRtcSignalController.php`, `WebRtcSignalEvent.php` and `routes/channels.php`: PASS; these files were inspected and not modified.
- Vite production build: **PASS**, 179 modules transformed. Used `vite --configLoader runner build --outDir <TEMP>` because the default bundled config loader hit the Windows workspace permission issue writing `.vite-temp` in an earlier build.
- `git diff --check`: PASS.

## Browser/WebRTC status and remaining checks

A real two-browser WebRTC/Reverb session was not available in this execution environment. No claim is made that real video transport was browser-tested. The following still require manual staging verification:

1. Lecturer opens the active session before the student starts; verify live video without any alert.
2. Student starts first and the active session becomes available afterward; verify the session bootstrap discovers it and connects without an alert.
3. Reload lecturer and student pages separately; verify each side rebuilds its channel/PeerConnection.
4. Trigger an AI warning after connection and confirm video does not restart or create a duplicate tile.
5. Disable/reconnect Reverb and confirm channel resubscription and the existing bounded offer retries recover.
6. Run with multiple students and confirm each tile receives only its own private-channel stream.
7. Verify `LAB_NO_CAMERA` creates no `getUserMedia` call or WebRTC video.

No migration ran and no MySQL data was read or changed for this task. No commit or push was made.
