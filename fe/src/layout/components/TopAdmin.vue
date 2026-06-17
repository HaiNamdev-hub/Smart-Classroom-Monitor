<template>
    <header class="admin-topbar">
        <div class="topbar-left">
            <div class="topbar-title">
                <slot name="title">Bảng điều khiển</slot>
            </div>
        </div>

        <div class="topbar-right">
            <button class="theme-btn" @click="toggleTheme">
                {{ darkMode ? "☀️" : "🌙" }}
            </button>
            <button class="topbar-icon-btn" aria-label="Thông báo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" />
                </svg>
                <span class="topbar-badge">3</span>
            </button>
            <div class="topbar-user">
                <div class="topbar-avatar">AD</div>
                <div class="topbar-user-info">
                    <strong>Admin</strong>
                    <span>Quản trị viên</span>
                </div>
            </div>
        </div>
    </header>
</template>
<script setup>
import { ref, onMounted } from "vue";

const darkMode = ref(false);

onMounted(() => {
    const savedTheme = localStorage.getItem("theme") || "light";

    darkMode.value = savedTheme === "dark";

    document.documentElement.setAttribute(
        "data-theme",
        darkMode.value ? "dark" : "light"
    );
});

const toggleTheme = () => {
    darkMode.value = !darkMode.value;

    const theme = darkMode.value ? "dark" : "light";

    document.documentElement.setAttribute("data-theme", theme);

    localStorage.setItem("theme", theme);
    window.dispatchEvent(new Event("theme-changed"))
};
</script>
