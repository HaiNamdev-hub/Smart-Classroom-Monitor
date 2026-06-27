<template>
  <div class="admin-layout">
    <MenuStudent :collapsed="collapsed" @toggle-sidebar="toggleSidebar" />

    <div class="admin-main" :class="{ collapsed }">
      <TopAdmin />

      <main class="admin-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

import TopAdmin from "../components/TopAdmin.vue";
import MenuStudent from "../components/MenuStudent.vue";

// assets
import "../../assets/css/admin.css";

const collapsed = ref(false);

const toggleSidebar = () => {
  collapsed.value = !collapsed.value;
};
</script>
