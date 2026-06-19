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
        <svg v-if="item.icon === 'grid'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="3" width="7" height="7" />
          <rect x="14" y="3" width="7" height="7" />
          <rect x="14" y="14" width="7" height="7" />
          <rect x="3" y="14" width="7" height="7" />
        </svg>
        <svg v-else-if="item.icon === 'users'" width="18" height="18" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        <svg v-else-if="item.icon === 'book'" width="18" height="18" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
        </svg>
        <svg v-else-if="item.icon === 'building'" width="18" height="18" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="4" y="2" width="16" height="20" rx="2" />
          <path d="M9 22v-4h6v4M9 6h.01M15 6h.01M9 10h.01M15 10h.01M9 14h.01M15 14h.01" />
        </svg>
        <svg v-else-if="item.icon === 'school'" width="18" height="18" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
          <path d="M6 12v5c3 3 9 3 12 0v-5" />
        </svg>
        <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <line x1="8" y1="6" x2="21" y2="6" />
          <line x1="8" y1="12" x2="21" y2="12" />
          <line x1="8" y1="18" x2="21" y2="18" />
          <line x1="3" y1="6" x2="3.01" y2="6" />
          <line x1="3" y1="12" x2="3.01" y2="12" />
          <line x1="3" y1="18" x2="3.01" y2="18" />
        </svg>
        <span v-if="!collapsed">
          {{ item.label }}
        </span>
      </RouterLink>

      <div class="nav-section">Quản trị</div>
      <RouterLink v-for="item in managementItems" :key="item.to" :to="item.to" :title="collapsed ? item.label : ''"
        class="nav-item" :class="{ active: route.path === item.to }">
        <svg v-if="item.icon === 'users'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
          stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
        <svg v-else-if="item.icon === 'book'" width="18" height="18" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
        </svg>
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
  { label: "Dashboard", to: "/admin/trang-chu", icon: "grid" },
]

const managementItems = [
  { label: "Quản lý người dùng", to: "/admin/ql-nguoi-dung", icon: "users" },
  { label: "Quản lý đào tạo", to: "/admin/quan-ly-dao-tao", icon: "book" },
]

function logout() {
  localStorage.clear()
  router.push("/dang-nhap")
}
</script>
