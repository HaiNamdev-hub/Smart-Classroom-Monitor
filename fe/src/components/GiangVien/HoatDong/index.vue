
<template>
  <div class="activity-page">
    <!-- HEADER -->
    <header class="page-header">
      <div>
        <span class="eyebrow">GIÁM SÁT HOẠT ĐỘNG</span>
        <h1>📋 Nhật ký hoạt động</h1>
        <p>
          Theo dõi các sự kiện, ứng dụng và website được
          ghi nhận trong phiên giám sát sinh viên.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn-outline"
          :disabled="loading"
          @click="fetchData"
        >
          <span :class="{ spinning: loading }">↻</span>
          {{ loading ? "Đang tải..." : "Làm mới" }}
        </button>

        <button
          type="button"
          class="btn-primary"
          :disabled="!hasLoaded || filteredActivities.length === 0"
          @click="exportCSV"
        >
          ↓ Xuất nhật ký
        </button>
      </div>
    </header>

    <!-- THỐNG KÊ -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">NK</div>
        <div class="stat-content">
          <span>Tổng hoạt động</span>
          <strong>{{ statText(danhSachHoatDong.length) }}</strong>
          <small>Trong dữ liệu đã tải</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">▣</div>
        <div class="stat-content">
          <span>Hoạt động ứng dụng</span>
          <strong>{{ statText(countType("ung_dung")) }}</strong>
          <small>Sự kiện liên quan ứng dụng</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">◎</div>
        <div class="stat-content">
          <span>Truy cập website</span>
          <strong>{{ statText(countType("website")) }}</strong>
          <small>Sự kiện website được ghi nhận</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">SV</div>
        <div class="stat-content">
          <span>Sinh viên hoạt động</span>
          <strong>{{ studentCountText }}</strong>
          <small>Sinh viên có nhật ký</small>
        </div>
      </div>
    </div>

    <!-- MAIN PANEL -->
    <section class="activity-panel">
      <div class="panel-heading">
        <div>
          <h2>Dòng thời gian hoạt động</h2>
          <p>
            Danh sách sự kiện do hệ thống giám sát ghi nhận.
          </p>
        </div>

        <span class="count-tag">
          {{ filteredActivities.length }} bản ghi
        </span>
      </div>

      <!-- FILTERS -->
      <div class="toolbar">
        <div class="search-box">
          <span class="search-icon">⌕</span>
          <input
            v-model="filters.search"
            type="search"
            placeholder="Tìm sinh viên, MSSV, ứng dụng, website..."
            aria-label="Tìm kiếm nhật ký hoạt động"
          />
        </div>

        <select
          v-model="filters.loai"
          class="filter-select"
          aria-label="Lọc theo loại hoạt động"
        >
          <option value="ALL">Tất cả hoạt động</option>
          <option value="ung_dung">Ứng dụng</option>
          <option value="website">Website</option>
          <option value="copy_paste">Sao chép / Dán</option>
          <option value="tham_gia">Tham gia phiên</option>
          <option value="roi_phien">Rời phiên</option>
        </select>

        <select
          v-model="filters.thoiGian"
          class="filter-select"
          aria-label="Lọc theo thời gian"
        >
          <option value="ALL">Tất cả thời gian</option>
          <option value="today">Hôm nay</option>
          <option value="week">7 ngày gần đây</option>
          <option value="month">30 ngày gần đây</option>
        </select>

        <button
          v-if="hasFilters"
          type="button"
          class="clear-btn"
          @click="clearFilters"
        >
          Xóa bộ lọc
        </button>
      </div>

      <!-- ERROR -->
      <div v-if="error" class="error-box" role="alert">
        <div>
          <strong>Không thể tải nhật ký hoạt động</strong>
          <p>{{ error }}</p>
        </div>

        <button type="button" @click="fetchData">
          Thử lại
        </button>
      </div>

      <!-- LOADING -->
      <div
        v-if="loading && !hasLoaded"
        class="state-box"
        role="status"
      >
        <div class="spinner"></div>
        <strong>Đang tải nhật ký hoạt động...</strong>
        <p>Hệ thống đang lấy dữ liệu từ máy chủ.</p>
      </div>

      <!-- EMPTY -->
      <div
        v-else-if="hasLoaded && danhSachHoatDong.length === 0"
        class="state-box"
      >
        <div class="empty-icon">📋</div>
        <strong>Chưa có nhật ký hoạt động</strong>
        <p>
          Hệ thống chưa ghi nhận sự kiện nào trong
          dữ liệu trả về.
        </p>
      </div>

      <div
        v-else-if="hasLoaded && filteredActivities.length === 0"
        class="state-box"
      >
        <div class="empty-icon">⌕</div>
        <strong>Không tìm thấy hoạt động phù hợp</strong>
        <p>Hãy thử thay đổi từ khóa hoặc bộ lọc.</p>
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
        <table class="activity-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>SINH VIÊN</th>
              <th>LOẠI HOẠT ĐỘNG</th>
              <th>CHI TIẾT SỰ KIỆN</th>
              <th>THỜI GIAN</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(item, index) in filteredActivities"
              :key="item.id ?? index"
            >
              <td class="muted">{{ index + 1 }}</td>

              <td>
                <div class="student-cell">
                  <div class="avatar">
                    {{ initials(item.tenSinhVien) }}
                  </div>
                  <div class="student-info">
                    <strong>
                      {{ item.tenSinhVien || "Chưa cập nhật" }}
                    </strong>
                    <small>
                      {{ item.mssv || "Chưa có MSSV" }}
                    </small>
                  </div>
                </div>
              </td>

              <td>
                <span
                  class="activity-pill"
                  :class="activityInfo(item.hanhDong).className"
                >
                  <span class="activity-symbol">
                    {{ activityInfo(item.hanhDong).icon }}
                  </span>
                  {{ activityInfo(item.hanhDong).text }}
                </span>
              </td>

              <td>
                <div class="event-cell">
                  <strong>
                    {{ eventTitle(item) }}
                  </strong>
                  <small>
                    {{ eventDescription(item) }}
                  </small>
                </div>
              </td>

              <td class="time-cell">
                {{ formatDate(item.thoiGian) }}
              </td>

              <td>
                <button
                  type="button"
                  class="detail-btn"
                  @click="openDetail(item)"
                >
                  Chi tiết →
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer class="panel-footer">
        <span>
          Hiển thị {{ filteredActivities.length }}
          / {{ danhSachHoatDong.length }} bản ghi
        </span>

        <span v-if="lastUpdatedAt">
          Cập nhật: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>

    <!-- GHI CHÚ -->
    <div class="info-note">
      <span class="note-symbol">i</span>
      <p>
        Nhật ký hiển thị dữ liệu đã được hệ thống ghi nhận
        trong bảng <strong>hoat_dong</strong>.
        Đây không phải dữ liệu trực tiếp từ máy trạm.
        Chức năng xuất CSV chỉ xuất các bản ghi đang
        hiển thị theo bộ lọc hiện tại.
      </p>
    </div>

    <!-- MODAL CHI TIẾT -->
    <div
      v-if="selectedActivity"
      class="modal-overlay"
      @click.self="closeDetail"
    >
      <div
        class="detail-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="detail-title"
      >
        <header class="modal-header">
          <div>
            <span class="eyebrow">
              NHẬT KÝ #{{ selectedActivity.id }}
            </span>
            <h2 id="detail-title">Chi tiết hoạt động</h2>
          </div>

          <button
            type="button"
            class="close-modal"
            aria-label="Đóng"
            @click="closeDetail"
          >
            ×
          </button>
        </header>

        <div class="modal-body">
          <div class="detail-highlight">
            <span class="highlight-icon">
              {{ activityInfo(selectedActivity.hanhDong).icon }}
            </span>

            <div>
              <strong>
                {{ activityInfo(selectedActivity.hanhDong).text }}
              </strong>
              <small>
                {{ eventTitle(selectedActivity) }}
              </small>
            </div>
          </div>

          <div class="detail-grid">
            <div>
              <span>Sinh viên</span>
              <strong>
                {{ selectedActivity.tenSinhVien || "Chưa cập nhật" }}
              </strong>
            </div>

            <div>
              <span>MSSV</span>
              <strong>
                {{ selectedActivity.mssv || "Chưa cập nhật" }}
              </strong>
            </div>

            <div>
              <span>Thời gian</span>
              <strong>
                {{ formatDate(selectedActivity.thoiGian) }}
              </strong>
            </div>

            <div>
              <span>Phiên giám sát</span>
              <strong>
                {{
                  selectedActivity.phienId != null
                    ? `#${selectedActivity.phienId}`
                    : "Chưa có dữ liệu"
                }}
              </strong>
            </div>

            <div>
              <span>Tên ứng dụng</span>
              <strong>
                {{ selectedActivity.tenUngDung || "Không có dữ liệu" }}
              </strong>
            </div>

            <div>
              <span>Máy trạm</span>
              <strong>
                {{ selectedActivity.tenMay || "Chưa xác định" }}
              </strong>
            </div>
          </div>

          <div class="description-box">
            <span>Chi tiết sự kiện</span>
            <p>
              {{ eventDescription(selectedActivity) }}
            </p>
          </div>

          <div
            v-if="selectedActivity.websiteUrl"
            class="description-box"
          >
            <span>Địa chỉ website được ghi nhận</span>
            <p class="website-value">
              {{ selectedActivity.websiteUrl }}
            </p>
          </div>
        </div>

        <footer class="modal-footer">
          <button
            type="button"
            class="btn-primary"
            @click="closeDetail"
          >
            Đóng
          </button>
        </footer>
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

/* STATE */
const danhSachHoatDong = ref([])
const loading = ref(false)
const hasLoaded = ref(false)
const error = ref("")
const lastUpdatedAt = ref("")
const selectedActivity = ref(null)

const filters = ref({
  search: "",
  loai: "ALL",
  thoiGian: "ALL",
})

let requestId = 0

/* CHUẨN HÓA API */
function extractList(response) {
  const body = response?.data

  if (
    body?.success === false ||
    body?.status === false
  ) {
    throw new Error(
      body.message || "API trả về lỗi"
    )
  }

  const data = body?.data ?? body

  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data

  throw new Error(
    "API không trả về danh sách hợp lệ"
  )
}

/* CHUẨN HÓA HOẠT ĐỘNG */
function normalizeActivity(item) {
  return {
    id: item.id,

    phienId:
      item.phienId ??
      item.phien_id ??
      null,

    sinhVienId:
      item.sinhVienId ??
      item.sinh_vien_id ??
      null,

    tenSinhVien:
      item.tenSinhVien ??
      item.ho_ten ??
      "",

    mssv:
      item.mssv ??
      item.ma_sinh_vien ??
      "",

    tenMay:
      item.tenMay ??
      item.ten_may ??
      "",

    hanhDong: String(
      item.hanhDong ??
      item.loai_hoat_dong ??
      ""
    ).toLowerCase(),

    tenUngDung:
      item.tenUngDung ??
      item.ten_ung_dung ??
      "",

    websiteUrl:
      item.websiteUrl ??
      item.website_url ??
      "",

    chiTiet:
      item.chiTiet ??
      "",

    thoiGian:
      item.thoiGian ??
      item.thoi_gian ??
      null,
  }
}

/* LOẠI HOẠT ĐỘNG */
const activityTypes = {
  ung_dung: {
    text: "Ứng dụng",
    icon: "▣",
    className: "type-app",
  },
  website: {
    text: "Website",
    icon: "◎",
    className: "type-web",
  },
  copy_paste: {
    text: "Sao chép / Dán",
    icon: "▤",
    className: "type-copy",
  },
  tham_gia: {
    text: "Tham gia phiên",
    icon: "↘",
    className: "type-join",
  },
  roi_phien: {
    text: "Rời phiên",
    icon: "↗",
    className: "type-leave",
  },
}

function activityInfo(value) {
  return activityTypes[String(value ?? "").toLowerCase()] || {
    text: value || "Chưa xác định",
    icon: "•",
    className: "type-unknown",
  }
}

/* THÔNG TIN SỰ KIỆN */
function eventTitle(item) {
  if (item.hanhDong === "ung_dung") {
    return item.tenUngDung || item.chiTiet || "Hoạt động ứng dụng"
  }

  if (item.hanhDong === "website") {
    return item.websiteUrl || item.chiTiet || "Hoạt động website"
  }

  if (item.hanhDong === "copy_paste") {
    return "Thao tác sao chép / dán"
  }

  if (item.hanhDong === "tham_gia") {
    return "Sinh viên tham gia phiên"
  }

  if (item.hanhDong === "roi_phien") {
    return "Sinh viên rời phiên"
  }

  return item.chiTiet || "Sự kiện chưa xác định"
}

function eventDescription(item) {
  const details = []

  if (item.tenUngDung) {
    details.push(`Ứng dụng: ${item.tenUngDung}`)
  }

  if (item.websiteUrl) {
    details.push(`Website: ${item.websiteUrl}`)
  }

  if (item.chiTiet) {
    const included =
      details.some((text) => text.includes(item.chiTiet))

    if (!included) details.push(item.chiTiet)
  }

  return details.length
    ? details.join(" · ")
    : "Không có thông tin bổ sung"
}

/* TÌM KIẾM TIẾNG VIỆT KHÔNG DẤU */
function normalizeText(value) {
  return String(value ?? "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D")
    .toLowerCase()
    .trim()
}

/* NGÀY GIỜ */
function parseTime(value) {
  if (!value) return null

  const date = new Date(
    typeof value === "string"
      ? value.replace(" ", "T")
      : value
  )

  return Number.isNaN(date.getTime()) ? null : date
}

function formatDate(value) {
  const date = parseTime(value)

  if (!date) return value || "Chưa cập nhật"

  return date.toLocaleString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  })
}

/* LỌC THEO THỜI GIAN */
function matchesTime(value, filter) {
  if (filter === "ALL") return true

  const date = parseTime(value)
  if (!date) return false

  const now = new Date()
  const start = new Date(now)

  if (filter === "today") {
    start.setHours(0, 0, 0, 0)
  } else if (filter === "week") {
    start.setDate(start.getDate() - 7)
  } else if (filter === "month") {
    start.setDate(start.getDate() - 30)
  }

  return (
    date.getTime() >= start.getTime() &&
    date.getTime() <= now.getTime()
  )
}

/* FILTER */
const filteredActivities = computed(() => {
  const keyword = normalizeText(filters.value.search)

  return danhSachHoatDong.value.filter((item) => {
    const text = normalizeText([
      item.tenSinhVien,
      item.mssv,
      item.tenMay,
      item.tenUngDung,
      item.websiteUrl,
      item.chiTiet,
      activityInfo(item.hanhDong).text,
    ].join(" "))

    const matchesSearch = text.includes(keyword)

    const matchesType =
      filters.value.loai === "ALL" ||
      item.hanhDong === filters.value.loai

    const matchesDate = matchesTime(
      item.thoiGian,
      filters.value.thoiGian
    )

    return matchesSearch && matchesType && matchesDate
  })
})

const hasFilters = computed(() =>
  Boolean(filters.value.search.trim()) ||
  filters.value.loai !== "ALL" ||
  filters.value.thoiGian !== "ALL"
)

function clearFilters() {
  filters.value = {
    search: "",
    loai: "ALL",
    thoiGian: "ALL",
  }
}

/* THỐNG KÊ */
function statText(value) {
  return hasLoaded.value
    ? Number(value).toLocaleString("vi-VN")
    : "—"
}

function countType(type) {
  return danhSachHoatDong.value.filter(
    (item) => item.hanhDong === type
  ).length
}

const studentCountText = computed(() => {
  if (!hasLoaded.value) return "—"

  const ids = new Set()
  let hasUnknown = false

  danhSachHoatDong.value.forEach((item) => {
    const id = item.sinhVienId != null
      ? `id:${item.sinhVienId}`
      : item.mssv
        ? `mssv:${item.mssv}`
        : null

    if (id) {
      ids.add(id)
    } else {
      hasUnknown = true
    }
  })

  if (hasUnknown) return "—"

  return ids.size.toLocaleString("vi-VN")
})

/* AVATAR */
function initials(name) {
  const words = String(name || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (!words.length) return "SV"

  return words
    .slice(-2)
    .map((word) => word[0].toLocaleUpperCase("vi-VN"))
    .join("")
}

/* LỖI API */
function getErrorMessage(err, fallback) {
  const status = err.response?.status

  if (status === 401) {
    return "Phiên đăng nhập đã hết hạn."
  }
  if (status === 403) {
    return "Bạn không có quyền xem nhật ký hoạt động."
  }
  if (status === 404) {
    return "Không tìm thấy API nhật ký hoạt động."
  }
  if (status >= 500) {
    return "Backend gặp lỗi khi truy vấn dữ liệu."
  }

  return err.message || fallback
}

/* LOAD NHẬT KÝ */
async function fetchData() {
  if (loading.value) return

  const current = ++requestId

  loading.value = true
  error.value = ""

  try {
    const response = await api.get(
      "/giang-vien/hoat-dong"
    )

    if (current !== requestId) return

    danhSachHoatDong.value =
      extractList(response).map(normalizeActivity)

    hasLoaded.value = true

    lastUpdatedAt.value =
      new Date().toLocaleString("vi-VN")

  } catch (err) {
    if (current !== requestId) return

    console.error("Lỗi tải nhật ký:", err)

    error.value = getErrorMessage(
      err,
      "Không thể tải nhật ký hoạt động."
    )

    hasLoaded.value = false
  } finally {
    if (current === requestId) {
      loading.value = false
    }
  }
}

/* XEM CHI TIẾT */
function openDetail(item) {
  selectedActivity.value = item
}

function closeDetail() {
  selectedActivity.value = null
}

/* XUẤT CSV */
function exportCSV() {
  if (!hasLoaded.value || filteredActivities.value.length === 0) {
    return
  }

  const rows = [
    [
      "STT",
      "Thời gian",
      "Sinh viên",
      "MSSV",
      "Loại hoạt động",
      "Tên ứng dụng",
      "Website",
      "Chi tiết",
      "Phiên giám sát",
    ],
    ...filteredActivities.value.map((item, index) => [
      index + 1,
      formatDate(item.thoiGian),
      item.tenSinhVien,
      item.mssv,
      activityInfo(item.hanhDong).text,
      item.tenUngDung,
      item.websiteUrl,
      item.chiTiet,
      item.phienId ?? "",
    ]),
  ]

  function escapeCSV(value) {
    let text = String(value ?? "")

    // Giảm nguy cơ thực thi công thức khi mở bằng Excel.
    if (/^\s*[=+\-@\t\r]/.test(text)) {
      text = "'" + text
    }

    return `"${text.replace(/"/g, '""')}"`
  }

  const content = rows
    .map((row) => row.map(escapeCSV).join(","))
    .join("\r\n")

  const blob = new Blob(
    ["\uFEFF" + content],
    { type: "text/csv;charset=utf-8;" }
  )

  const url = URL.createObjectURL(blob)
  const link = document.createElement("a")

  link.href = url
  link.download = "nhat-ky-hoat-dong.csv"

  document.body.appendChild(link)
  link.click()
  link.remove()

  setTimeout(() => URL.revokeObjectURL(url), 1000)
}

onMounted(fetchData)

onBeforeUnmount(() => {
  requestId++
})
</script>

<style scoped>
.activity-page {
  width: 100%;
  min-height: calc(100vh - 80px);
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
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.btn-primary,
.btn-outline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 16px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
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

.btn-outline {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
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

/* THỐNG KÊ */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 15px;
  margin-bottom: 23px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
  padding: 19px;
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
  font-size: 12px;
  font-weight: 900;
}

.stat-content {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 0;
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

.blue { background: #eff6ff; color: #2563eb; }
.purple { background: #faf5ff; color: #9333ea; }
.green { background: #f0fdf4; color: #16a34a; }
.orange { background: #fff7ed; color: #ea580c; }

/* PANEL */
.activity-panel {
  overflow: hidden;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
}

.panel-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  padding: 22px;
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

.count-tag {
  padding: 7px 12px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

/* FILTER */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 11px;
  padding: 0 22px 21px;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 9px;
  flex: 1;
  min-width: 230px;
  padding: 0 12px;
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
  min-width: 0;
  padding: 12px 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: #172033;
  font-size: 12px;
}

.filter-select {
  min-width: 160px;
  max-width: 225px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  outline: none;
  background: #fff;
  color: #334155;
  font-size: 12px;
}

.clear-btn {
  padding: 11px 13px;
  border: 0;
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

.activity-table {
  width: 100%;
  min-width: 950px;
  border-collapse: collapse;
  text-align: left;
}

.activity-table thead {
  background: #f8fafc;
}

.activity-table th {
  padding: 15px 18px;
  color: #64748b;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.3px;
  white-space: nowrap;
}

.activity-table td {
  padding: 16px 18px;
  border-top: 1px solid #f1f5f9;
  font-size: 12px;
  vertical-align: middle;
}

.activity-table tbody tr:hover {
  background: #f8fafc;
}

.student-cell {
  display: flex;
  align-items: center;
  gap: 11px;
}

.avatar {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
}

.student-info,
.event-cell {
  display: flex;
  flex-direction: column;
  min-width: 0;
  gap: 5px;
}

.student-info strong,
.event-cell strong {
  color: #172033;
  font-size: 12px;
  overflow-wrap: anywhere;
}

.student-info small,
.event-cell small {
  color: #94a3b8;
  font-size: 10px;
}

.event-cell small {
  display: block;
  max-width: 260px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-pill {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 11px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 800;
  white-space: nowrap;
}

.activity-symbol {
  font-size: 13px;
}

.type-app {
  background: #eff6ff;
  color: #2563eb;
}

.type-web {
  background: #f0fdf4;
  color: #15803d;
}

.type-copy {
  background: #fff7ed;
  color: #c2410c;
}

.type-join {
  background: #faf5ff;
  color: #9333ea;
}

.type-leave {
  background: #fef2f2;
  color: #b91c1c;
}

.type-unknown {
  background: #f1f5f9;
  color: #64748b;
}

.time-cell,
.muted {
  color: #94a3b8;
  font-size: 11px;
}

.detail-btn {
  padding: 9px 12px;
  border: 0;
  border-radius: 9px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
}

.detail-btn:hover {
  background: #dbeafe;
}

.panel-footer {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 17px 22px;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

/* EMPTY + ERRORS */
.state-box {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 12px;
  min-height: 290px;
  padding: 30px;
  text-align: center;
}

.empty-icon {
  font-size: 35px;
}

.state-box strong {
  font-size: 14px;
}

.state-box p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
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
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  margin: 0 22px 20px;
  padding: 14px;
  border: 1px solid #fecaca;
  border-radius: 10px;
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
  padding: 9px 12px;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  background: #fff;
  color: inherit;
  cursor: pointer;
}

.info-note {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  margin-top: 18px;
  padding: 15px;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  background: #eff6ff;
}

.note-symbol {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 22px;
  height: 22px;
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

/* MODAL */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(3px);
}

.detail-modal {
  width: 100%;
  max-width: 570px;
  max-height: 92vh;
  overflow-y: auto;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 20px 70px rgba(0,0,0,0.2);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 15px;
  padding: 24px 26px 19px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 7px 0 0;
  color: #172033;
  font-size: 21px;
  font-weight: 800;
}

.close-modal {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 9px;
  background: #f1f5f9;
  color: #475569;
  font-size: 23px;
  cursor: pointer;
}

.modal-body {
  padding: 24px 26px;
}

.detail-highlight {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 15px;
  margin-bottom: 21px;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  background: #eff6ff;
}

.highlight-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  border-radius: 10px;
  background: #dbeafe;
  color: #2563eb;
  font-size: 22px;
}

.detail-highlight > div {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.detail-highlight strong {
  font-size: 13px;
}

.detail-highlight small {
  color: #64748b;
  font-size: 11px;
  overflow-wrap: anywhere;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0,1fr));
  gap: 18px;
}

.detail-grid > div {
  display: flex;
  flex-direction: column;
  gap: 7px;
}

.detail-grid span,
.description-box span {
  color: #94a3b8;
  font-size: 11px;
}

.detail-grid strong {
  font-size: 12px;
  overflow-wrap: anywhere;
}

.description-box {
  margin-top: 20px;
  padding: 15px;
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 11px;
}

.description-box p {
  margin: 9px 0 0;
  color: #334155;
  font-size: 12px;
  line-height: 1.8;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 17px 26px;
  border-top: 1px solid #e5e7eb;
}

/* DARK MODE */
:global(html.dark-theme) .activity-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .stat-content strong,
:global(html.dark-theme) .panel-heading h2,
:global(html.dark-theme) .student-info strong,
:global(html.dark-theme) .event-cell strong,
:global(html.dark-theme) .modal-header h2,
:global(html.dark-theme) .detail-grid strong {
  color: #fff;
}

:global(html.dark-theme) .stat-card,
:global(html.dark-theme) .activity-panel,
:global(html.dark-theme) .detail-modal {
  background: #111;
  border-color: rgba(255,255,255,0.14);
}

:global(html.dark-theme) .search-box,
:global(html.dark-theme) .filter-select,
:global(html.dark-theme) .btn-outline,
:global(html.dark-theme) .description-box {
  background: #202327;
  color: #fff;
  border-color: rgba(255,255,255,0.14);
}

:global(html.dark-theme) .search-box input {
  color: #fff;
}

:global(html.dark-theme) .activity-table thead,
:global(html.dark-theme) .activity-table tbody tr:hover {
  background: #202327;
}

:global(html.dark-theme) .activity-table td,
:global(html.dark-theme) .panel-footer,
:global(html.dark-theme) .modal-header,
:global(html.dark-theme) .modal-footer {
  border-color: rgba(255,255,255,0.12);
}

:global(html.dark-theme) .detail-highlight {
  background: rgba(37,99,235,0.15);
  border-color: rgba(96,165,250,0.3);
}

:global(html.dark-theme) .description-box p {
  color: #cbd5e1;
}

:global(html.dark-theme) .info-note {
  background: rgba(37,99,235,0.12);
  border-color: rgba(96,165,250,0.3);
}

:global(html.dark-theme) .info-note p {
  color: #cbd5e1;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0,1fr));
  }
}

@media (max-width: 750px) {
  .activity-page {
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
    min-width: 0;
    max-width: none;
    box-sizing: border-box;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 18px;
    padding-right: 18px;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
