<script>
import api, {
  clearAuth,
  apiError,
} from '../../services/api'

import AdminIcon from '../../components/Admin/AdminIcon.vue'
import Overview from '../../components/Admin/TrangChu/index.vue'
import Users from '../../components/Admin/QLNguoiDung/index.vue'
import Classes from '../../components/Admin/QLLopHoc/index.vue'
import Roles from '../../components/Admin/QLVaiTro/index.vue'



const pages = [
  {
    path: '/admin/trang-chu',
    label: 'Tổng quan',
    icon: 'overview',
    component: Overview,
  },
  {
    path: '/admin/nguoi-dung',
    label: 'Quản lý người dùng',
    icon: 'users',
    component: Users,
  },
  {
    path: '/admin/lop-hoc',
    label: 'Quản lý lớp học',
    icon: 'classes',
    component: Classes,
  },
  {
    path: '/admin/vai-tro',
    label: 'Vai trò hệ thống',
    icon: 'roles',
    component: Roles,
  },
]

export default {
  components: {
    AdminIcon,
  },

  data() {
    return {
      pages,
      path: location.pathname,
      user: null,
      loading: true,
      error: '',
      menuOpen: false,
      loggingOut: false,
    }
  },

  computed: {
    current() {
      return (
        pages.find((page) => page.path === this.path) ||
        pages[0]
      )
    },
  },

  mounted() {
    this.loadUser()

    window.addEventListener(
      'popstate',
      this.onPopState
    )
  },

  beforeUnmount() {
    window.removeEventListener(
      'popstate',
      this.onPopState
    )
  },

  methods: {
    onPopState() {
      this.path = location.pathname
      this.menuOpen = false
    },

    navigate(path) {
      history.pushState({}, '', path)

      this.path = path
      this.menuOpen = false
    },

    async loadUser() {
      this.loading = true
      this.error = ''
      this.user = null

      try {
        const { data } = await api.get('/thong-tin')

        if (
          data.nguoi_dung.vai_tro?.ten_vai_tro !==
          'quan_tri_vien'
        ) {
          this.error =
            'Tài khoản này không có quyền truy cập trang quản trị.'
        } else {
          this.user = data.nguoi_dung
        }
      } catch (error) {
        this.error = apiError(error)
      } finally {
        this.loading = false
      }
    },

    async logout() {
      if (this.loggingOut) return

      this.loggingOut = true

      try {
        await api.post('/dang-xuat')

        clearAuth()

        location.replace('/dang-nhap')
      } catch (error) {
        this.error = apiError(error)
      } finally {
        this.loggingOut = false
      }
    },
  },
}
</script>

<template>
  <div class="admin-app">
    <!-- Backdrop -->
    <button
      v-if="menuOpen"
      class="sidebar-backdrop"
      aria-label="Đóng menu"
      @click="menuOpen = false"
    ></button>

    <!-- Sidebar -->
    <aside
      class="admin-sidebar"
      :class="{ open: menuOpen }"
    >
      <!-- Logo -->
      <a
        class="admin-brand"
        href="/admin/trang-chu"
        @click.prevent="navigate('/admin/trang-chu')"
      >
        <span class="brand-icon">
          <AdminIcon name="classes" />
        </span>

        <span>
          Smart Classroom
          <small>MONITOR</small>
        </span>
      </a>

      <!-- Workspace -->
      <div class="workspace">
        <span class="avatar">
          A
        </span>

        <div>
          Không gian quản trị
          <small>
            Administrator workspace
          </small>
        </div>

        <i class="dot"></i>
      </div>

      <!-- Navigation -->
      <p class="nav-caption">
        QUẢN LÝ HỆ THỐNG
      </p>

      <nav aria-label="Điều hướng admin">
        <a
          v-for="page in pages"
          :key="page.path"
          :href="page.path"
          :class="{
            active: current.path === page.path,
          }"
          :aria-current="
            current.path === page.path
              ? 'page'
              : undefined
          "
          @click.prevent="navigate(page.path)"
        >
          <AdminIcon :name="page.icon" />

          {{ page.label }}

          <span
            v-if="current.path === page.path"
            class="nav-dot"
          ></span>
        </a>
      </nav>

      <!-- Sidebar bottom -->
      <div class="sidebar-bottom">
        <div class="sidebar-note">
          <span>✧</span>

          <b>
            Một góc nhìn, mọi lớp học
          </b>

          <p>
            Kết nối và quản lý không gian học tập
            hiệu quả hơn mỗi ngày.
          </p>
        </div>

        <!-- Profile -->
        <div class="profile">
          <span class="avatar">
            {{ user?.ho_ten?.slice(0, 1) || 'A' }}
          </span>

          <div>
            <b>
              {{ user?.ho_ten || 'Quản trị viên' }}
            </b>

            <small>
              Quản trị hệ thống
            </small>
          </div>
        </div>

        <!-- Logout -->
        <button
          class="logout-btn"
          :disabled="loggingOut"
          @click="logout"
        >
          <AdminIcon name="logout" />

          {{
            loggingOut
              ? 'Đang đăng xuất…'
              : 'Đăng xuất'
          }}
        </button>
      </div>
    </aside>

    <!-- Main -->
    <div class="admin-body">
      <!-- Header -->
      <header class="admin-topbar">
        <div>
          <button
            class="menu-toggle icon-btn"
            aria-label="Mở menu"
            :aria-expanded="menuOpen"
            @click="menuOpen = !menuOpen"
          >
            <AdminIcon name="menu" />
          </button>

          <span class="breadcrumb">
            Không gian quản trị
            <span>/</span>
          </span>

          <b>
            {{ current.label }}
          </b>
        </div>

        <span class="header-badge">
          <i class="dot"></i>
          Smart Classroom Monitor
        </span>
      </header>

      <!-- Content -->
      <main class="admin-content">
        <div
          v-if="loading"
          class="state-panel"
          role="status"
        >
          Đang xác thực tài khoản…
        </div>

        <div
          v-else-if="error"
          class="alert error"
          role="alert"
        >
          {{ error }}

          <button
            class="text-btn"
            @click="loadUser"
          >
            Thử lại
          </button>
        </div>

        <component
          :is="current.component"
          v-else-if="user"
          :key="current.path"
          :current-user="user"
          @navigate="navigate"
        />
      </main>

      <!-- Footer -->
      <footer class="admin-footer">
        <span>
          © {{ new Date().getFullYear() }}
          Smart Classroom Monitor
        </span>

        <span>
          Không gian học tập kết nối
        </span>
      </footer>
    </div>
  </div>
</template>