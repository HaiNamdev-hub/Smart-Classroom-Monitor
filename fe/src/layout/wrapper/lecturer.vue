<template>
  <div class="lecturer-layout">
    <router-view />
  </div>
</template>

<script>
export default {
  name: "LecturerLayout",
}
</script>

<style scoped>
.lecturer-layout {
  width: 100%;
  min-height: 100vh;
}
</style>