
<template>
  <div class="screen-page">

    <!-- HEADER -->
    <header class="page-header">
      <div>
        <span class="eyebrow">HỆ THỐNG GIÁM SÁT</span>
        <h1>🖥️ Giám sát ảnh màn hình</h1>
        <p>
          Theo dõi ảnh chụp màn hình của sinh viên
          trong từng phiên giám sát lớp học.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn-outline"
          :disabled="loading || loadingPhien || !selectedPhienId"
          @click="fetchAnhManHinh"
        >
          <span :class="{ spinning: loading }">↻</span>
          {{ loading ? "Đang tải..." : "Làm mới ảnh" }}
        </button>

        <router-link
          to="/giang-vien/phien-giam-sat"
          class="btn-primary"
        >
          Quản lý phiên →
        </router-link>
      </div>
    </header>

    <!-- THÔNG TIN PHIÊN -->
    <section class="session-panel">
      <div class="session-icon">GS</div>

      <div class="session-select">
        <label for="session">Phiên giám sát đang xem</label>

        <select
          id="session"
          v-model="selectedPhienId"
          :disabled="loadingPhien"
          @change="changeSession"
        >
          <option value="">-- Chọn phiên giám sát --</option>

          <option
            v-for="phien in danhSachPhien"
            :key="phien.id"
            :value="String(phien.id)"
          >
            {{ phien.tenPhien }}
            {{ phien.tenLop ? `- ${phien.tenLop}` : "" }}
          </option>
        </select>
      </div>

      <div v-if="selectedPhien" class="session-status">
        <span
          class="status-pill"
          :class="sessionStatus(selectedPhien).className"
        >
          <span class="status-dot"></span>
          {{ sessionStatus(selectedPhien).text }}
        </span>
      </div>
    </section>

    <!-- THỐNG KÊ -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">Ả</div>
        <div class="stat-info">
          <span>Tổng ảnh màn hình</span>
          <strong>{{ statText(danhSachAnh.length) }}</strong>
          <small>Ảnh đã lấy từ hệ thống</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">SV</div>
        <div class="stat-info">
          <span>Sinh viên có ảnh</span>
          <strong>{{ statText(soSinhVienCoAnh) }}</strong>
          <small>Đếm theo MSSV hoặc ID sinh viên</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">✓</div>
        <div class="stat-info">
          <span>Ảnh có liên kết</span>
          <strong>{{ statText(soAnhCoLink) }}</strong>
          <small>Chưa bao gồm kiểm tra tải ảnh</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">⌕</div>
        <div class="stat-info">
          <span>Kết quả tìm kiếm</span>
          <strong>{{ statText(filteredImages.length) }}</strong>
          <small>Ảnh phù hợp bộ lọc</small>
        </div>
      </div>
    </div>

    <!-- PANEL ẢNH -->
    <section class="gallery-panel">

      <div class="panel-heading">
        <div>
          <h2>Danh sách ảnh màn hình</h2>
          <p>
            {{
              selectedPhien
                ? selectedPhien.tenPhien
                : "Chọn phiên giám sát để xem ảnh"
            }}
          </p>
        </div>

        <span class="count-tag">
          {{ filteredImages.length }} ảnh
        </span>
      </div>

      <!-- FILTER -->
      <div class="toolbar">
        <div class="search-box">
          <span class="search-icon">⌕</span>

          <input
            v-model="search"
            type="search"
            placeholder="Tìm sinh viên, MSSV hoặc máy trạm..."
            :disabled="!selectedPhienId"
          />
        </div>

        <select
          v-model="imageFilter"
          class="filter-select"
          :disabled="!hasLoaded"
        >
          <option value="all">Tất cả ảnh</option>
          <option value="available">Có liên kết ảnh</option>
          <option value="unavailable">Không có liên kết</option>
        </select>

        <button
          v-if="search || imageFilter !== 'all'"
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
          <strong>Không thể tải dữ liệu giám sát</strong>
          <p>{{ error }}</p>
        </div>

        <button type="button" @click="refreshData">
          Thử lại
        </button>
      </div>

      <!-- LOADING -->
      <div
        v-if="loading || loadingPhien"
        class="state-box"
        role="status"
      >
        <div class="spinner"></div>
        <strong>Đang tải ảnh màn hình...</strong>
        <p>Hệ thống đang lấy dữ liệu giám sát từ máy chủ.</p>
      </div>

      <!-- CHƯA CHỌN PHIÊN -->
      <div
        v-else-if="!selectedPhienId"
        class="state-box"
      >
        <div class="state-icon">🖥️</div>
        <strong>Hãy chọn phiên giám sát</strong>
        <p>
          Chọn một phiên giám sát ở phía trên
          để xem ảnh màn hình sinh viên.
        </p>
      </div>

      <!-- KHÔNG CÓ ẢNH -->
      <div
        v-else-if="hasLoaded && danhSachAnh.length === 0"
        class="state-box"
      >
        <div class="state-icon">🖼️</div>
        <strong>Chưa có ảnh màn hình</strong>
        <p>
          Phiên giám sát này hiện chưa có ảnh chụp
          được lưu trong hệ thống.
        </p>
      </div>

      <!-- KHÔNG TÌM THẤY -->
      <div
        v-else-if="hasLoaded && filteredImages.length === 0"
        class="state-box"
      >
        <div class="state-icon">⌕</div>
        <strong>Không tìm thấy ảnh phù hợp</strong>
        <p>Hãy thử thay đổi từ khóa hoặc bộ lọc.</p>

        <button
          type="button"
          class="btn-outline"
          @click="clearFilters"
        >
          Xóa bộ lọc
        </button>
      </div>

      <!-- GALLERY -->
      <div v-else-if="hasLoaded" class="screens-grid">
        <article
          v-for="item in filteredImages"
          :key="item.id"
          class="screen-card"
        >
          <!-- ẢNH PREVIEW -->
          <div class="screen-preview">
            <img
              v-if="getImageUrl(item) && !imageErrors[getImageKey(item)]"
              :src="getImageUrl(item)"
              :alt="`Ảnh màn hình của ${item.tenSinhVien || 'sinh viên'}`"
              loading="lazy"
              @error="markImageError(item)"
            />

            <div v-else class="no-image">
              <span class="no-image-icon">▣</span>
              <strong>
                {{
                  imageErrors[getImageKey(item)]
                    ? "Không thể tải ảnh"
                    : "Chưa có ảnh hiển thị"
                }}
              </strong>
              <small>
                {{
                  imageErrors[getImageKey(item)]
                    ? "Vui lòng kiểm tra liên kết hoặc quyền truy cập"
                    : "Hệ thống chưa cung cấp liên kết ảnh"
                }}
              </small>
            </div>

            <span class="image-code">
              #{{ item.id }}
            </span>
          </div>

          <!-- THÔNG TIN -->
          <div class="screen-body">

            <div class="student-header">
              <div class="student-avatar">
                {{ getInitials(item.tenSinhVien) }}
              </div>

              <div class="student-info">
                <strong>
                  {{ item.tenSinhVien || "Chưa có tên sinh viên" }}
                </strong>

                <span>
                  MSSV: {{ item.mssv || "Chưa cập nhật" }}
                </span>
              </div>
            </div>

            <div class="screen-details">
              <div class="detail-row">
                <span>Máy trạm</span>
                <strong>
                  {{ item.tenMay || "Chưa xác định" }}
                </strong>
              </div>

              <div class="detail-row">
                <span>Thời gian chụp</span>
                <strong>
                  {{ formatDate(item.thoiGianCapNhat) }}
                </strong>
              </div>

              <div class="detail-row">
                <span>Trạng thái Agent</span>
                <span
                  class="status-pill"
                  :class="agentStatus(item.trangThai).className"
                >
                  {{ agentStatus(item.trangThai).text }}
                </span>
              </div>
            </div>

            <button
              type="button"
              class="view-image-btn"
              :disabled="
                !getImageUrl(item) ||
                !!imageErrors[getImageKey(item)]
              "
              @click="phongToAnh(item)"
            >
              Phóng to ảnh
              <span>↗</span>
            </button>
          </div>
        </article>
      </div>

      <footer class="panel-footer">
        <span>
          Hiển thị {{ filteredImages.length }}
          / {{ danhSachAnh.length }} ảnh
        </span>

        <span v-if="lastUpdatedAt">
          Cập nhật lần cuối: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>

    <!-- GHI CHÚ -->
    <div class="info-note">
      <span class="note-icon">i</span>
      <p>
        Ảnh hiển thị là ảnh đã được Backend lưu trữ,
        không phải truyền hình màn hình trực tiếp.
        Trạng thái Agent chỉ được hiển thị khi API
        cung cấp thông tin kết nối.
      </p>
    </div>

    <!-- MODAL XEM ẢNH -->
    <div
      v-if="selectedImage"
      class="image-modal"
      role="dialog"
      aria-modal="true"
      aria-label="Phóng to ảnh màn hình"
      @click.self="closeImage"
    >
      <div class="image-modal-content">
        <div class="modal-top">
          <div>
            <h2>
              {{ selectedImage.tenSinhVien || "Ảnh màn hình" }}
            </h2>
            <p>
              {{ selectedImage.mssv || "Chưa có MSSV" }}
              · {{ formatDate(selectedImage.thoiGianCapNhat) }}
            </p>
          </div>

          <button type="button" @click="closeImage">
            ✕ Đóng
          </button>
        </div>

        <div class="large-image-wrapper">
          <img
            :src="getImageUrl(selectedImage)"
            alt="Ảnh màn hình phóng to"
            @error="handleLargeImageError"
          />
        </div>

        <div class="modal-bottom">
          <span>
            Máy trạm:
            {{ selectedImage.tenMay || "Chưa xác định" }}
          </span>
          <span>
            Phiên #{{ selectedPhienId }}
          </span>
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
  watch,
} from "vue"

import { useRoute } from "vue-router"
import api from "../../../services/api"

const route = useRoute()

/* STATE */
const danhSachPhien = ref([])
const danhSachAnh = ref([])

const selectedPhienId = ref("")
const selectedImage = ref(null)

const search = ref("")
const imageFilter = ref("all")
const imageErrors = ref({})

const loadingPhien = ref(false)
const loading = ref(false)
const hasLoaded = ref(false)

const error = ref("")
const lastUpdatedAt = ref("")

let sessionRequestId = 0
let imageRequestId = 0
let refreshTimer = null

/* HELPERS API */
function extractList(response) {
  const body = response?.data

  if (body?.status === false || body?.success === false) {
    throw new Error(body.message || "API trả về lỗi")
  }

  const data = body?.data ?? body

  if (Array.isArray(data)) return data

  if (Array.isArray(data?.data)) {
    return data.data
  }

  throw new Error("API không trả về danh sách hợp lệ")
}

/* CHUẨN HÓA PHIÊN */
function normalizePhien(item) {
  return {
    id: item.id,
    tenPhien:
      item.tenPhien ??
      item.ten_phien ??
      `Phiên ${item.id}`,
    tenLop:
      item.tenLop ??
      item.ten_lop ??
      "",
    trangThai:
      item.trangThai ??
      item.trang_thai ??
      "",
  }
}

/* CHUẨN HÓA ẢNH */
function normalizeAnh(item) {
  return {
    id: item.id,
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
    trangThai:
      item.trangThai ??
      item.trang_thai_agent ??
      null,
    imageUrl:
      item.imageUrl ??
      item.image_url ??
      "",
    thoiGianCapNhat:
      item.thoiGianCapNhat ??
      item.thoi_gian_chup ??
      null,
  }
}

const selectedPhien = computed(() =>
  danhSachPhien.value.find(
    (phien) =>
      String(phien.id) === selectedPhienId.value
  ) || null
)

/* TÌM KIẾM KHÔNG DẤU */
function normalizeText(value) {
  return String(value ?? "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/Đ/g, "D")
    .toLowerCase()
    .trim()
}

/* URL ẢNH */
function getImageUrl(item) {
  const url = item?.imageUrl

  if (typeof url !== "string") return ""

  const value = url.trim()

  // Không cho phép javascript: hoặc URL không hợp lệ.
  if (/^https?:\/\//i.test(value)) return value

  if (
    value.startsWith("/") &&
    !value.startsWith("//")
  ) {
    return value
  }

  return ""
}

function getImageKey(item) {
  return String(item?.id ?? "")
}

function markImageError(item) {
  imageErrors.value = {
    ...imageErrors.value,
    [getImageKey(item)]: true,
  }
}

function phongToAnh(item) {
  if (
    !getImageUrl(item) ||
    imageErrors.value[getImageKey(item)]
  ) {
    return
  }

  selectedImage.value = item
}

function closeImage() {
  selectedImage.value = null
}

function handleLargeImageError() {
  if (selectedImage.value) {
    markImageError(selectedImage.value)
  }

  closeImage()
}

/* HIỂN THỊ TRẠNG THÁI PHIÊN */
function sessionStatus(phien) {
  const value = String(
    phien?.trangThai || ""
  ).toUpperCase()

  if (value === "DANG_DIEN_RA") {
    return {
      text: "Đang diễn ra",
      className: "status-active",
    }
  }

  if (value === "CHUA_BAT_DAU") {
    return {
      text: "Chưa bắt đầu",
      className: "status-pending",
    }
  }

  if (value === "DA_KET_THUC") {
    return {
      text: "Đã kết thúc",
      className: "status-finished",
    }
  }

  return {
    text: "Chưa xác định",
    className: "status-unknown",
  }
}

/* TRẠNG THÁI AGENT */
function agentStatus(value) {
  if (
    value === true ||
    ["ONLINE", "CONNECTED"].includes(
      String(value ?? "").toUpperCase()
    )
  ) {
    return {
      text: "Đã kết nối",
      className: "status-active",
    }
  }

  if (
    value === false ||
    ["OFFLINE", "DISCONNECTED"].includes(
      String(value ?? "").toUpperCase()
    )
  ) {
    return {
      text: "Chưa kết nối",
      className: "status-finished",
    }
  }

  return {
    text: "Chưa xác định",
    className: "status-unknown",
  }
}

/* THỐNG KÊ */
function statText(value) {
  return hasLoaded.value
    ? Number(value).toLocaleString("vi-VN")
    : "—"
}

const soSinhVienCoAnh = computed(() => {
  const ids = new Set()

  danhSachAnh.value.forEach((item) => {
    const identifier =
      item.sinhVienId != null
        ? `id:${item.sinhVienId}`
        : item.mssv
          ? `mssv:${item.mssv}`
          : ""

    if (identifier) {
      ids.add(identifier)
    }
  })

  return ids.size
})

const soAnhCoLink = computed(() =>
  danhSachAnh.value.filter(
    (item) => Boolean(getImageUrl(item))
  ).length
)

/* TÌM KIẾM VÀ LỌC */
const filteredImages = computed(() => {
  const keyword = normalizeText(search.value)

  return danhSachAnh.value.filter((item) => {
    const content = normalizeText(
      `${item.tenSinhVien} ${item.mssv} ${item.tenMay}`
    )

    const hasLink = Boolean(getImageUrl(item))

    const matchedFilter =
      imageFilter.value === "all" ||
      (imageFilter.value === "available" && hasLink) ||
      (imageFilter.value === "unavailable" && !hasLink)

    return content.includes(keyword) && matchedFilter
  })
})

function clearFilters() {
  search.value = ""
  imageFilter.value = "all"
}

function getInitials(name) {
  const words = String(name || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (!words.length) return "SV"

  return words
    .slice(-2)
    .map((word) =>
      word.charAt(0).toLocaleUpperCase("vi-VN")
    )
    .join("")
}

function formatDate(value) {
  if (!value) return "Chưa cập nhật"

  const date = new Date(
    typeof value === "string"
      ? value.replace(" ", "T")
      : value
  )

  if (Number.isNaN(date.getTime())) {
    return String(value)
  }

  return date.toLocaleString("vi-VN", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  })
}

/* LỖI API */
function getErrorMessage(err, fallback) {
  const status = err.response?.status

  if (status === 401) {
    return "Phiên đăng nhập đã hết hạn."
  }

  if (status === 403) {
    return "Bạn không có quyền xem phiên giám sát này."
  }

  if (status === 404) {
    return "Không tìm thấy phiên giám sát hoặc API."
  }

  if (status >= 500) {
    return "Backend gặp lỗi khi truy vấn dữ liệu."
  }

  return err.message || fallback
}

/* LOAD DANH SÁCH PHIÊN */
async function fetchPhienGiamSat() {
  const current = ++sessionRequestId

  loadingPhien.value = true
  error.value = ""

  try {
    const response = await api.get(
      "/giang-vien/phien-giam-sat"
    )

    if (current !== sessionRequestId) return

    danhSachPhien.value =
      extractList(response).map(normalizePhien)

    const requestedId = String(
      route.query.phienId ?? selectedPhienId.value ?? ""
    )

    const valid = danhSachPhien.value.some(
      (phien) => String(phien.id) === requestedId
    )

    if (valid) {
      selectedPhienId.value = requestedId
      await fetchAnhManHinh()
    } else {
      selectedPhienId.value = ""
      danhSachAnh.value = []
      hasLoaded.value = false

      if (requestedId) {
        error.value =
          "Phiên giám sát được yêu cầu không thuộc danh sách của bạn."
      }
    }
  } catch (err) {
    if (current !== sessionRequestId) return

    console.error("Lỗi tải phiên giám sát:", err)

    error.value = getErrorMessage(
      err,
      "Không thể tải danh sách phiên giám sát."
    )
  } finally {
    if (current === sessionRequestId) {
      loadingPhien.value = false
    }
  }
}

/* LOAD ẢNH */
async function fetchAnhManHinh() {
  const current = ++imageRequestId
  const phienId = selectedPhienId.value

  error.value = ""
  hasLoaded.value = false
  danhSachAnh.value = []
  imageErrors.value = {}
  selectedImage.value = null

  if (!phienId) {
    loading.value = false
    return
  }

  loading.value = true

  try {
    const response = await api.get(
      "/giang-vien/anh-man-hinh",
      {
        params: { phienId },
      }
    )

    if (current !== imageRequestId) return

    danhSachAnh.value =
      extractList(response).map(normalizeAnh)

    hasLoaded.value = true
    lastUpdatedAt.value =
      new Date().toLocaleString("vi-VN")

  } catch (err) {
    if (current !== imageRequestId) return

    console.error("Lỗi tải ảnh màn hình:", err)

    error.value = getErrorMessage(
      err,
      "Không thể tải ảnh màn hình."
    )
  } finally {
    if (current === imageRequestId) {
      loading.value = false
    }
  }
}

function changeSession() {
  clearFilters()
  fetchAnhManHinh()
}

function refreshData() {
  if (selectedPhienId.value) {
    fetchAnhManHinh()
  } else {
    fetchPhienGiamSat()
  }
}

/* CHUYỂN PHIÊN TỪ URL */
watch(
  () => route.query.phienId,
  (newId) => {
    const id = String(newId ?? "")

    if (
      id &&
      danhSachPhien.value.some(
        (phien) => String(phien.id) === id
      ) &&
      id !== selectedPhienId.value
    ) {
      selectedPhienId.value = id
      clearFilters()
      fetchAnhManHinh()
    }
  }
)


onMounted(() => {
  fetchPhienGiamSat()

  // Cập nhật ảnh mới mỗi 15 giây.
  refreshTimer = setInterval(() => {
    if (
      selectedPhienId.value &&
      !loading.value &&
      !selectedImage.value
    ) {
      fetchAnhManHinh()
    }
  }, 15000)
})

onBeforeUnmount(() => {
  sessionRequestId++
  imageRequestId++

  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
})

</script>

<style scoped>
/* PAGE */
.screen-page {
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
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 16px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  white-space: nowrap;
}

.btn-primary {
  border: 1px solid #2563eb;
  background: #2563eb;
  color: #fff;
}

.btn-primary:hover {
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

/* SESSION SELECT */
.session-panel {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
  margin-bottom: 23px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
}

.session-icon {
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 900;
}

.session-select {
  flex: 1;
  min-width: 0;
}

.session-select label {
  display: block;
  margin-bottom: 7px;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
}

.session-select select {
  width: 100%;
  padding: 11px 13px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #172033;
  font-size: 12px;
  outline: none;
}

.session-select select:focus {
  border-color: #60a5fa;
}

/* STATS */
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
  gap: 14px;
  padding: 19px;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: #fff;
}

.stat-icon {
  width: 46px;
  height: 46px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 900;
}

.stat-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
  gap: 5px;
}

.stat-info span {
  color: #64748b;
  font-size: 11px;
}

.stat-info strong {
  color: #172033;
  font-size: 26px;
  font-weight: 800;
}

.stat-info small {
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

/* GALLERY PANEL */
.gallery-panel {
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
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

/* TOOLBAR */
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
  min-width: 180px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #334155;
  font-size: 12px;
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

/* SCREEN GRID */
.screens-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
  padding: 0 22px 22px;
}

.screen-card {
  min-width: 0;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: #fff;
  transition: 0.2s ease;
}

.screen-card:hover {
  transform: translateY(-3px);
  border-color: #bfdbfe;
  box-shadow: 0 9px 24px rgba(15, 23, 42, 0.08);
}

/* IMAGE */
.screen-preview {
  position: relative;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  background: #111827;
}

.screen-preview img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.image-code {
  position: absolute;
  top: 12px;
  left: 12px;
  padding: 5px 9px;
  border-radius: 7px;
  background: rgba(15, 23, 42, 0.75);
  color: #fff;
  font-size: 10px;
  font-weight: 700;
}

.no-image {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 8px;
  padding: 14px;
  box-sizing: border-box;
  color: #cbd5e1;
  text-align: center;
}

.no-image-icon {
  font-size: 37px;
}

.no-image strong {
  font-size: 12px;
}

.no-image small {
  font-size: 10px;
  color: #94a3b8;
}

/* CARD BODY */
.screen-body {
  padding: 18px;
}

.student-header {
  display: flex;
  align-items: center;
  gap: 11px;
  margin-bottom: 17px;
}

.student-avatar {
  width: 39px;
  height: 39px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 11px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
}

.student-info {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 5px;
}

.student-info strong {
  color: #172033;
  font-size: 12px;
  overflow-wrap: anywhere;
}

.student-info span {
  color: #94a3b8;
  font-size: 10px;
}

.screen-details {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 14px 0;
  border-top: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.detail-row > span:first-child {
  color: #64748b;
  font-size: 11px;
}

.detail-row strong {
  color: #334155;
  font-size: 11px;
  font-weight: 700;
  text-align: right;
}

.status-pill {
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

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-finished {
  background: #f1f5f9;
  color: #64748b;
}

.status-unknown {
  background: #e2e8f0;
  color: #475569;
}

.view-image-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 12px 14px;
  margin-top: 17px;
  border: none;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 800;
  cursor: pointer;
}

.view-image-btn:hover:not(:disabled) {
  background: #dbeafe;
}

/* EMPTY */
.state-box {
  min-height: 290px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 28px;
  text-align: center;
}

.state-icon {
  font-size: 35px;
}

.state-box strong {
  font-size: 14px;
}

.state-box p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.7;
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
  gap: 15px;
  margin: 0 22px 20px;
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
  border: 1px solid #fca5a5;
  border-radius: 9px;
  padding: 9px 13px;
  background: #fff;
  color: #b91c1c;
  cursor: pointer;
}

.panel-footer {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 17px 22px;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

/* INFO */
.info-note {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 15px;
  margin-top: 18px;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  background: #eff6ff;
}

.note-icon {
  width: 22px;
  height: 22px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
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

/* IMAGE MODAL */
.image-modal {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
  background: rgba(2, 6, 23, 0.94);
}

.image-modal-content {
  width: 100%;
  max-width: 1250px;
  max-height: 96vh;
  overflow-y: auto;
}

.modal-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-bottom: 15px;
}

.modal-top h2 {
  margin: 0 0 6px;
  color: #fff;
  font-size: 19px;
}

.modal-top p {
  margin: 0;
  color: #94a3b8;
  font-size: 12px;
}

.modal-top button {
  padding: 10px 15px;
  border: 1px solid #475569;
  border-radius: 9px;
  background: #1e293b;
  color: #fff;
  font-size: 12px;
  cursor: pointer;
}

.large-image-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 300px;
  background: #111827;
  border-radius: 12px;
  overflow: hidden;
}

.large-image-wrapper img {
  display: block;
  max-width: 100%;
  max-height: 75vh;
  object-fit: contain;
}

.modal-bottom {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  flex-wrap: wrap;
  padding-top: 14px;
  color: #94a3b8;
  font-size: 11px;
}

/* DARK MODE */
:global(html.dark-theme) .screen-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .stat-info strong,
:global(html.dark-theme) .panel-heading h2,
:global(html.dark-theme) .student-info strong,
:global(html.dark-theme) .detail-row strong {
  color: #fff;
}

:global(html.dark-theme) .session-panel,
:global(html.dark-theme) .stat-card,
:global(html.dark-theme) .gallery-panel,
:global(html.dark-theme) .screen-card {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .session-select select,
:global(html.dark-theme) .search-box,
:global(html.dark-theme) .filter-select,
:global(html.dark-theme) .btn-outline {
  background: #202327;
  border-color: rgba(255, 255, 255, 0.14);
  color: #fff;
}

:global(html.dark-theme) .search-box input {
  color: #fff;
}

:global(html.dark-theme) .screen-details,
:global(html.dark-theme) .panel-footer {
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .info-note {
  background: rgba(37, 99, 235, 0.12);
  border-color: rgba(96, 165, 250, 0.3);
}

:global(html.dark-theme) .info-note p {
  color: #cbd5e1;
}

:global(html.dark-theme) .status-finished {
  background: #2b3038;
  color: #cbd5e1;
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
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .screens-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .screen-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .page-header h1 {
    font-size: 23px;
  }

  .header-actions {
    flex-wrap: wrap;
  }

  .session-panel {
    flex-wrap: wrap;
  }

  .session-select {
    min-width: 180px;
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
  }

  .screens-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 470px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .modal-top {
    flex-direction: column;
    align-items: flex-start;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
