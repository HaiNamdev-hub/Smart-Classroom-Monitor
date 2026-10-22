
<template>
  <div class="thanh-vien-container p-3">

    <!-- Tiêu đề -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold text-dark mb-0">
        👥 Danh Sách Thành Viên Lớp
      </h3>

      <button
        class="btn btn-outline-primary btn-sm"
        :disabled="loading || !selectedLopId"
        @click="fetchThanhVien"
      >
        {{ loading ? 'Đang tải...' : 'Làm mới' }}
      </button>
    </div>

    <!-- Chọn lớp học -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <label class="form-label fw-bold">
          Chọn lớp học
        </label>

        <select
          v-model="selectedLopId"
          class="form-select"
          :disabled="loadingLop || danhSachLop.length === 0"
          @change="fetchThanhVien"
        >
          <option value="">-- Chọn lớp học --</option>

          <option
            v-for="lop in danhSachLop"
            :key="lop.id"
            :value="String(lop.id)"
          >
            {{ lop.tenLop || `Lớp ${lop.id}` }}
          </option>
        </select>
      </div>
    </div>

    <!-- Thông báo lỗi -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <!-- Loading -->
    <div v-if="loadingLop || loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="text-muted mt-3">
        Đang tải dữ liệu...
      </p>
    </div>

    <!-- Chưa chọn lớp -->
    <div
      v-else-if="!error && !selectedLopId"
      class="alert alert-info"
    >
      Vui lòng chọn lớp học để xem danh sách sinh viên.
    </div>

    <!-- Danh sách rỗng -->
    <div
      v-else-if="!error && danhSach.length === 0"
      class="text-center py-5"
    >
      <h5>Chưa có thành viên</h5>
      <p class="text-muted">
        Lớp học này hiện chưa có sinh viên.
      </p>
    </div>

    <!-- Bảng danh sách -->
    <div v-else-if="!error" class="card border-0 shadow-sm">

      <div class="card-header bg-white py-3">
        <span class="fw-bold">
          Tổng số sinh viên: {{ danhSach.length }}
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>STT</th>
              <th>MSSV</th>
              <th>Họ và Tên</th>
              <th>Email</th>
              <th>Trạng thái Agent</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(sv, index) in danhSach"
              :key="sv.id ?? sv.mssv ?? index"
            >
              <td>{{ index + 1 }}</td>

              <td class="fw-bold text-primary">
                {{ sv.mssv ?? 'Chưa có' }}
              </td>

              <td class="fw-semibold">
                {{ sv.hoTen || 'Chưa cập nhật' }}
              </td>

              <td class="text-muted small">
                {{ sv.email || 'Chưa cập nhật' }}
              </td>

              <td>
                <span
                  class="badge"
                  :class="agentStatus(sv).className"
                >
                  {{ agentStatus(sv).text }}
                </span>
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
  name: 'ThanhVienLop',

  data() {
    return {
      danhSachLop: [],
      selectedLopId: '',
      danhSach: [],
      loadingLop: false,
      loading: false,
      error: '',
      requestId: 0
    }
  },

  mounted() {
    this.fetchLopHoc()
  },

  beforeUnmount() {
    this.requestId++
  },

  methods: {
    // Chuẩn hóa dữ liệu trả về từ API
    extractList(response) {
      const body = response?.data
      const value = body?.data ?? body

      if (Array.isArray(value)) {
        return value
      }

      // Hỗ trợ dữ liệu phân trang
      if (Array.isArray(value?.data)) {
        return value.data
      }

      throw new Error('API không trả về danh sách hợp lệ')
    },

    // Lấy danh sách lớp học
    async fetchLopHoc() {
      this.loadingLop = true
      this.error = ''
      this.danhSachLop = []

      try {
        const response = await giangVienService.getLopHoc()

        this.danhSachLop = this.extractList(response)

        if (this.danhSachLop.length === 0) {
          this.error = 'Bạn chưa được phân công lớp học nào.'
        }
      } catch (err) {
        console.error('Lỗi tải lớp học:', err)

        this.error =
          'Không thể tải danh sách lớp học. ' +
          'Vui lòng kiểm tra kết nối API.'
      } finally {
        this.loadingLop = false
      }
    },

    // Lấy danh sách thành viên theo lớp đã chọn
    async fetchThanhVien() {
      const currentRequest = ++this.requestId

      this.danhSach = []
      this.error = ''

      if (!this.selectedLopId) {
        this.loading = false
        return
      }

      this.loading = true

      try {
        const response =
          await giangVienService.getThanhVienLop(
            this.selectedLopId
          )

        if (currentRequest !== this.requestId) {
          return
        }

        this.danhSach = this.extractList(response)

      } catch (err) {
        if (currentRequest !== this.requestId) {
          return
        }

        console.error('Lỗi tải thành viên:', err)

        this.error =
          'Không thể tải danh sách thành viên của lớp.'
      } finally {
        if (currentRequest === this.requestId) {
          this.loading = false
        }
      }
    },

    // Hiển thị trạng thái Agent theo dữ liệu API
    agentStatus(sv) {
      const status = sv.trangThaiAgent

      if (
        status === 'connected' ||
        status === 'online' ||
        status === true
      ) {
        return {
          text: 'Đã kết nối',
          className: 'bg-success'
        }
      }

      if (
        status === 'disconnected' ||
        status === 'offline' ||
        status === false
      ) {
        return {
          text: 'Chưa kết nối',
          className: 'bg-secondary'
        }
      }

      return {
        text: 'Chưa xác định',
        className: 'bg-warning text-dark'
      }
    }
  }
}
</script>
