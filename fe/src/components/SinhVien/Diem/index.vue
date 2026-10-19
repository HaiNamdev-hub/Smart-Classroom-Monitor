<template>
  <div class="score-page">

    <!-- =====================================================
         HEADER
    ====================================================== -->
    <header class="page-header">


      <div class="header-content">
        <h1>Điểm của tôi</h1>

        <p>
          Theo dõi điểm số, kết quả và nhận xét từ giảng viên.
        </p>
      </div>

    </header>


    <!-- =====================================================
         THỐNG KÊ
    ====================================================== -->
    <section class="statistics">

      <!-- ĐÃ CHẤM -->
      <div class="stat-card">

        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24">
            <path d="M6 2h9l5 5v15H6z" />
            <path d="M14 2v6h6" />
            <path d="M9 13h6M9 17h6" />
          </svg>
        </div>

        <div class="stat-content">
          <span>Bài đã chấm</span>

          <strong>
            {{ scores.length }}
          </strong>
        </div>

      </div>


      <!-- ĐIỂM TRUNG BÌNH -->
      <div class="stat-card">

        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24">
            <path d="M12 3l2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9z" />
          </svg>
        </div>

        <div class="stat-content">
          <span>Điểm trung bình</span>

          <strong>
            {{ averageScore }}
          </strong>
        </div>

      </div>


      <!-- ĐẠT -->
      <div class="stat-card">

        <div class="stat-icon green">
          <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16 9" />
          </svg>
        </div>

        <div class="stat-content">
          <span>Đạt</span>

          <strong>
            {{ passedCount }}
          </strong>
        </div>

      </div>


      <!-- KHÔNG ĐẠT -->
      <div class="stat-card">

        <div class="stat-icon red">
          <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="m9 9 6 6M15 9l-6 6" />
          </svg>
        </div>

        <div class="stat-content">
          <span>Không đạt</span>

          <strong>
            {{ failedCount }}
          </strong>
        </div>

      </div>

    </section>


    <!-- =====================================================
         TOOLBAR
    ====================================================== -->
    <section class="toolbar">

      <!-- SEARCH -->
      <div class="search-box">

        <svg viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="7" />
          <path d="m20 20-4-4" />
        </svg>

        <input
          v-model="keyword"
          type="text"
          placeholder="Tìm theo bài tập hoặc lớp học..."
        />

        <button
          v-if="keyword"
          type="button"
          class="clear-search"
          @click="keyword = ''"
        >
          ×
        </button>

      </div>


      <!-- LỌC LỚP -->
      <select v-model="classFilter">

        <option value="all">
          Tất cả lớp học
        </option>

        <option
          v-for="lop in classOptions"
          :key="lop"
          :value="lop"
        >
          {{ lop }}
        </option>

      </select>


      <!-- LỌC KẾT QUẢ -->
      <select v-model="resultFilter">

        <option value="all">
          Tất cả kết quả
        </option>

        <option value="dat">
          Đạt
        </option>

        <option value="khong_dat">
          Không đạt
        </option>

      </select>

    </section>


    <!-- =====================================================
         LOADING
    ====================================================== -->
    <div
      v-if="loading"
      class="state-box"
    >

      <div class="spinner"></div>

      <strong>
        Đang tải điểm...
      </strong>

      <p>
        Vui lòng chờ trong giây lát.
      </p>

    </div>


    <!-- =====================================================
         ERROR
    ====================================================== -->
    <div
      v-else-if="errorMessage"
      class="state-box"
    >

      <div class="state-icon error-icon">
        !
      </div>

      <strong>
        Không thể tải điểm
      </strong>

      <p>
        {{ errorMessage }}
      </p>

      <button
        type="button"
        class="retry-button"
        @click="getDiem"
      >
        Thử lại
      </button>

    </div>


    <!-- =====================================================
         EMPTY
    ====================================================== -->
    <div
      v-else-if="filteredScores.length === 0"
      class="state-box"
    >

      <div class="state-icon">

        <svg viewBox="0 0 24 24">
          <path d="M12 3l2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9z" />
        </svg>

      </div>

      <strong>
        {{
          keyword ||
          classFilter !== "all" ||
          resultFilter !== "all"
            ? "Không tìm thấy kết quả"
            : "Chưa có điểm"
        }}
      </strong>

      <p>
        {{
          keyword ||
          classFilter !== "all" ||
          resultFilter !== "all"
            ? "Không có kết quả phù hợp với điều kiện tìm kiếm."
            : "Các bài tập đã được chấm điểm sẽ xuất hiện tại đây."
        }}
      </p>

    </div>


    <!-- =====================================================
         DANH SÁCH ĐIỂM
    ====================================================== -->
    <section
      v-else
      class="score-grid"
    >

      <article
        v-for="item in filteredScores"
        :key="item.id"
        class="score-card"
      >

        <!-- ================= TOP ================= -->
        <div class="card-top">

          <div class="assignment-icon">

            <svg viewBox="0 0 24 24">
              <path d="M6 2h9l5 5v15H6z" />
              <path d="M14 2v6h6" />
              <path d="M9 13h6M9 17h6" />
            </svg>

          </div>


          <span
            class="result-badge"
            :class="{
              passed: getResult(item) === 'dat',
              failed: getResult(item) === 'khong_dat'
            }"
          >
            <span class="result-dot"></span>

            {{ getResultLabel(item) }}
          </span>

        </div>


        <!-- ================= TITLE ================= -->
        <div class="card-content">

          <span class="class-name">
            {{ getClassName(item) }}
          </span>

          <h2>
            {{ getAssignmentTitle(item) }}
          </h2>

          <p class="assignment-description">
            {{
              getAssignmentDescription(item) ||
              "Bài tập đã được giảng viên chấm điểm."
            }}
          </p>

        </div>


        <!-- ================= SCORE ================= -->
        <div class="score-display">

          <div class="score-circle">

            <strong>
              {{ formatScore(getScore(item)) }}
            </strong>

            <span>/ 10</span>

          </div>


          <div class="score-detail">

            <span>
              Điểm bài tập
            </span>

            <strong
              :class="getScoreClass(getScore(item))"
            >
              {{ getScoreText(getScore(item)) }}
            </strong>

          </div>

        </div>


        <!-- ================= INFORMATION ================= -->
        <div class="score-info">

          <!-- THỜI GIAN NỘP -->
          <div class="info-row">

            <div class="info-icon">

              <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 7v5l3 2" />
              </svg>

            </div>


            <div>
              <span>Thời gian nộp</span>

              <strong>
                {{ formatDateTime(getSubmissionTime(item)) }}
              </strong>
            </div>

          </div>


          <!-- THỜI GIAN CHẤM -->
          <div class="info-row">

            <div class="info-icon">

              <svg viewBox="0 0 24 24">
                <rect
                  x="3"
                  y="5"
                  width="18"
                  height="16"
                  rx="2"
                />

                <path d="M16 3v4M8 3v4M3 11h18" />
              </svg>

            </div>


            <div>
              <span>Thời gian chấm</span>

              <strong>
                {{ formatDateTime(item.thoi_gian_cham) }}
              </strong>
            </div>

          </div>


          <!-- NGƯỜI CHẤM -->
          <div class="info-row">

            <div class="info-icon">

              <svg viewBox="0 0 24 24">
                <circle cx="12" cy="8" r="4" />
                <path d="M4 21a8 8 0 0 1 16 0" />
              </svg>

            </div>


            <div>
              <span>Người chấm</span>

              <strong>
                {{ getGraderName(item) }}
              </strong>
            </div>

          </div>

        </div>


        <!-- ================= COMMENT ================= -->
        <div
          v-if="item.nhan_xet"
          class="comment-box"
        >

          <div class="comment-title">

            <svg viewBox="0 0 24 24">
              <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
            </svg>

            <span>
              Nhận xét của giảng viên
            </span>

          </div>

          <p>
            {{ item.nhan_xet }}
          </p>

        </div>


        <!-- ================= NO COMMENT ================= -->
        <div
          v-else
          class="no-comment"
        >
          Chưa có nhận xét từ giảng viên.
        </div>


        <!-- ================= FOOTER ================= -->
        <div class="card-footer">

          <button
            type="button"
            class="detail-button"
            @click="viewDetail(item)"
          >

            <span>
              Xem bài nộp
            </span>

            <svg viewBox="0 0 24 24">
              <path d="M5 12h14" />
              <path d="m13 6 6 6-6 6" />
            </svg>

          </button>

        </div>

      </article>

    </section>

  </div>
</template>


<script setup>
import axios from "axios"

import {
  computed,
  onMounted,
  ref,
} from "vue"

import { useRouter } from "vue-router"


/* =====================================================
   ROUTER
===================================================== */

const router = useRouter()


/* =====================================================
   DATA
===================================================== */

const scores = ref([])

const loading = ref(false)

const errorMessage = ref("")

const keyword = ref("")

const classFilter = ref("all")

const resultFilter = ref("all")


/* =====================================================
   TOKEN
===================================================== */

const getToken = () => {

  return (
    localStorage.getItem("auth_token") ||
    sessionStorage.getItem("auth_token") ||
    localStorage.getItem("access_token") ||
    sessionStorage.getItem("access_token") ||
    localStorage.getItem("token") ||
    sessionStorage.getItem("token")
  )

}


/* =====================================================
   CLEAR AUTH
===================================================== */

const clearAuth = () => {

  const keys = [
    "auth",
    "auth_token",
    "auth_user",
    "auth_expires_at",
    "access_token",
    "nguoi_dung",
    "token",
  ]


  keys.forEach((key) => {

    localStorage.removeItem(key)

    sessionStorage.removeItem(key)

  })

}


/* =====================================================
   GET ĐIỂM
===================================================== */

const getDiem = async () => {

  const token = getToken()


  if (!token) {

    router.replace("/dang-nhap")

    return

  }


  loading.value = true

  errorMessage.value = ""


  try {

    const response = await axios.get(
      "/api/sinh-vien/diem",
      {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
      }
    )


    if (response.data?.status) {

      const data = response.data.data


      if (Array.isArray(data)) {

        scores.value = data

      } else {

        scores.value =
          data?.diem || []

      }

    } else {

      scores.value = []

      errorMessage.value =
        response.data?.message ||
        "Không thể lấy danh sách điểm."

    }

  } catch (error) {

    console.error(
      "Lỗi lấy điểm:",
      error.response?.data || error
    )


    if (error.response?.status === 401) {

      clearAuth()

      router.replace("/dang-nhap")

      return

    }


    scores.value = []

    errorMessage.value =
      error.response?.data?.message ||
      "Không thể kết nối đến máy chủ."

  } finally {

    loading.value = false

  }

}


/* =====================================================
   SCORE HELPERS
===================================================== */

const getScore = (item) => {

  const value =
    item.diem ??
    item.diem_so ??
    null


  if (
    value === null ||
    value === undefined ||
    value === ""
  ) {

    return null

  }


  const number =
    Number(value)


  return Number.isNaN(number)
    ? null
    : number

}


const formatScore = (score) => {

  if (score === null) {

    return "--"

  }


  return Number(score).toFixed(1)

}


const getScoreText = (score) => {

  if (score === null) {

    return "Chưa xác định"

  }


  if (score >= 8.5) {

    return "Rất tốt"

  }


  if (score >= 7) {

    return "Tốt"

  }


  if (score >= 5) {

    return "Đạt"

  }


  return "Cần cải thiện"

}


const getScoreClass = (score) => {

  if (score === null) {

    return ""

  }


  if (score >= 8) {

    return "score-good"

  }


  if (score >= 5) {

    return "score-normal"

  }


  return "score-low"

}


/* =====================================================
   RESULT
===================================================== */

const getResult = (item) => {

  return item.ket_qua || ""

}


const getResultLabel = (item) => {

  if (getResult(item) === "dat") {

    return "Đạt"

  }


  if (
    getResult(item) === "khong_dat"
  ) {

    return "Không đạt"

  }


  return "Chưa xác định"

}


/* =====================================================
   ASSIGNMENT
===================================================== */

const getAssignmentTitle = (item) => {

  return (
    item.bai_nop?.bai_tap?.tieu_de ||
    item.bai_tap?.tieu_de ||
    item.tieu_de ||
    "Bài tập"
  )

}


const getAssignmentDescription = (item) => {

  return (
    item.bai_nop?.bai_tap?.mo_ta ||
    item.bai_tap?.mo_ta ||
    item.mo_ta ||
    ""
  )

}


/* =====================================================
   CLASS
===================================================== */

const getClassName = (item) => {

  return (
    item.bai_nop
      ?.bai_tap
      ?.lop_hoc
      ?.ten_lop ||

    item.bai_tap
      ?.lop_hoc
      ?.ten_lop ||

    item.lop_hoc?.ten_lop ||

    item.ten_lop ||

    "Lớp học"
  )

}


/* =====================================================
   SUBMISSION
===================================================== */

const getSubmissionTime = (item) => {

  return (
    item.bai_nop?.thoi_gian_nop ||
    item.thoi_gian_nop ||
    null
  )

}


/* =====================================================
   GRADER
===================================================== */

const getGraderName = (item) => {

  return (
    item.nguoi_cham?.ho_ten ||
    item.nguoiCham?.ho_ten ||
    item.ten_nguoi_cham ||
    "Giảng viên"
  )

}


/* =====================================================
   STATISTICS
===================================================== */

const averageScore = computed(() => {

  if (scores.value.length === 0) {

    return "0.0"

  }


  const validScores =
    scores.value
      .map((item) => getScore(item))
      .filter((score) => score !== null)


  if (validScores.length === 0) {

    return "0.0"

  }


  const total =
    validScores.reduce(
      (sum, score) =>
        sum + score,
      0
    )


  return (
    total /
    validScores.length
  ).toFixed(1)

})


const passedCount = computed(() => {

  return scores.value.filter(
    (item) =>
      getResult(item) === "dat"
  ).length

})


const failedCount = computed(() => {

  return scores.value.filter(
    (item) =>
      getResult(item) ===
      "khong_dat"
  ).length

})


/* =====================================================
   CLASS OPTIONS
===================================================== */

const classOptions = computed(() => {

  const classes =
    scores.value
      .map((item) =>
        getClassName(item)
      )
      .filter(Boolean)


  return [
    ...new Set(classes),
  ]

})


/* =====================================================
   FILTER
===================================================== */

const filteredScores = computed(() => {

  const search =
    keyword.value
      .trim()
      .toLowerCase()


  return scores.value.filter(
    (item) => {

      const assignmentName =
        getAssignmentTitle(item)
          .toLowerCase()


      const className =
        getClassName(item)
          .toLowerCase()


      const matchSearch =
        !search ||
        assignmentName.includes(search) ||
        className.includes(search)


      const matchClass =
        classFilter.value === "all" ||
        getClassName(item) ===
          classFilter.value


      const matchResult =
        resultFilter.value === "all" ||
        getResult(item) ===
          resultFilter.value


      return (
        matchSearch &&
        matchClass &&
        matchResult
      )

    }
  )

})


/* =====================================================
   DATE
===================================================== */

const formatDateTime = (date) => {

  if (!date) {

    return "Chưa cập nhật"

  }


  const value =
    new Date(date)


  if (
    Number.isNaN(
      value.getTime()
    )
  ) {

    return "Chưa cập nhật"

  }


  return value.toLocaleString(
    "vi-VN",
    {
      hour: "2-digit",
      minute: "2-digit",
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    }
  )

}


/* =====================================================
   NAVIGATION
===================================================== */

const goHome = () => {

  router.push(
    "/sinh-vien/trang-chu"
  )

}


const viewDetail = (item) => {

  /*
    Vì router hiện tại của bạn đã có:

    /sinh-vien/bai-nop

    nhưng chưa có route chi tiết bài nộp,
    tạm thời chuyển về trang Bài nộp.

    Sau này có thể dùng:
    /sinh-vien/bai-nop/:id
  */

  const baiNopId =
    item.bai_nop_id ||
    item.bai_nop?.id


  router.push({
    path: "/sinh-vien/bai-nop",

    query: baiNopId
      ? {
          bai_nop_id:
            baiNopId,
        }
      : {},
  })

}


/* =====================================================
   INIT
===================================================== */

onMounted(() => {

  getDiem()

})
</script>


<style scoped>

/* =====================================================
   PAGE
===================================================== */

.score-page {
  min-height:
    calc(100vh - 65px);

  box-sizing: border-box;

  padding:
    28px 32px 45px;

  color: #0f172a;

  background: #f6f8fc;
}


/* =====================================================
   HEADER
===================================================== */

.page-header {
  position: relative;

  width: 100%;

  max-width: 1450px;

  min-height: 88px;

  margin:
    0 auto 25px;

  display: flex;

  align-items: center;

  justify-content: center;
}


.header-content {
  text-align: center;
}


.header-content h1 {
  margin: 0;

  color: #0f172a;

  font-size: 30px;

  font-weight: 800;

  letter-spacing: -0.5px;
}


.header-content p {
  margin: 8px 0 0;

  color: #64748b;

  font-size: 14px;
}


/* =====================================================
   BACK
===================================================== */

.back-button {
  position: absolute;

  left: 0;

  top: 50%;

  width: 46px;

  height: 46px;

  padding: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border:
    1px solid #dce3ed;

  border-radius: 13px;

  color: #334155;

  background: #ffffff;

  cursor: pointer;

  transform:
    translateY(-50%);

  transition: 0.2s;
}


.back-button svg {
  width: 21px;

  height: 21px;
}


.back-button:hover {
  color: #ffffff;

  border-color: #2563eb;

  background: #2563eb;

  transform:
    translateY(-50%)
    translateX(-2px);
}


/* =====================================================
   STATISTICS
===================================================== */

.statistics {
  width: 100%;

  max-width: 1450px;

  margin:
    0 auto 20px;

  display: grid;

  grid-template-columns:
    repeat(4, 1fr);

  gap: 18px;
}


.stat-card {
  display: flex;

  align-items: center;

  gap: 14px;

  padding: 18px;

  border:
    1px solid #e3e8f0;

  border-radius: 15px;

  background: #ffffff;
}


.stat-icon {
  width: 48px;

  height: 48px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 13px;
}


.stat-icon svg {
  width: 23px;

  height: 23px;

  fill: none;

  stroke: currentColor;

  stroke-width: 1.8;

  stroke-linecap: round;

  stroke-linejoin: round;
}


.stat-icon.blue {
  color: #2563eb;

  background: #eff6ff;
}


.stat-icon.purple {
  color: #9333ea;

  background: #faf5ff;
}


.stat-icon.green {
  color: #16a34a;

  background: #f0fdf4;
}


.stat-icon.red {
  color: #dc2626;

  background: #fef2f2;
}


.stat-content {
  display: flex;

  flex-direction: column;

  gap: 3px;
}


.stat-content span {
  color: #64748b;

  font-size: 12px;
}


.stat-content strong {
  color: #0f172a;

  font-size: 23px;

  font-weight: 800;
}


/* =====================================================
   TOOLBAR
===================================================== */

.toolbar {
  width: 100%;

  max-width: 1450px;

  margin:
    0 auto 23px;

  display: flex;

  gap: 12px;
}


.search-box {
  position: relative;

  flex: 1;

  display: flex;

  align-items: center;

  border:
    1px solid #dce3ed;

  border-radius: 12px;

  background: #ffffff;

  overflow: hidden;
}


.search-box > svg {
  width: 19px;

  height: 19px;

  margin-left: 15px;

  flex-shrink: 0;

  fill: none;

  stroke: #94a3b8;

  stroke-width: 2;

  stroke-linecap: round;
}


.search-box input {
  width: 100%;

  padding:
    13px 42px
    13px 11px;

  border: 0;

  outline: none;

  color: #0f172a;

  background: transparent;

  font-size: 13px;
}


.clear-search {
  position: absolute;

  right: 10px;

  border: 0;

  color: #94a3b8;

  background: transparent;

  font-size: 21px;

  cursor: pointer;
}


.toolbar select {
  min-width: 180px;

  padding: 0 13px;

  border:
    1px solid #dce3ed;

  border-radius: 12px;

  outline: none;

  color: #334155;

  background: #ffffff;

  cursor: pointer;
}


/* =====================================================
   GRID
===================================================== */

.score-grid {
  width: 100%;

  max-width: 1450px;

  margin: 0 auto;

  display: grid;

  grid-template-columns:
    repeat(
      3,
      minmax(0, 1fr)
    );

  gap: 20px;
}


/* =====================================================
   CARD
===================================================== */

.score-card {
  min-width: 0;

  display: flex;

  flex-direction: column;

  padding: 21px;

  border:
    1px solid #e2e8f0;

  border-radius: 17px;

  background: #ffffff;

  transition:
    transform 0.2s,
    box-shadow 0.2s,
    border-color 0.2s;
}


.score-card:hover {
  transform:
    translateY(-3px);

  border-color: #bfdbfe;

  box-shadow:
    0 12px 30px
    rgba(
      15,
      23,
      42,
      0.08
    );
}


/* =====================================================
   CARD TOP
===================================================== */

.card-top {
  display: flex;

  align-items: center;

  justify-content:
    space-between;

  gap: 10px;
}


.assignment-icon {
  width: 45px;

  height: 45px;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 12px;

  color: #2563eb;

  background: #eff6ff;
}


.assignment-icon svg {
  width: 21px;

  height: 21px;

  fill: none;

  stroke: currentColor;

  stroke-width: 1.8;

  stroke-linecap: round;

  stroke-linejoin: round;
}


/* =====================================================
   RESULT BADGE
===================================================== */

.result-badge {
  display: inline-flex;

  align-items: center;

  gap: 6px;

  padding:
    6px 10px;

  border-radius: 999px;

  font-size: 11px;

  font-weight: 700;
}


.result-badge.passed {
  color: #15803d;

  background: #f0fdf4;
}


.result-badge.failed {
  color: #dc2626;

  background: #fef2f2;
}


.result-dot {
  width: 6px;

  height: 6px;

  border-radius: 50%;

  background: currentColor;
}


/* =====================================================
   CONTENT
===================================================== */

.card-content {
  margin-top: 18px;
}


.class-name {
  color: #2563eb;

  font-size: 12px;

  font-weight: 800;
}


.card-content h2 {
  min-height: 48px;

  margin:
    5px 0 7px;

  color: #0f172a;

  font-size: 18px;

  line-height: 1.35;

  font-weight: 800;
}


.assignment-description {
  min-height: 42px;

  margin: 0;

  color: #64748b;

  font-size: 12px;

  line-height: 1.6;

  display: -webkit-box;

  -webkit-line-clamp: 2;

  -webkit-box-orient:
    vertical;

  overflow: hidden;
}


/* =====================================================
   SCORE DISPLAY
===================================================== */

.score-display {
  margin-top: 18px;

  display: flex;

  align-items: center;

  gap: 15px;

  padding: 15px;

  border-radius: 13px;

  background: #f8fafc;
}


.score-circle {
  width: 70px;

  height: 70px;

  flex-shrink: 0;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  border:
    4px solid #dbeafe;

  border-radius: 50%;

  background: #ffffff;
}


.score-circle strong {
  color: #2563eb;

  font-size: 21px;

  line-height: 1;

  font-weight: 900;
}


.score-circle span {
  margin-top: 4px;

  color: #94a3b8;

  font-size: 9px;
}


.score-detail {
  display: flex;

  flex-direction: column;

  gap: 4px;
}


.score-detail span {
  color: #94a3b8;

  font-size: 10px;
}


.score-detail strong {
  font-size: 13px;

  font-weight: 800;
}


.score-good {
  color: #16a34a;
}


.score-normal {
  color: #2563eb;
}


.score-low {
  color: #dc2626;
}


/* =====================================================
   INFO
===================================================== */

.score-info {
  display: grid;

  gap: 13px;

  margin-top: 18px;

  padding: 17px 0;

  border-top:
    1px solid #edf0f5;

  border-bottom:
    1px solid #edf0f5;
}


.info-row {
  display: flex;

  align-items: center;

  gap: 10px;
}


.info-icon {
  width: 32px;

  height: 32px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 9px;

  color: #64748b;

  background: #f8fafc;
}


.info-icon svg {
  width: 16px;

  height: 16px;

  fill: none;

  stroke: currentColor;

  stroke-width: 1.8;

  stroke-linecap: round;

  stroke-linejoin: round;
}


.info-row > div:last-child {
  min-width: 0;

  display: flex;

  flex-direction: column;

  gap: 2px;
}


.info-row span {
  color: #94a3b8;

  font-size: 10px;
}


.info-row strong {
  overflow: hidden;

  color: #334155;

  font-size: 12px;

  font-weight: 700;

  white-space: nowrap;

  text-overflow: ellipsis;
}


/* =====================================================
   COMMENT
===================================================== */

.comment-box {
  margin-top: 15px;

  padding:
    13px 14px;

  border-left:
    3px solid #2563eb;

  border-radius: 9px;

  background: #eff6ff;
}


.comment-title {
  display: flex;

  align-items: center;

  gap: 7px;

  color: #2563eb;
}


.comment-title svg {
  width: 15px;

  height: 15px;

  fill: none;

  stroke: currentColor;

  stroke-width: 1.8;
}


.comment-title span {
  font-size: 10px;

  font-weight: 800;
}


.comment-box p {
  margin:
    7px 0 0;

  color: #475569;

  font-size: 11px;

  line-height: 1.6;
}


.no-comment {
  margin-top: 15px;

  padding:
    13px 14px;

  border-radius: 9px;

  color: #94a3b8;

  background: #f8fafc;

  font-size: 11px;
}


/* =====================================================
   FOOTER
===================================================== */

.card-footer {
  margin-top: auto;

  padding-top: 18px;
}


.detail-button {
  width: 100%;

  min-height: 42px;

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 8px;

  border:
    1px solid #dbeafe;

  border-radius: 10px;

  color: #2563eb;

  background: #eff6ff;

  font-size: 12px;

  font-weight: 800;

  cursor: pointer;

  transition: 0.2s;
}


.detail-button svg {
  width: 16px;

  height: 16px;

  fill: none;

  stroke: currentColor;

  stroke-width: 2;

  stroke-linecap: round;

  stroke-linejoin: round;
}


.detail-button:hover {
  color: #ffffff;

  border-color: #2563eb;

  background: #2563eb;
}


/* =====================================================
   STATE
===================================================== */

.state-box {
  width: 100%;

  max-width: 1450px;

  min-height: 320px;

  box-sizing: border-box;

  margin: 0 auto;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  padding: 30px;

  border:
    1px solid #e2e8f0;

  border-radius: 17px;

  text-align: center;

  background: #ffffff;
}


.state-icon {
  width: 58px;

  height: 58px;

  display: flex;

  align-items: center;

  justify-content: center;

  margin-bottom: 15px;

  border-radius: 50%;

  color: #9333ea;

  background: #faf5ff;
}


.state-icon svg {
  width: 27px;

  height: 27px;

  fill: none;

  stroke: currentColor;

  stroke-width: 1.8;

  stroke-linecap: round;

  stroke-linejoin: round;
}


.error-icon {
  color: #dc2626;

  background: #fef2f2;

  font-size: 24px;

  font-weight: 800;
}


.state-box strong {
  color: #0f172a;

  font-size: 16px;
}


.state-box p {
  margin:
    7px 0 0;

  color: #64748b;

  font-size: 12px;
}


.retry-button {
  margin-top: 16px;

  padding:
    9px 18px;

  border: 0;

  border-radius: 9px;

  color: #ffffff;

  background: #2563eb;

  cursor: pointer;
}


.spinner {
  width: 30px;

  height: 30px;

  margin-bottom: 16px;

  border:
    3px solid #dbeafe;

  border-top-color: #2563eb;

  border-radius: 50%;

  animation:
    spin 0.7s
    linear infinite;
}


@keyframes spin {

  to {
    transform:
      rotate(360deg);
  }

}


/* =====================================================
   DARK MODE
===================================================== */

:global(html.dark-theme)
.score-page {
  color: #f8fafc;

  background: #1f2124;
}


:global(html.dark-theme)
.header-content h1,

:global(html.dark-theme)
.stat-content strong,

:global(html.dark-theme)
.card-content h2,

:global(html.dark-theme)
.state-box strong {
  color: #ffffff;
}


:global(html.dark-theme)
.header-content p,

:global(html.dark-theme)
.assignment-description,

:global(html.dark-theme)
.state-box p {
  color: #94a3b8;
}


:global(html.dark-theme)
.back-button,

:global(html.dark-theme)
.stat-card,

:global(html.dark-theme)
.score-card,

:global(html.dark-theme)
.state-box,

:global(html.dark-theme)
.search-box {
  border-color:
    rgba(
      255,
      255,
      255,
      0.11
    );

  background: #111214;
}


:global(html.dark-theme)
.back-button {
  color: #e2e8f0;
}


:global(html.dark-theme)
.back-button:hover {
  color: #ffffff;

  border-color: #2563eb;

  background: #2563eb;
}


:global(html.dark-theme)
.toolbar select {
  color: #e2e8f0;

  border-color:
    rgba(
      255,
      255,
      255,
      0.11
    );

  background: #111214;
}


:global(html.dark-theme)
.search-box input {
  color: #ffffff;
}


:global(html.dark-theme)
.assignment-icon {
  color: #60a5fa;

  background:
    rgba(
      37,
      99,
      235,
      0.13
    );
}


:global(html.dark-theme)
.score-display,

:global(html.dark-theme)
.no-comment {
  background: #1c1e21;
}


:global(html.dark-theme)
.score-circle {
  border-color:
    rgba(
      59,
      130,
      246,
      0.3
    );

  background: #111214;
}


:global(html.dark-theme)
.score-info {
  border-color:
    rgba(
      255,
      255,
      255,
      0.09
    );
}


:global(html.dark-theme)
.info-icon {
  color: #94a3b8;

  background: #1c1e21;
}


:global(html.dark-theme)
.info-row strong {
  color: #e2e8f0;
}


:global(html.dark-theme)
.result-badge.passed {
  color: #4ade80;

  background:
    rgba(
      34,
      197,
      94,
      0.1
    );
}


:global(html.dark-theme)
.result-badge.failed {
  color: #f87171;

  background:
    rgba(
      220,
      38,
      38,
      0.12
    );
}


:global(html.dark-theme)
.comment-box {
  border-left-color: #60a5fa;

  background:
    rgba(
      37,
      99,
      235,
      0.1
    );
}


:global(html.dark-theme)
.comment-title {
  color: #60a5fa;
}


:global(html.dark-theme)
.comment-box p {
  color: #cbd5e1;
}


:global(html.dark-theme)
.detail-button {
  color: #60a5fa;

  border-color:
    rgba(
      59,
      130,
      246,
      0.2
    );

  background:
    rgba(
      37,
      99,
      235,
      0.1
    );
}


:global(html.dark-theme)
.detail-button:hover {
  color: #ffffff;

  border-color: #2563eb;

  background: #2563eb;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

  .score-grid {
    grid-template-columns:
      repeat(
        2,
        minmax(0, 1fr)
      );
  }


  .statistics {
    grid-template-columns:
      repeat(2, 1fr);
  }

}


@media (max-width: 800px) {

  .toolbar {
    flex-direction: column;
  }


  .toolbar select {
    min-height: 45px;
  }

}


@media (max-width: 700px) {

  .score-page {
    padding:
      18px 16px 30px;
  }


  .page-header {
    padding: 0 55px;
  }


  .header-content h1 {
    font-size: 23px;
  }


  .statistics {
    grid-template-columns: 1fr;
  }


  .score-grid {
    grid-template-columns: 1fr;
  }

}


@media (max-width: 480px) {

  .header-content p {
    display: none;
  }


  .page-header {
    min-height: 65px;
  }

}
</style>