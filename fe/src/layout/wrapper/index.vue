<template>
  <div class="app-layout">
    <!--
      Các route con sẽ được hiển thị tại đây.

      Ví dụ:
      /sinh-vien/trang-chu
      -> components/SinhVien/TrangChu/index.vue
    -->
    <router-view />
  </div>
</template>

<script>
export default {
  name: "AppLayout",
}
</script>

<style scoped>
/* ================================
   APP LAYOUT
================================ */

.app-layout {
  width: 100%;
  min-height: 100vh;
}
</style>