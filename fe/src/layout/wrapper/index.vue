
<script>
import api, { clearAuth, apiError } from '../../services/api'
import AdminIcon from '../../components/Admin/AdminIcon.vue'

import Overview from '../../components/GiangVien/TrangChu/index.vue'
import Classes from '../../components/GiangVien/LopHoc/index.vue'
import Members from '../../components/GiangVien/ThanhVienLop/index.vue'
import Assignments from '../../components/GiangVien/BaiTap/index.vue'
import Submissions from '../../components/GiangVien/BaiNop/index.vue'
import Grades from '../../components/GiangVien/Diem/index.vue'
import MonitorSession from '../../components/GiangVien/PhienGiamSat/index.vue'
import AnhManHinh from '../../components/GiangVien/AnhManHinh/index.vue'
import Activities from '../../components/GiangVien/HoatDong/index.vue'
import CanhBao from '../../components/GiangVien/CanhBao/index.vue'

const isTeacherPortal =
  window.location.pathname === '/giang-vien' ||
  window.location.pathname.startsWith('/giang-vien/')

// Giữ đường dẫn cũ của phần Admin.
// Tạo đường dẫn riêng cho Giảng viên.
const basePages = [
  {
    path: '/admin/giang-vien/trang-chu',
    label: 'Trang chủ',
    icon: 'overview',
    component: Overview
  },
  {
    path: '/admin/giang-vien/lop-hoc',
    label: 'Lớp học phụ trách',
    icon: 'classes',
    component: Classes
  },
  {
    path: '/admin/giang-vien/thanh-vien',
    label: 'Thành viên lớp',
    icon: 'users',
    component: Members
  },
  {
    path: '/admin/giang-vien/bai-tap',
    label: 'Quản lý bài tập',
    icon: 'roles',
    component: Assignments
  },
  {
    path: '/admin/giang-vien/bai-nop',
    label: 'Bài nộp & Chấm điểm',
    icon: 'classes',
    component: Submissions
  },
  {
    path: '/admin/giang-vien/diem',
    label: 'Bảng điểm',
    icon: 'overview',
    component: Grades
  },
  {
    path: '/admin/giang-vien/phien-giam-sat',
    label: 'Phiên giám sát',
    icon: 'classes',
    component: MonitorSession
  },
  {
    path: '/admin/giang-vien/anh-man-hinh',
    label: 'Ảnh màn hình',
    icon: 'classes',
    component: AnhManHinh
  },
  {
    path: '/admin/giang-vien/canh-bao',
    label: 'Cảnh báo hệ thống',
    icon: 'roles',
    component: CanhBao
  },
  {
    path: '/admin/giang-vien/hoat-dong',
    label: 'Nhật ký hoạt động',
    icon: 'roles',
    component: Activities
  }
]

const pages = basePages.map(page => {
  if (!isTeacherPortal) {
    return { ...page }
  }

  let path = page.path.replace(
    '/admin/giang-vien/',
    '/giang-vien/'
  )

  if (path === '/giang-vien/thanh-vien') {
    path = '/giang-vien/thanh-vien-lop'
  }

  return { ...page, path }
})

export default {
  name: 'GiangVienWrapper',

  components: {
    AdminIcon
  },

  data() {
    return {
      pages,
      path: window.location.pathname,
      user: null,
      loading: true,
      error: '',
      menuOpen: false,
      loggingOut: false
    }
  },

  computed: {
    current() {
      return (
        this.pages.find(page => page.path === this.path) ||
        this.pages[0]
      )
    }
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
      this.path = window.location.pathname
      this.menuOpen = false
    },

    navigate(path) {
      const exists = this.pages.some(
        page => page.path === path
      )

      if (!exists) {
        this.error = 'Trang được yêu cầu không tồn tại.'
        return
      }

      window.history.pushState({}, '', path)

      this.path = path
      this.error = ''
      this.menuOpen = false
    },

    // Kiểm tra người dùng từ Backend thật
    async loadUser() {
      this.loading = true
      this.error = ''
      this.user = null

      try {
        const response = await api.get('/thong-tin')

        const user = response.data?.nguoi_dung

        if (!user) {
          throw new Error(
            'Backend không trả về thông tin người dùng.'
          )
        }

        const role =
          user.vai_tro?.ten_vai_tro ??
          user.role

        // Chỉ Giảng viên được vào Teacher Portal
        if (isTeacherPortal && role !== 'giang_vien') {
          const redirects = {
            quan_tri_vien: '/admin/trang-chu',
            sinh_vien: '/sinh-vien/trang-chu'
          }

          const target = redirects[role]

          if (target) {
            window.location.replace(target)
          } else {
            clearAuth()
            window.location.replace('/dang-nhap')
          }

          return
        }

        this.user = user

      } catch (error) {
        console.error(
          'Lỗi xác thực Giảng viên:',
          error
        )

        const status = error.response?.status

        if (status === 401 || status === 403) {
          clearAuth()
          window.location.replace('/dang-nhap')
          return
        }

        this.error = error.response
          ? apiError(error)
          : 'Không thể xác thực tài khoản. Kiểm tra Backend và thử lại.'

      } finally {
        this.loading = false
      }
    },

    // Đăng xuất
    async logout() {
      if (this.loggingOut) return

      this.loggingOut = true
      this.error = ''

      try {
        await api.post('/dang-xuat')

        clearAuth()

        window.location.replace('/dang-nhap')

      } catch (error) {
        console.error('Lỗi đăng xuất:', error)

        if (
          error.response?.status === 401 ||
          error.response?.status === 403
        ) {
          clearAuth()
          window.location.replace('/dang-nhap')
          return
        }

        this.error = apiError(error)

      } finally {
        this.loggingOut = false
      }
    }
  }
}
</script>

<template>
  <div class="gv-layout">
    <!-- Menu Giảng viên -->
    <aside class="gv-sidebar">
      <div class="brand">
        <span class="logo-box">SCM</span>
        <div>
          <h3>Smart Classroom</h3>
          <small>GIẢNG VIÊN PORTAL</small>
        </div>
      </div>

      <nav class="nav-menu">
        <a
          v-for="page in pages"
          :key="page.path"
          href="#"
          :class="{ active: current.path === page.path }"
          @click.prevent="navigate(page.path)"
        >
          <span class="icon">🔹</span>
          <span>{{ page.label }}</span>
        </a>
      </nav>

      <div class="user-box">
        <div class="avatar">GV</div>
        <div class="info">
          <b>{{ user?.ho_ten || 'Giảng viên' }}</b>
          <small>Giảng viên bộ môn</small>
        </div>
        <button
          class="btn-logout"
          :disabled="loggingOut"
          @click="logout"
          title="Đăng xuất"
        >
          🚪
        </button>
      </div>
    </aside>

    <!-- Nội dung chính -->
    <div class="gv-main">
      <header class="gv-header">
        <div class="breadcrumb">
          <span>Phân vùng Giảng viên</span>
          /
          <b>{{ current.label }}</b>
        </div>
        <span class="status-badge">
          Smart Classroom Monitor
        </span>
      </header>

      <main class="gv-body">
        <!-- Đang xác thực -->
        <div v-if="loading" class="message info">
          Đang xác thực tài khoản Giảng viên...
        </div>

        <!-- Lỗi kết nối -->
        <div v-if="error" class="message error">
          {{ error }}
          <button @click="loadUser">
            Thử lại
          </button>
        </div>

        <!-- Màn hình được chọn -->
        <component
          :is="current.component"
          v-if="user && !loading && !error"
          :key="current.path"
          :current-user="user"
          @navigate="navigate"
        />
      </main>
    </div>
  </div>
</template>

<style scoped>
.gv-layout {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
  font-family: system-ui, sans-serif;
}

.gv-sidebar {
  width: 260px;
  flex-shrink: 0;
  background: #0f172a;
  color: white;
  display: flex;
  flex-direction: column;
  padding: 20px 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 20px 20px;
  border-bottom: 1px solid #1e293b;
}

.logo-box {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #2563eb;
  border-radius: 8px;
  font-weight: bold;
}

.brand h3 {
  margin: 0;
  font-size: 16px;
}

.brand small {
  color: #94a3b8;
  font-size: 11px;
}

.nav-menu {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 20px 12px;
}

.nav-menu a {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  color: #94a3b8;
  text-decoration: none;
  border-radius: 8px;
  font-size: 14px;
}

.nav-menu a:hover {
  background: #1e293b;
  color: white;
}

.nav-menu a.active {
  background: #2563eb;
  color: white;
}

.user-box {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-top: 1px solid #1e293b;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #334155;
  display: flex;
  align-items: center;
  justify-content: center;
}

.info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.info b {
  font-size: 13px;
}

.info small {
  font-size: 11px;
  color: #94a3b8;
}

.btn-logout {
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  font-size: 18px;
}

.gv-main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.gv-header {
  min-height: 64px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.breadcrumb {
  color: #64748b;
  font-size: 14px;
}

.breadcrumb b {
  color: #0f172a;
}

.status-badge {
  background: #e0f2fe;
  color: #075985;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
}

.gv-body {
  flex: 1;
  padding: 32px;
}

.message {
  padding: 14px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.message.info {
  background: #dbeafe;
  color: #1e40af;
}

.message.error {
  background: #fee2e2;
  color: #991b1b;
}

.message button {
  margin-left: 12px;
  padding: 6px 12px;
  border: 1px solid currentColor;
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
}

@media (max-width: 768px) {
  .gv-sidebar {
    width: 200px;
  }

  .gv-body {
    padding: 16px;
  }

  .gv-header {
    padding: 12px 16px;
  }
}
</style>

