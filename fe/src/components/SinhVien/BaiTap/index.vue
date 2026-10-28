<template>
  <div class="assignment-page">
    <!-- HEADER -->
    <header class="page-header">
      <div class="header-content">
        <span class="eyebrow">SMART CLASSROOM · SINH VIÊN</span>
        <h1>📝 Bài tập & Kiểm tra</h1>
        <p>
          Theo dõi lịch kiểm tra, bài tập được giao và thời hạn nộp bài.
          Danh sách tự cập nhật khi đến giờ mở đề.
        </p>
      </div>

      <button
        class="refresh-button"
        type="button"
        :disabled="loading"
        @click="loadAssignments"
      >
        <span :class="{ spinning: loading }">↻</span>
        {{ loading ? "Đang tải..." : "Làm mới" }}
      </button>
    </header>

    <!-- STATS -->
    <section class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">BT</div>
        <div class="stat-text">
          <span>Tổng bài tập</span>
          <strong>{{ statValue(statistics.total) }}</strong>
          <small>Tất cả bài được giao</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">CN</div>
        <div class="stat-text">
          <span>Chưa nộp</span>
          <strong>{{ statValue(statistics.pending) }}</strong>
          <small>Chưa đến hạn hoặc chưa có hạn</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">DN</div>
        <div class="stat-text">
          <span>Đã nộp</span>
          <strong>{{ statValue(statistics.submitted) }}</strong>
          <small>Đã nộp lên hệ thống</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red">QH</div>
        <div class="stat-text">
          <span>Quá hạn</span>
          <strong>{{ statValue(statistics.overdue) }}</strong>
          <small>Chưa nộp và đã quá hạn</small>
        </div>
      </div>
    </section>

    <!-- TOOLBAR -->
    <section class="toolbar">
      <div class="search-box">
        <span class="search-icon">⌕</span>
        <input
          v-model="search"
          type="search"
          placeholder="Tìm tên bài tập, môn học, nội dung..."
          aria-label="Tìm kiếm bài tập"
        />
      </div>

      <select
        v-model="selectedClass"
        class="filter-select"
        aria-label="Lọc lớp học"
      >
        <option value="">Tất cả lớp học</option>
        <option
          v-for="item in classOptions"
          :key="item"
          :value="item"
        >
          {{ item }}
        </option>
      </select>

      <select
        v-model="selectedType"
        class="filter-select"
        aria-label="Lọc loại bài"
      >
        <option value="">Tất cả loại bài</option>
        <option value="exam">Bài kiểm tra</option>
        <option value="assignment">Bài thực hành</option>
      </select>

      <select
        v-model="selectedStatus"
        class="filter-select"
        aria-label="Lọc trạng thái nộp bài"
      >
        <option value="">Tất cả trạng thái</option>
        <option value="upcoming">Sắp diễn ra</option>
        <option value="ongoing">Đang diễn ra</option>
        <option value="pending">Chưa nộp</option>
        <option value="submitted">Đã nộp</option>
        <option value="overdue">Quá hạn</option>
      </select>
    </section>

    <!-- ERROR -->
    <div
      v-if="errorMessage"
      class="message-error"
      role="alert"
    >
      <div>
        <strong>Không thể tải danh sách bài tập</strong>
        <p>{{ errorMessage }}</p>
      </div>

      <button
        type="button"
        :disabled="loading"
        @click="loadAssignments"
      >
        Thử lại
      </button>
    </div>

    <!-- MAIN -->
    <section class="list-panel">
      <div class="section-header">
        <div>
          <h2>Danh sách bài được giao</h2>
          <p>
            Chọn bài kiểm tra để làm trực tuyến hoặc
            mở trang nộp bài thực hành.
          </p>
        </div>

        <span class="count-tag">
          {{ filteredAssignments.length }} bài
        </span>
      </div>

      <div v-if="loading && !hasLoaded" class="state-box">
        <div class="spinner"></div>
        <strong>Đang tải danh sách bài tập...</strong>
        <p>Đang lấy dữ liệu từ Backend.</p>
      </div>

      <div
        v-else-if="!hasLoaded"
        class="state-box"
      >
        <div class="empty-icon">!</div>
        <strong>Chưa tải được dữ liệu</strong>
        <p>Hãy thử tải lại danh sách bài tập.</p>
      </div>

      <div
        v-else-if="assignments.length === 0"
        class="state-box"
      >
        <div class="empty-icon">📚</div>
        <strong>Chưa có bài tập được giao</strong>
        <p>
          Các bài tập và bài kiểm tra được giao sẽ hiển thị tại đây.
          Đề thi được mở theo lịch của giảng viên.
        </p>
      </div>

      <div
        v-else-if="filteredAssignments.length === 0"
        class="state-box"
      >
        <div class="empty-icon">⌕</div>
        <strong>Không tìm thấy bài phù hợp</strong>
        <p>Thử thay đổi từ khóa hoặc bộ lọc.</p>

        <button
          class="reset-button"
          type="button"
          @click="clearFilters"
        >
          Xóa bộ lọc
        </button>
      </div>

      <div v-else class="assignment-list">
        <article
          v-for="item in filteredAssignments"
          :key="item.id"
          class="assignment-card"
        >
          <div
            class="assignment-icon"
            :class="isOnlineExam(item) ? 'purple' : 'blue'"
          >
            {{ isOnlineExam(item) ? "KT" : "BT" }}
          </div>

          <div class="assignment-content">
            <div class="assignment-top">
              <div class="assignment-heading">
                <span class="type-label">
                  {{
                    isOnlineExam(item)
                      ? "BÀI KIỂM TRA TRỰC TUYẾN"
                      : "BÀI TẬP THỰC HÀNH"
                  }}
                </span>

                <h3>{{ item.tieu_de }}</h3>

                <p class="class-name">
                  {{ item.ten_lop || "Lớp học" }}
                  <span v-if="item.ma_lop">
                    · {{ item.ma_lop }}
                  </span>
                </p>
              </div>

              <span
                class="status"
                :class="getAssignmentStatus(item).value"
              >
                {{ getAssignmentStatus(item).text }}
              </span>
            </div>

            <p class="description">
              {{
                item.mo_ta ||
                "Giảng viên chưa cung cấp mô tả."
              }}
            </p>

            <div class="assignment-info">
              <div class="info-item">
                <span>Thời gian bắt đầu</span>
                <strong>
                  {{ formatDate(item.thoi_gian_bat_dau) }}
                </strong>
              </div>

              <div class="info-item">
                <span>{{ isOnlineExam(item) ? "Đóng bài lúc" : "Hạn nộp" }}</span>
                <strong>{{ formatDate(isOnlineExam(item) ? examDeadlineValue(item) : item.han_nop) }}</strong>
              </div>

              <div class="info-item">
                <span>
                  {{
                    isOnlineExam(item)
                      ? "Kết thúc kiểm tra"
                      : "Cho phép nộp trễ"
                  }}
                </span>

                <strong>
                  {{
                    isOnlineExam(item)
                      ? formatDate(item.thoi_gian_ket_thuc || item.han_nop)
                      : item.cho_phep_nop_tre
                        ? "Có"
                        : "Không"
                  }}
                </strong>
              </div>
            </div>

            <div class="assignment-footer">
              <div class="footer-note">
                <span v-if="isOnlineExam(item)">
                  <template v-if="item.bi_khoa">Bài kiểm tra bị khóa</template>
                  <template v-else-if="isNotStarted(item)">
                    Bài sẽ mở đúng giờ theo lịch của giảng viên
                  </template>
                  <template v-else-if="getAssignmentStatus(item).value === 'submitted'">
                    Đã hoàn thành bài kiểm tra
                  </template>
                  <template v-else-if="isExamExpired(item)">
                    Đã hết giờ kiểm tra
                  </template>
                  <template v-else>
                    Đang trong thời gian làm bài
                  </template>
                </span>

                <span v-else>
                  Nộp bài thực hành qua trang Bài nộp
                </span>
              </div>

              <!-- ONLINE EXAM -->
              <template v-if="isOnlineExam(item)">
                <span
                  v-if="getAssignmentStatus(item).value === 'submitted'"
                  class="completed-label"
                >
                  <span>✓</span> Đã nộp
                </span>

                <button
                  v-else
                  type="button"
                  class="action-button exam-action"
                  :disabled="!canStartExam(item)"
                  @click="startExam(item)"
                >
                  {{
                    item.bi_khoa
                      ? "Bài đã khóa"
                      : isNotStarted(item)
                        ? "Chưa đến giờ"
                      : isExamExpired(item)
                        ? "Đã hết thời gian"
                        : "Bắt đầu làm bài"
                  }}
                  <span v-if="canStartExam(item)">→</span>
                </button>
              </template>

              <!-- FILE ASSIGNMENT -->
              <button
                v-else
                type="button"
                class="action-button file-action"
                @click="viewAssignment(item)"
              >
                {{
                  getAssignmentStatus(item).value === "submitted"
                    ? "Xem bài đã nộp"
                    : "Xem / Nộp bài"
                }}
                <span>→</span>
              </button>
            </div>
          </div>
        </article>
      </div>

      <footer class="list-footer">
        <span>
          Hiển thị {{ filteredAssignments.length }}
          / {{ assignments.length }} bài
        </span>

        <span v-if="lastUpdatedAt">
          Cập nhật: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
} from "vue"

import { useRouter } from "vue-router"
import api from "../../../services/api"

const router = useRouter()

const assignments = ref([])
const loading = ref(false)
const hasLoaded = ref(false)
const errorMessage = ref("")
const lastUpdatedAt = ref("")

const search = ref("")
const selectedClass = ref("")
const selectedType = ref("")
const selectedStatus = ref("")

// Cập nhật trạng thái thời gian mỗi 30 giây.
const nowMs = ref(Date.now())
let timeInterval = null
let refreshInterval = null
let requestId = 0

function onVisibilityChange() {
  if (!document.hidden) {
    nowMs.value = Date.now()
    loadAssignments(true)
  }
}

/* ==========================
   CHUẨN HÓA API
========================== */
function extractList(response) {
  const body = response?.data

  if (
    body?.status === false ||
    body?.success === false
  ) {
    throw new Error(
      body.message || "API trả về lỗi."
    )
  }

  const data = body?.data ?? body

  if (Array.isArray(data)) return data

  if (Array.isArray(data?.bai_tap)) {
    return data.bai_tap
  }

  if (Array.isArray(data?.data)) {
    return data.data
  }

  throw new Error(
    "API không trả về danh sách bài tập hợp lệ."
  )
}

function toBool(value) {
  return (
    value === true ||
    value === 1 ||
    value === "1"
  )
}

function normalizeAssignment(item) {
  return {
    id: item.id,

    tieu_de:
      item.tieu_de ??
      item.tieuDe ??
      "",

    mo_ta:
      item.mo_ta ??
      item.moTa ??
      "",

    ten_lop:
      item.ten_lop ??
      item.tenLop ??
      "",

    ma_lop:
      item.ma_lop ??
      item.maLop ??
      "",

    thoi_gian_bat_dau:
      item.thoi_gian_bat_dau ??
      item.thoiGianBatDau ??
      null,

    thoi_gian_ket_thuc:
      item.thoi_gian_ket_thuc ??
      item.thoiGianKetThuc ??
      null,

    han_nop:
      item.han_nop ??
      item.hanNop ??
      null,

    cho_phep_nop_tre: toBool(
      item.cho_phep_nop_tre ??
      item.choPhepNopTre
    ),

    da_nop: toBool(
      item.da_nop ??
      item.daNop
    ),

    // API phải trả cờ xác định bài kiểm tra.
    // Không suy đoán theo tiêu đề.
    la_bai_kiem_tra: toBool(
      item.la_bai_kiem_tra ??
      item.laBaiKiemTra
    ),

    bi_khoa: toBool(item.bi_khoa ?? item.biKhoa),
  }
}

/* ==========================
   LOAD ASSIGNMENTS
========================== */
async function loadAssignments(silent = false) {
  // Khi đồng bộ nền, không che danh sách bằng màn hình loading.
  // Nếu đang có request chạy, bỏ qua vòng polling này.
  if (loading.value) return
  const current = ++requestId
  loading.value = true
  if (!silent) errorMessage.value = ""

  try {
    const response = await api.get("/sinh-vien/bai-tap")
    if (current !== requestId) return

    assignments.value = extractList(response).map(normalizeAssignment)
    hasLoaded.value = true
    errorMessage.value = ""
    nowMs.value = Date.now()
    lastUpdatedAt.value = new Date().toLocaleString("vi-VN")
  } catch (err) {
    if (current !== requestId) return
    console.error("Lỗi đồng bộ bài tập:", err)
    // Lỗi mạng ngắn hạn khi làm mới nền không xóa dữ liệu cũ.
    if (!silent || !hasLoaded.value) {
      errorMessage.value = err.response?.data?.message ||
        err.message || "Không thể tải bài tập."
    }
  } finally {
    if (current === requestId) loading.value = false
  }
}

/* ==========================
   KIỂM TRA LOẠI BÀI
========================== */
function isOnlineExam(item) {
  return item.la_bai_kiem_tra === true
}

/* ==========================
   DATE HELPERS
========================== */
function parseDate(value) {
  if (!value) return null

  const date = new Date(
    typeof value === "string"
      ? value.replace(" ", "T")
      : value
  )

  return Number.isNaN(date.getTime())
    ? null
    : date
}

function formatDate(value) {
  const date = parseDate(value)

  if (!date) return "Chưa cập nhật"

  return date.toLocaleString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  })
}

function isNotStarted(item) {
  const start = parseDate(item.thoi_gian_bat_dau)
  // Không rõ giờ bắt đầu: không cho mở bài kiểm tra.
  return start === null || nowMs.value < start.getTime()
}

function examDeadline(item) {
  const dates = [
    item.han_nop,
    item.thoi_gian_ket_thuc,
  ]
    .map(parseDate)
    .filter((date) => date !== null)
    .map((date) => date.getTime())

  return dates.length
    ? Math.min(...dates)
    : null
}

function examDeadlineValue(item) {
  const deadline = examDeadline(item)
  return deadline === null ? null : new Date(deadline).toISOString()
}

function isExamExpired(item) {
  const deadline = examDeadline(item)

  // Không có hạn hợp lệ thì không thể bắt đầu.
  return deadline === null ||
    nowMs.value >= deadline
}

/* ==========================
   TRẠNG THÁI
========================== */
function getAssignmentStatus(item) {
  if (item.bi_khoa && isOnlineExam(item)) {
    return { text: "Đã khóa", value: "locked" }
  }

  if (item.da_nop) {
    return {
      text: "Đã nộp",
      value: "submitted",
    }
  }

  if (isOnlineExam(item) && isNotStarted(item)) {
    return { text: "Sắp diễn ra", value: "upcoming" }
  }

  const deadline = isOnlineExam(item)
    ? examDeadline(item)
    : parseDate(item.han_nop)?.getTime() ?? null

  if (
    deadline !== null &&
    nowMs.value >= deadline
  ) {
    return {
      text: "Quá hạn",
      value: "overdue",
    }
  }

  if (isOnlineExam(item)) {
    return { text: "Đang diễn ra", value: "ongoing" }
  }

  return { text: "Chưa nộp", value: "pending" }
}

function canStartExam(item) {
  return (
    isOnlineExam(item) &&
    !item.da_nop &&
    !item.bi_khoa &&
    !isNotStarted(item) &&
    !isExamExpired(item)
  )
}

/* ==========================
   THỐNG KÊ
========================== */
const statistics = computed(() => {
  const data = {
    total: assignments.value.length,
    pending: 0,
    submitted: 0,
    overdue: 0,
  }

  assignments.value.forEach((item) => {
    const status = getAssignmentStatus(item).value

    if (status === "submitted") {
      data.submitted++
    } else if (status === "overdue") {
      data.overdue++
    } else {
      data.pending++
    }
  })

  return data
})

function statValue(value) {
  return hasLoaded.value
    ? value.toLocaleString("vi-VN")
    : "—"
}

/* ==========================
   FILTER
========================== */
function normalizeText(value) {
  return String(value ?? "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D")
    .toLowerCase()
    .trim()
}

const classOptions = computed(() =>
  [...new Set(
    assignments.value
      .map((item) => item.ten_lop)
      .filter(Boolean)
  )].sort()
)

const filteredAssignments = computed(() => {
  const keyword = normalizeText(search.value)

  return assignments.value.filter((item) => {
    const text = normalizeText([
      item.tieu_de,
      item.mo_ta,
      item.ten_lop,
      item.ma_lop,
    ].join(" "))

    const matchSearch = text.includes(keyword)

    const matchClass =
      !selectedClass.value ||
      selectedClass.value === item.ten_lop

    const matchType =
      !selectedType.value ||
      (
        selectedType.value === "exam"
          ? isOnlineExam(item)
          : !isOnlineExam(item)
      )

    const matchStatus =
      !selectedStatus.value ||
      getAssignmentStatus(item).value === selectedStatus.value

    return (
      matchSearch &&
      matchClass &&
      matchType &&
      matchStatus
    )
  })
})

function clearFilters() {
  search.value = ""
  selectedClass.value = ""
  selectedType.value = ""
  selectedStatus.value = ""
}

/* ==========================
   ROUTER
========================== */
function startExam(item) {
  // Kiểm tra lại ngay lúc nhấn nút,
  // không chỉ lúc render giao diện.
  nowMs.value = Date.now()

  if (!canStartExam(item)) return

  router.push({
    path: "/sinh-vien/lam-bai-kiem-tra",
    query: {
      baiTapId: item.id,
    },
  })
}

function viewAssignment(item) {
  router.push({
    path: "/sinh-vien/bai-nop",
    query: {
      bai_tap_id: item.id,
    },
  })
}

/* ==========================
   LIFECYCLE
========================== */
onMounted(() => {
  loadAssignments()

  // Cập nhật đồng hồ trạng thái mỗi giây để nút mở đúng giờ.
  timeInterval = setInterval(() => {
    nowMs.value = Date.now()
  }, 1000)

  // Lấy lại danh sách từ API mỗi 10 giây: bài mới được giao
  // tự xuất hiện mà sinh viên không phải bấm Làm mới.
  refreshInterval = setInterval(() => {
    if (!document.hidden) loadAssignments(true)
  }, 10000)

  document.addEventListener("visibilitychange", onVisibilityChange)
})

onBeforeUnmount(() => {
  requestId++
  clearInterval(timeInterval)
  clearInterval(refreshInterval)
  document.removeEventListener("visibilitychange", onVisibilityChange)
})
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.assignment-page {
  min-height: calc(100vh - 65px);
  padding: 27px;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, system-ui, sans-serif;
}

/* HEADER */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  margin-bottom: 25px;
}

.eyebrow {
  color: #2563eb;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 1px;
}

.header-content h1 {
  margin: 7px 0;
  font-size: 28px;
  font-weight: 800;
}

.header-content p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.7;
}

.refresh-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 11px 15px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  color: #475569;
  font-size: 12px;
  font-weight: 750;
  cursor: pointer;
}

.refresh-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* STATISTICS */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.stat-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 18px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.stat-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 900;
}

.blue {
  background: #eff6ff;
  color: #2563eb;
}

.orange {
  background: #fff7ed;
  color: #ea580c;
}

.green {
  background: #f0fdf4;
  color: #16a34a;
}

.red {
  background: #fef2f2;
  color: #dc2626;
}

.purple {
  background: #faf5ff;
  color: #9333ea;
}

.stat-text {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 0;
}

.stat-text span {
  color: #64748b;
  font-size: 11px;
}

.stat-text strong {
  color: #172033;
  font-size: 25px;
  font-weight: 850;
}

.stat-text small {
  color: #94a3b8;
  font-size: 10px;
}

/* FILTERS */
.toolbar {
  display: grid;
  grid-template-columns: minmax(180px, 1fr) repeat(3, 175px);
  gap: 12px;
  margin-bottom: 19px;
  padding: 16px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #f8fafc;
}

.search-icon {
  color: #94a3b8;
  font-size: 22px;
}

.search-box input {
  width: 100%;
  min-width: 0;
  padding: 11px 0;
  border: none;
  outline: none;
  background: transparent;
  color: #172033;
  font-size: 11px;
}

.filter-select {
  width: 100%;
  min-width: 0;
  padding: 11px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #475569;
  font-size: 11px;
  outline: none;
}

/* PANEL */
.list-panel {
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  padding: 21px;
}

.section-header h2 {
  margin: 0 0 6px;
  color: #172033;
  font-size: 17px;
  font-weight: 800;
}

.section-header p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
  line-height: 1.7;
}

.count-tag {
  padding: 7px 12px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 750;
  white-space: nowrap;
}

.assignment-list {
  display: flex;
  flex-direction: column;
  gap: 13px;
  padding: 0 21px 21px;
}

/* ASSIGNMENT CARD */
.assignment-card {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  min-width: 0;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: #fff;
  transition: 0.2s ease;
}

.assignment-card:hover {
  border-color: #bfdbfe;
  box-shadow: 0 7px 20px rgba(15, 23, 42, 0.06);
}

.assignment-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 900;
}

.assignment-content {
  flex: 1;
  min-width: 0;
}

.assignment-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 15px;
}

.assignment-heading {
  min-width: 0;
}

.type-label {
  color: #2563eb;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 0.5px;
}

.assignment-heading h3 {
  margin: 8px 0 6px;
  color: #172033;
  font-size: 16px;
  font-weight: 800;
  line-height: 1.6;
  overflow-wrap: anywhere;
}

.class-name {
  margin: 0;
  color: #64748b;
  font-size: 11px;
}

.description {
  margin: 14px 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.8;
  overflow-wrap: anywhere;
}

.status {
  flex-shrink: 0;
  padding: 8px 12px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
}

.status.submitted {
  background: #dcfce7;
  color: #15803d;
}

.status.pending {
  background: #fff7ed;
  color: #c2410c;
}

.status.overdue {
  background: #fee2e2;
  color: #b91c1c;
}

.status.upcoming { background: #dbeafe; color: #1d4ed8; }
.status.ongoing { background: #dcfce7; color: #15803d; }
.status.locked { background: #fee2e2; color: #b91c1c; }


/* INFO */
.assignment-info {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 11px;
  margin-top: 17px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 7px;
  padding: 13px;
  border-radius: 10px;
  background: #f8fafc;
  min-width: 0;
}

.info-item span {
  color: #94a3b8;
  font-size: 10px;
}

.info-item strong {
  color: #475569;
  font-size: 11px;
  font-weight: 750;
  overflow-wrap: anywhere;
}

/* FOOTER */
.assignment-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  margin-top: 19px;
  padding-top: 17px;
  border-top: 1px solid #e5e7eb;
}

.footer-note {
  color: #64748b;
  font-size: 11px;
  line-height: 1.6;
}

.action-button {
  display: inline-flex;
  justify-content: space-between;
  align-items: center;
  gap: 13px;
  min-width: 155px;
  padding: 11px 15px;
  border-radius: 10px;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
}

.file-action {
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #2563eb;
}

.file-action:hover {
  background: #dbeafe;
}

.exam-action {
  border: 1px solid #2563eb;
  background: #2563eb;
  color: #fff;
}

.exam-action:hover:not(:disabled) {
  background: #1d4ed8;
}

.exam-action:disabled {
  border-color: #e2e8f0;
  background: #f1f5f9;
  color: #94a3b8;
  cursor: not-allowed;
}

.completed-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 10px;
  background: #dcfce7;
  color: #15803d;
  font-size: 11px;
  font-weight: 800;
}

/* FOOTER OF LIST */
.list-footer {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 16px 21px;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

/* STATES */
.state-box {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 12px;
  min-height: 275px;
  padding: 30px;
  text-align: center;
}

.state-box strong {
  font-size: 13px;
}

.state-box p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
  line-height: 1.8;
}

.empty-icon {
  font-size: 34px;
}

.reset-button {
  padding: 10px 15px;
  border: 1px solid #bfdbfe;
  border-radius: 9px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  cursor: pointer;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.spinning {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

.message-error {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 15px;
  margin-bottom: 18px;
  border: 1px solid #fecaca;
  border-radius: 11px;
  background: #fef2f2;
  color: #b91c1c;
}

.message-error strong {
  font-size: 12px;
}

.message-error p {
  margin: 5px 0 0;
  font-size: 11px;
}

.message-error button {
  padding: 9px 12px;
  border: 1px solid #fca5a5;
  border-radius: 9px;
  background: #fff;
  color: #b91c1c;
  font-size: 11px;
  cursor: pointer;
}

/* DARK MODE */
:global(html.dark-theme) .assignment-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .header-content h1,
:global(html.dark-theme) .section-header h2,
:global(html.dark-theme) .stat-text strong,
:global(html.dark-theme) .assignment-heading h3 {
  color: #fff;
}

:global(html.dark-theme) .stat-card,
:global(html.dark-theme) .toolbar,
:global(html.dark-theme) .list-panel,
:global(html.dark-theme) .assignment-card,
:global(html.dark-theme) .refresh-button {
  background: #111;
  border-color: rgba(255, 255, 255, 0.15);
  box-shadow: none;
}

:global(html.dark-theme) .refresh-button,
:global(html.dark-theme) .filter-select {
  color: #e2e8f0;
}

:global(html.dark-theme) .search-box,
:global(html.dark-theme) .info-item,
:global(html.dark-theme) .filter-select {
  background: #202327;
  border-color: rgba(255, 255, 255, 0.1);
}

:global(html.dark-theme) .search-box input {
  color: #fff;
}

:global(html.dark-theme) .info-item strong {
  color: #cbd5e1;
}

:global(html.dark-theme) .assignment-footer,
:global(html.dark-theme) .list-footer {
  border-color: rgba(255, 255, 255, 0.12);
}

:global(html.dark-theme) .exam-action:disabled {
  background: #30343b;
  border-color: #30343b;
  color: #94a3b8;
}

/* RESPONSIVE */
@media (max-width: 1250px) {
  .toolbar {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 950px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 650px) {
  .assignment-page {
    padding: 15px;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .toolbar {
    grid-template-columns: 1fr;
  }

  .assignment-card {
    flex-direction: column;
  }

  .assignment-top {
    flex-direction: column;
  }

  .assignment-info {
    grid-template-columns: 1fr;
  }

  .assignment-footer {
    flex-direction: column;
    align-items: stretch;
  }

  .action-button {
    width: 100%;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
