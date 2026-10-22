
<template>
  <div class="diem-container p-3">

    <!-- Tiêu đề -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold text-dark mb-1">
          📊 Quản Lý Điểm Số
        </h3>
        <p class="text-muted mb-0">
          Theo dõi điểm bài tập của sinh viên theo lớp học.
        </p>
      </div>

      <button
        class="btn btn-outline-primary btn-sm"
        :disabled="loading || loadingLop || !selectedLopId"
        @click="fetchBangDiem"
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
          :disabled="loadingLop"
          @change="fetchBangDiem"
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
    <div
      v-if="error"
      class="alert alert-danger"
      role="alert"
    >
      {{ error }}
    </div>

    <!-- Đang tải dữ liệu -->
    <div
      v-if="loading || loadingLop"
      class="text-center py-5"
    >
      <div
        class="spinner-border text-primary"
        role="status"
      ></div>

      <p class="text-muted mt-3">
        Đang tải dữ liệu bảng điểm...
      </p>
    </div>

    <!-- Chưa chọn lớp -->
    <div
      v-else-if="!error && !selectedLopId"
      class="alert alert-info"
    >
      Vui lòng chọn lớp học để xem bảng điểm.
    </div>

    <!-- Không có dữ liệu -->
    <div
      v-else-if="!error && bangDiem.length === 0"
      class="text-center py-5"
    >
      <h5>Chưa có dữ liệu điểm</h5>

      <p class="text-muted">
        Lớp học này chưa có bài nộp được chấm điểm.
      </p>
    </div>

    <!-- Bảng điểm -->
    <div
      v-else-if="!error"
      class="card border-0 shadow-sm"
    >
      <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
          <span class="fw-bold">
            Bảng điểm bài tập
          </span>

          <span class="badge bg-primary">
            {{ bangDiem.length }} bài đã chấm
          </span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>STT</th>
              <th>Họ và Tên</th>
              <th>Tên Bài Tập</th>
              <th>Điểm Số</th>
              <th>Kết Quả</th>
              <th>Nhận Xét</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(d, index) in bangDiem"
              :key="d.id ?? index"
            >
              <td>{{ index + 1 }}</td>

              <!-- Tên sinh viên -->
              <td class="fw-semibold">
                {{ d.hoTen || 'Chưa cập nhật' }}
              </td>

              <!-- Tên bài tập -->
              <td>
                {{ d.tenBaiTap || 'Chưa cập nhật' }}
              </td>

              <!-- Điểm số -->
              <td>
                <span
                  class="badge"
                  :class="scoreClass(d.diem)"
                >
                  {{ formatScore(d.diem) }}
                </span>
              </td>

              <!-- Kết quả -->
              <td>
                <span
                  class="badge"
                  :class="resultClass(d.ketQua)"
                >
                  {{ formatResult(d.ketQua) }}
                </span>
              </td>

              <!-- Nhận xét -->
              <td class="nhan-xet">
                {{ d.nhanXet || 'Chưa có nhận xét' }}
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
  name: 'Diem',

  data() {
    return {
      danhSachLop: [],
      bangDiem: [],

      selectedLopId: '',

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

    // Chuẩn hóa dữ liệu API
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

    // Lấy danh sách lớp học của Giảng viên
    async fetchLopHoc() {
      this.loadingLop = true
      this.error = ''
      this.danhSachLop = []

      try {
        const response = await giangVienService.getLopHoc()

        this.danhSachLop = this.extractList(response)

      } catch (err) {
        console.error('Lỗi tải lớp học:', err)

        this.error =
          'Không thể tải danh sách lớp học. ' +
          'Vui lòng kiểm tra kết nối API.'

      } finally {
        this.loadingLop = false
      }
    },

    // Lấy điểm thực tế theo lớp
    async fetchBangDiem() {
      const currentRequest = ++this.requestId

      this.error = ''
      this.bangDiem = []

      if (!this.selectedLopId) {
        this.loading = false
        return
      }

      this.loading = true

      try {
        const response = await giangVienService.getBangDiem(
          this.selectedLopId
        )

        // Không dùng dữ liệu từ request cũ
        if (currentRequest !== this.requestId) {
          return
        }

        const items = this.extractList(response)

        this.bangDiem = items.map(item => ({
          id: item.id,
          hoTen: item.hoTen ?? '',
          tenBaiTap: item.tenBaiTap ?? '',
          diem: item.diem,
          ketQua: item.ketQua ?? null,
          nhanXet: item.nhanXet ?? ''
        }))

      } catch (err) {
        if (currentRequest !== this.requestId) {
          return
        }

        console.error('Lỗi tải bảng điểm:', err)

        this.error =
          'Không thể tải bảng điểm của lớp. ' +
          'Vui lòng kiểm tra kết nối API.'

      } finally {
        if (currentRequest === this.requestId) {
          this.loading = false
        }
      }
    },

    // Hiển thị điểm từ 0 đến 10
    formatScore(value) {
      if (
        value === null ||
        value === undefined ||
        value === ''
      ) {
        return 'Chưa có'
      }

      const score = Number(value)

      if (
        !Number.isFinite(score) ||
        score < 0 ||
        score > 10
      ) {
        return 'Không hợp lệ'
      }

      return score.toLocaleString('vi-VN', {
        maximumFractionDigits: 2
      })
    },

    // Màu điểm số
    scoreClass(value) {
      if (
        value === null ||
        value === undefined ||
        value === ''
      ) {
        return 'bg-secondary'
      }

      const score = Number(value)

      if (
        !Number.isFinite(score) ||
        score < 0 ||
        score > 10
      ) {
        return 'bg-secondary'
      }

      if (score >= 8) {
        return 'bg-success'
      }

      if (score >= 5) {
        return 'bg-primary'
      }

      return 'bg-danger'
    },

    // Hiển thị kết quả từ Backend
    formatResult(value) {
      if (value === 'dat') {
        return 'Đạt'
      }

      if (value === 'khong_dat') {
        return 'Không đạt'
      }

      return 'Chưa xác định'
    },

    // Màu trạng thái kết quả
    resultClass(value) {
      if (value === 'dat') {
        return 'bg-success'
      }

      if (value === 'khong_dat') {
        return 'bg-danger'
      }

      return 'bg-secondary'
    }
  }
}
</script>

<style scoped>
.diem-container {
  min-height: 400px;
}

.card {
  border-radius: 8px;
}

.table {
  width: 100%;
}

.table th,
.table td {
  padding: 14px;
  vertical-align: middle;
}

.table th {
  white-space: nowrap;
  font-weight: 600;
}

.table tbody tr:hover {
  background-color: #f8fafc;
}

.badge {
  padding: 7px 12px;
  font-size: 13px;
  font-weight: 600;
}

.nhan-xet {
  max-width: 320px;
  min-width: 170px;
  white-space: normal;
  overflow-wrap: anywhere;
}

@media (max-width: 768px) {
  .diem-container {
    padding: 12px !important;
  }

  .table th,
  .table td {
    padding: 10px;
    font-size: 13px;
  }
}
</style>
