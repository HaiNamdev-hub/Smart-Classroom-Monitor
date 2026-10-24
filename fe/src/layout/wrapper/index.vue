<template>
  <div class="student-layout" :class="{ 'sidebar-closed': !sidebarOpen }">

    <!-- ================= SIDEBAR ================= -->
    <aside class="student-sidebar">

      <!-- TOGGLE -->
      <div class="sidebar-toggle-area">

        <button type="button" class="sidebar-toggle" :title="sidebarOpen ? 'Thu gọn menu' : 'Mở menu'"
          @click="toggleSidebar">
          ☰
        </button>

        <span v-if="sidebarOpen" class="sidebar-name">
          MENU SINH VIÊN
        </span>

      </div>


      <!-- MENU -->
      <nav class="sidebar-menu">

        <!-- TRANG CHỦ -->
        <router-link to="/sinh-vien/trang-chu" class="menu-item">
          <div class="menu-icon home">
            TC
          </div>

          <div v-if="sidebarOpen" class="menu-text">
            <strong>Trang chủ</strong>
            <span>Tổng quan học tập</span>
          </div>

          <span v-if="sidebarOpen" class="arrow">
            ›
          </span>
        </router-link>


        <!-- LỚP HỌC -->
        <router-link to="/sinh-vien/lop-hoc" class="menu-item">
          <div class="menu-icon blue">
            LH
          </div>

          <div v-if="sidebarOpen" class="menu-text">
            <strong>Lớp học</strong>
            <span>Các lớp đang tham gia</span>
          </div>

          <span v-if="sidebarOpen" class="arrow">
            ›
          </span>
        </router-link>


        <!-- BÀI TẬP -->
        <router-link to="/sinh-vien/bai-tap" class="menu-item">
          <div class="menu-icon orange">
            BT
          </div>

          <div v-if="sidebarOpen" class="menu-text">
            <strong>Bài tập</strong>
            <span>Bài tập được giao</span>
          </div>

          <span v-if="sidebarOpen" class="arrow">
            ›
          </span>
        </router-link>


        <!-- BÀI NỘP -->
        <router-link to="/sinh-vien/bai-nop" class="menu-item">
          <div class="menu-icon green">
            BN
          </div>

          <div v-if="sidebarOpen" class="menu-text">
            <strong>Bài nộp</strong>
            <span>Theo dõi bài đã nộp</span>
          </div>

          <span v-if="sidebarOpen" class="arrow">
            ›
          </span>
        </router-link>


        <!-- ĐIỂM -->
        <router-link to="/sinh-vien/diem" class="menu-item">
          <div class="menu-icon purple">
            Đ
          </div>

          <div v-if="sidebarOpen" class="menu-text">
            <strong>Điểm</strong>
            <span>Điểm và nhận xét</span>
          </div>

          <span v-if="sidebarOpen" class="arrow">
            ›
          </span>
        </router-link>


        <!-- THÔNG TIN CÁ NHÂN -->
        <router-link to="/sinh-vien/thong-tin-ca-nhan" class="menu-item">
          <div class="menu-icon cyan">
            CN
          </div>

          <div v-if="sidebarOpen" class="menu-text">
            <strong>Thông tin cá nhân</strong>
            <span>Xem thông tin tài khoản</span>
          </div>

          <span v-if="sidebarOpen" class="arrow">
            ›
          </span>
        </router-link>
        <button class="logout-bottom" @click="dangXuat">
          <span class="logout-icon">↪</span>
          <span v-if="sidebarOpen">Đăng xuất</span>
        </button>

      </nav>

    </aside>


    <!-- ================= NỘI DUNG ================= -->
    <main class="student-content">
      <router-view />
    </main>

  </div>
</template>


<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"

const router = useRouter()
const sidebarOpen = ref(true)

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

const dangXuat = () => {
  // 1. Xóa token hoặc thông tin đăng nhập trong LocalStorage / SessionStorage
  localStorage.removeItem("token")
  localStorage.removeItem("user")
  sessionStorage.clear()

  // 2. Chuyển hướng người dùng về trang đăng nhập
  router.push("/dang-nhap")
}
</script>


<style scoped>
.student-layout {
  width: 100%;
  min-height: calc(100vh - 65px);

  display: grid;
  grid-template-columns: 260px minmax(0, 1fr);

  background: #f5f7fb;

  transition: grid-template-columns 0.25s ease;
}

.student-layout.sidebar-closed {
  grid-template-columns: 76px minmax(0, 1fr);
}


/* ================= SIDEBAR ================= */

.student-sidebar {
  min-height: calc(100vh - 65px);

  box-sizing: border-box;

  padding: 18px 14px;

  background: #ffffff;

  border-right: 1px solid #e5e7eb;

  transition: 0.25s ease;
}


/* ================= TOGGLE ================= */

.sidebar-toggle-area {
  min-height: 50px;

  display: flex;
  align-items: center;

  gap: 11px;

  padding: 0 7px 13px;

  margin-bottom: 12px;

  border-bottom: 1px solid #e5e7eb;
}

.sidebar-closed .sidebar-toggle-area {
  justify-content: center;

  padding-left: 0;
  padding-right: 0;
}

.sidebar-toggle {
  width: 40px;
  height: 40px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 0;

  border: 0;
  border-radius: 10px;

  background: #f1f5f9;

  color: #475569;

  font-size: 21px;

  cursor: pointer;
}

.sidebar-toggle:hover {
  background: #eff6ff;

  color: #2563eb;
}

.sidebar-name {
  color: #64748b;

  font-size: 10px;
  font-weight: 800;

  letter-spacing: 1px;

  white-space: nowrap;
}


/* ================= MENU ================= */

.sidebar-menu {
  display: flex;

  flex-direction: column;

  gap: 6px;
}

.menu-item {
  width: 100%;
  min-height: 58px;

  display: flex;
  align-items: center;

  gap: 11px;

  box-sizing: border-box;

  padding: 8px 10px;

  border: 1px solid transparent;
  border-radius: 12px;

  color: #475569;

  text-decoration: none;

  transition: 0.18s ease;
}

.menu-item:hover {
  background: #f8fafc;

  color: #172033;
}


/* Trang đang được chọn */

.menu-item.router-link-active {
  background: #eff6ff;

  border-color: #bfdbfe;

  color: #2563eb;
}


/* ================= ICON ================= */

.menu-icon {
  width: 39px;
  height: 39px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 10px;

  font-size: 10px;
  font-weight: 900;
}

.home {
  background: #f1f5f9;
  color: #64748b;
}

.blue {
  background: #eff6ff;
  color: #2563eb;
}

.orange {
  background: #fff7ed;
  color: #ea580c;
}

.green {
  background: #f0fdf4;
  color: #16a34a;
}

.purple {
  background: #faf5ff;
  color: #9333ea;
}

.cyan {
  background: #ecfeff;
  color: #0891b2;
}


/* ================= TEXT ================= */

.menu-text {
  min-width: 0;

  flex: 1;

  display: flex;
  flex-direction: column;
}

.menu-text strong {
  color: inherit;

  font-size: 11px;
  font-weight: 700;
}

.menu-text span {
  margin-top: 3px;

  overflow: hidden;

  color: #94a3b8;

  font-size: 8px;

  text-overflow: ellipsis;

  white-space: nowrap;
}

.arrow {
  flex-shrink: 0;

  color: #94a3b8;

  font-size: 20px;
}


/* ================= COLLAPSED ================= */

.sidebar-closed .menu-item {
  justify-content: center;

  padding-left: 0;
  padding-right: 0;
}


/* ================= CONTENT ================= */

.student-content {
  min-width: 0;

  min-height: calc(100vh - 65px);

  overflow: hidden;
}


/* ================= DARK ================= */

:global(html.dark-theme) .student-layout {
  background: #1f2124;
}

:global(html.dark-theme) .student-sidebar {
  background: #111111;

  border-right-color: rgba(255, 255, 255, 0.12);
}

:global(html.dark-theme) .sidebar-toggle-area {
  border-bottom-color: rgba(255, 255, 255, 0.1);
}

:global(html.dark-theme) .sidebar-toggle {
  background: #1f252d;

  color: #cbd5e1;
}

:global(html.dark-theme) .sidebar-name {
  color: #94a3b8;
}

:global(html.dark-theme) .menu-item {
  color: #cbd5e1;
}

:global(html.dark-theme) .menu-item:hover {
  background: rgba(255, 255, 255, 0.05);

  color: #ffffff;
}

:global(html.dark-theme) .menu-item.router-link-active {
  background: #1f252d;

  border-color: #475569;

  color: #60a5fa;
}

:global(html.dark-theme) .menu-text span {
  color: #94a3b8;
}

.logout-bottom {
  margin-top: auto; /* Đẩy nút xuống đáy sidebar nếu dùng flex-direction trong sidebar */
  width: 100%;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 10px;
  padding: 0 12px;
  color: #ef4444;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 10px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s ease;
}

.logout-bottom:hover {
  background: #fee2e2;
  color: #dc2626;
}

/* Khi sidebar thu gọn */
.sidebar-closed .logout-bottom {
  justify-content: center;
  padding: 0;
}

/* Dark theme cho nút đăng xuất */
:global(html.dark-theme) .logout-bottom {
  background: #2a1215;
  border-color: #7f1d1d;
  color: #fca5a5;
}

:global(html.dark-theme) .logout-bottom:hover {
  background: #451a03;
  color: #f87171;
}


/* ================= RESPONSIVE ================= */

@media (max-width: 800px) {
  .student-layout {
    grid-template-columns: 76px minmax(0, 1fr);
  }

  .sidebar-name,
  .menu-text,
  .arrow {
    display: none;
  }

  .menu-item {
    justify-content: center;

    padding-left: 0;
    padding-right: 0;
  }
}
</style>