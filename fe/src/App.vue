
<script setup>
import DangNhap from './components/Auth/DangNhap/index.vue'
import PortalLayout from './layout/wrapper/index.vue'

const path = window.location.pathname

const isPortal =
  path === '/admin' ||
  path.startsWith('/admin/') ||
  path === '/giang-vien' ||
  path.startsWith('/giang-vien/')
</script>

<template>
  <PortalLayout v-if="isPortal" />
  <DangNhap v-else />
</template>
