<template>
  <div class="exam-monitor-page">
    <!-- HEADER -->
    <header class="monitor-header">
      <div class="header-left">
        <button type="button" class="back-btn" @click="goBack" title="Quay về danh sách phiên">
          ←
        </button>
        <div>
          <span class="badge-running">● ĐANG GIÁM SÁT TRỰC TIẾP</span>
          <h1>{{ phienInfo?.tenPhien || 'Giám sát phòng thi' }}</h1>
          <p class="subtitle">
            <span class="highlight">{{ phienInfo?.tenLop }}</span> · Bài thi:
            <strong>{{ phienInfo?.tenBaiKiemTra || 'Chưa liên kết' }}</strong>
          </p>
        </div>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn-secondary"
          :disabled="loading"
          @click="fetchSessionDetail"
        >
          <span :class="{ spinning: loading }">↻</span>
          Làm mới
        </button>

        <button
          v-if="phienInfo?.trangThai === 'dang_dien_ra'"
          type="button"
          class="btn-danger"
          @click="confirmEndSession"
        >
          ⏹ Kết thúc phiên
        </button>
      </div>
    </header>

    <!-- LOADING & ERROR STATES -->
    <div v-if="loading && !phienInfo" class="state-card">
      <div class="spinner"></div>
      <h3>Đang tải dữ liệu phiên giám sát...</h3>
      <p>Hệ thống đang kết nối kênh WebRTC và tải danh sách sinh viên.</p>
    </div>

    <div v-else-if="error" class="notice error-notice">
      <span>{{ error }}</span>
      <button type="button" class="btn-retry" @click="fetchSessionDetail">Thử lại</button>
    </div>

    <!-- MAIN CONTENT KHI ĐÃ CÓ DATA -->
    <template v-else>
      <!-- THỐNG KÊ REALTIME GRID -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue">👥</div>
          <div class="stat-info">
            <span>Tổng số sinh viên</span>
            <strong>{{ students.length }}</strong>
            <small>Thành viên lớp thi</small>
          </div>
        </div>

        <div v-if="cameraRequired" class="stat-card">
          <div class="stat-icon green">🎥</div>
          <div class="stat-info">
            <span>Đang truyền webcam</span>
            <strong>{{ liveCameraCount }}</strong>
            <small>Kết nối P2P và track video đang hoạt động</small>
          </div>
        </div>

        <div v-if="cameraRequired" class="stat-card">
          <div class="stat-icon blue">↻</div>
          <div class="stat-info">
            <span>Đang kết nối</span>
            <strong>{{ connectingCount }}</strong>
            <small>Đang thương lượng WebRTC</small>
          </div>
        </div>

        <div v-if="cameraRequired" class="stat-card">
          <div class="stat-icon orange">⚠️</div>
          <div class="stat-info">
            <span>Chưa bật / Mất kết nối</span>
            <strong>{{ offlineCount }}</strong>
            <small>Cần kiểm tra lại</small>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon purple">✅</div>
          <div class="stat-info">
            <span>Đã nộp bài</span>
            <strong>{{ submittedCount }}</strong>
            <small>Hoàn thành bài thi</small>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon red">🚨</div>
          <div class="stat-info">
            <span>Cảnh báo vi phạm</span>
            <strong>{{ totalAlertCount }}</strong>
            <small>{{ lockedCount }} bài thi bị khóa</small>
          </div>
        </div>
      </div>

      <div class="exam-mode-banner" :class="{ lab: !cameraRequired }">
        <strong>{{ cameraRequired ? 'Thi trực tuyến — Camera AI bắt buộc' : 'Thi tại phòng máy — Không yêu cầu camera' }}</strong>
        <span>{{ cameraRequired ? 'Kết nối webcam được báo cáo theo trạng thái WebRTC và video track.' : 'Không khởi tạo WebRTC video và không tính thiếu camera là mất kết nối. Trạng thái lượt làm và cảnh báo bảo vệ vẫn được cập nhật.' }}</span>
      </div>

      <!-- MAIN LAYOUT: LƯỚI CAMERA VÀ SIDEBAR CẢNH BÁO -->
      <div class="monitor-content">
        <!-- CỘT TRÁI: THANH CÔNG CỤ & LƯỚI CAMERA -->
        <div class="camera-section">
          <!-- THANH CÔNG CỤ TÌM KIẾM & BỘ LỌC -->
          <div class="toolbar">
            <div class="search-box">
              <span class="search-icon">⌕</span>
              <input
                v-model="keyword"
                type="search"
                placeholder="Tìm theo họ tên hoặc MSSV..."
                aria-label="Tìm kiếm sinh viên"
              />
            </div>

            <div class="filter-group">
              <select v-if="cameraRequired" v-model="filterStatus" class="filter-select">
                <option value="all">Tất cả trạng thái camera</option>
                <option value="live">Đang truyền trực tiếp</option>
                <option value="connecting">Đang kết nối</option>
                <option value="offline">Chưa bật / Mất kết nối</option>
                <option value="submitted">Đã nộp bài</option>
                <option value="locked">Bị đình chỉ / Khóa</option>
              </select>

              <select v-model="filterViolation" class="filter-select">
                <option value="all">Tất cả sinh viên</option>
                <option value="has_violation">Có vi phạm (> 0)</option>
                <option value="clean">Không vi phạm</option>
              </select>
            </div>
          </div>

          <!-- LƯỚI CAMERA SINH VIÊN -->
          <div v-if="cameraRequired" class="camera-grid">
            <StudentCamera
              v-for="st in students"
              :key="st.sinhVienId"
              v-show="filteredStudents.includes(st)"
              :student="st"
              :phien-id="phienId"
              @zoom="openZoomModal"
              @alert-received="handleAlertReceived"
              @connection-state="handleConnectionState"
            />
          </div>

          <div v-else class="lab-student-grid">
            <article v-for="st in filteredStudents" :key="st.sinhVienId" class="lab-student-card">
              <div><strong>{{ st.hoTen }}</strong><span>{{ st.maSinhVien }}</span></div>
              <span class="lab-mode-status">Không yêu cầu camera</span>
              <div class="lab-student-state">
                <span>{{ st.daNopBai ? 'Đã nộp bài' : (st.attemptStarted ? 'Đang làm bài' : 'Chưa bắt đầu') }}</span>
                <span v-if="st.biKhoa" class="locked-state">Bài bị khóa</span>
                <span v-if="(st.soLanViPham || 0) > 0" class="violation-state">Rời tab {{ st.soLanViPham }}/3</span>
              </div>
            </article>
          </div>

          <div v-if="filteredStudents.length === 0" class="empty-state">
            <span class="icon">🔍</span>
            <h4>Không tìm thấy sinh viên phù hợp</h4>
            <p>Vui lòng thay đổi từ khóa tìm kiếm hoặc bỏ bớt bộ lọc.</p>
          </div>
        </div>

        <!-- CỘT PHẢI: STREAM CẢNH BÁO REALTIME -->
        <aside class="alert-feed-panel">
          <div class="panel-header">
            <h3>🔔 Cảnh báo mới nhất</h3>
            <span class="badge-count">{{ recentAlerts.length }}</span>
          </div>

          <div class="alert-list">
            <div
              v-for="(alert, idx) in recentAlerts"
              :key="alert.id || idx"
              class="alert-item"
              :class="alertTypeClass(alert.loai)"
            >
              <div class="alert-top">
                <strong class="student-name">{{ alert.hoTen }}</strong>
                <span class="student-mssv">{{ alert.maSinhVien }}</span>
              </div>
              <strong v-if="isScreenshotAlert(alert)" class="screenshot-alert-title">Nghi vấn chụp màn hình</strong>
              <p class="alert-desc">{{ alert.noiDung }}</p>
              <div v-if="isScreenshotAlert(alert)" class="screenshot-alert-details">
                <span>Tín hiệu: {{ screenshotSignalLabel(alert) }}</span>
                <span>Chỉ là nghi vấn; không xác nhận ảnh đã được chụp.</span>
                <span class="review-state" :class="{ reviewed: alert.daXem }">{{ alert.daXem ? 'Đã xem' : 'Cần xem xét' }}</span>
                <button v-if="!alert.daXem" type="button" class="mark-reviewed-btn"
                  :disabled="reviewingEventIds.includes(alert.eventId)" @click="markAlertViewed(alert)">
                  {{ reviewingEventIds.includes(alert.eventId) ? 'Đang cập nhật…' : 'Đánh dấu đã xem' }}
                </button>
                <small v-if="reviewErrors[alert.eventId]" class="review-error">{{ reviewErrors[alert.eventId] }}</small>
              </div>
              <time class="alert-time">{{ formatTime(alert.thoiGian) }}</time>
            </div>

            <div v-if="recentAlerts.length === 0" class="alert-empty">
              <span>✓</span>
              <p>Chưa có cảnh báo nào trong phiên thi.</p>
            </div>
          </div>
        </aside>
      </div>
    </template>

    <!-- MODAL PHÓNG TO CAMERA -->
    <div v-if="zoomedStudent" class="modal-backdrop" @click="closeZoomModal">
      <div class="modal-card" @click.stop>
        <div class="modal-header">
          <div>
            <h3>📷 {{ zoomedStudent.student.hoTen }}</h3>
            <small class="mssv-tag">{{ zoomedStudent.student.maSinhVien }} · {{ zoomedStudent.student.email }}</small>
          </div>
          <button type="button" class="close-btn" @click="closeZoomModal">✕</button>
        </div>

        <div class="modal-video-box">
          <video ref="modalVideoRef" autoplay playsinline muted></video>
          <div v-if="!zoomedStudent.stream" class="modal-placeholder">
            <span>📷</span>
            <p>Không có tín hiệu webcam trực tiếp</p>
          </div>
        </div>

        <div class="modal-footer">
          <div class="violation-stats">
            <span class="stat-pill">🗂 Rời tab: <strong>{{ zoomedStudent.student.soLanViPham || 0 }}/3</strong></span>
            <span class="stat-pill">📷 Camera: <strong>{{ zoomedStudent.student.soLanCamera || 0 }}/5</strong></span>
            <span class="stat-pill">📱 Điện thoại: <strong>{{ zoomedStudent.student.soLanDienThoai || 0 }}/3</strong></span>
            <span v-if="zoomedStudent.student.biKhoa" class="stat-pill danger">🔒 Bị khóa</span>
            <span v-if="zoomedStudent.student.daNopBai" class="stat-pill success">✅ Đã nộp</span>
          </div>
          <button type="button" class="btn-secondary" @click="closeZoomModal">Đóng</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../../services/api'
import teacherReceiver from '../../../services/webrtc/teacherReceiver'
import StudentCamera from './StudentCamera.vue'

const route = useRoute()
const router = useRouter()

const phienId = computed(() => route.query.phienId || route.params.phienId)

const loading = ref(false)
const error = ref('')
const phienInfo = ref(null)
const cameraRequired = computed(() => phienInfo.value?.yeuCauCamera !== false)
const students = ref([])
const recentAlerts = ref([])
const reviewingEventIds = ref([])
const reviewErrors = ref({})
let sessionRefreshTimer = null

const keyword = ref('')
const filterStatus = ref('all')
const filterViolation = ref('all')

const zoomedStudent = ref(null)
const modalVideoRef = ref(null)

// Thống kê computed
const liveCameraCount = computed(() => {
  return students.value.filter((s) =>
    !s.daNopBai && !s.biKhoa && s.webcamStatus === 'connected'
  ).length
})

const connectingCount = computed(() => {
  return students.value.filter((s) =>
    !s.daNopBai && !s.biKhoa && s.webcamStatus === 'connecting'
  ).length
})

const offlineCount = computed(() => {
  return students.value.filter((s) =>
    !s.daNopBai && !s.biKhoa &&
    ['waiting', 'camera_stopped', 'disconnected', 'failed'].includes(s.webcamStatus)
  ).length
})

const submittedCount = computed(() => {
  return students.value.filter((s) => s.daNopBai).length
})

const lockedCount = computed(() => {
  return students.value.filter((s) => s.biKhoa).length
})

const totalAlertCount = computed(() => {
  const monitoringCounts = students.value.reduce((acc, s) => {
    return acc + (s.soLanViPham || 0) + (cameraRequired.value ? ((s.soLanCamera || 0) + (s.soLanDienThoai || 0)) : 0)
  }, 0)
  const screenshotEvents = recentAlerts.value.filter(
    alert => alert.loai === 'SCREENSHOT_SHORTCUT_DETECTED'
  ).length
  return monitoringCounts + screenshotEvents
})

// Bộ lọc sinh viên
const filteredStudents = computed(() => {
  const kw = keyword.value.trim().toLowerCase()

  return students.value.filter((s) => {
    // 1. Lọc theo từ khóa
    if (kw) {
      const matchName = (s.hoTen || '').toLowerCase().includes(kw)
      const matchMssv = (s.maSinhVien || '').toLowerCase().includes(kw)
      if (!matchName && !matchMssv) return false
    }

    // 2. Lọc theo trạng thái
    if (cameraRequired.value && filterStatus.value === 'submitted' && !s.daNopBai) return false
    if (cameraRequired.value && filterStatus.value === 'locked' && !s.biKhoa) return false
    if (cameraRequired.value && filterStatus.value === 'live' && (s.daNopBai || s.biKhoa || s.webcamStatus !== 'connected')) return false
    if (cameraRequired.value && filterStatus.value === 'connecting' && s.webcamStatus !== 'connecting') return false
    if (cameraRequired.value && filterStatus.value === 'offline' && (
      s.daNopBai || s.biKhoa ||
      !['waiting', 'camera_stopped', 'disconnected', 'failed'].includes(s.webcamStatus)
    )) return false

    // 3. Lọc theo vi phạm
    const hasVio = (s.soLanViPham || 0) > 0 || (s.soLanCamera || 0) > 0 || (s.soLanDienThoai || 0) > 0
    if (filterViolation.value === 'has_violation' && !hasVio) return false
    if (filterViolation.value === 'clean' && hasVio) return false

    return true
  })
})

function formatTime(isoString) {
  if (!isoString) return ''
  try {
    const d = new Date(isoString)
    return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  } catch {
    return isoString
  }
}

function alertTypeClass(loai) {
  if (['copy_blocked', 'cut_blocked', 'select_all_blocked', 'context_menu_blocked', 'drag_blocked', 'screenshot_shortcut_key', 'SCREENSHOT_SHORTCUT_DETECTED', 'focus_lost', 'fullscreen_exit'].includes(loai)) return 'alert-protection'
  if (loai === 'phat_hien_dien_thoai') return 'alert-phone'
  if (loai === 'roi_tab') return 'alert-tab'
  if (loai === 'camera_ngat') return 'alert-cam'
  return 'alert-face'
}

function isScreenshotAlert(alert) {
  return alert?.loai === 'SCREENSHOT_SHORTCUT_DETECTED'
}

function screenshotSignalLabel(alert) {
  if (alert?.signalLabel) return alert.signalLabel
  const labels = {
    PRINT_SCREEN: 'phím Print Screen',
    META_SHIFT_S: 'tổ hợp Meta + Shift + S',
    META_SHIFT_3: 'tổ hợp Meta + Shift + 3',
    META_SHIFT_4: 'tổ hợp Meta + Shift + 4',
    META_SHIFT_5: 'tổ hợp Meta + Shift + 5',
  }
  return labels[alert?.signal?.signal] || labels[alert?.signal] || 'phím tắt nghi vấn'
}

async function markAlertViewed(alert) {
  if (!isScreenshotAlert(alert) || !alert.eventId || alert.daXem || reviewingEventIds.value.includes(alert.eventId)) return
  reviewingEventIds.value = [...reviewingEventIds.value, alert.eventId]
  delete reviewErrors.value[alert.eventId]
  try {
    const response = await api.put(`/giang-vien/su-kien-bao-ve/${encodeURIComponent(alert.eventId)}/da-xem`)
    alert.daXem = response.data?.data?.daXem === true
    alert.daXemLuc = response.data?.data?.daXemLuc || new Date().toISOString()
  } catch (err) {
    reviewErrors.value[alert.eventId] = err.response?.data?.message || err.message || 'Không thể cập nhật trạng thái đã xem.'
  } finally {
    reviewingEventIds.value = reviewingEventIds.value.filter(eventId => eventId !== alert.eventId)
  }
}

function goBack() {
  router.push('/giang-vien/phien-giam-sat')
}

async function fetchSessionDetail({ silent = false } = {}) {
  if (!phienId.value) {
    error.value = 'Thiếu ID phiên giám sát trên URL.'
    return
  }
  if (loading.value) return

  if (!silent) {
    loading.value = true
    error.value = ''
  }

  try {
    const res = await api.get(`/giang-vien/phien-giam-sat/${phienId.value}/chi-tiet`)

    if (res.data?.status === false) {
      throw new Error(res.data?.message || 'Không thể tải chi tiết phiên.')
    }

    const data = res.data?.data || res.data
    error.value = ''
    phienInfo.value = data.phien
    const previousStatuses = new Map(
      students.value.map((student) => [Number(student.sinhVienId), student.webcamStatus])
    )
    students.value = (data.students || []).map((student) => ({
      ...student,
      webcamStatus: student.trangThaiCamera === 'not_required'
        ? 'not_required'
        : (previousStatuses.get(Number(student.sinhVienId)) || 'waiting'),
    }))
    recentAlerts.value = data.recentAlerts || []

    // Khởi tạo receiver với phienId
    teacherReceiver.setPhienId(phienId.value)
  } catch (err) {
    if (silent) {
      if (import.meta.env.DEV) console.warn('[Giám sát thi] Làm mới trạng thái phiên thất bại:', err.message)
    } else {
      console.error('Lỗi lấy chi tiết phiên:', err)
      error.value = err.response?.data?.message || err.message || 'Không thể tải phiên giám sát.'
    }
  } finally {
    if (!silent) loading.value = false
  }
}

function handleConnectionState({ sinhVienId, status }) {
  const student = students.value.find((item) => Number(item.sinhVienId) === Number(sinhVienId))
  if (student) student.webcamStatus = status
}

function handleAlertReceived(alertData) {
  const sessionId = Number(phienId.value)
  const eventSessionId = Number(alertData.phienId)
  const studentId = Number(alertData.sinhVienId)
  const student = students.value.find(item => Number(item.sinhVienId) === studentId)
  const isScreenshot = alertData.loaiSuKien === 'SCREENSHOT_SHORTCUT_DETECTED'

  if (!student || (Number.isFinite(eventSessionId) && eventSessionId !== sessionId)) return
  if (isScreenshot && eventSessionId !== sessionId) return
  if (isScreenshot && !alertData.eventId) return

  const eventId = alertData.eventId || `live_${Date.now()}`
  if (recentAlerts.value.some(alert => alert.id === eventId || alert.eventId === eventId)) return

  // Thêm vào danh sách alert mới nhất ở đầu
  recentAlerts.value.unshift({
    id: eventId,
    eventId,
    sinhVienId: student.sinhVienId,
    hoTen: alertData.hoTen || student.hoTen,
    maSinhVien: alertData.maSinhVien || student.maSinhVien,
    loai: alertData.loaiCanhBao || alertData.loaiSuKien,
    noiDung: alertData.noiDung,
    signal: alertData.signal ? { signal: alertData.signal, source: alertData.signalSource } : null,
    signalLabel: screenshotSignalLabel({ signal: alertData.signal }),
    daXem: alertData.daXem === true,
    thoiGian: alertData.thoiGian || new Date().toISOString(),
  })

  // Giới hạn 40 cảnh báo
  if (recentAlerts.value.length > 40) {
    recentAlerts.value.pop()
  }
}

function openZoomModal({ student, stream }) {
  zoomedStudent.value = { student, stream }
  nextTick(() => {
    if (modalVideoRef.value && stream) {
      modalVideoRef.value.srcObject = stream
      modalVideoRef.value.play().catch(() => {})
    }
  })
}

function closeZoomModal() {
  if (modalVideoRef.value) {
    modalVideoRef.value.srcObject = null
  }
  zoomedStudent.value = null
}

async function confirmEndSession() {
  if (!confirm('Bạn có chắc chắn muốn kết thúc phiên giám sát thi này không?')) return

  try {
    const res = await api.put(`/giang-vien/phien-giam-sat/${phienId.value}/ket-thuc`)
    if (res.data?.status === false) {
      alert(res.data?.message || 'Không thể kết thúc phiên.')
      return
    }

    alert('Đã kết thúc phiên giám sát thành công.')
    router.push('/giang-vien/phien-giam-sat')
  } catch (err) {
    alert(err.response?.data?.message || 'Lỗi khi kết thúc phiên thi.')
  }
}

onMounted(() => {
  fetchSessionDetail()
  // StudentCamera tiles subscribe for every class member; this refresh also
  // updates attempt/submission state for students who join after the page opens.
  sessionRefreshTimer = window.setInterval(() => {
    void fetchSessionDetail({ silent: true })
  }, 15000)
})

onBeforeUnmount(() => {
  if (sessionRefreshTimer !== null) window.clearInterval(sessionRefreshTimer)
  teacherReceiver.stopAll()
})
</script>

<style scoped>
.exam-monitor-page {
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* HEADER */
.monitor-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  border-bottom: 1px solid var(--border-color, #e2e8f0);
  padding-bottom: 16px;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.back-btn {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--bg-card, #ffffff);
  border: 1px solid var(--border-color, #cbd5e1);
  font-size: 18px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s, border-color 0.15s;
}

.back-btn:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.badge-running {
  font-size: 11px;
  font-weight: 700;
  color: #10b981;
  letter-spacing: 0.5px;
}

.monitor-header h1 {
  font-size: 22px;
  font-weight: 800;
  color: var(--text-primary, #0f172a);
  margin: 2px 0;
}

.subtitle {
  font-size: 13px;
  color: var(--text-secondary, #64748b);
  margin: 0;
}

.subtitle .highlight {
  color: #2563eb;
  font-weight: 600;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-secondary {
  padding: 8px 14px;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-danger {
  padding: 8px 14px;
  border-radius: 8px;
  background: #ef4444;
  border: 1px solid #dc2626;
  font-size: 13px;
  font-weight: 600;
  color: #ffffff;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}

.btn-danger:hover {
  background: #dc2626;
}

.spinning {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

/* THỐNG KÊ GRID */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 14px;
}

.stat-card {
  background: var(--bg-card, #ffffff);
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 12px;
  padding: 14px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.stat-icon.blue { background: #eff6ff; color: #2563eb; }
.stat-icon.green { background: #ecfdf5; color: #059669; }
.stat-icon.orange { background: #fffbeb; color: #d97706; }
.stat-icon.purple { background: #faf5ff; color: #9333ea; }
.stat-icon.red { background: #fef2f2; color: #dc2626; }

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-info span {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary, #64748b);
  text-transform: uppercase;
}

.stat-info strong {
  font-size: 20px;
  font-weight: 800;
  color: var(--text-primary, #0f172a);
}

.stat-info small {
  font-size: 11px;
  color: var(--text-secondary, #94a3b8);
}

/* MONITOR CONTENT LAYOUT */
.monitor-content {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 20px;
}

@media (max-width: 1024px) {
  .monitor-content {
    grid-template-columns: 1fr;
  }
}

/* CAMERA SECTION */
.camera-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 240px;
}

.search-box .search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  font-size: 14px;
}

.search-box input {
  width: 100%;
  padding: 8px 12px 8px 32px;
  border-radius: 8px;
  border: 1px solid var(--border-color, #cbd5e1);
  background: var(--bg-card, #ffffff);
  font-size: 13px;
  outline: none;
}

.search-box input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-select {
  padding: 8px 12px;
  border-radius: 8px;
  border: 1px solid var(--border-color, #cbd5e1);
  background: var(--bg-card, #ffffff);
  font-size: 13px;
  color: var(--text-primary, #0f172a);
  outline: none;
}

/* CAMERA GRID RESPONSIVE */
.camera-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

.exam-mode-banner{display:grid;gap:4px;margin:0 0 18px;padding:14px 18px;border:1px solid #bfdbfe;border-radius:12px;background:#eff6ff;color:#1e3a8a}.exam-mode-banner span{font-size:13px}.exam-mode-banner.lab{border-color:#a7f3d0;background:#ecfdf5;color:#065f46}.lab-student-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}.lab-student-card{display:grid;gap:12px;padding:16px;border:1px solid var(--border-color,#cbd5e1);border-radius:12px;background:var(--bg-card,#fff)}.lab-student-card>div:first-child{display:grid;gap:3px}.lab-student-card>div:first-child span{font-size:12px;color:#64748b}.lab-mode-status{width:max-content;padding:5px 9px;border-radius:999px;background:#ecfdf5;color:#047857;font-size:12px;font-weight:700}.lab-student-state{display:flex;flex-wrap:wrap;gap:8px;font-size:12px;color:#475569}.lab-student-state .locked-state{color:#b91c1c}.lab-student-state .violation-state{color:#b45309}

.empty-state {
  padding: 48px 24px;
  text-align: center;
  background: var(--bg-card, #ffffff);
  border: 1px dashed var(--border-color, #cbd5e1);
  border-radius: 12px;
  color: #64748b;
}

.empty-state .icon {
  font-size: 32px;
}

.empty-state h4 {
  font-size: 16px;
  font-weight: 700;
  margin: 8px 0 4px;
  color: #1e293b;
}

/* ALERT FEED PANEL */
.alert-feed-panel {
  background: var(--bg-card, #ffffff);
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: calc(100vh - 220px);
  position: sticky;
  top: 24px;
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 10px;
}

.panel-header h3 {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.badge-count {
  background: #fee2e2;
  color: #ef4444;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 12px;
}

.alert-list {
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding-right: 4px;
}

.alert-item {
  padding: 10px 12px;
  border-radius: 8px;
  border-left: 3px solid;
  background: #f8fafc;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.alert-item.alert-phone {
  border-color: #ef4444;
  background: #fef2f2;
}

.alert-item.alert-tab {
  border-color: #f59e0b;
  background: #fffbeb;
}

.alert-item.alert-cam {
  border-color: #6366f1;
  background: #eef2ff;
}

.alert-item.alert-face {
  border-color: #ec4899;
  background: #fdf2f8;
}

.alert-item.alert-protection {
  border-color: #0ea5e9;
  background: #f0f9ff;
}

.screenshot-alert-details {
  display: grid;
  gap: 5px;
  padding-top: 4px;
  color: #475569;
  font-size: 10px;
  line-height: 1.5;
}

.screenshot-alert-title {
  color: #075985;
  font-size: 11px;
}

.review-state {
  color: #9a3412;
  font-weight: 700;
}

.review-state.reviewed {
  color: #166534;
}

.mark-reviewed-btn {
  justify-self: start;
  padding: 5px 8px;
  border: 1px solid #7dd3fc;
  border-radius: 6px;
  background: #e0f2fe;
  color: #075985;
  font-size: 10px;
  font-weight: 700;
  cursor: pointer;
}

.mark-reviewed-btn:disabled {
  opacity: .6;
  cursor: wait;
}

.review-error {
  color: #b91c1c;
}

.alert-top {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 6px;
}

.student-name {
  font-size: 12px;
  color: #0f172a;
}

.student-mssv {
  font-size: 10px;
  color: #64748b;
  font-family: monospace;
}

.alert-desc {
  font-size: 11px;
  font-weight: 600;
  color: #334155;
  margin: 0;
}

.alert-time {
  font-size: 10px;
  color: #94a3b8;
  align-self: flex-end;
}

.alert-empty {
  text-align: center;
  padding: 32px 12px;
  color: #94a3b8;
  font-size: 12px;
}

.alert-empty span {
  font-size: 24px;
  display: block;
  margin-bottom: 6px;
  color: #10b981;
}

/* MODAL PHÓNG TO */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.modal-card {
  background: #ffffff;
  border-radius: 16px;
  width: 100%;
  max-width: 860px;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
}

.modal-header h3 {
  font-size: 16px;
  font-weight: 700;
  margin: 0;
}

.mssv-tag {
  font-size: 12px;
  color: #64748b;
}

.close-btn {
  background: none;
  border: none;
  font-size: 18px;
  color: #64748b;
  cursor: pointer;
  padding: 4px;
}

.modal-video-box {
  width: 100%;
  aspect-ratio: 16 / 9;
  background: #090d16;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.modal-video-box video {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.modal-placeholder {
  color: #94a3b8;
  text-align: center;
}

.modal-placeholder span {
  font-size: 40px;
}

.modal-footer {
  padding: 14px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-top: 1px solid #f1f5f9;
}

.violation-stats {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.stat-pill {
  font-size: 12px;
  padding: 4px 10px;
  border-radius: 6px;
  background: #f1f5f9;
  color: #334155;
}

.stat-pill strong {
  color: #0f172a;
}

.stat-pill.danger {
  background: #fee2e2;
  color: #dc2626;
  font-weight: 700;
}

.stat-pill.success {
  background: #ecfdf5;
  color: #059669;
  font-weight: 700;
}

/* GENERAL UTILS */
.state-card {
  padding: 48px;
  text-align: center;
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  margin: 0 auto 16px;
  animation: spin 0.8s linear infinite;
}

.error-notice {
  background: #fee2e2;
  border: 1px solid #fca5a5;
  color: #b91c1c;
  padding: 12px 16px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.btn-retry {
  background: #dc2626;
  color: #ffffff;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 12px;
}
</style>
