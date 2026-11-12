<template>
  <div ref="examRootRef" class="exam-page" :class="{ 'exam-protection-active': protectionActive }">
    <!-- HEADER -->
    <header class="exam-header">
      <div>
        <span class="eyebrow">
          SMART CLASSROOM MONITOR · BÀI KIỂM TRA
        </span>

        <h1>{{ exam?.tieuDe || "Làm bài kiểm tra" }}</h1>

        <p>
          {{ exam?.moTa || "Hoàn thành bài trước thời hạn quy định." }}
        </p>
      </div>

      <div class="header-actions">
        <span class="save-pill">{{ saveStatus }}</span>

        <strong class="timer" :class="{ danger: remainingSeconds <= 300 }">
          ⏱ {{ formattedTime }}
        </strong>
      </div>
    </header>

    <!-- LỖI -->
    <div v-if="error" class="notice error-notice" role="alert">
      <span>{{ error }}</span>

      <button v-if="!locked" type="button" @click="reloadExam">
        Thử lại
      </button>
    </div>

    <div v-if="protectionNotice && exam && !loading" class="exam-protection-notice"
      :class="{ 'notice-error': protectionNoticeError }" role="status">
      {{ protectionNotice }}
    </div>

    <!-- LOADING -->
    <div v-if="loading" class="state-card">
      <div class="spinner"></div>
      <h3>Đang tải bài kiểm tra...</h3>
      <p>Hệ thống đang kiểm tra quyền truy cập và trạng thái giám sát.</p>
    </div>

    <!-- KHÓA BÀI -->
    <div v-else-if="locked" class="locked-card">
      <div class="locked-icon">🔒</div>

      <h2>Bài kiểm tra đã bị khóa</h2>

      <p v-if="lockReason === 'dien_thoai'">
        AI đã ghi nhận
        <strong>{{ phoneWarningCount }}/3 lần phát hiện điện thoại</strong>.
        Bài kiểm tra đã bị đình chỉ.
      </p>
      <p v-else-if="lockReason === 'camera'">
        Hệ thống đã ghi nhận
        <strong>{{ cameraWarningCount }}/5 cảnh báo camera</strong>.
      </p>
      <p v-else>
        Hệ thống đã ghi nhận
        <strong>{{ warningCount }}/3 lần rời tab</strong>.
      </p>

      <p>
        Bạn không thể tiếp tục trả lời, lưu nháp
        hoặc nộp bài kiểm tra này.
      </p>

      <div class="locked-details">
        <span>Trạng thái</span>
        <strong>Đình chỉ bài kiểm tra</strong>
      </div>

      <button v-if="canSubmit" type="button" class="btn primary" @click="openSubmitConfirmation">Nộp bài kiểm tra</button>
      <button type="button" class="btn primary" @click="goBack">
        Quay về danh sách bài tập
      </button>
    </div>

    <div v-else-if="examPhase === 'SUBMITTED'" class="state-card submitted-card" role="status">
      <div class="state-icon">✓</div>
      <h2>Bài kiểm tra đã được nộp</h2>
      <p>Hệ thống đã xác nhận bài nộp. Bạn không thể tiếp tục chỉnh sửa hoặc phát sinh cảnh báo giám sát cho bài thi này.</p>
      <button type="button" class="btn primary" @click="goBack">Quay về danh sách bài tập</button>
    </div>

    <!-- NỘI DUNG BÀI THI -->
    <div v-else-if="exam" class="exam-wrap">
      <div class="exam-workspace" :class="{ 'exam-workspace-preparing': !fullscreenActive }">
        <section class="exam-main">
          <section v-if="!fullscreenActive" class="fullscreen-required" :class="{ 'fullscreen-return-prompt': officialExamActive }" role="alert">
            <h2>{{ officialExamActive ? 'Quay lại toàn màn hình để tiếp tục' : 'Vào toàn màn hình để bắt đầu làm bài' }}</h2>
            <p v-if="officialExamActive">Câu trả lời và thời gian được giữ nguyên. Bật lại toàn màn hình để tiếp tục thao tác.</p>
            <p v-else>Đây là bước chuẩn bị. Hệ thống chỉ ghi nhận rời tab sau khi bài thi bắt đầu.</p>
            <span v-if="officialExamActive" class="camera-presence-pill" :class="cameraReady ? 'camera-online' : 'camera-offline'">
              Camera {{ cameraReady ? 'đang hoạt động; giám sát vẫn tiếp tục' : 'đang tắt' }}
            </span>
            <button type="button" class="btn primary" @click="requestExamFullscreen">{{ officialExamActive ? 'Quay lại toàn màn hình' : 'Bật toàn màn hình' }}</button>
            <small>Website không thể chặn chụp màn hình ở cấp hệ điều hành.</small>
          </section>

          <section v-else-if="!officialExamActive" class="exam-preparing" role="status" aria-live="polite">
            <div v-if="cameraReady" class="spinner"></div>
            <h2>{{ cameraReady ? 'Đang chuẩn bị bài thi' : 'Bật camera để tiếp tục' }}</h2>
            <p v-if="error">{{ error }}</p>
            <p v-else-if="cameraReady">Đang xác nhận trạng thái làm bài với máy chủ. Câu hỏi sẽ xuất hiện sau khi xác nhận thành công.</p>
            <p v-else>Cho phép webcam để tiếp tục. Câu trả lời và thời gian làm bài được giữ nguyên khi camera kết nối lại.</p>
            <p class="evidence-privacy-notice">AI có thể lưu ảnh webcam khi xác nhận cảnh báo; không ghi video và không yêu cầu chia sẻ màn hình.</p>
            <button v-if="error && cameraReady" type="button" class="btn primary" @click="beginExamWhenReady">Thử bắt đầu lại</button>
          </section>

          <template v-else>
            <div v-if="!monitorReady" class="monitor-sync-notice" role="status">
              Đang đồng bộ trạng thái giám sát. Câu trả lời vẫn được giữ; thao tác trả lời tạm khóa.
              <button type="button" @click="resumeMonitoring">Kết nối lại</button>
            </div>

            <div class="monitor-banner">
              <div>
                <strong>◉ Giám sát bài kiểm tra</strong>
                <p>Rời tab 3 lần, phát hiện điện thoại 3 lần hoặc có 5 cảnh báo camera khác sẽ khóa bài.</p>
              </div>
              <span class="violation-badge" :class="{ 'warning-level': warningCount >= 2 }">{{ warningCount }}/3 lần rời tab</span>
            </div>

            <div v-if="examPhase === 'CAMERA_BLOCKED'" class="camera-off-notice" role="alert">
              <strong>Camera đang tắt</strong>
              <span>Hãy bật lại camera ở bảng bên cạnh. Bài làm và thời gian còn lại được giữ nguyên.</span>
            </div>

            <p v-if="evidenceNotice" class="evidence-upload-notice" role="status">{{ evidenceNotice }}</p>

            <section class="overview">
              <div><small>SINH VIÊN</small><strong>{{ studentName }}</strong></div>
              <div><small>SỐ CÂU HỎI</small><strong>{{ questions.length }}</strong></div>
              <div><small>ĐÃ TRẢ LỜI</small><strong>{{ answeredCount }}/{{ questions.length }}</strong></div>
              <div><small>SỐ LẦN RỜI TAB</small><strong :class="{ 'text-danger': warningCount >= 2 }">{{ warningCount }}/3</strong></div>
            </section>

            <div v-if="questions.length === 0" class="state-card">
              <h3>Bài kiểm tra chưa có câu hỏi</h3>
              <p>Vui lòng liên hệ giảng viên.</p>
            </div>

            <main v-else ref="protectedQuestionsRef" class="question-card">
              <div class="question-meta">
                <span>Câu {{ currentIndex + 1 }}/{{ questions.length }}</span>
                <span class="point">{{ currentQuestion?.diem ?? 0 }} điểm</span>
              </div>
              <h2>{{ currentQuestion?.noi_dung }}</h2>

              <div v-if="currentQuestion?.loai_cau_hoi === 'trac_nghiem'" class="answers">
                <label v-for="(answer, index) in currentQuestion.dap_an" :key="answer.id" class="answer" :class="{
                  selected: String(answers[String(currentQuestion.id)]) === String(answer.id)
                }">
                  <input v-model="answers[String(currentQuestion.id)]" type="radio"
                    :name="`question-${currentQuestion.id}`" :value="answer.id" :disabled="!canEdit" />
                  <span class="letter">{{ String.fromCharCode(65 + index) }}</span>
                  <span>{{ answer.noi_dung }}</span>
                </label>
              </div>

              <textarea v-else-if="currentQuestion?.loai_cau_hoi === 'tu_luan'"
                v-model="answers[String(currentQuestion.id)]" :disabled="!canEdit" maxlength="20000" rows="9"
                placeholder="Nhập câu trả lời của bạn..."></textarea>
              <p v-else class="muted">Loại câu hỏi không được hỗ trợ.</p>

              <div class="question-nav">
                <button type="button" class="btn pale" :disabled="currentIndex === 0" @click="currentIndex--">← Câu trước</button>
                <button v-if="currentIndex < questions.length - 1" type="button" class="btn primary" @click="currentIndex++">Câu tiếp →</button>
                <button v-else type="button" class="btn primary" :disabled="!canSubmit" @click="openSubmitConfirmation">Kiểm tra & nộp</button>
              </div>
            </main>
          </template>
        </section>

        <aside class="exam-sidebar">
          <section v-if="exam && cameraEnabled && (fullscreenActive || cameraReady) && !locked && !loading"
            class="camera-panel" :class="{ 'camera-panel-paused': officialExamActive && !fullscreenActive }">
            <GiamSatCamera :exam-in-progress="officialExamActive" @warning="handleCameraWarning" @normal="handleCameraNormal"
              @camera-starting="handleCameraStarting" @camera-state="handleCameraState"
              @stream-ready="handleCameraStreamReady" @stream-stopped="handleCameraStreamStopped" />
          </section>

          <section v-if="officialExamActive && fullscreenActive" class="sidebar">
            <div class="sidebar-heading">
              <h3>Danh sách câu hỏi</h3>
              <span>{{ answeredCount }}/{{ questions.length }} đã trả lời</span>
            </div>
            <div class="question-grid">
              <button v-for="(question, index) in questions" :key="question.id" type="button" class="q-button" :class="{
                answered: isAnswered(question.id), active: index === currentIndex
              }" @click="currentIndex = index">{{ index + 1 }}</button>
            </div>
            <div class="progress">
              <div class="progress-header"><span>Tiến độ làm bài</span><strong>{{ progress }}%</strong></div>
              <div class="progress-track"><div class="progress-fill" :style="{ width: progress + '%' }"></div></div>
            </div>

            <details class="monitor-box">
              <summary>Chi tiết cảnh báo và quy định</summary>
              <p>Rời tab 3 lần, phát hiện điện thoại 3 lần hoặc 5 cảnh báo camera khác sẽ khóa bài.</p>
              <p>Cảnh báo camera khác: <strong>{{ cameraWarningCount }}/5</strong></p>
              <p>Phát hiện điện thoại: <strong>{{ phoneWarningCount }}/3</strong></p>
              <p>Rời tab: <strong>{{ warningCount }}/3</strong></p>
            </details>

            <button type="button" class="btn submit-button" :disabled="!canSubmit" @click="openSubmitConfirmation">Nộp bài kiểm tra</button>
          </section>
        </aside>
      </div>
    </div>

    <!-- XÁC NHẬN NỘP -->

    <!-- HỘP THOẠI XÁC NHẬN NỘP BÀI -->
    <div v-if="showConfirm" class="exam-modal-overlay" @click.self="showConfirm = false">
        <div ref="submitDialogRef" class="exam-modal-content" role="dialog" aria-modal="true" aria-labelledby="submit-dialog-title" tabindex="-1" @keydown.esc.prevent="showConfirm = false">
          <div class="exam-modal-icon">📤</div>

          <h2 id="submit-dialog-title">
            Xác nhận nộp bài
          </h2>

          <p>
            Bạn đã trả lời
            <strong>
              {{ answeredCount }}/{{ questions.length }}
            </strong>
            câu hỏi.
          </p>

          <p v-if="answeredCount < questions.length">
            Còn
            <strong>
              {{ questions.length - answeredCount }}
            </strong>
            câu chưa trả lời.
          </p>

          <p class="exam-modal-warning">
            Sau khi nộp bài, bạn sẽ không thể
            chỉnh sửa câu trả lời.
          </p>

          <p v-if="submitError" class="notice error-notice" role="alert">{{ submitError }}</p>

          <div class="exam-modal-actions">
            <button type="button" class="exam-modal-cancel" :disabled="submitting" @click="showConfirm = false">
              Tiếp tục làm
            </button>

            <button type="button" class="exam-modal-submit" :disabled="!canSubmit"
              @click="submitExam(false)">
              {{
                submitting
                  ? "Đang nộp..."
                  : "Xác nhận nộp bài"
              }}
            </button>
          </div>
        </div>
    </div>


    <!-- POPUP CẢNH BÁO -->
    <div v-if="showWarning && !locked" class="overlay">
      <div class="modal warning-modal" role="alertdialog" aria-modal="true">
        <div class="modal-icon">⚠️</div>

        <h2>Cảnh báo rời tab!</h2>

        <p>
          Bạn đã rời khỏi tab làm bài kiểm tra.
        </p>

        <div class="warning-number">
          {{ warningCount }}/3
        </div>

        <p>
          {{
            warningCount === 1
              ? "Đây là cảnh báo lần đầu. Bạn còn 2 lượt trước khi bị khóa bài."
              : "Đây là cảnh báo lần thứ hai. Nếu rời tab thêm một lần, bài sẽ bị khóa."
          }}
        </p>

        <button type="button" class="btn primary full-width" @click="showWarning = false">
          Tôi đã hiểu
        </button>
      </div>
    </div>

    <!-- THÔNG BÁO KHÓA -->
    <div v-if="showLockNotice" class="overlay">
      <div class="modal warning-modal" role="alertdialog" aria-modal="true">
        <div class="modal-icon">🔒</div>
        <h2>Bài kiểm tra bị khóa!</h2>

        <p v-if="lockReason === 'dien_thoai'">
          Đã ghi nhận 3 lần phát hiện điện thoại.
          Hệ thống đã khóa bài kiểm tra.
        </p>
        <p v-else-if="lockReason === 'camera'">
          Đã ghi nhận 5 cảnh báo camera.
          Hệ thống đã khóa bài kiểm tra.
        </p>
        <p v-else>
          Bạn đã rời tab 3 lần. Hệ thống đã khóa
          bài kiểm tra và không cho phép làm tiếp.
        </p>

        <button type="button" class="btn primary full-width" @click="showLockNotice = false">
          Đã hiểu
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  watch,
  nextTick,
  onMounted,
  onBeforeUnmount,
} from "vue"

import {
  useRoute,
  useRouter,
} from "vue-router"

import api from "../../../services/api"
import GiamSatCamera from "./GiamSatCamera.vue"
import studentSender from "../../../services/webrtc/studentSender"
import { useExamProtection } from "../../../services/useExamProtection"

import { shallowRef } from "vue"

const examCameraStream = shallowRef(null)
const phienGiamSatId = ref(null)
const sinhVienId = ref(null)
const webrtcConnected = ref(false)

function initWebRtcIfReady() {
  if (phienGiamSatId.value && sinhVienId.value && examCameraStream.value) {
    console.log("[SinhVien WebRTC] Khởi tạo kết nối tới phiên:", phienGiamSatId.value)
    studentSender.init(phienGiamSatId.value, sinhVienId.value, examCameraStream.value, (state) => {
      webrtcConnected.value = state === 'connected'
    })
  }
}

function handleCameraStreamReady(stream) {
  examCameraStream.value = stream
  console.log("Đã nhận webcam để truyền sang Giảng viên:", stream.id)

  if (studentSender.phienId && studentSender.connected) {
    studentSender.updateStream(stream)
  } else {
    initWebRtcIfReady()
  }
}

function handleCameraStreamStopped() {
  examCameraStream.value = null
  webrtcConnected.value = false
  console.log("Webcam đã dừng")
  studentSender.notifyCameraStopped()
}

const route = useRoute()
const router = useRouter()

const baiTapId = computed(() =>
  String(
    route.query.baiTapId ??
    route.params.baiTapId ??
    ""
  )
)

/* ==================================
   STATE
================================== */
const exam = ref(null)
const examRootRef = ref(null)
const protectedQuestionsRef = ref(null)
const answers = ref({})
const currentIndex = ref(0)

const loading = ref(false)
const submitting = ref(false)
const submitError = ref("")
const error = ref("")
const saveStatus = ref("Chưa có thay đổi")

const remainingSeconds = ref(0)
const serverOffset = ref(0)
const studentName = ref("Sinh viên")

const warningCount = ref(0)
const cameraWarningCount = ref(0)
const phoneWarningCount = ref(0)
const lockReason = ref("")
let cameraQueue = []
let cameraRequest = null
const evidenceNotice = ref("")
const locked = ref(false)
const monitorReady = ref(false)
const cameraEnabled = ref(false)
const cameraReady = ref(false)
const examPhase = ref('PRE_EXAM')
const officialExamActive = computed(() => ['IN_PROGRESS', 'CAMERA_BLOCKED'].includes(examPhase.value))

const protectionActive = computed(() => Boolean(
  exam.value &&
  examPhase.value === 'IN_PROGRESS' &&
  !locked.value &&
  !submitting.value &&
  remainingSeconds.value > 0
))

const protection = useExamProtection({
  active: protectionActive,
  protectedElement: protectedQuestionsRef,
  onEvent: recordProtectionEvent,
})
// Expose refs at the top level so Vue templates unwrap them reliably.
const fullscreenActive = protection.fullscreenActive
const protectionNotice = protection.notice
const protectionNoticeError = protection.noticeError

const showWarning = ref(false)
const showLockNotice = ref(false)
const showConfirm = ref(false)
const submitDialogRef = ref(null)
const submitReturnFocusRef = ref(null)

let deadlineMs = 0
let timerInterval = null
let saveTimer = null
let saveInFlight = null
let lastSavedVersion = -1

let loaded = false
let submitted = false
let submittingAtDeadline = false
let monitoringRequest = null
let startExamRequest = null
let eventQueue = []
let hiddenEventRecorded = false
let visibilityRearmTimer = null
const VISIBILITY_REARM_DELAY_MS = 1500
let requestGeneration = 0
let saveVersion = 0
let disposed = false

/* ==================================
   QUESTION
================================== */
const questions = computed(() =>
  exam.value?.questions || []
)

const currentQuestion = computed(() =>
  questions.value[currentIndex.value] || null
)

function isAnswered(questionId) {
  const value = answers.value[String(questionId)]

  return value !== null &&
    value !== undefined &&
    String(value).trim() !== ""
}

function handleCameraState(isActive) {
  cameraReady.value = isActive === true
  if (!cameraReady.value && examPhase.value === 'IN_PROGRESS') {
    examPhase.value = 'CAMERA_BLOCKED'
  } else if (cameraReady.value && examPhase.value === 'CAMERA_BLOCKED') {
    examPhase.value = 'IN_PROGRESS'
  } else if (cameraReady.value && !officialExamActive.value && examPhase.value !== 'SUBMITTED') {
    examPhase.value = 'PREPARING'
    void beginExamWhenReady()
  }
  if (!cameraReady.value) {
    clearTimeout(saveTimer)
  } else if (canEdit.value && saveStatus.value === "Chưa lưu") {
    clearTimeout(saveTimer)
    saveTimer = setTimeout(saveDraft, 700)
  }
}

function openSubmitConfirmation(event) {
  if (!canSubmit.value) return
  submitReturnFocusRef.value = event?.currentTarget ?? null
  showConfirm.value = true
}

watch(showConfirm, async (isOpen) => {
  await nextTick()
  if (isOpen) {
    submitDialogRef.value?.focus()
    return
  }
  submitReturnFocusRef.value?.focus?.()
  submitReturnFocusRef.value = null
})

function handleCameraWarning(event) {
  if (
    !loaded ||
    submitted ||
    locked.value ||
    disposed ||
    !baiTapId.value
  ) {
    return
  }

  const types = {
    no_face: "khong_co_khuon_mat",
    multiple_faces: "nhieu_khuon_mat",
    camera_off: "camera_ngat",

    khong_co_khuon_mat: "khong_co_khuon_mat",
    nhieu_khuon_mat: "nhieu_khuon_mat",
    camera_ngat: "camera_ngat",
    phat_hien_dien_thoai: "phat_hien_dien_thoai",
  }

  const loaiCanhBao = types[event.type]

  if (!loaiCanhBao) return

  cameraQueue.push({
    eventId: createEventId(),
    loaiCanhBao,
    evidence: event.type === 'camera_ngat' || loaiCanhBao === 'camera_ngat'
      ? null
      : (event.evidence ?? null),
  })

  monitorReady.value = false
  flushCameraQueue()
    .then(() => {
      if (!locked.value && !disposed && cameraQueue.length === 0) {
        resumeMonitoring()
      }
    })
    .catch((err) => {
      console.error("Lỗi gửi cảnh báo camera:", err)
      error.value =
        "Không thể đồng bộ cảnh báo camera: " + getMessage(err)
      monitorReady.value = false
    })
}

async function flushCameraQueue() {
  if (cameraRequest) {
    return cameraRequest
  }

  cameraRequest = (async () => {
    while (
      cameraQueue.length > 0 &&
      !locked.value
    ) {
      const event = cameraQueue[0]

      const response = await api.post(
        endpoint("/canh-bao-camera"),
        { eventId: event.eventId, loaiCanhBao: event.loaiCanhBao }
      )

      if (
        response.data?.status === false ||
        response.data?.success === false
      ) {
        throw new Error(
          response.data?.message ||
          "Không thể lưu cảnh báo camera."
        )
      }

      const data = response.data?.data ?? response.data

      // Chỉ xóa sau khi Backend phản hồi thành công.
      cameraQueue.shift()

      // Keep warning persistence independent from image encoding/network latency.
      void uploadCameraEvidence(event, data).catch(() => {})

      // Backend trả ba bộ đếm độc lập: camera, điện thoại và rời tab.
      // Dùng cùng một hàm với API /trang-thai để tránh lệch trạng thái.
      const stillAllowed = applyViolationState(data)
      if (!stillAllowed) {
        cameraQueue = []
        return
      }
    }
  })().finally(() => {
    cameraRequest = null
  })

  return cameraRequest
}

async function uploadCameraEvidence(event, alertResponse) {
  if (!alertResponse?.eventAccepted || event.loaiCanhBao === 'camera_ngat') return
  const base = endpoint(`/canh-bao-camera/${encodeURIComponent(event.eventId)}`)
  let blob = null
  try {
    blob = await Promise.resolve(event.evidence)
  } catch {
    blob = null
  }

  if (!blob) {
    evidenceNotice.value = 'Cảnh báo đã ghi nhận nhưng không tạo được ảnh webcam.'
    await api.post(`${base}/anh-that-bai`, { eventId: event.eventId }).catch(() => {})
    return
  }

  const formData = new FormData()
  formData.append('eventId', event.eventId)
  formData.append('image', blob, `${event.eventId}.jpg`)
  for (let attempt = 0; attempt < 2; attempt++) {
    try {
      await api.post(`${base}/anh`, formData)
      evidenceNotice.value = 'Ảnh webcam bằng chứng đã được lưu.'
      return
    } catch (uploadError) {
      if (attempt === 1) {
        evidenceNotice.value = 'Cảnh báo đã ghi nhận nhưng upload ảnh thất bại.'
        await api.post(`${base}/anh-that-bai`, { eventId: event.eventId }).catch(() => {})
      }
    }
  }
}

function handleCameraNormal(event) {
  console.log(
    "Camera trở lại trạng thái bình thường:",
    event
  )
}

const answeredCount = computed(() =>
  questions.value.filter(
    question => isAnswered(question.id)
  ).length
)

const progress = computed(() => {
  if (!questions.value.length) return 0

  return Math.round(
    (answeredCount.value / questions.value.length) * 100
  )
})

const canEdit = computed(() =>
  loaded &&
  exam.value !== null &&
  monitorReady.value &&
  cameraReady.value &&
  !locked.value &&
  !submitted &&
  !submitting.value &&
  officialExamActive.value &&
  protection.fullscreenActive.value &&
  remainingSeconds.value > 0
)

// Nộp bài là thao tác kết thúc: lỗi camera, giám sát hoặc fullscreen không
// được giữ Sinh viên mắc kẹt trong bài. Backend vẫn xác thực attempt và hạn nộp.
const canSubmit = computed(() =>
  loaded &&
  exam.value !== null &&
  !submitted &&
  !submitting.value &&
  officialExamActive.value &&
  (remainingSeconds.value > 0 || submittingAtDeadline)
)

function recordProtectionEvent(event) {
  if (!protectionActive.value || !baiTapId.value) return Promise.resolve()
  return api.post(endpoint("/bao-ve"), event)
}

function requestExamFullscreen() {
  if (examPhase.value === 'PRE_EXAM') examPhase.value = 'PREPARING'
  return protection.requestFullscreen(examRootRef.value)
}

function handleCameraStarting() {
  if (examPhase.value === 'PRE_EXAM') examPhase.value = 'PREPARING'
}

async function beginExamWhenReady() {
  if (officialExamActive.value || examPhase.value === 'SUBMITTED' || startExamRequest) {
    return startExamRequest
  }
  if (examPhase.value === 'PRE_EXAM' && fullscreenActive.value) examPhase.value = 'PREPARING'
  if (!loaded || !fullscreenActive.value || !cameraReady.value || !monitorReady.value || locked.value || submitted) {
    return
  }
  if (document.visibilityState !== 'visible' || (typeof document.hasFocus === 'function' && !document.hasFocus())) {
    return
  }

  examPhase.value = 'PREPARING'
  startExamRequest = api.post(endpoint('/bat-dau'), {
    fullscreen: fullscreenActive.value,
    cameraReady: cameraReady.value,
  })
    .then((response) => {
      if (response.data?.status === false || response.data?.success === false) {
        throw new Error(response.data?.message || 'Máy chủ chưa xác nhận bắt đầu bài thi.')
      }
      hiddenEventRecorded = false
      if (visibilityRearmTimer !== null) {
        clearTimeout(visibilityRearmTimer)
        visibilityRearmTimer = null
      }
      examPhase.value = 'IN_PROGRESS'
      error.value = ''
    })
    .catch((err) => {
      examPhase.value = 'PREPARING'
      error.value = `Chưa thể bắt đầu bài thi: ${handleApiError(err)}`
    })
    .finally(() => {
      startExamRequest = null
    })
  return startExamRequest
}

watch([fullscreenActive, cameraReady, monitorReady], () => {
  if (examPhase.value !== 'SUBMITTED') void beginExamWhenReady()
})

/* ==================================
   TIMER
================================== */
const formattedTime = computed(() => {
  const seconds = remainingSeconds.value

  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const remainder = seconds % 60

  return [hours, minutes, remainder]
    .map(value => String(value).padStart(2, "0"))
    .join(":")
})

function clockTick() {
  if (!exam.value || submitted) {
    return
  }

  remainingSeconds.value = Math.max(
    0,
    Math.ceil(
      (
        deadlineMs -
        Date.now() -
        serverOffset.value
      ) / 1000
    )
  )

  if (
    remainingSeconds.value === 0 &&
    loaded &&
    !submitting.value &&
    !submittingAtDeadline
  ) {
    submittingAtDeadline = true
    clearInterval(timerInterval)
    submitExam(true)
  }
}

/* ==================================
   USER INFO
================================== */
function loadStudent() {
  try {
    const raw =
      sessionStorage.getItem("auth_user") ||
      localStorage.getItem("auth_user") ||
      sessionStorage.getItem("nguoi_dung") ||
      localStorage.getItem("nguoi_dung")

    if (!raw) return

    const user = JSON.parse(raw)

    studentName.value =
      user.ho_ten ??
      user.hoTen ??
      "Sinh viên"
  } catch (err) {
    console.error("Lỗi đọc thông tin sinh viên:", err)
  }
}

function getMessage(err) {
  return (
    err.response?.data?.message ||
    err.message ||
    "Không thể thực hiện yêu cầu."
  )
}

function endpoint(suffix = "") {
  return `/sinh-vien/bai-kiem-tra/${encodeURIComponent(baiTapId.value)
    }${suffix}`
}

function cloneAnswers() {
  return JSON.parse(
    JSON.stringify(answers.value)
  )
}

/* ==================================
   LOCK
================================== */

function applyLocked() {
  locked.value = true
  cameraEnabled.value = false
  cameraReady.value = false
  monitorReady.value = false

  if (lockReason.value === "dien_thoai") {
    phoneWarningCount.value = Math.max(3, phoneWarningCount.value)
  } else if (lockReason.value === "camera") {
    cameraWarningCount.value = Math.max(5, cameraWarningCount.value)
  } else if (lockReason.value === "roi_tab") {
    warningCount.value = Math.max(3, warningCount.value)
  }

  showConfirm.value = false
  showWarning.value = false
  showLockNotice.value = exam.value !== null

  clearTimeout(saveTimer)

  saveStatus.value = "Bài kiểm tra đã khóa"
}




function applyViolationState(data) {
  const tabCount = Number(data?.soLanViPham ?? 0)
  const camCount = Number(data?.soLanCamera ?? 0)
  const phoneCount = Number(data?.soLanDienThoai ?? 0)

  if (Number.isFinite(tabCount)) {
    warningCount.value = Math.max(
      warningCount.value,
      tabCount
    )
  }

  if (Number.isFinite(camCount)) {
    cameraWarningCount.value = Math.max(
      cameraWarningCount.value,
      camCount
    )
  }

  if (Number.isFinite(phoneCount)) {
    phoneWarningCount.value = Math.max(
      phoneWarningCount.value,
      phoneCount
    )
  }

  if (
    data?.biKhoa === true ||
    phoneWarningCount.value >= 3 ||
    cameraWarningCount.value >= 5 ||
    warningCount.value >= 3
  ) {
    lockReason.value =
      data?.lyDoKhoa ||
      (phoneWarningCount.value >= 3
        ? "dien_thoai"
        : cameraWarningCount.value >= 5
          ? "camera"
          : "roi_tab")

    applyLocked()
    return false
  }

  return true
}



function handleApiError(err) {
  const message = getMessage(err)

  if (
    err.response?.status === 403 &&
    /kh[oó]a|rời tab|vi phạm|camera|điện thoại|dien thoai/i.test(message)
  ) {
    if (/điện thoại|dien thoai/i.test(message)) {
      lockReason.value = "dien_thoai"
    } else if (/camera/i.test(message)) {
      lockReason.value = "camera"
    } else if (!lockReason.value) {
      lockReason.value = "roi_tab"
    }
    applyLocked()
  }

  return message
}

/* ==================================
   MONITORING API
================================== */
async function fetchViolationState() {
  const response = await api.get(
    endpoint("/trang-thai")
  )

  const data = response.data?.data ?? response.data
  if (data?.phienGiamSat?.id) {
    phienGiamSatId.value = data.phienGiamSat.id
  }
  if (data?.sinhVienId) {
    sinhVienId.value = data.sinhVienId
  }
  initWebRtcIfReady()

  if (
    response.data?.status === false ||
    response.data?.success === false
  ) {
    throw new Error(
      response.data?.message ||
      "Không xác minh được trạng thái giám sát."
    )
  }

  if (data?.trangThaiLamBai === 'SUBMITTED' || data?.daNop === true) {
    submitted = true
    examPhase.value = 'SUBMITTED'
    return false
  }

  return applyViolationState(data)
}

function createEventId() {
  if (globalThis.crypto?.randomUUID) {
    return globalThis.crypto.randomUUID()
  }

  throw new Error(
    "Trình duyệt không hỗ trợ tạo mã sự kiện an toàn."
  )
}

/*
  Mỗi sự kiện ẩn tab có eventId riêng.
  Nếu gửi thất bại, giữ lại trong hàng đợi
  để gửi lại cùng eventId khi kết nối phục hồi.
*/
async function flushViolationQueue() {
  if (monitoringRequest) {
    return monitoringRequest
  }

  monitoringRequest = (async () => {
    while (eventQueue.length && !locked.value) {
      const eventId = eventQueue[0]

      const response = await api.post(
        endpoint("/roi-tab"),
        { eventId }
      )

      if (
        response.data?.status === false ||
        response.data?.success === false
      ) {
        throw new Error(
          response.data?.message ||
          "Ghi nhận vi phạm thất bại."
        )
      }

      const previousCount = warningCount.value
      const data = response.data?.data ?? response.data

      eventQueue.shift()

      const stillAllowed = applyViolationState(data)

      if (!stillAllowed) {
        eventQueue = []
        return
      }

      if (
        !document.hidden &&
        warningCount.value > previousCount &&
        warningCount.value < 3
      ) {
        showWarning.value = true
      }
    }
  })().finally(() => {
    monitoringRequest = null
  })

  return monitoringRequest
}

async function resumeMonitoring() {
  if (locked.value || submitted || disposed) {
    return
  }

  monitorReady.value = false
  error.value = ""

  try {
    // Đồng bộ camera trước rồi mới đồng bộ rời tab.
    await flushCameraQueue()
    if (locked.value) return
    await flushViolationQueue()

    if (locked.value) return

    // Sau đó lấy số lần vi phạm mới nhất.
    const allowed = await fetchViolationState()

    if (!allowed) return

    monitorReady.value = true

    // Đồng bộ lại đồng hồ.
    if (exam.value) {
      clockTick()
    }
  } catch (err) {
    monitorReady.value = false

    error.value =
      "Không thể đồng bộ giám sát: " +
      handleApiError(err)
  }
}

function handleVisibilityChange() {
  if (!officialExamActive.value || submitting.value || submitted) {
    if (!document.hidden) void beginExamWhenReady()
    return
  }

  if (document.hidden) {
    if (visibilityRearmTimer !== null) {
      clearTimeout(visibilityRearmTimer)
      visibilityRearmTimer = null
    }

    if (
      !loaded ||
      submitted ||
      locked.value ||
      hiddenEventRecorded
    ) {
      return
    }

    void saveDraft(true)
    hiddenEventRecorded = true
    monitorReady.value = false
    showConfirm.value = false

    try {
      eventQueue.push(createEventId())

      // Gửi ngay khi tab bắt đầu bị ẩn.
      flushViolationQueue().catch(err => {
        console.warn("Chưa gửi được vi phạm:", err)
      })
    } catch (err) {
      error.value = getMessage(err)
    }

    return
  }

  // Chỉ coi lần quay lại là một episode mới nếu trang ổn định visible.
  // Tránh nhiều eventId khi trình duyệt phát nhanh chuỗi hidden/visible.
  if (hiddenEventRecorded && visibilityRearmTimer === null) {
    visibilityRearmTimer = window.setTimeout(() => {
      hiddenEventRecorded = false
      visibilityRearmTimer = null
    }, VISIBILITY_REARM_DELAY_MS)
  }

  if (
    !loaded ||
    submitted ||
    locked.value
  ) {
    return
  }

  resumeMonitoring()
}

function handleExamWindowFocus() {
  if (!officialExamActive.value) void beginExamWhenReady()
}

watch(fullscreenActive, (isFullscreen, wasFullscreen) => {
  if (wasFullscreen && !isFullscreen && examPhase.value === 'IN_PROGRESS') {
    clearTimeout(saveTimer)
    void saveDraft(true)
  }
})

/* ==================================
   LOAD EXAM
================================== */
async function loadExam() {
  if (!baiTapId.value) {
    error.value = "Thiếu baiTapId trên đường dẫn."
    return
  }

  const generation = ++requestGeneration

  loading.value = true
  error.value = ""
  examPhase.value = 'PRE_EXAM'
  monitorReady.value = false
  loaded = false
  exam.value = null
  cameraEnabled.value = false
  cameraReady.value = false

  clearInterval(timerInterval)
  clearTimeout(saveTimer)

  try {
    // Kiểm tra bị khóa trước khi tải đề.
    const allowed = await fetchViolationState()

    if (generation !== requestGeneration || !allowed) {
      return
    }

    const before = Date.now()

    const response = await api.get(endpoint())

    const after = Date.now()

    if (
      response.data?.status === false ||
      response.data?.success === false
    ) {
      throw new Error(
        response.data.message || "Không tải được đề."
      )
    }

    const data = response.data?.data

    if (!data) {
      throw new Error("Backend không trả về đề kiểm tra.")
    }

    const serverMs = Date.parse(data.serverTime)
    const deadline = Date.parse(data.deadline)

    if (
      !Number.isFinite(serverMs) ||
      !Number.isFinite(deadline)
    ) {
      throw new Error(
        "Thời gian trả về từ Backend không hợp lệ."
      )
    }

    if (generation !== requestGeneration) return

    exam.value = data
    if (data?.phienGiamSat?.id) {
      phienGiamSatId.value = data.phienGiamSat.id
    }
    if (data?.sinhVienId) {
      sinhVienId.value = data.sinhVienId
    }
    initWebRtcIfReady()

    answers.value =
      data.answers &&
        typeof data.answers === "object" &&
        !Array.isArray(data.answers)
        ? data.answers
        : {}

    currentIndex.value = 0

    serverOffset.value =
      serverMs - (before + after) / 2

    deadlineMs = deadline

    submitted = false
    examPhase.value = data.trangThaiLamBai === 'IN_PROGRESS' ? 'PREPARING' : 'PRE_EXAM'
    submittingAtDeadline = false
    loaded = true

    await resumeMonitoring()
    cameraEnabled.value = !locked.value && !submitted

    if (
      !locked.value &&
      monitorReady.value &&
      !submittingAtDeadline
    ) {
      timerInterval = setInterval(clockTick, 1000)
      clockTick()
    }
  } catch (err) {
    error.value = handleApiError(err)
  } finally {
    if (generation === requestGeneration) {
      loading.value = false
    }
  }
}

function reloadExam() {
  if (!locked.value) {
    loadExam()
  }
}

/* ==================================
   AUTOSAVE
================================== */
async function saveDraft(force = false) {
  if (
    !loaded ||
    submitted ||
    locked.value ||
    (!force && (!monitorReady.value || !cameraReady.value)) ||
    submitting.value ||
    remainingSeconds.value <= 0
  ) {
    return
  }

  // Không cho hai request ghi nháp chạy song song.
  if (saveInFlight) {
    await saveInFlight

    if (lastSavedVersion === saveVersion || (!force && !canEdit.value)) return
  }

  const version = saveVersion
  if (version === lastSavedVersion) return
  const payload = cloneAnswers()

  saveStatus.value = "Đang lưu..."

  const request = api.put(
    endpoint("/nhap"),
    { answers: payload }
  )

  saveInFlight = request

  try {
    await request
    lastSavedVersion = version

    saveStatus.value =
      saveVersion === version
        ? "✓ Đã lưu bài nháp"
        : "Có thay đổi chưa lưu"

    if (saveVersion !== version && (canEdit.value || force)) {
      clearTimeout(saveTimer)
      saveTimer = setTimeout(() => saveDraft(force), 700)
    }
  } catch (err) {
    saveStatus.value = "Lưu nháp thất bại"
    error.value =
      "Không thể lưu nháp: " + handleApiError(err)
  } finally {
    if (saveInFlight === request) {
      saveInFlight = null
    }
  }
}

watch(
  answers,
  () => {
    if (!loaded || submitted || locked.value) return

    saveVersion++
    saveStatus.value = "Chưa lưu"

    clearTimeout(saveTimer)

    if (canEdit.value) {
      saveTimer = setTimeout(saveDraft, 700)
    }
  },
  { deep: true }
)

/* ==================================
   SUBMIT
================================== */
async function submitExam(auto = false) {
  if (
    submitting.value ||
    submitted ||
    !exam.value ||
    !loaded
  ) {
    return
  }

  if (!auto && !canSubmit.value) {
    return
  }

  clearTimeout(saveTimer)

  submitting.value = true
  error.value = ""
  submitError.value = ""

  try {
    // Lỗi mạng khi lưu nháp không được chặn gửi snapshot đáp án hiện tại.
    if (saveInFlight) {
      await saveInFlight.catch(() => {})
    }
    const answerSnapshot = cloneAnswers()
    const response = await api.post(
      endpoint("/nop"),
      {
        answers: answerSnapshot,
        tuDongNop: auto,
      }
    )

    if (
      response.data?.status === false ||
      response.data?.success === false
    ) {
      throw new Error(
        response.data?.message ||
        "Nộp bài thất bại."
      )
    }

    submitted = true
    submitReturnFocusRef.value = null
    showConfirm.value = false
    examPhase.value = 'SUBMITTED'
    cameraEnabled.value = false
    loaded = false
    monitorReady.value = false

    // The camera component unmounts only after the backend accepted the
    // submission. Mark protection inactive first so fullscreen exit is not
    // recorded as an exam violation.
    await nextTick()
    await protection.exitFullscreen()

    clearInterval(timerInterval)

    window.alert(
      auto
        ? "Đã hết giờ. Nộp bài thành công."
        : "Nộp bài kiểm tra thành công."
    )

    router.push("/sinh-vien/bai-tap")
  } catch (err) {
    submitError.value = "Không thể nộp bài: " + handleApiError(err)
    error.value = submitError.value
  } finally {
    submitting.value = false
  }
}

/* ==================================
   NAVIGATION
================================== */
function goBack() {
  router.push("/sinh-vien/bai-tap")
}

/* ==================================
   LIFECYCLE
================================== */
onMounted(() => {
  disposed = false
  loadStudent()

  document.addEventListener(
    "visibilitychange",
    handleVisibilityChange
  )
  window.addEventListener('focus', handleExamWindowFocus)

  loadExam()
})

onBeforeUnmount(() => {
  disposed = true
  studentSender.disconnect()
  cameraEnabled.value = false
  cameraReady.value = false
  loaded = false
  requestGeneration++

  clearInterval(timerInterval)
  clearTimeout(saveTimer)
  clearTimeout(visibilityRearmTimer)
  visibilityRearmTimer = null

  document.removeEventListener(
    "visibilitychange",
    handleVisibilityChange
  )
  window.removeEventListener('focus', handleExamWindowFocus)
})
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.exam-page {
  min-height: 100vh;
  padding: 28px;
  background: #f5f7fb;
  color: #182033;
  font-family: Inter, system-ui, sans-serif;
}

.exam-page:fullscreen {
  height: 100vh;
  min-height: 100vh;
  overflow-x: hidden;
  overflow-y: auto;
  overscroll-behavior: contain;
}

.exam-protection-active .question-card,
.exam-protection-active .question-card * {
  -webkit-user-select: none;
  user-select: none;
}

.exam-protection-active .question-card input,
.exam-protection-active .question-card textarea,
.exam-protection-active .question-card select,
.exam-protection-active .question-card [contenteditable="true"],
.exam-protection-active .question-card [role="textbox"] {
  -webkit-user-select: text;
  user-select: text;
}

.exam-protection-notice {
  margin: 12px 0;
  padding: 12px 15px;
  border: 1px solid #f59e0b;
  border-radius: 10px;
  background: #fffbeb;
  color: #92400e;
  font-size: 13px;
  line-height: 1.6;
}

.exam-protection-notice.notice-error {
  border-color: #ef4444;
  background: #fef2f2;
  color: #991b1b;
}

.fullscreen-required {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
  min-height: 300px;
  padding: 40px 25px;
  border: 1px solid #93c5fd;
  border-radius: 16px;
  background: #eff6ff;
  color: #1e3a8a;
  text-align: center;
}

.exam-preparing {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  min-height: 300px;
  padding: 40px 24px;
  border: 1px solid #cbd5e1;
  border-radius: 16px;
  background: #f8fafc;
  color: #334155;
  text-align: center;
}

.exam-preparing p {
  max-width: 600px;
  line-height: 1.7;
}

.fullscreen-required p,
.fullscreen-required small {
  max-width: 600px;
  line-height: 1.7;
}

.fullscreen-required small {
  color: #475569;
  font-size: 12px;
}

.fullscreen-return-prompt {
  display: grid;
  place-content: center;
  min-height: min(55vh, 440px);
  padding: clamp(22px, 5vw, 44px);
}

.camera-presence-pill {
  padding: 7px 12px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.camera-presence-pill.camera-online {
  background: #dcfce7;
  color: #166534;
}

.camera-presence-pill.camera-offline {
  background: #fee2e2;
  color: #991b1b;
}

:global(html.dark-theme) .exam-protection-notice,
:global(html.dark-theme) .fullscreen-required {
  background: #252b35;
  border-color: #475569;
  color: #dbeafe;
}

:global(html.dark-theme) .exam-preparing {
  border-color: #475569;
  background: #252b35;
  color: #e2e8f0;
}

.exam-header,
.notice,
.state-card,
.locked-card {
  max-width: 1350px;
  margin-left: auto;
  margin-right: auto;
}

.exam-wrap {
  width: 100%;
  max-width: 1800px;
  margin: 0 auto;
}

.exam-workspace {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 340px;
  align-items: start;
  gap: 20px;
}

.exam-workspace-preparing {
  grid-template-columns: minmax(0, 1fr);
}

.exam-main,
.exam-sidebar {
  min-width: 0;
}

.exam-sidebar {
  display: grid;
  align-content: start;
  gap: 16px;
}

.camera-panel {
  min-width: 0;
}

.camera-panel-paused {
  display: none;
}

.sidebar-heading {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.sidebar-heading span {
  color: #64748b;
  font-size: 11px;
}

.monitor-sync-notice,
.camera-off-notice {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px 14px;
  margin-bottom: 14px;
  padding: 11px 14px;
  border: 1px solid #fcd34d;
  border-radius: 10px;
  background: #fffbeb;
  color: #854d0e;
  font-size: 12px;
  line-height: 1.5;
}

.camera-off-notice strong {
  white-space: nowrap;
}

.monitor-sync-notice button {
  border: 0;
  background: transparent;
  color: #1d4ed8;
  font-weight: 700;
  cursor: pointer;
}

.monitor-box summary {
  cursor: pointer;
  font-size: 13px;
  font-weight: 700;
}

.monitor-box[open] summary {
  margin-bottom: 12px;
}

.monitor-box p {
  margin: 8px 0;
}

:global(html.dark-theme) .monitor-sync-notice,
:global(html.dark-theme) .camera-off-notice {
  border-color: #854d0e;
  background: #292117;
  color: #fcd34d;
}

/* HEADER */
.exam-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  margin-bottom: 23px;
}

.eyebrow {
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 1px;
}

.exam-header h1 {
  margin: 9px 0;
  font-size: 28px;
  font-weight: 800;
}

.exam-header p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.7;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 14px;
}

.save-pill {
  color: #64748b;
  font-size: 12px;
  white-space: nowrap;
}

.timer {
  padding: 12px 15px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  font-size: 21px;
  white-space: nowrap;
}

.timer.danger,
.text-danger {
  color: #dc2626;
}

/* MONITOR BANNER */
.monitor-banner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  padding: 18px 20px;
  margin-bottom: 20px;
  border: 1px solid #bfdbfe;
  border-radius: 14px;
  background: #eff6ff;
}

.monitor-banner strong {
  color: #1d4ed8;
  font-size: 14px;
}

.monitor-banner p {
  max-width: 850px;
  margin: 8px 0 0;
  color: #475569;
  font-size: 12px;
  line-height: 1.8;
}

.violation-badge {
  padding: 10px 15px;
  border-radius: 999px;
  background: #dbeafe;
  color: #1d4ed8;
  font-size: 12px;
  font-weight: 800;
  white-space: nowrap;
}

.violation-badge.warning-level {
  background: #fee2e2;
  color: #b91c1c;
}

/* OVERVIEW */
.overview {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-bottom: 20px;
  border: 1px solid #e2e8f0;
  border-radius: 15px;
  background: #fff;
}

.overview>div {
  display: flex;
  flex-direction: column;
  gap: 7px;
  min-width: 0;
  padding: 20px;
  border-right: 1px solid #e2e8f0;
}

.overview>div:last-child {
  border-right: 0;
}

.overview small {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
}

.overview strong {
  font-size: 17px;
  overflow-wrap: anywhere;
}

.question-card,
.sidebar {
  padding: 26px;
  border: 1px solid #e2e8f0;
  border-radius: 17px;
  background: #fff;
}

.question-card {
  min-height: 420px;
}

.question-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  color: #64748b;
  font-size: 13px;
  font-weight: 800;
}

.point {
  padding: 7px 12px;
  border-radius: 20px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
}

.question-card h2 {
  margin: 30px 0;
  font-size: 21px;
  font-weight: 800;
  line-height: 1.7;
}

/* ANSWERS */
.answers {
  display: grid;
  gap: 13px;
}

.answer {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 15px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  line-height: 1.6;
  transition: 0.18s;
}

.answer:hover {
  border-color: #93c5fd;
}

.answer.selected {
  border-color: #2563eb;
  background: #eff6ff;
}

.answer input {
  accent-color: #2563eb;
}

.letter {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 35px;
  height: 35px;
  border-radius: 9px;
  background: #f1f5f9;
  font-weight: 800;
}

.question-card textarea {
  width: 100%;
  padding: 15px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  outline: none;
  resize: vertical;
  background: #fff;
  color: #172033;
  font: inherit;
  line-height: 1.8;
}

.question-card textarea:focus {
  border-color: #2563eb;
}

/* NAV */
.question-nav {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  margin-top: 35px;
  padding-top: 22px;
  border-top: 1px solid #e2e8f0;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 16px;
  border: none;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pale {
  background: #f1f5f9;
  color: #334155;
}

.primary {
  background: #2563eb;
  color: #fff;
}

.primary:hover:not(:disabled) {
  background: #1d4ed8;
}

/* SIDEBAR */
.sidebar h3 {
  margin: 0 0 8px;
  font-size: 16px;
}

.muted {
  color: #64748b;
  font-size: 12px;
  line-height: 1.7;
}

.question-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 8px;
  margin: 20px 0;
}

.q-button {
  aspect-ratio: 1;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  background: #fff;
  color: #334155;
  font-weight: 800;
  cursor: pointer;
}

.q-button.answered {
  border-color: #bbf7d0;
  background: #dcfce7;
  color: #15803d;
}

.q-button.active {
  border-color: #2563eb;
  background: #2563eb;
  color: #fff;
}

/* PROGRESS */
.progress {
  margin: 25px 0;
}

.progress-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 11px;
  color: #475569;
  font-size: 12px;
}

.progress-track {
  height: 9px;
  overflow: hidden;
  border-radius: 10px;
  background: #e2e8f0;
}

.progress-fill {
  height: 100%;
  background: #22c55e;
  transition: width 0.2s;
}

/* MONITOR BOX */
.monitor-box {
  padding: 16px;
  border-radius: 12px;
  background: #eff6ff;
}

.monitor-box>strong {
  color: #1d4ed8;
  font-size: 13px;
}

.monitor-box p {
  color: #475569;
  font-size: 11px;
  line-height: 1.8;
}

.violation-list {
  display: grid;
  gap: 8px;
  margin-top: 14px;
}

.violation-item {
  display: flex;
  justify-content: space-between;
  gap: 8px;
  padding: 10px;
  border-radius: 8px;
  background: #fff;
  color: #64748b;
  font-size: 10px;
}

.violation-item.violated {
  background: #fff7ed;
  color: #c2410c;
  font-weight: 800;
}

.submit-button {
  width: 100%;
  margin-top: 18px;
  background: #dc2626;
  color: #fff;
}

.submit-button:hover:not(:disabled) {
  background: #b91c1c;
}

/* NOTICE */
.notice {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 20px;
  padding: 15px;
  border-radius: 11px;
  font-size: 12px;
}

.error-notice {
  border: 1px solid #fecaca;
  background: #fef2f2;
  color: #991b1b;
}

.notice button {
  border: none;
  background: transparent;
  color: inherit;
  font-weight: 800;
  cursor: pointer;
}

/* EMPTY / LOCKED */
.state-card,
.locked-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 13px;
  padding: 55px 25px;
  border: 1px solid #e2e8f0;
  border-radius: 17px;
  background: #fff;
  text-align: center;
}

.state-card p,
.locked-card p {
  max-width: 520px;
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.8;
}

.locked-card {
  border-color: #fecaca;
}

.locked-card h2 {
  margin: 5px 0;
  color: #dc2626;
  font-size: 25px;
}

.locked-icon,
.state-icon {
  font-size: 45px;
}

.locked-details {
  display: flex;
  justify-content: space-between;
  gap: 25px;
  width: min(450px, 100%);
  padding: 16px;
  border-radius: 10px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 12px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* MODAL */
.overlay {
  position: fixed;
  inset: 0;
  z-index: 1000;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(3px);
}

.modal {
  width: min(460px, 100%);
  padding: 30px;
  border-radius: 18px;
  background: #fff;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.modal-icon {
  font-size: 45px;
}

.modal h2 {
  margin: 12px 0;
  color: #172033;
  font-size: 22px;
}

.modal p {
  color: #64748b;
  font-size: 13px;
  line-height: 1.8;
}

.modal-actions {
  display: flex;
  gap: 12px;
  margin-top: 25px;
}

.modal-actions .btn {
  flex: 1;
}

.warning-modal {
  border-top: 5px solid #f59e0b;
}

.warning-number {
  display: inline-block;
  margin: 12px 0;
  padding: 13px 26px;
  border-radius: 14px;
  background: #fef3c7;
  color: #b45309;
  font-size: 30px;
  font-weight: 900;
}

.full-width {
  width: 100%;
  margin-top: 15px;
}

/* DARK MODE */
:global(html.dark-theme) .exam-page {
  background: #1f2124;
  color: #f8fafc;
}

:global(html.dark-theme) .timer,
:global(html.dark-theme) .overview,
:global(html.dark-theme) .question-card,
:global(html.dark-theme) .sidebar,
:global(html.dark-theme) .state-card,
:global(html.dark-theme) .locked-card,
:global(html.dark-theme) .modal {
  background: #111;
  color: #f8fafc;
  border-color: #334155;
}

:global(html.dark-theme) .modal h2 {
  color: #fff;
}

:global(html.dark-theme) .answer,
:global(html.dark-theme) .question-card textarea,
:global(html.dark-theme) .q-button {
  background: #202327;
  color: #f8fafc;
  border-color: #334155;
}

:global(html.dark-theme) .answer.selected,
:global(html.dark-theme) .q-button.active {
  background: #1d4ed8;
  border-color: #3b82f6;
  color: #fff;
}

:global(html.dark-theme) .monitor-banner,
:global(html.dark-theme) .monitor-box {
  background: #1e293b;
  border-color: #334155;
}

:global(html.dark-theme) .monitor-banner p,
:global(html.dark-theme) .monitor-box p {
  color: #cbd5e1;
}

:global(html.dark-theme) .violation-item {
  background: #202327;
  color: #cbd5e1;
}

:global(html.dark-theme) .violation-item.violated {
  background: #45240c;
  color: #fdba74;
}

:global(html.dark-theme) .locked-details {
  background: #3f1515;
}

:global(html.dark-theme) .pale {
  background: #30343b;
  color: #fff;
}

/* HỘP THOẠI XÁC NHẬN NỘP BÀI */
.exam-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 2147483000;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 20px;
  background: rgba(0, 0, 0, 0.72);
  backdrop-filter: blur(3px);
}

.exam-modal-content {
  position: relative;
  z-index: 1;

  width: min(460px, 100%);
  max-height: 90vh;
  overflow-y: auto;

  padding: 30px;
  border-radius: 18px;

  background: #ffffff;
  color: #172033;

  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
}

.exam-modal-icon {
  font-size: 44px;
}

.exam-modal-content h2 {
  margin: 14px 0;
  font-size: 22px;
  font-weight: 800;
}

.exam-modal-content p {
  margin: 12px 0;
  font-size: 13px;
  line-height: 1.8;
}

.exam-modal-warning {
  color: #dc2626;
}

.exam-modal-actions {
  display: flex;
  gap: 12px;
  margin-top: 25px;
}

.exam-modal-cancel,
.exam-modal-submit {
  flex: 1;
  padding: 12px 16px;
  border: none;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.exam-modal-cancel {
  background: #e2e8f0;
  color: #334155;
}

.exam-modal-submit {
  background: #2563eb;
  color: white;
}

.exam-modal-cancel:disabled,
.exam-modal-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* DARK MODE */
:global(html.dark-theme) .exam-modal-content {
  background: #17191d;
  color: #ffffff;
  border: 1px solid #334155;
}

:global(html.dark-theme) .exam-modal-content h2 {
  color: #ffffff;
}

:global(html.dark-theme) .exam-modal-cancel {
  background: #30343b;
  color: #ffffff;
}

@media (max-width: 500px) {
  .exam-modal-content {
    padding: 22px;
  }

  .exam-modal-actions {
    flex-direction: column;
  }
}


/* RESPONSIVE */
@media (max-width: 920px) {
  .exam-workspace {
    grid-template-columns: minmax(0, 1fr);
  }

  .camera-panel {
    width: min(100%, 420px);
  }

  .overview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .overview>div:nth-child(2) {
    border-right: 0;
  }

  .overview>div:nth-child(-n+2) {
    border-bottom: 1px solid #e2e8f0;
  }
}

@media (max-width: 650px) {
  .exam-page {
    padding: 15px;
  }

  .exam-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .exam-header h1 {
    font-size: 23px;
  }

  .header-actions {
    width: 100%;
    justify-content: space-between;
  }

  .overview {
    grid-template-columns: 1fr 1fr;
  }

  .overview>div {
    padding: 14px;
  }

  .question-card,
  .sidebar {
    padding: 18px;
  }

  .monitor-banner {
    flex-direction: column;
    align-items: flex-start;
  }

  .question-card h2 {
    font-size: 18px;
  }

  .modal-actions {
    flex-direction: column;
  }

  .question-nav {
    flex-wrap: wrap;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* SỐ LẦN CẢNH BÁO CAMERA */
.camera-warning-counter {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  padding: 14px 18px;
  border: 1px solid #fed7aa;
  border-radius: 12px;
  background: #fff7ed;
  color: #9a3412;
}

.camera-warning-counter>div {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.camera-warning-counter small {
  color: #9a3412;
  font-size: 11px;
}

.camera-warning-counter>strong {
  font-size: 20px;
  white-space: nowrap;
}

.camera-warning-counter .danger {
  color: #dc2626;
}

:global(html.dark-theme) .camera-warning-counter {
  background: #392617;
  border-color: #92400e;
  color: #fdba74;
}

:global(html.dark-theme) .camera-warning-counter small {
  color: #fed7aa;
}

/* BỘ ĐẾM PHÁT HIỆN ĐIỆN THOẠI */
.phone-warning-counter {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  padding: 14px 18px;
  border: 1px solid #fca5a5;
  border-radius: 12px;
  background: #fff1f2;
  color: #9f1239;
}

.phone-warning-counter>div {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.phone-warning-counter small {
  font-size: 11px;
  color: #9f1239;
}

.phone-warning-counter>strong {
  font-size: 20px;
  white-space: nowrap;
}

.phone-warning-counter .danger {
  color: #dc2626;
}

:global(html.dark-theme) .phone-warning-counter {
  background: #3b1923;
  border-color: #9f1239;
  color: #fecdd3;
}

:global(html.dark-theme) .phone-warning-counter small {
  color: #fecdd3;
}

.webrtc-live-indicator {
  background: rgba(15, 23, 42, 0.9);
  color: #10b981;
  font-size: 11px;
  font-weight: 600;
  padding: 6px 10px;
  display: flex;
  align-items: center;
  gap: 6px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  text-align: center;
  justify-content: center;
}
.webrtc-live-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #10b981;
  animation: pulse-webrtc 1.2s infinite;
}
@keyframes pulse-webrtc {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.3; transform: scale(0.8); }
}

</style>
