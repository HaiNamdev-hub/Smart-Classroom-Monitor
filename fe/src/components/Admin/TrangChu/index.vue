<template>
    <AdminLayout>
        <template #title>Dashboard</template>

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Tổng quan hệ thống</h1>
            <p>Theo dõi nhanh toàn bộ tình trạng đánh giá rèn luyện sinh viên.</p>
        </div>

        <!-- ROW 1: stat cards -->
        <div class="stats-grid">
            <div v-for="s in topStats" :key="s.label" class="stat-card">
                <div class="stat-icon" :style="{ background: s.color + '22', color: s.color }">
                    <svg v-if="s.icon === 'users'" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                        <circle cx="9" cy="7" r="4" />
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                    </svg>
                    <svg v-else-if="s.icon === 'building'" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="2" width="16" height="20" rx="2" />
                        <path d="M9 22v-4h6v4" />
                    </svg>
                    <svg v-else-if="s.icon === 'school'" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
                        <path d="M6 12v5c3 3 9 3 12 0v-5" />
                    </svg>
                    <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                </div>

                <!-- STAT INFO -->
                <div class="stat-info">
                    <div class="stat-value">{{ s.value }}</div>
                    <div class="stat-label">{{ s.label }}</div>
                </div>
            </div>
        </div>

        <!-- ROW 2: form stat cards -->
        <div class="stats-grid">
            <div v-for="s in formStats" :key="s.label" class="stat-card">
                <div class="stat-icon" :style="{ background: s.color + '22', color: s.color }">
                    <svg v-if="s.icon === 'check'" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    <svg v-else-if="s.icon === 'clock'" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    <svg v-else-if="s.icon === 'shield'" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <polyline points="9 12 11 14 15 10" />
                    </svg>
                    <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M5 22h14M5 2h14M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2" />
                    </svg>
                </div>
                <div class="stat-info">
                    <div class="stat-value">{{ s.value }}</div>
                    <div class="stat-label">{{ s.label }}</div>
                </div>
            </div>
        </div>

        <!-- ROW 3: charts -->
        <div class="charts-grid">
            <div class="panel">
                <div class="panel-title">Trạng thái đánh giá</div>
                <div class="chart-holder">
                    <Pie :key="currentTheme" :data="pieData" :options="pieOptions" />
                </div>
            </div>
            <div class="panel">
                <div class="panel-title">Sinh viên theo khoa</div>
                <div class="chart-holder">
                    <Bar :key="currentTheme + '-bar'" :data="barData" :options="barOptions" />
                </div>
            </div>
        </div>

        <!-- ROW 4: activity + notifications -->
        <div class="bottom-grid">
            <div class="panel">
                <div class="panel-title">Hoạt động gần đây</div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Sinh viên</th>
                            <th>Hoạt động</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="a in activities" :key="a.name + a.time">
                            <td>
                                <div class="activity-user">
                                    <div class="activity-avatar">{{ a.initials }}</div>
                                    {{ a.name }}
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="`badge-${a.type}`">{{ a.action }}</span>
                            </td>
                            <td class="activity-time">{{ a.time }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <div class="panel-title">Thông báo hệ thống</div>
                <div class="notif-list">
                    <div v-for="n in notifications" :key="n.title" class="notif-item">
                        <div class="notif-icon" :style="{ background: n.color + '22', color: n.color }">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0" />
                            </svg>
                        </div>
                        <div class="notif-body">
                            <strong>{{ n.title }}</strong>
                            <span>{{ n.desc }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
<script setup>
import axios from "axios"
import { Pie, Bar } from "vue-chartjs"
import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend,
    CategoryScale,
    LinearScale,
    BarElement,
} from "chart.js"
import { ref, computed, onMounted, onUnmounted } from "vue"

import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement)

const BASE_URL = "http://127.0.0.1:8000/api/admin"

function authHeader() {
    return {
        headers: {
            Authorization: "Bearer " + sessionStorage.getItem("auth_token"),
        },
    }
}

const currentTheme = ref("light")

const handleThemeChange = () => {
    currentTheme.value =
        document.documentElement.getAttribute("data-theme") || "light"
}

const isDark = computed(() => currentTheme.value === "dark")

const textColor = computed(() =>
    isDark.value ? "#f8fafc" : "#334155"
)

const gridColor = computed(() =>
    isDark.value ? "rgba(255,255,255,.08)" : "rgba(0,0,0,.08)"
)

const borderColor = computed(() =>
    isDark.value ? "#1e293b" : "#ffffff"
)

const topStats = ref([
    { label: "Sinh viên", value: 0, icon: "users", color: "#3b82f6" },
    { label: "Khoa", value: 0, icon: "building", color: "#22c55e" },
    { label: "Lớp", value: 0, icon: "school", color: "#f59e0b" },
    { label: "Đợt đánh giá", value: 0, icon: "calendar", color: "#a855f7" },
])

const formStats = ref([
    { label: "Đã nộp", value: 0, icon: "check", color: "#22c55e" },
    { label: "Chưa nộp", value: 0, icon: "clock", color: "#ef4444" },
    { label: "Đã duyệt", value: 0, icon: "shield", color: "#3b82f6" },
    { label: "Chờ duyệt", value: 0, icon: "hourglass", color: "#f59e0b" },
])

const activities = ref([])
const notifications = ref([])

const pieChartValues = ref([0, 0, 0])
const barLabels = ref([])
const barValues = ref([])

function getDashboardData() {
    axios
        .get(BASE_URL + "/trang-chu/get-data", authHeader())
        .then((res) => {
            if (!res.data.status) return

            const data = res.data.data

            topStats.value = [
                { label: "Sinh viên", value: data.top_stats.sinh_vien, icon: "users", color: "#3b82f6" },
                { label: "Khoa", value: data.top_stats.khoa, icon: "building", color: "#22c55e" },
                { label: "Lớp", value: data.top_stats.lop, icon: "school", color: "#f59e0b" },
                { label: "Đợt đánh giá", value: data.top_stats.dot_danh_gia, icon: "calendar", color: "#a855f7" },
            ]

            formStats.value = [
                { label: "Đã nộp", value: data.form_stats.da_nop, icon: "check", color: "#22c55e" },
                { label: "Chưa nộp", value: data.form_stats.chua_nop, icon: "clock", color: "#ef4444" },
                { label: "Đã duyệt", value: data.form_stats.da_duyet, icon: "shield", color: "#3b82f6" },
                { label: "Chờ duyệt", value: data.form_stats.cho_duyet, icon: "hourglass", color: "#f59e0b" },
            ]

            pieChartValues.value = [
                data.form_stats.da_duyet,
                data.form_stats.cho_duyet,
                data.form_stats.tra_lai,
            ]

            barLabels.value = data.sinh_vien_theo_khoa.map((item) => item.ten_khoa)
            barValues.value = data.sinh_vien_theo_khoa.map((item) => item.so_luong)

            activities.value = data.activities || []
            notifications.value = data.notifications || []
        })
        .catch((err) => {
            console.log("Lỗi lấy dữ liệu dashboard", err.response?.data || err)
        })
}

onMounted(() => {
    handleThemeChange()
    getDashboardData()

    window.addEventListener("theme-changed", handleThemeChange)
})

onUnmounted(() => {
    window.removeEventListener("theme-changed", handleThemeChange)
})

const pieData = computed(() => ({
    labels: ["Đã duyệt", "Chờ duyệt", "Trả lại"],
    datasets: [
        {
            data: pieChartValues.value,
            backgroundColor: ["#22c55e", "#f59e0b", "#ef4444"],
            borderColor: borderColor.value,
            borderWidth: 3,
        },
    ],
}))

const pieOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                color: textColor.value,
                padding: 16,
                font: {
                    size: 13,
                },
            },
        },
    },
}))

const barData = computed(() => ({
    labels: barLabels.value,
    datasets: [
        {
            label: "Số sinh viên",
            data: barValues.value,
            backgroundColor: "#dc2626",
            borderRadius: 8,
            maxBarThickness: 46,
        },
    ],
}))

const barOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
    },
    scales: {
        x: {
            ticks: {
                color: textColor.value,
            },
            grid: {
                display: false,
            },
        },
        y: {
            ticks: {
                color: textColor.value,
            },
            grid: {
                color: gridColor.value,
            },
        },
    },
}))
</script>