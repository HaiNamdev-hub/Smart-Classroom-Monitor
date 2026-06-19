<template>
    <div class="admin-content user-page">
        <div class="page-heading">
            <h1>Quản lý người dùng</h1>
            <p>Quản lý tài khoản admin, cố vấn học tập và sinh viên trong hệ thống.</p>
        </div>

        <div class="toolbar flex items-center gap-4 flex-nowrap overflow-x-auto w-full pb-2">
            <div class="search-box flex items-center shrink-0 flex-1 min-w-[250px]">
                <input v-model="search" @input="resetPage" type="text" placeholder="Tìm theo họ tên hoặc email..."
                    class="outline-none bg-transparent w-full" />
            </div>

            <div class="filter-select shrink-0">
                <select v-model="filterRole" @change="resetPage" class="cursor-pointer outline-none">
                    <option value="all">Tất cả vai trò</option>
                    <option value="admin">Admin</option>
                    <option value="co_van">Cố vấn học tập</option>
                    <option value="sinh_vien">Sinh viên</option>
                </select>
            </div>

            <div class="filter-select shrink-0">
                <select v-model="filterStatus" @change="resetPage" class="cursor-pointer outline-none">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="hoat_dong">Hoạt động</option>
                    <option value="khong_hoat_dong">Không hoạt động</option>
                    <option value="bi_khoa">Bị khóa</option>
                </select>
            </div>

            <button class="btn-primary flex items-center justify-center gap-2 text-nowrap shrink-0 ml-auto"
                @click="openAdd">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                    <line x1="12" y1="5" x2="12" y2="19" />
                    <line x1="5" y1="12" x2="19" y2="12" />
                </svg>
                Thêm người dùng
            </button>
        </div>

        <div class="table-wrap">
            <table class="data-table w-full text-left border-collapse">
                <thead>
                    <tr class="border-b">
                        <th style="width:48px" class="text-left align-middle text-nowrap py-3">STT</th>
                        <th class="text-left align-middle text-nowrap py-3">Họ tên</th>
                        <th class="text-left align-middle text-nowrap py-3">Email</th>
                        <th class="text-left align-middle text-nowrap py-3">Vai trò</th>
                        <th class="text-left align-middle text-nowrap py-3">Trạng thái</th>
                        <th class="text-left align-middle text-nowrap py-3">Ngày tạo</th>
                        <th style="width:170px" class="text-left align-middle text-nowrap py-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(u, i) in paged" :key="u.id" class="border-b hover:bg-gray-50">
                        <td class="align-middle py-2">{{ (currentPage - 1) * pageSize + i + 1 }}</td>
                        <td class="align-middle py-2">
                            <div class="cell-user flex items-center gap-3">
                                <div class="cell-avatar shrink-0">{{ initials(u.ho_ten) }}</div>
                                <div class="cell-name text-nowrap">
                                    <strong class="block">{{ u.ho_ten }}</strong>
                                    <span class="text-sm text-gray-500 ms-3">{{ u.so_dien_thoai || "—" }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle text-nowrap py-2">{{ u.email }}</td>
                        <td class="align-middle text-nowrap py-2">
                            <span class="role-chip" :class="'role-' + u.vai_tro">
                                {{ ROLE_LABELS[u.vai_tro] }}
                            </span>
                        </td>
                        <td class="align-middle py-3">
                            <span class="status-dot text-nowrap" :class="'st-' + u.trang_thai">
                                <span class="ms-3">{{ STATUS_LABELS[u.trang_thai] }}</span>
                            </span>
                        </td>
                        <td class="align-middle text-nowrap py-2">{{ formatDate(u.ngay_tao) }}</td>
                        <td class="align-middle py-2">
                            <div class="row-actions flex items-center gap-2 text-nowrap">
                                <button class="icon-btn edit" title="Sửa" @click="openEdit(u)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                        <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                    </svg>
                                </button>
                                <button v-if="u.trang_thai !== 'bi_khoa'" class="icon-btn lock" title="Khóa tài khoản"
                                    @click="askLock(u)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                </button>
                                <button v-else class="icon-btn unlock" title="Mở khóa" @click="askLock(u)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="11" width="18" height="11" rx="2" />
                                        <path d="M7 11V7a5 5 0 0 1 9.9-1" />
                                    </svg>
                                </button>
                                <button class="icon-btn del" title="Xóa" @click="askDelete(u)">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6" />
                                        <path
                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="paged.length === 0">
                        <td colspan="7" class="empty-row text-center py-4">Không tìm thấy người dùng nào phù hợp.</td>
                    </tr>
                </tbody>
            </table>

            <div class="text-end">
                <div
                    class="flex items-center justify-end gap-8 px-4 py-4 bg-white border-t border-gray-200 mt-2 mb-2 rounded-b-lg w-full">

                    <div class="flex items-center gap-4 text-base text-gray-600">
                        <span>Hiển thị <strong>{{ paged.length }}</strong> / <strong>{{ filtered.length }}</strong>
                            người dùng</span>
                        <select
                            class="h-10 px-3 bg-white border border-gray-300 rounded outline-none cursor-pointer focus:border-blue-500 text-base"
                            v-model.number="pageSize" @change="resetPage">
                            <option :value="5">5 dòng</option>
                            <option :value="10">10 dòng</option>
                            <option :value="20">20 dòng</option>
                            <option :value="50">50 dòng</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            class="w-10 h-10 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            :disabled="currentPage === 1" @click="goPage(currentPage - 1)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                        </button>

                        <button v-for="p in totalPages" :key="p"
                            class="w-10 h-10 flex items-center justify-center rounded border text-base font-medium transition-colors"
                            :class="p === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100'"
                            @click="goPage(p)">
                            {{ p }}
                        </button>

                        <button
                            class="w-10 h-10 flex items-center justify-center rounded border border-gray-300 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            :disabled="currentPage === totalPages" @click="goPage(currentPage + 1)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </button>
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
    admin: "Admin",
    co_van: "Cố vấn học tập",
    sinh_vien: "Sinh viên",
}

const STATUS_LABELS = {
    hoat_dong: "Hoạt động",
    khong_hoat_dong: "Không hoạt động",
    bi_khoa: "Bị khóa",
}

let nextId = 9
const users = ref([
    { id: 1, ho_ten: "Nguyễn Văn An", email: "an.nv@truong.edu.vn", so_dien_thoai: "0901234567", ngay_sinh: "2003-05-12", vai_tro: "sinh_vien", trang_thai: "hoat_dong", ngay_tao: "2025-01-15" },
    { id: 2, ho_ten: "Trần Thị Bình", email: "binh.tt@truong.edu.vn", so_dien_thoai: "0912345678", ngay_sinh: "1985-08-20", vai_tro: "co_van", trang_thai: "hoat_dong", ngay_tao: "2025-01-10" },
    { id: 3, ho_ten: "Lê Quản Trị", email: "admin@truong.edu.vn", so_dien_thoai: "0987654321", ngay_sinh: "1980-02-01", vai_tro: "admin", trang_thai: "hoat_dong", ngay_tao: "2024-12-01" },
    { id: 4, ho_ten: "Phạm Thị Dung", email: "dung.pt@truong.edu.vn", so_dien_thoai: "0934567890", ngay_sinh: "2004-11-30", vai_tro: "sinh_vien", trang_thai: "khong_hoat_dong", ngay_tao: "2025-02-03" },
    { id: 5, ho_ten: "Hoàng Văn Em", email: "em.hv@truong.edu.vn", so_dien_thoai: "0945678901", ngay_sinh: "2003-07-18", vai_tro: "sinh_vien", trang_thai: "bi_khoa", ngay_tao: "2025-02-11" },
    { id: 6, ho_ten: "Vũ Thị Giang", email: "giang.vt@truong.edu.vn", so_dien_thoai: "0956789012", ngay_sinh: "1990-03-25", vai_tro: "co_van", trang_thai: "hoat_dong", ngay_tao: "2025-01-22" },
    { id: 7, ho_ten: "Đỗ Văn Hùng", email: "hung.dv@truong.edu.vn", so_dien_thoai: "0967890123", ngay_sinh: "2002-09-09", vai_tro: "sinh_vien", trang_thai: "hoat_dong", ngay_tao: "2025-03-01" },
    { id: 8, ho_ten: "Bùi Thị Lan", email: "lan.bt@truong.edu.vn", so_dien_thoai: "0978901234", ngay_sinh: "2004-01-14", vai_tro: "sinh_vien", trang_thai: "khong_hoat_dong", ngay_tao: "2025-03-08" },
])

// ----- filters -----
const search = ref("")
const filterRole = ref("all")
const filterStatus = ref("all")

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    return users.value.filter((u) => {
        const matchSearch = !q || u.ho_ten.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
        const matchRole = filterRole.value === "all" || u.vai_tro === filterRole.value
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
function formatDate(d) {
    if (!d) return "—"
    const [y, m, day] = d.split("-")
    return `${day}/${m}/${y}`
}

// ----- add / edit modal -----
const showForm = ref(false)
const isEditing = ref(false)
const errors = ref({})
const blankForm = () => ({ id: null, ho_ten: "", email: "", so_dien_thoai: "", ngay_sinh: "", vai_tro: "sinh_vien", trang_thai: "hoat_dong", mat_khau: "", xac_nhan: "" })
const form = ref(blankForm())

function openAdd() {
    isEditing.value = false
    errors.value = {}
    form.value = blankForm()
    showForm.value = true
}
function openEdit(u) {
    isEditing.value = true
    errors.value = {}
    form.value = { ...u, mat_khau: "", xac_nhan: "" }
    showForm.value = true
}
function closeForm() { showForm.value = false }

function validate() {
    const e = {}
    if (!form.value.ho_ten.trim()) e.ho_ten = "Vui lòng nhập họ tên"
    if (!form.value.email.trim()) e.email = "Vui lòng nhập email"
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) e.email = "Email không hợp lệ"
    if (!isEditing.value) {
        if (!form.value.mat_khau) e.mat_khau = "Vui lòng nhập mật khẩu"
        else if (form.value.mat_khau.length < 6) e.mat_khau = "Mật khẩu tối thiểu 6 ký tự"
        if (form.value.mat_khau !== form.value.xac_nhan) e.xac_nhan = "Mật khẩu xác nhận không khớp"
    }
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
                trang_thai: form.value.trang_thai,
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
            trang_thai: form.value.trang_thai,
            ngay_tao: new Date().toISOString().slice(0, 10),
        })
        resetPage()
    }
    showForm.value = false
}

// ----- lock / unlock -----
const showLock = ref(false)
const lockTarget = ref(null)
function askLock(u) { lockTarget.value = u; showLock.value = true }
function confirmLock() {
    if (lockTarget.value) {
        lockTarget.value.trang_thai = lockTarget.value.trang_thai === "bi_khoa" ? "hoat_dong" : "bi_khoa"
    }
    showLock.value = false
}

// ----- delete -----
const showDelete = ref(false)
const deleteTarget = ref(null)
function askDelete(u) { deleteTarget.value = u; showDelete.value = true }
function confirmDelete() {
    users.value = users.value.filter((u) => u.id !== deleteTarget.value.id)
    showDelete.value = false
    if (currentPage.value > totalPages.value) currentPage.value = totalPages.value
}
</script>