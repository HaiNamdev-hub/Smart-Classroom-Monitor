
<template>
  <div class="phien-giam-sat-container p-3">

    <!-- TIÊU ĐỀ -->
    <div
      class="d-flex justify-content-between
             align-items-center flex-wrap gap-3 mb-4"
    >
      <div>
        <h3 class="fw-bold text-dark mb-1">
          🖥️ Quản Lý Phiên Giám Sát
        </h3>

        <p class="text-muted mb-0">
          Theo dõi và quản lý các phiên giám sát lớp học.
        </p>
      </div>

      <div class="d-flex gap-2">
        <button
          type="button"
          class="btn btn-outline-primary btn-sm"
          :disabled="loading"
          @click="fetchData"
        >
          {{ loading ? 'Đang tải...' : 'Làm mới' }}
        </button>

        <button
          type="button"
          class="btn btn-primary btn-sm"
          @click="openCreateForm"
        >
          + Tạo phiên mới
        </button>
      </div>
    </div>

    <!-- THÔNG BÁO -->
    <div
      v-if="error"
      class="alert alert-danger"
      role="alert"
    >
      {{ error }}
    </div>

    <div
      v-if="success"
      class="alert alert-success"
      role="status"
    >
      {{ success }}
    </div>

    <!-- ĐANG TẢI -->
    <div v-if="loading" class="text-center py-5">
      <div
        class="spinner-border text-primary"
        role="status"
      ></div>

      <p class="text-muted mt-3">
        Đang tải danh sách phiên giám sát...
      </p>
    </div>

    <!-- DANH SÁCH PHIÊN -->
    <div
      v-else-if="!error"
      class="card border-0 shadow-sm"
    >
      <div class="card-header bg-white py-3">
        <div
          class="d-flex justify-content-between
                 align-items-center"
        >
          <h6 class="fw-bold mb-0">
            Danh sách phiên giám sát
          </h6>

          <span class="badge bg-primary">
            {{ danhSachPhien.length }} phiên
          </span>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Tên phiên</th>
              <th>Lớp học</th>
              <th>Thời gian bắt đầu</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="phien in danhSachPhien"
              :key="phien.id"
            >
              <td class="fw-bold text-primary">
                #{{ phien.id }}
              </td>

              <td>
                <div class="fw-bold">
                  {{ phien.tenPhien || 'Chưa có tên phiên' }}
                </div>
              </td>

              <td>
                {{ phien.tenLop || getTenLop(phien.lopId) }}
              </td>

              <td class="text-muted small">
                {{ formatDate(phien.thoigianBatDau) }}
              </td>

              <td>
                <span
                  class="badge"
                  :class="getStatus(phien).className"
                >
                  {{ getStatus(phien).text }}
                </span>
              </td>

              <td>
                <div class="d-flex gap-2 flex-wrap">

                  <!-- NÚT XEM -->
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    title="Xem ảnh màn hình"
                    @click="xemGiamSat(phien)"
                  >
                    Xem
                  </button>

                  <!-- NÚT KẾT THÚC ĐÃ SỬA -->
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    :disabled="endingId !== null || !canEnd(phien)"
                    @click="ketThucPhien(phien)"
                    :title="canEnd(phien)
                      ? 'Kết thúc phiên giám sát'
                      : 'Chỉ có thể kết thúc phiên đang diễn ra'"
                  >
                    {{ endingId === phien.id
                      ? 'Đang kết thúc...'
                      : 'Kết thúc' }}
                  </button>

                </div>
              </td>
            </tr>

            <!-- CHƯA CÓ PHIÊN -->
            <tr v-if="danhSachPhien.length === 0">
              <td colspan="6" class="text-center py-5">
                <h6>Chưa có phiên giám sát</h6>

                <p class="text-muted mb-0">
                  Bạn có thể tạo phiên giám sát mới
                  cho lớp học đang phụ trách.
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL TẠO PHIÊN -->
    <div
      v-if="showCreateForm"
      class="modal-backdrop-custom"
    >
      <div
        class="card shadow-lg form-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="create-session-title"
      >
        <div class="card-header bg-white py-3">
          <h5
            id="create-session-title"
            class="fw-bold mb-0"
          >
            Tạo Phiên Giám Sát Mới
          </h5>
        </div>

        <form @submit.prevent="createPhien">
          <div class="card-body">

            <!-- TÊN PHIÊN -->
            <div class="mb-3">
              <label
                for="ten-phien"
                class="form-label fw-bold"
              >
                Tên phiên giám sát *
              </label>

              <input
                id="ten-phien"
                v-model.trim="form.tenPhien"
                class="form-control"
                type="text"
                maxlength="200"
                placeholder="Nhập tên phiên giám sát"
                :disabled="saving"
                required
              />
            </div>

            <!-- CHỌN LỚP HỌC -->
            <div class="mb-3">
              <label
                for="lop-giam-sat"
                class="form-label fw-bold"
              >
                Chọn lớp học *
              </label>

              <select
                id="lop-giam-sat"
                v-model="form.lopId"
                class="form-select"
                :disabled="saving || loadingLop"
                required
              >
                <option value="">
                  -- Chọn lớp học --
                </option>

                <option
                  v-for="lop in danhSachLop"
                  :key="lop.id"
                  :value="String(lop.id)"
                >
                  {{ lop.tenLop || `Lớp ${lop.id}` }}
                  {{ lop.maHocPhan ? `(${lop.maHocPhan})` : '' }}
                </option>
              </select>

              <div
                v-if="loadingLop"
                class="small text-muted mt-2"
              >
                Đang tải danh sách lớp học...
              </div>

              <div
                v-if="lopError"
                class="small text-danger mt-2"
              >
                {{ lopError }}

                <button
                  type="button"
                  class="btn btn-link btn-sm"
                  :disabled="loadingLop"
                  @click="fetchLopHoc"
                >
                  Thử lại
                </button>
              </div>

              <div
                v-if="!loadingLop && !lopError &&
                      danhSachLop.length === 0"
                class="alert alert-warning mt-2 mb-0"
              >
                Bạn chưa có lớp học để tạo phiên giám sát.
              </div>
            </div>

            <!-- THỜI GIAN BẮT ĐẦU -->
            <div class="mb-3">
              <label
                for="thoi-gian-bat-dau"
                class="form-label fw-bold"
              >
                Thời gian bắt đầu *
              </label>

              <input
                id="thoi-gian-bat-dau"
                v-model="form.thoigianBatDau"
                class="form-control"
                type="datetime-local"
                :disabled="saving"
                required
              />

              <div class="form-text">
                Thời gian được nhập theo múi giờ
                địa phương trên máy của bạn.
              </div>
            </div>

            <!-- LỖI FORM -->
            <div
              v-if="formError"
              class="alert alert-danger"
              role="alert"
            >
              {{ formError }}
            </div>
          </div>

          <!-- NÚT FORM -->
          <div
            class="card-footer bg-white
                   d-flex justify-content-end gap-2"
          >
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
              :disabled="
                saving ||
                loadingLop ||
                danhSachLop.length === 0
              "
            >
              <span
                v-if="saving"
                class="spinner-border spinner-border-sm me-1"
              ></span>

              {{ saving ? 'Đang tạo...' : 'Tạo phiên' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script>
import giangVienService from '../../../services/giangVienService'
import api from '../../../services/api'

export default {
  name: 'PhienGiamSat',

  data() {
    return {
      danhSachPhien: [],
      danhSachLop: [],

      endingId: null,

      loading: false,
      loadingLop: false,
      saving: false,

      error: '',
      success: '',
      formError: '',
      lopError: '',

      showCreateForm: false,
      requestId: 0,

      form: {
        tenPhien: '',
        lopId: '',
        thoigianBatDau: ''
      }
    }
  },

  mounted() {
    this.fetchData()
    this.fetchLopHoc()
  },

  beforeUnmount() {
    this.requestId++
  },

  methods: {

    // KIỂM TRA PHIÊN CÓ THỂ KẾT THÚC
    canEnd(phien) {
      return String(phien?.trangThai ?? '').toUpperCase()
        === 'DANG_DIEN_RA'
    },

    // KẾT THÚC PHIÊN GIÁM SÁT
    async ketThucPhien(phien) {
      if (!this.canEnd(phien) || this.endingId !== null) {
        return
      }

      const confirmed = window.confirm(
        `Bạn có chắc muốn kết thúc phiên #${phien.id} - ${phien.tenPhien}?`
      )

      if (!confirmed) return

      this.endingId = phien.id
      this.error = ''
      this.success = ''

      try {
        const response = await api.put(
          `/giang-vien/phien-giam-sat/${encodeURIComponent(phien.id)}/ket-thuc`
        )

        if (response?.data?.data?.trangThai !== 'DA_KET_THUC') {
          throw new Error(
            'Chưa xác nhận được trạng thái kết thúc.'
          )
        }

        const loaded = await this.fetchData()

        if (loaded) {
          this.success =
            `Đã kết thúc phiên #${phien.id} thành công.`
        }

      } catch (err) {
        console.error('Lỗi kết thúc phiên:', err)

        this.error =
          err.response?.data?.message ||
          'Không thể xác nhận kết thúc phiên. ' +
          'Vui lòng tải lại danh sách.'

      } finally {
        this.endingId = null
      }
    },

    // CHUẨN HÓA DANH SÁCH API
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

    // LẤY DANH SÁCH LỚP HỌC
    async fetchLopHoc() {
      if (this.loadingLop) return

      this.loadingLop = true
      this.lopError = ''

      try {
        const response =
          await giangVienService.getLopHoc()

        this.danhSachLop =
          this.extractList(response)

      } catch (err) {
        console.error('Lỗi tải lớp học:', err)

        this.danhSachLop = []
        this.lopError =
          'Không thể tải danh sách lớp học từ Backend.'

      } finally {
        this.loadingLop = false
      }
    },

    // LẤY DANH SÁCH PHIÊN GIÁM SÁT
    async fetchData() {
      const currentRequest = ++this.requestId

      this.loading = true
      this.error = ''

      try {
        const response =
          await giangVienService.getPhienGiamSat()

        if (currentRequest !== this.requestId) {
          return false
        }

        this.danhSachPhien =
          this.extractList(response)

        return true

      } catch (err) {
        if (currentRequest !== this.requestId) {
          return false
        }

        console.error('Lỗi tải phiên giám sát:', err)

        this.error =
          'Không thể tải danh sách phiên giám sát. ' +
          'Vui lòng kiểm tra kết nối API.'

        return false

      } finally {
        if (currentRequest === this.requestId) {
          this.loading = false
        }
      }
    },

    // TẠO DATETIME-LOCAL
    getLocalDateTime() {
      const now = new Date()

      const pad = number =>
        String(number).padStart(2, '0')

      return (
        now.getFullYear() + '-' +
        pad(now.getMonth() + 1) + '-' +
        pad(now.getDate()) + 'T' +
        pad(now.getHours()) + ':' +
        pad(now.getMinutes())
      )
    },

    // MỞ FORM TẠO PHIÊN
    openCreateForm() {
      this.form = {
        tenPhien: '',
        lopId: '',
        thoigianBatDau: this.getLocalDateTime()
      }

      this.formError = ''
      this.success = ''
      this.showCreateForm = true

      if (
        this.danhSachLop.length === 0 &&
        !this.loadingLop
      ) {
        this.fetchLopHoc()
      }
    },

    // ĐÓNG FORM
    closeCreateForm() {
      if (this.saving) return

      this.showCreateForm = false
      this.formError = ''
    },

    // TẠO PHIÊN GIÁM SÁT
    async createPhien() {
      if (this.saving) return

      this.formError = ''

      const tenPhien = this.form.tenPhien.trim()
      const lopId = Number(this.form.lopId)
      const timeValue = this.form.thoigianBatDau

      // Kiểm tra tên phiên
      if (!tenPhien) {
        this.formError =
          'Vui lòng nhập tên phiên giám sát.'
        return
      }

      // Kiểm tra lớp học
      if (
        !Number.isInteger(lopId) ||
        lopId <= 0 ||
        !this.danhSachLop.some(
          lop => Number(lop.id) === lopId
        )
      ) {
        this.formError =
          'Vui lòng chọn lớp học hợp lệ.'
        return
      }

      // Kiểm tra thời gian
      if (!timeValue) {
        this.formError =
          'Vui lòng chọn thời gian bắt đầu.'
        return
      }

      const startTime = new Date(timeValue)

      if (Number.isNaN(startTime.getTime())) {
        this.formError =
          'Thời gian bắt đầu không hợp lệ.'
        return
      }

      this.saving = true
      this.success = ''

      try {
        const payload = {
          tenPhien: tenPhien,
          lopId: lopId,
          thoigianBatDau: startTime.toISOString()
        }

        const response =
          await giangVienService.createPhienGiamSat(
            payload
          )

        if (response?.data?.success === false) {
          throw new Error(
            response.data.message ||
            'Backend không xác nhận tạo phiên.'
          )
        }

        const createdId = response?.data?.data?.id

        if (createdId == null) {
          throw new Error(
            'API không trả về ID phiên mới. ' +
            'Cần kiểm tra phản hồi trước khi gửi lại.'
          )
        }

        this.showCreateForm = false

        const loaded = await this.fetchData()

        if (loaded) {
          this.success =
            `Đã tạo phiên giám sát #${createdId} thành công.`
        } else {
          this.success =
            `Đã tạo phiên #${createdId}, ` +
            'nhưng chưa tải lại được danh sách.'
        }

      } catch (err) {
        console.error('Lỗi tạo phiên:', err)

        const status = err.response?.status

        if (status === 422) {
          const errors = err.response?.data?.errors

          const messages = errors
            ? Object.values(errors).flat()
            : []

          this.formError =
            messages.join(' ') ||
            'Dữ liệu không hợp lệ. ' +
            'Vui lòng kiểm tra các trường.'

        } else if (status === 401) {
          this.formError =
            'Phiên đăng nhập hết hạn. ' +
            'Vui lòng đăng nhập lại.'

        } else if (status === 403) {
          this.formError =
            'Bạn không có quyền tạo phiên ' +
            'cho lớp học này.'

        } else if (status === 500) {
          this.formError =
            'Backend gặp lỗi khi tạo phiên giám sát.'

        } else {
          this.formError =
            err.message ||
            'Không thể tạo phiên giám sát.'
        }

      } finally {
        this.saving = false
      }
    },

    // LẤY TÊN LỚP THEO ID
    getTenLop(lopId) {
      const lop = this.danhSachLop.find(
        item => Number(item.id) === Number(lopId)
      )

      return lop?.tenLop || 'Chưa cập nhật'
    },

    // CHUYỂN SANG MÀN HÌNH ẢNH
    xemGiamSat(phien) {
      if (phien?.id == null) {
        this.error =
          'Phiên giám sát không có ID hợp lệ.'
        return
      }

      const phienId =
        encodeURIComponent(String(phien.id))

      window.location.assign(
        `/giang-vien/anh-man-hinh?phienId=${phienId}`
      )
    },

    // HIỂN THỊ TRẠNG THÁI PHIÊN
    getStatus(phien) {
      const status = String(
        phien?.trangThai ?? ''
      ).toUpperCase()

      if (
        status === 'DANG_DIEN_RA' ||
        status === 'ACTIVE'
      ) {
        return {
          text: 'Đang diễn ra',
          className: 'bg-success'
        }
      }

      if (
        status === 'CHUA_BAT_DAU' ||
        status === 'PENDING'
      ) {
        return {
          text: 'Chưa bắt đầu',
          className: 'bg-warning text-dark'
        }
      }

      if (
        status === 'DA_KET_THUC' ||
        status === 'FINISHED'
      ) {
        return {
          text: 'Đã kết thúc',
          className: 'bg-secondary'
        }
      }

      return {
        text: 'Chưa xác định',
        className: 'bg-secondary'
      }
    },

    // ĐỊNH DẠNG THỜI GIAN
    formatDate(value) {
      if (!value) {
        return 'Chưa cập nhật'
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
        minute: '2-digit'
      })
    }
  }
}
</script>

<style scoped>
.phien-giam-sat-container {
  min-height: 400px;
}

.card {
  border-radius: 8px;
}

.table th,
.table td {
  padding: 14px;
  vertical-align: middle;
}

.table th {
  white-space: nowrap;
}

.badge {
  padding: 7px 10px;
  font-size: 12px;
}

.modal-backdrop-custom {
  position: fixed;
  inset: 0;
  z-index: 1050;
  background: rgba(0, 0, 0, 0.5);
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

.card-footer {
  padding: 14px 20px;
}

@media (max-width: 768px) {
  .phien-giam-sat-container {
    padding: 12px !important;
  }

  .table th,
  .table td {
    padding: 10px;
    font-size: 13px;
  }

  .form-card {
    max-width: 100%;
  }
}
</style>
