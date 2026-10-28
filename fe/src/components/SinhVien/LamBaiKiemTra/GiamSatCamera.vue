<template>
    <section class="camera-monitor">
        <div class="monitor-head">
            <div>
                <strong>📷 Giám sát khuôn mặt</strong>
                <p>Chỉ phân tích số khuôn mặt trên thiết bị, không lưu video.</p>
            </div>
            <span :class="['state', active ? 'running' : 'stopped']">{{ active ? 'Đang giám sát' : 'Chưa bật' }}</span>
        </div>

        <div class="camera-frame">
            <video ref="videoRef" autoplay playsinline muted></video>
            <div v-if="!active" class="camera-placeholder">Camera chưa hoạt động</div>
            <span v-if="active" class="face-count">Khuôn mặt: {{ faceCount }}</span>
        </div>

        <div v-if="warning" class="warning" role="alert">⚠ {{ warning }}</div>
        <div v-if="error" class="error" role="alert">{{ error }}</div>
        <p v-if="active && !warning" class="ok">{{ faceCount === 1 ? '✓ Có 1 khuôn mặt trong khung hình' : 'Đang kiểmtrakhuôn mặt...' }}</p>

        <div class="actions">
            <button v-if="!active" type="button" :disabled="starting" @click="startCamera">
                {{ starting ? 'Đang khởi tạo...' : 'Bật camera và bắt đầu giám sát' }}
            </button>
            <button v-else type="button" class="stop" @click="stopCameraManually">Tắt camera</button>
        </div>
        <small>Camera chỉ bật khi bạn cho phép. Việc phát hiện có thể sai khi thiếu sáng hoặc bị che khuất.</small>
    </section>
</template>

<script setup>
import { ref, onBeforeUnmount } from 'vue'
import { FaceDetector, FilesetResolver } from '@mediapipe/tasks-vision'

const emit = defineEmits(['warning', 'camera-state'])
const videoRef = ref(null)
const active = ref(false)
const starting = ref(false)
const faceCount = ref(0)
const warning = ref('')
const error = ref('')

const MODEL_URL = 'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_short_range/float16/1/blaze_face_short_range.tflite'
const WASM_URL = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision/wasm'

let detector = null
let stream = null
let timer = null
let absenceSince = null
let lastAbsenceAlertAt = null
let multipleSince = null
let lastWarningType = ''
let sequence = 0

function raiseWarning(type, message, repeat = false) {
    warning.value = message
    if (!repeat && lastWarningType === type) return
    lastWarningType = type
    emit('warning', { type, message, at: new Date().toISOString() })
}

function checkFaces() {
    const video = videoRef.value
    if (!active.value || !video || !detector || video.readyState < 2) return

    try {
        const result = detector.detectForVideo(video, performance.now())
        const count = result.detections?.length ?? 0
        faceCount.value = count
        const now = Date.now()


        if (
            now - absenceSince >= 5000 &&
            (
                lastAbsenceAlertAt === null ||
                now - lastAbsenceAlertAt >= 5000
            )
        ) {
            lastAbsenceAlertAt = now

            raiseWarning(
                "khong_co_khuon_mat",
                "Không phát hiện khuôn mặt liên tục 5 giây.",
                true
            )

        } else if (count >= 2) {
            multipleSince ??= now
            absenceSince = null
            lastAbsenceAlertAt = null
            if (now - multipleSince >= 3000) {
                raiseWarning('nhieu_khuon_mat', `Phát hiện ${count} khuôn mặt trong khung hình.`)
            }
        } else {
            absenceSince = null
            lastAbsenceAlertAt = null
            multipleSince = null
            warning.value = ''
            lastWarningType = ''
        }
    } catch (err) {
        console.error('Lỗi phân tích camera:', err)
        error.value = 'Không thể phân tích khung hình camera.'
        stopCamera()
    }
}

async function startCamera() {
    if (starting.value || active.value) return
    const current = ++sequence
    starting.value = true
    error.value = ''
    warning.value = ''
    lastWarningType = ''
    absenceSince = null
    lastAbsenceAlertAt = null
    multipleSince = null
    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            throw new Error('Trình duyệt cần chạy trên localhost hoặc HTTPS và hỗ trợ camera.')
        }
        const media = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false,
        })
        if (current !== sequence) { media.getTracks().forEach(t => t.stop()); return }
        stream = media
        const video = videoRef.value
        if (!video) throw new Error('Không tìm thấy phần tử video.')
        video.srcObject = media
        await video.play()

        const vision = await FilesetResolver.forVisionTasks(WASM_URL)
        if (current !== sequence) return
        detector = await FaceDetector.createFromOptions(vision, {
            baseOptions: { modelAssetPath: MODEL_URL, delegate: 'CPU' },
            runningMode: 'VIDEO',
            minDetectionConfidence: 0.5,
        })
        if (current !== sequence) { detector.close(); detector = null; return }
        active.value = true
        emit('camera-state', true)
        timer = window.setInterval(checkFaces, 500)
        for (const track of media.getVideoTracks()) {
            track.addEventListener('ended', () => {
                if (current !== sequence) return
                raiseWarning('camera_ngat', 'Camera đã bị ngắt kết nối.')
                stopCamera(true)
            }, { once: true })
        }
    } catch (err) {
        error.value = err?.name === 'NotAllowedError'
            ? 'Bạn chưa cấp quyền camera. Hãy cho phép camera trong trình duyệt.'
            : (err?.message || 'Không thể bật camera hoặc tải mô hình AI.')
        stopCamera(true)
    } finally {
        if (current === sequence) starting.value = false
    }
}

function stopCameraManually() {
    if (active.value) raiseWarning('camera_ngat', 'Sinh viên đã tắt camera trong bài kiểm tra.')
    stopCamera(true)
}

function stopCamera(keepMessage = false) {
    sequence++
    if (timer) window.clearInterval(timer)
    timer = null
    active.value = false
    emit('camera-state', false)
    starting.value = false
    faceCount.value = 0
    absenceSince = null
    lastAbsenceAlertAt = null
    multipleSince = null
    if (!keepMessage) { warning.value = ''; error.value = '' }
    if (stream) stream.getTracks().forEach(track => track.stop())
    stream = null
    if (videoRef.value) videoRef.value.srcObject = null
    if (detector) detector.close()
    detector = null
}

onBeforeUnmount(() => stopCamera())
</script>

<style scoped>
.camera-monitor {
    background: #111;
    color: #fff;
    border: 1px solid #3b3b3b;
    border-radius: 16px;
    padding: 16px;
    max-width: 400px;
    font-family: Inter, system-ui, sans-serif
}

.monitor-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 12px
}

.monitor-head strong {
    font-size: 15px
}

.monitor-head p,
small {
    color: #a3aab7;
    font-size: 11px;
    line-height: 1.6
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

.face-count {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #000b;
    padding: 6px 9px;
    border-radius: 7px;
    font-size: 12px
}

.warning,
.error {
    padding: 11px;
    border-radius: 9px;
    margin: 12px 0;
    font-size: 12px
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
    font-size: 12px;
    color: #86efac
}

.actions {
    margin: 12px 0
}

.actions button {
    width: 100%;
    background: #2563eb;
    color: #fff;
    border: 0;
    border-radius: 9px;
    padding: 11px;
    font-weight: 700;
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
