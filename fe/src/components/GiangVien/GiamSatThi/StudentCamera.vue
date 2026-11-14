<template>
  <div
    class="student-card"
    :class="{
      'is-live': connectionStatus === 'connected',
      'is-disconnected': connectionStatus === 'disconnected' || connectionStatus === 'failed',
      'is-submitted': student.daNopBai,
      'is-locked': student.biKhoa,
      'has-warning': hasRecentWarning,
    }"
  >
    <!-- VIDEO FRAME (16:9) -->
    <div class="video-container" @click="emit('select', student)">
      <video
        ref="videoRef"
        autoplay
        playsinline
        muted
        :class="{ active: connectionStatus === 'connected' && !student.daNopBai && !student.biKhoa }"
      ></video>

      <!-- OVERLAY TRẠNG THÁI KHI KHÔNG CÓ VIDEO LIVE -->
      <div v-if="connectionStatus !== 'connected' || student.daNopBai || student.biKhoa" class="video-placeholder">
        <template v-if="student.daNopBai">
          <div class="status-box submitted">
            <span class="icon">✅</span>
            <strong>Đã nộp bài thi</strong>
            <small>Hoàn thành bài kiểm tra</small>
          </div>
        </template>

        <template v-else-if="student.biKhoa">
          <div class="status-box locked">
            <span class="icon">🔒</span>
            <strong>Bài thi bị đình chỉ</strong>
            <small>{{ formatLockReason(student.lyDoKhoa) }}</small>
          </div>
        </template>

        <template v-else-if="connectionStatus === 'connecting'">
          <div class="status-box connecting">
            <div class="mini-spinner"></div>
            <strong>Đang kết nối camera...</strong>
            <small>Thiết lập WebRTC P2P ({{ connectingCountdown }}s)</small>
          </div>
        </template>

        <template v-else-if="connectionStatus === 'camera_stopped'">
          <div class="status-box off">
            <span class="icon">📷✕</span>
            <strong>Camera đã tắt</strong>
            <small>Sinh viên đã ngắt webcam</small>
          </div>
        </template>

        <template v-else-if="connectionStatus === 'disconnected' || connectionStatus === 'failed'">
          <div class="status-box error">
            <span class="icon">⚠️</span>
            <strong>Chưa nhận được tín hiệu</strong>
            <small>WebRTC timeout hoặc chưa phản hồi</small>
            <button
              type="button"
              class="retry-btn"
              @click.stop="handleRetry"
              :disabled="retrying"
            >
              {{ retrying ? 'Đang thử lại...' : '↻ Kết nối lại' }}
            </button>
          </div>
        </template>

        <template v-else>
          <div class="status-box waiting">
            <span class="icon">📷</span>
            <strong>Chờ camera</strong>
            <small>Đang đợi sinh viên bật webcam</small>
            <button
              type="button"
              class="retry-btn"
              @click.stop="handleRetry"
              :disabled="retrying"
            >
              {{ retrying ? 'Đang gửi...' : '⚡ Yêu cầu webcam' }}
            </button>
          </div>
        </template>
      </div>

      <!-- BADGE TRẠNG THÁI GÓC TRÊN TRÁI -->
      <div class="stream-badge" :class="streamBadgeClass">
        <span class="dot"></span>
        {{ streamBadgeText }}
      </div>

      <!-- NÚT PHÓNG TO GÓC TRÊN PHẢI -->
      <button
        type="button"
        class="zoom-btn"
        title="Phóng to camera"
        @click.stop="emit('zoom', { student, stream })"
      >
        ⛶
      </button>

      <!-- BANNER CẢNH BÁO NỔI BẬT NẾU VỪA CÓ CẢNH BÁO -->
      <transition name="fade">
        <div v-if="latestAlertMessage" class="alert-banner">
          ⚠️ {{ latestAlertMessage }}
        </div>
      </transition>
    </div>

    <!-- THÔNG TIN SINH VIÊN VÀ BỘ ĐẾM VI PHẠM -->
    <div class="card-footer">
      <div class="student-info">
        <strong class="name" :title="student.hoTen">{{ student.hoTen }}</strong>
        <span class="mssv">{{ student.maSinhVien }}</span>
      </div>

      <div class="violation-badges">
        <!-- Rời tab (ngưỡng 3) -->
        <span
          class="badge tab-badge"
          :class="{ danger: student.soLanViPham >= 3, warning: student.soLanViPham > 0 }"
          title="Số lần rời tab (tối đa 3 lần)"
        >
          🗂 {{ student.soLanViPham || 0 }}/3
        </span>

        <!-- Camera khác (ngưỡng 5) -->
        <span
          class="badge cam-badge"
          :class="{ danger: student.soLanCamera >= 5, warning: student.soLanCamera > 0 }"
          title="Số lần vi phạm camera (không mặt / nhiều mặt / tắt cam - tối đa 5 lần)"
        >
          📷 {{ student.soLanCamera || 0 }}/5
        </span>

        <!-- Điện thoại (ngưỡng 3) -->
        <span
          class="badge phone-badge"
          :class="{ danger: student.soLanDienThoai >= 3, warning: student.soLanDienThoai > 0 }"
          title="Số lần phát hiện điện thoại (tối đa 3 lần)"
        >
          📱 {{ student.soLanDienThoai || 0 }}/3
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue'
import teacherReceiver from '../../../services/webrtc/teacherReceiver'

const props = defineProps({
  student: {
    type: Object,
    required: true,
  },
  phienId: {
    type: [Number, String],
    required: true,
  },
})

const emit = defineEmits(['zoom', 'select', 'alert-received', 'connection-state'])

const videoRef = ref(null)
const stream = ref(null)
const connectionStatus = ref('waiting') // 'waiting' | 'connecting' | 'connected' | 'disconnected' | 'failed' | 'camera_stopped'
const retrying = ref(false)
const latestAlertMessage = ref('')
const connectingCountdown = ref(10)
let connectingTimer = null
let countdownInterval = null
let alertClearTimer = null
let peerConnected = false
let trackListeners = []

const hasRecentWarning = computed(() => !!latestAlertMessage.value)

const streamBadgeText = computed(() => {
  if (props.student.daNopBai) return 'Đã nộp bài'
  if (props.student.biKhoa) return 'Đã khóa'
  if (connectionStatus.value === 'connected') return 'TRỰC TIẾP'
  if (connectionStatus.value === 'connecting') return 'Đang nối...'
  if (connectionStatus.value === 'camera_stopped') return 'Tắt cam'
  if (connectionStatus.value === 'disconnected' || connectionStatus.value === 'failed') return 'Mất kết nối'
  return 'Chờ camera'
})

const streamBadgeClass = computed(() => {
  if (props.student.daNopBai) return 'badge-submitted'
  if (props.student.biKhoa) return 'badge-locked'
  if (connectionStatus.value === 'connected') return 'badge-live'
  if (connectionStatus.value === 'connecting') return 'badge-connecting'
  if (connectionStatus.value === 'camera_stopped') return 'badge-off'
  if (connectionStatus.value === 'disconnected' || connectionStatus.value === 'failed') return 'badge-error'
  return 'badge-waiting'
})

function formatLockReason(reason) {
  if (reason === 'dien_thoai') return 'Vi phạm điện thoại 3 lần'
  if (reason === 'camera') return 'Vi phạm camera 5 lần'
  if (reason === 'roi_tab') return 'Rời tab 3 lần'
  return 'Vi phạm quy chế thi'
}

function clearConnectingTimeout() {
  if (connectingTimer) clearTimeout(connectingTimer)
  if (countdownInterval) clearInterval(countdownInterval)
  connectingTimer = null
  countdownInterval = null
}

function setConnectionStatus(status) {
  if (connectionStatus.value === status) return
  connectionStatus.value = status
  emit('connection-state', { sinhVienId: props.student.sinhVienId, status })
}

function updateVideoStatus(fallback = 'connecting') {
  const videoTracks = stream.value?.getVideoTracks?.() || []
  const hasLiveVideo = videoTracks.some((track) => track.readyState === 'live' && !track.muted)
  if (peerConnected && hasLiveVideo) {
    clearConnectingTimeout()
    setConnectionStatus('connected')
    return
  }
  if (fallback === 'disconnected' || fallback === 'failed' || fallback === 'camera_stopped') {
    setConnectionStatus(fallback)
    return
  }
  setConnectionStatus('connecting')
  startConnectingTimeout()
}

function clearRemoteStream() {
  trackListeners.forEach(({ track, event, handler }) => track.removeEventListener(event, handler))
  trackListeners = []
  stream.value = null
  if (videoRef.value) videoRef.value.srcObject = null
}

function observeRemoteStream(remoteStream) {
  clearRemoteStream()
  stream.value = remoteStream
  for (const track of remoteStream.getVideoTracks()) {
    for (const event of ['unmute', 'mute', 'ended']) {
      const handler = () => {
        if (event === 'ended') {
          peerConnected = false
          updateVideoStatus('disconnected')
        }
        else updateVideoStatus()
      }
      track.addEventListener(event, handler)
      trackListeners.push({ track, event, handler })
    }
  }
  updateVideoStatus()
}

function startConnectingTimeout() {
  if (connectingTimer !== null) return
  clearConnectingTimeout()
  connectingCountdown.value = 10

  countdownInterval = setInterval(() => {
    if (connectingCountdown.value > 1) {
      connectingCountdown.value--
    }
  }, 1000)

  connectingTimer = setTimeout(() => {
    clearConnectingTimeout()
    if (connectionStatus.value === 'connecting') {
      console.warn(`[StudentCamera] Hết thời gian chờ kết nối SV #${props.student.sinhVienId}`)
      peerConnected = false
      clearRemoteStream()
      setConnectionStatus('disconnected')
    }
  }, 10000)
}

function handleRetry() {
  if (retrying.value) return
  retrying.value = true
  peerConnected = false
  setConnectionStatus('connecting')
  startConnectingTimeout()

  teacherReceiver.retryStudent(props.student.sinhVienId)

  setTimeout(() => {
    retrying.value = false
  }, 2000)
}

function playVideoSafely(remoteStream) {
  nextTick(() => {
    if (videoRef.value) {
      videoRef.value.srcObject = remoteStream
      videoRef.value.onloadedmetadata = () => {
        videoRef.value.play().catch((err) => {
          console.warn('[StudentCamera] Tự động phát video:', err.message)
        })
      }
      videoRef.value.play().catch(() => {})
    }
  })
}

function startWatching() {
  if (!props.phienId || !props.student.sinhVienId) return

  teacherReceiver.watchStudent(props.student.sinhVienId, {
    onTrack: (remoteStream) => {
      observeRemoteStream(remoteStream)
      playVideoSafely(remoteStream)
    },

    onStateChange: (state) => {
      if (state === 'camera_stopped') {
        clearConnectingTimeout()
        peerConnected = false
        clearRemoteStream()
        setConnectionStatus('camera_stopped')
      } else if (state === 'camera_ready') {
        peerConnected = false
        setConnectionStatus('connecting')
        startConnectingTimeout()
      } else if (state === 'connected') {
        peerConnected = true
        updateVideoStatus()
      } else if (state === 'disconnected') {
        clearConnectingTimeout()
        peerConnected = false
        setConnectionStatus('disconnected')
      } else if (state === 'failed') {
        clearConnectingTimeout()
        peerConnected = false
        clearRemoteStream()
        setConnectionStatus('failed')
      } else if (state === 'connecting') {
        peerConnected = false
        setConnectionStatus('connecting')
        startConnectingTimeout()
      } else {
        setConnectionStatus(state)
      }
    },

    onAlert: (alertEvent) => {
      if (alertEvent.soLanCamera !== undefined) {
        props.student.soLanCamera = alertEvent.soLanCamera
      }
      if (alertEvent.soLanDienThoai !== undefined) {
        props.student.soLanDienThoai = alertEvent.soLanDienThoai
      }
      if (alertEvent.soLanViPham !== undefined) {
        props.student.soLanViPham = alertEvent.soLanViPham
      }
      if (alertEvent.biKhoa !== undefined) {
        props.student.biKhoa = alertEvent.biKhoa
      }
      if (alertEvent.lyDoKhoa !== undefined) {
        props.student.lyDoKhoa = alertEvent.lyDoKhoa
      }

      latestAlertMessage.value = alertEvent.noiDung || 'Phát hiện vi phạm mới!'
      clearTimeout(alertClearTimer)
      alertClearTimer = setTimeout(() => {
        latestAlertMessage.value = ''
      }, 5000)

      emit('alert-received', {
        ...alertEvent,
        sinhVienId: alertEvent.sinhVienId ?? props.student.sinhVienId,
        hoTen: alertEvent.hoTen || props.student.hoTen,
        maSinhVien: alertEvent.maSinhVien || props.student.maSinhVien,
      })
    },
  })
}

onMounted(() => {
  emit('connection-state', { sinhVienId: props.student.sinhVienId, status: connectionStatus.value })
  startWatching()
})

onBeforeUnmount(() => {
  clearConnectingTimeout()
  clearTimeout(alertClearTimer)
  teacherReceiver.stopWatching(props.student.sinhVienId)
  clearRemoteStream()
})
</script>

<style scoped>
.student-card {
  background: var(--bg-card, #ffffff);
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  position: relative;
}

.student-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.student-card.is-live {
  border-color: #10b981;
}

.student-card.has-warning {
  border-color: #ef4444;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2);
}

.student-card.is-locked {
  border-color: #dc2626;
  opacity: 0.9;
}

.student-card.is-submitted {
  border-color: #3b82f6;
  opacity: 0.85;
}

/* VIDEO CONTAINER 16:9 */
.video-container {
  width: 100%;
  aspect-ratio: 16 / 9;
  background: #0f172a;
  position: relative;
  cursor: pointer;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-container video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: none;
}

.video-container video.active {
  display: block;
}

/* PLACEHOLDER KHI KHÔNG CÓ VIDEO */
.video-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 12px;
  text-align: center;
}

.status-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  color: #94a3b8;
}

.status-box .icon {
  font-size: 24px;
}

.status-box strong {
  font-size: 13px;
  color: #f1f5f9;
}

.status-box small {
  font-size: 11px;
  color: #64748b;
}

.status-box.submitted strong {
  color: #60a5fa;
}

.status-box.locked strong {
  color: #f87171;
}

.status-box.error strong {
  color: #fbbf24;
}

.retry-btn {
  margin-top: 6px;
  padding: 4px 10px;
  font-size: 11px;
  background: #334155;
  color: #f8fafc;
  border: 1px solid #475569;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.15s;
}

.retry-btn:hover:not(:disabled) {
  background: #475569;
}

.mini-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid #334155;
  border-top-color: #38bdf8;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* STREAM BADGE */
.stream-badge {
  position: absolute;
  top: 8px;
  left: 8px;
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  gap: 5px;
  backdrop-filter: blur(4px);
  z-index: 2;
}

.stream-badge .dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.badge-live {
  background: rgba(16, 185, 129, 0.9);
  color: #ffffff;
}

.badge-live .dot {
  background: #ffffff;
  animation: pulse-dot 1.2s infinite;
}

.badge-connecting {
  background: rgba(14, 165, 233, 0.85);
  color: #ffffff;
}

.badge-connecting .dot {
  background: #ffffff;
}

.badge-off {
  background: rgba(100, 116, 139, 0.85);
  color: #ffffff;
}

.badge-off .dot {
  background: #cbd5e1;
}

.badge-error {
  background: rgba(239, 68, 68, 0.85);
  color: #ffffff;
}

.badge-error .dot {
  background: #ffffff;
}

.badge-waiting {
  background: rgba(71, 85, 105, 0.85);
  color: #cbd5e1;
}

.badge-waiting .dot {
  background: #94a3b8;
}

.badge-submitted {
  background: rgba(59, 130, 246, 0.85);
  color: #ffffff;
}

.badge-locked {
  background: rgba(220, 38, 38, 0.9);
  color: #ffffff;
}

@keyframes pulse-dot {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.4;
    transform: scale(0.8);
  }
}

/* NÚT PHÓNG TO */
.zoom-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  background: rgba(15, 23, 42, 0.7);
  color: #f8fafc;
  border: none;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  cursor: pointer;
  opacity: 0.7;
  transition: opacity 0.15s, background 0.15s;
  z-index: 2;
}

.video-container:hover .zoom-btn {
  opacity: 1;
  background: rgba(15, 23, 42, 0.9);
}

/* ALERT BANNER TẠM THỜI */
.alert-banner {
  position: absolute;
  bottom: 0;
  inset-inline: 0;
  background: rgba(239, 68, 68, 0.95);
  color: #ffffff;
  padding: 6px 10px;
  font-size: 11px;
  font-weight: 600;
  text-align: center;
  backdrop-filter: blur(2px);
  z-index: 3;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* CARD FOOTER */
.card-footer {
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: var(--bg-card, #ffffff);
}

.student-info {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.student-info .name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary, #0f172a);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.student-info .mssv {
  font-size: 11px;
  color: var(--text-secondary, #64748b);
  font-family: monospace;
  font-weight: 600;
}

.violation-badges {
  display: flex;
  align-items: center;
  gap: 6px;
}

.badge {
  font-size: 11px;
  padding: 2px 6px;
  border-radius: 4px;
  background: var(--bg-badge, #f1f5f9);
  color: var(--text-secondary, #475569);
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 2px;
  border: 1px solid var(--border-badge, #e2e8f0);
}

.badge.warning {
  background: #fef3c7;
  color: #b45309;
  border-color: #fde68a;
}

.badge.danger {
  background: #fee2e2;
  color: #b91c1c;
  border-color: #fca5a5;
  animation: pulse-danger 1s infinite alternate;
}

@keyframes pulse-danger {
  from {
    transform: scale(1);
  }
  to {
    transform: scale(1.04);
  }
}
</style>
