<template>
  <div>
    <!-- �ang ph�t tri?n -->
  </div>
</template>

<template>
  <div class="anh-man-hinh-container p-3">

    <!-- Tiêu đề -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="fw-bold text-dark mb-1">
          🖼️ Giám Sát Ảnh Màn Hình
        </h3>

        <p class="text-muted mb-0">
          Theo dõi ảnh chụp màn hình máy trạm của sinh viên.
        </p>
      </div>

      <button
        class="btn btn-outline-primary btn-sm"
        :disabled="loading || !selectedPhienId"
        @click="fetchAnhManHinh"
      >
        <i class="bi bi-arrow-clockwise me-1"></i>
        {{ loading ? 'Đang tải...' : 'Làm mới ảnh' }}
      </button>
    </div>

    <!-- Bộ lọc -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">

          <!-- Chọn phiên -->
          <div class="col-md-6">
            <label class="form-label fw-bold">
              Chọn phiên giám sát
            </label>

            <select
              v-model="selectedPhienId"
              class="form-select"
              :disabled="loadingPhien"
              @change="fetchAnhManHinh"
            >
              <option value="">
                -- Chọn phiên giám sát --
              </option>

              <option
                v-for="phien in danhSachPhien"
                :key="phien.id"
                :value="String(phien.id)"
              >
                {{ phien.tenPhien || `Phiên ${phien.id}` }}
              </option>
            </select>
          </div>

          <!-- Tìm kiếm -->
          <div class="col-md-6">
            <label class="form-label fw-bold">
              Tìm kiếm máy trạm / sinh viên
            </label>

            <input
              v-model="search"
              class="form-control"
              type="text"
              placeholder="Nhập tên máy, sinh viên hoặc MSSV"
            />
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
      {{ error }}
    </div>

    <!-- Đang tải -->
    <div
      v-if="loading || loadingPhien"
      class="text-center py-5"
    >
      <div class="spinner-border text-primary"></div>

      <p class="text-muted mt-3">
        Đang tải dữ liệu giám sát...
      </p>
    </div>

    <!-- Chưa chọn phiên -->
    <div
      v-else-if="!error && !selectedPhienId"
      class="alert alert-info"
    >
      Vui lòng chọn phiên giám sát để xem ảnh màn hình.
    </div>

    <!-- Danh sách rỗng -->
    <div
      v-else-if="!error && filteredImages.length === 0"
      class="text-center py-5"
    >
      <i class="bi bi-images fs-1 text-muted"></i>

      <h5 class="mt-3">
        Không có ảnh màn hình
      </h5>

      <p class="text-muted">
        {{
          danhSachAnh.length === 0
            ? 'Phiên này chưa có ảnh màn hình từ hệ thống.'
            : 'Không tìm thấy máy trạm phù hợp.'
        }}
      </p>
    </div>

    <!-- Grid ảnh -->
    <div v-else-if="!error" class="row g-3">
      <div
        v-for="(item, index) in filteredImages"
        :key="item.id ?? index"
        class="col-md-6 col-xl-4"
      >
        <div class="card border-0 shadow-sm h-100">

          <!-- Ảnh màn hình -->
          <div class="screen-preview bg-dark">
            <img
              v-if="getImageUrl(item) && !imageErrors[getImageKey(item)]"
              :src="getImageUrl(item)"
              :alt="`Ảnh màn hình ${item.tenMay || 'máy trạm'}`"
              class="screen-image"
              @error="markImageError(item)"
            />

            <div
              v-else
              class="no-image text-center"
            >
              <i class="bi bi-display fs-1 d-block mb-2"></i>

              <span class="small">
                {{
                  imageErrors[getImageKey(item)]
                    ? 'Không thể tải ảnh'
                    : 'Chưa có ảnh màn hình'
                }}
              </span>
            </div>
          </div>

          <!-- Thông tin -->
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="fw-bold mb-1">
                  {{ item.tenMay || 'Máy chưa xác định' }}
                </h6>

                <div class="small text-muted">
                  {{ item.tenSinhVien || 'Chưa có thông tin' }}
                </div>

                <div class="small text-muted">
                  MSSV: {{ item.mssv || 'Chưa cập nhật' }}
                </div>
              </div>

              <span
                class="badge"
                :class="getStatus(item).className"
              >
                {{ getStatus(item).text }}
              </span>
            </div>

            <hr />

            <div class="small text-muted mb-3">
              <i class="bi bi-clock me-1"></i>
              Cập nhật:
              {{ formatDate(item.thoiGianCapNhat) }}
            </div>

            <button
              class="btn btn-outline-primary btn-sm w-100"
              :disabled="!getImageUrl(item) || !!imageErrors[getImageKey(item)]"
              @click="phongToAnh(item)"
            >
              <i class="bi bi-zoom-in me-1"></i>
              Phóng to ảnh
            </button>
          </div>

        </div>
      </div>
    </div>

    <!-- Modal phóng to -->
    <div
      v-if="selectedImage"
      class="image-modal"
      role="dialog"
      aria-modal="true"
      aria-label="Xem ảnh màn hình"
      @click.self="closeImage"
    >
      <div class="image-modal-content">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="text-white">
            <h5 class="mb-1">
              {{ selectedImage.tenMay || 'Máy trạm' }}
            </h5>

            <div class="small">
              {{ selectedImage.tenSinhVien || 'Chưa cập nhật' }}
            </div>
          </div>

          <button
            class="btn btn-light btn-sm"
            @click="closeImage"
          >
            Đóng
          </button>
        </div>

        <img
          :src="getImageUrl(selectedImage)"
          class="enlarged-image"
          alt="Ảnh màn hình phóng to"
          @error="closeImage"
        />

        <p class="text-white-50 small mt-2 text-center">
          Thời gian chụp:
          {{ formatDate(selectedImage.thoiGianCapNhat) }}
        </p>

      </div>
    </div>

  </div>
</template>

<script>
import giangVienService from '../../../services/giangVienService'

export default {
  name: 'AnhManHinh',

  data() {
    return {
      danhSachPhien: [],
      danhSachAnh: [],

      selectedPhienId: '',
      selectedImage: null,

      search: '',
      imageErrors: {},

      loadingPhien: false,
      loading: false,
      error: '',

      requestId: 0
    }
  },

  computed: {
    filteredImages() {
      const keyword = this.normalizeText(this.search)

      return this.danhSachAnh.filter(item => {
        const name = this.normalizeText(item.tenSinhVien)
        const studentId = this.normalizeText(item.mssv)
        const machine = this.normalizeText(item.tenMay)

        return (
          name.includes(keyword) ||
          studentId.includes(keyword) ||
          machine.includes(keyword)
        )
      })
    }
  },

 

  mounted() {
  const params = new URLSearchParams(window.location.search)
  const phienId = params.get('phienId')

  this.selectedPhienId = phienId || ''

  this.fetchPhienGiamSat()
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

      if (Array.isArray(value?.data)) {
        return value.data
      }

      throw new Error('API không trả về danh sách hợp lệ')
    },

    // Tải danh sách phiên
    async fetchPhienGiamSat() {
      this.loadingPhien = true
      this.error = ''

      try {
        const response =
          await giangVienService.getPhienGiamSat()

        this.danhSachPhien = this.extractList(response)

        if (this.selectedPhienId) {
          const exists = this.danhSachPhien.some(
            phien => String(phien.id) === this.selectedPhienId
          )

          if (exists) {
            await this.fetchAnhManHinh()
          } else {
            this.selectedPhienId = ''
            this.danhSachAnh = []
            this.error = 'Không tìm thấy phiên giám sát được yêu cầu.'
          }
        }

      } catch (err) {
        console.error('Lỗi tải phiên giám sát:', err)

        this.error =
          'Không thể tải danh sách phiên giám sát.'
      } finally {
        this.loadingPhien = false
      }
    },

    // Tải ảnh màn hình theo phiên
    async fetchAnhManHinh() {
      const currentRequest = ++this.requestId

      this.error = ''
      this.danhSachAnh = []
      this.imageErrors = {}
      this.selectedImage = null

      if (!this.selectedPhienId) {
        this.loading = false
        return
      }

      this.loading = true

      try {
        const response =
          await giangVienService.getAnhManHinh({
            phienId: this.selectedPhienId
          })

        if (currentRequest !== this.requestId) {
          return
        }

        this.danhSachAnh = this.extractList(response)

      } catch (err) {
        if (currentRequest !== this.requestId) {
          return
        }

        console.error('Lỗi tải ảnh màn hình:', err)

        this.error =
          'Không thể tải ảnh màn hình. ' +
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

    // Lấy đường dẫn ảnh an toàn
    getImageUrl(item) {
      const url = item?.imageUrl

      if (typeof url !== 'string') {
        return ''
      }

      const value = url.trim()

      if (
        /^https?:\/\//i.test(value) ||
        (value.startsWith('/') && !value.startsWith('//')) ||
        /^data:image\/(png|jpeg|webp|gif);base64,/i.test(value)
      ) {
        return value
      }

      return ''
    },

    getImageKey(item) {
      return String(item.id ?? item.tenMay ?? '')
    },

    markImageError(item) {
      this.imageErrors = {
        ...this.imageErrors,
        [this.getImageKey(item)]: true
      }
    },

    // Phóng to ảnh
    phongToAnh(item) {
      if (
        !this.getImageUrl(item) ||
        this.imageErrors[this.getImageKey(item)]
      ) {
        return
      }

      this.selectedImage = item
    },

    closeImage() {
      this.selectedImage = null
    },

    // Trạng thái máy
    getStatus(item) {
      const status = String(item.trangThai ?? '').toUpperCase()

      if (status === 'ONLINE') {
        return {
          text: 'Online',
          className: 'bg-success'
        }
      }

      if (status === 'OFFLINE') {
        return {
          text: 'Offline',
          className: 'bg-secondary'
        }
      }

      return {
        text: 'Chưa xác định',
        className: 'bg-warning text-dark'
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
.anh-man-hinh-container {
  min-height: 400px;
}

.screen-preview {
  position: relative;
  width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  border-radius: 8px 8px 0 0;
}

.screen-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.no-image {
  width: 100%;
  height: 100%;
  color: #adb5bd;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.image-modal {
  position: fixed;
  inset: 0;
  z-index: 2000;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.image-modal-content {
  width: 100%;
  max-width: 1100px;
  max-height: 95vh;
  overflow-y: auto;
}

.enlarged-image {
  display: block;
  width: 100%;
  max-height: 75vh;
  object-fit: contain;
  background: #111;
  border-radius: 8px;
}
</style>
