
<template>
  <div class="monitor-page">
    <!-- HEADER -->
    <header class="page-header">
      <div class="heading">
        <span class="eyebrow">GIÁM SÁT LỚP HỌC</span>
        <h1>🖥️ Quản lý phiên giám sát</h1>
        <p>
          Tạo phiên giám sát thi, liên kết bài kiểm tra và theo dõi
          hoạt động làm bài của sinh viên.
        </p>
      </div>

      <div class="header-actions">
        <button
          type="button"
          class="btn-secondary"
          :disabled="loading"
          @click="fetchData"
        >
          <span :class="{ spinning: loading }">↻</span>
          Làm mới
        </button>

        <button
          type="button"
          class="btn-primary"
          @click="openCreateForm"
        >
          <span class="plus">+</span>
          Tạo phiên mới
        </button>
      </div>
    </header>

    <!-- THỐNG KÊ -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">GS</div>
        <div class="stat-info">
          <span>Tổng số phiên</span>
          <strong>{{ statValue(danhSachPhien.length) }}</strong>
          <small>Phiên giám sát đã tạo</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">▶</div>
        <div class="stat-info">
          <span>Đang diễn ra</span>
          <strong>{{ statValue(soDangDienRa) }}</strong>
          <small>Phiên đang hoạt động</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon orange">◷</div>
        <div class="stat-info">
          <span>Chưa bắt đầu</span>
          <strong>{{ statValue(soChuaBatDau) }}</strong>
          <small>Phiên đã được lên lịch</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">✓</div>
        <div class="stat-info">
          <span>Đã kết thúc</span>
          <strong>{{ statValue(soDaKetThuc) }}</strong>
          <small>Phiên đã hoàn thành</small>
        </div>
      </div>
    </div>

    <!-- KHU VỰC DANH SÁCH -->
    <section class="session-panel">
      <div class="panel-heading">
        <div>
          <h2>Danh sách phiên giám sát</h2>
          <p>
            Xem trạng thái và quản lý các phiên
            thuộc lớp bạn phụ trách.
          </p>
        </div>
        <span class="count-tag">
          {{ danhSachHienThi.length }} phiên
        </span>
      </div>

      <!-- BỘ LỌC -->
      <div class="toolbar">
        <div class="search-box">
          <span class="search-icon">⌕</span>
          <input
            v-model="keyword"
            type="search"
            placeholder="Tìm tên phiên hoặc tên lớp..."
            aria-label="Tìm kiếm phiên giám sát"
          />
        </div>

        <select
          v-model="selectedLopId"
          class="filter-select"
          aria-label="Lọc theo lớp"
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
          class="filter-select"
          aria-label="Lọc trạng thái phiên"
        >
          <option value="all">Tất cả trạng thái</option>
          <option value="dang_dien_ra">Đang diễn ra</option>
          <option value="chua_bat_dau">Chưa bắt đầu</option>
          <option value="da_ket_thuc">Đã kết thúc</option>
        </select>
      </div>

      <!-- THÔNG BÁO -->
      <div v-if="error" class="notice error-notice" role="alert">
        <span>{{ error }}</span>
        <button type="button" @click="fetchData">Thử lại</button>
      </div>

      <div v-if="success" class="notice success-notice" role="status">
        <span>✓ {{ success }}</span>
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
        class="empty-state"
        role="status"
      >
        <div class="spinner"></div>
        <strong>Đang tải phiên giám sát...</strong>
        <p>Hệ thống đang lấy dữ liệu từ máy chủ.</p>
      </div>

      <!-- CHƯA CÓ PHIÊN -->
      <div
        v-else-if="hasLoaded && danhSachPhien.length === 0"
        class="empty-state"
      >
        <div class="empty-icon">🖥️</div>
        <strong>Chưa có phiên giám sát</strong>
        <p>
          Bạn có thể tạo phiên giám sát mới
          cho lớp học đang phụ trách.
        </p>

        <button
          type="button"
          class="btn-primary"
          @click="openCreateForm"
        >
          + Tạo phiên đầu tiên
        </button>
      </div>

      <!-- KHÔNG CÓ KẾT QUẢ -->
      <div
        v-else-if="hasLoaded && danhSachHienThi.length === 0"
        class="empty-state"
      >
        <div class="empty-icon">⌕</div>
        <strong>Không tìm thấy phiên phù hợp</strong>
        <p>Hãy thử thay đổi bộ lọc hoặc từ khóa.</p>

        <button type="button" class="btn-secondary" @click="clearFilters">
          Xóa bộ lọc
        </button>
      </div>

      <!-- DANH SÁCH PHIÊN -->
      <div v-else-if="hasLoaded" class="session-grid">
        <article
          v-for="phien in danhSachHienThi"
          :key="phien.id"
          class="session-card"
        >
          <div class="card-top">
            <div class="session-icon">GS</div>

            <span
              class="status-badge"
              :class="getStatus(phien).className"
            >
              <span class="status-dot"></span>
              {{ getStatus(phien).text }}
            </span>
          </div>

          <div class="session-info">
            <span class="session-code">
              PHIÊN #{{ phien.id }}
            </span>

            <h3>
              {{ phien.tenPhien || "Chưa có tên phiên" }}
            </h3>

            <p>{{ phien.tenLop || getTenLop(phien.lopId) }}</p>
            <p class="exam-title">📝 {{ phien.tenBaiKiemTra || 'Chưa liên kết bài kiểm tra' }}</p>
          </div>

          <div class="card-divider"></div>

          <div class="session-details">
            <div class="detail-row">
              <span>Bắt đầu</span>
              <strong>
                {{ formatDate(phien.thoigianBatDau) }}
              </strong>
            </div>

            <div class="detail-row">
              <span>Trạng thái</span>
              <strong>{{ getStatus(phien).text }}</strong>
            </div>
            <div v-if="phien.thoigianKetThuc" class="detail-row">
              <span>Kết thúc</span>
              <strong>{{ formatDate(phien.thoigianKetThuc) }}</strong>
            </div>
          </div>

          <div class="card-actions">
            <button
              type="button"
              class="view-btn"
              @click="xemGiamSat(phien)"
            >
              Xem giám sát
              <span>→</span>
            </button>

            <button
              v-if="getStatus(phien).value === 'chua_bat_dau'"
              type="button"
              class="start-btn"
              :disabled="startingId !== null || endingId !== null"
              @click="batDauPhien(phien)"
            >
              {{ startingId === phien.id ? "Đang bắt đầu..." : "▶ Bắt đầu" }}
            </button>

            <button
              type="button"
              class="end-btn"
              :disabled="endingId !== null || !canEnd(phien)"
              :title="
                canEnd(phien)
                  ? 'Kết thúc phiên giám sát'
                  : 'Chỉ phiên đang diễn ra mới có thể kết thúc'
              "
              @click="openEndConfirm(phien)"
            >
              {{
                endingId === phien.id
                  ? "Đang kết thúc..."
                  : "Kết thúc"
              }}
            </button>
          </div>
        </article>
      </div>

      <footer class="panel-footer">
        <span>
          Hiển thị {{ danhSachHienThi.length }}
          / {{ danhSachPhien.length }} phiên
        </span>
        <span v-if="lastUpdatedAt">
          Cập nhật lần cuối: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>

    <!-- GỢI Ý -->
    <div class="info-note">
      <span class="note-symbol">i</span>
      <p>
        Chỉ có thể kết thúc phiên có trạng thái
        <strong>Đang diễn ra</strong>. Bạn vẫn có thể
        xem dữ liệu giám sát của phiên đã kết thúc
        nếu dữ liệu đã được hệ thống lưu trữ.
      </p>
    </div>

    <!-- MODAL TẠO PHIÊN -->
    <div
      v-if="showCreateForm"
      class="modal-overlay"
      @click.self="closeCreateForm"
    >
      <div
        class="session-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-session-title"
      >
        <header class="modal-header">
          <div>
            <span class="eyebrow">PHIÊN GIÁM SÁT</span>
            <h2 id="create-session-title">Tạo phiên giám sát mới</h2>
            <p>
              Thiết lập một phiên giám sát cho lớp học.
            </p>
          </div>

          <button
            type="button"
            class="close-btn"
            :disabled="saving"
            aria-label="Đóng"
            @click="closeCreateForm"
          >
            ×
          </button>
        </header>

        <form @submit.prevent="createPhien">
          <div class="modal-body">
            <div class="form-field">
              <label for="session-name">
                Tên phiên giám sát *
              </label>

              <input
                id="session-name"
                v-model.trim="form.tenPhien"
                type="text"
                maxlength="200"
                placeholder="Ví dụ: Giám sát thực hành tuần 3"
                :disabled="saving"
                required
              />
            </div>

            <div class="form-field">
              <label for="session-class">Lớp học *</label>

              <select
                id="session-class"
                v-model="form.lopId"
                :disabled="saving || loadingLop"
                required
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

              <small v-if="loadingLop">
                Đang tải danh sách lớp học...
              </small>

              <small v-if="lopError" class="field-error">
                {{ lopError }}
                <button
                  type="button"
                  :disabled="loadingLop"
                  @click="fetchLopHoc"
                >
                  Thử lại
                </button>
              </small>

              <small
                v-if="!loadingLop && !lopError && danhSachLop.length === 0"
                class="field-error"
              >
                Bạn chưa có lớp học được phân công.
              </small>
            </div>

            <div class="form-field">
              <label for="session-exam">Bài kiểm tra *</label>
              <select id="session-exam" v-model="form.baiTapId"
                :disabled="saving || loadingBaiTap || !form.lopId" required>
                <option value="">-- Chọn bài kiểm tra thuộc lớp --</option>
                <option v-for="bai in baiTapTrongLop" :key="bai.id" :value="String(bai.id)">
                  {{ bai.tieuDe }} (#{{ bai.id }})
                </option>
              </select>
              <small v-if="loadingBaiTap">Đang tải danh sách bài kiểm tra...</small>
              <small v-if="baiTapError" class="field-error">{{ baiTapError }}</small>
              <small v-if="form.lopId && !loadingBaiTap && baiTapTrongLop.length === 0">
                Lớp này chưa có bài kiểm tra. Hãy tạo bài kiểm tra trước.
              </small>
            </div>

            <div class="form-field">
              <label for="session-start">
                Thời gian bắt đầu *
              </label>

              <input
                id="session-start"
                v-model="form.thoigianBatDau"
                type="datetime-local"
                :disabled="saving"
                required
              />

              <small>
                Bạn có thể bắt đầu ngay hoặc đặt thời gian
                trong tương lai.
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

          <footer class="modal-footer">
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
              :disabled="saving || loadingLop || danhSachLop.length === 0"
            >
              {{
                saving
                  ? "Đang tạo..."
                  : "Tạo phiên giám sát"
              }}
            </button>
          </footer>
        </form>
      </div>
    </div>

    <!-- MODAL XÁC NHẬN KẾT THÚC -->
    <div
      v-if="confirmSession"
      class="modal-overlay"
      @click.self="closeEndConfirm"
    >
      <div
        class="confirm-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="end-session-title"
      >
        <div class="confirm-icon">!</div>

        <h2 id="end-session-title">
          Kết thúc phiên giám sát?
        </h2>

        <p>
          Bạn có chắc muốn kết thúc phiên
          <strong>{{ confirmSession.tenPhien }}</strong>?
          Sau khi kết thúc, phiên sẽ chuyển sang
          trạng thái <strong>Đã kết thúc</strong>.
        </p>

        <div class="confirm-actions">
          <button
            type="button"
            class="btn-secondary"
            :disabled="endingId !== null"
            @click="closeEndConfirm"
          >
            Hủy
          </button>

          <button
            type="button"
            class="btn-danger"
            :disabled="endingId !== null"
            @click="ketThucPhien"
          >
            {{
              endingId !== null
                ? "Đang kết thúc..."
                : "Xác nhận kết thúc"
            }}
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
import { useRouter } from "vue-router"
import api from "../../../services/api"

const router = useRouter()

/* STATE */
const danhSachPhien = ref([])
const danhSachLop = ref([])
const danhSachBaiTap = ref([])
const loadingBaiTap = ref(false)
const baiTapError = ref("")

const loading = ref(false)
const loadingLop = ref(false)
const hasLoaded = ref(false)
const saving = ref(false)
const endingId = ref(null)
const startingId = ref(null)

const error = ref("")
const success = ref("")
const formError = ref("")
const lopError = ref("")
const lastUpdatedAt = ref("")

const keyword = ref("")
const selectedLopId = ref("")
const statusFilter = ref("all")

const showCreateForm = ref(false)
const confirmSession = ref(null)

const form = ref({
  tenPhien: "",
  lopId: "",
  baiTapId: "",
  thoigianBatDau: "",
})

let sessionRequestId = 0
let classRequestId = 0
let examRequestId = 0
let refreshTimer = null

/* CHUẨN HÓA DANH SÁCH API */
function extractList(response) {
  const body = response?.data

  if (body?.status === false || body?.success === false) {
    throw new Error(body.message || "API trả về lỗi")
  }

  const data = body?.data ?? body

  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data

  throw new Error("API không trả về danh sách hợp lệ")
}

/* CHUẨN HÓA LỚP */
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

/* CHUẨN HÓA PHIÊN */
function normalizePhien(item) {
  return {
    id: item.id,
    tenPhien:
      item.tenPhien ??
      item.ten_phien ??
      "",
    tenLop:
      item.tenLop ??
      item.ten_lop ??
      "",
    baiTapId: item.baiTapId ?? item.bai_tap_id ?? null,
    tenBaiKiemTra: item.tenBaiKiemTra ?? item.tieuDeBaiTap ?? "",
    lopId:
      item.lopId ??
      item.lop_hoc_id ??
      null,
    thoigianBatDau:
      item.thoigianBatDau ??
      item.thoi_gian_bat_dau ??
      null,
    thoigianKetThuc:
      item.thoigianKetThuc ??
      item.thoi_gian_ket_thuc ?? null,
    trangThai: String(
      item.trangThai ??
      item.trang_thai ??
      ""
    ).toUpperCase(),
  }
}

const baiTapTrongLop = computed(() =>
  danhSachBaiTap.value.filter(b => String(b.lopId) === String(form.value.lopId))
)

async function fetchBaiTap() {
  const current = ++examRequestId
  loadingBaiTap.value = true
  baiTapError.value = ""
  try {
    const response = await api.get("/giang-vien/bai-tap")
    if (current !== examRequestId) return
    danhSachBaiTap.value = extractList(response).map(item => ({
      id: item.id,
      lopId: item.lopId ?? item.lop_hoc_id,
      tieuDe: item.tieuDe ?? item.tieu_de ?? `Bài #${item.id}`,
    }))
  } catch (err) {
    if (current === examRequestId) baiTapError.value = getErrorMessage(err, "Không tải được bài kiểm tra")
  } finally {
    if (current === examRequestId) loadingBaiTap.value = false
  }
}

/* TRẠNG THÁI */
function getStatus(phien) {
  const status = String(phien?.trangThai ?? "")
    .toUpperCase()

  if (["DANG_DIEN_RA", "ACTIVE"].includes(status)) {
    return {
      text: "Đang diễn ra",
      className: "status-active",
      value: "dang_dien_ra",
    }
  }

  if (["CHUA_BAT_DAU", "PENDING"].includes(status)) {
    return {
      text: "Chưa bắt đầu",
      className: "status-pending",
      value: "chua_bat_dau",
    }
  }

  if (["DA_KET_THUC", "FINISHED"].includes(status)) {
    return {
      text: "Đã kết thúc",
      className: "status-finished",
      value: "da_ket_thuc",
    }
  }

  return {
    text: "Chưa xác định",
    className: "status-unknown",
    value: "unknown",
  }
}

function canEnd(phien) {
  return getStatus(phien).value === "dang_dien_ra"
}

/* THỐNG KÊ */
function statValue(value) {
  return hasLoaded.value
    ? Number(value).toLocaleString("vi-VN")
    : "—"
}

const soDangDienRa = computed(() =>
  danhSachPhien.value.filter(canEnd).length
)

const soChuaBatDau = computed(() =>
  danhSachPhien.value.filter(
    (p) => getStatus(p).value === "chua_bat_dau"
  ).length
)

const soDaKetThuc = computed(() =>
  danhSachPhien.value.filter(
    (p) => getStatus(p).value === "da_ket_thuc"
  ).length
)

/* TÊN LỚP */
function getTenLop(lopId) {
  const lop = danhSachLop.value.find(
    (item) => String(item.id) === String(lopId)
  )

  return lop?.ten_lop || "Chưa cập nhật"
}

/* TÌM KIẾM & LỌC */
const danhSachHienThi = computed(() => {
  const search = keyword.value
    .trim()
    .toLocaleLowerCase("vi-VN")

  return danhSachPhien.value.filter((phien) => {
    const name =
      `${phien.tenPhien} ${phien.tenLop || getTenLop(phien.lopId)}`
        .toLocaleLowerCase("vi-VN")

    const matchSearch = name.includes(search)

    const matchClass =
      !selectedLopId.value ||
      String(phien.lopId) === selectedLopId.value

    const matchStatus =
      statusFilter.value === "all" ||
      getStatus(phien).value === statusFilter.value

    return matchSearch && matchClass && matchStatus
  })
})

function clearFilters() {
  keyword.value = ""
  selectedLopId.value = ""
  statusFilter.value = "all"
}

/* NGÀY GIỜ */
function getLocalDateTime() {
  const date = new Date()
  const pad = (n) => String(n).padStart(2, "0")

  return (
    date.getFullYear() + "-" +
    pad(date.getMonth() + 1) + "-" +
    pad(date.getDate()) + "T" +
    pad(date.getHours()) + ":" +
    pad(date.getMinutes())
  )
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
  })
}

function getErrorMessage(err, fallback) {
  const status = err.response?.status

  if (status === 401) {
    return "Phiên đăng nhập đã hết hạn."
  }

  if (status === 403) {
    return "Bạn không có quyền thực hiện thao tác này."
  }

  if (status === 404) {
    return "Không tìm thấy API hoặc phiên giám sát."
  }

  if (status === 409) {
    return err.response?.data?.message ||
      "Trạng thái phiên đã thay đổi. Hãy tải lại."
  }

  if (status >= 500) {
    return "Backend gặp lỗi khi xử lý dữ liệu."
  }

  return err.message || fallback
}

/* LOAD DANH SÁCH PHIÊN */
async function fetchData() {
  const current = ++sessionRequestId

  loading.value = true
  error.value = ""

  try {
    const response = await api.get(
      "/giang-vien/phien-giam-sat"
    )

    if (current !== sessionRequestId) return false

    danhSachPhien.value =
      extractList(response).map(normalizePhien)

    hasLoaded.value = true
    lastUpdatedAt.value =
      new Date().toLocaleString("vi-VN")

    return true
  } catch (err) {
    if (current !== sessionRequestId) return false

    console.error("Lỗi tải phiên giám sát:", err)
    error.value = getErrorMessage(
      err,
      "Không thể tải danh sách phiên giám sát."
    )
    return false
  } finally {
    if (current === sessionRequestId) {
      loading.value = false
    }
  }
}

/* LOAD DANH SÁCH LỚP */
async function fetchLopHoc() {
  const current = ++classRequestId
  loadingLop.value = true
  lopError.value = ""

  try {
    const response = await api.get(
      "/giang-vien/lop-hoc"
    )

    if (current !== classRequestId) return

    danhSachLop.value =
      extractList(response).map(normalizeLop)
  } catch (err) {
    if (current !== classRequestId) return

    console.error("Lỗi tải lớp học:", err)
    lopError.value = getErrorMessage(
      err,
      "Không thể tải danh sách lớp học."
    )
  } finally {
    if (current === classRequestId) {
      loadingLop.value = false
    }
  }
}

/* XEM GIÁM SÁT */
function xemGiamSat(phien) {
  if (!phien?.id) return

  // Nếu phiên đang diễn ra: mở trang giám sát camera trực tiếp
  const status = getStatus(phien).value
  if (status === 'dang_dien_ra') {
    router.push({
      path: "/giang-vien/giam-sat-thi",
      query: { phienId: phien.id },
    })
    return
  }

  // Nếu phiên đã kết thúc hoặc trạng thái khác: xem lịch sử ảnh màn hình
  router.push({
    path: "/giang-vien/anh-man-hinh",
    query: { phienId: phien.id },
  })
}

/* MỞ FORM TẠO PHIÊN */
function openCreateForm() {
  form.value = {
    tenPhien: "",
    lopId: selectedLopId.value,
    baiTapId: "",
    thoigianBatDau: getLocalDateTime(),
  }

  formError.value = ""
  showCreateForm.value = true

  if (
    danhSachLop.value.length === 0 &&
    !loadingLop.value
  ) {
    fetchLopHoc()
  }
  if (!loadingBaiTap.value && danhSachBaiTap.value.length === 0) fetchBaiTap()
}

function closeCreateForm() {
  if (saving.value) return

  showCreateForm.value = false
  formError.value = ""
}

/* TẠO PHIÊN */
async function createPhien() {
  if (saving.value) return

  formError.value = ""

  const tenPhien = form.value.tenPhien.trim()
  const lopId = Number(form.value.lopId)
  const baiTapId = Number(form.value.baiTapId)
  const startTime = new Date(form.value.thoigianBatDau)

  if (!tenPhien) {
    formError.value = "Vui lòng nhập tên phiên."
    return
  }

  if (
    !Number.isInteger(lopId) ||
    lopId <= 0 ||
    !danhSachLop.value.some(
      (lop) => Number(lop.id) === lopId
    )
  ) {
    formError.value = "Vui lòng chọn lớp học hợp lệ."
    return
  }

  if (!Number.isInteger(baiTapId) || baiTapId <= 0 ||
      !baiTapTrongLop.value.some(b => Number(b.id) === baiTapId)) {
    formError.value = "Vui lòng chọn bài kiểm tra thuộc lớp đã chọn."
    return
  }

  if (Number.isNaN(startTime.getTime())) {
    formError.value = "Thời gian bắt đầu không hợp lệ."
    return
  }

  saving.value = true

  try {
    const response = await api.post(
      "/giang-vien/phien-giam-sat",
      {
        tenPhien,
        lopId,
        baiTapId,
        // Gửi giờ địa phương theo định dạng DATETIME của Laravel/MySQL.
        // Không dùng toISOString() vì sẽ chuyển thành UTC và lệch 7 giờ.
        thoigianBatDau: form.value.thoigianBatDau.replace("T", " ") + ":00",
      }
    )

    if (
      response.data?.success === false ||
      response.data?.status === false
    ) {
      throw new Error(
        response.data?.message || "Tạo phiên thất bại."
      )
    }

    const createdId = response.data?.data?.id

    if (createdId == null) {
      throw new Error(
        "API chưa trả về ID phiên mới. " +
        "Hãy kiểm tra danh sách trước khi tạo lại."
      )
    }

    showCreateForm.value = false
    success.value = `Đã tạo phiên #${createdId} thành công.`

    const refreshed = await fetchData()

    if (!refreshed) {
      success.value =
        `Đã tạo phiên #${createdId}, ` +
        "nhưng chưa tải lại được danh sách."
    }
  } catch (err) {
    console.error("Lỗi tạo phiên:", err)

    if (err.response?.status === 422) {
      const errors = err.response.data?.errors
      formError.value =
        (errors ? Object.values(errors).flat()[0] : null) ||
        "Thông tin phiên chưa hợp lệ."
    } else {
      formError.value = getErrorMessage(
        err,
        "Không thể tạo phiên giám sát."
      )
    }
  } finally {
    saving.value = false
  }
}

/* BẮT ĐẦU PHIÊN: dùng API Laravel sẵn có. */
async function batDauPhien(phien) {
  if (!phien?.id || getStatus(phien).value !== "chua_bat_dau" || startingId.value !== null) return

  startingId.value = phien.id
  error.value = ""
  success.value = ""

  try {
    const response = await api.post(
      `/giang-vien/phien-giam-sat/${encodeURIComponent(phien.id)}/bat-dau`
    )

    if (response.data?.status === false || response.data?.success === false) {
      throw new Error(response.data?.message || "Không thể bắt đầu phiên.")
    }

    success.value = `Đã gửi yêu cầu bắt đầu phiên #${phien.id}.`
    await fetchData()
  } catch (err) {
    console.error("Lỗi bắt đầu phiên giám sát:", err)
    error.value = err.response?.data?.message || getErrorMessage(err, "Không thể bắt đầu phiên.")
  } finally {
    startingId.value = null
  }
}

/* XÁC NHẬN KẾT THÚC */
function openEndConfirm(phien) {
  if (!canEnd(phien) || endingId.value !== null) return

  confirmSession.value = phien
}

function closeEndConfirm() {
  if (endingId.value !== null) return
  confirmSession.value = null
}

/* KẾT THÚC PHIÊN */
async function ketThucPhien() {
  const phien = confirmSession.value

  if (!phien || !canEnd(phien) || endingId.value !== null) {
    return
  }

  endingId.value = phien.id
  error.value = ""
  success.value = ""

  try {
    const response = await api.put(
      `/giang-vien/phien-giam-sat/${encodeURIComponent(phien.id)}/ket-thuc`
    )

    if (
      String(response.data?.data?.trangThai ?? "")
        .toUpperCase() !== "DA_KET_THUC"
    ) {
      throw new Error(
        "API chưa xác nhận trạng thái đã kết thúc."
      )
    }

    confirmSession.value = null
    success.value =
      `Đã kết thúc phiên #${phien.id} thành công.`

    const refreshed = await fetchData()

    if (!refreshed) {
      success.value =
        `Đã kết thúc phiên #${phien.id}, ` +
        "nhưng chưa tải lại được danh sách."
    }
  } catch (err) {
    console.error("Lỗi kết thúc phiên:", err)

    error.value = getErrorMessage(
      err,
      "Không thể kết thúc phiên giám sát."
    )
  } finally {
    endingId.value = null
  }
}

onMounted(() => {
  fetchData()
  fetchLopHoc()
  fetchBaiTap()
  // Danh sách tự cập nhật để phiên đến giờ chuyển trạng thái.
  refreshTimer = setInterval(() => {
    if (!loading.value && !showCreateForm.value && !confirmSession.value) {
      fetchData()
    }
  }, 15000)
})

onBeforeUnmount(() => {
  clearInterval(refreshTimer)
  sessionRequestId++
  classRequestId++
  examRequestId++
})
</script>

<style scoped>
.monitor-page {
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

.heading h1 {
  margin: 8px 0;
  color: #172033;
  font-size: 28px;
  font-weight: 800;
}

.heading p {
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
.btn-secondary,
.btn-danger {
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

.btn-secondary {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
}

.btn-danger {
  background: #dc2626;
  border: 1px solid #dc2626;
  color: #fff;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
}

button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.plus {
  font-size: 18px;
  line-height: 1;
}

.spinning {
  display: inline-block;
  animation: spin 0.8s linear infinite;
}

/* STATS */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
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
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.stat-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 47px;
  height: 47px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 900;
}

.stat-info {
  display: flex;
  min-width: 0;
  flex-direction: column;
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

/* PANEL */
.session-panel {
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.panel-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
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
  padding: 12px 0;
  border: none;
  outline: none;
  background: transparent;
  color: #172033;
  font-size: 12px;
}

.filter-select {
  min-width: 160px;
  max-width: 230px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #334155;
  font-size: 12px;
  outline: none;
}

/* SESSION CARDS */
.session-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 17px;
  padding: 0 22px 22px;
}

.session-card {
  min-width: 0;
  display: flex;
  flex-direction: column;
  padding: 19px;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: #fff;
  transition: 0.2s ease;
}

.session-card:hover {
  transform: translateY(-3px);
  border-color: #bfdbfe;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
}

.session-icon {
  width: 44px;
  height: 44px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 900;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
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

.session-info {
  flex: 1;
  margin-top: 17px;
}

.session-code {
  color: #2563eb;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.session-info h3 {
  margin: 9px 0;
  color: #172033;
  font-size: 16px;
  font-weight: 800;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.session-info p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
  line-height: 1.6;
}

.card-divider {
  height: 1px;
  margin: 19px 0 15px;
  background: #e5e7eb;
}

.session-details {
  display: flex;
  flex-direction: column;
  gap: 13px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  font-size: 11px;
}

.detail-row span {
  color: #64748b;
}

.detail-row strong {
  color: #334155;
  text-align: right;
  font-weight: 700;
}

.card-actions {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 9px;
  margin-top: 19px;
}

.view-btn {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding: 11px 12px;
  border: none;
  border-radius: 9px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.view-btn:hover {
  background: #dbeafe;
}

.start-btn {
  padding: 11px 14px;
  border: 1px solid #86efac;
  border-radius: 10px;
  background: #dcfce7;
  color: #166534;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}
.start-btn:disabled { opacity: .5; cursor: not-allowed; }
.start-btn:hover:not(:disabled) { background: #bbf7d0; }

.end-btn {
  padding: 11px 13px;
  border: 1px solid #fecaca;
  border-radius: 9px;
  background: #fff;
  color: #dc2626;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.end-btn:hover:not(:disabled) {
  background: #fef2f2;
}

/* MESSAGES */
.notice {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin: 0 22px 20px;
  padding: 15px;
  border-radius: 10px;
  font-size: 12px;
}

.notice button {
  border: none;
  background: transparent;
  color: inherit;
  font-weight: 800;
  cursor: pointer;
}

.error-notice {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}

.success-notice {
  background: #f0fdf4;
  color: #15803d;
  border: 1px solid #bbf7d0;
}

/* EMPTY STATE */
.empty-state {
  min-height: 290px;
  padding: 35px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 12px;
  text-align: center;
}

.empty-icon {
  font-size: 35px;
}

.empty-state strong {
  font-size: 14px;
}

.empty-state p {
  max-width: 420px;
  color: #64748b;
  font-size: 11px;
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
  margin-top: 18px;
  padding: 15px;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  background: #eff6ff;
}

.note-symbol {
  width: 23px;
  height: 23px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #2563eb;
  color: #fff;
  font-size: 12px;
  font-weight: 800;
}

.info-note p {
  margin: 2px 0 0;
  color: #475569;
  font-size: 11px;
  line-height: 1.7;
}

/* MODALS */
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

.session-modal {
  width: 100%;
  max-width: 570px;
  max-height: 92vh;
  overflow-y: auto;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 20px 70px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  padding: 24px 26px 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 8px 0;
  font-size: 21px;
  font-weight: 800;
}

.modal-header p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
}

.close-btn {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 9px;
  background: #f1f5f9;
  color: #475569;
  font-size: 23px;
  cursor: pointer;
}

.modal-body {
  padding: 24px 26px;
}

.form-field {
  margin-bottom: 19px;
}

.form-field label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 700;
}

.form-field input,
.form-field select {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #172033;
  font: inherit;
  font-size: 12px;
  outline: none;
}

.form-field input:focus,
.form-field select:focus {
  border-color: #60a5fa;
}

.form-field small {
  display: block;
  margin-top: 8px;
  color: #94a3b8;
  font-size: 10px;
  line-height: 1.6;
}

.form-field .field-error {
  color: #dc2626;
}

.field-error button {
  border: none;
  background: transparent;
  color: #2563eb;
  font-weight: 700;
  cursor: pointer;
}

.form-error {
  padding: 12px;
  border-radius: 10px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 12px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 18px 26px;
  border-top: 1px solid #e5e7eb;
}

/* CONFIRM END */
.confirm-modal {
  width: 100%;
  max-width: 420px;
  padding: 26px;
  box-sizing: border-box;
  border-radius: 18px;
  background: #fff;
  text-align: center;
  box-shadow: 0 20px 70px rgba(0, 0, 0, 0.2);
}

.confirm-icon {
  width: 54px;
  height: 54px;
  margin: 0 auto 16px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #fef2f2;
  color: #dc2626;
  font-size: 25px;
  font-weight: 900;
}

.confirm-modal h2 {
  margin: 0 0 12px;
  font-size: 19px;
  font-weight: 800;
}

.confirm-modal p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.8;
}

.confirm-actions {
  display: flex;
  justify-content: center;
  gap: 10px;
  margin-top: 23px;
}

/* DARK MODE */
:global(html.dark-theme) .monitor-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .heading h1,
:global(html.dark-theme) .stat-info strong,
:global(html.dark-theme) .panel-heading h2,
:global(html.dark-theme) .session-info h3,
:global(html.dark-theme) .detail-row strong,
:global(html.dark-theme) .modal-header h2,
:global(html.dark-theme) .confirm-modal h2 {
  color: #fff;
}

:global(html.dark-theme) .stat-card,
:global(html.dark-theme) .session-panel,
:global(html.dark-theme) .session-card,
:global(html.dark-theme) .session-modal,
:global(html.dark-theme) .confirm-modal {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .search-box,
:global(html.dark-theme) .filter-select,
:global(html.dark-theme) .form-field input,
:global(html.dark-theme) .form-field select,
:global(html.dark-theme) .btn-secondary {
  background: #202327;
  border-color: rgba(255, 255, 255, 0.14);
  color: #fff;
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

:global(html.dark-theme) .info-note {
  background: rgba(37, 99, 235, 0.12);
  border-color: rgba(96, 165, 250, 0.3);
}

:global(html.dark-theme) .info-note p {
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

:global(html.dark-theme) .status-finished {
  background: #2b3038;
  color: #cbd5e1;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.exam-title { margin-top: 10px !important; color: #2563eb !important; font-weight: 700; }
/* RESPONSIVE */
@media (max-width: 1250px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .session-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 760px) {
  .monitor-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .heading h1 {
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

  .session-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 18px;
    padding-right: 18px;
  }

  .confirm-actions {
    flex-direction: column;
  }
}
</style>
