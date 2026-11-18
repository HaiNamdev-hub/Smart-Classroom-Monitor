<template>
  <div class="lecturer-layout" :class="{ 'sidebar-closed': !sidebarOpen }">
    <!-- MOBILE BACKDROP -->
    <div
      v-if="mobileMenuOpen"
      class="sidebar-backdrop"
      @click="mobileMenuOpen = false"
    ></div>

    <!-- ================= SIDEBAR ================= -->
    <aside class="lecturer-sidebar" :class="{ 'mobile-open': mobileMenuOpen }">
      <!-- BRAND & TOGGLE -->
      <div class="sidebar-header">
        <div class="brand">
          <div class="brand-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="13" rx="3" />
              <path d="M8 21h8M12 17v4M7 12l3-3 3 2 4-4" />
            </svg>
          </div>
          <div v-if="sidebarOpen" class="brand-text">
            <div class="brand-title">Smart Classroom</div>
            <div class="brand-badge">GIẢNG VIÊN</div>
          </div>
        </div>

        <button
          type="button"
          class="sidebar-toggle d-none d-md-flex"
          :title="sidebarOpen ? 'Thu gọn menu' : 'Mở rộng menu'"
          @click="toggleSidebar"
        >
          <i :class="sidebarOpen ? 'bi bi-chevron-left' : 'bi bi-chevron-right'"></i>
        </button>

        <button
          type="button"
          class="sidebar-close d-md-none"
          title="Đóng menu"
          @click="mobileMenuOpen = false"
        >
          <i class="bi bi-x-lg"></i>
        </button>
      </div>

      <!-- WORKSPACE LABEL -->
      <div v-if="sidebarOpen" class="workspace-label">
        QUẢN LÝ ĐÀO TẠO & GIÁM SÁT
      </div>

      <!-- NAVIGATION MENU -->
      <nav class="sidebar-menu">
        <router-link
          v-for="item in menuItems"
          :key="item.path"
          :to="item.path"
          class="menu-item"
          @click="mobileMenuOpen = false"
        >
          <div class="menu-icon" :class="item.badgeColor">
            <i :class="['bi', item.icon]"></i>
          </div>

          <div v-if="sidebarOpen" class="menu-text">
            <strong>{{ item.title }}</strong>
            <span>{{ item.subtitle }}</span>
          </div>

          <span v-if="sidebarOpen" class="arrow">›</span>
        </router-link>
      </nav>

      <!-- USER FOOTER -->
      <div class="sidebar-footer">
        <div class="user-card" :class="{ compact: !sidebarOpen }">
          <div class="user-avatar" :title="user?.ho_ten || 'Giảng viên'">
            {{ userInitials }}
          </div>

          <div v-if="sidebarOpen" class="user-info">
            <div class="user-name text-truncate">
              {{ user?.ho_ten || 'Giảng viên' }}
            </div>
            <div class="user-role">
              {{ user?.email || 'giangvien@dtu.edu.vn' }}
            </div>
          </div>

          <button
            type="button"
            class="btn-logout"
            :disabled="loggingOut"
            :title="sidebarOpen ? 'Đăng xuất' : 'Đăng xuất (' + (user?.ho_ten || 'Giảng viên') + ')'"
            @click="dangXuat"
          >
            <i class="bi bi-box-arrow-right"></i>
          </button>
        </div>
      </div>
    </aside>

    <!-- ================= MAIN AREA ================= -->
    <div class="lecturer-main">
      <!-- TOPBAR HEADER -->
      <header class="topbar">
        <div class="topbar-left">
          <button
            type="button"
            class="btn-menu-mobile d-md-none"
            aria-label="Mở menu"
            @click="mobileMenuOpen = true"
          >
            <i class="bi bi-list"></i>
          </button>

          <div class="breadcrumb-trail">
            <span class="text-muted">Phân hệ Giảng viên</span>
            <span class="separator">/</span>
            <span class="current-crumb">{{ currentPage.title }}</span>
          </div>
        </div>

        <div class="topbar-right">
          <span class="portal-badge d-none d-sm-inline-flex">
            <span class="status-dot"></span>
            Smart Classroom Monitor
          </span>

          <div class="user-greeting d-none d-md-block">
            <span>Xin chào, </span>
            <strong>{{ user?.ho_ten || 'Thầy/Cô' }}</strong>
          </div>
        </div>
      </header>

      <!-- VIEW CONTENT -->
      <main class="lecturer-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { clearAuth } from '../../services/api'

const route = useRoute()
const router = useRouter()

const sidebarOpen = ref(true)
const mobileMenuOpen = ref(false)
const loggingOut = ref(false)
const user = ref(null)

const menuItems = [
  {
    path: '/giang-vien/trang-chu',
    title: 'Trang chủ',
    subtitle: 'Tổng quan & Thống kê',
    icon: 'bi-speedometer2',
    badgeColor: 'blue'
  },
  {
    path: '/giang-vien/lop-hoc',
    title: 'Lớp học phụ trách',
    subtitle: 'Danh sách lớp giảng dạy',
    icon: 'bi-mortarboard',
    badgeColor: 'blue'
  },
  {
    path: '/giang-vien/thanh-vien-lop',
    title: 'Thành viên lớp',
    subtitle: 'Danh sách sinh viên',
    icon: 'bi-people',
    badgeColor: 'green'
  },
  {
    path: '/giang-vien/bai-tap',
    title: 'Quản lý bài tập',
    subtitle: 'Giao bài & thời hạn nộp',
    icon: 'bi-journal-text',
    badgeColor: 'orange'
  },
  {
    path: '/giang-vien/ngan-hang-cau-hoi',
    title: 'Ngân hàng câu hỏi',
    subtitle: 'Quản lý câu hỏi tái sử dụng',
    icon: 'bi-collection',
    badgeColor: 'blue'
  },
  {
    path: '/giang-vien/bai-nop',
    title: 'Bài nộp & Chấm điểm',
    subtitle: 'Theo dõi và chấm điểm',
    icon: 'bi-inbox',
    badgeColor: 'green'
  },
  {
    path: '/giang-vien/diem',
    title: 'Bảng điểm',
    subtitle: 'Tổng kết kết quả lớp',
    icon: 'bi-award',
    badgeColor: 'purple'
  },
  {
    path: '/giang-vien/bao-cao-ky-thi',
    title: 'Báo cáo kỳ thi',
    subtitle: 'Xuất kết quả và cảnh báo',
    icon: 'bi-file-earmark-spreadsheet',
    badgeColor: 'blue'
  },
  {
    path: '/giang-vien/phien-giam-sat',
    title: 'Phiên giám sát',
    subtitle: 'Giám sát thi & thực hành',
    icon: 'bi-camera-video',
    badgeColor: 'purple'
  },
  {
    path: '/giang-vien/anh-man-hinh',
    title: 'Ảnh màn hình',
    subtitle: 'Chụp ảnh màn hình SV',
    icon: 'bi-display',
    badgeColor: 'cyan'
  },
  {
    path: '/giang-vien/canh-bao',
    title: 'Cảnh báo vi phạm',
    subtitle: 'Phát hiện nghi vấn',
    icon: 'bi-exclamation-triangle',
    badgeColor: 'red'
  },
  {
    path: '/giang-vien/hoat-dong',
    title: 'Nhật ký hoạt động',
    subtitle: 'Lịch sử thao tác sinh viên',
    icon: 'bi-clock-history',
    badgeColor: 'orange'
  }
]

const currentPage = computed(() => {
  return (
    menuItems.find(
      item => route.path === item.path || route.path.startsWith(item.path + '/')
    ) || {
      title: 'Giảng viên',
      subtitle: ''
    }
  )
})

const userInitials = computed(() => {
  if (!user.value?.ho_ten) return 'GV'
  const parts = user.value.ho_ten.trim().split(/\s+/)
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const loadStoredUser = () => {
  const raw =
    sessionStorage.getItem('auth_user') ||
    localStorage.getItem('auth_user')
  if (raw) {
    try {
      user.value = JSON.parse(raw)
    } catch {
      user.value = null
    }
  }
}

const refreshUser = async () => {
  try {
    const { data } = await api.get('/thong-tin')
    const fetchedUser = data?.nguoi_dung ?? data?.data ?? data
    if (fetchedUser) {
      user.value = fetchedUser
      const storage = sessionStorage.getItem('auth_token')
        ? sessionStorage
        : localStorage
      storage.setItem('auth_user', JSON.stringify(fetchedUser))
    }
  } catch (err) {
    console.error('Không thể làm mới thông tin giảng viên:', err)
  }
}

const dangXuat = async () => {
  if (loggingOut.value) return
  loggingOut.value = true

  try {
    await api.post('/dang-xuat')
  } catch (err) {
    console.error('Lỗi khi đăng xuất:', err)
  } finally {
    clearAuth()
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    sessionStorage.clear()
    router.replace('/dang-nhap')
  }
}

onMounted(() => {
  loadStoredUser()
  refreshUser()
})
</script>

<style scoped>
.lecturer-layout {
  width: 100%;
  min-height: 100vh;
  display: grid;
  grid-template-columns: 270px minmax(0, 1fr);
  background: #f8fafc;
  transition: grid-template-columns 0.25s ease;
}

.lecturer-layout.sidebar-closed {
  grid-template-columns: 80px minmax(0, 1fr);
}

/* =====================================================
   SIDEBAR
===================================================== */
.lecturer-sidebar {
  min-height: 100vh;
  box-sizing: border-box;
  background: #0f172a;
  color: #f1f5f9;
  display: flex;
  flex-direction: column;
  border-right: 1px solid #1e293b;
  position: sticky;
  top: 0;
  height: 100vh;
  z-index: 100;
  transition: width 0.25s ease;
}

/* HEADER */
.sidebar-header {
  height: 68px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  border-bottom: 1px solid #1e293b;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.brand-icon {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  background: #2563eb;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffffff;
}

.brand-icon svg {
  width: 22px;
  height: 22px;
}

.brand-text {
  min-width: 0;
}

.brand-title {
  font-size: 14px;
  font-weight: 700;
  color: #ffffff;
  white-space: nowrap;
  letter-spacing: -0.2px;
}

.brand-badge {
  font-size: 9px;
  font-weight: 800;
  color: #60a5fa;
  letter-spacing: 1.2px;
}

.sidebar-toggle,
.sidebar-close {
  width: 32px;
  height: 32px;
  border: 1px solid #334155;
  background: #1e293b;
  color: #94a3b8;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
}

.sidebar-toggle:hover,
.sidebar-close:hover {
  background: #334155;
  color: #ffffff;
}

.workspace-label {
  padding: 16px 20px 8px;
  font-size: 10px;
  font-weight: 800;
  color: #64748b;
  letter-spacing: 1px;
}

/* NAVIGATION MENU */
.sidebar-menu {
  flex: 1;
  overflow-y: auto;
  padding: 8px 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* Custom scrollbar for sidebar */
.sidebar-menu::-webkit-scrollbar {
  width: 4px;
}
.sidebar-menu::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 4px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 9px 12px;
  border-radius: 10px;
  color: #94a3b8;
  text-decoration: none;
  font-size: 13px;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.menu-item:hover {
  background: #1e293b;
  color: #f8fafc;
}

.menu-item.router-link-active {
  background: rgba(37, 99, 235, 0.18);
  border-color: rgba(37, 99, 235, 0.4);
  color: #ffffff;
}

.menu-icon {
  width: 34px;
  height: 34px;
  flex-shrink: 0;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  transition: all 0.2s ease;
}

.blue {
  background: rgba(37, 99, 235, 0.2);
  color: #60a5fa;
}
.green {
  background: rgba(22, 163, 74, 0.2);
  color: #4ade80;
}
.orange {
  background: rgba(234, 88, 12, 0.2);
  color: #fb923c;
}
.purple {
  background: rgba(147, 51, 234, 0.2);
  color: #c084fc;
}
.cyan {
  background: rgba(8, 145, 178, 0.2);
  color: #22d3ee;
}
.red {
  background: rgba(220, 38, 38, 0.2);
  color: #f87171;
}

.menu-item.router-link-active .menu-icon {
  background: #2563eb;
  color: #ffffff;
}

.menu-text {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.menu-text strong {
  font-size: 12.5px;
  font-weight: 600;
  color: inherit;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.menu-text span {
  font-size: 10.5px;
  color: #64748b;
  margin-top: 1px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.menu-item.router-link-active .menu-text span {
  color: #93c5fd;
}

.arrow {
  color: #475569;
  font-size: 16px;
  transition: transform 0.2s;
}

.menu-item:hover .arrow {
  color: #94a3b8;
  transform: translateX(2px);
}

.menu-item.router-link-active .arrow {
  color: #60a5fa;
}

/* SIDEBAR COLLAPSED */
.sidebar-closed .sidebar-header {
  justify-content: center;
  padding: 0;
}

.sidebar-closed .menu-item {
  justify-content: center;
  padding: 8px 0;
}

.sidebar-closed .menu-icon {
  width: 40px;
  height: 40px;
  font-size: 18px;
}

/* USER FOOTER */
.sidebar-footer {
  padding: 12px;
  border-top: 1px solid #1e293b;
  background: #0b1120;
}

.user-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border-radius: 10px;
  background: #131d33;
}

.user-card.compact {
  justify-content: center;
  padding: 6px 0;
}

.user-avatar {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  border-radius: 50%;
  background: #2563eb;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-size: 12.5px;
  font-weight: 600;
  color: #f1f5f9;
}

.user-role {
  font-size: 10.5px;
  color: #64748b;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.btn-logout {
  width: 32px;
  height: 32px;
  border: none;
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  padding: 0;
}

.btn-logout:hover {
  background: #ef4444;
  color: #ffffff;
}

/* =====================================================
   MAIN AREA & TOPBAR
===================================================== */
.lecturer-main {
  min-width: 0;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.topbar {
  height: 64px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  z-index: 90;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.btn-menu-mobile {
  width: 38px;
  height: 38px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #334155;
  border-radius: 8px;
  font-size: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.breadcrumb-trail {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13.5px;
}

.separator {
  color: #cbd5e1;
}

.current-crumb {
  font-weight: 600;
  color: #0f172a;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.portal-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 12px;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  color: #1d4ed8;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #22c55e;
}

.user-greeting {
  font-size: 13px;
  color: #475569;
}

.user-greeting strong {
  color: #0f172a;
}

/* CONTENT VIEWPORT */
.lecturer-content {
  flex: 1;
  padding: 20px;
  box-sizing: border-box;
  background: #f8fafc;
}

/* MOBILE OFF-CANVAS BACKDROP */
.sidebar-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(2px);
  z-index: 99;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .lecturer-layout {
    grid-template-columns: 1fr !important;
  }

  .lecturer-sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: -280px;
    width: 270px;
    height: 100vh;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.2);
    transition: left 0.25s ease;
  }

  .lecturer-sidebar.mobile-open {
    left: 0;
  }

  .topbar {
    padding: 0 14px;
  }

  .lecturer-content {
    padding: 12px;
  }
}
</style>
