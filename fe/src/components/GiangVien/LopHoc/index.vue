<template>
  <div>
    <!-- �ang ph�t tri?n -->
  </div>
</template>

<template>
  <div class="lop-hoc-container p-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold text-dark mb-0">
        📚 Quản Lý Lớp Học
      </h3>

      <button
        class="btn btn-outline-primary btn-sm"
        :disabled="loading"
        @click="fetchData"
      >
        <i class="bi bi-arrow-clockwise me-1"></i>
        {{ loading ? 'Đang tải...' : 'Làm mới' }}
      </button>
    </div>

    <!-- Thông báo lỗi -->
    <div v-if="error" class="alert alert-danger">
      <i class="bi bi-exclamation-triangle me-2"></i>
      {{ error }}
    </div>

    <!-- Đang tải -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="text-muted mt-3">
        Đang tải danh sách lớp học...
      </p>
    </div>

    <!-- Danh sách rỗng -->
    <div
      v-else-if="!error && danhSachLop.length === 0"
      class="text-center py-5"
    >
      <i class="bi bi-journal-x fs-1 text-muted"></i>
      <h5 class="mt-3">Chưa có lớp học</h5>
      <p class="text-muted">
        Hiện chưa có lớp học nào được phân công.
      </p>
    </div>

    <!-- Danh sách lớp -->
    <div v-else-if="!error" class="row g-3">
      <div
        v-for="lop in danhSachLop"
        :key="lop.id"
        class="col-md-4"
      >
        <div class="card border-0 shadow-sm h-100 p-3">
          <div class="fw-bold fs-5 text-primary mb-1">
            {{ lop.tenLop || 'Chưa có tên lớp' }}
          </div>

          <div class="text-muted small mb-3">
            Mã HP: {{ lop.maHocPhan ?? 'Chưa cập nhật' }}
          </div>

          <div
            class="d-flex justify-content-between
                   text-muted small border-top pt-3 mt-auto"
          >
            <span>
              <i class="bi bi-people me-1"></i>
              Sĩ số: {{ lop.siSo ?? 'Chưa có' }}
            </span>

            <span>
              <i class="bi bi-geo-alt me-1"></i>
              {{ lop.phongHoc || 'Chưa có phòng' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import giangVienService from '../../../services/giangVienService'

export default {
  name: 'LopHoc',

  data() {
    return {
      danhSachLop: [],
      loading: false,
      error: ''
    }
  },

  mounted() {
    this.fetchData()
  },

  methods: {
    async fetchData() {
      this.loading = true
      this.error = ''
      this.danhSachLop = []

      try {
        const response = await giangVienService.getLopHoc()

        const data = response.data?.data ?? response.data

        if (!Array.isArray(data)) {
          throw new Error('Dữ liệu lớp học không hợp lệ')
        }

        this.danhSachLop = data
      } catch (err) {
        console.error('Lỗi tải lớp học:', err)

        this.error =
          'Không thể tải danh sách lớp học. ' +
          'Vui lòng kiểm tra kết nối hệ thống.'
      } finally {
        this.loading = false
      }
    }
  }
}
</script>
