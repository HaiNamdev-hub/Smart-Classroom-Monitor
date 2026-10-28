<template>
  <div class="assignment-page">
    <!-- HEADER -->
    <header class="page-header">
      <div>
        <span class="eyebrow">QUẢN LÝ GIẢNG DẠY</span>
        <h1>📝 Quản lý bài tập & kiểm tra</h1>
        <p>
          Tạo bài tập, thiết lập câu hỏi, đặt lịch kiểm tra
          và theo dõi tiến độ nộp bài của sinh viên.
        </p>
      </div>

      <div class="header-actions">
        <button type="button" class="btn-secondary" :disabled="loading" @click="fetchBaiTap">
          ↻ Làm mới
        </button>

        <button type="button" class="btn-primary" :disabled="loadingLop || danhSachLop.length === 0"
          @click="openCreateForm">
          + Tạo bài tập mới
        </button>
      </div>
    </header>

    <!-- THỐNG KÊ -->
    <section class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon blue">BT</div>
        <div class="summary-content">
          <span>Tổng bài tập</span>
          <strong>{{ summaryText(danhSachBaiTap.length) }}</strong>
          <small>Tất cả bài tập đã tạo</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon green">✓</div>
        <div class="summary-content">
          <span>Còn hạn nộp</span>
          <strong>{{ summaryText(soConHan) }}</strong>
          <small>Bài tập chưa quá hạn</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon orange">!</div>
        <div class="summary-content">
          <span>Đã quá hạn</span>
          <strong>{{ summaryText(soQuaHan) }}</strong>
          <small>Bài tập hết hạn nộp</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon purple">BN</div>
        <div class="summary-content">
          <span>Lượt bài đã nộp</span>
          <strong>{{ tongBaiNopText }}</strong>
          <small>Dữ liệu từ Backend</small>
        </div>
      </div>
    </section>

    <!-- DANH SÁCH -->
    <section class="assignment-panel">
      <div class="panel-header">
        <div>
          <h2>Danh sách bài tập</h2>
          <p>
            Quản lý bài tập và lịch kiểm tra của các lớp
            bạn đang phụ trách.
          </p>
        </div>

        <span class="result-badge">
          {{ danhSachHienThi.length }} bài tập
        </span>
      </div>

      <!-- FILTER -->
      <div class="toolbar">
        <div class="search-box">
          <span>⌕</span>
          <input v-model="searchKeyword" type="search" placeholder="Tìm tên hoặc nội dung bài tập..." />
        </div>

        <select v-model="selectedLopId" class="filter-select" :disabled="loadingLop" @change="changeClass">
          <option value="">Tất cả lớp học</option>

          <option v-for="lop in danhSachLop" :key="lop.id" :value="String(lop.id)">
            {{ lop.ten_lop }}
          </option>
        </select>

        <select v-model="statusFilter" class="filter-select">
          <option value="all">Tất cả thời hạn</option>
          <option value="con_han">Còn hạn nộp</option>
          <option value="qua_han">Đã quá hạn</option>
          <option value="khong_xac_dinh">Chưa xác định</option>
        </select>
      </div>

      <!-- MESSAGES -->
      <div v-if="error" class="alert error-alert" role="alert">
        <div>
          <strong>Không thể tải dữ liệu</strong>
          <p>{{ error }}</p>
        </div>

        <button type="button" @click="fetchBaiTap">
          Thử lại
        </button>
      </div>

      <div v-if="success" class="alert success-alert">
        <span>✓ {{ success }}</span>
        <button type="button" @click="success = ''">
          ×
        </button>
      </div>

      <!-- LOADING -->
      <div v-if="loading && !hasLoaded" class="state-box">
        <div class="spinner"></div>
        <strong>Đang tải danh sách bài tập...</strong>
      </div>

      <!-- EMPTY -->
      <div v-else-if="hasLoaded && danhSachBaiTap.length === 0" class="state-box">
        <div class="empty-symbol">📝</div>
        <strong>Chưa có bài tập</strong>

        <p>
          Bạn chưa tạo bài tập nào cho phạm vi lớp đang chọn.
        </p>

        <button type="button" class="btn-primary" @click="openCreateForm">
          + Tạo bài tập
        </button>
      </div>

      <div v-else-if="hasLoaded && danhSachHienThi.length === 0" class="state-box">
        <div class="empty-symbol">⌕</div>
        <strong>Không tìm thấy bài tập</strong>
        <p>Thử thay đổi từ khóa hoặc bộ lọc.</p>

        <button type="button" class="btn-secondary" @click="resetFilters">
          Xóa bộ lọc
        </button>
      </div>

      <!-- ASSIGNMENT GRID -->
      <div v-else-if="hasLoaded" class="assignment-grid">
        <article v-for="bt in danhSachHienThi" :key="bt.id" class="assignment-card">
          <div class="card-header">
            <div class="assignment-icon" :class="{ exam: bt.laBaiKiemTra }">
              {{ bt.laBaiKiemTra ? "KT" : "BT" }}
            </div>

            <span class="status-badge" :class="assignmentStatus(bt).className">
              <span class="status-dot"></span>
              {{ assignmentStatus(bt).text }}
            </span>
          </div>

          <div class="card-main">
            <div class="class-label">
              {{ className(bt.lopId) }}
            </div>

            <h3>
              {{ bt.tieuDe || "Chưa có tiêu đề" }}
            </h3>

            <p class="description">
              {{
                bt.moTa ||
                "Bài tập chưa có phần mô tả."
              }}
            </p>
          </div>

          <div class="card-divider"></div>

          <!-- THÔNG TIN THỜI GIAN -->
          <div class="card-details">

            <div v-if="bt.thoiGianBatDau" class="detail-row">
              <div class="detail-left">
                <span class="detail-symbol">🕒</span>
                <span>Giờ bắt đầu</span>
              </div>

              <strong>
                {{ formatDate(bt.thoiGianBatDau) }}
              </strong>
            </div>

            <div v-if="bt.thoiGianKetThuc" class="detail-row">
              <div class="detail-left">
                <span class="detail-symbol">⏰</span>
                <span>Giờ kết thúc</span>
              </div>

              <strong>
                {{ formatDate(bt.thoiGianKetThuc) }}
              </strong>
            </div>

            <div class="detail-row">
              <span>📅 Hạn nộp</span>
              <strong>{{ formatDate(bt.hanNop) }}</strong>
            </div>

            <div class="detail-row">
              <span>📤 Bài đã nộp</span>
              <strong class="submission-count">
                {{ formatCount(bt.daNop) }}
              </strong>
            </div>

            <template v-if="bt.thoiGianBatDau">
              <div class="detail-row">
                <span>🕒 Bắt đầu</span>
                <strong>
                  {{ formatDate(bt.thoiGianBatDau) }}
                </strong>
              </div>
            </template>

            <template v-if="bt.thoiGianKetThuc">
              <div class="detail-row">
                <span>⏰ Kết thúc</span>
                <strong>
                  {{ formatDate(bt.thoiGianKetThuc) }}
                </strong>
              </div>
            </template>

            <div v-if="bt.laBaiKiemTra" class="exam-state" :class="examStatus(bt).className">
              <span>◉</span>
              {{ examStatus(bt).text }}
            </div>
          </div>

          <!-- BUTTONS -->
          <div class="card-actions">
            <router-link :to="{
              name: 'GiangVienQuanLyCauHoi',
              params: { baiTapId: bt.id }
            }" class="card-action questions-action">
              <span>📝 Quản lý câu hỏi</span>
              <span>→</span>
            </router-link>

            <button type="button" class="card-action schedule-action" @click="openSchedule(bt)">
              <span>🕒 Đặt lịch kiểm tra</span>
              <span>→</span>
            </button>

            <router-link :to="{
              path: '/giang-vien/bai-nop',
              query: { baiTapId: bt.id }
            }" class="card-action submissions-action">
              <span>📋 Xem bài nộp</span>
              <span>→</span>
            </router-link>
          </div>
        </article>
      </div>

      <footer class="panel-footer">
        <span>
          Hiển thị {{ danhSachHienThi.length }}
          / {{ danhSachBaiTap.length }} bài tập
        </span>

        <span v-if="lastUpdatedAt">
          Cập nhật: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>

    <!-- MODAL TẠO BÀI TẬP -->
    <div v-if="showCreateForm" class="modal-overlay" @click.self="closeCreateForm">
      <div class="create-modal" role="dialog" aria-modal="true" aria-labelledby="create-title">
        <div class="modal-header">
          <div>
            <span class="eyebrow">QUẢN LÝ BÀI TẬP</span>
            <h2 id="create-title">Tạo bài tập mới</h2>
            <p>Nhập thông tin bài tập để giao cho lớp.</p>
          </div>

          <button type="button" class="close-modal" :disabled="saving" @click="closeCreateForm">
            ×
          </button>
        </div>

        <form @submit.prevent="createBaiTap">
          <div class="modal-body">
            <div class="form-group">
              <label for="assignment-class">
                Lớp học <span>*</span>
              </label>

              <select id="assignment-class" v-model="form.lopId" required :disabled="saving">
                <option value="">
                  -- Chọn lớp học --
                </option>

                <option v-for="lop in danhSachLop" :key="lop.id" :value="String(lop.id)">
                  {{ lop.ten_lop }}
                  ({{ lop.ma_lop || "Chưa có mã" }})
                </option>
              </select>
            </div>

            <div class="form-group">
              <label for="assignment-title">
                Tiêu đề bài tập <span>*</span>
              </label>

              <input id="assignment-title" v-model.trim="form.tieuDe" type="text" maxlength="200" required
                :disabled="saving" placeholder="Ví dụ: Kiểm tra giữa kỳ lập trình Web" />
            </div>

            <div class="form-group">
              <label for="assignment-description">
                Mô tả
              </label>

              <textarea id="assignment-description" v-model.trim="form.moTa" rows="5" :disabled="saving"
                placeholder="Nhập nội dung và hướng dẫn..."></textarea>
            </div>


            <!-- THỜI GIAN BẮT ĐẦU -->
            <div class="form-group">
              <label for="exam-start">
                Thời gian bắt đầu <span>*</span>
              </label>

              <input id="exam-start" v-model="form.thoiGianBatDau" type="datetime-local" required :disabled="saving" />

              <small>
                Bài kiểm tra sẽ bắt đầu mở cho Sinh viên
                từ thời điểm này.
              </small>
            </div>

            <!-- THỜI GIAN KẾT THÚC -->
            <div class="form-group">
              <label for="exam-end">
                Thời gian kết thúc / Hạn nộp <span>*</span>
              </label>

              <input id="exam-end" v-model="form.thoiGianKetThuc" type="datetime-local" required :disabled="saving" />

              <small>
                Thời gian kết thúc phải sau thời gian bắt đầu.
                Hạn nộp sẽ tự động bằng thời gian kết thúc.
              </small>
            </div>


            <div v-if="formError" class="form-error" role="alert">
              {{ formError }}
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-secondary" :disabled="saving" @click="closeCreateForm">
              Hủy
            </button>

            <button type="submit" class="btn-primary" :disabled="saving">
              {{
                saving
                  ? "Đang tạo..."
                  : "Tạo bài tập"
              }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL ĐẶT LỊCH KIỂM TRA -->
    <div v-if="showSchedule && selectedAssignment" class="modal-overlay" @click.self="closeSchedule">
      <div class="schedule-modal" role="dialog" aria-modal="true" aria-labelledby="schedule-title">
        <div class="modal-header">
          <div>
            <span class="eyebrow">
              LỊCH KIỂM TRA TỰ ĐỘNG
            </span>

            <h2 id="schedule-title">
              {{ selectedAssignment.tieuDe }}
            </h2>

            <p>
              Thiết lập thời gian mở và đóng bài kiểm tra.
            </p>
          </div>

          <button type="button" class="close-modal" @click="closeSchedule">
            ×
          </button>
        </div>

        <div class="schedule-content">
          <LichKiemTra :key="selectedAssignment.id" :bai-tap-id="selectedAssignment.id" />

          <div class="schedule-note">
            <strong>ℹ Cách hoạt động</strong>

            <p>
              Khi đến giờ bắt đầu, Backend cho phép
              Sinh viên truy cập bài thi.
              Đến giờ kết thúc, hệ thống sẽ chặn
              việc bắt đầu bài mới.
            </p>

            <p>
              Sinh viên đang mở trang Bài tập sẽ thấy
              bài mới khi danh sách được làm mới.
            </p>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-primary" @click="closeSchedule">
            Đóng & làm mới
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
} from "vue"

import api from "../../../services/api"
import LichKiemTra from "./LichKiemTra.vue"

/* ==================================
   STATE
================================== */

const danhSachLop = ref([])
const danhSachBaiTap = ref([])

const selectedLopId = ref("")
const searchKeyword = ref("")
const statusFilter = ref("all")

const loading = ref(false)
const loadingLop = ref(false)
const hasLoaded = ref(false)
const saving = ref(false)

const error = ref("")
const success = ref("")
const formError = ref("")
const lastUpdatedAt = ref("")

const showCreateForm = ref(false)

const showSchedule = ref(false)
const selectedAssignment = ref(null)

const nowMs = ref(Date.now())

const form = ref({
  lopId: "",
  tieuDe: "",
  moTa: "",
  thoiGianBatDau: "",
  thoiGianKetThuc: "",
})

let requestId = 0
let classRequestId = 0
let clockTimer = null

/* ==================================
   API HELPERS
================================== */

function extractList(response) {
  const body = response?.data

  if (
    body?.success === false ||
    body?.status === false
  ) {
    throw new Error(
      body.message || "API trả về lỗi."
    )
  }

  const data = body?.data ?? body

  if (Array.isArray(data)) {
    return data
  }

  if (Array.isArray(data?.data)) {
    return data.data
  }

  throw new Error(
    "API không trả về danh sách hợp lệ."
  )
}

function getErrorMessage(err, fallback) {
  const status = err.response?.status

  const validationErrors =
    err.response?.data?.errors

  if (validationErrors) {
    const first = Object.values(
      validationErrors
    ).flat()[0]

    if (first) return first
  }

  if (status === 401) {
    return "Phiên đăng nhập đã hết hạn."
  }

  if (status === 403) {
    return "Bạn không có quyền thực hiện thao tác này."
  }

  if (status === 404) {
    return "Không tìm thấy API hoặc tài nguyên."
  }

  return (
    err.response?.data?.message ||
    err.message ||
    fallback
  )
}

/* ==================================
   NORMALIZE DATA
================================== */

function normalizeLop(item) {
  return {
    id: item.id,

    ten_lop:
      item.ten_lop ??
      item.tenLop ??
      `Lớp ${item.id}`,

    ma_lop:
      item.ma_lop ??
      item.maLop ??
      item.maHocPhan ??
      "",
  }
}

function normalizeCount(value) {
  if (
    value === null ||
    value === undefined ||
    value === ""
  ) {
    return null
  }

  const number = Number(value)

  return Number.isInteger(number) &&
    number >= 0
    ? number
    : null
}


function normalizeBaiTap(item) {
  return {
    id: item.id,

    lopId:
      item.lopId ??
      item.lop_hoc_id ??
      null,

    tieuDe:
      item.tieuDe ??
      item.tieu_de ??
      "",

    moTa:
      item.moTa ??
      item.mo_ta ??
      "",

    hanNop:
      item.hanNop ??
      item.han_nop ??
      null,

    thoiGianBatDau:
      item.thoiGianBatDau ??
      item.thoi_gian_bat_dau ??
      null,

    thoiGianKetThuc:
      item.thoiGianKetThuc ??
      item.thoi_gian_ket_thuc ??
      null,

    daNop: normalizeCount(
      item.daNop ??
      item.bai_nops_count ??
      null
    ),
  }
}


/* ==================================
   FORMAT
================================== */

function className(lopId) {
  const lop = danhSachLop.value.find(
    item => String(item.id) === String(lopId)
  )

  return lop
    ? `${lop.ten_lop} · ${lop.ma_lop || "Chưa có mã"}`
    : "Lớp học"
}

/*
  Laravel/MySQL thường trả về:
  2026-10-28 08:00:00

  Chuẩn hóa để trình duyệt đọc theo giờ địa phương.
*/
function parseDate(value) {
  if (!value) return null

  const normalized =
    String(value).includes("T")
      ? String(value)
      : String(value).replace(" ", "T")

  const date = new Date(normalized)

  return Number.isNaN(date.getTime())
    ? null
    : date
}

function formatDate(value) {
  const date = parseDate(value)

  if (!date) {
    return "Chưa cập nhật"
  }

  return new Intl.DateTimeFormat(
    "vi-VN",
    {
      hour: "2-digit",
      minute: "2-digit",
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    }
  ).format(date)
}

function formatCount(value) {
  return value === null
    ? "—"
    : value.toLocaleString("vi-VN")
}

/* ==================================
   ASSIGNMENT STATUS
================================== */

function assignmentStatus(bt) {
  const deadline = parseDate(bt.hanNop)

  if (!deadline) {
    return {
      text: "Chưa xác định",
      className: "status-unknown",
      value: "khong_xac_dinh",
    }
  }

  if (deadline.getTime() <= nowMs.value) {
    return {
      text: "Đã quá hạn",
      className: "status-expired",
      value: "qua_han",
    }
  }

  return {
    text: "Còn hạn nộp",
    className: "status-active",
    value: "con_han",
  }
}

function examStatus(bt) {
  const start = parseDate(bt.thoiGianBatDau)
  const end = parseDate(bt.thoiGianKetThuc)

  if (!start || !end) {
    return {
      text: "Chưa có lịch kiểm tra",
      className: "exam-unscheduled",
    }
  }

  if (nowMs.value < start.getTime()) {
    return {
      text: "Sắp diễn ra",
      className: "exam-upcoming",
    }
  }

  if (nowMs.value >= end.getTime()) {
    return {
      text: "Đã kết thúc",
      className: "exam-ended",
    }
  }

  return {
    text: "Đang diễn ra",
    className: "exam-running",
  }
}

/* ==================================
   SUMMARY
================================== */

function summaryText(value) {
  return hasLoaded.value
    ? Number(value).toLocaleString("vi-VN")
    : "—"
}

const soConHan = computed(() =>
  danhSachBaiTap.value.filter(
    bt => assignmentStatus(bt).value === "con_han"
  ).length
)

const soQuaHan = computed(() =>
  danhSachBaiTap.value.filter(
    bt => assignmentStatus(bt).value === "qua_han"
  ).length
)

const tongBaiNopText = computed(() => {
  if (!hasLoaded.value) {
    return "—"
  }

  if (
    danhSachBaiTap.value.some(
      bt => bt.daNop === null
    )
  ) {
    return "—"
  }

  return danhSachBaiTap.value
    .reduce(
      (sum, bt) => sum + bt.daNop,
      0
    )
    .toLocaleString("vi-VN")
})

/* ==================================
   SEARCH
================================== */

const danhSachHienThi = computed(() => {
  const keyword = searchKeyword.value
    .trim()
    .toLocaleLowerCase("vi-VN")

  return danhSachBaiTap.value.filter(bt => {
    const content =
      `${bt.tieuDe} ${bt.moTa}`
        .toLocaleLowerCase("vi-VN")

    const matchKeyword =
      content.includes(keyword)

    const matchStatus =
      statusFilter.value === "all" ||
      assignmentStatus(bt).value ===
      statusFilter.value

    return matchKeyword && matchStatus
  })
})

function resetFilters() {
  searchKeyword.value = ""
  statusFilter.value = "all"
}

/* ==================================
   FETCH CLASSES
================================== */

async function fetchLopHoc() {
  const currentRequest = ++classRequestId

  loadingLop.value = true

  try {
    const response = await api.get(
      "/giang-vien/lop-hoc"
    )

    if (currentRequest !== classRequestId) {
      return
    }

    danhSachLop.value =
      extractList(response).map(normalizeLop)

  } catch (err) {
    if (currentRequest !== classRequestId) {
      return
    }

    error.value = getErrorMessage(
      err,
      "Không thể tải danh sách lớp học."
    )
  } finally {
    if (currentRequest === classRequestId) {
      loadingLop.value = false
    }
  }
}

/* ==================================
   FETCH ASSIGNMENTS
================================== */

async function fetchBaiTap() {
  if (loading.value) {
    return
  }

  const currentRequest = ++requestId

  loading.value = true
  error.value = ""

  try {
    const params = selectedLopId.value
      ? { lopId: selectedLopId.value }
      : {}

    const response = await api.get(
      "/giang-vien/bai-tap",
      { params }
    )

    if (currentRequest !== requestId) {
      return
    }

    danhSachBaiTap.value =
      extractList(response).map(normalizeBaiTap)

    hasLoaded.value = true

    lastUpdatedAt.value =
      new Date().toLocaleString("vi-VN")

  } catch (err) {
    if (currentRequest !== requestId) {
      return
    }

    error.value = getErrorMessage(
      err,
      "Không thể tải bài tập."
    )
  } finally {
    if (currentRequest === requestId) {
      loading.value = false
    }
  }
}

function changeClass() {
  hasLoaded.value = false
  danhSachBaiTap.value = []

  requestId++
  loading.value = false

  fetchBaiTap()
}

/* ==================================
   CREATE ASSIGNMENT
================================== */


function openCreateForm() {
  form.value = {
    lopId: selectedLopId.value,
    tieuDe: "",
    moTa: "",
    thoiGianBatDau: "",
    thoiGianKetThuc: "",
  }

  formError.value = ""
  showCreateForm.value = true
}


function closeCreateForm() {
  if (saving.value) return

  showCreateForm.value = false
  formError.value = ""
}


function toSqlDateTime(value) {
  if (!value) return ""

  // Chuyển:
  // 2026-10-28T08:00
  // thành:
  // 2026-10-28 08:00:00
  return value.replace("T", " ") + ":00"
}

async function createBaiTap() {
  formError.value = ""

  // Kiểm tra dữ liệu bắt buộc
  if (
    !form.value.lopId ||
    !form.value.tieuDe.trim() ||
    !form.value.thoiGianBatDau ||
    !form.value.thoiGianKetThuc
  ) {
    formError.value =
      "Vui lòng nhập đầy đủ các trường bắt buộc."

    return
  }

  // Lấy thời gian từ form
  const start = new Date(
    form.value.thoiGianBatDau
  )

  const end = new Date(
    form.value.thoiGianKetThuc
  )

  // Kiểm tra giờ bắt đầu
  if (
    Number.isNaN(start.getTime()) ||
    start.getTime() <= Date.now()
  ) {
    formError.value =
      "Giờ bắt đầu phải ở tương lai."

    return
  }

  // Kiểm tra giờ kết thúc
  if (
    Number.isNaN(end.getTime()) ||
    end.getTime() <= start.getTime()
  ) {
    formError.value =
      "Giờ kết thúc phải sau giờ bắt đầu."

    return
  }

  saving.value = true

  try {
    // Chuẩn bị dữ liệu gửi Laravel
    const payload = {
      lopId: Number(form.value.lopId),

      tieuDe:
        form.value.tieuDe.trim(),

      moTa:
        form.value.moTa.trim() || null,

      thoiGianBatDau:
        toSqlDateTime(
          form.value.thoiGianBatDau
        ),

      thoiGianKetThuc:
        toSqlDateTime(
          form.value.thoiGianKetThuc
        ),
    }

    // Gọi API tạo bài tập
    const response = await api.post(
      "/giang-vien/bai-tap",
      payload
    )

    // Kiểm tra kết quả
    if (
      response.data?.success === false ||
      response.data?.status === false
    ) {
      throw new Error(
        response.data?.message ||
        "Tạo bài tập thất bại."
      )
    }

    // Cập nhật lớp đang chọn
    selectedLopId.value = String(
      form.value.lopId
    )

    // Đóng modal
    showCreateForm.value = false

    // Thông báo
    success.value =
      "Đã tạo bài tập và đặt lịch thành công. " +
      "Hãy thêm câu hỏi trước giờ bắt đầu."

    hasLoaded.value = false

    // Tải lại danh sách bài tập
    await fetchBaiTap()

  } catch (err) {
    console.error(
      "Lỗi tạo bài tập:",
      err
    )

    if (err.response?.status === 422) {
      const errors =
        err.response.data?.errors

      const firstMessage = errors
        ? Object.values(errors).flat()[0]
        : null

      formError.value =
        firstMessage ||
        "Thời gian hoặc dữ liệu không hợp lệ."

    } else {
      formError.value = getErrorMessage(
        err,
        "Không thể tạo bài tập."
      )
    }

  } finally {
    saving.value = false
  }
}


/* ==================================
   SCHEDULE MODAL
================================== */

function openSchedule(bt) {
  selectedAssignment.value = bt
  showSchedule.value = true
}

function closeSchedule() {
  showSchedule.value = false
  selectedAssignment.value = null

  // Sau khi đóng, lấy lịch mới nhất từ Backend.
  fetchBaiTap()
}

/* ==================================
   LIFECYCLE
================================== */

onMounted(() => {
  fetchLopHoc()
  fetchBaiTap()

  clockTimer = setInterval(() => {
    nowMs.value = Date.now()
  }, 1000)
})

onBeforeUnmount(() => {
  requestId++
  classRequestId++

  clearInterval(clockTimer)
})
</script>

<style scoped>
* {
  box-sizing: border-box;
}

/* PAGE */
.assignment-page {
  width: 100%;
  min-height: calc(100vh - 80px);
  padding: 28px;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, system-ui, sans-serif;
}

/* HEADER */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 24px;
}

.eyebrow {
  color: #2563eb;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1px;
}

.page-header h1 {
  margin: 8px 0;
  font-size: 27px;
  font-weight: 800;
}

.page-header p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.7;
}

.header-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

/* BUTTONS */
.btn-primary,
.btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 11px 15px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 750;
  cursor: pointer;
  white-space: nowrap;
  text-decoration: none;
}

.btn-primary {
  border: 1px solid #2563eb;
  background: #2563eb;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-secondary {
  border: 1px solid #e2e8f0;
  background: white;
  color: #334155;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* SUMMARY */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: white;
}

.summary-icon {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  flex-shrink: 0;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 900;
}

.blue {
  background: #eff6ff;
  color: #2563eb;
}

.green {
  background: #f0fdf4;
  color: #16a34a;
}

.orange {
  background: #fff7ed;
  color: #ea580c;
}

.purple {
  background: #faf5ff;
  color: #9333ea;
}

.summary-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
  gap: 5px;
}

.summary-content span {
  color: #64748b;
  font-size: 11px;
}

.summary-content strong {
  font-size: 25px;
  font-weight: 800;
}

.summary-content small {
  color: #94a3b8;
  font-size: 10px;
}

/* PANEL */
.assignment-panel {
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: white;
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 22px;
}

.panel-header h2 {
  margin: 0 0 6px;
  font-size: 17px;
}

.panel-header p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
}

.result-badge {
  padding: 7px 12px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
}

/* TOOLBAR */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 0 22px 22px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 9px;
  flex: 1;
  min-width: 220px;
  padding: 0 13px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.search-box span {
  color: #94a3b8;
  font-size: 23px;
}

.search-box input {
  width: 100%;
  padding: 12px 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: inherit;
  font-size: 12px;
}

.filter-select {
  min-width: 175px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #334155;
  font-size: 12px;
}

/* GRID */
.assignment-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 17px;
  padding: 0 22px 22px;
}

.assignment-card {
  display: flex;
  flex-direction: column;
  min-width: 0;
  padding: 19px;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: white;
  transition: 0.2s ease;
}

.assignment-card:hover {
  transform: translateY(-2px);
  border-color: #bfdbfe;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}

.assignment-icon {
  display: grid;
  place-items: center;
  width: 43px;
  height: 43px;
  border-radius: 11px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 900;
}

.assignment-icon.exam {
  background: #faf5ff;
  color: #9333ea;
}

/* STATUS */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 800;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-active {
  background: #dcfce7;
  color: #15803d;
}

.status-expired {
  background: #fee2e2;
  color: #b91c1c;
}

.status-unknown {
  background: #fef3c7;
  color: #92400e;
}

/* CARD CONTENT */
.card-main {
  flex: 1;
  margin-top: 16px;
}

.class-label {
  color: #2563eb;
  font-size: 10px;
  font-weight: 800;
}

.card-main h3 {
  margin: 8px 0;
  color: #172033;
  font-size: 16px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.description {
  color: #64748b;
  font-size: 11px;
  line-height: 1.8;
  overflow-wrap: anywhere;
}

.card-divider {
  height: 1px;
  margin: 17px 0;
  background: #e5e7eb;
}

.card-details {
  display: flex;
  flex-direction: column;
  gap: 13px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  font-size: 11px;
}

.detail-row span {
  color: #64748b;
}

.detail-row strong {
  color: #334155;
  text-align: right;
  font-size: 11px;
}

.submission-count {
  color: #2563eb !important;
}

/* EXAM STATUS */
.exam-state {
  display: inline-flex;
  align-self: flex-start;
  align-items: center;
  gap: 7px;
  padding: 9px 12px;
  border-radius: 9px;
  font-size: 10px;
  font-weight: 800;
}

.exam-unscheduled {
  background: #f1f5f9;
  color: #64748b;
}

.exam-upcoming {
  background: #dbeafe;
  color: #1d4ed8;
}

.exam-running {
  background: #dcfce7;
  color: #15803d;
}

.exam-ended {
  background: #fee2e2;
  color: #b91c1c;
}

/* ACTIONS */
.card-actions {
  display: flex;
  flex-direction: column;
  gap: 9px;
  margin-top: 19px;
}

.card-action {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 12px 14px;
  border: none;
  border-radius: 9px;
  font: inherit;
  font-size: 11px;
  font-weight: 800;
  text-decoration: none;
  cursor: pointer;
}

.questions-action {
  background: #eff6ff;
  color: #2563eb;
}

.questions-action:hover {
  background: #dbeafe;
}

.schedule-action {
  background: #faf5ff;
  color: #9333ea;
}

.schedule-action:hover {
  background: #f3e8ff;
}

.submissions-action {
  background: #f0fdf4;
  color: #15803d;
}

.submissions-action:hover {
  background: #dcfce7;
}

/* FOOTER */
.panel-footer {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 12px;
  padding: 16px 22px;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

/* ALERTS */
.alert {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin: 0 22px 20px;
  padding: 15px;
  border-radius: 10px;
  font-size: 12px;
}

.alert p {
  margin: 5px 0 0;
}

.alert button {
  border: none;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

.error-alert {
  border: 1px solid #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

.success-alert {
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #15803d;
}

/* EMPTY */
.state-box {
  display: flex;
  min-height: 250px;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 35px;
  text-align: center;
}

.state-box p {
  color: #64748b;
  font-size: 11px;
}

.empty-symbol {
  font-size: 35px;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* MODAL */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(3px);
}

.create-modal,
.schedule-modal {
  width: 100%;
  max-height: 92vh;
  overflow-y: auto;
  border-radius: 18px;
  background: white;
  box-shadow: 0 20px 70px rgba(0, 0, 0, 0.2);
}

.create-modal {
  max-width: 590px;
}

.schedule-modal {
  max-width: 740px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 14px;
  padding: 23px 26px 19px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 7px 0;
  font-size: 20px;
}

.modal-header p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
  line-height: 1.7;
}

.close-modal {
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 8px;
  background: #f1f5f9;
  color: #475569;
  font-size: 23px;
  cursor: pointer;
}

.modal-body {
  padding: 23px 26px;
}

.form-group {
  margin-bottom: 19px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 800;
}

.form-group label span {
  color: #dc2626;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  outline: none;
  background: white;
  color: #172033;
  font: inherit;
  font-size: 12px;
}

.form-group textarea {
  resize: vertical;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  border-color: #2563eb;
}

.form-group small {
  display: block;
  margin-top: 7px;
  color: #94a3b8;
  font-size: 10px;
  line-height: 1.7;
}

.form-error {
  padding: 12px;
  border-radius: 9px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 12px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 17px 26px;
  border-top: 1px solid #e5e7eb;
}

/* SCHEDULE MODAL */
.schedule-content {
  padding: 20px 23px;
}

.schedule-content :deep(.schedule-panel) {
  margin-bottom: 15px;
}

.schedule-note {
  padding: 15px;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  background: #eff6ff;
  color: #1e40af;
  font-size: 11px;
  line-height: 1.8;
}

.schedule-note p {
  margin: 8px 0 0;
}

/* DARK MODE */
:global(html.dark-theme) .assignment-page {
  background: #1f2124;
  color: white;
}

:global(html.dark-theme) .summary-card,
:global(html.dark-theme) .assignment-panel,
:global(html.dark-theme) .assignment-card,
:global(html.dark-theme) .create-modal,
:global(html.dark-theme) .schedule-modal {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .card-main h3,
:global(html.dark-theme) .summary-content strong,
:global(html.dark-theme) .detail-row strong,
:global(html.dark-theme) .modal-header h2 {
  color: white;
}

:global(html.dark-theme) .search-box,
:global(html.dark-theme) .filter-select,
:global(html.dark-theme) .form-group input,
:global(html.dark-theme) .form-group select,
:global(html.dark-theme) .form-group textarea,
:global(html.dark-theme) .btn-secondary {
  background: #202327;
  color: white;
  border-color: #334155;
}

:global(html.dark-theme) .card-divider {
  background: #334155;
}

:global(html.dark-theme) .panel-footer,
:global(html.dark-theme) .modal-header,
:global(html.dark-theme) .modal-footer {
  border-color: #334155;
}

:global(html.dark-theme) .questions-action {
  background: #1e3a5f;
  color: #93c5fd;
}

:global(html.dark-theme) .schedule-action {
  background: #392456;
  color: #d8b4fe;
}

:global(html.dark-theme) .submissions-action {
  background: #153b29;
  color: #86efac;
}

:global(html.dark-theme) .schedule-note {
  background: #1e293b;
  border-color: #334155;
  color: #bfdbfe;
}

:global(html.dark-theme) .exam-unscheduled {
  background: #202327;
  color: #cbd5e1;
}

:global(html.dark-theme) .exam-upcoming {
  background: #1e3a5f;
  color: #93c5fd;
}

:global(html.dark-theme) .exam-running {
  background: #153b29;
  color: #86efac;
}

:global(html.dark-theme) .exam-ended {
  background: #451a1a;
  color: #fca5a5;
}

/* RESPONSIVE */
@media (max-width: 1250px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .assignment-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .assignment-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .page-header h1 {
    font-size: 23px;
  }

  .toolbar {
    flex-direction: column;
  }

  .search-box,
  .filter-select {
    width: 100%;
  }

  .assignment-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 470px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 17px;
    padding-right: 17px;
  }

  .schedule-content {
    padding: 15px;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
