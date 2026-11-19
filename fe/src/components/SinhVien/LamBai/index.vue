<template>
  <div class="exam-page">

    <!-- ================= HEADER ================= -->
    <header class="exam-header">
      <div class="exam-title">
        <span class="subject">LẬP TRÌNH WEB</span>
        <h1>Kiểm tra giữa kỳ</h1>
      </div>

      <div class="header-right">
        <div class="monitor-status">
          <span class="status-dot"></span>
          Đang giám sát
        </div>

        <div class="timer" :class="{ danger: remainingSeconds <= 300 }">
          <span class="timer-icon">⏱</span>
          {{ formattedTime }}
        </div>
      </div>
    </header>

    <!-- ================= THÔNG TIN ================= -->
    <section class="exam-info">
      <div>
        <span>Sinh viên</span>
        <strong>{{ studentName }}</strong>
      </div>

      <div>
        <span>Số câu</span>
        <strong>{{ questions.length }} câu</strong>
      </div>

      <div>
        <span>Đã trả lời</span>
        <strong>{{ answeredCount }}/{{ questions.length }}</strong>
      </div>

      <div>
        <span>Cảnh báo</span>
        <strong class="warning-text">{{ warningCount }}</strong>
      </div>
    </section>

    <!-- ================= MAIN ================= -->
    <div class="exam-layout">

      <!-- ================= CÂU HỎI ================= -->
      <main class="question-panel" v-if="currentQuestion">

        <div class="question-top">
          <div>
            <span class="question-number">
              Câu {{ currentIndex + 1 }}
            </span>

            <span class="question-total">
              / {{ questions.length }}
            </span>
          </div>

          <span class="question-score">
            {{ currentQuestion.diem }} điểm
          </span>
        </div>

        <div class="question-content">
          {{ currentQuestion.noi_dung }}
        </div>

        <!-- TRẮC NGHIỆM -->
        <div
          v-if="currentQuestion.loai_cau_hoi === 'trac_nghiem'"
          class="answers"
        >
          <label
            v-for="(answer, index) in currentQuestion.dap_an"
            :key="answer.id"
            class="answer-item"
            :class="{
              selected:
                studentAnswers[currentQuestion.id] === answer.id
            }"
          >
            <input
              type="radio"
              :name="'question-' + currentQuestion.id"
              :value="answer.id"
              v-model="studentAnswers[currentQuestion.id]"
            />

            <span class="answer-letter">
              {{ getLetter(index) }}
            </span>

            <span class="answer-content">
              {{ answer.noi_dung }}
            </span>
          </label>
        </div>

        <!-- TỰ LUẬN -->
        <div
          v-else-if="currentQuestion.loai_cau_hoi === 'tu_luan'"
          class="essay-area"
        >
          <textarea
            v-model="studentAnswers[currentQuestion.id]"
            placeholder="Nhập câu trả lời của bạn..."
          ></textarea>
        </div>

        <!-- ================= NAVIGATION ================= -->
        <div class="question-navigation">

          <button
            class="nav-btn secondary"
            :disabled="currentIndex === 0"
            @click="previousQuestion"
          >
            ← Câu trước
          </button>

          <button
            v-if="currentIndex < questions.length - 1"
            class="nav-btn primary"
            @click="nextQuestion"
          >
            Câu sau →
          </button>

          <button
            v-else
            class="nav-btn primary"
            @click="showSubmitConfirm = true"
          >
            Hoàn thành
          </button>

        </div>
      </main>

      <!-- ================= DANH SÁCH CÂU ================= -->
      <aside class="question-sidebar">

        <h3>Danh sách câu hỏi</h3>

        <div class="legend">
          <span>
            <i class="legend-box answered"></i>
            Đã làm
          </span>

          <span>
            <i class="legend-box"></i>
            Chưa làm
          </span>
        </div>

        <div class="question-grid">
          <button
            v-for="(question, index) in questions"
            :key="question.id"
            class="question-button"
            :class="{
              active: index === currentIndex,
              answered: isAnswered(question.id)
            }"
            @click="goToQuestion(index)"
          >
            {{ index + 1 }}
          </button>
        </div>

        <div class="progress-box">
          <div class="progress-info">
            <span>Tiến độ</span>

            <strong>
              {{ progressPercentage }}%
            </strong>
          </div>

          <div class="progress-bar">
            <div
              class="progress-value"
              :style="{ width: progressPercentage + '%' }"
            ></div>
          </div>
        </div>

        <!-- ================= GIÁM SÁT ================= -->
        <div class="monitor-box">
          <div class="monitor-title">
            <span class="monitor-dot"></span>
            Giám sát đang hoạt động
          </div>

          <p>
            Không rời khỏi trang kiểm tra trong quá trình làm bài.
          </p>

          <div
            v-if="warningCount > 0"
            class="warning-box"
          >
            ⚠ Bạn đã có
            <strong>{{ warningCount }}</strong>
            cảnh báo.
          </div>
        </div>

        <button
          class="submit-btn"
          @click="showSubmitConfirm = true"
        >
          Nộp bài
        </button>

      </aside>
    </div>

    <!-- ================= CẢNH BÁO RỜI TAB ================= -->
    <div
      v-if="showWarning"
      class="warning-toast"
    >
      <div class="warning-icon">⚠</div>

      <div>
        <strong>Cảnh báo</strong>
        <p>
          Hệ thống phát hiện bạn vừa rời khỏi trang kiểm tra.
        </p>
      </div>

      <button @click="showWarning = false">
        ×
      </button>
    </div>

    <!-- ================= MODAL NỘP BÀI ================= -->
    <div
      v-if="showSubmitConfirm"
      class="modal-overlay"
      @click.self="showSubmitConfirm = false"
    >
      <div class="submit-modal">

        <div class="modal-icon">✓</div>

        <h2>Xác nhận nộp bài</h2>

        <p>
          Bạn đã hoàn thành
          <strong>
            {{ answeredCount }}/{{ questions.length }}
          </strong>
          câu hỏi.
        </p>

        <div
          v-if="answeredCount < questions.length"
          class="unfinished-warning"
        >
          ⚠ Bạn vẫn còn
          <strong>
            {{ questions.length - answeredCount }}
          </strong>
          câu chưa trả lời.
        </div>

        <p class="submit-note">
          Sau khi nộp bài, bạn sẽ không thể thay đổi câu trả lời.
        </p>

        <div class="modal-actions">
          <button
            class="cancel-btn"
            @click="showSubmitConfirm = false"
          >
            Tiếp tục làm bài
          </button>

          <button
            class="confirm-btn"
            @click="submitExam"
          >
            Nộp bài
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<script setup>
import {
  computed,
  onBeforeUnmount,
  onMounted,
  ref
} from "vue"


/* =====================================================
   THÔNG TIN SINH VIÊN
===================================================== */

const studentName = ref("Sinh viên")


function loadStudent() {
  const raw =
    sessionStorage.getItem("auth_user") ||
    localStorage.getItem("auth_user") ||
    sessionStorage.getItem("nguoi_dung") ||
    localStorage.getItem("nguoi_dung")

  if (!raw) return

  try {
    const user = JSON.parse(raw)

    studentName.value =
      user.ho_ten || "Sinh viên"
  } catch (error) {
    console.error(
      "Không thể đọc thông tin sinh viên:",
      error
    )
  }
}


/* =====================================================
   CÂU HỎI
   Dữ liệu mẫu để test giao diện.
   Sau này thay bằng API.
===================================================== */

const questions = ref([
  {
    id: 1,
    noi_dung:
      "HTTP là viết tắt của cụm từ nào sau đây?",
    loai_cau_hoi: "trac_nghiem",
    diem: 1,
    dap_an: [
      {
        id: 1,
        noi_dung:
          "HyperText Transfer Protocol"
      },
      {
        id: 2,
        noi_dung:
          "High Transfer Text Protocol"
      },
      {
        id: 3,
        noi_dung:
          "Hyper Transfer Technology Protocol"
      },
      {
        id: 4,
        noi_dung:
          "High Text Transfer Program"
      }
    ]
  },

  {
    id: 2,
    noi_dung:
      "Trong Laravel, Route dùng để làm gì?",
    loai_cau_hoi: "trac_nghiem",
    diem: 1,
    dap_an: [
      {
        id: 5,
        noi_dung:
          "Kết nối URL với Controller hoặc xử lý request"
      },
      {
        id: 6,
        noi_dung:
          "Tạo database"
      },
      {
        id: 7,
        noi_dung:
          "Tạo CSS"
      },
      {
        id: 8,
        noi_dung:
          "Quản lý file hình ảnh"
      }
    ]
  },

  {
    id: 3,
    noi_dung:
      "Hãy giải thích ngắn gọn mô hình MVC.",
    loai_cau_hoi: "tu_luan",
    diem: 2,
    dap_an: []
  },

  {
    id: 4,
    noi_dung:
      "Trong mô hình MVC, Controller có nhiệm vụ gì?",
    loai_cau_hoi: "trac_nghiem",
    diem: 1,
    dap_an: [
      {
        id: 9,
        noi_dung:
          "Xử lý request và điều phối dữ liệu"
      },
      {
        id: 10,
        noi_dung:
          "Chỉ lưu dữ liệu"
      },
      {
        id: 11,
        noi_dung:
          "Chỉ hiển thị giao diện"
      },
      {
        id: 12,
        noi_dung:
          "Quản lý CSS"
      }
    ]
  },

  {
    id: 5,
    noi_dung:
      "MySQL thuộc loại hệ quản trị cơ sở dữ liệu nào?",
    loai_cau_hoi: "trac_nghiem",
    diem: 1,
    dap_an: [
      {
        id: 13,
        noi_dung:
          "Cơ sở dữ liệu quan hệ"
      },
      {
        id: 14,
        noi_dung:
          "Cơ sở dữ liệu đồ thị"
      },
      {
        id: 15,
        noi_dung:
          "Cơ sở dữ liệu tài liệu"
      },
      {
        id: 16,
        noi_dung:
          "Không phải cơ sở dữ liệu"
      }
    ]
  }
])


/* =====================================================
   CÂU HIỆN TẠI
===================================================== */

const currentIndex = ref(0)


const currentQuestion = computed(() => {
  return questions.value[currentIndex.value]
})


/* =====================================================
   CÂU TRẢ LỜI SINH VIÊN
===================================================== */

const studentAnswers = ref({})


const isAnswered = (questionId) => {

  const answer =
    studentAnswers.value[questionId]

  if (answer === undefined) {
    return false
  }

  if (answer === null) {
    return false
  }

  if (
    typeof answer === "string" &&
    answer.trim() === ""
  ) {
    return false
  }

  return true
}


const answeredCount = computed(() => {

  return questions.value.filter(
    question => isAnswered(question.id)
  ).length

})


const progressPercentage = computed(() => {

  if (questions.value.length === 0) {
    return 0
  }

  return Math.round(
    (
      answeredCount.value /
      questions.value.length
    ) * 100
  )

})


/* =====================================================
   CHUYỂN CÂU
===================================================== */

function nextQuestion() {

  if (
    currentIndex.value <
    questions.value.length - 1
  ) {
    currentIndex.value++
  }

}


function previousQuestion() {

  if (currentIndex.value > 0) {
    currentIndex.value--
  }

}


function goToQuestion(index) {

  currentIndex.value = index

}


function getLetter(index) {

  return String.fromCharCode(
    65 + index
  )

}


/* =====================================================
   ĐỒNG HỒ
===================================================== */

const remainingSeconds = ref(30 * 60)

let timerInterval = null


const formattedTime = computed(() => {

  const minutes =
    Math.floor(
      remainingSeconds.value / 60
    )

  const seconds =
    remainingSeconds.value % 60

  return (
    String(minutes).padStart(2, "0") +
    ":" +
    String(seconds).padStart(2, "0")
  )

})


function startTimer() {

  timerInterval = setInterval(() => {

    if (remainingSeconds.value <= 0) {

      clearInterval(timerInterval)

      submitExam(true)

      return
    }

    remainingSeconds.value--

  }, 1000)

}


/* =====================================================
   GIÁM SÁT
===================================================== */

const warningCount = ref(0)

const showWarning = ref(false)

let warningTimeout = null


function handleVisibilityChange() {

  if (!document.hidden) {
    return
  }

  warningCount.value++

  showWarning.value = true

  console.log(
    "Cảnh báo: Sinh viên rời khỏi tab"
  )

  if (warningTimeout) {
    clearTimeout(warningTimeout)
  }

  warningTimeout = setTimeout(() => {

    showWarning.value = false

  }, 5000)

}


/* =====================================================
   NỘP BÀI
===================================================== */

const showSubmitConfirm = ref(false)


function submitExam(autoSubmit = false) {

  showSubmitConfirm.value = false

  const data = {

    cau_tra_loi:
      studentAnswers.value,

    so_cau_da_lam:
      answeredCount.value,

    tong_so_cau:
      questions.value.length,

    so_canh_bao:
      warningCount.value,

    tu_dong_nop:
      autoSubmit

  }

  console.log(
    "Dữ liệu bài làm:",
    data
  )

  /*
   Sau này gọi API ở đây:

   await api.post(
     `/sinh-vien/bai-nop/${baiNopId}/nop-bai`,
     data
   )
  */

  if (autoSubmit) {

    alert(
      "Đã hết thời gian. Hệ thống tự động nộp bài."
    )

  } else {

    alert(
      "Nộp bài thành công."
    )

  }

}


/* =====================================================
   LIFECYCLE
===================================================== */

onMounted(() => {

  loadStudent()

  startTimer()

  document.addEventListener(
    "visibilitychange",
    handleVisibilityChange
  )

})


onBeforeUnmount(() => {

  if (timerInterval) {
    clearInterval(timerInterval)
  }

  if (warningTimeout) {
    clearTimeout(warningTimeout)
  }

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
  padding: 28px 32px 45px;
  background: #f5f7fb;
  color: #182033;
}


/* ================= HEADER ================= */

.exam-header {
  max-width: 1450px;
  margin: 0 auto 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.exam-title .subject {
  display: block;
  margin-bottom: 6px;
  color: #64748b;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1.5px;
}

.exam-title h1 {
  margin: 0;
  font-size: 30px;
  font-weight: 800;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 14px;
}

.monitor-status {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 15px;
  border: 1px solid #bbf7d0;
  border-radius: 12px;
  background: #f0fdf4;
  color: #15803d;
  font-size: 14px;
  font-weight: 700;
}

.status-dot,
.monitor-dot {
  width: 9px;
  height: 9px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #22c55e;
}

.timer {
  min-width: 110px;
  padding: 11px 16px;
  border: 1px solid #dbe3ef;
  border-radius: 12px;
  background: #ffffff;
  text-align: center;
  font-size: 19px;
  font-weight: 800;
}

.timer.danger {
  border-color: #fecaca;
  background: #fef2f2;
  color: #dc2626;
}


/* ================= INFO ================= */

.exam-info {
  max-width: 1450px;
  margin: 0 auto 22px;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  overflow: hidden;
  border: 1px solid #e1e7f0;
  border-radius: 16px;
  background: #ffffff;
}

.exam-info > div {
  padding: 17px 22px;
  border-right: 1px solid #e1e7f0;
}

.exam-info > div:last-child {
  border-right: none;
}

.exam-info span {
  display: block;
  margin-bottom: 5px;
  color: #718096;
  font-size: 12px;
  font-weight: 700;
}

.exam-info strong {
  font-size: 15px;
}

.warning-text {
  color: #dc2626;
}


/* ================= LAYOUT ================= */

.exam-layout {
  max-width: 1450px;
  margin: auto;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 310px;
  gap: 22px;
  align-items: start;
}


/* ================= QUESTION ================= */

.question-panel {
  min-height: 570px;
  padding: 30px;
  border: 1px solid #e1e7f0;
  border-radius: 18px;
  background: #ffffff;
}

.question-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 25px;
}

.question-number {
  font-size: 17px;
  font-weight: 800;
}

.question-total {
  color: #94a3b8;
  font-size: 14px;
}

.question-score {
  padding: 7px 12px;
  border-radius: 20px;
  background: #eef2ff;
  color: #4f46e5;
  font-size: 13px;
  font-weight: 800;
}

.question-content {
  margin-bottom: 28px;
  font-size: 20px;
  font-weight: 700;
  line-height: 1.6;
}


/* ================= ANSWER ================= */

.answers {
  display: flex;
  flex-direction: column;
  gap: 13px;
}

.answer-item {
  display: flex;
  align-items: center;
  gap: 14px;
  min-height: 64px;
  padding: 13px 16px;
  border: 2px solid #e5eaf2;
  border-radius: 13px;
  cursor: pointer;
  transition: 0.2s;
}

.answer-item:hover {
  border-color: #a5b4fc;
  background: #f8faff;
}

.answer-item.selected {
  border-color: #6366f1;
  background: #eef2ff;
}

.answer-item input {
  display: none;
}

.answer-letter {
  display: flex;
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  background: #f1f5f9;
  font-weight: 800;
}

.answer-item.selected .answer-letter {
  background: #6366f1;
  color: #ffffff;
}

.answer-content {
  line-height: 1.5;
}


/* ================= ESSAY ================= */

.essay-area textarea {
  width: 100%;
  min-height: 220px;
  padding: 16px;
  resize: vertical;
  border: 2px solid #e5eaf2;
  border-radius: 13px;
  outline: none;
  font: inherit;
  line-height: 1.6;
}

.essay-area textarea:focus {
  border-color: #6366f1;
}


/* ================= NAV ================= */

.question-navigation {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  margin-top: 35px;
  padding-top: 25px;
  border-top: 1px solid #e5eaf2;
}

.nav-btn {
  min-width: 125px;
  padding: 12px 18px;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 700;
}

.nav-btn.primary {
  background: #4f46e5;
  color: #ffffff;
}

.nav-btn.secondary {
  border: 1px solid #d7deea;
  background: #ffffff;
  color: #334155;
}

.nav-btn:disabled {
  cursor: not-allowed;
  opacity: 0.4;
}


/* ================= SIDEBAR ================= */

.question-sidebar {
  padding: 22px;
  border: 1px solid #e1e7f0;
  border-radius: 18px;
  background: #ffffff;
}

.question-sidebar h3 {
  margin: 0 0 14px;
  font-size: 16px;
}

.legend {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-bottom: 20px;
  color: #64748b;
  font-size: 12px;
}

.legend span {
  display: flex;
  align-items: center;
  gap: 5px;
}

.legend-box {
  width: 10px;
  height: 10px;
  border: 1px solid #cbd5e1;
  border-radius: 3px;
}

.legend-box.answered {
  border-color: #22c55e;
  background: #22c55e;
}

.question-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 9px;
}

.question-button {
  aspect-ratio: 1;
  border: 1px solid #dce3ed;
  border-radius: 9px;
  background: #ffffff;
  color: #475569;
  cursor: pointer;
  font-weight: 700;
}

.question-button.answered {
  border-color: #bbf7d0;
  background: #dcfce7;
  color: #15803d;
}

.question-button.active {
  border-color: #4f46e5;
  background: #4f46e5;
  color: #ffffff;
}


/* ================= PROGRESS ================= */

.progress-box {
  margin-top: 24px;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 12px;
}

.progress-bar {
  height: 7px;
  overflow: hidden;
  border-radius: 20px;
  background: #e8edf5;
}

.progress-value {
  height: 100%;
  border-radius: inherit;
  background: #22c55e;
  transition: width 0.3s;
}


/* ================= MONITOR ================= */

.monitor-box {
  margin-top: 24px;
  padding: 15px;
  border: 1px solid #dcfce7;
  border-radius: 12px;
  background: #f0fdf4;
}

.monitor-title {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #15803d;
  font-size: 13px;
  font-weight: 800;
}

.monitor-box p {
  margin: 8px 0 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.5;
}

.warning-box {
  margin-top: 12px;
  padding: 10px;
  border-radius: 8px;
  background: #fff7ed;
  color: #c2410c;
  font-size: 12px;
}


/* ================= SUBMIT ================= */

.submit-btn {
  width: 100%;
  margin-top: 20px;
  padding: 13px;
  border: none;
  border-radius: 10px;
  background: #dc2626;
  color: #ffffff;
  cursor: pointer;
  font-size: 14px;
  font-weight: 800;
}


/* ================= WARNING TOAST ================= */

.warning-toast {
  position: fixed;
  right: 25px;
  bottom: 25px;
  z-index: 2000;
  display: flex;
  width: min(420px, calc(100% - 40px));
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border: 1px solid #fed7aa;
  border-radius: 13px;
  background: #fff7ed;
  color: #9a3412;
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.14);
}

.warning-icon {
  font-size: 21px;
}

.warning-toast strong {
  font-size: 14px;
}

.warning-toast p {
  margin: 4px 0 0;
  font-size: 12px;
  line-height: 1.5;
}

.warning-toast button {
  margin-left: auto;
  border: none;
  background: transparent;
  color: #9a3412;
  cursor: pointer;
  font-size: 20px;
}


/* ================= MODAL ================= */

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.58);
}

.submit-modal {
  width: min(460px, 100%);
  padding: 30px;
  border-radius: 18px;
  background: #ffffff;
  text-align: center;
}

.modal-icon {
  display: flex;
  width: 55px;
  height: 55px;
  margin: auto;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  font-size: 25px;
  font-weight: 900;
}

.submit-modal h2 {
  margin: 15px 0 8px;
}

.submit-modal p {
  color: #64748b;
}

.unfinished-warning {
  margin: 17px 0;
  padding: 12px;
  border-radius: 10px;
  background: #fff7ed;
  color: #c2410c;
  font-size: 13px;
}

.submit-note {
  font-size: 12px;
}

.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 24px;
}

.cancel-btn,
.confirm-btn {
  flex: 1;
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 700;
}

.cancel-btn {
  border: 1px solid #d7deea;
  background: #ffffff;
}

.confirm-btn {
  border: none;
  background: #dc2626;
  color: #ffffff;
}


/* ================= DARK MODE ================= */

@media (prefers-color-scheme: dark) {

  .exam-page {
    background: #111827;
    color: #f1f5f9;
  }

  .exam-info,
  .question-panel,
  .question-sidebar,
  .timer,
  .submit-modal {
    border-color: #2c3748;
    background: #182131;
  }

  .exam-info > div {
    border-color: #2c3748;
  }

  .exam-info span,
  .exam-title .subject,
  .question-total,
  .monitor-box p,
  .submit-modal p {
    color: #94a3b8;
  }

  .answer-item {
    border-color: #334155;
  }

  .answer-item:hover {
    border-color: #6366f1;
    background: #1e293b;
  }

  .answer-item.selected {
    border-color: #6366f1;
    background: #252c52;
  }

  .answer-letter {
    background: #273244;
  }

  .question-navigation {
    border-color: #334155;
  }

  .nav-btn.secondary,
  .question-button {
    border-color: #334155;
    background: #1e293b;
    color: #e2e8f0;
  }

  .question-button.answered {
    border-color: #166534;
    background: #153524;
    color: #86efac;
  }

  .question-button.active {
    background: #4f46e5;
    color: #ffffff;
  }

  .essay-area textarea {
    border-color: #334155;
    background: #111827;
    color: #f1f5f9;
  }

  .monitor-box {
    border-color: #14532d;
    background: #14291e;
  }

}


/* ================= RESPONSIVE ================= */

@media (max-width: 950px) {

  .exam-layout {
    grid-template-columns: 1fr;
  }

  .question-sidebar {
    order: -1;
  }

  .question-grid {
    grid-template-columns: repeat(10, 1fr);
  }

}


@media (max-width: 700px) {

  .exam-page {
    padding: 20px 15px 35px;
  }

  .exam-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .header-right {
    width: 100%;
  }

  .monitor-status,
  .timer {
    flex: 1;
  }

  .exam-info {
    grid-template-columns: 1fr 1fr;
  }

  .exam-info > div:nth-child(2) {
    border-right: none;
  }

  .exam-info > div:nth-child(-n + 2) {
    border-bottom: 1px solid #e1e7f0;
  }

  .question-panel {
    min-height: auto;
    padding: 20px;
  }

  .question-content {
    font-size: 17px;
  }

  .question-grid {
    grid-template-columns: repeat(5, 1fr);
  }

  .modal-actions {
    flex-direction: column;
  }

}
</style>
