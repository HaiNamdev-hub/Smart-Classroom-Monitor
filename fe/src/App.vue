<script setup>
import DangNhap from './components/Auth/DangNhap/index.vue'
import AdminLayout from './layout/wrapper/index.vue'
const isAdmin = window.location.pathname.startsWith('/admin')
</script>

<template>
  <AdminLayout v-if="isAdmin" />
  <DangNhap v-else />
</template>
