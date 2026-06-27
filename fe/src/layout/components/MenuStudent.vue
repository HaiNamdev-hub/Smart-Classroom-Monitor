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
                <h2>{{ brandTitle }}</h2>
                <span>{{ brandSubtitle }}</span>
            </div>

            <button class="collapse-btn" type="button" aria-label="Thu gọn menu" @click="$emit('toggle-sidebar')">
                ☰
            </button>
        </div>

        <nav class="sidebar-nav">
            <template v-for="section in filteredSections" :key="section.title">
                <div class="nav-section">
                    {{ section.title }}
                </div>

                <RouterLink v-for="item in section.items" :key="item.to" :to="item.to"
                    :title="collapsed ? item.label : ''" class="nav-item" :class="{ active: isActive(item.to) }">
                    <i :class="item.icon" class="nav-icon"></i>
                    <span v-if="!collapsed">{{ item.label }}</span>
                </RouterLink>
            </template>
        </nav>

        <div class="sidebar-footer">
            <button class="nav-item" type="button" @click="logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>

                <span v-if="!collapsed">Đăng xuất</span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"

defineProps({
    collapsed: Boolean,
})

defineEmits(["toggle-sidebar"])

const route = useRoute()
const router = useRouter()

const currentUser = ref({
    id: null,
    ho_ten: "Sinh viên",
    email: "",
    vai_tro_id: 6,
})

const roleId = computed(() => {
    return Number(currentUser.value?.vai_tro_id)
})

const isStudent = computed(() => {
    return [4, 5, 6].includes(roleId.value)
})

const isOfficer = computed(() => {
    return [4, 5].includes(roleId.value)
})

const officerLabel = computed(() => {
    if (roleId.value === 4) return "Lớp trưởng"
    if (roleId.value === 5) return "Bí thư"

    return ""
})

const brandTitle = computed(() => {
    if (isOfficer.value) return "Không gian cán sự"
    return "Không gian sinh viên"
})

const brandSubtitle = computed(() => {
    if (isOfficer.value) {
        return `${officerLabel.value} · Đánh giá rèn luyện`
    }

    return "Đánh giá rèn luyện SV"
})

const menuSections = [
    {
        title: "Tổng quan",
        items: [
            {
                label: "Trang chủ",
                to: "/sinh-vien/trang-chu",
                icon: "fa-solid fa-house",
            },
        ],
    },
    {
        title: "Phiếu rèn luyện",
        items: [
            {
                label: "Phiếu của tôi",
                to: "/sinh-vien/phieu-ren-luyen",
                icon: "fa-solid fa-file-pen",
            },
            {
                label: "Kết quả rèn luyện",
                to: "/sinh-vien/ket-qua-ren-luyen",
                icon: "fa-solid fa-chart-line",
            },
        ],
    },
    {
        title: "Cán sự lớp",
        items: [
            {
                label: "Phiếu lớp chờ đánh giá",
                to: "/sinh-vien/danh-sach-phieu-lop",
                icon: "fa-solid fa-users-gear",
                requiresOfficer: true,
            },
        ],
    },
]

const canShowMenu = (item) => {
    if (!isStudent.value) return false

    if (item.requiresOfficer) {
        return isOfficer.value
    }

    return true
}

const filteredSections = computed(() => {
    return menuSections
        .map((section) => {
            return {
                ...section,
                items: section.items.filter(canShowMenu),
            }
        })
        .filter((section) => section.items.length > 0)
})

const isActive = (path) => {
    return route.path === path || route.path.startsWith(path + "/")
}

function loadCurrentUser() {
    const rawUser =
        sessionStorage.getItem("auth_user") ||
        localStorage.getItem("auth_user") ||
        localStorage.getItem("user") ||
        localStorage.getItem("currentUser") ||
        localStorage.getItem("nguoi_dung")

    if (!rawUser) return

    try {
        currentUser.value = JSON.parse(rawUser)
    } catch (error) {
        console.error("Không đọc được thông tin người dùng:", error)
    }
}

onMounted(() => {
    loadCurrentUser()
})

function logout() {
    sessionStorage.removeItem("auth_token")
    sessionStorage.removeItem("auth_user")
    sessionStorage.removeItem("sinh_vien")

    localStorage.removeItem("auth_token")
    localStorage.removeItem("auth_user")
    localStorage.removeItem("user")
    localStorage.removeItem("currentUser")
    localStorage.removeItem("nguoi_dung")
    localStorage.removeItem("sinh_vien")
    localStorage.removeItem("currentSinhVien")
    localStorage.removeItem("student")

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