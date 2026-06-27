<template>
    <div class="admin-content training-page class-evaluation-list-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Phiếu lớp chờ đánh giá</h1>
            <p>
                Lớp trưởng/Bí thư xem danh sách phiếu rèn luyện của sinh viên cùng lớp.
            </p>
        </div>

        <!-- THÔNG TIN LỚP -->
        <div class="class-info-card">
            <div class="class-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <div>
                <span>Lớp đang quản lý</span>
                <strong>
                    {{ lopDangQuanLy.ma_lop }} - {{ lopDangQuanLy.ten_lop }}
                </strong>
                <p>
                    {{ lopDangQuanLy.khoa.ten_khoa }}
                    ·
                    {{ lopDangQuanLy.nganh.ten_nganh }}
                </p>
            </div>
        </div>

        <!-- THỐNG KÊ NHANH -->
        <div class="review-stat-grid">
            <div class="review-stat-card">
                <span>Tổng phiếu trong lớp</span>
                <strong>{{ classmateForms.length }}</strong>
            </div>

            <div class="review-stat-card warning">
                <span>Đã gửi phiếu</span>
                <strong>{{ countByStatus('da_gui') }}</strong>
            </div>

            <div class="review-stat-card draft">
                <span>Chờ lớp đánh giá</span>
                <strong>{{ waitingClassReview }}</strong>
            </div>

            <div class="review-stat-card success">
                <span>Lớp đã đánh giá</span>
                <strong>{{ countByStatus('lop_da_danh_gia') }}</strong>
            </div>
        </div>

        <!-- PANEL DANH SÁCH -->
        <div class="training-panel">

            <!-- TOOLBAR -->
            <div class="toolbar">
                <div class="search-box">
                    <input v-model="search" type="text" placeholder="Tìm theo mã sinh viên, họ tên, email..." />
                </div>

                <div class="filter-select">
                    <select v-model="filterStatus">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="da_gui">Đã gửi</option>
                        <option value="lop_da_danh_gia">Lớp đã đánh giá</option>
                        <option value="co_van_da_kiem_tra">CVHT đã kiểm tra</option>
                        <option value="hoan_thanh">Hoàn thành</option>
                        <option value="tra_lai">Trả lại</option>
                    </select>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 48px;">STT</th>
                            <th>Mã SV</th>
                            <th>Họ tên</th>
                            <th>Lớp</th>
                            <th>Ngành</th>
                            <th>Ngày cập nhật</th>
                            <th class="text-center">Điểm SV</th>
                            <th class="text-center">Điểm lớp</th>
                            <th class="text-center">Trạng thái</th>
                            <th style="width: 150px;" class="text-center">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(row, index) in filteredForms" :key="row.id">
                            <td class="action-cell">
                                {{ index + 1 }}
                            </td>

                            <td class="action-cell">
                                <strong>{{ row.sinh_vien.ma_sinh_vien }}</strong>
                            </td>

                            <td>
                                <div class="student-name-cell">
                                    <strong>{{ row.sinh_vien.nguoi_dung.ho_ten }}</strong>
                                    <span>{{ row.sinh_vien.nguoi_dung.email || 'Chưa có email' }}</span>
                                </div>
                            </td>

                            <td class="action-cell">
                                {{ row.sinh_vien.lop.ma_lop }}
                            </td>

                            <td>
                                {{ row.sinh_vien.lop.nganh.ten_nganh }}
                            </td>

                            <td class="action-cell">
                                {{ formatDate(row.updated_at) }}
                            </td>

                            <td class="action-cell text-center">
                                <strong>{{ row.tong_diem_sv }}</strong>
                            </td>

                            <td class="action-cell text-center">
                                <strong>
                                    {{ hasClassScore(row) ? row.tong_diem_lop : '—' }}
                                </strong>
                            </td>

                            <td class="action-cell text-center">
                                <span class="status-badge" :class="'status-' + row.trang_thai">
                                    {{ formatTrangThai(row.trang_thai) }}
                                </span>
                            </td>

                            <td class="action-cell">
                                <div class="row-actions row-actions-center">
                                    <button v-if="canReview(row)" type="button" class="action-btn primary"
                                        title="Đánh giá phiếu" @click="goToEvaluation(row, 'edit')">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        <span>Đánh giá</span>
                                    </button>

                                    <button v-else type="button" class="action-btn ghost" title="Xem phiếu"
                                        @click="goToEvaluation(row, 'view')">
                                        <i class="fa-solid fa-eye"></i>
                                        <span>Xem</span>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredForms.length === 0">
                            <td colspan="10" class="empty-row">
                                Không tìm thấy phiếu đánh giá phù hợp.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import "../../../assets/css/admin.css"
import "../../../assets/css/dark-theme.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const router = useRouter()

const search = ref('')
const filterStatus = ref('all')

/*
|--------------------------------------------------------------------------
| MOCK ĐÚNG DATABASE
|--------------------------------------------------------------------------
| currentUser      -> nguoi_dung
| currentSinhVien  -> sinh_vien
| lopDangQuanLy    -> lop + khoa + nganh
| phieuRenLuyen    -> phieu_ren_luyen + sinh_vien + nguoi_dung + lop
|--------------------------------------------------------------------------
*/

const currentUser = reactive({
    id: 1,
    ho_ten: 'Nguyễn Văn A',
    email: 'sv001@student.edu.vn',
    vai_tro_id: 6,
    trang_thai: 'hoat_dong',
})

const lopDangQuanLy = reactive({
    id: 1,
    ma_lop: 'D23CQCN01',
    ten_lop: 'Công nghệ thông tin 01',

    khoa_id: 1,
    nganh_id: 1,
    co_van_id: 2,

    lop_truong_id: 1,
    bi_thu_id: 3,

    khoa: {
        id: 1,
        ma_khoa: 'CNTT',
        ten_khoa: 'Công nghệ thông tin',
    },

    nganh: {
        id: 1,
        ma_nganh: 'KTPM',
        ten_nganh: 'Kỹ thuật phần mềm',
    },
})

const currentSinhVien = reactive({
    id: 1,
    ma_sinh_vien: 'SV001',
    nam_trung_tuyen: 2023,
    user_id: currentUser.id,
    lop_id: lopDangQuanLy.id,
    nguoi_dung: currentUser,
    lop: lopDangQuanLy,
})

const dotDanhGia = reactive({
    id: 1,
    nam_hoc: '2025 - 2026',
    hoc_ky: 1,
    ngay_bat_dau: '2026-06-01 00:00:00',
    ngay_ket_thuc: '2026-06-30 23:59:59',
    trang_thai: 'dang_mo',
})

const isOfficer = computed(() => {
    return (
        currentSinhVien.id === lopDangQuanLy.lop_truong_id ||
        currentSinhVien.id === lopDangQuanLy.bi_thu_id
    )
})

const phieuRenLuyen = ref([
    {
        id: 1,
        sinh_vien_id: 1,
        dot_danh_gia_id: dotDanhGia.id,

        tong_diem_sv: 86,
        tong_diem_lop: 0,

        xep_loai_sv: 'tot',
        xep_loai_lop: null,

        trang_thai: 'da_gui',

        created_at: '2026-06-10 08:00:00',
        updated_at: '2026-06-12 09:20:00',

        sinh_vien: {
            id: 1,
            ma_sinh_vien: 'SV001',
            nam_trung_tuyen: 2023,
            user_id: 1,
            lop_id: 1,

            nguoi_dung: {
                id: 1,
                ho_ten: 'Nguyễn Văn A',
                email: 'sv001@student.edu.vn',
                so_dien_thoai: '0901234567',
                ngay_sinh: '15/01/2005',
                vai_tro_id: 6,
                trang_thai: 'hoat_dong',
            },

            lop: lopDangQuanLy,
        },

        dot_danh_gia: dotDanhGia,
    },

    {
        id: 2,
        sinh_vien_id: 2,
        dot_danh_gia_id: dotDanhGia.id,

        tong_diem_sv: 85,
        tong_diem_lop: 0,

        xep_loai_sv: 'tot',
        xep_loai_lop: null,

        trang_thai: 'da_gui',

        created_at: '2026-06-11 08:00:00',
        updated_at: '2026-06-12 10:30:00',

        sinh_vien: {
            id: 2,
            ma_sinh_vien: 'SV002',
            nam_trung_tuyen: 2023,
            user_id: 2,
            lop_id: 1,

            nguoi_dung: {
                id: 2,
                ho_ten: 'Trần Thị Bình',
                email: 'sv002@student.edu.vn',
                so_dien_thoai: '0912345678',
                ngay_sinh: '20/03/2005',
            vai_tro_id: 6,
                trang_thai: 'hoat_dong',
            },

            lop: lopDangQuanLy,
        },

        dot_danh_gia: dotDanhGia,
    },

    {
        id: 3,
        sinh_vien_id: 3,
        dot_danh_gia_id: dotDanhGia.id,

        tong_diem_sv: 78,
        tong_diem_lop: 0,

        xep_loai_sv: 'kha',
        xep_loai_lop: null,

        trang_thai: 'da_gui',

        created_at: '2026-06-11 08:00:00',
        updated_at: '2026-06-12 11:20:00',

        sinh_vien: {
            id: 3,
            ma_sinh_vien: 'SV003',
            nam_trung_tuyen: 2023,
            user_id: 3,
            lop_id: 1,

            nguoi_dung: {
                id: 3,
                ho_ten: 'Lê Văn Cường',
                email: 'sv003@student.edu.vn',
                so_dien_thoai: '0922222222',
                ngay_sinh: '11/07/2005',
                vai_tro_id: 6,
                trang_thai: 'hoat_dong',
            },

            lop: lopDangQuanLy,
        },

        dot_danh_gia: dotDanhGia,
    },

    {
        id: 4,
        sinh_vien_id: 4,
        dot_danh_gia_id: dotDanhGia.id,

        tong_diem_sv: 92,
        tong_diem_lop: 90,

        xep_loai_sv: 'xuat_sac',
        xep_loai_lop: 'xuat_sac',

        trang_thai: 'lop_da_danh_gia',

        created_at: '2026-06-10 08:00:00',
        updated_at: '2026-06-12 14:45:00',

        sinh_vien: {
            id: 4,
            ma_sinh_vien: 'SV004',
            nam_trung_tuyen: 2023,
            user_id: 4,
            lop_id: 1,

            nguoi_dung: {
                id: 4,
                ho_ten: 'Phạm Thị Dung',
                email: 'sv004@student.edu.vn',
                so_dien_thoai: '0933333333',
                ngay_sinh: '03/02/2005',
                vai_tro_id: 6,
                trang_thai: 'hoat_dong',
            },

            lop: lopDangQuanLy,
        },

        dot_danh_gia: dotDanhGia,
    },

    {
        id: 5,
        sinh_vien_id: 5,
        dot_danh_gia_id: dotDanhGia.id,

        tong_diem_sv: 70,
        tong_diem_lop: 68,

        xep_loai_sv: 'kha',
        xep_loai_lop: 'kha',

        trang_thai: 'co_van_da_kiem_tra',

        created_at: '2026-06-09 08:00:00',
        updated_at: '2026-06-13 09:15:00',

        sinh_vien: {
            id: 5,
            ma_sinh_vien: 'SV005',
            nam_trung_tuyen: 2023,
            user_id: 5,
            lop_id: 1,

            nguoi_dung: {
                id: 5,
                ho_ten: 'Đỗ Văn Hùng',
                email: 'sv005@student.edu.vn',
                so_dien_thoai: '0944444444',
                ngay_sinh: '09/09/2005',
                vai_tro_id: 6,
                trang_thai: 'hoat_dong',
            },

            lop: lopDangQuanLy,
        },

        dot_danh_gia: dotDanhGia,
    },

    {
        id: 6,
        sinh_vien_id: 6,
        dot_danh_gia_id: dotDanhGia.id,

        tong_diem_sv: 65,
        tong_diem_lop: 0,

        xep_loai_sv: 'kha',
        xep_loai_lop: null,

        trang_thai: 'tra_lai',

        created_at: '2026-06-08 08:00:00',
        updated_at: '2026-06-13 15:00:00',

        sinh_vien: {
            id: 6,
            ma_sinh_vien: 'SV006',
            nam_trung_tuyen: 2023,
            user_id: 6,
            lop_id: 1,

            nguoi_dung: {
                id: 6,
                ho_ten: 'Hoàng Minh Khang',
                email: 'sv006@student.edu.vn',
                so_dien_thoai: '0955555555',
                ngay_sinh: '12/12/2005',
                vai_tro_id: 6,
                trang_thai: 'hoat_dong',
            },

            lop: lopDangQuanLy,
        },

        dot_danh_gia: dotDanhGia,
    },
])

const classmateForms = computed(() => {
    return phieuRenLuyen.value.filter((item) => {
        const sameClass = item.sinh_vien.lop_id === currentSinhVien.lop_id
        const notSelf = item.sinh_vien_id !== currentSinhVien.id

        return sameClass && notSelf
    })
})

const filteredForms = computed(() => {
    const q = search.value.trim().toLowerCase()

    return classmateForms.value.filter((item) => {
        const student = item.sinh_vien
        const user = student.nguoi_dung

        const matchSearch =
            !q ||
            student.ma_sinh_vien.toLowerCase().includes(q) ||
            user.ho_ten.toLowerCase().includes(q) ||
            String(user.email || '').toLowerCase().includes(q)

        const matchStatus =
            filterStatus.value === 'all' ||
            item.trang_thai === filterStatus.value

        return matchSearch && matchStatus
    })
})

const waitingClassReview = computed(() => {
    return classmateForms.value.filter((item) => item.trang_thai === 'da_gui').length
})

function countByStatus(status) {
    return classmateForms.value.filter((item) => item.trang_thai === status).length
}

function hasClassScore(row) {
    return [
        'lop_da_danh_gia',
        'co_van_da_kiem_tra',
        'hoan_thanh',
    ].includes(row.trang_thai)
}

function canReview(row) {
    return isOfficer.value && row.trang_thai === 'da_gui'
}

function goToEvaluation(row, mode = 'view') {
    router.push({
        path: `/sinh-vien/phieu-lop/${row.id}`,
        query: {
            mode,
        },
    })
}

function formatTrangThai(value) {
    const map = {
        nhap: 'Nháp',
        da_gui: 'Đã gửi',
        lop_da_danh_gia: 'Lớp đã đánh giá',
        co_van_da_kiem_tra: 'CVHT đã kiểm tra',
        hoan_thanh: 'Hoàn thành',
        tra_lai: 'Trả lại',
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
</script>

<style scoped>
.class-evaluation-list-page {
    padding-bottom: 40px;
}

/* fallback nếu theme đang dùng html.dark-theme */
:global(html.dark-theme) .class-evaluation-list-page {
    --bg-main: #0b1120;
    --bg-sidebar: #111827;
    --bg-card: #1e293b;
    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
    --border-color: rgba(255, 255, 255, 0.08);
    --accent: #dc2626;
}

/* CLASS INFO */
.class-info-card {
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

.class-icon {
    width: 52px;
    height: 52px;
    border-radius: 16px;
    background: rgba(220, 38, 38, 0.12);
    color: #dc2626;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.class-info-card span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
    margin-bottom: 4px;
}

.class-info-card strong {
    display: block;
    color: var(--text-primary);
    font-size: 20px;
    font-weight: 800;
}

.class-info-card p {
    color: var(--text-secondary);
    margin: 4px 0 0;
    font-size: 14px;
}

/* STAT */
.review-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 18px;
}

.review-stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
}

.review-stat-card span {
    display: block;
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 8px;
}

.review-stat-card strong {
    display: block;
    color: var(--text-primary);
    font-size: 32px;
    line-height: 1;
}

/* TABLE */
.table-wrap {
    background: var(--bg-card);
}

.data-table {
    min-width: 1000px;
}

.student-name-cell {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.student-name-cell strong {
    color: var(--text-primary);
    font-weight: 800;
}

.student-name-cell span {
    color: var(--text-secondary);
    font-size: 13px;
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

/* STATUS BADGE */
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

/* STATUS DARK */
:global(html.dark-theme) .status-nhap,
:global([data-theme="dark"]) .status-nhap {
    background: rgba(129, 140, 248, 0.22);
    color: #c7d2fe;
    border-color: rgba(129, 140, 248, 0.42);
}

:global(html.dark-theme) .status-da_gui,
:global([data-theme="dark"]) .status-da_gui {
    background: rgba(251, 191, 36, 0.22);
    color: #fde68a;
    border-color: rgba(251, 191, 36, 0.42);
}

:global(html.dark-theme) .status-lop_da_danh_gia,
:global([data-theme="dark"]) .status-lop_da_danh_gia {
    background: rgba(96, 165, 250, 0.22);
    color: #bfdbfe;
    border-color: rgba(96, 165, 250, 0.42);
}

:global(html.dark-theme) .status-co_van_da_kiem_tra,
:global([data-theme="dark"]) .status-co_van_da_kiem_tra {
    background: rgba(74, 222, 128, 0.20);
    color: #bbf7d0;
    border-color: rgba(74, 222, 128, 0.42);
}

:global(html.dark-theme) .status-hoan_thanh,
:global([data-theme="dark"]) .status-hoan_thanh {
    background: rgba(45, 212, 191, 0.20);
    color: #99f6e4;
    border-color: rgba(45, 212, 191, 0.42);
}

:global(html.dark-theme) .status-tra_lai,
:global([data-theme="dark"]) .status-tra_lai {
    background: rgba(248, 113, 113, 0.22);
    color: #fecaca;
    border-color: rgba(248, 113, 113, 0.42);
}

/* ACTION BUTTON */
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

.action-btn.primary {
    background: var(--accent);
    color: #ffffff;
    border-color: var(--accent);
}

.action-btn.primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(220, 38, 38, 0.22);
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
    .review-stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .review-stat-grid {
        grid-template-columns: 1fr;
    }

    .class-info-card {
        align-items: flex-start;
    }

    .action-btn span {
        display: none;
    }
}
</style>