<template>
    <div class="admin-content training-page evaluation-list-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Danh sách phiếu chờ duyệt</h1>
            <p>CVHT xem danh sách phiếu rèn luyện của sinh viên và chọn phiếu cần duyệt.</p>
        </div>

        <!-- THỐNG KÊ NHANH -->
        <div class="review-stat-grid">
            <div class="review-stat-card">
                <span>Tổng phiếu</span>
                <strong>{{ evaluationForms.length }}</strong>
            </div>

            <div class="review-stat-card warning">
                <span>Đã gửi</span>
                <strong>{{ countByStatus('da_gui') }}</strong>
            </div>

            <div class="review-stat-card draft">
                <span>Lớp đã đánh giá</span>
                <strong>{{ countByStatus('lop_da_danh_gia') }}</strong>
            </div>

            <div class="review-stat-card success">
                <span>CVHT đã kiểm tra</span>
                <strong>{{ countByStatus('co_van_da_kiem_tra') }}</strong>
            </div>
        </div>

        <!-- PANEL DANH SÁCH -->
        <div class="training-panel">

            <!-- TOOLBAR -->
            <div class="toolbar flex items-center gap-4 flex-wrap w-full pb-4">
                <div class="search-box flex items-center shrink-0 flex-1 min-w-[250px]">
                    <input v-model="search" type="text" placeholder="Tìm theo mã sinh viên, họ tên, lớp..."
                        class="outline-none bg-transparent w-full" />
                </div>

                <div class="filter-select">
                    <select v-model="filterStatus">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="nhap">Nháp</option>
                        <option value="da_gui">Đã gửi</option>
                        <option value="lop_da_danh_gia">Lớp đã đánh giá</option>
                        <option value="co_van_da_kiem_tra">CVHT đã kiểm tra</option>
                        <option value="hoan_thanh">Hoàn thành</option>
                        <option value="tra_lai">Trả lại</option>
                    </select>
                </div>

                <div class="filter-select">
                    <select v-model="filterSemester">
                        <option value="all">Tất cả học kỳ</option>
                        <option value="Kỳ I">Kỳ I</option>
                        <option value="Kỳ II">Kỳ II</option>
                    </select>
                </div>

                <div class="filter-select">
                    <select v-model="filterClass">
                        <option value="all">Tất cả lớp</option>
                        <option v-for="lop in classOptions" :key="lop" :value="lop">
                            {{ lop }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-wrap">
                <table class="data-table w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th style="width:48px">STT</th>
                            <th>Mã SV</th>
                            <th>Họ tên</th>
                            <th>Lớp</th>
                            <th>Ngành</th>
                            <th>Học kỳ</th>
                            <th>Năm học</th>
                            <th>Ngày nộp</th>
                            <th>Điểm SV</th>
                            <th>Điểm lớp/CVHT</th>
                            <th>Trạng thái</th>
                            <th style="width:150px">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(row, index) in filteredForms" :key="row.id">
                            <td class="action-cell">{{ index + 1 }}</td>
                            <td class="action-cell">{{ row.ma_sinh_vien }}</td>
                            <td>
                                <div class="student-name-cell text-nowrap">
                                    <strong>{{ row.ho_ten }}</strong>
                                    <p>{{ row.khoa }}</p>
                                </div>
                            </td>
                            <td class="action-cell">{{ row.lop }}</td>
                            <td>{{ row.nganh }}</td>
                            <td class="action-cell">{{ row.hoc_ky }}</td>
                            <td class="action-cell">{{ row.nam_hoc }}</td>
                            <td class="action-cell">{{ row.ngay_nop }}</td>
                            <td class="action-cell">
                                <strong>{{ row.tong_diem_sv ?? '—' }}</strong>
                            </td>
                            <td class="action-cell">
                                <strong>{{ row.tong_diem_lop ?? '—' }}</strong>
                            </td>
                            <td class="action-cell text-center">
                                <span class="status-dot" :class="'st-' + row.trang_thai">
                                    {{ formatTrangThai(row.trang_thai) }}
                                </span>
                            </td>
                            <td class="action-cell">
                                <div class="row-actions">
                                        <button v-if="canReview(row)" class="icon-btn edit" type="button"
                                            title="Kiểm tra phiếu" @click="goToDetail(row, 'edit')">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <button v-else class="icon-btn view" type="button" title="Xem chi tiết"
                                            @click="goToDetail(row, 'view')">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredForms.length === 0">
                            <td colspan="12" class="empty-row text-center py-4 action-cell">
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
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const router = useRouter()

const search = ref('')
const filterStatus = ref('all')
const filterSemester = ref('all')
const filterClass = ref('all')

const evaluationForms = ref([
    {
        id: 1, // id này là phieu_ren_luyen.id
        ma_sinh_vien: 'SV001',
        ho_ten: 'Nguyễn Văn A',
        khoa: 'Công nghệ thông tin',
        nganh: 'Kỹ thuật phần mềm',
        lop: 'D23CQCN01',
        hoc_ky: 'Kỳ I',
        nam_hoc: '2025 - 2026',
        ngay_nop: '12/06/2026',
        tong_diem_sv: 90,
        tong_diem_lop: null,
        trang_thai: 'lop_da_danh_gia',
    },
    {
        id: 2,
        ma_sinh_vien: 'SV002',
        ho_ten: 'Trần Thị Bình',
        khoa: 'Công nghệ thông tin',
        nganh: 'Kỹ thuật phần mềm',
        lop: 'D23CQCN01',
        hoc_ky: 'Kỳ I',
        nam_hoc: '2025 - 2026',
        ngay_nop: '13/06/2026',
        tong_diem_sv: 85,
        tong_diem_lop: null,
        trang_thai: 'da_gui',
    },
    {
        id: 3,
        ma_sinh_vien: 'SV003',
        ho_ten: 'Lê Văn Cường',
        khoa: 'Công nghệ thông tin',
        nganh: 'Hệ thống thông tin',
        lop: 'D23CQCN02',
        hoc_ky: 'Kỳ I',
        nam_hoc: '2025 - 2026',
        ngay_nop: '13/06/2026',
        tong_diem_sv: 78,
        tong_diem_lop: null,
        trang_thai: 'lop_da_danh_gia',
    },
    {
        id: 4,
        ma_sinh_vien: 'SV004',
        ho_ten: 'Phạm Thị Dung',
        khoa: 'Công nghệ thông tin',
        nganh: 'Hệ thống thông tin',
        lop: 'D23CQCN02',
        hoc_ky: 'Kỳ I',
        nam_hoc: '2025 - 2026',
        ngay_nop: '11/06/2026',
        tong_diem_sv: 92,
        tong_diem_lop: 90,
        trang_thai: 'co_van_da_kiem_tra',
    },
    {
        id: 5,
        ma_sinh_vien: 'SV005',
        ho_ten: 'Đỗ Văn Hùng',
        khoa: 'Công nghệ thông tin',
        nganh: 'Kỹ thuật phần mềm',
        lop: 'D23CQCN01',
        hoc_ky: 'Kỳ I',
        nam_hoc: '2025 - 2026',
        ngay_nop: '10/06/2026',
        tong_diem_sv: 70,
        tong_diem_lop: 65,
        trang_thai: 'tra_lai',
    },
])

const classOptions = computed(() => {
    return [...new Set(evaluationForms.value.map((item) => item.lop))]
})

const filteredForms = computed(() => {
    const q = search.value.trim().toLowerCase()

    return evaluationForms.value.filter((item) => {
        const matchSearch =
            !q ||
            item.ma_sinh_vien.toLowerCase().includes(q) ||
            item.ho_ten.toLowerCase().includes(q) ||
            item.lop.toLowerCase().includes(q) ||
            item.nganh.toLowerCase().includes(q)

        const matchStatus =
            filterStatus.value === 'all' ||
            item.trang_thai === filterStatus.value

        const matchSemester =
            filterSemester.value === 'all' ||
            item.hoc_ky === filterSemester.value

        const matchClass =
            filterClass.value === 'all' ||
            item.lop === filterClass.value

        return matchSearch && matchStatus && matchSemester && matchClass
    })
})

function countByStatus(status) {
    return evaluationForms.value.filter((item) => item.trang_thai === status).length
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

function canReview(row) {
    return row.trang_thai === 'da_gui' || row.trang_thai === 'lop_da_danh_gia'
}

function goToDetail(row, mode = 'view') {
    router.push({
        name: 'AdminChiTietPhieuDanhGia',
        params: {
            id: row.id,
        },
        query: {
            mode,
        },
    })
}
</script>

<style scoped>
.evaluation-list-page {
    padding-bottom: 40px;
}

.review-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 18px;
}

.row-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    flex-wrap: nowrap;
}

.icon-btn {
    width: 32px;
    height: 32px;
    min-width: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.action-cell {
    white-space: nowrap;
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

.student-name-cell strong {
    color: var(--text-primary);
    font-weight: 700;
}

.student-name-cell span {
    color: var(--text-secondary);
    font-size: 13px;
}

.filter-select select {
    height: 42px;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    padding: 0 12px;
    background: var(--bg-card);
    color: var(--text-primary);
    outline: none;
}

.filter-select select option {
    background: var(--bg-card);
    color: var(--text-primary);
}

.action-cell strong {
    color: var(--text-primary);
}

/* STATUS */
.status-dot {
    width: auto;
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;

    border-radius: 999px;
    padding: 7px 14px;

    font-size: 13px;
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;

    border: 1px solid transparent;
}

/* LIGHT MODE */
.status-dot.st-nhap {
    background: #eef2ff !important;
    color: #4338ca !important;
    border-color: #c7d2fe !important;
}

.status-dot.st-da_gui {
    background: #fff7ed !important;
    color: #c2410c !important;
    border-color: #fed7aa !important;
}

.status-dot.st-lop_da_danh_gia {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    border-color: #bfdbfe !important;
}

.status-dot.st-co_van_da_kiem_tra {
    background: #ecfdf5 !important;
    color: #047857 !important;
    border-color: #bbf7d0 !important;
}

.status-dot.st-hoan_thanh {
    background: #f0fdfa !important;
    color: #0f766e !important;
    border-color: #99f6e4 !important;
}

.status-dot.st-tra_lai {
    background: #fef2f2 !important;
    color: #b91c1c !important;
    border-color: #fecaca !important;
}

/* DARK MODE - dùng selector mạnh theo page */
:global(html.dark-theme) .evaluation-list-page .status-dot.st-nhap,
:global(html[data-theme="dark"]) .evaluation-list-page .status-dot.st-nhap,
:global(body.dark-theme) .evaluation-list-page .status-dot.st-nhap {
    background: rgba(99, 102, 241, 0.24) !important;
    color: #c7d2fe !important;
    border-color: rgba(165, 180, 252, 0.38) !important;
    box-shadow: inset 0 0 0 1px rgba(199, 210, 254, 0.06);
}

:global(html.dark-theme) .evaluation-list-page .status-dot.st-da_gui,
:global(html[data-theme="dark"]) .evaluation-list-page .status-dot.st-da_gui,
:global(body.dark-theme) .evaluation-list-page .status-dot.st-da_gui {
    background: rgba(245, 158, 11, 0.24) !important;
    color: #fde68a !important;
    border-color: rgba(251, 191, 36, 0.42) !important;
    box-shadow: inset 0 0 0 1px rgba(253, 230, 138, 0.06);
}

:global(html.dark-theme) .evaluation-list-page .status-dot.st-lop_da_danh_gia,
:global(html[data-theme="dark"]) .evaluation-list-page .status-dot.st-lop_da_danh_gia,
:global(body.dark-theme) .evaluation-list-page .status-dot.st-lop_da_danh_gia {
    background: rgba(37, 99, 235, 0.28) !important;
    color: #bfdbfe !important;
    border-color: rgba(96, 165, 250, 0.45) !important;
    box-shadow: inset 0 0 0 1px rgba(191, 219, 254, 0.06);
}

:global(html.dark-theme) .evaluation-list-page .status-dot.st-co_van_da_kiem_tra,
:global(html[data-theme="dark"]) .evaluation-list-page .status-dot.st-co_van_da_kiem_tra,
:global(body.dark-theme) .evaluation-list-page .status-dot.st-co_van_da_kiem_tra {
    background: rgba(22, 163, 74, 0.28) !important;
    color: #bbf7d0 !important;
    border-color: rgba(74, 222, 128, 0.42) !important;
    box-shadow: inset 0 0 0 1px rgba(187, 247, 208, 0.06);
}

:global(html.dark-theme) .evaluation-list-page .status-dot.st-hoan_thanh,
:global(html[data-theme="dark"]) .evaluation-list-page .status-dot.st-hoan_thanh,
:global(body.dark-theme) .evaluation-list-page .status-dot.st-hoan_thanh {
    background: rgba(13, 148, 136, 0.28) !important;
    color: #99f6e4 !important;
    border-color: rgba(45, 212, 191, 0.42) !important;
    box-shadow: inset 0 0 0 1px rgba(153, 246, 228, 0.06);
}

:global(html.dark-theme) .evaluation-list-page .status-dot.st-tra_lai,
:global(html[data-theme="dark"]) .evaluation-list-page .status-dot.st-tra_lai,
:global(body.dark-theme) .evaluation-list-page .status-dot.st-tra_lai {
    background: rgba(220, 38, 38, 0.26) !important;
    color: #fecaca !important;
    border-color: rgba(248, 113, 113, 0.44) !important;
    box-shadow: inset 0 0 0 1px rgba(254, 202, 202, 0.06);
}


/* RESPONSIVE */
@media (max-width: 992px) {
    .review-stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .review-stat-grid {
        grid-template-columns: 1fr;
    }
}
</style>