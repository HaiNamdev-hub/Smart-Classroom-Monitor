<template>
  <PortalLayout v-if="isPortal" />
  <DangNhap v-else-if="isLogin" />
  <router-view v-else />
</template>

<script setup>
import DangNhap from './components/Auth/DangNhap/index.vue'
import PortalLayout from './layout/wrapper/index.vue'

const path = window.location.pathname

const isPortal =
  path === '/admin' ||
  path.startsWith('/admin/') ||
  path === '/giang-vien' ||
  path.startsWith('/giang-vien/')

const isLogin = path === '/dang-nhap'
</script>