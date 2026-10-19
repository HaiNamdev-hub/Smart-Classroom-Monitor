<template>
  <div class="submission-page">

    <!-- ================= HEADER ================= -->
    <header class="page-header">
      <div class="header-content">
        <h1>Bài nộp của tôi</h1>

        <p>
          Theo dõi các bài tập đã nộp và kết quả chấm bài của bạn.
        </p>
      </div>

    </header>


    <!-- ================= THỐNG KÊ ================= -->
    <section class="statistics">

      <!-- TỔNG -->
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24">
            <path d="M6 2h9l5 5v15H6z" />
            <path d="M14 2v6h6" />
            <path d="M9 13h6M9 17h6" />
          </svg>
        </div>

        <div>
          <span>Tổng bài nộp</span>
          <strong>{{ submissions.length }}</strong>
        </div>
      </div>


      <!-- ĐÚNG HẠN -->
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 2.5 2.5L16 9" />
          </svg>
        </div>

        <div>
          <span>Đúng hạn</span>
          <strong>{{ onTimeCount }}</strong>
        </div>
      </div>


      <!-- TRỄ HẠN -->
      <div class="stat-card">
        <div class="stat-icon red">
          <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v6" />
            <path d="M12 17h.01" />
          </svg>
        </div>

        <div>
          <span>Trễ hạn</span>
          <strong>{{ lateCount }}</strong>
        </div>
      </div>

    </section>


    <!-- ================= TOOLBAR ================= -->
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
          placeholder="Tìm theo bài tập, lớp học hoặc tên file..."
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


      <!-- TRẠNG THÁI -->
      <select v-model="statusFilter">
        <option value="all">
          Tất cả trạng thái
        </option>

        <option value="dung_han">
          Đúng hạn
        </option>

        <option value="tre_han">
          Trễ hạn
        </option>
      </select>


      <!-- ĐIỂM -->
      <select v-model="scoreFilter">
        <option value="all">
          Tất cả kết quả
        </option>

        <option value="graded">
          Đã chấm
        </option>

        <option value="ungraded">
          Chưa chấm
        </option>
      </select>

    </section>


    <!-- ================= LOADING ================= -->
    <div
      v-if="loading"
      class="state-box"
    >
      <div class="spinner"></div>

      <strong>Đang tải bài nộp...</strong>

      <p>
        Vui lòng chờ trong giây lát.
      </p>
    </div>


    <!-- ================= ERROR ================= -->
    <div
      v-else-if="errorMessage"
      class="state-box"
    >
      <div class="state-icon error-icon">
        !
      </div>

      <strong>Không thể tải bài nộp</strong>

      <p>{{ errorMessage }}</p>

      <button
        type="button"
        class="retry-button"
        @click="getBaiNop"
      >
        Thử lại
      </button>
    </div>


    <!-- ================= EMPTY ================= -->
    <div
      v-else-if="filteredSubmissions.length === 0"
      class="state-box"
    >
      <div class="state-icon">
        <svg viewBox="0 0 24 24">
          <path d="M6 2h9l5 5v15H6z" />
          <path d="M14 2v6h6" />
          <path d="M9 13h6M9 17h6" />
        </svg>
      </div>

      <strong>
        {{
          keyword ||
          statusFilter !== "all" ||
          scoreFilter !== "all"
            ? "Không tìm thấy bài nộp"
            : "Chưa có bài nộp"
        }}
      </strong>

      <p>
        {{
          keyword ||
          statusFilter !== "all" ||
          scoreFilter !== "all"
            ? "Không có bài nộp phù hợp với điều kiện tìm kiếm."
            : "Bạn chưa nộp bài tập nào."
        }}
      </p>
    </div>


    <!-- ================= DANH SÁCH ================= -->
    <section
      v-else
      class="submission-grid"
    >

      <article
        v-for="item in filteredSubmissions"
        :key="item.id"
        class="submission-card"
      >

        <!-- TOP -->
        <div class="card-top">

          <div class="submission-icon">
            <svg viewBox="0 0 24 24">
              <path d="M6 2h9l5 5v15H6z" />
              <path d="M14 2v6h6" />
              <path d="M9 13h6M9 17h6" />
            </svg>
          </div>


          <span
            class="status-badge"
            :class="{
              success: item.trang_thai === 'dung_han',
              late: item.trang_thai === 'tre_han'
            }"
          >
            <span class="status-dot"></span>

            {{ getStatusLabel(item.trang_thai) }}
          </span>

        </div>


        <!-- CONTENT -->
        <div class="card-content">

          <span class="class-name">
            {{ getClassName(item) }}
          </span>

          <h2>
            {{ getAssignmentTitle(item) }}
          </h2>

          <p class="description">
            {{
              item.ghi_chu ||
              item.mo_ta ||
              "Bài tập đã được gửi đến giảng viên."
            }}
          </p>

        </div>


        <!-- INFORMATION -->
        <div class="submission-info">

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
                {{ formatDateTime(item.thoi_gian_nop) }}
              </strong>
            </div>

          </div>


          <!-- FILE -->
          <div class="info-row">

            <div class="info-icon">
              <svg viewBox="0 0 24 24">
                <path
                  d="M21 11.5 12.5 20a6 6 0 0 1-8.5-8.5L13 2.5a4 4 0 0 1 5.7 5.7l-9 9a2 2 0 0 1-2.8-2.8l8.3-8.3"
                />
              </svg>
            </div>

            <div>
              <span>File đã nộp</span>

              <strong>
                {{
                  item.ten_file ||
                  item.file_name ||
                  "Không có file"
                }}
              </strong>
            </div>

          </div>


          <!-- HẠN NỘP -->
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
              <span>Hạn nộp</span>

              <strong>
                {{ formatDateTime(getDeadline(item)) }}
              </strong>
            </div>

          </div>

        </div>


        <!-- ================= SCORE ================= -->
        <div class="score-box">

          <template v-if="getScore(item) !== null">

            <div class="score-content">
              <span>Điểm</span>

              <strong>
                {{ formatScore(getScore(item)) }}
              </strong>
            </div>


            <div
              class="result-badge"
              :class="getResultClass(item)"
            >
              {{ getResultLabel(item) }}
            </div>

          </template>


          <template v-else>

            <div class="not-graded">
              <div class="not-graded-icon">
                <svg viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="9" />
                  <path d="M12 7v5" />
                  <path d="M12 16h.01" />
                </svg>
              </div>

              <div>
                <span>Kết quả</span>

                <strong>Chưa chấm điểm</strong>
              </div>
            </div>

          </template>

        </div>


        <!-- NHẬN XÉT -->
        <div
          v-if="getComment(item)"
          class="comment-box"
        >
          <span>Nhận xét của giảng viên</span>

          <p>
            {{ getComment(item) }}
          </p>
        </div>


        <!-- FOOTER -->
        <div class="card-footer">

          <button
            type="button"
            class="view-button"
            @click="viewSubmission(item)"
          >
            <span>Xem chi tiết</span>

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


const router = useRouter()


/* =====================================================
   DATA
===================================================== */

const submissions = ref([])

const loading = ref(false)

const errorMessage = ref("")

const keyword = ref("")

const statusFilter = ref("all")

const scoreFilter = ref("all")


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
   GET BÀI NỘP
===================================================== */

const getBaiNop = async () => {

  const token = getToken()


  if (!token) {

    router.replace("/dang-nhap")

    return

  }


  loading.value = true

  errorMessage.value = ""


  try {

    const response = await axios.get(
      "/api/sinh-vien/bai-nop",
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

        submissions.value = data

      } else {

        submissions.value =
          data?.bai_nop || []

      }

    } else {

      submissions.value = []

      errorMessage.value =
        response.data?.message ||
        "Không thể lấy danh sách bài nộp."

    }

  } catch (error) {

    console.error(
      "Lỗi lấy bài nộp:",
      error.response?.data || error
    )


    if (error.response?.status === 401) {

      clearAuth()

      router.replace("/dang-nhap")

      return

    }


    submissions.value = []

    errorMessage.value =
      error.response?.data?.message ||
      "Không thể kết nối đến máy chủ."

  } finally {

    loading.value = false

  }

}


/* =====================================================
   COUNT
===================================================== */

const onTimeCount = computed(() => {

  return submissions.value.filter(
    (item) =>
      item.trang_thai === "dung_han"
  ).length

})


const lateCount = computed(() => {

  return submissions.value.filter(
    (item) =>
      item.trang_thai === "tre_han"
  ).length

})


/* =====================================================
   FILTER
===================================================== */

const filteredSubmissions = computed(() => {

  const search =
    keyword.value
      .trim()
      .toLowerCase()


  return submissions.value.filter((item) => {

    /* STATUS */

    const matchStatus =
      statusFilter.value === "all" ||
      item.trang_thai === statusFilter.value


    /* SCORE */

    const score = getScore(item)

    const matchScore =
      scoreFilter.value === "all" ||
      (
        scoreFilter.value === "graded" &&
        score !== null
      ) ||
      (
        scoreFilter.value === "ungraded" &&
        score === null
      )


    /* SEARCH */

    const assignment =
      getAssignmentTitle(item)
        .toLowerCase()

    const className =
      getClassName(item)
        .toLowerCase()

    const fileName =
      String(
        item.ten_file ||
        item.file_name ||
        ""
      ).toLowerCase()


    const matchSearch =
      !search ||
      assignment.includes(search) ||
      className.includes(search) ||
      fileName.includes(search)


    return (
      matchStatus &&
      matchScore &&
      matchSearch
    )

  })

})


/* =====================================================
   HELPERS - BÀI TẬP
===================================================== */

const getAssignmentTitle = (item) => {

  return (
    item.bai_tap?.tieu_de ||
    item.tieu_de ||
    "Bài tập"
  )

}


const getClassName = (item) => {

  return (
    item.bai_tap?.lop_hoc?.ten_lop ||
    item.lop_hoc?.ten_lop ||
    item.ten_lop ||
    "Lớp học"
  )

}


const getDeadline = (item) => {

  return (
    item.bai_tap?.han_nop ||
    item.han_nop ||
    null
  )

}


/* =====================================================
   SCORE
===================================================== */

const getScore = (item) => {

  const score =
    item.diem?.diem ??
    item.diem_so ??
    null

  if (
    score === null ||
    score === undefined ||
    score === ""
  ) {

    return null

  }

  return Number(score)

}


const formatScore = (score) => {

  if (
    score === null ||
    Number.isNaN(Number(score))
  ) {

    return "--"

  }

  return Number(score).toFixed(1)

}


const getComment = (item) => {

  return (
    item.diem?.nhan_xet ||
    item.nhan_xet ||
    ""
  )

}


const getResultLabel = (item) => {

  const result =
    item.diem?.ket_qua ||
    item.ket_qua


  if (result === "dat") {

    return "Đạt"

  }


  if (result === "khong_dat") {

    return "Không đạt"

  }


  return "Đã chấm"

}


const getResultClass = (item) => {

  const result =
    item.diem?.ket_qua ||
    item.ket_qua


  if (result === "dat") {

    return "passed"

  }


  if (result === "khong_dat") {

    return "failed"

  }


  return "graded"

}


/* =====================================================
   STATUS
===================================================== */

const getStatusLabel = (status) => {

  if (status === "dung_han") {

    return "Đúng hạn"

  }


  if (status === "tre_han") {

    return "Trễ hạn"

  }


  return "Không xác định"

}


/* =====================================================
   DATE
===================================================== */

const formatDateTime = (date) => {

  if (!date) {

    return "Chưa cập nhật"

  }


  const value = new Date(date)


  if (Number.isNaN(value.getTime())) {

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
   BACK HOME
===================================================== */

const goHome = () => {

  router.push(
    "/sinh-vien/trang-chu"
  )

}


/* =====================================================
   VIEW SUBMISSION
===================================================== */

const viewSubmission = (item) => {

  /*
    Hiện tại router của bạn chưa có
    /sinh-vien/bai-nop/:id

    Khi tạo trang chi tiết, đổi thành:

    router.push(
      `/sinh-vien/bai-nop/${item.id}`
    )

    Tạm thời chưa chuyển trang.
  */

  console.log(
    "Bài nộp:",
    item
  )

}


/* =====================================================
   INIT
===================================================== */

onMounted(() => {

  getBaiNop()

})
</script>


<style scoped>

/* =====================================================
   PAGE
===================================================== */

.submission-page {
  min-height: calc(100vh - 65px);

  box-sizing: border-box;

  padding: 28px 32px 45px;

  color: #0f172a;

  background: #f6f8fc;
}


/* =====================================================
   HEADER
===================================================== */

.page-header {
  position: relative;

  max-width: 1450px;

  min-height: 88px;

  margin: 0 auto 25px;

  display: flex;

  align-items: center;

  justify-content: center;
}


.header-content {
  text-align: center;
}


.header-content h1 {
  margin: 0;

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

  transform: translateY(-50%);

  width: 46px;

  height: 46px;

  display: flex;

  align-items: center;

  justify-content: center;

  padding: 0;

  border: 1px solid #dce3ed;

  border-radius: 13px;

  color: #334155;

  background: #ffffff;

  cursor: pointer;

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

  margin: 0 auto 20px;

  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 18px;
}


.stat-card {
  display: flex;

  align-items: center;

  gap: 14px;

  padding: 18px;

  border: 1px solid #e3e8f0;

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


.stat-icon.green {
  color: #16a34a;

  background: #f0fdf4;
}


.stat-icon.red {
  color: #dc2626;

  background: #fef2f2;
}


.stat-card > div:last-child {
  display: flex;

  flex-direction: column;

  gap: 3px;
}


.stat-card span {
  color: #64748b;

  font-size: 12px;
}


.stat-card strong {
  font-size: 23px;

  font-weight: 800;
}


/* =====================================================
   TOOLBAR
===================================================== */

.toolbar {
  width: 100%;

  max-width: 1450px;

  margin: 0 auto 23px;

  display: flex;

  gap: 12px;
}


.search-box {
  position: relative;

  flex: 1;

  display: flex;

  align-items: center;

  border: 1px solid #dce3ed;

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

  padding: 13px 42px 13px 11px;

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

  border: 1px solid #dce3ed;

  border-radius: 12px;

  outline: none;

  color: #334155;

  background: #ffffff;
}


/* =====================================================
   GRID
===================================================== */

.submission-grid {
  width: 100%;

  max-width: 1450px;

  margin: 0 auto;

  display: grid;

  grid-template-columns:
    repeat(3, minmax(0, 1fr));

  gap: 20px;
}


/* =====================================================
   CARD
===================================================== */

.submission-card {
  min-width: 0;

  display: flex;

  flex-direction: column;

  padding: 21px;

  border: 1px solid #e2e8f0;

  border-radius: 17px;

  background: #ffffff;

  transition:
    transform 0.2s,
    box-shadow 0.2s,
    border-color 0.2s;
}


.submission-card:hover {
  transform: translateY(-3px);

  border-color: #bfdbfe;

  box-shadow:
    0 12px 30px
    rgba(15, 23, 42, 0.08);
}


/* =====================================================
   CARD TOP
===================================================== */

.card-top {
  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 10px;
}


.submission-icon {
  width: 45px;

  height: 45px;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 12px;

  color: #2563eb;

  background: #eff6ff;
}


.submission-icon svg {
  width: 21px;

  height: 21px;

  fill: none;

  stroke: currentColor;

  stroke-width: 1.8;

  stroke-linecap: round;

  stroke-linejoin: round;
}


/* =====================================================
   STATUS
===================================================== */

.status-badge {
  display: inline-flex;

  align-items: center;

  gap: 6px;

  padding: 6px 10px;

  border-radius: 999px;

  font-size: 11px;

  font-weight: 700;
}


.status-badge.success {
  color: #15803d;

  background: #f0fdf4;
}


.status-badge.late {
  color: #dc2626;

  background: #fef2f2;
}


.status-dot {
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

  margin: 5px 0 7px;

  font-size: 18px;

  line-height: 1.35;

  font-weight: 800;
}


.description {
  min-height: 42px;

  margin: 0;

  color: #64748b;

  font-size: 12px;

  line-height: 1.6;

  display: -webkit-box;

  -webkit-line-clamp: 2;

  -webkit-box-orient: vertical;

  overflow: hidden;
}


/* =====================================================
   INFO
===================================================== */

.submission-info {
  display: grid;

  gap: 13px;

  margin-top: 19px;

  padding: 17px 0;

  border-top: 1px solid #edf0f5;

  border-bottom: 1px solid #edf0f5;
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
   SCORE
===================================================== */

.score-box {
  min-height: 62px;

  margin-top: 16px;

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 12px;

  padding: 13px 15px;

  border-radius: 12px;

  background: #f8fafc;
}


.score-content {
  display: flex;

  flex-direction: column;

  gap: 2px;
}


.score-content span {
  color: #94a3b8;

  font-size: 10px;
}


.score-content strong {
  color: #2563eb;

  font-size: 22px;

  font-weight: 900;
}


.result-badge {
  padding: 6px 10px;

  border-radius: 999px;

  font-size: 10px;

  font-weight: 800;
}


.result-badge.passed {
  color: #15803d;

  background: #dcfce7;
}


.result-badge.failed {
  color: #dc2626;

  background: #fee2e2;
}


.result-badge.graded {
  color: #2563eb;

  background: #dbeafe;
}


.not-graded {
  display: flex;

  align-items: center;

  gap: 10px;
}


.not-graded-icon {
  width: 34px;

  height: 34px;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 9px;

  color: #64748b;

  background: #ffffff;
}


.not-graded-icon svg {
  width: 17px;

  height: 17px;

  fill: none;

  stroke: currentColor;

  stroke-width: 1.8;
}


.not-graded > div:last-child {
  display: flex;

  flex-direction: column;

  gap: 2px;
}


.not-graded span {
  color: #94a3b8;

  font-size: 10px;
}


.not-graded strong {
  color: #475569;

  font-size: 12px;
}


/* =====================================================
   COMMENT
===================================================== */

.comment-box {
  margin-top: 13px;

  padding: 12px 14px;

  border-left: 3px solid #2563eb;

  border-radius: 8px;

  background: #eff6ff;
}


.comment-box span {
  color: #2563eb;

  font-size: 10px;

  font-weight: 800;
}


.comment-box p {
  margin: 5px 0 0;

  color: #475569;

  font-size: 11px;

  line-height: 1.5;
}


/* =====================================================
   FOOTER
===================================================== */

.card-footer {
  margin-top: auto;

  padding-top: 18px;
}


.view-button {
  width: 100%;

  min-height: 42px;

  display: flex;

  align-items: center;

  justify-content: center;

  gap: 8px;

  border: 1px solid #dbeafe;

  border-radius: 10px;

  color: #2563eb;

  background: #eff6ff;

  font-size: 12px;

  font-weight: 800;

  cursor: pointer;

  transition: 0.2s;
}


.view-button svg {
  width: 16px;

  height: 16px;

  fill: none;

  stroke: currentColor;

  stroke-width: 2;

  stroke-linecap: round;

  stroke-linejoin: round;
}


.view-button:hover {
  color: #ffffff;

  border-color: #2563eb;

  background: #2563eb;
}


/* =====================================================
   EMPTY / LOADING
===================================================== */

.state-box {
  max-width: 1450px;

  min-height: 320px;

  box-sizing: border-box;

  margin: 0 auto;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  padding: 30px;

  border: 1px solid #e2e8f0;

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

  color: #2563eb;

  background: #eff6ff;
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
  font-size: 16px;
}


.state-box p {
  margin: 7px 0 0;

  color: #64748b;

  font-size: 12px;
}


.retry-button {
  margin-top: 16px;

  padding: 9px 18px;

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

  border: 3px solid #dbeafe;

  border-top-color: #2563eb;

  border-radius: 50%;

  animation: spin 0.7s linear infinite;
}


@keyframes spin {

  to {
    transform: rotate(360deg);
  }

}


/* =====================================================
   DARK MODE
===================================================== */

:global(html.dark-theme)
.submission-page {
  color: #f8fafc;

  background: #1f2124;
}


:global(html.dark-theme)
.header-content p {
  color: #94a3b8;
}


:global(html.dark-theme)
.back-button,

:global(html.dark-theme)
.stat-card,

:global(html.dark-theme)
.submission-card,

:global(html.dark-theme)
.state-box,

:global(html.dark-theme)
.search-box {
  border-color:
    rgba(255, 255, 255, 0.11);

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
    rgba(255, 255, 255, 0.11);

  background: #111214;
}


:global(html.dark-theme)
.search-box input {
  color: #ffffff;
}


:global(html.dark-theme)
.submission-info {
  border-color:
    rgba(255, 255, 255, 0.09);
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
.description,

:global(html.dark-theme)
.state-box p {
  color: #94a3b8;
}


:global(html.dark-theme)
.submission-icon {
  color: #60a5fa;

  background:
    rgba(37, 99, 235, 0.13);
}


:global(html.dark-theme)
.status-badge.success {
  color: #4ade80;

  background:
    rgba(34, 197, 94, 0.1);
}


:global(html.dark-theme)
.status-badge.late {
  color: #f87171;

  background:
    rgba(220, 38, 38, 0.12);
}


:global(html.dark-theme)
.score-box {
  background: #1c1e21;
}


:global(html.dark-theme)
.not-graded-icon {
  background: #111214;
}


:global(html.dark-theme)
.not-graded strong {
  color: #e2e8f0;
}


:global(html.dark-theme)
.comment-box {
  border-left-color: #60a5fa;

  background:
    rgba(37, 99, 235, 0.1);
}


:global(html.dark-theme)
.comment-box span {
  color: #60a5fa;
}


:global(html.dark-theme)
.comment-box p {
  color: #cbd5e1;
}


:global(html.dark-theme)
.view-button {
  color: #60a5fa;

  border-color:
    rgba(59, 130, 246, 0.2);

  background:
    rgba(37, 99, 235, 0.1);
}


:global(html.dark-theme)
.view-button:hover {
  color: #ffffff;

  border-color: #2563eb;

  background: #2563eb;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

  .submission-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }

}


@media (max-width: 800px) {

  .statistics {
    grid-template-columns: 1fr;
  }


  .toolbar {
    flex-direction: column;
  }


  .toolbar select {
    min-height: 45px;
  }

}


@media (max-width: 700px) {

  .submission-page {
    padding: 18px 16px 30px;
  }


  .page-header {
    padding: 0 55px;
  }


  .header-content h1 {
    font-size: 23px;
  }


  .submission-grid {
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