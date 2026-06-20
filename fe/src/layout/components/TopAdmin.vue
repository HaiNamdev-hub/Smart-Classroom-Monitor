<template>
    <header class="admin-topbar">
        <div class="topbar-left">
            <div class="topbar-title">
                <slot name="title">{{ pageTitle }}</slot>
            </div>
        </div>

        <div class="topbar-right">
            <button class="theme-btn" @click="toggleTheme">
                {{ darkMode ? "☀️" : "🌙" }}
            </button>

            <div class="topbar-user">
                <div class="topbar-avatar">
                    {{ avatarText }}
                </div>

                <div class="topbar-user-info">
                    <strong>{{ userName }}</strong>
                    <span>{{ roleLabel }}</span>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();

const darkMode = ref(false);

const currentUser = ref({
    ho_ten: "Người dùng",
    vai_tro: {
        ten_vai_tro: ""
    }
});

const ROLE_LABELS = {
    admin: "Quản trị viên",
    co_van: "Cố vấn học tập",
    sinh_vien: "Sinh viên",
    phong_dao_tao: "Phòng đào tạo",
    phong_ql_dt: "Phòng QLĐT & KH"
};

const pageTitle = computed(() => {
    return route.meta?.title || "Bảng điều khiển";
});

const roleKey = computed(() => {
    return (
        currentUser.value?.vai_tro?.ten_vai_tro ||
        currentUser.value?.ten_vai_tro ||
        currentUser.value?.role ||
        currentUser.value?.vai_tro ||
        ""
    );
});

const roleLabel = computed(() => {
    return ROLE_LABELS[roleKey.value] || "Người dùng";
});

const userName = computed(() => {
    return currentUser.value?.ho_ten || currentUser.value?.name || "Người dùng";
});

const avatarText = computed(() => {
    const name = userName.value.trim();

    if (!name) return "ND";

    const parts = name.split(/\s+/);

    if (parts.length === 1) {
        return parts[0].substring(0, 2).toUpperCase();
    }

    return (
        parts[0].charAt(0) + parts[parts.length - 1].charAt(0)
    ).toUpperCase();
});

const loadCurrentUser = () => {
    const rawUser =
        sessionStorage.getItem("auth_user") ||
        localStorage.getItem("auth_user") ||
        localStorage.getItem("user") ||
        localStorage.getItem("currentUser") ||
        localStorage.getItem("nguoi_dung");

    if (!rawUser) return;

    try {
        currentUser.value = JSON.parse(rawUser);
    } catch (error) {
        console.error("Không đọc được thông tin người dùng:", error);
    }
};

onMounted(() => {
    const savedTheme = localStorage.getItem("theme") || "light";

    darkMode.value = savedTheme === "dark";

    document.documentElement.setAttribute(
        "data-theme",
        darkMode.value ? "dark" : "light"
    );

    loadCurrentUser();
});

const toggleTheme = () => {
    darkMode.value = !darkMode.value;

    const theme = darkMode.value ? "dark" : "light";

    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("theme", theme);

    window.dispatchEvent(new Event("theme-changed"));
};
</script>