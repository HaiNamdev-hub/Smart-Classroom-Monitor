<template>
  <div class="user-page">
    <!-- =========================
         HEADER
    ========================== -->
    <div class="page-header">
      <div>
        <p class="page-label">
          QUẢN LÝ HỆ THỐNG
        </p>

        <h1>Quản lý người dùng</h1>

        <p class="page-description">
          Quản lý tài khoản, vai trò và trạng thái người dùng trong hệ thống.
        </p>
      </div>

      <button
        class="btn-add"
        @click="openCreateModal"
      >
        <span>+</span>
        Thêm người dùng
      </button>
    </div>

    <!-- =========================
         STATISTICS
    ========================== -->
    <div class="stats">
      <!-- Tổng người dùng -->
      <div class="stat-card">
        <div class="stat-icon blue">
          ♙
        </div>

        <div>
          <span>Tổng người dùng</span>
          <h3>{{ totalUsers }}</h3>
          <p>Tài khoản trong hệ thống</p>
        </div>
      </div>

      <!-- Giảng viên -->
      <div class="stat-card">
        <div class="stat-icon purple">
          ♙
        </div>

        <div>
          <span>Giảng viên</span>
          <h3>{{ countRole('giang_vien') }}</h3>
          <p>Trong trang hiện tại</p>
        </div>
      </div>

      <!-- Sinh viên -->
      <div class="stat-card">
        <div class="stat-icon green">
          ♙
        </div>

        <div>
          <span>Sinh viên</span>
          <h3>{{ countRole('sinh_vien') }}</h3>
          <p>Trong trang hiện tại</p>
        </div>
      </div>

      <!-- Đang hoạt động -->
      <div class="stat-card">
        <div class="stat-icon orange">
          ✓
        </div>

        <div>
          <span>Đang hoạt động</span>
          <h3>{{ countStatus('hoat_dong') }}</h3>
          <p>Trong trang hiện tại</p>
        </div>
      </div>
    </div>

    <!-- =========================
         FILTER
    ========================== -->
    <div class="filter-card">
      <!-- Search -->
      <div class="search-box">
        <span>⌕</span>

        <input
          v-model="search"
          type="text"
          placeholder="Tìm theo tên hoặc email..."
        />
      </div>

      <!-- Vai trò -->
      <select v-model="roleFilter">
        <option value="">
          Tất cả vai trò
        </option>

        <option
          v-for="role in roles"
          :key="role.id"
          :value="role.id"
        >
          {{ formatRole(role.ten_vai_tro) }}
        </option>
      </select>

      <!-- Trạng thái -->
      <select v-model="statusFilter">
        <option value="">
          Tất cả trạng thái
        </option>

        <option value="hoat_dong">
          Hoạt động
        </option>

        <option value="khong_hoat_dong">
          Không hoạt động
        </option>

        <option value="bi_khoa">
          Bị khóa
        </option>
      </select>

      <button
        v-if="hasFilter"
        class="btn-reset"
        @click="resetFilters"
      >
        Đặt lại
      </button>
    </div>

    <!-- =========================
         TABLE
    ========================== -->
    <div class="table-card">
      <!-- Loading -->
      <div
        v-if="loading"
        class="state-message"
      >
        Đang tải dữ liệu...
      </div>

      <!-- Error -->
      <div
        v-else-if="errorMessage"
        class="state-message error"
      >
        {{ errorMessage }}

        <button
          class="btn-retry"
          @click="getUsers"
        >
          Thử lại
        </button>
      </div>

      <!-- Empty -->
      <div
        v-else-if="users.length === 0"
        class="state-message"
      >
        Không tìm thấy người dùng.
      </div>

      <!-- Data -->
      <template v-else>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>NGƯỜI DÙNG</th>
                <th>EMAIL</th>
                <th>VAI TRÒ</th>
                <th>TRẠNG THÁI</th>
                <th>NGÀY TẠO</th>
                <th>THAO TÁC</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="user in users"
                :key="user.id"
              >
                <!-- Người dùng -->
                <td>
                  <div class="user-info">
                    <div class="avatar">
                      {{ getInitial(user.ho_ten) }}
                    </div>

                    <div class="user-name">
                      <strong>
                        {{ user.ho_ten }}
                      </strong>

                      <span>
                        ID: #{{ user.id }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Email -->
                <td>
                  <span class="email">
                    {{ user.email }}
                  </span>
                </td>

                <!-- Vai trò -->
                <td>
                  <span
                    class="role-badge"
                    :class="
                      roleClass(
                        user.vai_tro?.ten_vai_tro
                      )
                    "
                  >
                    {{
                      formatRole(
                        user.vai_tro?.ten_vai_tro
                      )
                    }}
                  </span>
                </td>

                <!-- Trạng thái -->
                <td>
                  <span
                    class="status-badge"
                    :class="
                      statusClass(
                        user.trang_thai
                      )
                    "
                  >
                    <span class="dot"></span>

                    {{
                      formatStatus(
                        user.trang_thai
                      )
                    }}
                  </span>
                </td>

                <!-- Ngày tạo -->
                <td>
                  {{ formatDate(user.created_at) }}
                </td>

                <!-- Actions -->
                <td>
                  <div class="actions">
                    <button
                      class="btn-edit"
                      title="Chỉnh sửa"
                      @click="openEditModal(user)"
                    >
                      ✎
                    </button>

                    <button
                      class="btn-delete"
                      title="Xóa"
                      @click="deleteUser(user)"
                    >
                      ♲
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- =========================
             PAGINATION
        ========================== -->
        <div class="pagination-container">
          <div class="pagination-info">
            Hiển thị
            <strong>
              {{ paginationFrom }}
            </strong>
            -
            <strong>
              {{ paginationTo }}
            </strong>
            trong
            <strong>
              {{ totalUsers }}
            </strong>
            người dùng
          </div>

          <div
            v-if="lastPage > 1"
            class="pagination"
          >
            <button
              :disabled="currentPage === 1"
              @click="changePage(currentPage - 1)"
            >
              ‹
            </button>

            <button
              v-for="page in paginationPages"
              :key="page"
              :class="{
                active: page === currentPage
              }"
              @click="changePage(page)"
            >
              {{ page }}
            </button>

            <button
              :disabled="
                currentPage === lastPage
              "
              @click="
                changePage(currentPage + 1)
              "
            >
              ›
            </button>
          </div>
        </div>
      </template>
    </div>

    <!-- =========================
         MODAL ADD / EDIT
    ========================== -->
    <div
      v-if="showModal"
      class="modal-overlay"
      @click.self="closeModal"
    >
      <div class="modal">
        <!-- Modal header -->
        <div class="modal-header">
          <div>
            <h2>
              {{
                editingUser
                  ? 'Chỉnh sửa người dùng'
                  : 'Thêm người dùng'
              }}
            </h2>

            <p>
              {{
                editingUser
                  ? 'Cập nhật thông tin tài khoản người dùng.'
                  : 'Tạo tài khoản mới cho hệ thống.'
              }}
            </p>
          </div>

          <button
            type="button"
            class="close-button"
            @click="closeModal"
          >
            ×
          </button>
        </div>

        <!-- Form -->
        <form @submit.prevent="saveUser">
          <!-- Họ tên -->
          <div class="form-group">
            <label>
              Họ và tên
              <b>*</b>
            </label>

            <input
              v-model="form.ho_ten"
              type="text"
              maxlength="255"
              placeholder="Nhập họ và tên"
              required
            />
          </div>

          <!-- Email -->
          <div class="form-group">
            <label>
              Email
              <b>*</b>
            </label>

            <input
              v-model="form.email"
              type="email"
              maxlength="255"
              placeholder="example@gmail.com"
              required
            />
          </div>

          <!-- Vai trò + trạng thái -->
          <div class="form-row">
            <div class="form-group">
              <label>
                Vai trò
                <b>*</b>
              </label>

              <select
                v-model="form.vai_tro_id"
                required
              >
                <option
                  value=""
                  disabled
                >
                  Chọn vai trò
                </option>

                <option
                  v-for="role in roles"
                  :key="role.id"
                  :value="role.id"
                >
                  {{
                    formatRole(
                      role.ten_vai_tro
                    )
                  }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>
                Trạng thái
                <b>*</b>
              </label>

              <select
                v-model="form.trang_thai"
                required
              >
                <option value="hoat_dong">
                  Hoạt động
                </option>

                <option value="khong_hoat_dong">
                  Không hoạt động
                </option>

                <option value="bi_khoa">
                  Bị khóa
                </option>
              </select>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group">
            <label>
              Mật khẩu

              <b v-if="!editingUser">
                *
              </b>
            </label>

            <input
              v-model="form.password"
              type="password"
              minlength="8"
              maxlength="255"
              :required="!editingUser"
              :placeholder="
                editingUser
                  ? 'Để trống nếu không đổi mật khẩu'
                  : 'Nhập mật khẩu (ít nhất 8 ký tự)'
              "
            />

            <small v-if="editingUser">
              Để trống nếu bạn không muốn thay đổi mật khẩu.
            </small>
          </div>

          <!-- Error modal -->
          <div
            v-if="formError"
            class="form-error"
          >
            {{ formError }}
          </div>

          <!-- Footer -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn-cancel"
              :disabled="saving"
              @click="closeModal"
            >
              Hủy
            </button>

            <button
              type="submit"
              class="btn-save"
              :disabled="saving"
            >
              {{
                saving
                  ? 'Đang lưu...'
                  : editingUser
                    ? 'Lưu thay đổi'
                    : 'Thêm người dùng'
              }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api, {
  apiError,
} from '../../../services/api'

export default {
  data() {
    return {
      /* =========================
         STATE
      ========================== */

      loading: true,
      saving: false,

      errorMessage: '',
      formError: '',

      /* =========================
         DATA
      ========================== */

      users: [],
      roles: [],

      /* =========================
         FILTER
      ========================== */

      search: '',
      roleFilter: '',
      statusFilter: '',

      searchTimer: null,

      /* =========================
         PAGINATION
      ========================== */

      currentPage: 1,
      lastPage: 1,
      totalUsers: 0,
      perPage: 10,

      /* =========================
         MODAL
      ========================== */

      showModal: false,
      editingUser: null,

      /* =========================
         FORM
      ========================== */

      form: {
        ho_ten: '',
        email: '',
        password: '',
        vai_tro_id: '',
        trang_thai: 'hoat_dong',
      },
    }
  },

  /* =========================
     COMPUTED
  ========================== */

  computed: {
    hasFilter() {
      return Boolean(
        this.search ||
        this.roleFilter ||
        this.statusFilter
      )
    },

    paginationPages() {
      const pages = []

      const start = Math.max(
        1,
        this.currentPage - 2
      )

      const end = Math.min(
        this.lastPage,
        this.currentPage + 2
      )

      for (
        let page = start;
        page <= end;
        page++
      ) {
        pages.push(page)
      }

      return pages
    },

    paginationFrom() {
      if (this.totalUsers === 0) {
        return 0
      }

      return (
        (this.currentPage - 1) *
          this.perPage +
        1
      )
    },

    paginationTo() {
      return Math.min(
        this.currentPage *
          this.perPage,
        this.totalUsers
      )
    },
  },

  /* =========================
     WATCH
  ========================== */

  watch: {
    search() {
      clearTimeout(
        this.searchTimer
      )

      this.searchTimer =
        setTimeout(() => {
          this.currentPage = 1
          this.getUsers()
        }, 400)
    },

    roleFilter() {
      this.currentPage = 1
      this.getUsers()
    },

    statusFilter() {
      this.currentPage = 1
      this.getUsers()
    },
  },

  /* =========================
     MOUNTED
  ========================== */

  mounted() {
    this.getData()
  },

  beforeUnmount() {
    clearTimeout(
      this.searchTimer
    )
  },

  methods: {
    /* =========================
       INITIAL DATA
    ========================== */

    async getData() {
      this.loading = true
      this.errorMessage = ''

      try {
        await Promise.all([
          this.getUsers(false),
          this.getRoles(),
        ])
      } catch (error) {
        this.errorMessage =
          apiError(error)

        console.error(
          'Lỗi tải dữ liệu:',
          error
        )
      } finally {
        this.loading = false
      }
    },

    /* =========================
       GET USERS
    ========================== */

    async getUsers(
      showLoading = true
    ) {
      if (showLoading) {
        this.loading = true
      }

      this.errorMessage = ''

      try {
        const params = {
          page: this.currentPage,
        }

        /* Search */

        const keyword =
          this.search.trim()

        if (keyword) {
          params.search = keyword
        }

        /* Role filter */

        if (this.roleFilter) {
          params.vai_tro_id =
            this.roleFilter
        }

        /* Status filter */

        if (this.statusFilter) {
          params.trang_thai =
            this.statusFilter
        }

        const {
          data,
        } = await api.get(
          '/admin/nguoi-dung',
          {
            params,
          }
        )

        /*
          Laravel paginate(10)

          {
            current_page: 1,
            data: [],
            last_page: 1,
            per_page: 10,
            total: 0
          }
        */

        this.users =
          data.data ?? []

        this.currentPage =
          data.current_page ?? 1

        this.lastPage =
          data.last_page ?? 1

        this.totalUsers =
          data.total ?? 0

        this.perPage =
          data.per_page ?? 10

      } catch (error) {
        this.errorMessage =
          apiError(error)

        console.error(
          'Lỗi tải người dùng:',
          error
        )
      } finally {
        if (showLoading) {
          this.loading = false
        }
      }
    },

    /* =========================
       GET ROLES
    ========================== */

    async getRoles() {
      try {
        const {
          data,
        } = await api.get(
          '/admin/vai-tro'
        )

        /*
          Hỗ trợ:
          []
          { data: [] }
          { vai_tro: [] }
        */

        if (Array.isArray(data)) {
          this.roles = data
          return
        }

        this.roles =
          data.data ??
          data.vai_tro ??
          []

      } catch (error) {
        console.error(
          'Lỗi tải vai trò:',
          error
        )

        throw error
      }
    },

    /* =========================
       RESET FILTER
    ========================== */

    resetFilters() {
      /*
        Tạm ngắt watcher search để
        tránh request dư.
      */

      clearTimeout(
        this.searchTimer
      )

      this.search = ''
      this.roleFilter = ''
      this.statusFilter = ''
      this.currentPage = 1

      /*
        Watchers có thể tự gọi lại.
        Request này đảm bảo dữ liệu
        được tải ngay.
      */

      this.getUsers()
    },

    /* =========================
       PAGINATION
    ========================== */

    changePage(page) {
      if (
        page < 1 ||
        page > this.lastPage ||
        page === this.currentPage
      ) {
        return
      }

      this.currentPage = page

      this.getUsers()
    },

    /* =========================
       OPEN CREATE
    ========================== */

    openCreateModal() {
      this.editingUser = null
      this.formError = ''

      this.form = {
        ho_ten: '',
        email: '',
        password: '',
        vai_tro_id: '',
        trang_thai: 'hoat_dong',
      }

      this.showModal = true
    },

    /* =========================
       OPEN EDIT
    ========================== */

    openEditModal(user) {
      this.editingUser = user
      this.formError = ''

      this.form = {
        ho_ten:
          user.ho_ten ?? '',

        email:
          user.email ?? '',

        password: '',

        vai_tro_id:
          user.vai_tro_id ??
          user.vai_tro?.id ??
          '',

        trang_thai:
          user.trang_thai ??
          'hoat_dong',
      }

      this.showModal = true
    },

    /* =========================
       SAVE USER
    ========================== */

    async saveUser() {
      if (this.saving) {
        return
      }

      this.formError = ''

      /* =========================
         FRONTEND VALIDATION
      ========================== */

      const hoTen =
        this.form.ho_ten.trim()

      const email =
        this.form.email.trim()

      if (!hoTen) {
        this.formError =
          'Vui lòng nhập họ và tên.'

        return
      }

      if (!email) {
        this.formError =
          'Vui lòng nhập email.'

        return
      }

      if (!this.form.vai_tro_id) {
        this.formError =
          'Vui lòng chọn vai trò.'

        return
      }

      if (
        !this.editingUser &&
        this.form.password.length < 8
      ) {
        this.formError =
          'Mật khẩu phải có ít nhất 8 ký tự.'

        return
      }

      if (
        this.editingUser &&
        this.form.password &&
        this.form.password.length < 8
      ) {
        this.formError =
          'Mật khẩu mới phải có ít nhất 8 ký tự.'

        return
      }

      this.saving = true

      try {
        const payload = {
          ho_ten: hoTen,
          email: email,

          vai_tro_id:
            this.form.vai_tro_id,

          trang_thai:
            this.form.trang_thai,
        }

        /* =========================
           PASSWORD
        ========================== */

        if (
          !this.editingUser ||
          this.form.password
        ) {
          payload.password =
            this.form.password
        }

        /* =========================
           UPDATE
        ========================== */

        if (this.editingUser) {
          await api.post(
            `/admin/nguoi-dung/sua-nguoi-dung/${this.editingUser.id}`,
            payload
          )
        }

        /* =========================
           CREATE
        ========================== */

        else {
          await api.post(
            '/admin/nguoi-dung/them-nguoi-dung',
            payload
          )

          /*
            Backend sắp xếp latest(id)
            nên người mới nằm trang 1.
          */

          this.currentPage = 1
        }

        this.closeModal()

        await this.getUsers()

      } catch (error) {
        this.formError =
          apiError(error)

        console.error(
          'Lỗi lưu người dùng:',
          error
        )
      } finally {
        this.saving = false
      }
    },

    /* =========================
       DELETE USER
    ========================== */

    async deleteUser(user) {
      const confirmed =
        window.confirm(
          `Bạn có chắc muốn xóa tài khoản "${user.ho_ten}" không?`
        )

      if (!confirmed) {
        return
      }

      try {
        await api.post(
          `/admin/nguoi-dung/xoa-nguoi-dung/${user.id}`
        )

        /*
          Nếu xóa người cuối cùng
          trong trang hiện tại thì
          quay về trang trước.
        */

        if (
          this.users.length === 1 &&
          this.currentPage > 1
        ) {
          this.currentPage--
        }

        await this.getUsers()

      } catch (error) {
        window.alert(
          apiError(error)
        )

        console.error(
          'Lỗi xóa người dùng:',
          error
        )
      }
    },

    /* =========================
       CLOSE MODAL
    ========================== */

    closeModal() {
      if (this.saving) {
        return
      }

      this.showModal = false
      this.editingUser = null
      this.formError = ''

      this.form = {
        ho_ten: '',
        email: '',
        password: '',
        vai_tro_id: '',
        trang_thai: 'hoat_dong',
      }
    },

    /* =========================
       STATISTICS
    ========================== */

    countRole(role) {
      return this.users.filter(
        (user) =>
          user.vai_tro
            ?.ten_vai_tro === role
      ).length
    },

    countStatus(status) {
      return this.users.filter(
        (user) =>
          user.trang_thai === status
      ).length
    },

    /* =========================
       FORMAT INITIAL
    ========================== */

    getInitial(name) {
      if (!name) {
        return 'U'
      }

      return name
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    /* =========================
       FORMAT ROLE
    ========================== */

    formatRole(role) {
      const roles = {
        quan_tri_vien:
          'Quản trị viên',

        giang_vien:
          'Giảng viên',

        sinh_vien:
          'Sinh viên',

        tro_giang:
          'Trợ giảng',
      }

      return (
        roles[role] ||
        role ||
        'Chưa có vai trò'
      )
    },

    /* =========================
       FORMAT STATUS
    ========================== */

    formatStatus(status) {
      const statuses = {
        hoat_dong:
          'Hoạt động',

        khong_hoat_dong:
          'Không hoạt động',

        bi_khoa:
          'Bị khóa',
      }

      return (
        statuses[status] ||
        status ||
        '-'
      )
    },

    /* =========================
       ROLE CLASS
    ========================== */

    roleClass(role) {
      const classes = {
        quan_tri_vien:
          'role-admin',

        giang_vien:
          'role-teacher',

        sinh_vien:
          'role-student',

        tro_giang:
          'role-assistant',
      }

      return (
        classes[role] || ''
      )
    },

    /* =========================
       STATUS CLASS
    ========================== */

    statusClass(status) {
      const classes = {
        hoat_dong:
          'status-active',

        khong_hoat_dong:
          'status-inactive',

        bi_khoa:
          'status-locked',
      }

      return (
        classes[status] || ''
      )
    },

    /* =========================
       FORMAT DATE
    ========================== */

    formatDate(date) {
      if (!date) {
        return '-'
      }

      const parsed =
        new Date(date)

      if (
        Number.isNaN(
          parsed.getTime()
        )
      ) {
        return '-'
      }

      return parsed
        .toLocaleDateString(
          'vi-VN'
        )
    },
  },
}
</script>

<style scoped>
/* =========================
   PAGE
========================= */

.user-page {
  width: 100%;
}

/* =========================
   HEADER
========================= */

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 20px;

  margin-bottom: 24px;
}

.page-label {
  margin: 0 0 7px;

  color: #315de5;

  font-size: 9px;
  font-weight: 700;

  letter-spacing: 1.4px;
}

.page-header h1 {
  margin: 0;

  color: #1e293b;

  font-size: 24px;
}

.page-description {
  margin: 7px 0 0;

  color: #8a94a6;

  font-size: 11px;
}

.btn-add {
  display: flex;
  align-items: center;

  border: none;
  border-radius: 9px;

  padding: 11px 17px;

  background: #315de5;

  color: white;

  cursor: pointer;

  font-size: 11px;
  font-weight: 600;

  transition: 0.2s;
}

.btn-add:hover {
  background: #274fc9;

  transform: translateY(-1px);
}

.btn-add span {
  margin-right: 6px;

  font-size: 16px;
  line-height: 1;
}

/* =========================
   STATISTICS
========================= */

.stats {
  display: grid;

  grid-template-columns:
    repeat(
      4,
      minmax(0, 1fr)
    );

  gap: 17px;

  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: center;

  min-width: 0;

  gap: 14px;

  padding: 18px;

  border:
    1px solid #e9edf4;

  border-radius: 12px;

  background: white;

  box-shadow:
    0 4px 13px
    rgba(29, 46, 83, 0.03);
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 44px;
  min-width: 44px;
  height: 44px;

  border-radius: 11px;

  font-weight: 700;
}

.stat-card span {
  color: #818a9c;

  font-size: 10px;
}

.stat-card h3 {
  margin: 3px 0;

  color: #1e293b;

  font-size: 22px;
}

.stat-card p {
  margin: 0;

  color: #a0a7b5;

  font-size: 9px;
}

/* =========================
   COLORS
========================= */

.blue {
  background: #edf2ff;
  color: #315de5;
}

.purple {
  background: #f3edff;
  color: #8055d9;
}

.green {
  background: #eaf8f1;
  color: #31966b;
}

.orange {
  background: #fff4e6;
  color: #d78828;
}

/* =========================
   FILTER
========================= */

.filter-card {
  display: flex;
  align-items: stretch;

  gap: 12px;

  margin-bottom: 18px;

  padding: 17px;

  border:
    1px solid #e9edf4;

  border-radius: 12px;

  background: white;
}

.search-box {
  display: flex;
  align-items: center;

  flex: 1;

  gap: 8px;

  padding: 0 12px;

  border:
    1px solid #e2e8f0;

  border-radius: 8px;

  background: white;
}

.search-box:focus-within {
  border-color: #315de5;

  box-shadow:
    0 0 0 3px
    rgba(49, 93, 229, 0.07);
}

.search-box span {
  color: #94a3b8;

  font-size: 18px;
}

.search-box input {
  width: 100%;

  border: none;

  outline: none;

  padding: 10px 0;

  color: #334155;

  background: transparent;

  font-size: 11px;
}

.filter-card select {
  min-width: 170px;

  border:
    1px solid #e2e8f0;

  border-radius: 8px;

  padding: 0 12px;

  background: white;

  color: #475569;

  outline: none;

  font-size: 10px;
}

.filter-card select:focus {
  border-color: #315de5;
}

.btn-reset {
  border:
    1px solid #e2e8f0;

  border-radius: 8px;

  padding: 0 14px;

  background: #f8fafc;

  color: #64748b;

  cursor: pointer;

  font-size: 10px;
}

.btn-reset:hover {
  border-color: #cbd5e1;

  background: #f1f5f9;
}

/* =========================
   TABLE CARD
========================= */

.table-card {
  overflow: hidden;

  border:
    1px solid #e9edf4;

  border-radius: 12px;

  background: white;

  box-shadow:
    0 4px 13px
    rgba(29, 46, 83, 0.025);
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;

  border-collapse: collapse;
}

th {
  padding: 13px 17px;

  background: #f8fafc;

  color: #8490a3;

  text-align: left;

  font-size: 9px;
  font-weight: 700;

  letter-spacing: 0.4px;

  white-space: nowrap;
}

td {
  padding: 14px 17px;

  border-top:
    1px solid #edf1f7;

  color: #475569;

  font-size: 10px;

  vertical-align: middle;
}

tbody tr {
  transition:
    background 0.15s;
}

tbody tr:hover {
  background: #fafcff;
}

/* =========================
   USER
========================= */

.user-info {
  display: flex;
  align-items: center;

  gap: 11px;

  min-width: 170px;
}

.avatar {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 38px;
  min-width: 38px;
  height: 38px;

  border-radius: 50%;

  background: #edf2ff;

  color: #315de5;

  font-size: 12px;
  font-weight: 700;
}

.user-name {
  min-width: 0;
}

.user-info strong {
  display: block;

  overflow: hidden;

  max-width: 180px;

  color: #334155;

  font-size: 11px;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-info span {
  display: block;

  margin-top: 3px;

  color: #9aa3b5;

  font-size: 8px;
}

.email {
  color: #64748b;

  white-space: nowrap;
}

/* =========================
   BADGES
========================= */

.role-badge,
.status-badge {
  display: inline-flex;
  align-items: center;

  gap: 5px;

  border-radius: 20px;

  padding: 5px 9px;

  font-size: 9px;
  font-weight: 600;

  white-space: nowrap;
}

.role-admin {
  background: #f3edff;
  color: #8055d9;
}

.role-teacher {
  background: #edf2ff;
  color: #315de5;
}

.role-student {
  background: #eaf8f1;
  color: #31966b;
}

.role-assistant {
  background: #fff4e6;
  color: #d78828;
}

.status-active {
  background: #eaf8f1;
  color: #31966b;
}

.status-inactive {
  background: #f1f5f9;
  color: #64748b;
}

.status-locked {
  background: #fff0f1;
  color: #d04444;
}

.dot {
  width: 5px;
  height: 5px;

  border-radius: 50%;

  background: currentColor;
}

/* =========================
   ACTIONS
========================= */

.actions {
  display: flex;

  gap: 7px;
}

.actions button {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 30px;
  height: 30px;

  border: none;

  border-radius: 7px;

  cursor: pointer;

  transition: 0.2s;
}

.actions button:hover {
  transform: translateY(-1px);
}

.btn-edit {
  background: #edf2ff;
  color: #315de5;
}

.btn-edit:hover {
  background: #dfe7ff;
}

.btn-delete {
  background: #fff0f1;
  color: #d04444;
}

.btn-delete:hover {
  background: #ffe3e5;
}

/* =========================
   PAGINATION
========================= */

.pagination-container {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 15px;

  padding: 14px 17px;

  border-top:
    1px solid #edf1f7;

  background: #fff;
}

.pagination-info {
  color: #94a3b8;

  font-size: 9px;
}

.pagination-info strong {
  color: #64748b;
}

.pagination {
  display: flex;
  align-items: center;

  gap: 6px;
}

.pagination button {
  min-width: 31px;
  height: 31px;

  border:
    1px solid #e2e8f0;

  border-radius: 7px;

  padding: 0 8px;

  background: white;

  color: #64748b;

  cursor: pointer;

  font-size: 10px;

  transition: 0.15s;
}

.pagination button:hover:not(:disabled) {
  border-color: #315de5;

  color: #315de5;
}

.pagination button.active {
  border-color: #315de5;

  background: #315de5;

  color: white;
}

.pagination button:disabled {
  cursor: not-allowed;

  opacity: 0.4;
}

/* =========================
   STATE
========================= */

.state-message {
  padding: 45px 20px;

  color: #94a3b8;

  text-align: center;

  font-size: 11px;
}

.state-message.error {
  color: #d04444;

  background: #fffafb;
}

.btn-retry {
  display: block;

  margin: 12px auto 0;

  border: none;

  border-radius: 7px;

  padding: 8px 14px;

  background: #315de5;

  color: white;

  cursor: pointer;

  font-size: 9px;
}

/* =========================
   MODAL OVERLAY
========================= */

.modal-overlay {
  position: fixed;

  z-index: 9999;

  inset: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 20px;

  background:
    rgba(15, 23, 42, 0.42);

  backdrop-filter:
    blur(2px);
}

/* =========================
   MODAL
========================= */

.modal {
  width: 100%;
  max-width: 520px;

  max-height: calc(
    100vh - 40px
  );

  overflow-y: auto;

  border-radius: 14px;

  padding: 24px;

  background: white;

  box-shadow:
    0 20px 50px
    rgba(15, 23, 42, 0.18);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;

  gap: 15px;

  margin-bottom: 22px;
}

.modal-header h2 {
  margin: 0;

  color: #1e293b;

  font-size: 18px;
}

.modal-header p {
  margin: 5px 0 0;

  color: #94a3b8;

  font-size: 10px;
}

.close-button {
  border: none;

  padding: 0;

  background: transparent;

  color: #94a3b8;

  cursor: pointer;

  font-size: 25px;
  line-height: 1;
}

.close-button:hover {
  color: #475569;
}

/* =========================
   FORM
========================= */

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;

  margin-bottom: 7px;

  color: #475569;

  font-size: 10px;
  font-weight: 600;
}

.form-group label b {
  color: #d04444;
}

.form-group input,
.form-group select {
  box-sizing: border-box;

  width: 100%;

  border:
    1px solid #e2e8f0;

  border-radius: 8px;

  padding: 10px 11px;

  background: white;

  color: #334155;

  outline: none;

  font-size: 11px;

  transition: 0.15s;
}

.form-group input:focus,
.form-group select:focus {
  border-color: #315de5;

  box-shadow:
    0 0 0 3px
    rgba(49, 93, 229, 0.08);
}

.form-group input::placeholder {
  color: #b0b7c4;
}

.form-group small {
  display: block;

  margin-top: 6px;

  color: #94a3b8;

  font-size: 9px;
}

.form-row {
  display: grid;

  grid-template-columns:
    1fr 1fr;

  gap: 14px;
}

.form-error {
  margin-top: 5px;

  border-radius: 8px;

  padding: 10px 12px;

  background: #fff0f1;

  color: #c43d4b;

  font-size: 10px;
}

/* =========================
   MODAL FOOTER
========================= */

.modal-footer {
  display: flex;
  justify-content: flex-end;

  gap: 9px;

  margin-top: 24px;

  padding-top: 17px;

  border-top:
    1px solid #edf1f7;
}

.btn-cancel,
.btn-save {
  border: none;

  border-radius: 8px;

  padding: 10px 16px;

  cursor: pointer;

  font-size: 10px;
  font-weight: 600;

  transition: 0.2s;
}

.btn-cancel {
  background: #f1f5f9;

  color: #64748b;
}

.btn-cancel:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-save {
  background: #315de5;

  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #274fc9;
}

.btn-save:disabled,
.btn-cancel:disabled {
  cursor: not-allowed;

  opacity: 0.6;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1200px) {
  .stats {
    grid-template-columns:
      repeat(2, 1fr);
  }
}

@media (max-width: 900px) {
  .filter-card {
    flex-wrap: wrap;
  }

  .search-box {
    flex-basis: 100%;
  }

  .filter-card select {
    flex: 1;

    min-height: 38px;
  }
}

@media (max-width: 750px) {
  .page-header {
    align-items: flex-start;

    flex-direction: column;
  }

  .btn-add {
    width: 100%;

    justify-content: center;
  }

  .stats {
    grid-template-columns: 1fr;
  }

  .filter-card {
    flex-direction: column;
  }

  .filter-card select,
  .btn-reset {
    width: 100%;

    min-height: 38px;
  }

  .form-row {
    grid-template-columns: 1fr;

    gap: 0;
  }

  .pagination-container {
    align-items: flex-start;

    flex-direction: column;
  }

  .pagination {
    flex-wrap: wrap;
  }
}
</style>