<template>
  <div>
    <!-- �ang ph�t tri?n -->
  </div>
</template>

<template>
  <div class="hoat-dong-container p-3">

    <!-- Tiêu đề -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold text-dark mb-1">
          📋 Nhật Ký Hoạt Động Sinh Viên
        </h3>

        <p class="text-muted mb-0">
          Theo dõi các hoạt động và sự kiện của sinh viên.
        </p>
      </div>

      <button
        class="btn btn-outline-primary btn-sm"
        :disabled="loading"
        @click="fetchData"
      >
        <i class="bi bi-arrow-clockwise me-1"></i>
        {{ loading ? 'Đang tải...' : 'Làm mới' }}
      </button>
    </div>

    <!-- Bộ lọc -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">

          <!-- Loại hoạt động -->
          <div class="col-md-4">
            <label class="form-label fw-bold">
              Loại hoạt động
            </label>

            <select
              v-model="filters.loai"
              class="form-select"
            >
              <option value="ALL">
                Tất cả hoạt động
              </option>

              <option value="DANG_NHAP">
                Đăng nhập / Đăng xuất
              </option>

              <option value="MO_UNG_DUNG">
                Mở ứng dụng
              </option>

              <option value="NOP_BAI">
                Nộp bài tập
              </option>
            </select>
          </div>

          <!-- Tìm kiếm sinh viên -->
          <div class="col-md-8">
            <label class="form-label fw-bold">
              Tìm kiếm sinh viên
            </label>

            <div class="input-group">
              <span class="input-group-text bg-white">
                <i class="bi bi-search"></i>
              </span>

              <input
                v-model="filters.search"
                type="text"
                class="form-control"
                placeholder="Nhập tên sinh viên hoặc MSSV..."
              />

              <button
                v-if="filters.search"
                class="btn btn-outline-secondary"
                type="button"
                @click="filters.search = ''"
              >
                Xóa
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Thông báo lỗi -->
    <div
      v-if="error"
      class="alert alert-danger"
      role="alert"
    >
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ error }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div
        class="spinner-border text-primary"
        role="status"
      ></div>

      <p class="text-muted mt-3">
        Đang tải nhật ký hoạt động...
      </p>
    </div>

    <!-- Bảng nhật ký -->
    <div
      v-else-if="!error"
      class="card border-0 shadow-sm"
    >
      <div
        class="card-header bg-white py-3
               d-flex justify-content-between align-items-center"
      >
        <h6 class="fw-bold mb-0">
          <i class="bi bi-clock-history text-primary me-2"></i>
          Dòng Thời Gian Hoạt Động
        </h6>

        <span class="badge bg-primary">
          {{ filteredActivities.length }} bản ghi
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>STT</th>
              <th>Thời gian</th>
              <th>Sinh viên</th>
              <th>Máy trạm</th>
              <th>Hành động / Sự kiện</th>
              <th>Chi tiết mô tả</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(item, index) in filteredActivities"
              :key="item.id ?? index"
            >
              <td>{{ index + 1 }}</td>

              <!-- Thời gian -->
              <td class="text-muted small">
                {{ formatDate(item.thoiGian) }}
              </td>

              <!-- Sinh viên -->
              <td>
                <div class="fw-bold text-dark">
                  {{ item.tenSinhVien || 'Chưa cập nhật' }}
                </div>

                <div class="small text-muted">
                  {{ item.mssv || 'Chưa có MSSV' }}
                </div>
              </td>

              <!-- Máy trạm -->
              <td>
                <span class="badge bg-light text-dark border">
                  {{ item.tenMay || 'Chưa xác định' }}
                </span>
              </td>

              <!-- Hành động -->
              <td>
                <span
                  class="badge"
                  :class="getActivityStatus(item.hanhDong).className"
                >
                  {{ getActivityStatus(item.hanhDong).text }}
                </span>
              </td>

              <!-- Chi tiết -->
              <td class="text-muted small">
                {{ item.chiTiet || 'Không có mô tả' }}
              </td>
            </tr>

            <!-- Danh sách rỗng -->
            <tr v-if="filteredActivities.length === 0">
              <td
                colspan="6"
                class="text-center py-5"
              >
                <i class="bi bi-inbox fs-1 text-muted"></i>

                <h6 class="mt-3">
                  Không có hoạt động phù hợp
                </h6>

                <p class="text-muted mb-0">
                  {{
                    danhSachHoatDong.length === 0
                      ? 'Hiện chưa có nhật ký hoạt động.'
                      : 'Không tìm thấy hoạt động theo bộ lọc.'
                  }}
                </p>
              </td>
            </tr>

          </tbody>
        </table>
      </div>
    </div>

  </div>
</template>

<script>
import giangVienService from '../../../services/giangVienService'

export default {
  name: 'HoatDong',

  data() {
    return {
      danhSachHoatDong: [],

      filters: {
        loai: 'ALL',
        search: ''
      },

      loading: false,
      error: '',
      requestId: 0
    }
  },

  computed: {
    // Lọc hoạt động trên dữ liệu đã tải từ API
    filteredActivities() {
      const search = this.normalizeText(this.filters.search)

      return this.danhSachHoatDong.filter(item => {
        const action = String(item.hanhDong ?? '').toUpperCase()

        let matchType = true

        if (this.filters.loai === 'DANG_NHAP') {
          matchType = [
            'DANG_NHAP',
            'DANG_XUAT'
          ].includes(action)
        } else if (this.filters.loai !== 'ALL') {
          matchType = action === this.filters.loai
        }

        const fullName = this.normalizeText(
          item.tenSinhVien
        )

        const studentId = this.normalizeText(
          item.mssv
        )

        const matchSearch =
          fullName.includes(search) ||
          studentId.includes(search)

        return matchType && matchSearch
      })
    }
  },

  mounted() {
    this.fetchData()
  },

  beforeUnmount() {
    this.requestId++
  },

  methods: {
    // Chuẩn hóa danh sách từ API
    extractList(response) {
      const body = response?.data
      const value = body?.data ?? body

      if (Array.isArray(value)) {
        return value
      }

      // Hỗ trợ phản hồi có phân trang
      if (Array.isArray(value?.data)) {
        return value.data
      }

      throw new Error('API không trả về danh sách hợp lệ')
    },

    // Tải nhật ký hoạt động
    async fetchData() {
      const currentRequest = ++this.requestId

      this.loading = true
      this.error = ''
      this.danhSachHoatDong = []

      try {
        const response =
          await giangVienService.getHoatDong()

        if (currentRequest !== this.requestId) {
          return
        }

        const data = this.extractList(response)

        this.danhSachHoatDong = data

      } catch (err) {
        if (currentRequest !== this.requestId) {
          return
        }

        console.error('Lỗi tải nhật ký hoạt động:', err)

        this.error =
          'Không thể tải nhật ký hoạt động. ' +
          'Vui lòng kiểm tra kết nối API.'
      } finally {
        if (currentRequest === this.requestId) {
          this.loading = false
        }
      }
    },

    // Chuẩn hóa văn bản để tìm kiếm
    normalizeText(value) {
      return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .trim()
    },

    // Chuyển mã hoạt động thành nhãn hiển thị
    getActivityStatus(value) {
      const action = String(value ?? '').toUpperCase()

      switch (action) {
        case 'DANG_NHAP':
          return {
            text: 'Đăng nhập',
            className: 'bg-success'
          }

        case 'DANG_XUAT':
          return {
            text: 'Đăng xuất',
            className: 'bg-secondary'
          }

        case 'MO_UNG_DUNG':
          return {
            text: 'Mở ứng dụng',
            className: 'bg-primary'
          }

        case 'NOP_BAI':
          return {
            text: 'Nộp bài tập',
            className: 'bg-info text-dark'
          }

        default:
          return {
            text: value || 'Chưa xác định',
            className: 'bg-light text-dark border'
          }
      }
    },

    // Định dạng thời gian
    formatDate(value) {
      if (!value) return 'Chưa cập nhật'

      // Nếu API trả về giờ dạng HH:mm:ss
      if (/^\d{2}:\d{2}(:\d{2})?$/.test(String(value))) {
        return String(value)
      }

      const date = new Date(value)

      if (Number.isNaN(date.getTime())) {
        return String(value)
      }

      return date.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
.hoat-dong-container {
  min-height: 400px;
}

.table th,
.table td {
  padding: 14px;
  vertical-align: middle;
}

.table th {
  white-space: nowrap;
}
</style>
