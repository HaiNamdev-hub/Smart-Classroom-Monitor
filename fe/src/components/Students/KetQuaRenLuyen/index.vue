<template>
    <div class="admin-content training-page result-page">

        <!-- TIÊU ĐỀ -->
        <div class="page-heading">
            <h1>Kết quả rèn luyện</h1>
            <p>
                Xem điểm rèn luyện, xếp loại và trạng thái phiếu qua từng học kỳ.
            </p>
        </div>

        <!-- THÔNG TIN SINH VIÊN -->
        <div class="student-result-card">
            <div class="student-avatar">
                {{ getInitials(currentUser.ho_ten) }}
            </div>

            <div>
                <span>Sinh viên</span>
                <strong>{{ currentUser.ho_ten }}</strong>
                <p>
                    {{ currentSinhVien.ma_sinh_vien || '—' }}
                    ·
                    {{ currentSinhVien.lop?.ma_lop || '—' }}
                    ·
                    {{ currentSinhVien.lop?.nganh?.ten_nganh || '—' }}
                </p>
            </div>
        </div>

        <!-- THỐNG KÊ -->
        <div class="result-stat-grid">
            <div class="result-stat-card">
                <span>Điểm hiện tại</span>
                <strong>{{ latestResult ? getFinalScore(latestResult) : '—' }}</strong>
            </div>

            <div class="result-stat-card success">
                <span>Xếp loại hiện tại</span>
                <strong>{{ latestResult ? formatXepLoai(getFinalRank(latestResult)) : '—' }}</strong>
            </div>

            <div class="result-stat-card warning">
                <span>Số phiếu đã hoàn thành</span>
                <strong>{{ completedCount }}</strong>
            </div>

            <div class="result-stat-card info">
                <span>Điểm cao nhất</span>
                <strong>{{ highestScore }}</strong>
            </div>
        </div>

        <!-- PANEL -->
        <div class="training-panel">

            <!-- TOOLBAR -->
            <div class="toolbar">
                <div class="search-box">
                    <input v-model="search" type="text" placeholder="Tìm theo năm học, học kỳ, trạng thái..." />
                </div>

                <div class="filter-select">
                    <select v-model="filterStatus">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="nhap">Nháp</option>
                        <option value="da_gui">Đã gửi</option>
                        <option value="lop_da_duyet">Lớp đã duyệt</option>
                        <option value="co_van_da_duyet">Cố vấn đã duyệt</option>
                        <option value="hoan_thanh">Hoàn thành</option>
                    </select>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 48px;">STT</th>
                            <th>Năm học</th>
                            <th>Học kỳ</th>
                            <th>Thời gian đánh giá</th>
                            <th class="text-center">Điểm SV</th>
                            <th class="text-center">Điểm lớp</th>
                            <th class="text-center">Điểm cuối</th>
                            <th class="text-center">Xếp loại</th>
                            <th class="text-center">Trạng thái</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(row, index) in filteredResults" :key="row.id">
                            <td class="action-cell">{{ index + 1 }}</td>

                            <td class="action-cell">
                                <strong>{{ row.dot_danh_gia.nam_hoc }}</strong>
                            </td>

                            <td class="action-cell">
                                {{ formatHocKy(row.dot_danh_gia.hoc_ky) }}
                            </td>

                            <td>
                                <div class="date-cell">
                                    <span>{{ formatDate(row.dot_danh_gia.ngay_bat_dau) }}</span>
                                    <small>đến {{ formatDate(row.dot_danh_gia.ngay_ket_thuc) }}</small>
                                </div>
                            </td>

                            <td class="action-cell text-center">
                                <strong>{{ row.tong_diem_sv }}</strong>
                            </td>

                            <td class="action-cell text-center">
                                <strong>{{ hasClassScore(row) ? row.tong_diem_lop : '—' }}</strong>
                            </td>

                            <td class="action-cell text-center">
                                <strong class="final-score">
                                    {{ getFinalScore(row) }}
                                </strong>
                            </td>

                            <td class="action-cell text-center">
                                <span class="rank-badge" :class="'rank-' + getFinalRank(row)">
                                    {{ formatXepLoai(getFinalRank(row)) }}
                                </span>
                            </td>

                            <td class="action-cell text-center">
                                <span class="status-badge" :class="'status-' + row.trang_thai">
                                    {{ formatTrangThai(row.trang_thai) }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="filteredResults.length === 0">
                            <td colspan="10" class="empty-row">
                                Không tìm thấy kết quả rèn luyện phù hợp.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

import "../../../assets/css/admin.css"
import "../../../assets/css/dark-theme.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const router = useRouter()

const API = 'http://127.0.0.1:8000/api/sinh-vien/ket-qua-ren-luyen/get-data'

const search = ref('')
const filterStatus = ref('all')
const loading = ref(false)

const currentUser = reactive({
    id: null,
    ho_ten: '',
    email: '',
    so_dien_thoai: '',
    ngay_sinh: '',
    vai_tro_id: null,
    trang_thai: '',
})

const currentSinhVien = reactive({
    id: null,
    ma_sinh_vien: '',
    nam_trung_tuyen: '',
    user_id: null,
    lop_id: null,

    lop: {
        id: null,
        ma_lop: '',
        ten_lop: '',

        khoa: {
            id: null,
            ma_khoa: '',
            ten_khoa: '',
        },

        nganh: {
            id: null,
            ma_nganh: '',
            ten_nganh: '',
        },
    },
})

const phieuRenLuyenList = ref([])

const thongKe = reactive({
    diem_hien_tai: null,
    xep_loai_hien_tai: null,
    so_phieu_hoan_thanh: 0,
    diem_cao_nhat: null,
})

function authHeader() {
    const token =
        sessionStorage.getItem('auth_token') ||
        localStorage.getItem('auth_token') ||
        localStorage.getItem('key_admin')

    return {
        headers: {
            Authorization: 'Bearer ' + token,
        },
    }
}

onMounted(() => {
    getKetQuaRenLuyen()
})

function getKetQuaRenLuyen() {
    loading.value = true

    axios
        .get(API, authHeader())
        .then((res) => {
            if (!res.data.status) {
                alert(res.data.message || 'Không lấy được kết quả rèn luyện!')
                return
            }

            const user = res.data.current_user || {}
            const sinhVien = res.data.current_sinh_vien || {}
            const ketQua = res.data.ket_qua || []
            const tk = res.data.thong_ke || {}

            Object.assign(currentUser, {
                id: user.id || null,
                ho_ten: user.ho_ten || '',
                email: user.email || '',
                so_dien_thoai: user.so_dien_thoai || '',
                ngay_sinh: user.ngay_sinh || '',
                vai_tro_id: user.vai_tro_id || null,
                trang_thai: user.trang_thai || '',
            })

            Object.assign(currentSinhVien, {
                id: sinhVien.id || null,
                ma_sinh_vien: sinhVien.ma_sinh_vien || '',
                nam_trung_tuyen: sinhVien.nam_trung_tuyen || '',
                user_id: sinhVien.user_id || null,
                lop_id: sinhVien.lop_id || null,
                lop: {
                    id: sinhVien.lop?.id || null,
                    ma_lop: sinhVien.lop?.ma_lop || '',
                    ten_lop: sinhVien.lop?.ten_lop || '',
                    khoa: {
                        id: sinhVien.lop?.khoa?.id || null,
                        ma_khoa: sinhVien.lop?.khoa?.ma_khoa || '',
                        ten_khoa: sinhVien.lop?.khoa?.ten_khoa || '',
                    },
                    nganh: {
                        id: sinhVien.lop?.nganh?.id || null,
                        ma_nganh: sinhVien.lop?.nganh?.ma_nganh || '',
                        ten_nganh: sinhVien.lop?.nganh?.ten_nganh || '',
                    },
                },
            })

            phieuRenLuyenList.value = ketQua

            Object.assign(thongKe, {
                diem_hien_tai: tk.diem_hien_tai ?? null,
                xep_loai_hien_tai: tk.xep_loai_hien_tai ?? null,
                so_phieu_hoan_thanh: tk.so_phieu_hoan_thanh ?? 0,
                diem_cao_nhat: tk.diem_cao_nhat ?? null,
            })
        })
        .catch((err) => {
            console.log('Lỗi lấy kết quả rèn luyện:', err.response?.data || err)
            alert(err.response?.data?.message || 'Lỗi lấy kết quả rèn luyện!')
        })
        .finally(() => {
            loading.value = false
        })
}

const filteredResults = computed(() => {
    const q = search.value.trim().toLowerCase()

    return phieuRenLuyenList.value.filter((item) => {
        const hocKyText = formatHocKy(item.dot_danh_gia?.hoc_ky).toLowerCase()
        const namHocText = String(item.dot_danh_gia?.nam_hoc || '').toLowerCase()
        const trangThaiText = formatTrangThai(item.trang_thai).toLowerCase()

        const matchSearch =
            !q ||
            hocKyText.includes(q) ||
            namHocText.includes(q) ||
            trangThaiText.includes(q)

        const matchStatus =
            filterStatus.value === 'all' ||
            item.trang_thai === filterStatus.value

        return matchSearch && matchStatus
    })
})

const latestResult = computed(() => {
    if (!phieuRenLuyenList.value.length) return null

    return [...phieuRenLuyenList.value].sort((a, b) => {
        const dateA = new Date(String(a.updated_at || '').replace(' ', 'T'))
        const dateB = new Date(String(b.updated_at || '').replace(' ', 'T'))

        return dateB - dateA
    })[0]
})

const completedCount = computed(() => {
    return phieuRenLuyenList.value.filter((item) => item.trang_thai === 'hoan_thanh').length
})

const highestScore = computed(() => {
    if (!phieuRenLuyenList.value.length) return '—'

    return Math.max(
        ...phieuRenLuyenList.value.map((item) => Number(getFinalScore(item) || 0))
    )
})

function hasClassScore(row) {
    return [
        'lop_da_duyet',
        'co_van_da_duyet',
        'hoan_thanh',
    ].includes(row.trang_thai)
}

function getFinalScore(row) {
    if (!row) return '—'

    if (row.tong_diem_cuoi !== null && row.tong_diem_cuoi !== undefined) {
        return row.tong_diem_cuoi
    }

    if (hasClassScore(row)) {
        return row.tong_diem_lop || 0
    }

    return row.tong_diem_sv || 0
}

function getFinalRank(row) {
    if (!row) return null

    if (row.xep_loai) {
        return row.xep_loai
    }

    return classifyScore(getFinalScore(row))
}

function classifyScore(score) {
    const value = Number(score || 0)

    if (value >= 90) return 'xuat_sac'
    if (value >= 80) return 'tot'
    if (value >= 65) return 'kha'
    if (value >= 50) return 'trung_binh'
    if (value >= 35) return 'yeu'

    return 'kem'
}

function formatHocKy(value) {
    if (value === 1 || value === '1') return 'Kỳ I'
    if (value === 2 || value === '2') return 'Kỳ II'

    return value ? `Kỳ ${value}` : '—'
}

function formatTrangThai(value) {
    const map = {
        nhap: 'Nháp',
        da_gui: 'Đã gửi',
        lop_da_duyet: 'Lớp đã duyệt',
        co_van_da_duyet: 'Cố vấn đã duyệt',
        hoan_thanh: 'Hoàn thành',
    }

    return map[value] || '—'
}

function formatXepLoai(value) {
    const map = {
        xuat_sac: 'Xuất sắc',
        tot: 'Tốt',
        kha: 'Khá',
        trung_binh: 'Trung bình',
        yeu: 'Yếu',
        kem: 'Kém',
    }

    return map[value] || '—'
}

function formatDate(value) {
    if (!value) return '—'

    const date = new Date(String(value).replace(' ', 'T'))

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toLocaleDateString('vi-VN')
}

function getInitials(name) {
    return String(name || '')
        .trim()
        .split(/\s+/)
        .map((word) => word[0])
        .slice(-2)
        .join('')
        .toUpperCase()
}
</script>
<style scoped>
.result-page {
    padding-bottom: 40px;
}

:global(html.dark-theme) .result-page {
    --bg-main: #0b1120;
    --bg-sidebar: #111827;
    --bg-card: #1e293b;
    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
    --border-color: rgba(255, 255, 255, 0.08);
    --accent: #dc2626;
}

/* STUDENT CARD */
.student-result-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 18px;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.student-avatar {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    background: rgba(220, 38, 38, 0.12);
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    flex-shrink: 0;
}

.student-result-card span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
    margin-bottom: 4px;
}

.student-result-card strong {
    display: block;
    color: var(--text-primary);
    font-size: 20px;
    font-weight: 800;
}

.student-result-card p {
    color: var(--text-secondary);
    margin: 4px 0 0;
    font-size: 14px;
}

/* STATS */
.result-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 18px;
}

.result-stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
}

.result-stat-card span {
    display: block;
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 8px;
}

.result-stat-card strong {
    display: block;
    color: var(--text-primary);
    font-size: 32px;
    line-height: 1;
}

.date-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.date-cell span {
    color: var(--text-primary);
    font-weight: 700;
}

.date-cell small {
    color: var(--text-secondary);
}

.action-cell {
    white-space: nowrap;
}

.text-center {
    text-align: center !important;
}

.row-actions-center {
    justify-content: center;
}

.final-score {
    color: var(--accent);
}

/* STATUS */
.status-badge,
.rank-badge {
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

/* RANK */
.rank-xuat_sac {
    background: rgba(20, 184, 166, 0.16);
    color: #0f766e;
    border-color: rgba(20, 184, 166, 0.32);
}

.rank-tot {
    background: rgba(34, 197, 94, 0.16);
    color: #15803d;
    border-color: rgba(34, 197, 94, 0.32);
}

.rank-kha {
    background: rgba(59, 130, 246, 0.16);
    color: #2563eb;
    border-color: rgba(59, 130, 246, 0.32);
}

.rank-trung_binh {
    background: rgba(245, 158, 11, 0.16);
    color: #b45309;
    border-color: rgba(245, 158, 11, 0.32);
}

.rank-yeu,
.rank-kem {
    background: rgba(239, 68, 68, 0.16);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, 0.32);
}

/* BUTTON */
.action-btn {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;

    border-radius: 10px;
    padding: 8px 12px;

    font-size: 13px;
    font-weight: 800;
    cursor: pointer;

    border: 1px solid var(--border-color);
    transition: 0.18s ease;
}

.action-btn.ghost {
    background: var(--bg-card);
    color: var(--text-primary);
}

.action-btn.ghost:hover {
    background: rgba(148, 163, 184, 0.10);
}

/* RESPONSIVE */
@media (max-width: 1100px) {
    .result-stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .result-stat-grid {
        grid-template-columns: 1fr;
    }

    .student-result-card {
        align-items: flex-start;
    }

    .action-btn span {
        display: none;
    }
}
</style>