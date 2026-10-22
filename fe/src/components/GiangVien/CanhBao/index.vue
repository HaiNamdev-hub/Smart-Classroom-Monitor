
<template>
  <div class="canh-bao-container p-3">

    <!-- Tiêu đề -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold text-dark mb-1">
          🚨 Nhật Ký & Cảnh Báo Hệ Thống
        </h3>

        <p class="text-muted mb-0">
          Theo dõi và xử lý các cảnh báo trong phòng Lab.
        </p>
      </div>

      <div class="d-flex gap-2">
        <button
          class="btn btn-outline-primary btn-sm"
          :disabled="isBusy"
          @click="fetchData"
        >
          {{ loading ? 'Đang tải...' : 'Làm mới' }}
        </button>

        <button
          class="btn btn-danger btn-sm"
          :disabled="isBusy || pendingCount === 0 || !!error"
          @click="xuLyTatCa"
        >
          {{ bulkProcessing ? 'Đang xử lý...' : 'Xử lý tất cả đang hiển thị' }}
        </button>
      </div>
    </div>

    <!-- Thống kê -->
    <div class="row g-3 mb-4">

      <div class="col-md-3">
        <div class="stat-card card border-0 shadow-sm p-3">
          <div class="text-muted small fw-bold">
            TỔNG CẢNH BÁO
          </div>

          <h3 class="fw-bold text-primary mt-2">
            {{ loading || error ? '--' : stats.tong }}
          </h3>
        </div>
      </div>

      <div class="col-md-3">
        <div class="stat-card card border-0 shadow-sm p-3">
          <div class="text-muted small fw-bold">
            CHƯA XỬ LÝ
          </div>

          <h3 class="fw-bold text-danger mt-2">
            {{ loading || error ? '--' : stats.chuaXuLy }}
          </h3>
        </div>
      </div>

      <div class="col-md-3">
        <div class="stat-card card border-0 shadow-sm p-3">
          <div class="text-muted small fw-bold">
            MỨC ĐỘ CAO
          </div>

          <h3 class="fw-bold text-warning mt-2">
            {{ loading || error ? '--' : stats.nghiemTrong }}
          </h3>
        </div>
      </div>

      <div class="col-md-3">
        <div class="stat-card card border-0 shadow-sm p-3">
          <div class="text-muted small fw-bold">
            ĐÃ XỬ LÝ
          </div>

          <h3 class="fw-bold text-success mt-2">
            {{ loading || error ? '--' : stats.daXuLy }}
          </h3>
        </div>
      </div>

    </div>

    <!-- Bộ lọc -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">

        <div class="row g-3">

          <div class="col-md-4">
            <label class="form-label fw-bold">
              Chọn phòng Lab
            </label>

            <select
              v-model="filters.phongLab"
              class="form-select"
            >
              <option value="ALL">
                Tất cả phòng Lab
              </option>

              <option
                v-for="phong in danhSachPhong"
                :key="phong"
                :value="phong"
              >
                {{ phong }}
              </option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-bold">
              Mức độ cảnh báo
            </label>

            <select
              v-model="filters.mucDo"
              class="form-select"
            >
              <option value="ALL">Tất cả mức độ</option>
              <option value="HIGH">Nghiêm trọng</option>
              <option value="MEDIUM">Trung bình</option>
              <option value="LOW">Thấp</option>
            </select>
          </div>

          <div class="col-md-5">
            <label class="form-label fw-bold">
              Tìm kiếm sinh viên / máy trạm
            </label>

            <input
              v-model="filters.search"
              class="form-control"
              type="text"
              placeholder="Nhập tên, MSSV hoặc tên máy..."
            />
          </div>

        </div>
      </div>
    </div>

    <!-- Thông báo -->
    <div v-if="error" class="alert alert-danger" role="alert">
      {{ error }}
    </div>

    <div v-if="success" class="alert alert-success" role="status">
      {{ success }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>

      <p class="text-muted mt-3">
        Đang tải danh sách cảnh báo...
      </p>
    </div>

    <!-- Danh sách cảnh báo -->
    <div v-else-if="!error" class="card border-0 shadow-sm">

      <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
          <h6 class="fw-bold mb-0">
            Danh Sách Cảnh Báo
          </h6>

          <span class="badge bg-primary">
            {{ filteredAlerts.length }} cảnh báo
          </span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">

          <thead class="table-light">
            <tr>
              <th>STT</th>
              <th>Thời gian</th>
              <th>Sinh viên</th>
              <th>Máy trạm</th>
              <th>Nội dung cảnh báo</th>
              <th>Mức độ</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(item, index) in filteredAlerts"
              :key="item.id ?? index"
            >
              <td>{{ index + 1 }}</td>

              <td class="text-muted small">
                {{ formatDate(item.thoiGian) }}
              </td>

              <td>
                <div class="fw-bold">
                  {{ item.tenSinhVien || 'Chưa cập nhật' }}
                </div>

                <div class="text-muted small">
                  {{ item.mssv || 'Chưa có MSSV' }}
                </div>
              </td>

              <td>
                <span class="badge bg-light text-dark border">
                  {{ item.tenMay || 'Chưa xác định' }}
                </span>
              </td>

              <td>
                <div class="fw-semibold">
                  {{ item.tieuDe || 'Cảnh báo hệ thống' }}
                </div>

                <div class="small text-muted">
                  {{ item.chiTiet || 'Không có mô tả' }}
                </div>
              </td>

              <td>
                <span
                  class="badge"
                  :class="getMucDo(item).className"
                >
                  {{ getMucDo(item).text }}
                </span>
              </td>

              <td>
                <span
                  class="badge"
                  :class="getStatus(item).className"
                >
                  {{ getStatus(item).text }}
                </span>
              </td>

              <td>
                <div class="d-flex gap-1">

                  <!-- Xử lý cảnh báo -->
                  <button
                    v-if="isPending(item)"
                    class="btn btn-outline-success btn-sm"
                    :disabled="isBusy || item.id == null"
                    :title="item.id == null ? 'Cảnh báo thiếu ID' : 'Đánh dấu đã xử lý'"
                    @click="xuLyAlert(item)"
                  >
                    {{
                      isProcessing(item.id)
                        ? 'Đang xử lý...'
                        : 'Xử lý'
                    }}
                  </button>

                  <!-- Chưa có API nhắc nhở -->
                  <button
                    class="btn btn-outline-warning btn-sm"
                    disabled
                    title="Chưa có API gửi nhắc nhở"
                  >
                    <i class="bi bi-bell"></i>
                  </button>

                  <!-- Chưa có API khóa máy -->
                  <button
                    class="btn btn-outline-danger btn-sm"
                    disabled
                    title="Chưa có API khóa máy"
                  >
                    <i class="bi bi-lock"></i>
                  </button>

                </div>
              </td>
            </tr>

            <tr v-if="filteredAlerts.length === 0">
              <td colspan="8" class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted"></i>

                <h6 class="mt-3">
                  Không có cảnh báo phù hợp
                </h6>

                <p class="text-muted mb-0">
                  {{
                    alerts.length === 0
                      ? 'Hệ thống chưa có dữ liệu cảnh báo.'
                      : 'Không tìm thấy cảnh báo theo bộ lọc.'
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
  name: 'CanhBao',

  data() {
    return {
      alerts: [],

      filters: {
        phongLab: 'ALL',
        mucDo: 'ALL',
        search: ''
      },

      loading: false,
      bulkProcessing: false,
      processingIds: [],

      error: '',
      success: '',

      requestId: 0
    }
  },

  computed: {
    isBusy() {
      return (
        this.loading ||
        this.bulkProcessing ||
        this.processingIds.length > 0
      )
    },

    // Thống kê từ dữ liệu API
    stats() {
      return {
        tong: this.alerts.length,

        chuaXuLy: this.alerts.filter(
          item => this.isPending(item)
        ).length,

        nghiemTrong: this.alerts.filter(
          item => String(item.mucDo ?? '').toUpperCase() === 'HIGH'
        ).length,

        daXuLy: this.alerts.filter(
          item => this.getStatusCode(item) === 'DA_XU_LY'
        ).length
      }
    },

    // Danh sách phòng Lab lấy từ API
    danhSachPhong() {
      return [
        ...new Set(
          this.alerts
            .map(item => String(item.phongLab ?? '').trim())
            .filter(Boolean)
        )
      ].sort()
    },

    // Lọc dữ liệu đã tải
    filteredAlerts() {
      const keyword = this.normalizeText(this.filters.search)

      return this.alerts.filter(item => {
        const name = this.normalizeText(item.tenSinhVien)
        const mssv = this.normalizeText(item.mssv)
        const machine = this.normalizeText(item.tenMay)

        const matchSearch =
          name.includes(keyword) ||
          mssv.includes(keyword) ||
          machine.includes(keyword)

        const matchSeverity =
          this.filters.mucDo === 'ALL' ||
          String(item.mucDo ?? '').toUpperCase() ===
          this.filters.mucDo

        const matchRoom =
          this.filters.phongLab === 'ALL' ||
          String(item.phongLab ?? '') ===
          this.filters.phongLab

        return matchSearch && matchSeverity && matchRoom
      })
    },

    pendingCount() {
      return this.filteredAlerts.filter(
        item => this.isPending(item) && item.id != null
      ).length
    }
  },

  mounted() {
    this.fetchData()
  },

  beforeUnmount() {
    this.requestId++
  },

  methods: {
    // Chuẩn hóa phản hồi API
    extractList(response) {
      const body = response?.data
      const value = body?.data ?? body

      if (Array.isArray(value)) {
        return value
      }

      if (Array.isArray(value?.data)) {
        return value.data
      }

      throw new Error('API không trả về danh sách hợp lệ')
    },

    // Lấy dữ liệu cảnh báo
    async fetchData() {
      if (this.isBusy) return

      const currentRequest = ++this.requestId

      this.loading = true
      this.error = ''
      this.success = ''
      this.alerts = []

      try {
        const response =
          await giangVienService.getCanhBao()

        if (currentRequest !== this.requestId) return

        this.alerts = this.extractList(response)

      } catch (err) {
        if (currentRequest !== this.requestId) return

        console.error('Lỗi tải cảnh báo:', err)

        this.error =
          'Không thể tải danh sách cảnh báo. ' +
          'Vui lòng kiểm tra kết nối API.'
      } finally {
        if (currentRequest === this.requestId) {
          this.loading = false
        }
      }
    },

    // Chuẩn hóa tìm kiếm tiếng Việt
    normalizeText(value) {
      return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D')
        .toLowerCase()
        .trim()
    },

    getStatusCode(item) {
      return String(item.trangThai ?? '').toUpperCase()
    },

    isPending(item) {
      return this.getStatusCode(item) === 'CHUA_XU_LY'
    },

    isProcessing(id) {
      return this.processingIds.includes(id)
    },

    // Hiển thị trạng thái
    getStatus(item) {
      const status = this.getStatusCode(item)

      if (status === 'CHUA_XU_LY') {
        return {
          text: 'Chưa xử lý',
          className: 'bg-danger'
        }
      }

      if (status === 'DA_XU_LY') {
        return {
          text: 'Đã xử lý',
          className: 'bg-success'
        }
      }

      return {
        text: 'Chưa xác định',
        className: 'bg-secondary'
      }
    },

    // Hiển thị mức độ
    getMucDo(item) {
      const level = String(item.mucDo ?? '').toUpperCase()

      if (level === 'HIGH') {
        return {
          text: 'Nghiêm trọng',
          className: 'bg-danger'
        }
      }

      if (level === 'MEDIUM') {
        return {
          text: 'Trung bình',
          className: 'bg-warning text-dark'
        }
      }

      if (level === 'LOW') {
        return {
          text: 'Thấp',
          className: 'bg-info text-dark'
        }
      }

      return {
        text: 'Chưa xác định',
        className: 'bg-secondary'
      }
    },

    // Gọi API đánh dấu đã xử lý
    async sendProcessRequest(id) {
      const response =
        await giangVienService.xuLyCanhBao(id)

      if (response?.data?.success === false) {
        throw new Error('API từ chối xử lý cảnh báo')
      }

      // Chỉ thay đổi khi API trả về thành công
      this.alerts = this.alerts.map(item => {
        if (String(item.id) === String(id)) {
          return {
            ...item,
            trangThai: 'DA_XU_LY'
          }
        }

        return item
      })
    },

    // Xử lý một cảnh báo
    async xuLyAlert(item) {
      if (
        this.isBusy ||
        item.id == null ||
        !this.isPending(item)
      ) {
        return
      }

      this.error = ''
      this.success = ''

      this.processingIds = [item.id]

      try {
        await this.sendProcessRequest(item.id)

        this.success = 'Đã xử lý cảnh báo thành công.'

      } catch (err) {
        console.error('Lỗi xử lý cảnh báo:', err)

        this.error =
          'Không thể xử lý cảnh báo. ' +
          'Vui lòng kiểm tra kết nối API.'
      } finally {
        this.processingIds = []
      }
    },

    // Xử lý các cảnh báo đang hiển thị
    async xuLyTatCa() {
      if (this.isBusy || this.pendingCount === 0) {
        return
      }

      const pendingItems = this.filteredAlerts.filter(
        item => this.isPending(item) && item.id != null
      )

      const confirmed = window.confirm(
        `Bạn có muốn xử lý ${pendingItems.length} cảnh báo đang hiển thị?`
      )

      if (!confirmed) return

      this.bulkProcessing = true
      this.error = ''
      this.success = ''

      let successCount = 0
      let failureCount = 0

      try {
        // Service chưa có API xử lý hàng loạt.
        // Gọi API xử lý từng cảnh báo.
        for (const item of pendingItems) {
          try {
            await this.sendProcessRequest(item.id)
            successCount++
          } catch (err) {
            console.error(
              'Không thể xử lý cảnh báo:',
              item.id,
              err
            )

            failureCount++
          }
        }

        if (successCount > 0) {
          this.success =
            `Đã xử lý thành công ${successCount}/${pendingItems.length} cảnh báo.`
        }

        if (failureCount > 0) {
          this.error =
            `Có ${failureCount} cảnh báo xử lý thất bại. ` +
            'Vui lòng thử lại hoặc kiểm tra API.'
        }

      } finally {
        this.bulkProcessing = false
      }
    },

    // Hiển thị thời gian
    formatDate(value) {
      if (!value) return 'Chưa cập nhật'

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
.canh-bao-container {
  min-height: 400px;
}

.stat-card {
  border-left: 4px solid #dee2e6 !important;
}

.table th,
.table td {
  padding: 13px;
  vertical-align: middle;
}

.table th {
  white-space: nowrap;
}

.table-responsive {
  min-height: 160px;
}
</style>
