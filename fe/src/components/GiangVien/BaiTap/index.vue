
<template>
  <div class="assignment-page">

    <!-- HEADER -->
    <header class="page-header">
      <div class="page-heading">
        <span class="eyebrow">QUẢN LÝ GIẢNG DẠY</span>
        <h1>📝 Quản lý bài tập</h1>
        <p>
          Tạo bài tập, quản lý thời hạn và theo dõi
          tiến độ nộp bài của sinh viên.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn-secondary"
          :disabled="loading"
          @click="fetchBaiTap"
        >
          <span :class="{ spinning: loading }">↻</span>
          Làm mới
        </button>

        <button
          type="button"
          class="btn-primary"
          :disabled="loadingLop || danhSachLop.length === 0"
          @click="openCreateForm"
        >
          <span class="plus-icon">+</span>
          Tạo bài tập mới
        </button>
      </div>
    </header>

    <!-- THỐNG KÊ -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon blue">BT</div>
        <div class="summary-content">
          <span>Tổng bài tập</span>
          <strong>{{ summaryText(danhSachBaiTap.length) }}</strong>
          <small>Trong phạm vi lớp đã chọn</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon green">✓</div>
        <div class="summary-content">
          <span>Còn hạn nộp</span>
          <strong>{{ summaryText(soConHan) }}</strong>
          <small>Bài tập đang nhận bài</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon orange">!</div>
        <div class="summary-content">
          <span>Đã quá hạn</span>
          <strong>{{ summaryText(soQuaHan) }}</strong>
          <small>Bài tập đã đến hạn nộp</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon purple">BN</div>
        <div class="summary-content">
          <span>Lượt bài đã nộp</span>
          <strong>{{ tongBaiNopText }}</strong>
          <small>Thống kê từ dữ liệu Backend</small>
        </div>
      </div>
    </div>

    <!-- DANH SÁCH BÀI TẬP -->
    <section class="assignment-panel">

      <div class="panel-header">
        <div>
          <h2>Danh sách bài tập</h2>
          <p>
            Quản lý bài tập trong các lớp đang phụ trách
          </p>
        </div>

        <span class="result-badge">
          {{ danhSachHienThi.length }} bài tập
        </span>
      </div>

      <!-- BỘ LỌC -->
      <div class="toolbar">
        <div class="search-box">
          <span class="search-icon">⌕</span>

          <input
            v-model="searchKeyword"
            type="search"
            placeholder="Tìm tiêu đề hoặc nội dung bài tập..."
            aria-label="Tìm kiếm bài tập"
          />
        </div>

        <select
          v-model="selectedLopId"
          class="filter-select"
          :disabled="loadingLop"
          aria-label="Lọc theo lớp học"
          @change="changeClass"
        >
          <option value="">Tất cả lớp học</option>

          <option
            v-for="lop in danhSachLop"
            :key="lop.id"
            :value="String(lop.id)"
          >
            {{ lop.ten_lop }}
          </option>
        </select>

        <select
          v-model="statusFilter"
          class="filter-select status-select"
          aria-label="Lọc theo hạn nộp"
        >
          <option value="all">Tất cả thời hạn</option>
          <option value="con_han">Còn hạn nộp</option>
          <option value="qua_han">Đã quá hạn</option>
          <option value="khong_xac_dinh">Chưa xác định</option>
        </select>
      </div>

      <!-- THÔNG BÁO -->
      <div v-if="error" class="alert error-alert">
        <div>
          <strong>Không thể tải dữ liệu</strong>
          <p>{{ error }}</p>
        </div>
        <button type="button" @click="fetchBaiTap">
          Thử lại
        </button>
      </div>

      <div v-if="success" class="alert success-alert" role="status">
        <span>✓</span>
        {{ success }}
        <button
          type="button"
          aria-label="Đóng thông báo"
          @click="success = ''"
        >
          ×
        </button>
      </div>

      <!-- LOADING -->
      <div
        v-if="loading && !hasLoaded"
        class="state-box"
        role="status"
      >
        <div class="spinner"></div>
        <strong>Đang tải danh sách bài tập...</strong>
        <p>Hệ thống đang lấy dữ liệu từ máy chủ.</p>
      </div>

      <!-- DANH SÁCH TRỐNG -->
      <div
        v-else-if="hasLoaded && danhSachBaiTap.length === 0"
        class="state-box"
      >
        <div class="empty-symbol">📝</div>
        <strong>Chưa có bài tập</strong>
        <p>
          Hiện chưa có bài tập nào trong phạm vi lớp
          học đã chọn.
        </p>

        <button
          type="button"
          class="btn-primary"
          :disabled="danhSachLop.length === 0"
          @click="openCreateForm"
        >
          + Tạo bài tập đầu tiên
        </button>
      </div>

      <!-- KHÔNG CÓ KẾT QUẢ TÌM KIẾM -->
      <div
        v-else-if="hasLoaded && danhSachHienThi.length === 0"
        class="state-box"
      >
        <div class="empty-symbol">⌕</div>
        <strong>Không tìm thấy bài tập</strong>
        <p>Hãy thử thay đổi từ khóa hoặc bộ lọc.</p>

        <button
          type="button"
          class="btn-secondary"
          @click="resetFilters"
        >
          Xóa bộ lọc
        </button>
      </div>

      <!-- GRID BÀI TẬP -->
      <div v-else-if="hasLoaded" class="assignment-grid">

        <article
          v-for="bt in danhSachHienThi"
          :key="bt.id"
          class="assignment-card"
        >

          <div class="card-header">
            <div class="assignment-icon">
              BT
            </div>

            <span
              class="status-badge"
              :class="assignmentStatus(bt).className"
            >
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
                "Bài tập chưa có phần mô tả nội dung."
              }}
            </p>
          </div>

          <div class="card-divider"></div>

          <div class="card-details">

            <div class="detail-row">
              <div class="detail-left">
                <span class="detail-symbol">▦</span>
                <span>Hạn nộp</span>
              </div>

              <strong>
                {{ formatDate(bt.hanNop) }}
              </strong>
            </div>

            <div class="detail-row">
              <div class="detail-left">
                <span class="detail-symbol">▤</span>
                <span>Bài đã nộp</span>
              </div>

              <strong class="submission-count">
                {{ formatCount(bt.daNop) }}
              </strong>
            </div>

          </div>

          <!-- XEM BÀI NỘP -->
          <router-link
            :to="{
              path: '/giang-vien/bai-nop',
              query: { baiTapId: bt.id }
            }"
            class="card-action"
          >
            Xem bài nộp
            <span>→</span>
          </router-link>

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

    <!-- POPUP TẠO BÀI TẬP -->
    <div
      v-if="showCreateForm"
      class="modal-overlay"
      @click.self="closeCreateForm"
    >
      <div
        class="create-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-title"
      >

        <div class="modal-header">
          <div>
            <span class="eyebrow">QUẢN LÝ BÀI TẬP</span>
            <h2 id="create-title">Tạo bài tập mới</h2>
            <p>
              Nhập thông tin bài tập để giao cho lớp học.
            </p>
          </div>

          <button
            type="button"
            class="close-modal"
            aria-label="Đóng"
            :disabled="saving"
            @click="closeCreateForm"
          >
            ×
          </button>
        </div>

        <form @submit.prevent="createBaiTap">
          <div class="modal-body">

            <div class="form-group">
              <label for="assignment-class">
                Lớp học <span>*</span>
              </label>

              <select
                id="assignment-class"
                v-model="form.lopId"
                required
                :disabled="saving"
              >
                <option value="">-- Chọn lớp học --</option>

                <option
                  v-for="lop in danhSachLop"
                  :key="lop.id"
                  :value="String(lop.id)"
                >
                  {{ lop.ten_lop }}
                  ({{ lop.ma_lop || "Chưa có mã" }})
                </option>
              </select>
            </div>

            <div class="form-group">
              <label for="assignment-title">
                Tiêu đề bài tập <span>*</span>
              </label>

              <input
                id="assignment-title"
                v-model.trim="form.tieuDe"
                type="text"
                maxlength="200"
                placeholder="Ví dụ: Bài tập thực hành tuần 3"
                required
                :disabled="saving"
              />
            </div>

            <div class="form-group">
              <label for="assignment-description">
                Mô tả bài tập
              </label>

              <textarea
                id="assignment-description"
                v-model.trim="form.moTa"
                rows="5"
                placeholder="Nhập yêu cầu và hướng dẫn thực hiện bài tập..."
                :disabled="saving"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="assignment-deadline">
                Hạn nộp <span>*</span>
              </label>

              <input
                id="assignment-deadline"
                v-model="form.hanNop"
                type="datetime-local"
                required
                :disabled="saving"
              />

              <small>
                Hạn nộp phải là thời gian trong tương lai.
              </small>
            </div>

            <div
              v-if="formError"
              class="form-error"
              role="alert"
            >
              {{ formError }}
            </div>

          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn-secondary"
              :disabled="saving"
              @click="closeCreateForm"
            >
              Hủy
            </button>

            <button
              type="submit"
              class="btn-primary"
              :disabled="saving"
            >
              {{ saving ? "Đang tạo..." : "Tạo bài tập" }}
            </button>
          </div>
        </form>

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

/* ==========================
   STATE
========================== */
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

const form = ref({
  lopId: "",
  tieuDe: "",
  moTa: "",
  hanNop: "",
})

let requestId = 0
let classRequestId = 0

/* ==========================
   API HELPERS
========================== */
function extractList(response) {
  const body = response?.data

  if (body?.success === false || body?.status === false) {
    throw new Error(body.message || "API trả về lỗi")
  }

  const data = body?.data ?? body

  if (Array.isArray(data)) {
    return data
  }

  if (Array.isArray(data?.data)) {
    return data.data
  }

  throw new Error("API không trả về danh sách hợp lệ")
}

/* ==========================
   CHUẨN HÓA LỚP HỌC
========================== */
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

/* ==========================
   CHUẨN HÓA BÀI TẬP
========================== */
function normalizeBaiTap(item) {
  return {
    id: item.id,
    lopId: item.lopId ?? item.lop_hoc_id ?? null,
    tieuDe: item.tieuDe ?? item.tieu_de ?? "",
    moTa: item.moTa ?? item.mo_ta ?? "",
    hanNop: item.hanNop ?? item.han_nop ?? null,
    daNop: normalizeCount(
      item.daNop ?? item.bai_nops_count ?? null
    ),
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

  return Number.isInteger(number) && number >= 0
    ? number
    : null
}

function formatCount(value) {
  return value === null
    ? "—"
    : value.toLocaleString("vi-VN")
}

/* ==========================
   TÊN LỚP HỌC
========================== */
function className(lopId) {
  const lop = danhSachLop.value.find(
    (item) => String(item.id) === String(lopId)
  )

  return lop
    ? `${lop.ten_lop} · ${lop.ma_lop || "Chưa có mã"}`
    : "Lớp học"
}

/* ==========================
   HẠN NỘP
========================== */
function parseDate(value) {
  if (!value) return null

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? null : date
}

function formatDate(value) {
  const date = parseDate(value)

  if (!date) return "Chưa cập nhật"

  return new Intl.DateTimeFormat("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(date)
}

function assignmentStatus(bt) {
  const date = parseDate(bt.hanNop)

  if (!date) {
    return {
      text: "Chưa xác định",
      className: "status-unknown",
      value: "khong_xac_dinh",
    }
  }

  if (date.getTime() < Date.now()) {
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

/* ==========================
   THỐNG KÊ
========================== */
function summaryText(value) {
  return hasLoaded.value
    ? Number(value).toLocaleString("vi-VN")
    : "—"
}

const soConHan = computed(() =>
  danhSachBaiTap.value.filter(
    (bt) => assignmentStatus(bt).value === "con_han"
  ).length
)

const soQuaHan = computed(() =>
  danhSachBaiTap.value.filter(
    (bt) => assignmentStatus(bt).value === "qua_han"
  ).length
)

const tongBaiNopText = computed(() => {
  if (!hasLoaded.value) return "—"

  if (
    danhSachBaiTap.value.some((bt) => bt.daNop === null)
  ) {
    return "—"
  }

  return danhSachBaiTap.value
    .reduce((total, bt) => total + bt.daNop, 0)
    .toLocaleString("vi-VN")
})

/* ==========================
   TÌM KIẾM
========================== */
const danhSachHienThi = computed(() => {
  const keyword = searchKeyword.value
    .trim()
    .toLocaleLowerCase("vi-VN")

  return danhSachBaiTap.value.filter((bt) => {
    const content =
      `${bt.tieuDe} ${bt.moTa}`
        .toLocaleLowerCase("vi-VN")

    const matchKeyword = content.includes(keyword)

    const matchStatus =
      statusFilter.value === "all" ||
      assignmentStatus(bt).value === statusFilter.value

    return matchKeyword && matchStatus
  })
})

function resetFilters() {
  searchKeyword.value = ""
  statusFilter.value = "all"
}

/* ==========================
   LOAD DANH SÁCH LỚP
========================== */
async function fetchLopHoc() {
  const currentRequest = ++classRequestId

  loadingLop.value = true

  try {
    const response = await api.get(
      "/giang-vien/lop-hoc"
    )

    if (currentRequest !== classRequestId) return

    danhSachLop.value =
      extractList(response).map(normalizeLop)

  } catch (err) {
    if (currentRequest !== classRequestId) return

    console.error("Lỗi tải lớp học:", err)

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

/* ==========================
   LOAD DANH SÁCH BÀI TẬP
========================== */
async function fetchBaiTap() {
  if (loading.value) return

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

    if (currentRequest !== requestId) return

    danhSachBaiTap.value =
      extractList(response).map(normalizeBaiTap)

    hasLoaded.value = true

    lastUpdatedAt.value =
      new Date().toLocaleString("vi-VN")

  } catch (err) {
    if (currentRequest !== requestId) return

    console.error("Lỗi tải bài tập:", err)

    error.value = getErrorMessage(
      err,
      "Không thể tải danh sách bài tập."
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

  // Hủy hiệu lực các request cũ nếu đang tải.
  requestId++
  loading.value = false

  fetchBaiTap()
}

/* ==========================
   FORM TẠO BÀI TẬP
========================== */
function openCreateForm() {
  form.value = {
    lopId: selectedLopId.value,
    tieuDe: "",
    moTa: "",
    hanNop: "",
  }

  formError.value = ""
  showCreateForm.value = true
}

function closeCreateForm() {
  if (saving.value) return

  showCreateForm.value = false
  formError.value = ""
}

/* ==========================
   TẠO BÀI TẬP
========================== */
async function createBaiTap() {
  formError.value = ""

  if (
    !form.value.lopId ||
    !form.value.tieuDe.trim() ||
    !form.value.hanNop
  ) {
    formError.value =
      "Vui lòng nhập đầy đủ các trường bắt buộc."
    return
  }

  const deadline = new Date(form.value.hanNop)

  if (
    Number.isNaN(deadline.getTime()) ||
    deadline.getTime() <= Date.now()
  ) {
    formError.value =
      "Hạn nộp phải là thời gian trong tương lai."
    return
  }

  saving.value = true

  try {
    const payload = {
      lopId: Number(form.value.lopId),
      tieuDe: form.value.tieuDe.trim(),
      moTa: form.value.moTa.trim() || null,
      hanNop: form.value.hanNop,
    }

    const response = await api.post(
      "/giang-vien/bai-tap",
      payload
    )

    if (
      response.data?.success === false ||
      response.data?.status === false
    ) {
      throw new Error(
        response.data.message || "Tạo bài tập thất bại"
      )
    }

    selectedLopId.value = String(form.value.lopId)

    showCreateForm.value = false
    success.value = "Đã tạo bài tập thành công."

    hasLoaded.value = false
    await fetchBaiTap()

  } catch (err) {
    console.error("Lỗi tạo bài tập:", err)

    if (err.response?.status === 422) {
      const errors = err.response.data?.errors
      const firstMessage = errors
        ? Object.values(errors).flat()[0]
        : null

      formError.value =
        firstMessage ||
        "Dữ liệu không hợp lệ. Vui lòng kiểm tra lại."
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

/* ==========================
   LỖI API
========================== */
function getErrorMessage(err, fallback) {
  const status = err.response?.status

  if (status === 401) {
    return "Phiên đăng nhập không hợp lệ hoặc đã hết hạn."
  }

  if (status === 403) {
    return "Bạn không có quyền thực hiện thao tác này."
  }

  if (status === 404) {
    return "Không tìm thấy API hoặc tài nguyên."
  }

  if (status >= 500) {
    return "Máy chủ gặp lỗi khi xử lý dữ liệu."
  }

  return err.message || fallback
}

/* ==========================
   LIFECYCLE
========================== */
onMounted(() => {
  fetchLopHoc()
  fetchBaiTap()
})

onBeforeUnmount(() => {
  requestId++
  classRequestId++
})
</script>

<style scoped>
/* PAGE */
.assignment-page {
  width: 100%;
  min-height: calc(100vh - 80px);
  padding: 28px;
  box-sizing: border-box;
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

.page-heading h1 {
  margin: 8px 0;
  font-size: 28px;
  font-weight: 800;
}

.page-heading p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
  line-height: 1.7;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-primary,
.btn-secondary {
  display: inline-flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  padding: 11px 16px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  white-space: nowrap;
}

.btn-primary {
  border: 1px solid #2563eb;
  background: #2563eb;
  color: #fff;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-secondary {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
}

.btn-primary:disabled,
.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.plus-icon {
  font-size: 19px;
  line-height: 1;
}

/* SUMMARY */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 19px;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.summary-icon {
  width: 47px;
  height: 47px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 900;
}

.summary-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.summary-content span {
  color: #64748b;
  font-size: 11px;
}

.summary-content strong {
  color: #172033;
  font-size: 26px;
  font-weight: 800;
}

.summary-content small {
  color: #94a3b8;
  font-size: 10px;
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

/* MAIN PANEL */
.assignment-panel {
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
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
  font-weight: 800;
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
  font-weight: 700;
}

/* FILTERS */
.toolbar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  padding: 0 22px 22px;
}

.search-box {
  flex: 1;
  min-width: 230px;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 0 13px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.search-box:focus-within {
  border-color: #60a5fa;
}

.search-icon {
  color: #94a3b8;
  font-size: 24px;
}

.search-box input {
  width: 100%;
  padding: 12px 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: #172033;
  font-size: 12px;
}

.filter-select {
  min-width: 160px;
  max-width: 240px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #334155;
  font-size: 12px;
  outline: 0;
}

/* CARDS */
.assignment-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 17px;
  padding: 0 22px 22px;
}

.assignment-card {
  min-width: 0;
  display: flex;
  flex-direction: column;
  padding: 19px;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #fff;
  transition: 0.2s ease;
}

.assignment-card:hover {
  transform: translateY(-3px);
  border-color: #bfdbfe;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}

.assignment-icon {
  width: 43px;
  height: 43px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 900;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 10px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
  white-space: nowrap;
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

.card-main {
  flex: 1;
  margin-top: 16px;
}

.class-label {
  color: #2563eb;
  font-size: 10px;
  font-weight: 700;
  line-height: 1.5;
}

.card-main h3 {
  margin: 8px 0;
  color: #172033;
  font-size: 16px;
  font-weight: 800;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.description {
  color: #64748b;
  font-size: 11px;
  line-height: 1.8;
  overflow-wrap: anywhere;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  overflow: hidden;
}

.card-divider {
  height: 1px;
  margin: 18px 0 14px;
  background: #e5e7eb;
}

.card-details {
  display: flex;
  flex-direction: column;
  gap: 13px;
}

.detail-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  font-size: 11px;
}

.detail-left {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
}

.detail-symbol {
  color: #94a3b8;
  font-size: 16px;
}

.detail-row strong {
  font-size: 11px;
  color: #334155;
  text-align: right;
}

.submission-count {
  color: #2563eb !important;
}

.card-action {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 19px;
  padding: 12px 14px;
  border-radius: 9px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
}

.card-action:hover {
  background: #dbeafe;
}

/* FOOTER */
.panel-footer {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 16px 22px;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

/* MESSAGES */
.alert {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin: 0 22px 20px;
  padding: 15px;
  border-radius: 11px;
  font-size: 12px;
}

.error-alert {
  border: 1px solid #fecaca;
  background: #fef2f2;
  color: #b91c1c;
}

.success-alert {
  background: #f0fdf4;
  color: #15803d;
  border: 1px solid #bbf7d0;
}

.alert strong {
  font-size: 12px;
}

.alert p {
  margin: 4px 0 0;
}

.alert button {
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

/* EMPTY */
.state-box {
  min-height: 280px;
  padding: 35px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 12px;
  text-align: center;
}

.state-box strong {
  font-size: 14px;
}

.state-box p {
  color: #64748b;
  font-size: 11px;
  line-height: 1.7;
}

.empty-symbol {
  font-size: 35px;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #dbeafe;
  border-top: 3px solid #2563eb;
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
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(3px);
}

.create-modal {
  width: 100%;
  max-width: 590px;
  max-height: 92vh;
  overflow-y: auto;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 20px 70px rgba(0, 0, 0, 0.2);
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
  margin: 7px 0 6px;
  font-size: 21px;
  font-weight: 800;
}

.modal-header p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
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
  font-weight: 700;
}

.form-group label span {
  color: #dc2626;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  outline: none;
  background: #fff;
  color: #172033;
  font: inherit;
  font-size: 12px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}

.form-group textarea {
  resize: vertical;
}

.form-group small {
  display: block;
  margin-top: 7px;
  color: #94a3b8;
  font-size: 10px;
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

/* DARK MODE */
:global(html.dark-theme) .assignment-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-heading h1,
:global(html.dark-theme) .summary-content strong,
:global(html.dark-theme) .panel-header h2,
:global(html.dark-theme) .card-main h3,
:global(html.dark-theme) .modal-header h2,
:global(html.dark-theme) .detail-row strong {
  color: #fff;
}

:global(html.dark-theme) .summary-card,
:global(html.dark-theme) .assignment-panel,
:global(html.dark-theme) .assignment-card,
:global(html.dark-theme) .create-modal {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .search-box,
:global(html.dark-theme) .filter-select,
:global(html.dark-theme) .form-group input,
:global(html.dark-theme) .form-group select,
:global(html.dark-theme) .form-group textarea,
:global(html.dark-theme) .btn-secondary {
  background: #202327;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .search-box input {
  color: #fff;
}

:global(html.dark-theme) .card-divider {
  background: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .modal-header,
:global(html.dark-theme) .modal-footer,
:global(html.dark-theme) .panel-footer {
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .card-action {
  background: rgba(37, 99, 235, 0.15);
  color: #60a5fa;
}

:global(html.dark-theme) .blue {
  background: rgba(37, 99, 235, 0.15);
  color: #60a5fa;
}

:global(html.dark-theme) .green {
  background: rgba(22, 163, 74, 0.15);
  color: #4ade80;
}

:global(html.dark-theme) .orange {
  background: rgba(234, 88, 12, 0.15);
  color: #fb923c;
}

:global(html.dark-theme) .purple {
  background: rgba(147, 51, 234, 0.15);
  color: #c084fc;
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

  .page-heading h1 {
    font-size: 23px;
  }

  .header-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .search-box {
    min-width: 0;
  }

  .filter-select {
    width: 100%;
    max-width: none;
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
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
