<template>
  <div class="alert-page">
    <!-- HEADER -->
    <header class="page-header">
      <div>
        <span class="eyebrow">GIÁM SÁT VÀ AN TOÀN LỚP HỌC</span>
        <h1>🚨 Cảnh báo vi phạm</h1>
        <p>
          Theo dõi hành vi bất thường và quản lý cảnh báo
          trong các phiên giám sát lớp học.
        </p>
      </div>

      <div class="header-actions">
        <button type="button" class="btn-outline" :disabled="isBusy" @click="fetchData">
          <span :class="{ spinning: loading }">↻</span>
          {{ loading ? "Đang tải..." : "Làm mới" }}
        </button>

        <button type="button" class="btn-primary" :disabled="isBusy || pendingVisible.length === 0 || !!error"
          @click="openBulkConfirm">
          ✓ Xử lý đang hiển thị
          <span v-if="pendingVisible.length">
            ({{ pendingVisible.length }})
          </span>
        </button>
      </div>
    </header>

    <!-- THỐNG KÊ -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon blue">CB</div>
        <div class="stat-content">
          <span>Tổng cảnh báo</span>
          <strong>{{ statValue(alerts.length) }}</strong>
          <small>Cảnh báo phiên và camera đã tải</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon red">!</div>
        <div class="stat-content">
          <span>Chưa xử lý</span>
          <strong>{{ statValue(soChuaXuLy) }}</strong>
          <small>Cảnh báo cần xem xét</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">✓</div>
        <div class="stat-content">
          <span>Đã xử lý</span>
          <strong>{{ statValue(soDaXuLy) }}</strong>
          <small>Đã được đánh dấu hoàn tất</small>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">SV</div>
        <div class="stat-content">
          <span>Sinh viên có cảnh báo</span>
          <strong>{{ soSinhVienText }}</strong>
          <small>Đếm theo ID hoặc MSSV</small>
        </div>
      </div>
    </div>

    <div class="camera-summary" role="status">
      <strong>📷 Cảnh báo camera: {{ soCanhBaoCamera }}</strong>
      <strong>📱 Điện thoại: {{ soCanhBaoDienThoai }}</strong>
      <strong>🔒 Bài thi đã khóa: {{ soBaiThiBiKhoa }}</strong>
      <span>Phát hiện điện thoại 3 lần, camera khác 5 lần hoặc rời tab 3 lần sẽ khóa bài. Các cảnh báo AI cần được giảng
        viên xem xét.</span>
    </div>

    <!-- PANEL DANH SÁCH -->
    <section class="alerts-panel">
      <div class="panel-heading">
        <div>
          <h2>Danh sách cảnh báo</h2>
          <p>
            Theo dõi và xử lý cảnh báo phát sinh
            trong các lớp được phân công.
          </p>
        </div>

        <span class="count-label">
          {{ filteredAlerts.length }} cảnh báo
        </span>
      </div>

      <!-- FILTER -->
      <div class="toolbar">
        <div class="search-box">
          <span class="search-icon">⌕</span>
          <input v-model="filters.search" type="search" placeholder="Tìm sinh viên, MSSV, nội dung..."
            aria-label="Tìm cảnh báo" />
        </div>

        <select v-model="filters.loai" class="filter-select" aria-label="Lọc loại vi phạm">
          <option value="ALL">Tất cả loại vi phạm</option>
          <option value="ung_dung_khong_cho_phep">
            Ứng dụng không cho phép
          </option>
          <option value="website_khong_cho_phep">
            Website không cho phép
          </option>
          <option value="copy_paste">Copy / Paste</option>
          <option value="roi_phien">Rời phiên</option>
          <option value="mat_ket_noi">Mất kết nối</option>
          <option value="khong_co_khuon_mat">Không phát hiện khuôn mặt</option>
          <option value="nhieu_khuon_mat">Nhiều khuôn mặt</option>
          <option value="camera_ngat">Camera bị ngắt</option>
          <option value="phat_hien_dien_thoai">Phát hiện điện thoại</option>
        </select>

        <select v-model="filters.trangThai" class="filter-select" aria-label="Lọc trạng thái">
          <option value="ALL">Tất cả trạng thái</option>
          <option value="CHUA_XU_LY">Chưa xử lý</option>
          <option value="DA_XU_LY">Đã xử lý</option>
          <option value="CAN_XEM_XET">Camera · Cần xem xét</option>
        </select>

        <button v-if="hasActiveFilters" type="button" class="clear-btn" @click="clearFilters">
          Xóa bộ lọc
        </button>
      </div>

      <!-- MESSAGES -->
      <div v-if="error" class="notice notice-error" role="alert">
        <div>
          <strong>Không thể hoàn thành thao tác</strong>
          <p>{{ error }}</p>
        </div>
        <button type="button" @click="fetchData">
          Thử tải lại
        </button>
      </div>

      <div v-if="success" class="notice notice-success" role="status">
        <span>✓ {{ success }}</span>
        <button type="button" aria-label="Đóng thông báo" @click="success = ''">
          ×
        </button>
      </div>

      <!-- LOADING -->
      <div v-if="loading && !hasLoaded" class="state-box" role="status">
        <div class="spinner"></div>
        <strong>Đang tải cảnh báo...</strong>
        <p>Hệ thống đang lấy dữ liệu từ máy chủ.</p>
      </div>

      <!-- EMPTY -->
      <div v-else-if="hasLoaded && alerts.length === 0" class="state-box">
        <div class="state-icon">🛡️</div>
        <strong>Chưa có cảnh báo vi phạm</strong>
        <p>
          Không có cảnh báo nào trong danh sách
          được Backend trả về.
        </p>
      </div>

      <div v-else-if="hasLoaded && filteredAlerts.length === 0" class="state-box">
        <div class="state-icon">⌕</div>
        <strong>Không tìm thấy cảnh báo phù hợp</strong>
        <p>Hãy thay đổi từ khóa hoặc bộ lọc.</p>
        <button type="button" class="btn-outline" @click="clearFilters">
          Xóa bộ lọc
        </button>
      </div>

      <!-- TABLE -->
      <div v-else-if="hasLoaded" class="table-scroll">
        <table class="alerts-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>SINH VIÊN</th>
              <th>LOẠI VI PHẠM</th>
              <th>THỜI GIAN</th>
              <th>KHÓA BÀI THI</th>
              <th>TRẠNG THÁI</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(item, index) in filteredAlerts" :key="item.id" :class="{ 'pending-row': isPending(item) }">
              <td class="muted">{{ index + 1 }}</td>

              <td>
                <div class="student-cell">
                  <span class="avatar">
                    {{ initials(item.tenSinhVien) }}
                  </span>

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
                <div class="violation-cell">
                  <span class="violation-icon">
                    {{ typeInfo(item.loaiCanhBao).icon }}
                  </span>
                  <div>
                    <strong>
                      {{ typeInfo(item.loaiCanhBao).text }}
                    </strong>
                    <small v-if="item.nguon === 'camera'">📷 {{ item.tenBaiKiemTra || 'Giám sát bài kiểm tra' }}</small>
                    <small v-if="item.nguon === 'camera' && item.loaiCanhBao === 'phat_hien_dien_thoai'">
                      📱 {{ item.soLanDienThoai }}/3 lần phát hiện điện thoại
                    </small>
                    <small :title="item.chiTiet || ''">
                      {{ item.chiTiet || "Chưa có mô tả" }}
                    </small>
                  </div>
                </div>
              </td>

              <td class="time-cell">
                {{ formatDate(item.thoiGian) }}
              </td>

              <td>
                <template v-if="item.nguon === 'camera'">
                  <span class="status-pill" :class="item.biKhoa ? 'status-locked' : 'status-unknown'">
                    {{ item.biKhoa ? '🔒 Đã khóa bài' : 'Chưa khóa' }}
                  </span>
                  <small v-if="item.biKhoa" class="lock-reason">{{ lockReasonText(item.lyDoKhoa) }}</small>
                </template>
                <span v-else class="muted">—</span>
              </td>

              <td>
                <span class="status-pill" :class="statusInfo(item).className">
                  <span class="status-dot"></span>
                  {{ statusInfo(item).text }}
                </span>
              </td>

              <td>
                <div class="row-actions">
                  <button v-if="item.nguon === 'camera'" type="button" class="detail-btn"
                    :disabled="!item.imageAvailable" @click="openEvidence(item)">
                    Xem ảnh bằng chứng
                  </button>
                  <small v-if="item.nguon === 'camera'" class="evidence-status-label">
                    {{ evidenceStatusText(item) }}
                  </small>
                  <small v-else-if="item.loaiCanhBao === 'roi_tab'" class="evidence-status-label">
                    Không có ảnh: bài thi không yêu cầu chia sẻ màn hình.
                  </small>
                  <button type="button" class="detail-btn" @click="openDetail(item)">
                    Chi tiết
                  </button>

                  <button v-if="isPending(item)" type="button" class="process-btn" :disabled="isBusy || item.id == null"
                    @click="openSingleConfirm(item)">
                    {{
                      processingId === item.id
                        ? "Đang xử lý..."
                        : "Xử lý"
                    }}
                  </button>

                  <span v-else-if="item.nguon === 'camera'" class="camera-view-only">
                    Cần xem xét
                  </span>
                  <span v-else class="processed-label">
                    Hoàn tất
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer class="panel-footer">
        <span>
          Hiển thị {{ filteredAlerts.length }}
          / {{ alerts.length }} cảnh báo
        </span>
        <span v-if="lastUpdatedAt">
          Cập nhật lần cuối: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>

    <!-- GHI CHÚ -->
    <div class="info-note">
      <span class="info-symbol">i</span>
      <p>
        Cảnh báo camera được hệ thống ghi lại để giảng viên xem xét; hiện chưa có API
        đánh dấu đã xử lý dành cho loại này. Chức năng xử lý chỉ áp dụng cho cảnh báo phiên.
        Cảnh báo AI không phải bằng chứng kết luận gian lận.
      </p>
    </div>

    <!-- MODAL CHI TIẾT -->
    <div v-if="selectedAlert" class="modal-overlay" @click.self="closeDetail">
      <div class="detail-modal" role="dialog" aria-modal="true" aria-labelledby="detail-title">
        <header class="modal-header">
          <div>
            <span class="eyebrow">
              CẢNH BÁO #{{ selectedAlert.id }}
            </span>
            <h2 id="detail-title">Chi tiết vi phạm</h2>
          </div>
          <button type="button" class="close-btn" aria-label="Đóng" @click="closeDetail">
            ×
          </button>
        </header>

        <div class="modal-body">
          <div class="detail-summary">
            <span class="detail-type-icon">
              {{ typeInfo(selectedAlert.loaiCanhBao).icon }}
            </span>
            <div>
              <strong>
                {{ typeInfo(selectedAlert.loaiCanhBao).text }}
              </strong>
              <span class="status-pill" :class="statusInfo(selectedAlert).className">
                {{ statusInfo(selectedAlert).text }}
              </span>
            </div>
          </div>

          <div class="detail-grid">
            <div>
              <small>Sinh viên</small>
              <strong>
                {{ selectedAlert.tenSinhVien || "Chưa cập nhật" }}
              </strong>
            </div>
            <div>
              <small>MSSV</small>
              <strong>
                {{ selectedAlert.mssv || "Chưa cập nhật" }}
              </strong>
            </div>
            <div>
              <small>Thời gian</small>
              <strong>{{ formatDate(selectedAlert.thoiGian) }}</strong>
            </div>
            <div>
              <small>{{ selectedAlert.nguon === 'camera' ? 'Bài kiểm tra' : 'Phiên giám sát' }}</small>
              <strong>
                {{ selectedAlert.nguon === 'camera'
                  ? (selectedAlert.tenBaiKiemTra || `#${selectedAlert.baiTapId}`)
                  : (selectedAlert.phienId ? `#${selectedAlert.phienId}` : 'Chưa có dữ liệu') }}
              </strong>
            </div>
          </div>

          <div v-if="selectedAlert.nguon === 'camera'" class="camera-detail-stats">
            <div><small>📱 Điện thoại</small><strong>{{ selectedAlert.soLanDienThoai }}/3</strong></div>
            <div><small>📷 Camera khác</small><strong>{{ selectedAlert.soLanCamera }}/5</strong></div>
            <div><small>↗ Rời tab</small><strong>{{ selectedAlert.soLanViPham }}/3</strong></div>
            <div><small>Trạng thái bài thi</small><strong :class="{ 'text-locked': selectedAlert.biKhoa }">{{
              selectedAlert.biKhoa ? 'Đã khóa · ' + lockReasonText(selectedAlert.lyDoKhoa) : 'Chưa khóa' }}</strong>
            </div>
          </div>

          <div class="description-box">
            <small>Nội dung cảnh báo</small>
            <p>
              {{ selectedAlert.chiTiet || "Chưa có mô tả." }}
            </p>
          </div>
          <section v-if="selectedAlert.nguon === 'camera'" class="evidence-panel">
            <h3>Ảnh bằng chứng</h3>
            <p v-if="evidenceLoading">Đang tải ảnh bảo vệ bằng quyền Giảng viên...</p>
            <p v-else-if="evidenceError" role="alert">{{ evidenceError }}</p>
            <img v-else-if="evidenceObjectUrl" :src="evidenceObjectUrl" alt="Ảnh webcam tại thời điểm cảnh báo" />
            <p v-else>{{ evidenceStatusText(selectedAlert) }}</p>
          </section>
        </div>

        <footer class="modal-footer">
          <button type="button" class="btn-outline" @click="closeDetail">
            Đóng
          </button>
          <button v-if="isPending(selectedAlert)" type="button" class="btn-primary" :disabled="isBusy"
            @click="processFromDetail">
            ✓ Đánh dấu đã xử lý
          </button>
        </footer>
      </div>
    </div>

    <!-- XÁC NHẬN XỬ LÝ -->
    <div v-if="confirmAction" class="modal-overlay" @click.self="closeConfirm">
      <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div class="confirm-icon">✓</div>

        <h2 id="confirm-title">Xác nhận xử lý cảnh báo</h2>

        <p v-if="confirmAction.type === 'single'">
          Đánh dấu cảnh báo
          <strong>#{{ confirmAction.items[0].id }}</strong>
          là đã xử lý?
        </p>

        <p v-else>
          Đánh dấu
          <strong>{{ confirmAction.items.length }} cảnh báo</strong>
          chưa xử lý đang hiển thị là đã xử lý?
        </p>

        <div v-if="actionError" class="form-error">
          {{ actionError }}
        </div>

        <div class="confirm-actions">
          <button type="button" class="btn-outline" :disabled="isBusy" @click="closeConfirm">
            Hủy
          </button>

          <button type="button" class="btn-primary" :disabled="isBusy" @click="confirmProcess">
            {{
              isBusy
                ? "Đang xử lý..."
                : "Xác nhận"
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
import api from "../../../services/api"

const alerts = ref([])
const loading = ref(false)
const hasLoaded = ref(false)
const bulkProcessing = ref(false)
const processingId = ref(null)

const error = ref("")
const success = ref("")
const actionError = ref("")
const lastUpdatedAt = ref("")

const selectedAlert = ref(null)
const evidenceObjectUrl = ref("")
const evidenceLoading = ref(false)
const evidenceError = ref("")
let evidenceRequestId = 0
const confirmAction = ref(null)

const filters = ref({
  search: "",
  loai: "ALL",
  trangThai: "ALL",
})

let requestId = 0
let refreshTimer = null

/* API */
function extractList(response) {
  const body = response?.data

  if (body?.success === false || body?.status === false) {
    throw new Error(body.message || "API trả về lỗi")
  }

  const data = body?.data ?? body

  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data

  throw new Error("API không trả về danh sách hợp lệ")
}

function cameraDescription(type) {
  return {
    khong_co_khuon_mat: "Không phát hiện khuôn mặt liên tục trong 5 giây.",
    nhieu_khuon_mat: "Phát hiện nhiều khuôn mặt trong 3 giây.",
    camera_ngat: "Camera bị ngắt trong lúc làm bài.",
    phat_hien_dien_thoai: "AI phát hiện điện thoại trong khung hình khi làm bài kiểm tra.",
  }[type] || "Sự kiện cảnh báo từ camera khi thi."
}

/* CHUẨN HÓA CẢNH BÁO */

function normalizeAlert(item, nguon = "phien") {
  // 1. Xác định nguồn cảnh báo thực tế
  const laCamera = nguon === "camera"
  const laViPhamKiemTra = item.nguon === "kiem_tra"
  const laCanhBaoPhien = !laCamera && !laViPhamKiemTra

  // 2. Lấy trạng thái từ Backend
  const status =
    item.trangThai ??
    item.trang_thai ??
    (
      item.da_xu_ly === true || item.da_xu_ly === 1
        ? "DA_XU_LY"
        : item.da_xu_ly === false || item.da_xu_ly === 0
          ? "CHUA_XU_LY"
          : ""
    )

  // 3. Xác định ID gốc
  const rawId = String(item.id ?? "")

  // Backend có thể trả GS-16 hoặc KT-16
  const canhBaoId = item.canhBaoId ??
    (laCanhBaoPhien && /^GS-\d+$/.test(rawId)
      ? rawId.slice(3)
      : null)

  const viPhamId = item.viPhamId ??
    (laViPhamKiemTra && /^KT-\d+$/.test(rawId)
      ? rawId.slice(3)
      : null)

  // Chỉ cảnh báo phiên thật sự mới được xử lý
  const apiId = laCanhBaoPhien
    ? canhBaoId ??
    (/^\d+$/.test(rawId) ? rawId : null)
    : null

  // 4. Tạo ID hiển thị không trùng nhau
  let displayId

  if (laCamera) {
    displayId = `CAM-${rawId}`
  } else if (laViPhamKiemTra) {
    displayId = `KT-${viPhamId ?? rawId}`
  } else {
    displayId = `GS-${canhBaoId ?? rawId}`
  }

  // 5. Xác định loại cảnh báo
  const loaiCanhBao =
    item.loaiCanhBao ??
    item.loai_canh_bao ??
    ""

  // 6. Chuẩn hóa trạng thái
  let trangThai

  if (laCamera || laViPhamKiemTra) {
    trangThai = "CAN_XEM_XET"
  } else {
    trangThai = String(status).toUpperCase()
  }

  // 7. Trả dữ liệu chuẩn hóa
  return {
    id: displayId,

    // ID dùng để gọi API xử lý
    apiId,

    // Nguồn cảnh báo
    nguon: laCamera
      ? "camera"
      : laViPhamKiemTra
        ? "kiem_tra"
        : "phien",

    // Thông tin bài kiểm tra
    baiTapId:
      item.baiTapId ??
      item.bai_tap_id ??
      null,

    tenBaiKiemTra:
      item.tenBaiKiemTra ??
      item.tieu_de ??
      "",

    tenLop:
      item.tenLop ??
      item.ten_lop ??
      "",

    // Thống kê vi phạm
    soLanDienThoai: Number(
      item.soLanDienThoai ?? 0
    ),

    soLanCamera: Number(
      item.soLanCamera ?? 0
    ),

    soLanViPham: Number(
      item.soLanViPham ?? 0
    ),

    // Trạng thái khóa bài
    biKhoa:
      item.biKhoa === true ||
      item.biKhoa === 1 ||
      item.biKhoa === "1",

    lyDoKhoa:
      item.lyDoKhoa ??
      null,

    // Thông tin sinh viên
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

    // Thông tin phiên
    phienId:
      item.phienId ??
      item.phien_id ??
      null,

    // Loại cảnh báo
    loaiCanhBao,

    tieuDe:
      item.tieuDe ??
      "",

    // Nội dung cảnh báo
    chiTiet:
      item.chiTiet ??
      item.noi_dung ??
      (
        laCamera
          ? cameraDescription(loaiCanhBao)
          : ""
      ),

    // Thời gian
    thoiGian:
      item.thoiGian ??
      item.thoi_gian ??
      item.created_at ??
      null,

    // Trạng thái xử lý
    trangThai,
  }
}


/* LOẠI VI PHẠM */

const alertTypes = {
  ung_dung_khong_cho_phep: {
    text: "Ứng dụng không cho phép",
    icon: "▣"
  },

  website_khong_cho_phep: {
    text: "Website không cho phép",
    icon: "◎"
  },

  copy_paste: {
    text: "Sao chép / Dán",
    icon: "▤"
  },

  roi_phien: {
    text: "Rời phiên giám sát",
    icon: "↗"
  },

  roi_tab: {
    text: "Rời tab khi làm bài kiểm tra",
    icon: "⚠️"
  },

  mat_ket_noi: {
    text: "Mất kết nối",
    icon: "⌁"
  },

  khong_co_khuon_mat: {
    text: "Không phát hiện khuôn mặt",
    icon: "📷"
  },

  nhieu_khuon_mat: {
    text: "Phát hiện nhiều khuôn mặt",
    icon: "👥"
  },

  camera_ngat: {
    text: "Camera bị ngắt",
    icon: "📹"
  },

  phat_hien_dien_thoai: {
    text: "Phát hiện điện thoại",
    icon: "📱"
  }
}


function typeInfo(value) {
  return alertTypes[value] || {
    text: "Chưa xác định",
    icon: "!",
  }
}

/* TRẠNG THÁI */
function statusCode(item) {
  return String(item?.trangThai || "").toUpperCase()
}

function isPending(item) {
  return item?.nguon === "phien" && statusCode(item) === "CHUA_XU_LY"
}

function statusInfo(item) {
  const status = statusCode(item)

  if (status === "CAN_XEM_XET") {
    return { text: "Cần xem xét", className: "status-review" }
  }

  if (status === "CHUA_XU_LY") {
    return {
      text: "Chưa xử lý",
      className: "status-pending",
    }
  }

  if (status === "DA_XU_LY") {
    return {
      text: "Đã xử lý",
      className: "status-complete",
    }
  }

  return {
    text: "Chưa xác định",
    className: "status-unknown",
  }
}

const isBusy = computed(
  () =>
    loading.value ||
    bulkProcessing.value ||
    processingId.value !== null
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

const filteredAlerts = computed(() => {
  const keyword = normalizeText(filters.value.search)

  return alerts.value.filter((item) => {
    const content = normalizeText(
      [
        item.tenSinhVien,
        item.mssv,
        item.tieuDe,
        item.tenBaiKiemTra,
        item.chiTiet,
        typeInfo(item.loaiCanhBao).text,
      ].join(" ")
    )

    const matchSearch = content.includes(keyword)

    const matchType =
      filters.value.loai === "ALL" ||
      item.loaiCanhBao === filters.value.loai

    const matchStatus =
      filters.value.trangThai === "ALL" ||
      statusCode(item) === filters.value.trangThai

    return matchSearch && matchType && matchStatus
  })
})

const hasActiveFilters = computed(
  () =>
    filters.value.search.trim() !== "" ||
    filters.value.loai !== "ALL" ||
    filters.value.trangThai !== "ALL"
)

function clearFilters() {
  filters.value = {
    search: "",
    loai: "ALL",
    trangThai: "ALL",
  }
}

/* THỐNG KÊ */
function statValue(value) {
  return hasLoaded.value
    ? Number(value).toLocaleString("vi-VN")
    : "—"
}

const soCanhBaoCamera = computed(() =>
  alerts.value.filter((item) => item.nguon === "camera").length
)

const soCanhBaoDienThoai = computed(() =>
  alerts.value.filter((item) => item.nguon === "camera" && item.loaiCanhBao === "phat_hien_dien_thoai").length
)

// Đếm số bài thi bị khóa theo cặp (bài tập, sinh viên), không theo số dòng cảnh báo.
const soBaiThiBiKhoa = computed(() => {
  const keys = new Set()
  for (const item of alerts.value) {
    if (item.nguon === "camera" && item.biKhoa && item.baiTapId != null && item.sinhVienId != null) {
      keys.add(`${item.baiTapId}:${item.sinhVienId}`)
    }
  }
  return keys.size
})

function lockReasonText(reason) {
  return {
    dien_thoai: "Phát hiện điện thoại 3 lần",
    camera: "Cảnh báo camera khác 5 lần",
    roi_tab: "Rời tab 3 lần",
  }[reason] || "Đạt ngưỡng vi phạm"
}

const soChuaXuLy = computed(() =>
  alerts.value.filter(isPending).length
)

const soDaXuLy = computed(() =>
  alerts.value.filter(
    (item) => statusCode(item) === "DA_XU_LY"
  ).length
)

const soSinhVienText = computed(() => {
  if (!hasLoaded.value) return "—"

  const identifiers = new Set()

  for (const item of alerts.value) {
    const id =
      item.sinhVienId != null
        ? `id:${item.sinhVienId}`
        : item.mssv
          ? `mssv:${item.mssv}`
          : null

    if (id) identifiers.add(id)
  }

  return identifiers.size > 0
    ? identifiers.size.toLocaleString("vi-VN")
    : alerts.value.length === 0 ? "0" : "—"
})

const pendingVisible = computed(() =>
  filteredAlerts.value.filter(
    (item) => isPending(item) && item.id != null
  )
)

/* THÔNG TIN HIỂN THỊ */
function initials(name) {
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

function getErrorMessage(err, fallback) {
  const status = err.response?.status

  if (status === 401) {
    return "Phiên đăng nhập đã hết hạn."
  }
  if (status === 403) {
    return "Bạn không có quyền xử lý cảnh báo."
  }
  if (status === 404) {
    return "Không tìm thấy cảnh báo hoặc API."
  }
  if (status === 422) {
    return "Dữ liệu gửi lên không hợp lệ."
  }
  if (status >= 500) {
    return "Máy chủ gặp lỗi khi xử lý dữ liệu."
  }

  return err.message || fallback
}

/* TẢI CẢNH BÁO TỪ 2 API. Nếu một API lỗi, vẫn hiển thị nguồn còn lại. */
async function fetchData() {
  if (isBusy.value) return
  const current = ++requestId
  loading.value = true
  error.value = ""

  try {
    const [gs, cam] = await Promise.allSettled([
      api.get("/giang-vien/canh-bao"),
      api.get("/giang-vien/canh-bao-camera"),
    ])

    if (current !== requestId) return

    const merged = []
    const failed = []

    for (const [result, source, label] of [
      [gs, "phien", "cảnh báo phiên"],
      [cam, "camera", "cảnh báo camera"],
    ]) {
      try {
        if (result.status !== "fulfilled") throw result.reason
        merged.push(...extractList(result.value).map(x => normalizeAlert(x, source)))
      } catch (err) {
        failed.push(`${label}: ${getErrorMessage(err, "Không tải được")}`)
      }
    }

    merged.sort((a, b) => {
      const da = Date.parse(String(a.thoiGian ?? "").replace(" ", "T")) || 0
      const db = Date.parse(String(b.thoiGian ?? "").replace(" ", "T")) || 0
      return db - da
    })

    if (failed.length === 2) {
      throw new Error(failed.join("; "))
    }

    alerts.value = merged
    hasLoaded.value = true
    lastUpdatedAt.value = new Date().toLocaleString("vi-VN")
    if (failed.length) error.value = `Chỉ tải được một nguồn: ${failed.join("; ")}`
  } catch (err) {
    if (current !== requestId) return
    error.value = getErrorMessage(err, "Không thể tải cảnh báo.")
    if (!hasLoaded.value) alerts.value = []
  } finally {
    if (current === requestId) loading.value = false
  }
}

/* XEM CHI TIẾT */
function openDetail(item) {
  clearEvidence()
  selectedAlert.value = item
}

function evidenceStatusText(item) {
  if (item.imageAvailable) return 'Ảnh bằng chứng đã lưu.'
  if (item.evidenceStatus === 'failed') return 'Upload ảnh thất bại.'
  if (item.evidenceStatus === 'pending') return 'Ảnh đang chờ upload.'
  if (item.evidenceStatus === 'expired') return 'Ảnh đã hết thời hạn lưu và được xóa.'
  if (item.loaiCanhBao === 'camera_ngat') return 'Camera đã ngắt; không có ảnh tại thời điểm này.'
  return 'Cảnh báo này chưa có ảnh bằng chứng.'
}

function clearEvidence() {
  evidenceRequestId++
  if (evidenceObjectUrl.value) URL.revokeObjectURL(evidenceObjectUrl.value)
  evidenceObjectUrl.value = ""
  evidenceError.value = ""
  evidenceLoading.value = false
}

function closeDetail() {
  clearEvidence()
  selectedAlert.value = null
}

async function openEvidence(item) {
  openDetail(item)
  if (!item.imageAvailable || !item.evidenceUrl) return
  const current = ++evidenceRequestId
  evidenceLoading.value = true
  try {
    const response = await api.get(item.evidenceUrl, { responseType: 'blob' })
    if (current !== evidenceRequestId) return
    evidenceObjectUrl.value = URL.createObjectURL(response.data)
  } catch (err) {
    if (current !== evidenceRequestId) return
    evidenceError.value = err.response?.status === 403
      ? 'Bạn không có quyền xem ảnh này.'
      : 'Không thể tải ảnh bằng chứng.'
  } finally {
    if (current === evidenceRequestId) evidenceLoading.value = false
  }
}

/* MỞ XÁC NHẬN */
function openSingleConfirm(item) {
  if (!isPending(item) || isBusy.value || item.id == null) {
    return
  }

  actionError.value = ""
  confirmAction.value = {
    type: "single",
    items: [item],
  }
}

function openBulkConfirm() {
  if (isBusy.value || pendingVisible.value.length === 0) {
    return
  }

  actionError.value = ""

  confirmAction.value = {
    type: "bulk",
    items: [...pendingVisible.value],
  }
}

function processFromDetail() {
  const item = selectedAlert.value
  if (!item) return

  selectedAlert.value = null
  openSingleConfirm(item)
}

function closeConfirm() {
  if (isBusy.value) return

  confirmAction.value = null
  actionError.value = ""
}

/* GỌI API XỬ LÝ */

async function sendProcessRequest(id) {
  const sourceAlert = alerts.value.find(
    item => item.id === id
  )

  if (!sourceAlert || sourceAlert.nguon !== "phien") {
    throw new Error("Cảnh báo không hợp lệ.")
  }

  // apiId là ID gốc Backend trả về.
  const apiId = sourceAlert.apiId

  if (apiId === null || apiId === undefined) {
    throw new Error("Không tìm thấy ID cảnh báo.")
  }

  const response = await api.put(
    `/giang-vien/canh-bao/${encodeURIComponent(apiId)}/xu-ly`
  )

  if (
    response.data?.success === false ||
    response.data?.status === false
  ) {
    throw new Error(
      response.data?.message ||
      "Không thể xử lý cảnh báo."
    )
  }

  alerts.value = alerts.value.map(item =>
    item.id === id
      ? { ...item, trangThai: "DA_XU_LY" }
      : item
  )
}


/* XỬ LÝ CẢNH BÁO */
async function confirmProcess() {
  const action = confirmAction.value

  if (!action || isBusy.value) return

  actionError.value = ""
  error.value = ""
  success.value = ""

  if (action.type === "single") {
    const item = action.items[0]
    processingId.value = item.id

    try {
      await sendProcessRequest(item.id)

      success.value =
        `Đã xử lý cảnh báo #${item.id} thành công.`

      confirmAction.value = null
    } catch (err) {
      console.error("Lỗi xử lý cảnh báo:", err)
      actionError.value = getErrorMessage(
        err,
        "Không thể xử lý cảnh báo."
      )
    } finally {
      processingId.value = null
    }

    return
  }

  // Backend chưa có API xử lý hàng loạt:
  // gửi từng request và thống kê kết quả.
  bulkProcessing.value = true

  let completed = 0
  let failed = 0

  try {
    for (const item of action.items) {
      try {
        await sendProcessRequest(item.id)
        completed++
      } catch (err) {
        console.error(
          `Không thể xử lý cảnh báo #${item.id}:`,
          err
        )
        failed++
      }
    }

    if (completed > 0) {
      success.value =
        `Đã xử lý ${completed}/${action.items.length} cảnh báo.`
    }

    if (failed > 0) {
      error.value =
        `${failed} cảnh báo xử lý thất bại. ` +
        "Bạn có thể thử lại các cảnh báo còn lại."
    }

    confirmAction.value = null
  } finally {
    bulkProcessing.value = false
  }
}

onMounted(() => {
  fetchData()
  refreshTimer = setInterval(() => {
    if (!confirmAction.value && !selectedAlert.value) fetchData()
  }, 10000)
})

onBeforeUnmount(() => {
  requestId++
  clearEvidence()
  if (refreshTimer) clearInterval(refreshTimer)
})
</script>

<style scoped>
.alert-page {
  min-height: calc(100vh - 80px);
  width: 100%;
  box-sizing: border-box;
  padding: 28px;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, system-ui, sans-serif;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 24px;
}

.eyebrow {
  display: block;
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
  flex-wrap: wrap;
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
  background: #2563eb;
  color: #fff;
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

/* CẢNH BÁO CAMERA */
.camera-summary {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  padding: 12px 16px;
  margin-bottom: 16px;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  background: #eff6ff;
  color: #1e40af;
  font-size: 12px;
}

.evidence-status-label {
  color: #64748b;
  font-size: 11px;
}

.evidence-panel {
  margin-top: 18px;
  padding: 16px;
  border: 1px solid #dbe3ef;
  border-radius: 12px;
  background: #f8fafc;
}

.evidence-panel h3 { margin: 0 0 10px; }
.evidence-panel img { display: block; max-width: 100%; max-height: 65vh; margin: 0 auto; border-radius: 8px; }

.camera-summary span {
  color: #475569;
}

.camera-view-only {
  color: #b45309;
  font-size: 11px;
  font-weight: 700;
}

.status-review {
  background: #fff7ed;
  color: #9a3412;
}

:global(html.dark-theme) .camera-summary {
  background: #1e293b;
  border-color: #334155;
  color: #bfdbfe;
}

:global(html.dark-theme) .camera-summary span {
  color: #cbd5e1;
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
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  border-radius: 12px;
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

.red {
  background: #fef2f2;
  color: #dc2626;
}

.green {
  background: #f0fdf4;
  color: #16a34a;
}

.purple {
  background: #faf5ff;
  color: #9333ea;
}

/* PANEL */
.alerts-panel {
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

/* FILTER */
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 11px;
  padding: 0 22px 21px;
}

.search-box {
  flex: 1;
  min-width: 220px;
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
  max-width: 240px;
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

.alerts-table {
  width: 100%;
  min-width: 950px;
  border-collapse: collapse;
  text-align: left;
}

.alerts-table thead {
  background: #f8fafc;
}

.alerts-table th {
  padding: 15px 18px;
  color: #64748b;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.4px;
  white-space: nowrap;
}

.alerts-table td {
  padding: 16px 18px;
  border-top: 1px solid #f1f5f9;
  font-size: 12px;
  vertical-align: middle;
}

.alerts-table tbody tr:hover {
  background: #f8fafc;
}

.pending-row td:first-child {
  border-left: 3px solid #f59e0b;
}

.muted,
.time-cell {
  color: #94a3b8;
  font-size: 11px;
}

.student-cell,
.violation-cell {
  display: flex;
  align-items: center;
  gap: 11px;
}

.avatar,
.violation-icon {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 800;
}

.violation-icon {
  background: #fff7ed;
  color: #ea580c;
  font-size: 19px;
}

.student-info,
.violation-cell>div {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.student-info strong,
.violation-cell strong {
  color: #172033;
  font-size: 12px;
}

.student-info small,
.violation-cell small {
  color: #94a3b8;
  font-size: 10px;
}

.violation-cell small {
  display: block;
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 8px 11px;
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

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-complete {
  background: #dcfce7;
  color: #15803d;
}

.status-unknown {
  background: #f1f5f9;
  color: #64748b;
}

.row-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.detail-btn,
.process-btn {
  padding: 9px 12px;
  border-radius: 9px;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.detail-btn {
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #475569;
}

.process-btn {
  border: 1px solid #bbf7d0;
  background: #f0fdf4;
  color: #15803d;
}

.process-btn:hover:not(:disabled) {
  background: #dcfce7;
}

.processed-label {
  color: #16a34a;
  font-size: 11px;
  font-weight: 700;
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

/* STATES */
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

.notice {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin: 0 22px 19px;
  padding: 14px;
  border: 1px solid transparent;
  border-radius: 10px;
}

.notice strong {
  font-size: 12px;
}

.notice p {
  margin: 5px 0 0;
  font-size: 11px;
}

.notice button {
  border: none;
  background: transparent;
  color: inherit;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.notice-error {
  background: #fef2f2;
  border-color: #fecaca;
  color: #b91c1c;
}

.notice-success {
  background: #f0fdf4;
  border-color: #bbf7d0;
  color: #15803d;
}

/* INFO */
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

.info-symbol {
  flex-shrink: 0;
  display: grid;
  place-items: center;
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

/* MODALS */
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
  max-width: 560px;
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
  gap: 15px;
  padding: 24px 26px 19px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 8px 0 0;
  color: #172033;
  font-size: 21px;
  font-weight: 800;
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

.detail-summary {
  display: flex;
  align-items: center;
  gap: 13px;
  margin-bottom: 22px;
}

.detail-type-icon {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #fff7ed;
  color: #ea580c;
  font-size: 22px;
}

.detail-summary>div {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 8px;
}

.detail-summary strong {
  font-size: 14px;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 15px;
}

.detail-grid>div {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.detail-grid small,
.description-box small {
  color: #94a3b8;
  font-size: 11px;
}

.detail-grid strong {
  font-size: 12px;
  overflow-wrap: anywhere;
}

.description-box {
  margin-top: 21px;
  padding: 15px;
  border: 1px solid #e5e7eb;
  border-radius: 11px;
  background: #f8fafc;
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
  gap: 10px;
  padding: 17px 26px;
  border-top: 1px solid #e5e7eb;
}

.confirm-modal {
  width: 100%;
  max-width: 420px;
  box-sizing: border-box;
  padding: 27px;
  border-radius: 18px;
  background: #fff;
  text-align: center;
}

.confirm-icon {
  display: grid;
  place-items: center;
  width: 55px;
  height: 55px;
  margin: 0 auto 16px;
  border-radius: 50%;
  background: #f0fdf4;
  color: #16a34a;
  font-size: 25px;
  font-weight: 900;
}

.confirm-modal h2 {
  font-size: 19px;
  font-weight: 800;
}

.confirm-modal p {
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

.form-error {
  padding: 12px;
  margin-top: 15px;
  border-radius: 9px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 11px;
}

/* DARK MODE */
:global(html.dark-theme) .alert-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .stat-content strong,
:global(html.dark-theme) .panel-heading h2,
:global(html.dark-theme) .student-info strong,
:global(html.dark-theme) .violation-cell strong,
:global(html.dark-theme) .modal-header h2,
:global(html.dark-theme) .confirm-modal h2 {
  color: #fff;
}

:global(html.dark-theme) .stat-card,
:global(html.dark-theme) .alerts-panel,
:global(html.dark-theme) .detail-modal,
:global(html.dark-theme) .confirm-modal {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .search-box,
:global(html.dark-theme) .filter-select,
:global(html.dark-theme) .btn-outline,
:global(html.dark-theme) .detail-btn,
:global(html.dark-theme) .description-box {
  background: #202327;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .search-box input {
  color: #fff;
}

:global(html.dark-theme) .alerts-table thead,
:global(html.dark-theme) .alerts-table tbody tr:hover {
  background: #202327;
}

:global(html.dark-theme) .alerts-table td,
:global(html.dark-theme) .panel-footer,
:global(html.dark-theme) .modal-header,
:global(html.dark-theme) .modal-footer {
  border-color: rgba(255, 255, 255, 0.12);
}

:global(html.dark-theme) .description-box p {
  color: #cbd5e1;
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

:global(html.dark-theme) .red {
  background: rgba(220, 38, 38, 0.15);
  color: #f87171;
}

:global(html.dark-theme) .green {
  background: rgba(22, 163, 74, 0.15);
  color: #4ade80;
}

:global(html.dark-theme) .purple {
  background: rgba(147, 51, 234, 0.15);
  color: #c084fc;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 750px) {
  .alert-page {
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

  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
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

  .confirm-actions {
    flex-direction: column;
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Trạng thái khóa bài kiểm tra và chi tiết giám sát */
.status-locked {
  background: #fee2e2;
  color: #b91c1c;
}

.lock-reason {
  display: block;
  margin-top: 6px;
  font-size: 10px;
  color: #b91c1c;
}

.camera-detail-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-top: 18px;
}

.camera-detail-stats>div {
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 7px;
  background: #f8fafc;
  border-radius: 10px;
}

.camera-detail-stats small {
  color: #64748b;
  font-size: 11px;
}

.camera-detail-stats strong {
  font-size: 13px;
  overflow-wrap: anywhere;
}

.text-locked {
  color: #dc2626;
}

:global(html.dark-theme) .camera-detail-stats>div {
  background: #202327;
}

@media (max-width: 480px) {
  .camera-detail-stats {
    grid-template-columns: 1fr;
  }
}
</style>
