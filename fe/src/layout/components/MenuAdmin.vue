<template>
  <aside class="admin-sidebar" :class="{ collapsed: collapsed }">
    <div class="sidebar-brand">
      <div class="sidebar-logo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
          <path d="M6 12v5c3 3 9 3 12 0v-5" />
        </svg>
      </div>
      <div v-if="!collapsed" class="sidebar-brand-text">
        <h2>Quản trị hệ thống</h2>
        <span>Đánh giá rèn luyện SV</span>
      </div>
      <button class="collapse-btn" type="button" aria-label="Thu gon menu" @click="$emit('toggle-sidebar')">
        ☰
      </button>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section">Tổng quan</div>
      <RouterLink v-for="item in overviewItems" :key="item.to" :to="item.to" :title="collapsed ? item.label : ''"
        class="nav-item" :class="{ active: route.path === item.to }">
        <i :class="item.icon" class="nav-icon"></i>
        <span v-if="!collapsed">
          {{ item.label }}
        </span>
      </RouterLink>

      <div class="nav-section">Quản trị</div>
      <RouterLink v-for="item in managementItems" :key="item.to" :to="item.to" :title="collapsed ? item.label : ''"
        class="nav-item" :class="{ active: route.path === item.to }">
        <i :class="item.icon" class="nav-icon"></i>
        <span v-if="!collapsed">{{ item.label }}</span>
      </RouterLink>
    </nav>

    <div class="sidebar-footer">
      <button class="nav-item" @click="logout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" y1="12" x2="9" y2="12" />
        </svg>
        <span v-if="!collapsed">
          Đăng xuất
        </span>
      </button>
    </div>
  </aside>
</template>
<script setup>
import { useRoute, useRouter } from "vue-router"

defineProps({
  collapsed: Boolean
})

defineEmits(["toggle-sidebar"])

const route = useRoute()
const router = useRouter()

const overviewItems = [
  {
    label: "Dashboard",
    to: "/admin/trang-chu",
    icon: "fa-solid fa-table-cells-large",
  },
]

const managementItems = [
  {
    label: "Quản lý người dùng",
    to: "/admin/ql-nguoi-dung",
    icon: "fa-solid fa-users",
  },
  {
    label: "Quản lý đào tạo",
    to: "/admin/quan-ly-dao-tao",
    icon: "fa-solid fa-building-columns",
  },
  {
    label: "Quản lý đợt đánh giá",
    to: "/admin/quan-ly-dot-danh-gia",
    icon: "fa-solid fa-calendar-days",
  },
  {
    label: "Quản lý tiêu chí",
    to: "/admin/quan-ly-tieu-chi",
    icon: "fa-solid fa-list-check",
  },
  {
    label: "Phân quyền",
    to: "/admin/phan-quyen",
    icon: "fa-solid fa-user-shield",
  },
]

function logout() {
  localStorage.clear()
  router.push("/dang-nhap")
}
</script>
<style>
.nav-icon {
  width: 18px;
  min-width: 18px;
  font-size: 16px;
  text-align: center;
}
</style>
