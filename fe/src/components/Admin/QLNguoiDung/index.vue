<template>
  <div class="admin-content user-page">

    <!-- TIÊU ĐỀ PAGE -->
    <div class="page-heading">
      <h1>Quản lý người dùng</h1>
      <p>Quản lý tài khoản cố vấn học tập, sinh viên, lớp trưởng và bí thư trong hệ thống.</p>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar flex items-center gap-4 flex-nowrap overflow-x-auto w-full pb-2">

      <!-- SEARCH BOX -->
      <div class="search-box flex items-center shrink-0 flex-1 min-w-[250px]">
        <input v-model="search" @input="resetPage" type="text" placeholder="Tìm theo họ tên, email hoặc lớp..."
          class="outline-none bg-transparent w-full" />
      </div>

      <!-- FILTER SELECT  VAI TRÒ -->
      <div class="filter-select shrink-0">
        <select v-model="filterRole" @change="resetPage" class="cursor-pointer outline-none">
          <option value="all">Tất cả vai trò hiển thị</option>
          <option value="co_van">Cố vấn học tập</option>
          <option value="sinh_vien">Sinh viên</option>
          <option value="lop_truong">Lớp trưởng</option>
          <option value="bi_thu">Bí thư</option>
        </select>
      </div>

      <!-- FILTER SELECT TRẠNG THÁI-->
      <div class="filter-select shrink-0">
        <select v-model="filterStatus" @change="resetPage" class="cursor-pointer outline-none">
          <option value="all">Tất cả trạng thái</option>
          <option value="hoat_dong">Hoạt động</option>
          <option value="khong_hoat_dong">Không hoạt động</option>
          <option value="bi_khoa">Bị khóa</option>
        </select>
      </div>

      <!-- ADD BUTTON -->
      <button class="btn-primary flex items-center justify-center gap-2 text-nowrap shrink-0 ml-auto" @click="openAdd"
        data-bs-toggle="modal" data-bs-target="#userFormModal">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
          <line x1="12" y1="5" x2="12" y2="19" />
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Thêm người dùng
      </button>
    </div>

    <!-- TABLE -->
    <div class="table-wrap">
      <table class="data-table w-full text-left border-collapse">

        <!-- TABLE HEAD -->
        <thead>
          <tr class="border-b">
            <th style="width:48px" class="text-left align-middle text-nowrap py-3">STT</th>
            <th class="text-left align-middle text-nowrap py-3">Họ tên</th>
            <th class="text-left align-middle text-nowrap py-3">Lớp</th>
            <th class="text-left align-middle text-nowrap py-3">Vai trò</th>
            <th class="text-left align-middle text-nowrap py-3">Trạng thái</th>
            <th style="width:200px" class="text-left align-middle text-nowrap py-3">Thao tác</th>
          </tr>
        </thead>

        <!-- TABLE BODY -->
        <tbody>

          <tr v-for="(u, i) in paged" :key="u.id" class="border-b hover:bg-gray-50">

            <!-- ROW ID -->
            <td class="align-middle py-2">{{ (currentPage - 1) * pageSize + i + 1 }}</td>

            <!-- ROW USER -->
            <td class="align-middle py-2">
              <div class="cell-user flex items-center gap-3">
                <div class="cell-avatar shrink-0">{{ initials(u.ho_ten) }}</div>
                <div class="cell-name text-nowrap">
                  <strong class="block">{{ u.ho_ten }}</strong>
                  <p class="block text-sm text-gray-500 text-secondary">{{ u.email }}</p>
                </div>
              </div>
            </td>

            <!-- ROW CLASS -->
            <td class="align-middle text-nowrap py-2">{{ hienThiMaLop(u) }}</td>

            <!-- ROW ROLE -->
            <td class="align-middle text-nowrap py-2">
              <span class="role-chip" :class="'role-' + displayRole(u)">
                {{ ROLE_LABELS[displayRole(u)] }}
              </span>
            </td>

            <!-- ROW STATUS -->
            <td class="align-middle py-3">
              <span class="status-dot text-nowrap" :class="'st-' + u.trang_thai">
                <span class="ms-3">{{ STATUS_LABELS[u.trang_thai] }}</span>
              </span>
            </td>

            <!-- ROW ACTIONS -->
            <td class="align-middle py-2">
              <div class="row-actions flex items-center gap-2 text-nowrap">

                <!-- EDIT BUTTON -->
                <button class="icon-btn edit" title="Sửa" @click="openEdit(u)" data-bs-toggle="modal"
                  data-bs-target="#userFormModal">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>

                <!-- LOCK BUTTON -->
                <button v-if="u.trang_thai !== 'bi_khoa'" class="icon-btn lock" title="Khóa tài khoản"
                  @click="prepareLock(u)" data-bs-toggle="modal" data-bs-target="#userLockModal">
                  <i class="fa-solid fa-lock"></i>
                </button>

                <!-- UNLOCK BUTTON -->
                <button v-else class="icon-btn unlock" title="Mở khóa" @click="prepareLock(u)" data-bs-toggle="modal"
                  data-bs-target="#userLockModal">
                  <i class="fa-solid fa-lock-open"></i>
                </button>

                <!-- DELETE BUTTON -->
                <button class="icon-btn del" title="Xóa" @click="prepareDelete(u)" data-bs-toggle="modal"
                  data-bs-target="#userDeleteModal">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>
            </td>
          </tr>

          <!-- EMPTY ROW -->
          <tr v-if="paged.length === 0">
            <td colspan="6" class="empty-row text-center py-4">Không tìm thấy người dùng nào phù hợp.</td>
          </tr>
        </tbody>
      </table>

      <!-- NÚT CHUYỂN TRANG -->
      <div class="text-end">
        <div class="pagination">
          <div class="pagination-left">
            <span>
              Hiển thị <strong>{{ paged.length }}</strong> / <strong>{{ filtered.length }}</strong>
              người dùng
            </span>

            <select class="page-size" v-model.number="pageSize" @change="resetPage">
              <option :value="5">5 dòng</option>
              <option :value="10">10 dòng</option>
              <option :value="20">20 dòng</option>
              <option :value="50">50 dòng</option>
            </select>
          </div>

          <div class="pagination-right">
            <button class="page-btn page-nav" :disabled="currentPage === 1" @click="goPage(currentPage - 1)">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>

            <button v-for="p in totalPages" :key="p" class="page-btn" :class="{ 'is-active': p === currentPage }"
              @click="goPage(p)">
              {{ p }}
            </button>

            <button class="page-btn page-nav" :disabled="currentPage === totalPages" @click="goPage(currentPage + 1)">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal form Sửa người dùng -->
    <div class="modal fade" id="userFormModal" tabindex="-1" aria-labelledby="userFormModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <form @submit.prevent="saveForm">
            <div class="modal-header">
              <div>
                <h1 class="modal-title fs-5" id="userFormModalLabel">{{ isEditing ? 'Sửa người dùng' :
                  'Thêm người dùng' }}</h1>
                <p class="text-sm text-gray-500 mb-0">Thông tin chính để quản lý nhanh.</p>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>Họ tên <span class="req">*</span></label>
                  <input v-model="form.ho_ten" type="text" placeholder="Nhập họ tên" />
                  <span v-if="errors.ho_ten" class="form-error">{{ errors.ho_ten }}</span>
                </div>

                <div class="form-group">
                  <label>Email <span class="req">*</span></label>
                  <input v-model="form.email" type="email" placeholder="Nhập email" />
                  <span v-if="errors.email" class="form-error">{{ errors.email }}</span>
                </div>

                <div class="form-group">
                  <label>Số điện thoại</label>
                  <input v-model="form.so_dien_thoai" type="text" placeholder="Nhập số điện thoại" />
                </div>

                <div class="form-group">
                  <label>Ngày sinh</label>
                  <input v-model="form.ngay_sinh" type="date" />
                </div>

                <div class="form-group">
                  <label>Vai trò hệ thống</label>
                  <select v-model="form.vai_tro" @change="handleRoleChange">
                    <option value="co_van">Cố vấn học tập</option>
                    <option value="sinh_vien">Sinh viên</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>{{ form.vai_tro === 'co_van' ? 'Lớp phụ trách' : 'Lớp' }} <span class="req">*</span></label>
                  <input v-model="form.lop" type="text"
                    :placeholder="form.vai_tro === 'co_van' ? 'Ví dụ: D23CQCN01, D23CQCN02' : 'Ví dụ: D23CQCN01'" />
                  <span v-if="errors.lop" class="form-error">{{ errors.lop }}</span>
                </div>

                <div v-if="form.vai_tro === 'sinh_vien'" class="form-group">
                  <label>Chức vụ lớp</label>
                  <select v-model="form.chuc_vu_lop">
                    <option :value="null">Sinh viên thường</option>
                    <option value="lop_truong">Lớp trưởng</option>
                    <option value="bi_thu">Bí thư</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Trạng thái</label>
                  <select v-model="form.trang_thai">
                    <option value="hoat_dong">Hoạt động</option>
                    <option value="khong_hoat_dong">Không hoạt động</option>
                    <option value="bi_khoa">Bị khóa</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
              <button type="submit" class="btn btn-primary">{{ isEditing ? 'Cập nhật' : 'Thêm mới'
              }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal form Khoa người dùng -->
    <div class="modal fade" id="userLockModal" tabindex="-1" aria-labelledby="userLockModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h1 class="modal-title fs-5" id="userLockModalLabel">
                {{ lockTarget?.trang_thai === 'bi_khoa' ? 'Mở khóa tài khoản' : 'Khóa tài khoản' }}
              </h1>
              <p class="text-sm text-gray-500 mb-0">
                {{ lockTarget?.trang_thai === 'bi_khoa' ? 'Tài khoản sẽ hoạt động lại sau khi mở khóa.'
                  : 'Tài khoản sẽ không thể đăng nhập sau khi khóa.' }}
              </p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
          </div>
          <div class="modal-body">
            <p>
              Bạn có chắc muốn {{ lockTarget?.trang_thai === 'bi_khoa' ? 'mở khóa' : 'khóa' }}
              <strong>{{ lockTarget?.ho_ten }}</strong> không?
            </p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="button" class="btn btn-primary" @click="confirmLock">
              {{ lockTarget?.trang_thai === 'bi_khoa' ? 'Mở khóa' : 'Khóa ngay' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal form Xóa người dùng -->
    <div class="modal fade" id="userDeleteModal" tabindex="-1" aria-labelledby="userDeleteModalLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h1 class="modal-title fs-5" id="userDeleteModalLabel">Xóa người dùng</h1>
              <p class="text-sm text-gray-500 mb-0">Hành động này không thể hoàn tác.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
          </div>
          <div class="modal-body">
            <p>Bạn có chắc muốn xóa <strong>{{ deleteTarget?.ho_ten }}</strong> khỏi hệ thống không?</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
            <button type="button" class="btn btn-primary" @click="confirmDelete">Xóa</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import axios from 'axios'

const ROLE_MAP = {
  1: 'admin',
  2: 'phong_qldt',
  3: 'co_van',
  4: 'lop_truong',
  5: 'bi_thu',
  6: 'sinh_vien',
}

const ROLE_KEY_TO_ID = {
  admin: 1,
  phong_qldt: 2,
  co_van: 3,
  lop_truong: 4,
  bi_thu: 5,
  sinh_vien: 6,
}

const ROLE_LABELS = {
  admin: 'Admin',
  phong_qldt: 'Phòng QLĐT',
  co_van: 'Cố vấn học tập',
  lop_truong: 'Lớp trưởng',
  bi_thu: 'Bí thư',
  sinh_vien: 'Sinh viên',
}

const STATUS_LABELS = {
  hoat_dong: 'Hoạt động',
  khong_hoat_dong: 'Không hoạt động',
  bi_khoa: 'Bị khóa',
}

export default {
  data() {
    return {



      ROLE_LABELS,
      STATUS_LABELS,

      list_nguoi_dung: [],
      list_lop: [],
      them_nguoi_dung: {
        ho_ten: '',
        email: '',
        password: '',
        so_dien_thoai: '',
        ngay_sinh: '',
        avatar: '',
        vai_tro_id: ROLE_KEY_TO_ID.sinh_vien,
        lop: '',
        chuc_vu_lop: null,
        trang_thai: 'hoat_dong',
      },
      xoa_nguoi_dung: {
        id: null,
      },
      chi_tiet_nguoi_dung: {},
      sua_nguoi_dung: {
        id: null,
        ho_ten: '',
        email: '',
        password: '',
        so_dien_thoai: '',
        ngay_sinh: '',
        avatar: '',
        vai_tro_id: ROLE_KEY_TO_ID.sinh_vien,
        lop: '',
        chuc_vu_lop: null,
        trang_thai: 'hoat_dong',
      },
      tim_kiem: {
        tim_nguoi_dung: '',
      },
      form: {
        id: null,
        ho_ten: '',
        email: '',
        password: '',
        so_dien_thoai: '',
        ngay_sinh: '',
        avatar: '',
        vai_tro: 'sinh_vien',
        lop: '',
        chuc_vu_lop: null,
        trang_thai: 'hoat_dong',
      },
      search: '',
      filterRole: 'all',
      filterStatus: 'all',
      currentPage: 1,
      pageSize: 10,
      isEditing: false,
      errors: {},
      lockTarget: null,
      deleteTarget: null,
    }
  },
  mounted() {
    this.getNguoiDung()
    this.layDanhSachLop()
  },
  computed: {
    filtered() {
      const keyword = this.search.trim().toLowerCase()
      return this.list_nguoi_dung.filter((user) => {
        if (this.filterRole !== 'all') {
          const roleKey = ROLE_MAP[user.vai_tro_id] || 'sinh_vien'
          if (roleKey !== this.filterRole) {
            return false
          }
        }
        if (this.filterStatus !== 'all') {
          if ((user.trang_thai || '') !== this.filterStatus) {
            return false
          }
        }
        if (!keyword) {
          return true
        }
        return (
          (user.ho_ten || '').toLowerCase().includes(keyword) ||
          (user.email || '').toLowerCase().includes(keyword) ||
          (user.so_dien_thoai || '').toLowerCase().includes(keyword) ||
          ((user.lop || '').toLowerCase().includes(keyword))
        )
      })
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.filtered.length / this.pageSize))
    },
    paged() {
      const start = (this.currentPage - 1) * this.pageSize
      return this.filtered.slice(start, start + this.pageSize)
    },
  },
  methods: {
    layDataChucVu() {
      axios
        .get('http://127.0.0.1:8000/api/admin/chuc-vu/get-data', {
          headers: {
            Authorization: 'Bearer ' + sessionStorage.getItem('auth_token'),
          },
        })
        .then((res) => {
          this.list_chuc_vu = res.data.data || []
        })
        .catch((err) => {
          console.error('layDataChucVu', err)
        })
    },

    getNguoiDung() {
      axios
        .get('http://localhost:8000/api/admin/nguoi-dung', {
          headers: { Authorization: 'Bearer ' + sessionStorage.getItem('auth_token') },
        })
        .then((res) => {
          if (res.data.status) {
            this.list_nguoi_dung = res.data.data || []
          } else {
            this.$toast?.error(res.data.message || 'Lấy người dùng thất bại.')
          }
        })
        .catch((err) => {
          console.error('getNguoiDung', err)
          this.$toast?.error('Lấy người dùng thất bại.')
        })
    },
    themNguoiDung() {
      axios
        .post('http://localhost:8000/api/admin/nguoi-dung/them-nguoi-dung', this.them_nguoi_dung, {
          headers: { Authorization: 'Bearer ' + sessionStorage.getItem('auth_token') },
        })
        .then((res) => {
          if (res.data.status) {
            this.$toast?.success(res.data.message || 'Thêm người dùng thành công!')
            this.getNguoiDung()

            this.them_nguoi_dung = {
              ho_ten: '',
              email: '',
              password: '',
              so_dien_thoai: '',
              ngay_sinh: '',
              avatar: '',
              vai_tro_id: ROLE_KEY_TO_ID.sinh_vien,
              lop: '',
              chuc_vu_lop: null,
              trang_thai: 'hoat_dong',
            }

            this.form = {
              id: null,
              ho_ten: '',
              email: '',
              password: '',
              so_dien_thoai: '',
              ngay_sinh: '',
              avatar: '',
              vai_tro: 'sinh_vien',
              lop: '',
              chuc_vu_lop: null,
              trang_thai: 'hoat_dong',
            }
          } else {
            this.$toast?.error(res.data.message || 'Thêm người dùng thất bại.')
          }
        })
        .catch((err) => {
          console.log('LOI THEM NGUOI DUNG:', err.response?.data || err)

          if (err.response?.data?.errors) {
            const errors = err.response.data.errors
            const firstKey = Object.keys(errors)[0]
            const firstMessage = errors[firstKey][0]

            this.$toast?.error(firstMessage || 'Dữ liệu không hợp lệ.')
            return
          }

          this.$toast?.error(err.response?.data?.message || 'Thêm người dùng thất bại.')
        })
    },
    chiTietNguoiDung() {
      axios
        .get('http://localhost:8000/api/admin/nguoi-dung/chi-tiet-nguoi-dung', {
          headers: { Authorization: 'Bearer ' + sessionStorage.getItem('auth_token') },
        })
        .then((res) => {
          if (res.data.status) {
            this.chi_tiet_nguoi_dung = res.data.data
          } else {
            this.$toast?.error(res.data.message || 'Lấy chi tiết người dùng thất bại.')
          }
        })
        .catch(() => {
          this.$toast?.error('Lấy chi tiết người dùng thất bại.')
        })
    },
    xoaNguoiDung() {
      axios
        .post('http://127.0.0.1:8000/api/admin/nguoi-dung/xoa-nguoi-dung', this.xoa_nguoi_dung, {
          headers: { Authorization: 'Bearer ' + sessionStorage.getItem('auth_token') },
        })
        .then((res) => {
          if (res.data.status) {
            this.$toast?.success(res.data.message)
            this.getNguoiDung()
          } else {
            this.$toast?.error(res.data.message || 'Xóa người dùng thất bại.')
          }
        })
        .catch(() => {
          this.$toast?.error('Xóa người dùng thất bại!')
        })
    },
    suaNguoiDung() {
      axios
        .post('http://localhost:8000/api/admin/nguoi-dung/sua-nguoi-dung', this.sua_nguoi_dung, {
          headers: { Authorization: 'Bearer ' + sessionStorage.getItem('auth_token') },
        })
        .then((res) => {
          if (res.data.status) {
            this.$toast?.success(res.data.message || 'Cập nhật người dùng thành công!')
            this.getNguoiDung()
          } else {
            this.$toast?.error(res.data.message || 'Sửa người dùng thất bại.')
          }
        })
        .catch((err) => {
          console.log('LOI SUA NGUOI DUNG:', err.response?.data || err)

          if (err.response?.data?.errors) {
            const errors = err.response.data.errors
            const firstKey = Object.keys(errors)[0]
            const firstMessage = errors[firstKey][0]

            this.$toast?.error(firstMessage || 'Dữ liệu không hợp lệ.')
            return
          }

          this.$toast?.error(err.response?.data?.message || 'Sửa người dùng thất bại.')
        })
    },
    timKiem() {
      axios
        .post('http://127.0.0.1:8000/api/admin/nguoi-dung/tim-kiem', this.tim_kiem, {
          headers: { Authorization: 'Bearer ' + sessionStorage.getItem('auth_token') },
        })
        .then((res) => {
          this.list_nguoi_dung = res.data.data || []
        })
        .catch(() => {
          this.$toast?.error('Tìm kiếm người dùng thất bại.')
        })
    },
    openAdd() {
      this.isEditing = false
      this.form = {
        id: null,
        ho_ten: '',
        email: '',
        password: '',
        so_dien_thoai: '',
        ngay_sinh: '',
        avatar: '',
        vai_tro: 'sinh_vien',
        lop: '',
        chuc_vu_lop: null,
        trang_thai: 'hoat_dong',
      }
    },
    openEdit(user) {
      this.isEditing = true
      this.form = {
        id: user.id,
        ho_ten: user.ho_ten || '',
        email: user.email || '',
        password: '',
        so_dien_thoai: user.so_dien_thoai || '',
        ngay_sinh: user.ngay_sinh || '',
        avatar: user.avatar || '',
        vai_tro: ROLE_MAP[user.vai_tro_id] || 'sinh_vien',
        lop: user.lop || '',
        chuc_vu_lop: user.chuc_vu_lop || null,
        trang_thai: user.trang_thai || 'hoat_dong',
      }
      this.sua_nguoi_dung = { ...this.form, vai_tro_id: ROLE_KEY_TO_ID[this.form.vai_tro] }
    },
    saveForm() {
      if (this.isEditing) {
        this.sua_nguoi_dung = {
          id: this.form.id,
          ho_ten: this.form.ho_ten,
          email: this.form.email,
          password: this.form.password,
          so_dien_thoai: this.form.so_dien_thoai,
          ngay_sinh: this.form.ngay_sinh,
          avatar: this.form.avatar,
          vai_tro_id: ROLE_KEY_TO_ID[this.form.vai_tro] || ROLE_KEY_TO_ID.sinh_vien,
          lop: this.form.lop,
          chuc_vu_lop: this.form.chuc_vu_lop,
          trang_thai: this.form.trang_thai,
        }
        this.suaNguoiDung()
      } else {
        this.them_nguoi_dung = {
          ho_ten: this.form.ho_ten,
          email: this.form.email,
          password: this.form.password || '123456',
          so_dien_thoai: this.form.so_dien_thoai,
          ngay_sinh: this.form.ngay_sinh,
          avatar: this.form.avatar,
          vai_tro_id: ROLE_KEY_TO_ID[this.form.vai_tro] || ROLE_KEY_TO_ID.sinh_vien,
          lop: this.form.lop,
          chuc_vu_lop: this.form.chuc_vu_lop,
          trang_thai: this.form.trang_thai,
        }
        this.themNguoiDung()
      }
    },
    prepareLock(user) {
      this.lockTarget = user
    },
    async confirmLock() {
      if (!this.lockTarget) {
        return
      }
      const newStatus = this.lockTarget.trang_thai === 'bi_khoa' ? 'hoat_dong' : 'bi_khoa'
      this.sua_nguoi_dung = {
        id: this.lockTarget.id,
        ho_ten: this.lockTarget.ho_ten,
        email: this.lockTarget.email,
        password: '',
        so_dien_thoai: this.lockTarget.so_dien_thoai,
        ngay_sinh: this.lockTarget.ngay_sinh,
        avatar: this.lockTarget.avatar,
        vai_tro_id: this.lockTarget.vai_tro_id,
        lop: this.lockTarget.lop,
        chuc_vu_lop: this.lockTarget.chuc_vu_lop,
        trang_thai: newStatus,
      }
      this.suaNguoiDung()
    },
    prepareDelete(user) {
      this.deleteTarget = user
      this.xoa_nguoi_dung = { id: user.id }
    },
    confirmDelete() {
      this.xoaNguoiDung()
    },
    initials(name) {
      if (!name) return ''
      return name
        .split(' ')
        .map((part) => part.charAt(0).toUpperCase())
        .slice(0, 2)
        .join('')
    },
    displayRole(user) {
      return ROLE_MAP[user.vai_tro_id] || 'sinh_vien'
    },
    goPage(page) {
      if (page < 1) {
        page = 1
      }
      if (page > this.totalPages) {
        page = this.totalPages
      }
      this.currentPage = page
    },
    resetPage() {
      this.currentPage = 1
    },
    handleRoleChange() {
      if (this.form.vai_tro !== 'sinh_vien') {
        this.form.chuc_vu_lop = null
      }
    },
    layDanhSachLop() {
      axios
        .get('http://127.0.0.1:8000/api/admin/lop', {
          headers: {
            Authorization: 'Bearer ' + localStorage.getItem('auth_token'),
          },
        })
        .then((res) => {
          this.list_lop = res.data.data || []
        })
        .catch((err) => {
          console.error('layDanhSachLop', err.response?.data || err)
        })
    },
    hienThiMaLop(user) {
      if (!user) {
        return '—'
      }

      if (user.lop) {
        return user.lop
      }

      let dsLop = []

      if (Number(user.vai_tro_id) === 3) {
        dsLop = this.list_lop.filter((lop) => Number(lop.co_van_id) === Number(user.id))
      }

      if (Number(user.vai_tro_id) === 4) {
        dsLop = this.list_lop.filter((lop) => Number(lop.lop_truong_id) === Number(user.id))
      }

      if (Number(user.vai_tro_id) === 5) {
        dsLop = this.list_lop.filter((lop) => Number(lop.bi_thu_id) === Number(user.id))
      }

      if (dsLop.length > 0) {
        return dsLop.map((lop) => lop.ma_lop).join(', ')
      }

      return '—'
    },
  },
  
}
</script>