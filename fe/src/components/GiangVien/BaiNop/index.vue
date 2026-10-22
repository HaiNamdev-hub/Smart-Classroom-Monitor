
<template>
  <div class="bai-nop-container p-3">
    <!-- Tiêu đề -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold text-dark mb-1">
          📥 Danh Sách Bài Nộp Sinh Viên
        </h3>
        <p class="text-muted mb-0">
          Theo dõi bài tập đã được sinh viên nộp
        </p>
      </div>

      <button
        class="btn btn-outline-primary btn-sm"
        :disabled="loading || loadingBaiTap || !selectedBaiTapId"
        @click="fetchBaiNop"
      >
        {{ loading ? 'Đang tải...' : 'Làm mới' }}
      </button>
    </div>

    <!-- Chọn lớp và bài tập -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">

          <div class="col-md-6">
            <label class="form-label fw-bold">
              Chọn lớp học
            </label>

            <select
              v-model="selectedLopId"
              class="form-select"
              :disabled="loadingLop"
              @change="fetchBaiTap"
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

          <div class="col-md-6">
            <label class="form-label fw-bold">
              Chọn bài tập
            </label>

            <select
              v-model="selectedBaiTapId"
              class="form-select"
              :disabled="!selectedLopId || loadingBaiTap"
              @change="fetchBaiNop"
            >
              <option value="">-- Chọn bài tập --</option>

              <option
                v-for="bt in danhSachBaiTap"
                :key="bt.id"
                :value="String(bt.id)"
              >
                {{ bt.tieuDe || `Bài tập ${bt.id}` }}
              </option>
            </select>
          </div>

        </div>
      </div>
    </div>

    <!-- Thông báo lỗi -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <!-- Loading -->
    <div
      v-if="loadingLop || loadingBaiTap || loading"
      class="text-center py-5"
    >
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
      Vui lòng chọn lớp học để tiếp tục.
    </div>

    <!-- Chưa có bài tập -->
    <div
      v-else-if="!error && danhSachBaiTap.length === 0"
      class="alert alert-info"
    >
      Lớp học này chưa có bài tập.
    </div>

    <!-- Chưa chọn bài tập -->
    <div
      v-else-if="!error && !selectedBaiTapId"
      class="alert alert-info"
    >
      Vui lòng chọn bài tập để xem danh sách bài nộp.
    </div>

    <!-- Danh sách rỗng -->
    <div
      v-else-if="!error && danhSach.length === 0"
      class="text-center py-5"
    >
      <h5>Chưa có bài nộp</h5>
      <p class="text-muted">
        Chưa có bài nộp nào cho bài tập này.
      </p>
    </div>

    <!-- Bảng bài nộp -->
    <div v-else-if="!error" class="card border-0 shadow-sm">

      <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
          <span class="fw-bold">
            Danh sách bài nộp
          </span>

          <span class="badge bg-primary">
            {{ danhSach.length }} bài nộp
          </span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>STT</th>
              <th>Sinh viên</th>
              <th>Bài tập</th>
              <th>Thời gian nộp</th>
              <th>Trạng thái</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(item, index) in danhSach"
              :key="item.id ?? index"
            >
              <td>{{ index + 1 }}</td>

              <td class="fw-bold text-dark">
                {{ item.tenSinhVien || 'Chưa cập nhật' }}
              </td>

              <td>
                {{
                  item.tenBaiTap ||
                  selectedBaiTap?.tieuDe ||
                  'Chưa cập nhật'
                }}
              </td>

              <td class="text-muted small">
                {{ formatDate(item.thoiGianNop) }}
              </td>

              <td>
                <span
                  class="badge"
                  :class="getStatus(item).className"
                >
                  {{ getStatus(item).text }}
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
  name: 'BaiNop',

  data() {
    return {
      danhSachLop: [],
      danhSachBaiTap: [],
      danhSach: [],

      selectedLopId: '',
      selectedBaiTapId: '',

      loadingLop: false,
      loadingBaiTap: false,
      loading: false,

      error: '',

      baiTapRequestId: 0,
      baiNopRequestId: 0
    }
  },

  computed: {
    selectedBaiTap() {
      return this.danhSachBaiTap.find(
        bt => String(bt.id) === this.selectedBaiTapId
      ) || null
    }
  },

  mounted() {
    this.fetchLopHoc()
  },

  beforeUnmount() {
    this.baiTapRequestId++
    this.baiNopRequestId++
  },

  methods: {
    // Chuẩn hóa danh sách từ API
    extractList(response) {
      const body = response?.data
      const value = body?.data ?? body

      if (Array.isArray(value)) {
        return value
      }

      if (Array.isArray(value?.data)) {
        return value.data
      }

      throw new Error('Dữ liệu API không hợp lệ')
    },

    // Lấy danh sách lớp học
    async fetchLopHoc() {
      this.loadingLop = true
      this.error = ''

      try {
        const response = await giangVienService.getLopHoc()
        this.danhSachLop = this.extractList(response)
      } catch (err) {
        console.error('Lỗi tải lớp học:', err)
        this.danhSachLop = []
        this.error = 'Không thể tải danh sách lớp học.'
      } finally {
        this.loadingLop = false
      }
    },

    // Lấy bài tập theo lớp
    async fetchBaiTap() {
      const requestId = ++this.baiTapRequestId
      ++this.baiNopRequestId

      this.selectedBaiTapId = ''
      this.danhSachBaiTap = []
      this.danhSach = []
      this.loading = false
      this.error = ''

      if (!this.selectedLopId) {
        this.loadingBaiTap = false
        return
      }

      this.loadingBaiTap = true

      try {
        const response = await giangVienService.getBaiTap({
          lopId: this.selectedLopId
        })

        if (requestId !== this.baiTapRequestId) {
          return
        }

        this.danhSachBaiTap = this.extractList(response)

      } catch (err) {
        if (requestId !== this.baiTapRequestId) {
          return
        }

        console.error('Lỗi tải bài tập:', err)
        this.error = 'Không thể tải danh sách bài tập.'
      } finally {
        if (requestId === this.baiTapRequestId) {
          this.loadingBaiTap = false
        }
      }
    },

    // Lấy bài nộp theo bài tập được chọn
    async fetchBaiNop() {
      const requestId = ++this.baiNopRequestId

      this.danhSach = []
      this.error = ''

      if (!this.selectedBaiTapId) {
        this.loading = false
        return
      }

      this.loading = true

      try {
        const response = await giangVienService.getBaiNop(
          this.selectedBaiTapId
        )

        if (requestId !== this.baiNopRequestId) {
          return
        }

        this.danhSach = this.extractList(response)

      } catch (err) {
        if (requestId !== this.baiNopRequestId) {
          return
        }

        console.error('Lỗi tải bài nộp:', err)

        this.error =
          'Không thể tải bài nộp. Vui lòng kiểm tra kết nối API.'
      } finally {
        if (requestId === this.baiNopRequestId) {
          this.loading = false
        }
      }
    },

    // Định dạng thời gian
    formatDate(value) {
      if (!value) return 'Chưa cập nhật'

      const date = new Date(value)

      if (Number.isNaN(date.getTime())) {
        return String(value)
      }

      return new Intl.DateTimeFormat('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }).format(date)
    },

    // Trạng thái chỉ dựa trên dữ liệu API
    getStatus(item) {
      const status = item.trangThai ?? item.trangThaiNop

      if (status === 'dung_han' || status === 'on_time') {
        return {
          text: 'Đúng hạn',
          className: 'bg-success'
        }
      }

      if (status === 'tre_han' || status === 'late') {
        return {
          text: 'Trễ hạn',
          className: 'bg-danger'
        }
      }

      if (status === 'da_nop' || status === 'submitted') {
        return {
          text: 'Đã nộp',
          className: 'bg-primary'
        }
      }

      return {
        text: 'Chưa xác định',
        className: 'bg-secondary'
      }
    }
  }
}
</script>
