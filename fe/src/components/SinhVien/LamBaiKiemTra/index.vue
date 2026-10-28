
<template>
  <div class="exam-page">
    <!-- Camera giám sát cố định ở góc trái trên. Không ghi video. -->
    <Teleport to="body">
      <div v-if="exam && cameraEnabled && !locked && !loading" class="exam-camera-floating">
        <GiamSatCamera
          @warning="handleCameraWarning"
          @normal="handleCameraNormal"
          @camera-state="handleCameraState"
        />
      </div>
    </Teleport>
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

        <strong
          class="timer"
          :class="{ danger: remainingSeconds <= 300 }"
        >
          ⏱ {{ formattedTime }}
        </strong>
      </div>
    </header>

    <!-- LỖI -->
    <div v-if="error" class="notice error-notice" role="alert">
      <span>{{ error }}</span>

      <button
        v-if="!locked"
        type="button"
        @click="reloadExam"
      >
        Thử lại
      </button>
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

      <p v-if="lockReason === 'camera'">
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

      <button
        type="button"
        class="btn primary"
        @click="goBack"
      >
        Quay về danh sách bài tập
      </button>
    </div>

    <!-- MẤT ĐỒNG BỘ GIÁM SÁT -->
    <div
      v-else-if="!monitorReady && exam"
      class="state-card"
    >
      <div class="state-icon">⚠️</div>

      <h2>Tạm dừng bài kiểm tra</h2>

      <p>
        Không thể xác minh trạng thái giám sát.
        Vui lòng kiểm tra kết nối và thử lại.
      </p>

      <button
        type="button"
        class="btn primary"
        @click="resumeMonitoring"
      >
        Kết nối lại
      </button>
    </div>

    <!-- NỘI DUNG BÀI THI -->
    <div
      v-else-if="exam && monitorReady"
      class="exam-wrap"
    >
      <!-- Yêu cầu bật camera mới được làm bài -->
      <div v-if="!cameraReady" class="camera-required" role="alert">
        <div class="camera-required-icon">📷</div>
        <h2>Vui lòng bật camera để làm bài kiểm tra</h2>
        <p>
          Nhấn <strong>Bật camera và bắt đầu giám sát</strong> ở góc
          trên bên trái. Sau khi camera hoạt động, câu hỏi sẽ hiển thị.
          Thời gian bài thi vẫn tiếp tục đếm.
        </p>
      </div>

      <template v-else>
      <!-- CẢNH BÁO GIÁM SÁT -->
      <div class="monitor-banner">
        <div>
          <strong>◉ Giám sát bài kiểm tra</strong>

          <p>
            Không chuyển sang tab khác hoặc thu nhỏ trình duyệt.
            Mỗi lần rời tab được gửi về hệ thống.
            Sau 3 lần rời tab hoặc 5 cảnh báo camera,
            bài kiểm tra có thể bị khóa.
          </p>
        </div>

        <span
          class="violation-badge"
          :class="{
            'warning-level': warningCount >= 2
          }"
        >
          {{ warningCount }}/3 vi phạm
        </span>
      </div>

      <!-- THỐNG KÊ CẢNH BÁO CAMERA -->
      <div class="camera-warning-counter" role="status">
        <div>
          <strong>📷 Cảnh báo camera</strong>
          <small>Không thấy khuôn mặt, nhiều khuôn mặt hoặc camera bị ngắt</small>
        </div>
        <strong :class="{ danger: cameraWarningCount >= 4 }">
          {{ cameraWarningCount }}/5
        </strong>
      </div>

      <!-- THỐNG KÊ -->
      <section class="overview">
        <div>
          <small>SINH VIÊN</small>
          <strong>{{ studentName }}</strong>
        </div>

        <div>
          <small>SỐ CÂU HỎI</small>
          <strong>{{ questions.length }}</strong>
        </div>

        <div>
          <small>ĐÃ TRẢ LỜI</small>
          <strong>
            {{ answeredCount }}/{{ questions.length }}
          </strong>
        </div>

        <div>
          <small>SỐ LẦN RỜI TAB</small>
          <strong
            :class="{ 'text-danger': warningCount >= 2 }"
          >
            {{ warningCount }}/3
          </strong>
        </div>
      </section>

      <!-- KHÔNG CÓ CÂU HỎI -->
      <div
        v-if="questions.length === 0"
        class="state-card"
      >
        <div class="state-icon">📋</div>
        <h3>Bài kiểm tra chưa có câu hỏi</h3>
        <p>Vui lòng liên hệ giảng viên.</p>
      </div>

      <!-- LAYOUT -->
      <div v-else class="exam-layout">
        <!-- CÂU HỎI -->
        <main class="question-card">
          <div class="question-meta">
            <span>
              Câu {{ currentIndex + 1 }}/{{ questions.length }}
            </span>

            <span class="point">
              {{ currentQuestion?.diem ?? 0 }} điểm
            </span>
          </div>

          <h2>
            {{ currentQuestion?.noi_dung }}
          </h2>

          <!-- TRẮC NGHIỆM -->
          <div
            v-if="currentQuestion?.loai_cau_hoi === 'trac_nghiem'"
            class="answers"
          >
            <label
              v-for="(answer, index) in currentQuestion.dap_an"
              :key="answer.id"
              class="answer"
              :class="{
                selected:
                  String(answers[String(currentQuestion.id)]) ===
                  String(answer.id)
              }"
            >
              <input
                v-model="answers[String(currentQuestion.id)]"
                type="radio"
                :name="`question-${currentQuestion.id}`"
                :value="answer.id"
                :disabled="!canEdit"
              />

              <span class="letter">
                {{ String.fromCharCode(65 + index) }}
              </span>

              <span>{{ answer.noi_dung }}</span>
            </label>
          </div>

          <!-- TỰ LUẬN -->
          <textarea
            v-else-if="currentQuestion?.loai_cau_hoi === 'tu_luan'"
            v-model="answers[String(currentQuestion.id)]"
            :disabled="!canEdit"
            maxlength="20000"
            rows="9"
            placeholder="Nhập câu trả lời của bạn..."
          ></textarea>

          <p v-else class="muted">
            Loại câu hỏi không được hỗ trợ.
          </p>

          <!-- ĐIỀU HƯỚNG -->
          <div class="question-nav">
            <button
              type="button"
              class="btn pale"
              :disabled="currentIndex === 0"
              @click="currentIndex--"
            >
              ← Câu trước
            </button>

            <button
              v-if="currentIndex < questions.length - 1"
              type="button"
              class="btn primary"
              @click="currentIndex++"
            >
              Câu tiếp →
            </button>

            <button
              v-else
              type="button"
              class="btn primary"
              :disabled="!canEdit"
              @click="showConfirm = true"
            >
              Kiểm tra & nộp
            </button>
          </div>
        </main>

        <!-- SIDEBAR -->
        <aside class="sidebar">
          <h3>Danh sách câu hỏi</h3>

          <p class="muted">
            Chọn số câu để chuyển đến câu hỏi tương ứng.
          </p>

          <div class="question-grid">
            <button
              v-for="(question, index) in questions"
              :key="question.id"
              type="button"
              class="q-button"
              :class="{
                answered: isAnswered(question.id),
                active: index === currentIndex
              }"
              @click="currentIndex = index"
            >
              {{ index + 1 }}
            </button>
          </div>

          <!-- TIẾN ĐỘ -->
          <div class="progress">
            <div class="progress-header">
              <span>Tiến độ làm bài</span>
              <strong>{{ progress }}%</strong>
            </div>

            <div class="progress-track">
              <div
                class="progress-fill"
                :style="{ width: progress + '%' }"
              ></div>
            </div>
          </div>

          <!-- GIÁM SÁT -->
          <div class="monitor-box">
            <strong>🛡 Quy định giám sát</strong>

            <p>
              Rời tab 3 lần hoặc ghi nhận 5 cảnh báo camera
              sẽ khóa bài kiểm tra. Hai bộ đếm độc lập.
            </p>
            <p>📷 Cảnh báo camera: <strong>{{ cameraWarningCount }}/5</strong></p>

            <div class="violation-list">
              <div
                v-for="number in 3"
                :key="number"
                class="violation-item"
                :class="{
                  violated: warningCount >= number
                }"
              >
                <span>
                  {{ warningCount >= number ? "⚠" : "○" }}
                  Lần {{ number }}
                </span>

                <span>
                  {{
                    warningCount >= number
                      ? "Đã ghi nhận"
                      : "Chưa vi phạm"
                  }}
                </span>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="btn submit-button"
            :disabled="!canEdit"
            @click="showConfirm = true"
          >
            Nộp bài kiểm tra
          </button>
        </aside>
      </div>
      </template>
    </div>

    <!-- XÁC NHẬN NỘP -->
    
<!-- HỘP THOẠI XÁC NHẬN NỘP BÀI -->
<Teleport to="body">
  <div
    v-if="showConfirm && !locked"
    class="exam-modal-overlay"
    @click.self="showConfirm = false"
  >
    <div
      class="exam-modal-content"
      role="dialog"
      aria-modal="true"
      aria-labelledby="submit-dialog-title"
    >
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

      <div class="exam-modal-actions">
        <button
          type="button"
          class="exam-modal-cancel"
          :disabled="submitting"
          @click="showConfirm = false"
        >
          Tiếp tục làm
        </button>

        <button
          type="button"
          class="exam-modal-submit"
          :disabled="submitting || !canEdit"
          @click="submitExam(false)"
        >
          {{
            submitting
              ? "Đang nộp..."
              : "Xác nhận nộp bài"
          }}
        </button>
      </div>
    </div>
  </div>
</Teleport>


    <!-- POPUP CẢNH BÁO -->
    <div
      v-if="showWarning && !locked"
      class="overlay"
    >
      <div
        class="modal warning-modal"
        role="alertdialog"
        aria-modal="true"
      >
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

        <button
          type="button"
          class="btn primary full-width"
          @click="showWarning = false"
        >
          Tôi đã hiểu
        </button>
      </div>
    </div>

    <!-- THÔNG BÁO KHÓA -->
    <div
      v-if="showLockNotice"
      class="overlay"
    >
      <div
        class="modal warning-modal"
        role="alertdialog"
        aria-modal="true"
      >
        <div class="modal-icon">🔒</div>
        <h2>Bài kiểm tra bị khóa!</h2>

        <p v-if="lockReason === 'camera'">
          Đã ghi nhận 5 cảnh báo camera.
          Hệ thống đã khóa bài kiểm tra.
        </p>
        <p v-else>
          Bạn đã rời tab 3 lần. Hệ thống đã khóa
          bài kiểm tra và không cho phép làm tiếp.
        </p>

        <button
          type="button"
          class="btn primary full-width"
          @click="showLockNotice = false"
        >
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
  onMounted,
  onBeforeUnmount,
} from "vue"

import {
  useRoute,
  useRouter,
} from "vue-router"

import api from "../../../services/api"
import GiamSatCamera from "./GiamSatCamera.vue"

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
const answers = ref({})
const currentIndex = ref(0)

const loading = ref(false)
const submitting = ref(false)
const error = ref("")
const saveStatus = ref("Chưa có thay đổi")

const remainingSeconds = ref(0)
const serverOffset = ref(0)
const studentName = ref("Sinh viên")

const warningCount = ref(0)
const cameraWarningCount = ref(0)
const lockReason = ref("")
let cameraQueue = []
let cameraRequest = null
const locked = ref(false)
const monitorReady = ref(false)
const cameraEnabled = ref(false)
const cameraReady = ref(false)

const showWarning = ref(false)
const showLockNotice = ref(false)
const showConfirm = ref(false)

let deadlineMs = 0
let timerInterval = null
let saveTimer = null
let saveInFlight = null

let loaded = false
let submitted = false
let submittingAtDeadline = false
let monitoringRequest = null
let eventQueue = []
let hiddenEventRecorded = false
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
  if (!cameraReady.value) {
    showConfirm.value = false
    clearTimeout(saveTimer)
  } else if (canEdit.value && saveStatus.value === "Chưa lưu") {
    clearTimeout(saveTimer)
    saveTimer = setTimeout(saveDraft, 700)
  }
}

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
  }

  const loaiCanhBao = types[event.type]

  if (!loaiCanhBao) return

  cameraQueue.push({
    eventId: createEventId(),
    loaiCanhBao,
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
        event
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

      cameraWarningCount.value = Math.max(
        cameraWarningCount.value,
        Number(data.soLanCamera ?? 0)
      )

      if (data.biKhoa === true) {
        lockReason.value =
          data.lyDoKhoa ||
          (cameraWarningCount.value >= 5
            ? "camera"
            : "roi_tab")

        applyLocked()
        return
      }
    }
  })().finally(() => {
    cameraRequest = null
  })

  return cameraRequest
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
  remainingSeconds.value > 0
)

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
  if (!exam.value || locked.value || submitted) {
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
  return `/sinh-vien/bai-kiem-tra/${
    encodeURIComponent(baiTapId.value)
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

  if (lockReason.value === "camera") {
    cameraWarningCount.value = Math.max(
      5,
      cameraWarningCount.value
    )
  } else {
    warningCount.value = Math.max(
      3,
      warningCount.value
    )
  }

  showConfirm.value = false
  showWarning.value = false
  showLockNotice.value = exam.value !== null

  clearInterval(timerInterval)
  clearTimeout(saveTimer)

  saveStatus.value = "Bài kiểm tra đã khóa"
}



function applyViolationState(data) {
  const tabCount = Number(data?.soLanViPham ?? 0)
  // Một số API rời tab cũ chỉ trả về soLanViPham;
  // khi thiếu soLanCamera giữ nguyên giá trị đã đồng bộ trước đó.
  const camCount = Number(data?.soLanCamera ?? cameraWarningCount.value)

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

  if (
    data?.biKhoa === true ||
    warningCount.value >= 3 ||
    cameraWarningCount.value >= 5
  ) {
    lockReason.value =
      data?.lyDoKhoa ||
      (cameraWarningCount.value >= 5
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
    /kh[oó]a|rời tab|vi phạm|camera/i.test(message)
  ) {
    if (/camera/i.test(message)) {
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

  if (
    response.data?.status === false ||
    response.data?.success === false
  ) {
    throw new Error(
      response.data?.message ||
      "Không xác minh được trạng thái giám sát."
    )
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
  if (document.hidden) {
    if (
      !loaded ||
      submitted ||
      locked.value ||
      hiddenEventRecorded
    ) {
      return
    }

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

  hiddenEventRecorded = false

  if (
    !loaded ||
    submitted ||
    locked.value
  ) {
    return
  }

  resumeMonitoring()
}

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
  monitorReady.value = false
  loaded = false
  exam.value = null
  cameraEnabled.value = false

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
async function saveDraft() {
  if (
    !loaded ||
    submitted ||
    locked.value ||
    !monitorReady.value ||
    !cameraReady.value ||
    submitting.value ||
    remainingSeconds.value <= 0
  ) {
    return
  }

  // Không cho hai request ghi nháp chạy song song.
  if (saveInFlight) {
    await saveInFlight

    if (!canEdit.value) return
  }

  const version = saveVersion
  const payload = cloneAnswers()

  saveStatus.value = "Đang lưu..."

  const request = api.put(
    endpoint("/nhap"),
    { answers: payload }
  )

  saveInFlight = request

  try {
    await request

    saveStatus.value =
      saveVersion === version
        ? "✓ Đã lưu bài nháp"
        : "Có thay đổi chưa lưu"

    if (saveVersion !== version && canEdit.value) {
      clearTimeout(saveTimer)
      saveTimer = setTimeout(saveDraft, 700)
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
    locked.value ||
    !exam.value ||
    !loaded
  ) {
    return
  }

  if (!auto && !canEdit.value) {
    return
  }

  showConfirm.value = false
  clearTimeout(saveTimer)

  submitting.value = true
  error.value = ""

  try {
    // Nếu có request lưu nháp đang chạy,
    // cho request đó kết thúc trước.
    if (saveInFlight) {
      await saveInFlight
    }

    // Không nộp khi còn cảnh báo camera chưa xác minh.
    await flushCameraQueue()
    if (locked.value) return
    await flushViolationQueue()

    const allowed = await fetchViolationState()

    if (!allowed || locked.value) {
      return
    }

    // Nếu đang tự nộp do hết giờ, chỉ thực hiện
    // khi đã đồng bộ được trạng thái giám sát.
    const response = await api.post(
      endpoint("/nop"),
      {
        answers: cloneAnswers(),
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
    cameraEnabled.value = false
    loaded = false
    monitorReady.value = false

    clearInterval(timerInterval)

    window.alert(
      auto
        ? "Đã hết giờ. Nộp bài thành công."
        : "Nộp bài kiểm tra thành công."
    )

    router.push("/sinh-vien/bai-tap")
  } catch (err) {
    error.value =
      "Không thể nộp bài: " + handleApiError(err)

    monitorReady.value = false
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

  loadExam()
})

onBeforeUnmount(() => {
  disposed = true
  cameraEnabled.value = false
  cameraReady.value = false
  loaded = false
  requestGeneration++

  clearInterval(timerInterval)
  clearTimeout(saveTimer)

  document.removeEventListener(
    "visibilitychange",
    handleVisibilityChange
  )
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

.exam-header,
.exam-wrap,
.notice,
.state-card,
.locked-card {
  max-width: 1350px;
  margin-left: auto;
  margin-right: auto;
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

.overview > div {
  display: flex;
  flex-direction: column;
  gap: 7px;
  min-width: 0;
  padding: 20px;
  border-right: 1px solid #e2e8f0;
}

.overview > div:last-child {
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

/* LAYOUT */
.exam-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 310px;
  align-items: start;
  gap: 20px;
}

.question-card,
.sidebar {
  padding: 26px;
  border: 1px solid #e2e8f0;
  border-radius: 17px;
  background: #fff;
}

.question-card {
  min-height: 500px;
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

.monitor-box > strong {
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
  .exam-layout {
    grid-template-columns: 1fr;
  }

  .sidebar {
    order: -1;
  }

  .overview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .overview > div:nth-child(2) {
    border-right: 0;
  }

  .overview > div:nth-child(-n+2) {
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

  .overview > div {
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
.camera-warning-counter > div { display: flex; flex-direction: column; gap: 5px; }
.camera-warning-counter small { color: #9a3412; font-size: 11px; }
.camera-warning-counter > strong { font-size: 20px; white-space: nowrap; }
.camera-warning-counter .danger { color: #dc2626; }
:global(html.dark-theme) .camera-warning-counter {
  background: #392617;
  border-color: #92400e;
  color: #fdba74;
}
:global(html.dark-theme) .camera-warning-counter small { color: #fed7aa; }

/* YÊU CẦU CAMERA TRƯỚC KHI LÀM BÀI */
.camera-required {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  padding: 40px 25px;
  border: 1px solid #fbbf24;
  border-radius: 16px;
  background: #fffbeb;
  color: #92400e;
  text-align: center;
}
.camera-required-icon { font-size: 46px; }
.camera-required h2 { font-size: 22px; margin: 12px 0; }
.camera-required p { max-width: 550px; font-size: 13px; line-height: 1.8; }
:global(html.dark-theme) .camera-required {
  background: #292117;
  border-color: #854d0e;
  color: #fcd34d;
}

/* CAMERA CỐ ĐỊNH GÓC TRÊN BÊN TRÁI */
:global(.exam-camera-floating) {
  position: fixed;
  top: 12px;
  left: 12px;
  z-index: 900;
  width: 260px;
  max-width: calc(100vw - 24px);
  max-height: calc(100vh - 24px);
  overflow-y: auto;
  overflow-x: hidden;
  border-radius: 15px;
  box-shadow: 0 12px 35px rgba(0,0,0,.4);
}
@media (max-width: 650px) {
  :global(.exam-camera-floating) {
    top: 8px;
    left: 8px;
    width: 190px;
    max-height: 65vh;
  }
}

</style>
