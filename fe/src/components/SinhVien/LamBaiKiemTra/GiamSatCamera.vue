<template>
    <section class="camera-monitor">
        <div class="monitor-head">
            <div>
                <strong>📷 Giám sát bài kiểm tra</strong>
                <p>Nhận diện khuôn mặt và điện thoại ngay trên thiết bị, không lưu video.</p>
            </div>
            <span :class="['state', active ? 'running' : 'stopped']">
                {{ active ? 'Đang giám sát' : 'Chưa bật' }}
            </span>
        </div>

        <div class="camera-frame">
            <video ref="videoRef" autoplay muted playsinline></video>
            <div v-if="!active" class="camera-placeholder">Camera chưa hoạt động</div>
            <span v-if="active" class="face-count">👤 {{ faceCount }} khuôn mặt</span>
            <span v-if="active && phoneDetected" class="phone-label">📱 Có điện thoại</span>
        </div>

        <div class="monitor-info">
            <div><span>Camera</span><strong :class="active ? 'good' : 'bad'">{{ active ? 'Đang bật' : 'Chưa bật'
            }}</strong></div>
            <div><span>Số khuôn mặt</span><strong>{{ active ? faceCount : '—' }}</strong></div>
            <div><span>Điện thoại</span><strong :class="phoneDetected ? 'bad' : 'good'">{{ active ? (phoneDetected ?
                'Phát hiện' : 'Không phát hiện') : '—' }}</strong></div>
        </div>

        <div v-if="warning" class="warning" role="alert">⚠ {{ warning }}</div>
        <div v-if="error" class="error" role="alert">{{ error }}</div>
        <p v-if="active && !warning && !error" class="ok">
            {{ faceCount === 1 && !phoneDetected ? '✓ Giám sát bình thường' : 'Đang kiểm tra hình ảnh camera...' }}
        </p>

        <div class="actions">
            <button v-if="!active" type="button" :disabled="starting" @click="startCamera">
                {{ starting ? 'Đang khởi tạo AI...' : 'Bật camera và bắt đầu giám sát' }}
            </button>
            <button v-else type="button" class="stop" @click="stopCameraManually">Tắt camera</button>
        </div>
        <small>
            Camera chỉ bật khi bạn cấp quyền. Không có khuôn mặt liên tục: cảnh báo mỗi 5 giây;
            điện thoại xuất hiện liên tục 3 giây: cảnh báo một lần mỗi đợt.
            AI có thể nhận nhầm, cần được giảng viên xem xét.
        </small>
    </section>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { FaceDetector, ObjectDetector, FilesetResolver } from '@mediapipe/tasks-vision'

const emit = defineEmits([
  "warning",
  "camera-state",
  "normal",
  "stream-ready",
  "stream-stopped"
])
const videoRef = ref(null)
const active = ref(false)
const starting = ref(false)
const faceCount = ref(0)
const phoneDetected = ref(false)
const warning = ref('')
const error = ref('')

const FACE_MODEL_URL = 'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite'
const OBJECT_MODEL_URL = 'https://storage.googleapis.com/mediapipe-tasks/object_detector/efficientdet_lite0_uint8.tflite'
const WASM_URL = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision/wasm'

const CHECK_INTERVAL_MS = 500
const NO_FACE_WARNING_MS = 5000
const MANY_FACES_WARNING_MS = 3000
// Cảnh báo ngay khi AI phát hiện điện thoại
const PHONE_WARNING_MS = 0

// Kiểm tra điện thoại mỗi 300ms
const PHONE_DETECT_INTERVAL_MS = 300
const PHONE_RESET_MS = 2000

let faceDetector = null
let objectDetector = null
let stream = null
let timer = null
let generation = 0
let destroyed = false
let busy = false
let previousVideoTime = -1
let lastPhoneDetectAt = -Infinity

let absenceSince = null
let absenceWarnings = 0
let multipleSince = null
let multipleWarningSent = false
let phoneSince = null
let phoneWarningSent = false
let phoneMissingSince = null
let mostRecentWarning = ''

function emitWarning(type, message) {
    mostRecentWarning = message
    warning.value = message
    emit('warning', {
        type,
        message,
        at: new Date().toISOString(),
        timestamp: new Date().toISOString(),
    })
}

function updateWarningText() {
    if (faceCount.value === 0 && absenceWarnings > 0) {
        warning.value = 'Không phát hiện khuôn mặt trong camera.'
    } else if (faceCount.value >= 2 && multipleWarningSent) {
        warning.value = 'Phát hiện nhiều khuôn mặt trong camera.'
    } else if (phoneDetected.value && phoneWarningSent) {
        warning.value = 'Phát hiện điện thoại trong camera.'
    } else {
        if (warning.value) emit('normal', { at: new Date().toISOString() })
        warning.value = ''
    }
}

function processFaces(count, now) {
    faceCount.value = count

    if (count === 0) {
        multipleSince = null
        multipleWarningSent = false
        if (absenceSince === null) absenceSince = now

        // Mốc 5 giây, 10 giây, 15 giây... kể từ khi liên tục không có mặt.
        const shouldHaveWarned = Math.floor((now - absenceSince) / NO_FACE_WARNING_MS)
        if (shouldHaveWarned > absenceWarnings) {
            absenceWarnings = shouldHaveWarned
            emitWarning('khong_co_khuon_mat', 'Không phát hiện khuôn mặt liên tục 5 giây.')
        }
    } else if (count >= 2) {
        absenceSince = null
        absenceWarnings = 0
        if (multipleSince === null) multipleSince = now
        if (!multipleWarningSent && now - multipleSince >= MANY_FACES_WARNING_MS) {
            multipleWarningSent = true
            emitWarning('nhieu_khuon_mat', `Phát hiện ${count} khuôn mặt liên tục 3 giây.`)
        }
    } else {
        absenceSince = null
        absenceWarnings = 0
        multipleSince = null
        multipleWarningSent = false
    }
    updateWarningText()
}


function processPhone(hasPhone, now) {
    phoneDetected.value = hasPhone

    if (hasPhone) {
        // Điện thoại đã xuất hiện trở lại
        phoneMissingSince = null

        if (phoneSince === null) {
            phoneSince = now
        }

        // Cảnh báo ngay khi nhận diện được
        if (
            !phoneWarningSent &&
            now - phoneSince >= PHONE_WARNING_MS
        ) {
            phoneWarningSent = true

            emitWarning(
                'phat_hien_dien_thoai',
                'Phát hiện điện thoại trong camera!'
            )

            console.warn(
                'CẢNH BÁO: Phát hiện điện thoại!'
            )
        }
    } else {
        phoneSince = null

        if (phoneMissingSince === null) {
            phoneMissingSince = now
        }

        // Không thấy điện thoại liên tục 2 giây
        // thì mới cho phép cảnh báo lần tiếp theo
        if (
            now - phoneMissingSince >= PHONE_RESET_MS
        ) {
            phoneWarningSent = false
        }
    }

    updateWarningText()
}


function checkCamera() {
    if (!active.value || busy || !faceDetector || !objectDetector) return
    const video = videoRef.value
    if (!stream?.getVideoTracks().some(track => track.readyState === 'live')) {
        disconnectCamera()
        return
    }
    if (!video || video.readyState < HTMLMediaElement.HAVE_CURRENT_DATA) return
    if (video.currentTime === previousVideoTime) return

    busy = true
    try {
        previousVideoTime = video.currentTime
        const now = performance.now()
        const faces = faceDetector.detectForVideo(video, now)
        processFaces(faces.detections?.length ?? 0, now)

        if (now - lastPhoneDetectAt >= PHONE_DETECT_INTERVAL_MS) {
            lastPhoneDetectAt = now
            const objects = objectDetector.detectForVideo(video, now)
            const hasPhone = (objects.detections || []).some(detection =>
                (detection.categories || []).some(category =>
                    (category.categoryName === 'cell phone' || category.categoryName === 'mobile phone') &&
                    (category.score ?? 0) >= 0.5
                )
            )
            processPhone(hasPhone, now)
        }
    } catch (err) {
        console.error('Lỗi phân tích hình ảnh:', err)
        error.value = 'Không thể phân tích camera. Vui lòng bật lại camera.'
        disconnectCamera()
    } finally {
        busy = false
    }
}


async function startCamera() {
    if (starting.value || active.value || destroyed) return

    const current = ++generation

    starting.value = true
    error.value = ''
    warning.value = ''

    resetDetection()

    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error(
                'Camera yêu cầu localhost hoặc HTTPS và trình duyệt có hỗ trợ.'
            )
        }

        // 1. Mở webcam của Sinh viên
        const media = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 },
            },
            audio: false,
        })

        if (current !== generation || destroyed) {
            media.getTracks().forEach(track => track.stop())
            return
        }

        // 2. Lưu luồng webcam để AI và WebRTC cùng sử dụng
        stream = media

        const video = videoRef.value

        if (!video) {
            throw new Error('Không tìm thấy phần tử video.')
        }

        video.srcObject = media
        await video.play()

        if (current !== generation || destroyed) return

        // 3. Tải MediaPipe
        const vision = await FilesetResolver.forVisionTasks(WASM_URL)

        if (current !== generation || destroyed) return

        // 4. Khởi tạo AI phát hiện khuôn mặt
        const newFace = await FaceDetector.createFromOptions(
            vision,
            {
                baseOptions: {
                    modelAssetPath: FACE_MODEL_URL,
                    delegate: 'CPU',
                },
                runningMode: 'VIDEO',
                minDetectionConfidence: 0.5,
            }
        )

        if (current !== generation || destroyed) {
            newFace.close()
            return
        }

        faceDetector = newFace

        // 5. Khởi tạo AI phát hiện điện thoại
        const newObject = await ObjectDetector.createFromOptions(
            vision,
            {
                baseOptions: {
                    modelAssetPath: OBJECT_MODEL_URL,
                    delegate: 'CPU',
                },
                runningMode: 'VIDEO',
                scoreThreshold: 0.5,
                categoryAllowlist: ['cell phone'],
            }
        )

        if (current !== generation || destroyed) {
            newObject.close()
            return
        }

        objectDetector = newObject

        // 6. Đánh dấu camera đã sẵn sàng
        active.value = true

        emit('camera-state', true)

        // 7. Gửi luồng webcam sang LamBaiKiemTra/index.vue
        // Luồng này sẽ được dùng để truyền video qua WebRTC.
        emit('stream-ready', media)

        // 8. Theo dõi khi camera bị ngắt
        for (const track of media.getVideoTracks()) {
            track.addEventListener(
                'ended',
                () => {
                    if (current === generation) {
                        disconnectCamera()
                    }
                },
                { once: true }
            )
        }

        // 9. Bắt đầu AI giám sát
        timer = window.setInterval(
            checkCamera,
            CHECK_INTERVAL_MS
        )

        checkCamera()

    } catch (err) {
        console.error('Không thể bật camera:', err)

        if (!destroyed) {
            error.value =
                err?.name === 'NotAllowedError'
                    ? 'Bạn chưa cấp quyền camera. Hãy cho phép camera trong trình duyệt.'
                    : (
                        err?.message ||
                        'Không thể bật camera hoặc tải mô hình AI.'
                    )
        }

        stopCamera(true)

    } finally {
        if (current === generation) {
            starting.value = false
        }
    }
}


function resetDetection() {
    faceCount.value = 0
    phoneDetected.value = false
    previousVideoTime = -1
    lastPhoneDetectAt = -Infinity
    absenceSince = null
    absenceWarnings = 0
    multipleSince = null
    multipleWarningSent = false
    phoneSince = null
    phoneWarningSent = false
    phoneMissingSince = null
    mostRecentWarning = ''
}

function disconnectCamera() {
    if (!active.value) return
    emitWarning('camera_ngat', 'Camera đã bị ngắt trong thời gian làm bài.')
    stopCamera(true)
}

function stopCameraManually() {
    if (active.value) emitWarning('camera_ngat', 'Sinh viên đã tắt camera trong bài kiểm tra.')
    stopCamera(true)
}


function stopCamera(keepMessage = false) {
    // 1. Hủy phiên xử lý camera hiện tại
    generation++

    // 2. Dừng vòng lặp AI
    if (timer !== null) {
        window.clearInterval(timer)
    }
    timer = null

    // 3. Cập nhật trạng thái camera
    const wasActive = active.value
    active.value = false

    if (wasActive) {
        emit('camera-state', false)
    }

    starting.value = false

    // 4. Thông báo cho component cha trước khi tắt webcam
    if (stream) {
        emit('stream-stopped')

        // Dừng tất cả track webcam
        stream.getTracks().forEach(track => {
            track.stop()
        })

        stream = null
    }

    // 5. Ngắt hình ảnh khỏi phần tử video
    if (videoRef.value) {
        videoRef.value.srcObject = null
    }

    // 6. Giải phóng AI nhận diện khuôn mặt
    if (faceDetector) {
        faceDetector.close()
        faceDetector = null
    }

    // 7. Giải phóng AI nhận diện điện thoại
    if (objectDetector) {
        objectDetector.close()
        objectDetector = null
    }

    // 8. Đặt lại trạng thái nhận diện
    resetDetection()

    // 9. Xóa thông báo nếu không cần giữ lại
    if (!keepMessage) {
        warning.value = ''
        error.value = ''
    }
}


onBeforeUnmount(() => {
    destroyed = true
    stopCamera()
})
</script>

<style scoped>
.camera-monitor {
    background: #111;
    color: #fff;
    border: 1px solid #3b3b3b;
    border-radius: 16px;
    padding: 13px;
    width: 100%;
    font-family: Inter, system-ui, sans-serif;
    box-sizing: border-box
}

.monitor-head {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    align-items: flex-start;
    margin-bottom: 12px
}

.monitor-head strong {
    font-size: 13px
}

.monitor-head p,
small {
    color: #a3aab7;
    font-size: 10px;
    line-height: 1.5
}

.monitor-head p {
    margin: 5px 0 0
}

.state {
    white-space: nowrap;
    font-size: 10px;
    border-radius: 20px;
    padding: 6px 8px
}

.running {
    background: #153b29;
    color: #86efac
}

.stopped {
    background: #36393e;
    color: #cbd5e1
}

.camera-frame {
    position: relative;
    background: #202327;
    border-radius: 12px;
    overflow: hidden;
    aspect-ratio: 4/3
}

.camera-frame video {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1)
}

.camera-placeholder {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    color: #9ca3af;
    font-size: 12px
}

.face-count,
.phone-label {
    position: absolute;
    left: 8px;
    background: #000b;
    padding: 5px 7px;
    border-radius: 7px;
    font-size: 10px
}

.face-count {
    top: 8px
}

.phone-label {
    bottom: 8px;
    background: #7f1d1d
}

.monitor-info {
    display: grid;
    gap: 8px;
    padding: 12px 0
}

.monitor-info>div {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    font-size: 11px
}

.monitor-info span {
    color: #a3aab7
}

.good {
    color: #86efac
}

.bad {
    color: #fca5a5
}

.warning,
.error {
    padding: 10px;
    border-radius: 9px;
    margin: 8px 0;
    font-size: 11px;
    line-height: 1.5
}

.warning {
    background: #51301c;
    color: #fed7aa
}

.error {
    background: #4a2020;
    color: #fecaca
}

.ok {
    font-size: 11px;
    color: #86efac
}

.actions {
    margin: 10px 0
}

.actions button {
    width: 100%;
    background: #2563eb;
    color: #fff;
    border: 0;
    border-radius: 9px;
    padding: 11px;
    font-weight: 700;
    font-size: 11px;
    cursor: pointer
}

.actions button:disabled {
    opacity: .6
}

.actions .stop {
    background: #7f1d1d
}

small {
    display: block
}
</style>
