
<template>
  <div class="bai-tap-container p-3">
    <!-- Tiêu đề -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="fw-bold text-dark mb-0">
        📝 Quản Lý Bài Tập
      </h3>

      <button
        class="btn btn-primary btn-sm"
        :disabled="loadingLop || danhSachLop.length === 0"
        @click="openCreateForm"
      >
        + Tạo Bài Tập Mới
      </button>
    </div>

    <!-- Chọn lớp -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <label class="form-label fw-bold">
          Chọn lớp học
        </label>

        <select
          v-model="selectedLopId"
          class="form-select"
          :disabled="loadingLop"
          @change="fetchBaiTap"
        >
          <option value="">Tất cả lớp học</option>

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

    <!-- Thông báo -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>

    <div v-if="success" class="alert alert-success">
      {{ success }}
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <p class="text-muted mt-3">
        Đang tải danh sách bài tập...
      </p>
    </div>

    <!-- Danh sách trống -->
    <div
      v-else-if="!error && danhSachBaiTap.length === 0"
      class="text-center py-5"
    >
      <h5>Chưa có bài tập</h5>
      <p class="text-muted">
        Hiện chưa có bài tập nào trong danh sách.
      </p>
    </div>

    <!-- Danh sách bài tập -->
    <div v-else-if="!error" class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between">
          <span class="fw-bold">
            Danh sách bài tập
          </span>
          <span class="badge bg-primary">
            {{ danhSachBaiTap.length }} bài tập
          </span>
        </div>
      </div>

      <ul class="list-group list-group-flush">
        <li
          v-for="bt in danhSachBaiTap"
          :key="bt.id"
          class="list-group-item p-3"
        >
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold text-dark fs-6">
                {{ bt.tieuDe || 'Chưa có tiêu đề' }}
              </div>

              <div class="small text-muted mt-1">
                Hạn nộp:
                {{ formatDate(bt.hanNop) }}
              </div>

              <div
                v-if="bt.moTa"
                class="small text-muted mt-1"
              >
                {{ bt.moTa }}
              </div>
            </div>

            <span class="badge bg-primary-subtle text-primary">
              {{ bt.daNop ?? 0 }} bài đã nộp
            </span>
          </div>
        </li>
      </ul>
    </div>

    <!-- Form tạo bài tập -->
    <div
      v-if="showCreateForm"
      class="modal-backdrop-custom"
    >
      <div class="card shadow-lg form-card">
        <div class="card-header bg-white">
          <h5 class="mb-0 fw-bold">
            Tạo Bài Tập Mới
          </h5>
        </div>

        <form @submit.prevent="createBaiTap">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-bold">
                Lớp học *
              </label>

              <select
                v-model="form.lopId"
                class="form-select"
                required
              >
                <option value="">-- Chọn lớp --</option>

                <option
                  v-for="lop in danhSachLop"
                  :key="lop.id"
                  :value="String(lop.id)"
                >
                  {{ lop.tenLop || `Lớp ${lop.id}` }}
                </option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">
                Tiêu đề bài tập *
              </label>

              <input
                v-model.trim="form.tieuDe"
                class="form-control"
                type="text"
                maxlength="200"
                placeholder="Nhập tiêu đề bài tập"
                required
              />
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">
                Mô tả bài tập
              </label>

              <textarea
                v-model.trim="form.moTa"
                class="form-control"
                rows="4"
                placeholder="Nhập nội dung hoặc yêu cầu bài tập"
              ></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">
                Hạn nộp *
              </label>

              <input
                v-model="form.hanNop"
                class="form-control"
                type="datetime-local"
                required
              />
            </div>

            <div
              v-if="formError"
              class="alert alert-danger py-2"
            >
              {{ formError }}
            </div>
          </div>

          <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <button
              type="button"
              class="btn btn-secondary"
              :disabled="saving"
              @click="closeCreateForm"
            >
              Hủy
            </button>

            <button
              type="submit"
              class="btn btn-primary"
              :disabled="saving"
            >
              {{ saving ? 'Đang lưu...' : 'Tạo bài tập' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import giangVienService from '../../../services/giangVienService'

export default {
  name: 'BaiTap',

  data() {
    return {
      danhSachLop: [],
      danhSachBaiTap: [],
      selectedLopId: '',

      loading: false,
      loadingLop: false,
      saving: false,

      error: '',
      success: '',
      formError: '',

      showCreateForm: false,
      requestId: 0,

      form: {
        lopId: '',
        tieuDe: '',
        moTa: '',
        hanNop: ''
      }
    }
  },

  mounted() {
    this.fetchLopHoc()
    this.fetchBaiTap()
  },

  beforeUnmount() {
    this.requestId++
  },

  methods: {
    extractList(response) {
      const body = response?.data
      const value = body?.data ?? body

      if (Array.isArray(value)) {
        return value
      }

      if (Array.isArray(value?.data)) {
        return value.data
      }

      throw new Error('Dữ liệu danh sách không hợp lệ')
    },

    // Tải danh sách lớp học
    async fetchLopHoc() {
      this.loadingLop = true

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

    // Tải danh sách bài tập
    async fetchBaiTap() {
      const currentRequest = ++this.requestId

      this.loading = true
      this.error = ''
      this.danhSachBaiTap = []

      try {
        const params = this.selectedLopId
          ? { lopId: this.selectedLopId }
          : {}

        const response = await giangVienService.getBaiTap(params)

        if (currentRequest !== this.requestId) {
          return
        }

        this.danhSachBaiTap = this.extractList(response)
      } catch (err) {
        if (currentRequest !== this.requestId) {
          return
        }

        console.error('Lỗi tải bài tập:', err)
        this.error =
          'Không thể tải danh sách bài tập. Vui lòng kiểm tra kết nối API.'
      } finally {
        if (currentRequest === this.requestId) {
          this.loading = false
        }
      }
    },

    // Mở form
    openCreateForm() {
      this.form = {
        lopId: this.selectedLopId,
        tieuDe: '',
        moTa: '',
        hanNop: ''
      }

      this.formError = ''
      this.showCreateForm = true
    },

    closeCreateForm() {
      if (this.saving) return

      this.showCreateForm = false
      this.formError = ''
    },

    // Gửi yêu cầu tạo bài tập
    async createBaiTap() {
      this.formError = ''

      if (!this.form.lopId || !this.form.tieuDe || !this.form.hanNop) {
        this.formError = 'Vui lòng nhập đầy đủ thông tin bắt buộc.'
        return
      }

      const deadline = new Date(this.form.hanNop)

      if (
        Number.isNaN(deadline.getTime()) ||
        deadline.getTime() <= Date.now()
      ) {
        this.formError = 'Hạn nộp phải là thời gian trong tương lai.'
        return
      }

      this.saving = true

      try {
        const data = {
          lopId: this.form.lopId,
          tieuDe: this.form.tieuDe,
          moTa: this.form.moTa,
          hanNop: this.form.hanNop
        }

        const response = await giangVienService.createBaiTap(data)

        if (response?.data?.success === false) {
          throw new Error('API không xác nhận tạo bài tập')
        }

        this.showCreateForm = false
        this.success = 'Đã gửi yêu cầu tạo bài tập thành công.'

        this.selectedLopId = this.form.lopId
        await this.fetchBaiTap()

      } catch (err) {
        console.error('Lỗi tạo bài tập:', err)

        this.formError =
          'Không thể tạo bài tập. Vui lòng kiểm tra dữ liệu hoặc kết nối API.'
      } finally {
        this.saving = false
      }
    },

    // Định dạng ngày
    formatDate(value) {
      if (!value) return 'Chưa cập nhật'

      const date = new Date(value)

      if (Number.isNaN(date.getTime())) {
        return String(value)
      }

      return new Intl.DateTimeFormat('vi-VN', {
        hour: '2-digit',
        minute: '2-digit',
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      }).format(date)
    }
  }
}
</script>

<style scoped>
.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1050;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 16px;
}

.form-card {
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}
</style>
