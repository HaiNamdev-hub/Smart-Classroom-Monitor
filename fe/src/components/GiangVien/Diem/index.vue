
<template>
  <div class="score-page">

    <!-- HEADER -->
    <header class="page-header">
      <div>
        <span class="eyebrow">QUẢN LÝ GIẢNG DẠY</span>
        <h1>📊 Quản lý điểm số</h1>
        <p>
          Theo dõi kết quả học tập, tra cứu bảng điểm
          và đánh giá bài làm của sinh viên.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn-outline"
          :disabled="busy || !selectedLopId"
          @click="fetchBangDiem"
        >
          <span :class="{ spinning: loading }">↻</span>
          {{ loading ? "Đang tải..." : "Làm mới" }}
        </button>

        <button
          type="button"
          class="btn-primary"
          :disabled="!hasLoaded || filteredScores.length === 0"
          @click="exportCSV"
        >
          ↓ Xuất bảng điểm
        </button>
      </div>
    </header>

    <!-- CHỌN LỚP -->
    <section class="class-panel">
      <div class="class-icon">LH</div>

      <div class="class-field">
        <label for="lop-select">Lớp học đang xem</label>

        <select
          id="lop-select"
          v-model="selectedLopId"
          :disabled="loadingLop"
          @change="changeClass"
        >
          <option value="">-- Chọn lớp học --</option>

          <option
            v-for="lop in danhSachLop"
            :key="lop.id"
            :value="String(lop.id)"
          >
            {{ lop.ten_lop }} ({{ lop.ma_lop || "Chưa có mã" }})
          </option>
        </select>
      </div>

      <router-link
        to="/giang-vien/bai-nop"
        class="class-link"
      >
        Đến trang chấm điểm →
      </router-link>
    </section>

    <!-- THỐNG KÊ -->
    <div class="stats-grid">

      <div class="stat-card">
        <div class="stat-icon blue">Đ</div>

        <div class="stat-content">
          <span>Bài đã chấm</span>
          <strong>{{ statText(bangDiem.length) }}</strong>
          <small>Tổng bản ghi điểm của lớp</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">TB</div>

        <div class="stat-content">
          <span>Điểm trung bình</span>
          <strong>{{ diemTrungBinh }}</strong>
          <small>Trung bình các bài đã chấm</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">✓</div>

        <div class="stat-content">
          <span>Kết quả đạt</span>
          <strong>{{ statText(soDat) }}</strong>
          <small>Theo đánh giá giảng viên</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red">!</div>

        <div class="stat-content">
          <span>Kết quả không đạt</span>
          <strong>{{ statText(soKhongDat) }}</strong>
          <small>Cần xem xét kết quả học tập</small>
        </div>
      </div>

    </div>

    <!-- BẢNG ĐIỂM -->
    <section class="table-panel">

      <div class="panel-heading">
        <div>
          <h2>Bảng điểm bài tập</h2>

          <p>
            {{
              selectedClass
                ? `${selectedClass.ten_lop} · ${selectedClass.ma_lop || "Chưa có mã"}`
                : "Chọn lớp để xem kết quả chấm điểm"
            }}
          </p>
        </div>

        <span class="count-label">
          {{ filteredScores.length }} kết quả
        </span>
      </div>

      <!-- TÌM KIẾM VÀ LỌC -->
      <div class="toolbar">
        <div class="search-box">
          <span class="search-icon">⌕</span>

          <input
            v-model="keyword"
            type="search"
            placeholder="Tìm sinh viên hoặc tên bài tập..."
            aria-label="Tìm kiếm bảng điểm"
            :disabled="!hasLoaded"
          />
        </div>

        <select
          v-model="resultFilter"
          class="filter-select"
          :disabled="!hasLoaded"
          aria-label="Lọc theo kết quả"
        >
          <option value="all">Tất cả kết quả</option>
          <option value="dat">Đạt</option>
          <option value="khong_dat">Không đạt</option>
          <option value="unknown">Chưa đánh giá</option>
        </select>

        <select
          v-model="scoreFilter"
          class="filter-select"
          :disabled="!hasLoaded"
          aria-label="Lọc theo khoảng điểm"
        >
          <option value="all">Tất cả điểm số</option>
          <option value="excellent">Từ 8 đến 10</option>
          <option value="middle">Từ 5 đến dưới 8</option>
          <option value="low">Dưới 5</option>
          <option value="invalid">Chưa có điểm hợp lệ</option>
        </select>

        <button
          v-if="keyword || resultFilter !== 'all' || scoreFilter !== 'all'"
          type="button"
          class="clear-btn"
          @click="clearFilters"
        >
          Xóa bộ lọc
        </button>
      </div>

      <!-- THÔNG BÁO LỖI -->
      <div v-if="error" class="error-box" role="alert">
        <div>
          <strong>Không thể tải dữ liệu bảng điểm</strong>
          <p>{{ error }}</p>
        </div>

        <button type="button" @click="refreshData">
          Thử lại
        </button>
      </div>

      <!-- LOADING -->
      <div v-if="busy" class="state-box" role="status">
        <div class="spinner"></div>
        <strong>Đang tải bảng điểm...</strong>
        <p>Hệ thống đang lấy dữ liệu từ máy chủ.</p>
      </div>

      <!-- CHƯA CHỌN LỚP -->
      <div v-else-if="!selectedLopId" class="state-box">
        <div class="state-icon">📚</div>
        <strong>Hãy chọn một lớp học</strong>
        <p>
          Chọn lớp ở phía trên để xem bảng điểm của sinh viên.
        </p>
      </div>

      <!-- CHƯA CÓ ĐIỂM -->
      <div
        v-else-if="hasLoaded && bangDiem.length === 0"
        class="state-box"
      >
        <div class="state-icon">📊</div>
        <strong>Chưa có dữ liệu điểm</strong>
        <p>
          Lớp học này chưa có bài nộp được chấm điểm.
        </p>

        <router-link
          to="/giang-vien/bai-nop"
          class="btn-primary state-action"
        >
          Đi đến chấm điểm →
        </router-link>
      </div>

      <!-- KHÔNG CÓ KẾT QUẢ -->
      <div
        v-else-if="hasLoaded && filteredScores.length === 0"
        class="state-box"
      >
        <div class="state-icon">⌕</div>
        <strong>Không tìm thấy kết quả phù hợp</strong>
        <p>Hãy thay đổi từ khóa hoặc bộ lọc tìm kiếm.</p>

        <button
          type="button"
          class="btn-outline"
          @click="clearFilters"
        >
          Xóa bộ lọc
        </button>
      </div>

      <!-- TABLE -->
      <div v-else-if="hasLoaded" class="table-scroll">
        <table class="score-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>SINH VIÊN</th>
              <th>BÀI TẬP</th>
              <th>ĐIỂM SỐ</th>
              <th>KẾT QUẢ</th>
              <th>NHẬN XÉT</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(item, index) in filteredScores"
              :key="item.id ?? index"
            >
              <td class="muted">{{ index + 1 }}</td>

              <td>
                <div class="student-cell">
                  <div class="avatar">
                    {{ getInitials(item.hoTen) }}
                  </div>

                  <div class="student-info">
                    <strong>
                      {{ item.hoTen || "Chưa cập nhật" }}
                    </strong>

                    <small>
                      {{ item.mssv || "Sinh viên" }}
                    </small>
                  </div>
                </div>
              </td>

              <td>
                <span class="assignment-name">
                  {{ item.tenBaiTap || "Chưa cập nhật" }}
                </span>
              </td>

              <td>
                <span
                  class="score-badge"
                  :class="scoreClass(item.diem)"
                >
                  {{ formatScore(item.diem) }}
                </span>
              </td>

              <td>
                <span
                  class="result-badge"
                  :class="resultClass(item.ketQua)"
                >
                  <span class="dot"></span>
                  {{ formatResult(item.ketQua) }}
                </span>
              </td>

              <td>
                <span
                  class="comment-text"
                  :title="item.nhanXet || ''"
                >
                  {{ item.nhanXet || "Chưa có nhận xét" }}
                </span>
              </td>

              <td>
                <router-link
                  v-if="item.baiTapId"
                  :to="{
                    path: '/giang-vien/bai-nop',
                    query: { baiTapId: item.baiTapId }
                  }"
                  class="action-link"
                >
                  Xem bài nộp →
                </router-link>

                <router-link
                  v-else
                  to="/giang-vien/bai-nop"
                  class="action-link"
                >
                  Chấm điểm →
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer class="panel-footer">
        <span>
          Hiển thị {{ filteredScores.length }}
          / {{ bangDiem.length }} bản ghi
        </span>

        <span v-if="lastUpdatedAt">
          Cập nhật lần cuối: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>

    <div class="info-note">
      <span class="info-symbol">i</span>

      <p>
        Bảng điểm chỉ hiển thị những bài đã có bản ghi
        trong bảng <strong>diem</strong>. Điểm trung bình
        được tính theo các bài đã chấm, không phải điểm
        tổng kết môn học. Kết quả Đạt/Không đạt do Backend
        cung cấp.
      </p>
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

import { useRoute } from "vue-router"
import api from "../../../services/api"

const route = useRoute()

/* ==========================
   STATE
========================== */
const danhSachLop = ref([])
const bangDiem = ref([])

const selectedLopId = ref("")

const loadingLop = ref(false)
const loading = ref(false)
const hasLoaded = ref(false)

const error = ref("")
const lastUpdatedAt = ref("")

const keyword = ref("")
const resultFilter = ref("all")
const scoreFilter = ref("all")

let lopRequestId = 0
let diemRequestId = 0

const busy = computed(
  () => loadingLop.value || loading.value
)

/* ==========================
   DATA HELPERS
========================== */
function extractList(response) {
  const body = response?.data

  if (
    body?.status === false ||
    body?.success === false
  ) {
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

function parseScore(value) {
  if (
    value === null ||
    value === undefined ||
    value === ""
  ) {
    return null
  }

  const number = Number(value)

  return Number.isFinite(number) &&
    number >= 0 &&
    number <= 10
    ? number
    : null
}

function normalizeGrade(item) {
  return {
    id: item.id,
    hoTen:
      item.hoTen ??
      item.ho_ten ??
      "",
    mssv:
      item.mssv ??
      item.ma_sinh_vien ??
      "",
    tenBaiTap:
      item.tenBaiTap ??
      item.tieu_de ??
      "",
    baiTapId:
      item.baiTapId ??
      item.bai_tap_id ??
      null,
    diem: parseScore(item.diem),
    ketQua:
      item.ketQua ??
      item.ket_qua ??
      null,
    nhanXet:
      item.nhanXet ??
      item.nhan_xet ??
      "",
  }
}

const selectedClass = computed(() =>
  danhSachLop.value.find(
    (lop) => String(lop.id) === selectedLopId.value
  ) || null
)

/* ==========================
   HIỂN THỊ ĐIỂM
========================== */
function formatScore(value) {
  if (value === null) return "—"

  return value.toLocaleString("vi-VN", {
    maximumFractionDigits: 2,
  })
}

function scoreClass(value) {
  if (value === null) return "score-unknown"
  if (value >= 8) return "score-high"
  if (value >= 5) return "score-medium"
  return "score-low"
}

function formatResult(value) {
  if (value === "dat") return "Đạt"
  if (value === "khong_dat") return "Không đạt"

  return "Chưa đánh giá"
}

function resultClass(value) {
  if (value === "dat") return "result-pass"
  if (value === "khong_dat") return "result-fail"

  return "result-unknown"
}

function getInitials(name) {
  const words = String(name || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (words.length === 0) return "SV"

  return words
    .slice(-2)
    .map((word) =>
      word.charAt(0).toLocaleUpperCase("vi-VN")
    )
    .join("")
}

/* ==========================
   THỐNG KÊ
========================== */
function statText(value) {
  return hasLoaded.value
    ? Number(value).toLocaleString("vi-VN")
    : "—"
}

const diemTrungBinh = computed(() => {
  if (!hasLoaded.value) return "—"

  const scores = bangDiem.value
    .map((item) => item.diem)
    .filter((value) => value !== null)

  if (scores.length === 0) return "—"

  const average = scores.reduce(
    (sum, value) => sum + value,
    0
  ) / scores.length

  return average.toLocaleString("vi-VN", {
    minimumFractionDigits: 1,
    maximumFractionDigits: 2,
  })
})

const soDat = computed(() =>
  bangDiem.value.filter(
    (item) => item.ketQua === "dat"
  ).length
)

const soKhongDat = computed(() =>
  bangDiem.value.filter(
    (item) => item.ketQua === "khong_dat"
  ).length
)

/* ==========================
   TÌM KIẾM & LỌC
========================== */
const filteredScores = computed(() => {
  const search = keyword.value
    .trim()
    .toLocaleLowerCase("vi-VN")

  return bangDiem.value.filter((item) => {
    const matchSearch = [
      item.hoTen,
      item.mssv,
      item.tenBaiTap,
    ].some((value) =>
      String(value || "")
        .toLocaleLowerCase("vi-VN")
        .includes(search)
    )

    const matchResult =
      resultFilter.value === "all" ||
      (
        resultFilter.value === "unknown"
          ? !["dat", "khong_dat"].includes(item.ketQua)
          : item.ketQua === resultFilter.value
      )

    const score = item.diem

    let matchScore = true

    if (scoreFilter.value === "excellent") {
      matchScore = score !== null && score >= 8
    } else if (scoreFilter.value === "middle") {
      matchScore =
        score !== null &&
        score >= 5 &&
        score < 8
    } else if (scoreFilter.value === "low") {
      matchScore = score !== null && score < 5
    } else if (scoreFilter.value === "invalid") {
      matchScore = score === null
    }

    return matchSearch && matchResult && matchScore
  })
})

function clearFilters() {
  keyword.value = ""
  resultFilter.value = "all"
  scoreFilter.value = "all"
}

/* ==========================
   API ERRORS
========================== */
function getErrorMessage(err, fallback) {
  const status = err.response?.status

  if (status === 401) {
    return "Phiên đăng nhập đã hết hạn."
  }

  if (status === 403) {
    return "Bạn không có quyền xem bảng điểm của lớp."
  }

  if (status === 404) {
    return "Không tìm thấy lớp học hoặc API tương ứng."
  }

  if (status >= 500) {
    return "Backend gặp lỗi khi truy vấn bảng điểm."
  }

  return err.message || fallback
}

/* ==========================
   LOAD LỚP HỌC
========================== */
async function fetchLopHoc() {
  const current = ++lopRequestId

  loadingLop.value = true
  error.value = ""

  try {
    const response = await api.get(
      "/giang-vien/lop-hoc"
    )

    if (current !== lopRequestId) return

    danhSachLop.value =
      extractList(response).map(normalizeLop)

    const fromUrl = String(route.query.lopId ?? "")

    if (
      fromUrl &&
      danhSachLop.value.some(
        (lop) => String(lop.id) === fromUrl
      )
    ) {
      selectedLopId.value = fromUrl
      await fetchBangDiem()
    }
  } catch (err) {
    if (current !== lopRequestId) return

    console.error("Lỗi tải danh sách lớp:", err)

    error.value = getErrorMessage(
      err,
      "Không thể tải danh sách lớp học."
    )
  } finally {
    if (current === lopRequestId) {
      loadingLop.value = false
    }
  }
}

/* ==========================
   LOAD BẢNG ĐIỂM
========================== */
async function fetchBangDiem() {
  const current = ++diemRequestId
  const lopId = selectedLopId.value

  error.value = ""
  hasLoaded.value = false
  bangDiem.value = []

  if (!lopId) {
    loading.value = false
    return
  }

  loading.value = true

  try {
    const response = await api.get(
      "/giang-vien/diem",
      {
        params: { lopId },
      }
    )

    if (current !== diemRequestId) return

    bangDiem.value =
      extractList(response).map(normalizeGrade)

    hasLoaded.value = true

    lastUpdatedAt.value =
      new Date().toLocaleString("vi-VN")
  } catch (err) {
    if (current !== diemRequestId) return

    console.error("Lỗi tải bảng điểm:", err)

    error.value = getErrorMessage(
      err,
      "Không thể tải bảng điểm."
    )
  } finally {
    if (current === diemRequestId) {
      loading.value = false
    }
  }
}

function changeClass() {
  clearFilters()
  fetchBangDiem()
}

function refreshData() {
  if (selectedLopId.value) {
    fetchBangDiem()
  } else {
    fetchLopHoc()
  }
}

/* ==========================
   XUẤT CSV
========================== */
function exportCSV() {
  if (!hasLoaded.value || filteredScores.value.length === 0) {
    return
  }

  const rows = [
    [
      "STT",
      "Họ và tên",
      "MSSV",
      "Tên bài tập",
      "Điểm số",
      "Kết quả",
      "Nhận xét",
    ],
    ...filteredScores.value.map((item, index) => [
      index + 1,
      item.hoTen,
      item.mssv,
      item.tenBaiTap,
      item.diem === null ? "" : item.diem,
      formatResult(item.ketQua),
      item.nhanXet,
    ]),
  ]

  // Chống CSV formula injection khi mở bằng Excel.
  function csvCell(value) {
    let text = String(value ?? "")

    if (/^[\s]*[=+\-@]/.test(text)) {
      text = "'" + text
    }

    return `"${text.replace(/"/g, '""')}"`
  }

  const csv = rows
    .map((row) => row.map(csvCell).join(","))
    .join("\r\n")

  const blob = new Blob(
    ["\uFEFF" + csv],
    { type: "text/csv;charset=utf-8;" }
  )

  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")

  link.href = url
  link.download = `bang-diem-lop-${selectedLopId.value}.csv`

  document.body.appendChild(link)
  link.click()
  link.remove()

  URL.revokeObjectURL(url)
}

/* ==========================
   LIFECYCLE
========================== */
onMounted(fetchLopHoc)

onBeforeUnmount(() => {
  lopRequestId++
  diemRequestId++
})
</script>

<style scoped>
/* TRANG BẢNG ĐIỂM */
.score-page {
  min-height: calc(100vh - 80px);
  width: 100%;
  box-sizing: border-box;
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
  color: #172033;
  font-size: 28px;
  font-weight: 800;
}

.page-header p {
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
.btn-outline {
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
  color: #fff;
  background: #2563eb;
  border: 1px solid #2563eb;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-outline {
  background: #fff;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.btn-outline:hover:not(:disabled) {
  background: #f8fafc;
}

button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.spinning {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

/* CHỌN LỚP */
.class-panel {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
  margin-bottom: 23px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
}

.class-icon {
  width: 47px;
  height: 47px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 900;
}

.class-field {
  flex: 1;
  min-width: 0;
}

.class-field label {
  display: block;
  margin-bottom: 7px;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
}

.class-field select {
  width: 100%;
  padding: 11px 13px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #172033;
  font-size: 12px;
  outline: none;
}

.class-field select:focus {
  border-color: #60a5fa;
}

.class-link {
  color: #2563eb;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
}

/* THẺ THỐNG KÊ */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 15px;
  margin-bottom: 23px;
}

.stat-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 19px;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.stat-icon {
  width: 44px;
  height: 44px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 11px;
  font-size: 12px;
  font-weight: 900;
}

.stat-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.stat-content span {
  color: #64748b;
  font-size: 11px;
}

.stat-content strong {
  color: #172033;
  font-size: 26px;
  font-weight: 800;
}

.stat-content small {
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

.purple {
  background: #faf5ff;
  color: #9333ea;
}

.red {
  background: #fef2f2;
  color: #dc2626;
}

/* BẢNG ĐIỂM */
.table-panel {
  overflow: hidden;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
}

.panel-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 21px;
}

.panel-heading h2 {
  margin: 0 0 6px;
  color: #172033;
  font-size: 17px;
  font-weight: 800;
}

.panel-heading p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
}

.count-label {
  padding: 7px 12px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

/* TÌM KIẾM */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding: 0 21px 20px;
}

.search-box {
  flex: 1;
  min-width: 230px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
}

.search-box:focus-within {
  border-color: #60a5fa;
}

.search-icon {
  font-size: 24px;
  color: #94a3b8;
}

.search-box input {
  width: 100%;
  padding: 12px 0;
  border: none;
  outline: none;
  background: transparent;
  color: #172033;
  font-size: 12px;
}

.filter-select {
  min-width: 170px;
  max-width: 210px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #334155;
  font-size: 12px;
  outline: none;
}

.clear-btn {
  padding: 11px 13px;
  border: none;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}

/* TABLE */
.table-scroll {
  width: 100%;
  overflow-x: auto;
}

.score-table {
  width: 100%;
  min-width: 970px;
  border-collapse: collapse;
  text-align: left;
}

.score-table thead {
  background: #f8fafc;
}

.score-table th {
  padding: 15px 18px;
  color: #64748b;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.4px;
  white-space: nowrap;
}

.score-table td {
  padding: 16px 18px;
  border-top: 1px solid #f1f5f9;
  font-size: 12px;
  vertical-align: middle;
}

.score-table tbody tr:hover {
  background: #f8fafc;
}

.student-cell {
  display: flex;
  align-items: center;
  gap: 11px;
}

.avatar {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
}

.student-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.student-info strong {
  color: #172033;
  font-size: 12px;
}

.student-info small {
  color: #94a3b8;
  font-size: 10px;
}

.assignment-name {
  color: #475569;
  font-weight: 600;
}

.score-badge,
.result-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 12px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
}

.score-high {
  background: #dcfce7;
  color: #15803d;
}

.score-medium {
  background: #eff6ff;
  color: #2563eb;
}

.score-low {
  background: #fee2e2;
  color: #b91c1c;
}

.score-unknown {
  background: #f1f5f9;
  color: #64748b;
}

.result-pass {
  background: #dcfce7;
  color: #15803d;
}

.result-fail {
  background: #fee2e2;
  color: #b91c1c;
}

.result-unknown {
  background: #f1f5f9;
  color: #64748b;
}

.dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.comment-text {
  display: block;
  min-width: 150px;
  max-width: 280px;
  color: #64748b;
  line-height: 1.6;
  overflow-wrap: anywhere;
}

.action-link {
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  text-decoration: none;
  white-space: nowrap;
}

.action-link:hover {
  text-decoration: underline;
}

.muted {
  color: #94a3b8;
}

/* TRẠNG THÁI */
.state-box {
  min-height: 290px;
  padding: 28px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  text-align: center;
}

.state-icon {
  font-size: 34px;
}

.state-box strong {
  font-size: 14px;
}

.state-box p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
}

.state-action {
  margin-top: 10px;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.error-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  margin: 0 21px 18px;
  padding: 15px;
  border: 1px solid #fecaca;
  border-radius: 11px;
  background: #fef2f2;
  color: #b91c1c;
}

.error-box strong {
  font-size: 12px;
}

.error-box p {
  margin: 5px 0 0;
  font-size: 11px;
}

.error-box button {
  padding: 9px 13px;
  border: 1px solid #fca5a5;
  border-radius: 9px;
  background: #fff;
  color: #b91c1c;
  font-size: 11px;
  cursor: pointer;
}

.panel-footer {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 17px 21px;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

/* GHI CHÚ */
.info-note {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-top: 18px;
  padding: 15px;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  background: #eff6ff;
}

.info-symbol {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #2563eb;
  color: #fff;
  font-size: 12px;
  font-weight: 900;
}

.info-note p {
  margin: 2px 0 0;
  color: #475569;
  font-size: 11px;
  line-height: 1.7;
}

/* DARK MODE */
:global(html.dark-theme) .score-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .stat-content strong,
:global(html.dark-theme) .panel-heading h2,
:global(html.dark-theme) .student-info strong {
  color: #fff;
}

:global(html.dark-theme) .class-panel,
:global(html.dark-theme) .stat-card,
:global(html.dark-theme) .table-panel {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .class-field select,
:global(html.dark-theme) .search-box,
:global(html.dark-theme) .filter-select,
:global(html.dark-theme) .btn-outline {
  background: #202327;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .search-box input {
  color: #fff;
}

:global(html.dark-theme) .score-table thead,
:global(html.dark-theme) .score-table tbody tr:hover {
  background: #202327;
}

:global(html.dark-theme) .score-table td,
:global(html.dark-theme) .panel-footer {
  border-color: rgba(255, 255, 255, 0.12);
}

:global(html.dark-theme) .assignment-name,
:global(html.dark-theme) .comment-text {
  color: #cbd5e1;
}

:global(html.dark-theme) .info-note {
  background: rgba(37, 99, 235, 0.12);
  border-color: rgba(96, 165, 250, 0.3);
}

:global(html.dark-theme) .info-note p {
  color: #cbd5e1;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 750px) {
  .score-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .page-header h1 {
    font-size: 23px;
  }

  .header-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .class-panel {
    flex-wrap: wrap;
  }

  .class-field {
    min-width: 180px;
  }

  .class-link {
    width: 100%;
  }

  .toolbar {
    flex-direction: column;
  }

  .search-box,
  .filter-select {
    width: 100%;
    min-width: 0;
    max-width: none;
    box-sizing: border-box;
  }
}

@media (max-width: 470px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
