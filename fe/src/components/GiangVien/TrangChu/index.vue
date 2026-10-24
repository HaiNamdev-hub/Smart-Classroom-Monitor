<template>
  <div>
    <h1>Trang chủ</h1>
  </div>
</template>

<template>
  <div class="trang-chu-container p-3">

    <!-- TIÊU ĐỀ -->
    <div
      class="d-flex justify-content-between align-items-center
             flex-wrap gap-3 mb-4"
    >
      <div>
        <h3 class="fw-bold text-dark mb-1">
          🏠 Dashboard Tổng Quan Giảng Viên
        </h3>

        <p class="text-muted mb-0">
          Hệ thống giám sát Smart Classroom Monitor -
          Đại học Duy Tân
        </p>
      </div>

      <button
        type="button"
        class="btn btn-outline-primary btn-sm"
        :disabled="loading"
        @click="fetchData"
      >
        <span
          v-if="loading"
          class="spinner-border spinner-border-sm me-2"
        ></span>

        {{ loading ? 'Đang cập nhật...' : '↻ Cập nhật' }}
      </button>
    </div>

    <!-- THÔNG BÁO LỖI -->
    <div
      v-if="error"
      class="alert alert-danger"
      role="alert"
    >
      {{ error }}

      <div v-if="hasLoaded" class="small mt-2">
        Dữ liệu đang hiển thị là kết quả tải thành công
        gần nhất.
      </div>

      <button
        type="button"
        class="btn btn-outline-danger btn-sm mt-2"
        :disabled="loading"
        @click="fetchData"
      >
        Thử lại
      </button>
    </div>

    <!-- TRẠNG THÁI ĐANG TẢI -->
    <div
      v-if="loading && !hasLoaded"
      class="text-center py-5"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      ></div>

      <p class="text-muted mt-3">
        Đang tải thống kê từ Backend...
      </p>
    </div>

    <!-- CHƯA CÓ DỮ LIỆU -->
    <div
      v-else-if="!hasLoaded && !error"
      class="alert alert-info"
    >
      Chưa có dữ liệu thống kê.
    </div>

    <!-- THỐNG KÊ TỪ BACKEND -->
    <div
      v-if="hasLoaded"
      class="row g-3 mb-4"
    >

      <!-- SỐ LỚP -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stats-card border-0 shadow-sm">
          <div class="card-body p-3">
            <div class="text-muted small fw-bold">
              LỚP ĐANG PHỤ TRÁCH
            </div>

            <div class="fs-3 fw-bold text-primary mt-2">
              {{ formatCount(stats.tongSoLop) }}
            </div>

            <div class="small text-muted mt-2">
              Tổng số lớp học được phân công
            </div>
          </div>
        </div>
      </div>

      <!-- SINH VIÊN -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stats-card border-0 shadow-sm">
          <div class="card-body p-3">
            <div class="text-muted small fw-bold">
              SINH VIÊN ONLINE
            </div>

            <div
              class="fw-bold text-success mt-2"
              :class="stats.sinhVienOnline === null
                ? 'fs-5'
                : 'fs-3'"
            >
              {{ onlineText }}
            </div>

            <div class="small text-muted mt-2">
              Tổng sinh viên:
              {{ formatCount(stats.tongSinhVien) }}
            </div>
          </div>
        </div>
      </div>

      <!-- PHIÊN GIÁM SÁT -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stats-card border-0 shadow-sm">
          <div class="card-body p-3">
            <div class="text-muted small fw-bold">
              PHIÊN GIÁM SÁT
            </div>

            <div class="fs-3 fw-bold text-info mt-2">
              {{ formatCount(stats.phienDangChay) }}
            </div>

            <div class="small text-muted mt-2">
              Số phiên đang diễn ra
            </div>
          </div>
        </div>
      </div>

      <!-- CẢNH BÁO -->
      <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stats-card border-0 shadow-sm">
          <div class="card-body p-3">
            <div class="text-muted small fw-bold">
              CẢNH BÁO CHƯA XỬ LÝ
            </div>

            <div class="fs-3 fw-bold text-danger mt-2">
              {{ formatCount(stats.canhBaoMoi) }}
            </div>

            <div class="small text-muted mt-2">
              Cảnh báo cần kiểm tra
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- THÔNG BÁO THỐNG KÊ ONLINE -->
    <div
      v-if="hasLoaded && stats.sinhVienOnline === null"
      class="alert alert-info"
    >
      ℹ️ Backend hiện chưa cung cấp số lượng sinh viên
      trực tuyến. Tổng số sinh viên vẫn được lấy từ
      Database.
    </div>

    <!-- LẦN CẬP NHẬT -->
    <div
      v-if="hasLoaded && lastUpdatedAt"
      class="text-muted small mt-3"
    >
      Cập nhật lần cuối: {{ lastUpdatedAt }}
    </div>

  </div>
</template>

<script>
import giangVienService from '../../../services/giangVienService'

function normalizeCount(value) {
  if (
    value === null ||
    value === undefined ||
    value === ''
  ) {
    return null
  }

  const number = Number(value)

  if (!Number.isInteger(number) || number < 0) {
    return null
  }

  return number
}

export default {
  name: 'TrangChu',

  data() {
    return {
      stats: {
        tongSoLop: null,
        sinhVienOnline: null,
        tongSinhVien: null,
        phienDangChay: null,
        canhBaoMoi: null
      },

      loading: false,
      hasLoaded: false,
      error: '',
      lastUpdatedAt: '',
      requestId: 0
    }
  },

  computed: {
    onlineText() {
      const online = this.stats.sinhVienOnline
      const total = this.stats.tongSinhVien

      if (online === null) {
        return 'Chưa xác định'
      }

      return (
        this.formatCount(online) +
        ' / ' +
        this.formatCount(total)
      )
    }
  },

  mounted() {
    this.fetchData()
  },

  beforeUnmount() {
    this.requestId++
  },

  methods: {
    formatCount(value) {
      return value === null ? '—' : String(value)
    },

    async fetchData() {
      if (this.loading) return

      const currentRequest = ++this.requestId

      this.loading = true
      this.error = ''

      try {
        const response =
          await giangVienService.getDashboardStats()

        if (currentRequest !== this.requestId) {
          return
        }

        const body = response?.data
        const data = body?.data ?? body

        if (
          !data ||
          typeof data !== 'object' ||
          Array.isArray(data)
        ) {
          throw new Error('Dữ liệu thống kê không hợp lệ')
        }

        const requiredFields = [
          'tongSoLop',
          'tongSinhVien',
          'phienDangChay',
          'canhBaoMoi'
        ]

        if (
          !requiredFields.every(key =>
            Object.prototype.hasOwnProperty.call(data, key)
          )
        ) {
          throw new Error('API thiếu trường thống kê')
        }

        this.stats = {
          tongSoLop: normalizeCount(data.tongSoLop),
          sinhVienOnline:
            normalizeCount(data.sinhVienOnline),
          tongSinhVien: normalizeCount(data.tongSinhVien),
          phienDangChay:
            normalizeCount(data.phienDangChay),
          canhBaoMoi: normalizeCount(data.canhBaoMoi)
        }

        this.hasLoaded = true

        this.lastUpdatedAt =
          new Date().toLocaleString('vi-VN')

      } catch (err) {
        if (currentRequest !== this.requestId) {
          return
        }

        console.error('Lỗi tải Dashboard:', err)

        const status = err.response?.status

        if (status === 401) {
          this.error =
            'Phiên đăng nhập không hợp lệ hoặc đã hết hạn.'
        } else if (status === 403) {
          this.error =
            'Tài khoản không có quyền xem Dashboard.'
        } else if (status === 404) {
          this.error =
            'Không tìm thấy API thống kê Giảng viên.'
        } else if (status === 500) {
          this.error =
            'Backend gặp lỗi khi xử lý thống kê.'
        } else {
          this.error =
            'Không thể tải dữ liệu Dashboard. ' +
            'Vui lòng kiểm tra API và thử lại.'
        }

      } finally {
        if (currentRequest === this.requestId) {
          this.loading = false
        }
      }
    }
  }
}
</script>

<style scoped>
.trang-chu-container {
  min-height: 400px;
}

.stats-card {
  border-radius: 10px;
  min-height: 145px;
  height: 100%;
  transition: transform 0.2s ease,
              box-shadow 0.2s ease;
}

.stats-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
}

.stats-card .card-body {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

@media (max-width: 768px) {
  .trang-chu-container {
    padding: 12px !important;
  }

  h3 {
    font-size: 22px;
  }
}
</style>
