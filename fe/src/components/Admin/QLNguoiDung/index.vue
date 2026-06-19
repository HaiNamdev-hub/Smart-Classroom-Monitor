<template>
    <div class="admin-content user-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Quản lý người dùng</h1>
            <p>Quản lý tài khoản cố vấn học tập, sinh viên, lớp trưởng và bí thư trong hệ thống.</p>
        </div>

        <!-- TOOLBAR -->
        <div class="toolbar flex items-center gap-4 flex-nowrap overflow-x-auto w-full pb-2">

            <!-- SEARCH BOX -->
            <div class="search-box flex items-center shrink-0 flex-1 min-w-[250px]">
                <input v-model="search" @input="resetPage" type="text" placeholder="Tìm theo họ tên, email hoặc lớp..."
                    class="outline-none bg-transparent w-full" />
            </div>

            <!-- FILTER SELECT  VAI TRÒ -->
            <div class="filter-select shrink-0">
                <select v-model="filterRole" @change="resetPage" class="cursor-pointer outline-none">
                    <option value="all">Tất cả vai trò hiển thị</option>
                    <option value="co_van">Cố vấn học tập</option>
                    <option value="sinh_vien">Sinh viên</option>
                    <option value="lop_truong">Lớp trưởng</option>
                    <option value="bi_thu">Bí thư</option>
                </select>
            </div>

            <!-- FILTER SELECT TRẠNG THÁI-->
            <div class="filter-select shrink-0">
                <select v-model="filterStatus" @change="resetPage" class="cursor-pointer outline-none">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="hoat_dong">Hoạt động</option>
                    <option value="khong_hoat_dong">Không hoạt động</option>
                    <option value="bi_khoa">Bị khóa</option>
                </select>
            </div>

            <!-- ADD BUTTON -->
            <button class="btn-primary flex items-center justify-center gap-2 text-nowrap shrink-0 ml-auto"
                @click="openAdd" data-bs-toggle="modal" data-bs-target="#userFormModal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Thêm người dùng
            </button>
        </div>

        <!-- TABLE -->
        <div class="table-wrap">
            <table class="data-table w-full text-left border-collapse">

                <!-- TABLE HEAD -->
                <thead>
                    <tr class="border-b">
                        <th style="width:48px" class="text-left align-middle text-nowrap py-3">STT</th>
                        <th class="text-left align-middle text-nowrap py-3">Họ tên</th>
                        <th class="text-left align-middle text-nowrap py-3">Lớp</th>
                        <th class="text-left align-middle text-nowrap py-3">Vai trò</th>
                        <th class="text-left align-middle text-nowrap py-3">Trạng thái</th>
                        <th style="width:200px" class="text-left align-middle text-nowrap py-3">Thao tác</th>
                    </tr>
                </thead>

                <!-- TABLE BODY -->
                <tbody>

                    <tr v-for="(u, i) in paged" :key="u.id" class="border-b hover:bg-gray-50">

                        <!-- ROW ID -->
                        <td class="align-middle py-2">{{ (currentPage - 1) * pageSize + i + 1 }}</td>

                        <!-- ROW USER -->
                        <td class="align-middle py-2">
                            <div class="cell-user flex items-center gap-3">
                                <div class="cell-avatar shrink-0">{{ initials(u.ho_ten) }}</div>
                                <div class="cell-name text-nowrap">
                                    <strong class="block">{{ u.ho_ten }}</strong>
                                    <p class="block text-sm text-gray-500 text-secondary">{{ u.email }}</p>
                                </div>
                            </div>
                        </td>

                        <!-- ROW CLASS -->
                        <td class="align-middle text-nowrap py-2">{{ u.lop || "—" }}</td>

                        <!-- ROW ROLE -->
                        <td class="align-middle text-nowrap py-2">
                            <span class="role-chip" :class="'role-' + displayRole(u)">
                                {{ ROLE_LABELS[displayRole(u)] }}
                            </span>
                        </td>

                        <!-- ROW STATUS -->
                        <td class="align-middle py-3">
                            <span class="status-dot text-nowrap" :class="'st-' + u.trang_thai">
                                <span class="ms-3">{{ STATUS_LABELS[u.trang_thai] }}</span>
                            </span>
                        </td>

                        <!-- ROW ACTIONS -->
                        <td class="align-middle py-2">
                            <div class="row-actions flex items-center gap-2 text-nowrap">

                                <!-- EDIT BUTTON -->
                                <button class="icon-btn edit" title="Sửa" @click="openEdit(u)" data-bs-toggle="modal"
                                    data-bs-target="#userFormModal">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <!-- LOCK BUTTON -->
                                <button v-if="u.trang_thai !== 'bi_khoa'" class="icon-btn lock" title="Khóa tài khoản"
                                    @click="prepareLock(u)" data-bs-toggle="modal" data-bs-target="#userLockModal">
                                    <i class="fa-solid fa-lock"></i>
                                </button>

                                <!-- UNLOCK BUTTON -->
                                <button v-else class="icon-btn unlock" title="Mở khóa" @click="prepareLock(u)"
                                    data-bs-toggle="modal" data-bs-target="#userLockModal">
                                    <i class="fa-solid fa-lock-open"></i>
                                </button>

                                <!-- DELETE BUTTON -->
                                <button class="icon-btn del" title="Xóa" @click="prepareDelete(u)"
                                    data-bs-toggle="modal" data-bs-target="#userDeleteModal">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <!-- EMPTY ROW -->
                    <tr v-if="paged.length === 0">
                        <td colspan="6" class="empty-row text-center py-4">Không tìm thấy người dùng nào phù hợp.</td>
                    </tr>
                </tbody>
            </table>

            <!-- NÚT CHUYỂN TRANG -->
            <div class="text-end">
                <div class="pagination">
                    <div class="pagination-left">
                        <span>
                            Hiển thị <strong>{{ paged.length }}</strong> / <strong>{{ filtered.length }}</strong>
                            người dùng
                        </span>

                        <select class="page-size" v-model.number="pageSize" @change="resetPage">
                            <option :value="5">5 dòng</option>
                            <option :value="10">10 dòng</option>
                            <option :value="20">20 dòng</option>
                            <option :value="50">50 dòng</option>
                        </select>
                    </div>

                    <div class="pagination-right">
                        <button class="page-btn page-nav" :disabled="currentPage === 1"
                            @click="goPage(currentPage - 1)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>

                        <button v-for="p in totalPages" :key="p" class="page-btn"
                            :class="{ 'is-active': p === currentPage }" @click="goPage(p)">
                            {{ p }}
                        </button>

                        <button class="page-btn page-nav" :disabled="currentPage === totalPages"
                            @click="goPage(currentPage + 1)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal form Sửa người dùng -->
        <div class="modal fade" id="userFormModal" tabindex="-1" aria-labelledby="userFormModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form @submit.prevent="saveForm">
                        <div class="modal-header">
                            <div>
                                <h1 class="modal-title fs-5" id="userFormModalLabel">{{ isEditing ? 'Sửa người dùng' :
                                    'Thêm người dùng' }}</h1>
                                <p class="text-sm text-gray-500 mb-0">Thông tin chính để quản lý nhanh.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Họ tên <span class="req">*</span></label>
                                    <input v-model="form.ho_ten" type="text" placeholder="Nhập họ tên" />
                                    <span v-if="errors.ho_ten" class="form-error">{{ errors.ho_ten }}</span>
                                </div>

                                <div class="form-group">
                                    <label>Email <span class="req">*</span></label>
                                    <input v-model="form.email" type="email" placeholder="Nhập email" />
                                    <span v-if="errors.email" class="form-error">{{ errors.email }}</span>
                                </div>

                                <div class="form-group">
                                    <label>Số điện thoại</label>
                                    <input v-model="form.so_dien_thoai" type="text" placeholder="Nhập số điện thoại" />
                                </div>

                                <div class="form-group">
                                    <label>Ngày sinh</label>
                                    <input v-model="form.ngay_sinh" type="date" />
                                </div>

                                <div class="form-group">
                                    <label>Vai trò hệ thống</label>
                                    <select v-model="form.vai_tro" @change="handleRoleChange">
                                        <option value="co_van">Cố vấn học tập</option>
                                        <option value="sinh_vien">Sinh viên</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>{{ form.vai_tro === 'co_van' ? 'Lớp phụ trách' : 'Lớp' }} <span
                                            class="req">*</span></label>
                                    <input v-model="form.lop" type="text"
                                        :placeholder="form.vai_tro === 'co_van' ? 'Ví dụ: D23CQCN01, D23CQCN02' : 'Ví dụ: D23CQCN01'" />
                                    <span v-if="errors.lop" class="form-error">{{ errors.lop }}</span>
                                </div>

                                <div v-if="form.vai_tro === 'sinh_vien'" class="form-group">
                                    <label>Chức vụ lớp</label>
                                    <select v-model="form.chuc_vu_lop">
                                        <option :value="null">Sinh viên thường</option>
                                        <option value="lop_truong">Lớp trưởng</option>
                                        <option value="bi_thu">Bí thư</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Trạng thái</label>
                                    <select v-model="form.trang_thai">
                                        <option value="hoat_dong">Hoạt động</option>
                                        <option value="khong_hoat_dong">Không hoạt động</option>
                                        <option value="bi_khoa">Bị khóa</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">{{ isEditing ? 'Cập nhật' : 'Thêm mới'
                            }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal form Khoa người dùng -->
        <div class="modal fade" id="userLockModal" tabindex="-1" aria-labelledby="userLockModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5" id="userLockModalLabel">
                                {{ lockTarget?.trang_thai === 'bi_khoa' ? 'Mở khóa tài khoản' : 'Khóa tài khoản' }}
                            </h1>
                            <p class="text-sm text-gray-500 mb-0">
                                {{ lockTarget?.trang_thai === 'bi_khoa' ? 'Tài khoản sẽ hoạt động lại sau khi mở khóa.'
                                    : 'Tài khoản sẽ không thể đăng nhập sau khi khóa.' }}
                            </p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            Bạn có chắc muốn {{ lockTarget?.trang_thai === 'bi_khoa' ? 'mở khóa' : 'khóa' }}
                            <strong>{{ lockTarget?.ho_ten }}</strong> không?
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" @click="confirmLock">
                            {{ lockTarget?.trang_thai === 'bi_khoa' ? 'Mở khóa' : 'Khóa ngay' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal form Xóa người dùng -->
        <div class="modal fade" id="userDeleteModal" tabindex="-1" aria-labelledby="userDeleteModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5" id="userDeleteModalLabel">Xóa người dùng</h1>
                            <p class="text-sm text-gray-500 mb-0">Hành động này không thể hoàn tác.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body">
                        <p>Bạn có chắc muốn xóa <strong>{{ deleteTarget?.ho_ten }}</strong> khỏi hệ thống không?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" @click="confirmDelete">Xóa</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed } from "vue"
import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const ROLE_LABELS = {
    co_van: "Cố vấn học tập",
    sinh_vien: "Sinh viên",
    lop_truong: "Lớp trưởng",
    bi_thu: "Bí thư",
}

const STATUS_LABELS = {
    hoat_dong: "Hoạt động",
    khong_hoat_dong: "Không hoạt động",
    bi_khoa: "Bị khóa",
}

let nextId = 9
const users = ref([
    { id: 1, ho_ten: "Nguyễn Văn An", email: "an.nv@truong.edu.vn", so_dien_thoai: "0901234567", ngay_sinh: "2003-05-12", vai_tro: "sinh_vien", chuc_vu_lop: "lop_truong", lop: "D23CQCN01", khoa: "Công nghệ thông tin", nganh: "Kỹ thuật phần mềm", ma_sinh_vien: "SV23001", trang_thai: "hoat_dong", ngay_tao: "2025-01-15", ngay_cap_nhat: "2025-01-18" },
    { id: 2, ho_ten: "Trần Thị Bình", email: "binh.tt@truong.edu.vn", so_dien_thoai: "0912345678", ngay_sinh: "1985-08-20", vai_tro: "co_van", chuc_vu_lop: null, lop: "D23CQCN01, D23CQCN02", khoa: "Công nghệ thông tin", nganh: "—", ma_sinh_vien: null, trang_thai: "hoat_dong", ngay_tao: "2025-01-10", ngay_cap_nhat: "2025-01-12" },
    { id: 3, ho_ten: "Lê Quản Trị", email: "admin@truong.edu.vn", so_dien_thoai: "0987654321", ngay_sinh: "1980-02-01", vai_tro: "admin", chuc_vu_lop: null, lop: "—", khoa: "—", nganh: "—", ma_sinh_vien: null, trang_thai: "hoat_dong", ngay_tao: "2024-12-01", ngay_cap_nhat: "2025-01-01" },
    { id: 4, ho_ten: "Phạm Thị Dung", email: "dung.pt@truong.edu.vn", so_dien_thoai: "0934567890", ngay_sinh: "2004-11-30", vai_tro: "sinh_vien", chuc_vu_lop: "bi_thu", lop: "D23CQCN02", khoa: "Công nghệ thông tin", nganh: "Hệ thống thông tin", ma_sinh_vien: "SV23004", trang_thai: "khong_hoat_dong", ngay_tao: "2025-02-03", ngay_cap_nhat: "2025-02-10" },
    { id: 5, ho_ten: "Hoàng Văn Em", email: "em.hv@truong.edu.vn", so_dien_thoai: "0945678901", ngay_sinh: "2003-07-18", vai_tro: "sinh_vien", chuc_vu_lop: null, lop: "D23CQCN01", khoa: "Công nghệ thông tin", nganh: "Khoa học máy tính", ma_sinh_vien: "SV23005", trang_thai: "bi_khoa", ngay_tao: "2025-02-11", ngay_cap_nhat: "2025-02-12" },
    { id: 6, ho_ten: "Vũ Thị Giang", email: "giang.vt@truong.edu.vn", so_dien_thoai: "0956789012", ngay_sinh: "1990-03-25", vai_tro: "co_van", chuc_vu_lop: null, lop: "D23CQCN03, D23CQCN04", khoa: "Công nghệ thông tin", nganh: "—", ma_sinh_vien: null, trang_thai: "hoat_dong", ngay_tao: "2025-01-22", ngay_cap_nhat: "2025-01-25" },
    { id: 7, ho_ten: "Đỗ Văn Hùng", email: "hung.dv@truong.edu.vn", so_dien_thoai: "0967890123", ngay_sinh: "2002-09-09", vai_tro: "sinh_vien", chuc_vu_lop: "lop_truong", lop: "D23CQCN03", khoa: "Công nghệ thông tin", nganh: "Kỹ thuật phần mềm", ma_sinh_vien: "SV23007", trang_thai: "hoat_dong", ngay_tao: "2025-03-01", ngay_cap_nhat: "2025-03-02" },
    { id: 8, ho_ten: "Bùi Thị Lan", email: "lan.bt@truong.edu.vn", so_dien_thoai: "0978901234", ngay_sinh: "2004-01-14", vai_tro: "sinh_vien", chuc_vu_lop: null, lop: "D23CQCN04", khoa: "Công nghệ thông tin", nganh: "Hệ thống thông tin", ma_sinh_vien: "SV23008", trang_thai: "khong_hoat_dong", ngay_tao: "2025-03-08", ngay_cap_nhat: "2025-03-09" },
])

// ----- filters -----
const search = ref("")
const filterRole = ref("all")
const filterStatus = ref("all")

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    return users.value.filter((u) => {
        if (u.vai_tro === "admin") return false
        const matchSearch = !q || u.ho_ten.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || (u.lop || "").toLowerCase().includes(q)
        const roleDisplay = displayRole(u)
        const matchRole = filterRole.value === "all" || roleDisplay === filterRole.value
        const matchStatus = filterStatus.value === "all" || u.trang_thai === filterStatus.value
        return matchSearch && matchRole && matchStatus
    })
})

// ----- pagination -----
const pageSize = ref(5)
const currentPage = ref(1)

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / pageSize.value)))
const paged = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value
    return filtered.value.slice(start, start + pageSize.value)
})

function goPage(p) {
    if (p >= 1 && p <= totalPages.value) currentPage.value = p
}
function resetPage() { currentPage.value = 1 }

function initials(name) {
    const parts = name.trim().split(" ")
    return (parts[0][0] + (parts[parts.length - 1][0] || "")).toUpperCase()
}

function displayRole(u) {
    return u.chuc_vu_lop || u.vai_tro
}

function displayRoleLabel(u) {
    if (u.vai_tro === "co_van") return "Cố vấn học tập"
    if (u.vai_tro === "admin") return "Quản trị hệ thống"
    return u.chuc_vu_lop ? ROLE_LABELS[u.chuc_vu_lop] : "Sinh viên thường"
}

function formatDate(d) {
    if (!d) return "—"
    const [y, m, day] = d.split("-")
    return `${day}/${m}/${y}`
}

function hideBootstrapModal(modalId) {
    const modalEl = document.getElementById(modalId)
    const ModalCtor = window.bootstrap?.Modal
    if (!modalEl || !ModalCtor) return

    const instance = ModalCtor.getInstance(modalEl) || ModalCtor.getOrCreateInstance(modalEl)
    instance.hide()
}

// ----- add / edit modal -----
const isEditing = ref(false)
const errors = ref({})
const blankForm = () => ({ id: null, ho_ten: "", email: "", so_dien_thoai: "", ngay_sinh: "", vai_tro: "sinh_vien", lop: "", chuc_vu_lop: null, khoa: "", nganh: "", ma_sinh_vien: "", trang_thai: "hoat_dong" })
const form = ref(blankForm())
const detailTarget = ref(null)

function openAdd() {
    isEditing.value = false
    errors.value = {}
    form.value = blankForm()
}
function openEdit(u) {
    isEditing.value = true
    errors.value = {}
    form.value = {
        ...u,
        chuc_vu_lop: u.chuc_vu_lop || null,
        lop: u.lop || "",
        khoa: u.khoa || "",
        nganh: u.nganh || "",
        ma_sinh_vien: u.ma_sinh_vien || "",
    }
}

function openDetail(u) {
    detailTarget.value = u
}

function handleRoleChange() {
    if (form.value.vai_tro === "co_van") {
        form.value.chuc_vu_lop = null
    }
}

function validate() {
    const e = {}
    if (!form.value.ho_ten.trim()) e.ho_ten = "Vui lòng nhập họ tên"
    if (!form.value.email.trim()) e.email = "Vui lòng nhập email"
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) e.email = "Email không hợp lệ"
    if (!form.value.lop.trim()) e.lop = "Vui lòng nhập lớp"
    if (form.value.vai_tro === "sinh_vien" && !form.value.ma_sinh_vien.trim()) e.ma_sinh_vien = "Vui lòng nhập mã sinh viên"
    errors.value = e
    return Object.keys(e).length === 0
}

function saveForm() {
    if (!validate()) return
    if (isEditing.value) {
        const idx = users.value.findIndex((u) => u.id === form.value.id)
        if (idx !== -1) {
            users.value[idx] = {
                ...users.value[idx],
                ho_ten: form.value.ho_ten,
                email: form.value.email,
                so_dien_thoai: form.value.so_dien_thoai,
                ngay_sinh: form.value.ngay_sinh,
                vai_tro: form.value.vai_tro,
                chuc_vu_lop: form.value.vai_tro === "sinh_vien" ? (form.value.chuc_vu_lop || null) : null,
                lop: form.value.lop,
                khoa: form.value.khoa,
                nganh: form.value.nganh,
                ma_sinh_vien: form.value.vai_tro === "sinh_vien" ? form.value.ma_sinh_vien : null,
                trang_thai: form.value.trang_thai,
                ngay_cap_nhat: new Date().toISOString().slice(0, 10),
            }
        }
    } else {
        users.value.unshift({
            id: nextId++,
            ho_ten: form.value.ho_ten,
            email: form.value.email,
            so_dien_thoai: form.value.so_dien_thoai,
            ngay_sinh: form.value.ngay_sinh,
            vai_tro: form.value.vai_tro,
            chuc_vu_lop: form.value.vai_tro === "sinh_vien" ? (form.value.chuc_vu_lop || null) : null,
            lop: form.value.lop,
            khoa: form.value.khoa,
            nganh: form.value.nganh,
            ma_sinh_vien: form.value.vai_tro === "sinh_vien" ? form.value.ma_sinh_vien : null,
            trang_thai: form.value.trang_thai,
            ngay_tao: new Date().toISOString().slice(0, 10),
            ngay_cap_nhat: new Date().toISOString().slice(0, 10),
        })
        resetPage()
    }
    hideBootstrapModal("userFormModal")
}

// ----- lock / unlock -----
const lockTarget = ref(null)
function prepareLock(u) { lockTarget.value = u }
function confirmLock() {
    if (lockTarget.value) {
        lockTarget.value.trang_thai = lockTarget.value.trang_thai === "bi_khoa" ? "hoat_dong" : "bi_khoa"
        lockTarget.value.ngay_cap_nhat = new Date().toISOString().slice(0, 10)
    }
    hideBootstrapModal("userLockModal")
    lockTarget.value = null
}

// ----- delete -----
const deleteTarget = ref(null)
function prepareDelete(u) { deleteTarget.value = u }
function confirmDelete() {
    if (!deleteTarget.value) return
    users.value = users.value.filter((u) => u.id !== deleteTarget.value.id)
    hideBootstrapModal("userDeleteModal")
    deleteTarget.value = null
    if (currentPage.value > totalPages.value) currentPage.value = totalPages.value
}
</script>