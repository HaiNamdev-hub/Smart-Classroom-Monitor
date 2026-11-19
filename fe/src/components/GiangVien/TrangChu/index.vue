
<template>
  <div class="dashboard">

    <!-- ==============================
         HEADER
    =============================== -->
    <div class="page-header">
      <div class="header-info">
        <span class="eyebrow">KHÔNG GIAN GIẢNG VIÊN</span>

        <h1>
          Xin chào, {{ userName }} 👋
        </h1>

        <p>
          Quản lý lớp học, theo dõi sinh viên và giám sát
          hoạt động học tập của bạn.
        </p>
      </div>

      <div class="header-actions">
        <div class="date-box">
          <span class="date-symbol">▦</span>
          {{ currentDate }}
        </div>

        <button
          type="button"
          class="refresh-btn"
          :disabled="loading"
          @click="fetchData"
        >
          <span :class="{ rotating: loading }">↻</span>
          {{ loading ? "Đang cập nhật..." : "Làm mới" }}
        </button>
      </div>
    </div>

    <!-- ==============================
         THÔNG BÁO LỖI
    =============================== -->
    <div v-if="error" class="error-box" role="alert">
      <div>
        <strong>Không thể cập nhật dữ liệu</strong>
        <p>{{ error }}</p>
        <small v-if="hasLoaded">
          Đang hiển thị dữ liệu của lần tải thành công gần nhất.
        </small>
      </div>

      <button
        type="button"
        :disabled="loading"
        @click="fetchData"
      >
        Thử lại
      </button>
    </div>

    <!-- ==============================
         THỐNG KÊ
    =============================== -->
    <div class="stats-grid">

      <!-- LỚP HỌC -->
      <router-link
        to="/giang-vien/lop-hoc"
        class="stat-card"
      >
        <div class="stat-top">
          <div class="stat-icon blue">LH</div>
          <span class="stat-arrow">↗</span>
        </div>

        <div class="stat-content">
          <span>Lớp đang phụ trách</span>

          <strong>
            {{ loading && !hasLoaded
              ? "..."
              : formatCount(stats.tongSoLop) }}
          </strong>

          <small>Tổng số lớp được phân công</small>
        </div>
      </router-link>

      <!-- SINH VIÊN -->
      <router-link
        to="/giang-vien/thanh-vien-lop"
        class="stat-card"
      >
        <div class="stat-top">
          <div class="stat-icon green">SV</div>
          <span class="stat-arrow">↗</span>
        </div>

        <div class="stat-content">
          <span>Tổng sinh viên</span>

          <strong>
            {{ loading && !hasLoaded
              ? "..."
              : formatCount(stats.tongSinhVien) }}
          </strong>

          <small>
            Đang trực tuyến:
            {{ formatCount(stats.sinhVienOnline) }}
          </small>
        </div>
      </router-link>

      <!-- PHIÊN GIÁM SÁT -->
      <router-link
        to="/giang-vien/phien-giam-sat"
        class="stat-card"
      >
        <div class="stat-top">
          <div class="stat-icon purple">GS</div>
          <span class="stat-arrow">↗</span>
        </div>

        <div class="stat-content">
          <span>Phiên giám sát</span>

          <strong>
            {{ loading && !hasLoaded
              ? "..."
              : formatCount(stats.phienDangChay) }}
          </strong>

          <small>Số phiên đang diễn ra</small>
        </div>
      </router-link>

      <!-- CẢNH BÁO -->
      <router-link
        to="/giang-vien/canh-bao"
        class="stat-card"
      >
        <div class="stat-top">
          <div class="stat-icon red">CB</div>
          <span class="stat-arrow">↗</span>
        </div>

        <div class="stat-content">
          <span>Cảnh báo chưa xử lý</span>

          <strong>
            {{ loading && !hasLoaded
              ? "..."
              : formatCount(stats.canhBaoMoi) }}
          </strong>

          <small>Cảnh báo cần được kiểm tra</small>
        </div>
      </router-link>

    </div>

    <!-- ==============================
         LOADING
    =============================== -->
    <div
      v-if="loading && !hasLoaded"
      class="loading-box"
      role="status"
    >
      <div class="spinner"></div>
      <span>Đang tải dữ liệu giảng viên...</span>
    </div>

    <template v-else>

      <!-- ==============================
           NỘI DUNG CHÍNH
      =============================== -->
      <div class="content-grid">

        <!-- TỔNG QUAN HOẠT ĐỘNG -->
        <section class="panel overview-panel">

          <div class="panel-header">
            <div>
              <h2>Tổng quan hoạt động</h2>
              <p>
                Theo dõi tình hình lớp học và giám sát
              </p>
            </div>

            <span class="panel-tag">
              Tổng quan
            </span>
          </div>

          <div v-if="!hasLoaded" class="empty">
            <div class="empty-icon">!</div>

            <strong>Chưa có dữ liệu thống kê</strong>

            <span>
              Nhấn Làm mới để tải dữ liệu từ hệ thống.
            </span>
          </div>

          <div v-else class="overview-list">

            <div class="overview-item">
              <div class="overview-icon blue">LH</div>

              <div class="overview-info">
                <strong>Lớp học phụ trách</strong>
                <span>
                  Số lớp hiện đang được phân công
                </span>
              </div>

              <strong class="overview-value">
                {{ formatCount(stats.tongSoLop) }}
              </strong>
            </div>

            <div class="overview-item">
              <div class="overview-icon green">SV</div>

              <div class="overview-info">
                <strong>Sinh viên trực tuyến</strong>
                <span>
                  Sinh viên đang hoạt động trên hệ thống
                </span>
              </div>

              <strong class="overview-value">
                {{ onlineText }}
              </strong>
            </div>

            <div class="overview-item">
              <div class="overview-icon purple">GS</div>

              <div class="overview-info">
                <strong>Phiên giám sát đang chạy</strong>
                <span>
                  Các phiên học đang được giám sát
                </span>
              </div>

              <strong class="overview-value">
                {{ formatCount(stats.phienDangChay) }}
              </strong>
            </div>

            <div class="overview-item">
              <div class="overview-icon red">CB</div>

              <div class="overview-info">
                <strong>Cảnh báo cần xử lý</strong>
                <span>
                  Các hoạt động cần được kiểm tra
                </span>
              </div>

              <strong class="overview-value">
                {{ formatCount(stats.canhBaoMoi) }}
              </strong>
            </div>

          </div>
        </section>

        <!-- TRUY CẬP NHANH -->
        <section class="panel quick-panel">

          <div class="panel-header">
            <div>
              <h2>Truy cập nhanh</h2>
              <p>
                Các chức năng thường xuyên sử dụng
              </p>
            </div>
          </div>

          <div class="quick-grid">

            <router-link
              to="/giang-vien/lop-hoc"
              class="quick-item"
            >
              <span class="quick-icon blue">LH</span>

              <div class="quick-info">
                <strong>Quản lý lớp học</strong>
                <small>Xem lớp phụ trách</small>
              </div>

              <span class="quick-arrow">›</span>
            </router-link>

            <router-link
              to="/giang-vien/bai-tap"
              class="quick-item"
            >
              <span class="quick-icon orange">BT</span>

              <div class="quick-info">
                <strong>Quản lý bài tập</strong>
                <small>Giao bài và deadline</small>
              </div>

              <span class="quick-arrow">›</span>
            </router-link>

            <router-link
              to="/giang-vien/bai-nop"
              class="quick-item"
            >
              <span class="quick-icon green">BN</span>

              <div class="quick-info">
                <strong>Quản lý bài nộp</strong>
                <small>Xem bài sinh viên</small>
              </div>

              <span class="quick-arrow">›</span>
            </router-link>

            <router-link
              to="/giang-vien/diem"
              class="quick-item"
            >
              <span class="quick-icon purple">Đ</span>

              <div class="quick-info">
                <strong>Quản lý điểm</strong>
                <small>Chấm và đánh giá</small>
              </div>

              <span class="quick-arrow">›</span>
            </router-link>

            <router-link
              to="/giang-vien/phien-giam-sat"
              class="quick-item"
            >
              <span class="quick-icon blue">GS</span>

              <div class="quick-info">
                <strong>Phiên giám sát</strong>
                <small>Theo dõi phòng máy</small>
              </div>

              <span class="quick-arrow">›</span>
            </router-link>

            <router-link
              to="/giang-vien/canh-bao"
              class="quick-item"
            >
              <span class="quick-icon red">CB</span>

              <div class="quick-info">
                <strong>Quản lý cảnh báo</strong>
                <small>Kiểm tra bất thường</small>
              </div>

              <span class="quick-arrow">›</span>
            </router-link>

          </div>
        </section>

      </div>

      <!-- ==============================
           THÔNG TIN BỔ SUNG
      =============================== -->
      <div class="bottom-grid">

        <!-- THÔNG TIN GIÁM SÁT -->
        <section class="info-panel">
          <div class="info-icon purple">GS</div>

          <div class="info-content">
            <strong>Giám sát hoạt động lớp học</strong>

            <p>
              Theo dõi các phiên giám sát, hoạt động
              của sinh viên và phát hiện những cảnh báo
              cần xử lý.
            </p>

            <router-link
              to="/giang-vien/phien-giam-sat"
              class="info-link"
            >
              Đi đến phiên giám sát →
            </router-link>
          </div>
        </section>

        <!-- THÔNG TIN BÀI TẬP -->
        <section class="info-panel">
          <div class="info-icon orange">BT</div>

          <div class="info-content">
            <strong>Quản lý bài tập hiệu quả</strong>

            <p>
              Tạo và quản lý bài tập, theo dõi hạn nộp,
              kiểm tra bài làm và đánh giá kết quả
              học tập của sinh viên.
            </p>

            <router-link
              to="/giang-vien/bai-tap"
              class="info-link"
            >
              Đi đến quản lý bài tập →
            </router-link>
          </div>
        </section>

      </div>

      <!-- ==============================
           FOOTER
      =============================== -->
      <div class="dashboard-footer">
        <span>
          Smart Classroom Monitor · Giảng viên
        </span>

        <span v-if="lastUpdatedAt">
          Cập nhật lần cuối: {{ lastUpdatedAt }}
        </span>
      </div>

    </template>

  </div>
</template>

<script setup>
import {
  computed,
  onMounted,
  onBeforeUnmount,
  ref,
} from "vue"

import api from "../../../services/api"
import echo from '../../../services/reverb'

/* ==============================
   USER
=============================== */
const userName = computed(() => {
  const raw =
    sessionStorage.getItem("auth_user") ||
    localStorage.getItem("auth_user")

  if (!raw) return "Giảng viên"

  try {
    return JSON.parse(raw)?.ho_ten || "Giảng viên"
  } catch {
    return "Giảng viên"
  }
})

/* ==============================
   NGÀY HIỆN TẠI
=============================== */
const currentDate = computed(() => {
  return new Intl.DateTimeFormat("vi-VN", {
    weekday: "long",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(new Date())
})

/* ==============================
   TRẠNG THÁI
=============================== */
const loading = ref(false)
const hasLoaded = ref(false)
const error = ref("")
const lastUpdatedAt = ref("")

/* ==============================
   THỐNG KÊ
=============================== */
const stats = ref({
  tongSoLop: null,
  tongSinhVien: null,
  sinhVienOnline: null,
  phienDangChay: null,
  canhBaoMoi: null,
})

function testPrivateChannel(phienId, sinhVienId) {
    const channelName =
        `exam-monitor.${phienId}.student.${sinhVienId}`

    console.log('Đang kiểm tra kênh:', channelName)

    const channel = echo.private(channelName)

    channel.subscribed(() => {
        console.log('✅ Private Channel đã xác thực thành công!')
    })

    channel.error((error) => {
        console.error('❌ Không thể tham gia Private Channel:', error)
    })

    return channel
}

let requestId = 0

/* ==============================
   FORMAT SỐ
=============================== */
function normalizeCount(value) {
  if (
    value === null ||
    value === undefined ||
    value === ""
  ) {
    return null
  }

  const number = Number(value)

  if (!Number.isInteger(number) || number < 0) {
    return null
  }

  return number
}

function formatCount(value) {
  return value === null
    ? "—"
    : Number(value).toLocaleString("vi-VN")
}

/* ==============================
   SINH VIÊN ONLINE
=============================== */
const onlineText = computed(() => {
  const online = stats.value.sinhVienOnline

  if (online === null) {
    return "Chưa xác định"
  }

  return formatCount(online)
})

/* ==============================
   GỌI API
=============================== */
async function fetchData() {
  if (loading.value) return

  const currentRequest = ++requestId

  loading.value = true
  error.value = ""

  try {
    const response = await api.get(
      "/giang-vien/dashboard/stats"
    )

    if (currentRequest !== requestId) return

    const body = response.data

    if (body?.status === false) {
      throw new Error(
        body.message || "API trả về lỗi"
      )
    }

    const data = body?.data ?? body

    if (
      !data ||
      typeof data !== "object" ||
      Array.isArray(data)
    ) {
      throw new Error("Dữ liệu thống kê không hợp lệ")
    }

    const requiredFields = [
      "tongSoLop",
      "tongSinhVien",
      "phienDangChay",
      "canhBaoMoi",
    ]

    const missingFields = requiredFields.filter(
      key =>
        !Object.prototype.hasOwnProperty.call(data, key)
    )

    if (missingFields.length > 0) {
      throw new Error(
        "API thiếu trường: " +
        missingFields.join(", ")
      )
    }

    stats.value = {
      tongSoLop: normalizeCount(data.tongSoLop),
      tongSinhVien: normalizeCount(data.tongSinhVien),
      sinhVienOnline: normalizeCount(data.sinhVienOnline),
      phienDangChay: normalizeCount(data.phienDangChay),
      canhBaoMoi: normalizeCount(data.canhBaoMoi),
    }

    hasLoaded.value = true

    lastUpdatedAt.value =
      new Date().toLocaleString("vi-VN")

  } catch (err) {
    if (currentRequest !== requestId) return

    console.error(
      "Lỗi tải Dashboard Giảng viên:",
      err.response?.data || err
    )

    const status = err.response?.status

    if (status === 401) {
      error.value =
        "Phiên đăng nhập không hợp lệ hoặc đã hết hạn."
    } else if (status === 403) {
      error.value =
        "Bạn không có quyền xem Dashboard."
    } else if (status === 404) {
      error.value =
        "Không tìm thấy API thống kê Giảng viên."
    } else if (status >= 500) {
      error.value =
        "Backend gặp lỗi khi xử lý thống kê."
    } else {
      error.value =
        err.message ||
        "Không thể tải dữ liệu Dashboard."
    }
  } finally {
    if (currentRequest === requestId) {
      loading.value = false
    }
  }
}

/* ==============================
   LIFECYCLE
=============================== */
onMounted(() => {
  fetchData()
})

onBeforeUnmount(() => {
  requestId++
})
</script>

<style scoped>
/* ==============================
   DASHBOARD
=============================== */
.dashboard {
  width: 100%;
  min-height: calc(100vh - 65px);
  box-sizing: border-box;
  padding: 27px;
  background: #f5f7fb;
  color: #172033;
  font-family:
    Inter,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

/* ==============================
   HEADER
=============================== */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 25px;
}

.eyebrow {
  color: #2563eb;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 1px;
}

.page-header h1 {
  margin: 7px 0 6px;
  color: #172033;
  font-size: 29px;
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
  flex-shrink: 0;
}

.date-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 11px;
  background: #fff;
  color: #64748b;
  font-size: 11px;
  font-weight: 600;
}

.date-symbol {
  color: #2563eb;
  font-size: 16px;
}

.refresh-btn {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 11px 15px;
  border: none;
  border-radius: 11px;
  background: #2563eb;
  color: white;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  transition: 0.2s;
}

.refresh-btn:hover:not(:disabled) {
  background: #1d4ed8;
}

.refresh-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.refresh-btn span {
  font-size: 17px;
}

.rotating {
  animation: spin 0.8s linear infinite;
}

/* ==============================
   STATS
=============================== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 15px;
  margin-bottom: 20px;
}

.stat-card {
  min-width: 0;
  min-height: 162px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 18px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
  color: inherit;
  text-decoration: none;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  transition: 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-3px);
  border-color: #bfdbfe;
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
}

.stat-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.stat-icon,
.overview-icon,
.quick-icon,
.info-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-weight: 900;
}

.stat-icon {
  width: 45px;
  height: 45px;
  border-radius: 12px;
  font-size: 11px;
}

.stat-arrow {
  color: #94a3b8;
  font-size: 18px;
}

.stat-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-top: 15px;
}

.stat-content > span {
  font-size: 11px;
  color: #64748b;
  font-weight: 600;
}

.stat-content strong {
  color: #172033;
  font-size: 27px;
  font-weight: 800;
}

.stat-content small {
  color: #94a3b8;
  font-size: 10px;
}

/* ==============================
   COLORS
=============================== */
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

.red {
  background: #fef2f2;
  color: #dc2626;
}

/* ==============================
   PANELS
=============================== */
.content-grid {
  display: grid;
  grid-template-columns:
    minmax(0, 1.15fr)
    minmax(0, 1fr);
  gap: 18px;
}

.panel {
  min-width: 0;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.panel-header h2 {
  margin: 0;
  font-size: 15px;
  font-weight: 800;
}

.panel-header p {
  margin: 5px 0 0;
  font-size: 10px;
  color: #64748b;
}

.panel-tag {
  padding: 6px 10px;
  background: #eff6ff;
  border-radius: 999px;
  color: #2563eb;
  font-size: 10px;
  font-weight: 700;
}

/* ==============================
   OVERVIEW
=============================== */
.overview-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 15px;
}

.overview-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 1px solid #f1f5f9;
  border-radius: 12px;
}

.overview-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  font-size: 10px;
}

.overview-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.overview-info strong {
  color: #172033;
  font-size: 11px;
}

.overview-info span {
  color: #94a3b8;
  font-size: 10px;
}

.overview-value {
  color: #172033;
  font-size: 16px;
  font-weight: 800;
  text-align: right;
}

/* ==============================
   QUICK LINKS
=============================== */
.quick-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 15px;
}

.quick-item {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 11px;
  background: #fff;
  color: inherit;
  text-decoration: none;
  transition: 0.2s;
}

.quick-item:hover {
  transform: translateY(-2px);
  background: #f8fafc;
  border-color: #bfdbfe;
}

.quick-icon {
  width: 37px;
  height: 37px;
  border-radius: 9px;
  font-size: 10px;
}

.quick-info {
  display: flex;
  flex: 1;
  min-width: 0;
  flex-direction: column;
  gap: 4px;
}

.quick-info strong {
  font-size: 10px;
  color: #172033;
}

.quick-info small {
  font-size: 9px;
  color: #94a3b8;
}

.quick-arrow {
  color: #94a3b8;
  font-size: 20px;
}

/* ==============================
   BOTTOM INFORMATION
=============================== */
.bottom-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
  margin-top: 20px;
}

.info-panel {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.info-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  font-size: 11px;
}

.info-content {
  flex: 1;
  min-width: 0;
}

.info-content strong {
  color: #172033;
  font-size: 13px;
}

.info-content p {
  margin: 8px 0 12px;
  color: #64748b;
  font-size: 11px;
  line-height: 1.8;
}

.info-link {
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
}

.info-link:hover {
  text-decoration: underline;
}

/* ==============================
   EMPTY / ERROR / LOADING
=============================== */
.empty {
  min-height: 250px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 8px;
  text-align: center;
}

.empty-icon {
  width: 45px;
  height: 45px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #f1f5f9;
  color: #64748b;
  font-weight: 800;
}

.empty strong {
  font-size: 12px;
}

.empty span {
  color: #94a3b8;
  font-size: 10px;
}

.error-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 15px;
  margin-bottom: 20px;
  border: 1px solid #fecaca;
  border-radius: 12px;
  background: #fef2f2;
  color: #b91c1c;
}

.error-box strong {
  font-size: 12px;
}

.error-box p,
.error-box small {
  display: block;
  margin: 5px 0 0;
  font-size: 11px;
}

.error-box button {
  border: 1px solid #fca5a5;
  border-radius: 8px;
  padding: 8px 12px;
  background: #fff;
  color: #b91c1c;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
  flex-shrink: 0;
}

.loading-box {
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #64748b;
  font-size: 12px;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ==============================
   FOOTER
=============================== */
.dashboard-footer {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 24px;
  padding: 15px 0;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 10px;
}

/* ==============================
   DARK MODE
=============================== */
:global(html.dark-theme) .dashboard {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .stat-content strong,
:global(html.dark-theme) .panel-header h2,
:global(html.dark-theme) .overview-info strong,
:global(html.dark-theme) .overview-value,
:global(html.dark-theme) .quick-info strong,
:global(html.dark-theme) .info-content strong {
  color: #fff;
}

:global(html.dark-theme) .page-header p,
:global(html.dark-theme) .stat-content > span,
:global(html.dark-theme) .panel-header p,
:global(html.dark-theme) .info-content p {
  color: #94a3b8;
}

:global(html.dark-theme) .eyebrow,
:global(html.dark-theme) .info-link {
  color: #60a5fa;
}

:global(html.dark-theme) .date-box,
:global(html.dark-theme) .stat-card,
:global(html.dark-theme) .panel,
:global(html.dark-theme) .info-panel,
:global(html.dark-theme) .quick-item {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
  color: #fff;
  box-shadow: none;
}

:global(html.dark-theme) .overview-item {
  border-color: rgba(255, 255, 255, 0.08);
}

:global(html.dark-theme) .panel-header,
:global(html.dark-theme) .dashboard-footer {
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .quick-item:hover {
  background: #202327;
  border-color: rgba(96, 165, 250, 0.4);
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

:global(html.dark-theme) .red {
  background: rgba(220, 38, 38, 0.15);
  color: #f87171;
}

:global(html.dark-theme) .panel-tag {
  background: rgba(37, 99, 235, 0.15);
  color: #60a5fa;
}

:global(html.dark-theme) .empty-icon {
  background: #202327;
  color: #94a3b8;
}

/* ==============================
   RESPONSIVE
=============================== */
@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 950px) {
  .content-grid,
  .bottom-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 650px) {
  .dashboard {
    padding: 15px;
  }

  .page-header {
    flex-direction: column;
  }

  .page-header h1 {
    font-size: 24px;
  }

  .header-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .date-box {
    flex: 1;
  }

  .quick-grid {
    grid-template-columns: 1fr;
  }

  .error-box {
    align-items: flex-start;
    flex-direction: column;
  }
}

@media (max-width: 430px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    min-height: 135px;
  }
}
</style>
