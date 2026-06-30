<template>
    <div class="admin-content training-page student-dashboard-page">

        <!-- TIÊU ĐỀ -->
        <div class="dashboard-hero">
            <div>
                <span class="hello-text">Xin chào,</span>
                <h1>{{ currentUser.ho_ten }}</h1>
                <p>
                    {{ currentSinhVien.ma_sinh_vien }}
                    ·
                    {{ currentSinhVien.lop.ma_lop }}
                    ·
                    {{ currentSinhVien.lop.nganh.ten_nganh }}
                </p>
            </div>

            <div class="hero-badge">
                <i class="fa-solid fa-calendar-days"></i>
                <div>
                    <span>Đợt đánh giá hiện tại</span>
                    <strong>{{ dotDanhGia.nam_hoc }} - {{ formatHocKy(dotDanhGia.hoc_ky) }}</strong>
                </div>
            </div>
        </div>

        <!-- THỐNG KÊ -->
        <div class="dashboard-stat-grid">
            <div class="dashboard-stat-card">
                <div class="stat-icon-box bg-blue">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div>
                    <span>Điểm SV tự đánh giá</span>
                    <strong>
                        {{
                            phieuHienTai.trang_thai == "hoan_thanh"
                                ? phieuHienTai.tong_diem_cuoi + "/100"
                                : phieuHienTai.tong_diem_sv + "/100"
                        }}
                    </strong>
                </div>
            </div>

            <div class="dashboard-stat-card">
                <div class="stat-icon-box bg-green">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <span>Điểm lớp đánh giá</span>
                    <strong>{{ hasClassScore ? `${phieuHienTai.tong_diem_lop}/100` : '—' }}</strong>
                </div>
            </div>

            <div class="dashboard-stat-card">
                <div class="stat-icon-box bg-amber">
                    <i class="fa-solid fa-award"></i>
                </div>
                <div>
                    <span>Xếp loại tạm tính</span>
                    <strong>{{ formatXepLoai(currentRank) }}</strong>
                </div>
            </div>

            <div class="dashboard-stat-card">
                <div class="stat-icon-box bg-red">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div>
                    <span>Trạng thái phiếu</span>
                    <strong>{{ formatTrangThai(phieuHienTai.trang_thai) }}</strong>
                </div>
            </div>
        </div>

        <!-- GRID CHÍNH -->
        <div class="dashboard-main-grid">

            <!-- PHIẾU HIỆN TẠI -->
            <section class="dashboard-panel">
                <div class="panel-head">
                    <div>
                        <h2>Phiếu rèn luyện hiện tại</h2>
                        <p>Theo dõi trạng thái phiếu trong đợt đánh giá đang mở.</p>
                    </div>

                    <span class="status-badge" :class="'status-' + phieuHienTai.trang_thai">
                        {{ formatTrangThai(phieuHienTai.trang_thai) }}
                    </span>
                </div>

                <div class="current-form-box">
                    <div class="progress-row">
                        <span>Tiến độ xử lý</span>
                        <strong>{{ progressPercent }}%</strong>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
                    </div>

                    <div class="timeline-list">
                        <div class="timeline-item active">
                            <i class="fa-solid fa-pen"></i>
                            <div>
                                <strong>Sinh viên tự đánh giá</strong>
                                <span>
                                    {{
                                        phieuHienTai.trang_thai == "hoan_thanh"
                                            ? phieuHienTai.tong_diem_cuoi + " điểm"
                                    : phieuHienTai.tong_diem_sv + " điểm"
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="timeline-item" :class="{ active: hasClassScore }">
                            <i class="fa-solid fa-users"></i>
                            <div>
                                <strong>Lớp đánh giá</strong>
                                <span>{{ hasClassScore ? `${phieuHienTai.tong_diem_lop} điểm` : 'Chưa đánh giá'
                                    }}</span>
                            </div>
                        </div>

                        <div class="timeline-item" :class="{ active: hasAdvisorChecked }">
                            <i class="fa-solid fa-user-check"></i>
                            <div>
                                <strong>CVHT kiểm tra</strong>
                                <span>{{ hasAdvisorChecked ? 'Đã kiểm tra' : 'Đang chờ' }}</span>
                            </div>
                        </div>

                        <div class="timeline-item" :class="{ active: phieuHienTai.trang_thai === 'hoan_thanh' }">
                            <i class="fa-solid fa-circle-check"></i>
                            <div>
                                <strong>Hoàn thành</strong>
                                <span>{{ phieuHienTai.trang_thai === 'hoan_thanh' ? 'Đã hoàn tất' : 'Chưa hoàn tất'
                                    }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel-actions">
                    <button type="button" class="btn-primary" @click="goToMyEvaluation">
                        <i class="fa-solid fa-file-pen"></i>
                        {{ canEditStudentForm ? 'Tiếp tục điền phiếu' : 'Xem phiếu của tôi' }}
                    </button>

                    <button type="button" class="btn-ghost" @click="goToResult">
                        <i class="fa-solid fa-chart-line"></i>
                        Xem kết quả
                    </button>
                </div>
            </section>

            <!-- THÔNG TIN ĐỢT ĐÁNH GIÁ -->
            <section class="dashboard-panel">
                <div class="panel-head">
                    <div>
                        <h2>Thông tin đợt đánh giá</h2>
                        <p>Thời gian mở và hạn hoàn thành phiếu.</p>
                    </div>
                </div>

                <div class="deadline-box">
                    <div class="deadline-icon">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>

                    <div>
                        <span>Hạn nộp phiếu</span>
                        <strong>{{ formatDate(dotDanhGia.ngay_ket_thuc) }}</strong>
                        <p>Còn {{ remainingDays }} ngày để hoàn thành.</p>
                    </div>
                </div>

                <div class="info-list">
                    <div>
                        <span>Năm học</span>
                        <strong>{{ dotDanhGia.nam_hoc }}</strong>
                    </div>

                    <div>
                        <span>Học kỳ</span>
                        <strong>{{ formatHocKy(dotDanhGia.hoc_ky) }}</strong>
                    </div>

                    <div>
                        <span>Ngày bắt đầu</span>
                        <strong>{{ formatDate(dotDanhGia.ngay_bat_dau) }}</strong>
                    </div>

                    <div>
                        <span>Trạng thái đợt</span>
                        <strong>{{ formatTrangThaiDot(dotDanhGia.trang_thai) }}</strong>
                    </div>
                </div>
            </section>

            <!-- CÁN SỰ LỚP -->
            <section v-if="isOfficer" class="dashboard-panel officer-panel">
                <div class="panel-head">
                    <div>
                        <h2>Khu vực cán sự lớp</h2>
                        <p>Lớp trưởng/Bí thư theo dõi phiếu sinh viên cùng lớp.</p>
                    </div>
                </div>

                <div class="officer-stat-grid">
                    <div>
                        <span>Tổng phiếu lớp</span>
                        <strong>{{ classStats.total }}</strong>
                    </div>

                    <div>
                        <span>Chờ lớp đánh giá</span>
                        <strong>{{ classStats.waiting }}</strong>
                    </div>

                    <div>
                        <span>Lớp đã đánh giá</span>
                        <strong>{{ classStats.done }}</strong>
                    </div>
                </div>

                <button type="button" class="btn-primary full-btn" @click="goToClassForms">
                    <i class="fa-solid fa-users-gear"></i>
                    Xem phiếu lớp chờ đánh giá
                </button>
            </section>

            <!-- LỊCH SỬ GẦN ĐÂY -->
            <section class="dashboard-panel">
                <div class="panel-head">
                    <div>
                        <h2>Lịch sử gần đây</h2>
                        <p>Các lần cập nhật phiếu rèn luyện.</p>
                    </div>
                </div>

                <div class="activity-list">
                    <div v-for="item in recentActivities" :key="item.id" class="activity-item">
                        <div class="activity-dot" :class="'dot-' + item.type">
                            <i :class="item.icon"></i>
                        </div>

                        <div>
                            <strong>{{ item.title }}</strong>
                            <span>{{ item.time }}</span>
                        </div>
                    </div>
                </div>
            </section>

        </div>

    </div>
</template>

<script setup>
import axios from "axios"
import { computed, reactive, onMounted } from "vue"
import { useRouter } from "vue-router"

import "../../../assets/css/admin.css"
import "../../../assets/css/dark-theme.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const router = useRouter()

const BASE_URL = "http://127.0.0.1:8000/api"

function authHeader() {
    return {
        headers: {
            Authorization: "Bearer " + sessionStorage.getItem("auth_token"),
        },
    }
}

const currentUser = reactive({
    id: null,
    ho_ten: "",
    email: "",
    so_dien_thoai: "",
    ngay_sinh: "",
    vai_tro_id: null,
    trang_thai: "",
})

const currentSinhVien = reactive({
    id: null,
    ma_sinh_vien: "",
    nam_trung_tuyen: "",
    user_id: null,
    lop_id: null,
    lop: {
        id: null,
        ma_lop: "",
        ten_lop: "",
        lop_truong_id: null,
        bi_thu_id: null,
        khoa: {
            ma_khoa: "",
            ten_khoa: "",
        },
        nganh: {
            ma_nganh: "",
            ten_nganh: "",
        },
    },
})

const dotDanhGia = reactive({
    id: null,
    nam_hoc: "",
    hoc_ky: "",
    ngay_bat_dau: "",
    ngay_ket_thuc: "",
    trang_thai: "",
})

const phieuHienTai = reactive({
    id: null,
    sinh_vien_id: null,
    dot_danh_gia_id: null,

    tong_diem_sv: 0,
    tong_diem_lop: 0,
    tong_diem_cuoi: 0,

    xep_loai: null,

    trang_thai: "nhap",

    created_at: "",
    updated_at: "",
})

const classStats = reactive({
    total: 0,
    waiting: 0,
    done: 0,
})

const recentActivities = reactive([])

onMounted(() => {
    getStudentDashboard()
})

function getStudentDashboard() {
    axios
        .get(BASE_URL + "/sinh-vien/trang-chu/get-data", authHeader())
        .then((res) => {
            if (!res.data.status) {
                console.log(res.data.message)
                return
            }

            const data = res.data.data

            Object.assign(currentUser, data.current_user)
            Object.assign(currentSinhVien, data.current_sinh_vien)
            Object.assign(dotDanhGia, data.dot_danh_gia)
            Object.assign(phieuHienTai, data.phieu_hien_tai)
            Object.assign(classStats, data.class_stats)

            recentActivities.splice(
                0,
                recentActivities.length,
                ...(data.recent_activities || [])
            )
        })
        .catch((err) => {
            console.log("Lỗi lấy dữ liệu trang chủ sinh viên", err.response?.data || err)
        })
}

const roleId = computed(() => {
    return Number(currentUser.vai_tro_id)
})

const isNormalStudent = computed(() => {
    return roleId.value === 6
})

const isClassOfficer = computed(() => {
    return [4, 5].includes(roleId.value)
})

const isOfficer = computed(() => {
    if (!isClassOfficer.value) return false

    return (
        Number(currentSinhVien.id) === Number(currentSinhVien.lop.lop_truong_id) ||
        Number(currentSinhVien.id) === Number(currentSinhVien.lop.bi_thu_id)
    )
})

const hasClassScore = computed(() => {
    return [
        "lop_da_danh_gia",
        "co_van_da_kiem_tra",
        "hoan_thanh",
    ].includes(phieuHienTai.trang_thai)
})

const hasAdvisorChecked = computed(() => {
    return [
        "co_van_da_kiem_tra",
        "hoan_thanh",
    ].includes(phieuHienTai.trang_thai)
})

const currentRank = computed(() => {
    return phieuHienTai.xep_loai
})

const canEditStudentForm = computed(() => {
    return ["nhap", "tra_lai"].includes(phieuHienTai.trang_thai)
})

const canViewStudentForm = computed(() => {
    return [4, 5, 6].includes(roleId.value)
})

const canViewResult = computed(() => {
    return [
        "da_gui",
        "lop_da_danh_gia",
        "co_van_da_kiem_tra",
        "hoan_thanh",
        "tra_lai",
    ].includes(phieuHienTai.trang_thai)
})

const progressPercent = computed(() => {
    const map = {
        nhap: 25,
        tra_lai: 25,
        da_gui: 45,
        lop_da_danh_gia: 65,
        co_van_da_kiem_tra: 85,
        hoan_thanh: 100,
    }

    return map[phieuHienTai.trang_thai] || 0
})

const remainingDays = computed(() => {
    const end = new Date(String(dotDanhGia.ngay_ket_thuc).replace(" ", "T"))
    const now = new Date()
    const diff = end.getTime() - now.getTime()

    if (Number.isNaN(end.getTime()) || diff <= 0) return 0

    return Math.ceil(diff / (1000 * 60 * 60 * 24))
})

function formatHocKy(value) {
    if (Number(value) === 1) return "Kỳ I"
    if (Number(value) === 2) return "Kỳ II"

    return value ? `Kỳ ${value}` : "—"
}

function formatTrangThai(value) {
    const map = {
        nhap: "Nháp",
        da_gui: "Đã gửi",
        lop_da_danh_gia: "Lớp đã đánh giá",
        co_van_da_kiem_tra: "CVHT đã kiểm tra",
        hoan_thanh: "Hoàn thành",
        tra_lai: "Trả lại",
    }

    return map[value] || "—"
}

function formatTrangThaiDot(value) {
    const map = {
        nhap: "Nháp",
        dang_mo: "Đang mở",
        da_dong: "Đã đóng",
    }

    return map[value] || "—"
}

function formatXepLoai(value) {
    const map = {
        xuat_sac: "Xuất sắc",
        tot: "Tốt",
        kha: "Khá",
        trung_binh: "Trung bình",
        yeu: "Yếu",
        kem: "Kém",
    }

    return map[value] || "—"
}

function formatDate(value) {
    if (!value) return "—"

    const date = new Date(String(value).replace(" ", "T"))

    if (Number.isNaN(date.getTime())) return value

    return date.toLocaleDateString("vi-VN")
}

function goToMyEvaluation() {
    if (!canViewStudentForm.value) return

    router.push({
        path: "/sinh-vien/phieu-ren-luyen",
        query: {
            phieuId: phieuHienTai.id,
            mode: canEditStudentForm.value ? "edit" : "view",
        },
    })
}

function goToResult() {
    if (!canViewResult.value) return

    router.push("/sinh-vien/ket-qua-ren-luyen")
}

function goToClassForms() {
    if (!isOfficer.value) return

    router.push("/sinh-vien/danh-sach-phieu-lop")
}
</script>

<style scoped>
.student-dashboard-page {
    padding-bottom: 40px;
}

:global(html.dark-theme) .student-dashboard-page {
    --bg-main: #0b1120;
    --bg-sidebar: #111827;
    --bg-card: #1e293b;
    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
    --border-color: rgba(255, 255, 255, 0.08);
    --accent: #dc2626;
}

/* HERO */
.dashboard-hero {
    border-radius: 24px;
    padding: 26px;
    margin-bottom: 18px;
    background:
        radial-gradient(circle at top left, rgba(220, 38, 38, 0.18), transparent 32%),
        var(--bg-card);
    border: 1px solid var(--border-color);
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
}

.hello-text {
    color: var(--text-secondary);
    font-size: 14px;
    font-weight: 700;
}

.dashboard-hero h1 {
    color: var(--text-primary);
    font-size: 32px;
    font-weight: 900;
    margin: 4px 0;
}

.dashboard-hero p {
    color: var(--text-secondary);
    margin: 0;
}

.hero-badge {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid var(--border-color);
    background: rgba(148, 163, 184, 0.08);
    border-radius: 18px;
    padding: 14px 16px;
}

.hero-badge i {
    color: var(--accent);
    font-size: 22px;
}

.hero-badge span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
}

.hero-badge strong {
    display: block;
    color: var(--text-primary);
    margin-top: 3px;
}

/* STATS */
.dashboard-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 18px;
}

.dashboard-stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
}

.stat-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 18px;
}

.dashboard-stat-card span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
    margin-bottom: 5px;
}

.dashboard-stat-card strong {
    display: block;
    color: var(--text-primary);
    font-size: 22px;
    font-weight: 900;
}

/* COLORS */
.bg-blue {
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
}

.bg-green {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
}

.bg-amber {
    background: rgba(245, 158, 11, 0.14);
    color: #d97706;
}

.bg-red {
    background: rgba(220, 38, 38, 0.12);
    color: #dc2626;
}

/* MAIN GRID */
.dashboard-main-grid {
    display: grid;
    grid-template-columns: 1.35fr 1fr;
    gap: 18px;
}

.dashboard-panel {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
}

.panel-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 16px;
}

.panel-head h2 {
    color: var(--text-primary);
    font-size: 18px;
    font-weight: 900;
    margin: 0;
}

.panel-head p {
    color: var(--text-secondary);
    margin: 4px 0 0;
    font-size: 14px;
}

/* STATUS */
.status-badge {
    width: auto;
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 7px 14px;
    font-size: 12px;
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;
    border: 1px solid transparent;
}

.status-nhap {
    background: rgba(99, 102, 241, 0.16);
    color: #4f46e5;
    border-color: rgba(99, 102, 241, 0.32);
}

.status-da_gui {
    background: rgba(245, 158, 11, 0.16);
    color: #b45309;
    border-color: rgba(245, 158, 11, 0.32);
}

.status-lop_da_danh_gia {
    background: rgba(59, 130, 246, 0.16);
    color: #2563eb;
    border-color: rgba(59, 130, 246, 0.32);
}

.status-co_van_da_kiem_tra {
    background: rgba(34, 197, 94, 0.16);
    color: #15803d;
    border-color: rgba(34, 197, 94, 0.32);
}

.status-hoan_thanh {
    background: rgba(20, 184, 166, 0.16);
    color: #0f766e;
    border-color: rgba(20, 184, 166, 0.32);
}

.status-tra_lai {
    background: rgba(239, 68, 68, 0.16);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, 0.32);
}

/* PROGRESS */
.current-form-box {
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 16px;
    background: rgba(148, 163, 184, 0.06);
}

.progress-row {
    display: flex;
    justify-content: space-between;
    color: var(--text-primary);
    font-weight: 800;
    margin-bottom: 10px;
}

.progress-row span {
    color: var(--text-secondary);
}

.progress-bar {
    height: 10px;
    background: rgba(148, 163, 184, 0.20);
    border-radius: 999px;
    overflow: hidden;
    margin-bottom: 16px;
}

.progress-fill {
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(135deg, #dc2626, #ef4444);
}

.timeline-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.timeline-item {
    display: flex;
    align-items: center;
    gap: 12px;
    opacity: 0.55;
}

.timeline-item.active {
    opacity: 1;
}

.timeline-item i {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    background: rgba(148, 163, 184, 0.12);
    color: var(--text-secondary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.timeline-item.active i {
    background: rgba(220, 38, 38, 0.12);
    color: var(--accent);
}

.timeline-item strong {
    display: block;
    color: var(--text-primary);
    font-size: 14px;
}

.timeline-item span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
}

/* ACTION */
.panel-actions {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.panel-actions button,
.full-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.full-btn {
    width: 100%;
    margin-top: 16px;
}

/* DEADLINE */
.deadline-box {
    display: flex;
    gap: 14px;
    padding: 16px;
    border-radius: 16px;
    background: rgba(220, 38, 38, 0.08);
    border: 1px solid rgba(220, 38, 38, 0.18);
    margin-bottom: 14px;
}

.deadline-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: rgba(220, 38, 38, 0.12);
    color: var(--accent);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.deadline-box span,
.info-list span,
.officer-stat-grid span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
}

.deadline-box strong {
    display: block;
    color: var(--text-primary);
    font-size: 22px;
    margin-top: 4px;
}

.deadline-box p {
    color: var(--text-secondary);
    margin: 4px 0 0;
    font-size: 14px;
}

.info-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.info-list div,
.officer-stat-grid div {
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 12px;
    background: rgba(148, 163, 184, 0.06);
}

.info-list strong,
.officer-stat-grid strong {
    display: block;
    color: var(--text-primary);
    margin-top: 5px;
    font-weight: 900;
}

/* OFFICER */
.officer-panel {
    grid-column: span 1;
}

.officer-stat-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

/* ACTIVITY */
.activity-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 12px;
    background: rgba(148, 163, 184, 0.06);
}

.activity-dot {
    width: 38px;
    height: 38px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dot-blue {
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
}

.dot-green {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
}

.dot-amber {
    background: rgba(245, 158, 11, 0.14);
    color: #d97706;
}

.activity-item strong {
    display: block;
    color: var(--text-primary);
    font-size: 14px;
}

.activity-item span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
    margin-top: 3px;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .dashboard-stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .dashboard-main-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {

    .dashboard-stat-grid,
    .info-list,
    .officer-stat-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-hero {
        padding: 18px;
    }

    .dashboard-hero h1 {
        font-size: 26px;
    }

    .panel-head {
        flex-direction: column;
    }

    .panel-actions button {
        width: 100%;
    }
}
</style>