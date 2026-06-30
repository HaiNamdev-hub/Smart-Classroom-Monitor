<template>
    <div class="admin-content training-page">
        <div class="page-heading">
            <h1>Phân quyền chức năng</h1>
            <p>Quản lý vai trò, chức năng và quyền truy cập trong hệ thống.</p>
        </div>

        <div class="rbac-layout">
            <!-- VAI TRÒ -->
            <div class="rbac-card role-panel">
                <div class="rbac-header">
                    <div>
                        <h2>Vai trò</h2>
                        <p>Chọn vai trò cố định để cấp quyền.</p>
                    </div>
                </div>

                <div class="search-box mb-3">
                    <input v-model="roleSearch" type="text" placeholder="Tìm vai trò..."
                        class="outline-none bg-transparent w-full" />
                </div>

                <div class="role-list">
                    <div v-for="role in filteredRoles" :key="role.id" class="role-item"
                        :class="{ active: selectedRole?.id === role.id }" @click="selectRole(role)">
                        <div class="role-avatar">
                            {{ getRoleShortName(role.ten_vai_tro) }}
                        </div>

                        <div class="role-info">
                            <strong>{{ formatRoleName(role.ten_vai_tro) }}</strong>
                            <span>{{ role.mo_ta || '—' }}</span>
                        </div>
                    </div>

                    <div v-if="filteredRoles.length === 0" class="empty-box">
                        Không tìm thấy vai trò.
                    </div>
                </div>
            </div>

            <!-- PHÂN QUYỀN -->
            <div class="rbac-card permission-panel">
                <div class="permission-top">
                    <div>
                        <h2>
                            Phân quyền:
                            <span>{{ selectedRole ? formatRoleName(selectedRole.ten_vai_tro) : 'Chưa chọn' }}</span>
                        </h2>
                        <p>Click vào chức năng để cấp quyền, click thùng rác để gỡ quyền.</p>
                    </div>

                    <div class="permission-stats">
                        <div>
                            <strong>{{ activePermissions.length }}</strong>
                            <span>Đã cấp</span>
                        </div>
                        <div>
                            <strong>{{ functions.length }}</strong>
                            <span>Chức năng</span>
                        </div>
                    </div>
                </div>

                <div class="permission-body">
                    <!-- KHO CHỨC NĂNG -->
                    <div class="function-box">
                        <div class="section-title-row">
                            <h3>Kho chức năng</h3>

                            <button class="btn-light-red" type="button" @click="openAddFunction" data-bs-toggle="modal"
                                data-bs-target="#functionFormModal">
                                Thêm chức năng
                            </button>
                        </div>

                        <div class="search-box mb-3">
                            <input v-model="functionSearch" type="text" placeholder="Tìm chức năng..."
                                class="outline-none bg-transparent w-full" />
                        </div>

                        <div class="function-list">
                            <div v-for="fn in filteredFunctions" :key="fn.id" class="function-item" :class="{
                                granted: isGranted(fn.id),
                                paused: fn.trang_thai === 'tam_dung'
                            }" @click="grantPermission(fn)">
                                <div class="function-icon">
                                    <i class="fa-solid fa-layer-group"></i>
                                </div>

                                <div class="function-info">
                                    <strong>{{ fn.ten_chuc_nang }}</strong>
                                    <small>{{ fn.mo_ta || '—' }}</small>
                                </div>

                                <div class="function-right">
                                    <span class="status-pill" :class="'st-' + fn.trang_thai">
                                        {{ formatFunctionStatus(fn.trang_thai) }}
                                    </span>

                                    <div class="function-actions">
                                        <button class="mini-icon edit" title="Sửa chức năng" type="button"
                                            @click.stop="openEditFunction(fn)" data-bs-toggle="modal"
                                            data-bs-target="#functionFormModal">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <button class="mini-icon del" title="Xóa chức năng" type="button"
                                            @click.stop="openDeleteFunction(fn)" data-bs-toggle="modal"
                                            data-bs-target="#functionDeleteModal">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>

                                    <i v-if="isGranted(fn.id)" class="fa-solid fa-circle-check granted-icon"
                                        title="Đã cấp quyền"></i>

                                    <i v-else class="fa-solid fa-circle-plus add-icon" title="Cấp quyền"></i>
                                </div>
                            </div>

                            <div v-if="filteredFunctions.length === 0" class="empty-box">
                                Không tìm thấy chức năng.
                            </div>
                        </div>
                    </div>

                    <!-- QUYỀN ĐANG CẤP -->
                    <div class="granted-box">
                        <h3 class="mb-3">Quyền đang kích hoạt</h3>

                        <div class="granted-list">
                            <div v-for="item in activePermissions" :key="item.permission.id" class="granted-item">
                                <div>
                                    <strong>{{ item.function.ten_chuc_nang }}</strong>
                                    <span>{{ item.function.ma_chuc_nang }}</span>
                                </div>

                                <button v-if="!isSelectedAdmin" class="mini-icon del" title="Gỡ quyền" type="button"
                                    @click="revokePermission(item.permission)">
                                    <i class="fa-solid fa-trash-arrow-up"></i>
                                </button>
                            </div>

                            <div v-if="activePermissions.length === 0" class="empty-box">
                                Vai trò này chưa được cấp chức năng nào.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL FUNCTION FORM -->
        <div class="modal fade" id="functionFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form @submit.prevent="saveFunction">
                        <div class="modal-header">
                            <div>
                                <h1 class="modal-title fs-5">
                                    {{ isEditingFunction ? 'Sửa' : 'Thêm' }} chức năng
                                </h1>
                                <p class="text-sm text-gray-500 mb-0">
                                    Chức năng dùng để cấp quyền cho từng vai trò.
                                </p>
                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Mã chức năng <span class="req">*</span></label>
                                    <input v-model="functionForm.ma_chuc_nang" type="text"
                                        placeholder="VD: quan_ly_user" />
                                    <span v-if="functionErrors.ma_chuc_nang" class="form-error">
                                        {{ functionErrors.ma_chuc_nang }}
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label>Tên chức năng <span class="req">*</span></label>
                                    <input v-model="functionForm.ten_chuc_nang" type="text"
                                        placeholder="VD: Quản lý người dùng" />
                                    <span v-if="functionErrors.ten_chuc_nang" class="form-error">
                                        {{ functionErrors.ten_chuc_nang }}
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label>Trạng thái <span class="req">*</span></label>
                                    <select v-model="functionForm.trang_thai">
                                        <option value="hoat_dong">Hoạt động</option>
                                        <option value="tam_dung">Tạm dừng</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Mô tả</label>
                                    <textarea v-model="functionForm.mo_ta"
                                        placeholder="Nhập mô tả chức năng..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Hủy
                            </button>
                            <button type="submit" class="btn btn-primary">
                                {{ isEditingFunction ? 'Cập nhật' : 'Thêm mới' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL FUNCTION DELETE -->
        <div class="modal fade" id="functionDeleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5">Xóa chức năng</h1>
                            <p class="text-sm text-gray-500 mb-0">Các quyền liên quan cũng sẽ bị gỡ khỏi demo.</p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p>
                            Bạn có chắc muốn xóa chức năng
                            <strong>{{ functionDeleteTarget?.ten_chuc_nang }}</strong>
                            không?
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Hủy
                        </button>
                        <button type="button" class="btn btn-primary" @click="confirmDeleteFunction">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from "axios"
import { computed, ref, onMounted } from "vue"
import { createToaster } from "@meforma/vue-toaster"

import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const toaster = createToaster({
    position: "top-right",
})

const BASE_URL = "http://127.0.0.1:8000/api/admin"

const roles = ref([])
const functions = ref([])
const permissions = ref([])
const selectedRole = ref(null)

const roleSearch = ref("")
const functionSearch = ref("")
const functionForm = ref({
    ma_chuc_nang: "",
    ten_chuc_nang: "",
    mo_ta: "",
    trang_thai: "hoat_dong",
})

const functionErrors = ref({})

const isEditingFunction = ref(false)

const functionDeleteTarget = ref(null)

function authHeader() {
    return {
        headers: {
            Authorization: "Bearer " + sessionStorage.getItem("auth_token"),
        },
    }
}

onMounted(() => {
    getVaiTro()
    getChucNang()
})

function getVaiTro() {
    axios
        .get(BASE_URL + "/vai-tro/get-data", authHeader())
        .then((res) => {
            if (res.data.status) {
                roles.value = res.data.data || []

                if (roles.value.length > 0) {
                    selectedRole.value = roles.value[0]
                    getChiTietPhanQuyen()
                }
            } else {
                toaster.error(res.data.message || "Không lấy được vai trò")
            }
        })
        .catch((err) => {
            handleError(err, "Không lấy được danh sách vai trò")
        })
}

function getChucNang() {
    axios
        .get(BASE_URL + "/phan-quyen/chuc-nang/get-data", authHeader())
        .then((res) => {
            if (res.data.status) {
                functions.value = res.data.data || []
            } else {
                toaster.error(res.data.message || "Không lấy được chức năng")
            }
        })
        .catch((err) => {
            handleError(err, "Không lấy được danh sách chức năng")
        })
}

function getChiTietPhanQuyen() {
    if (!selectedRole.value) return

    axios
        .post(
            BASE_URL + "/phan-quyen/chi-tiet-phan-quyen/get-data",
            {
                vai_tro_id: selectedRole.value.id,
            },
            authHeader()
        )
        .then((res) => {
            if (res.data.status) {
                permissions.value = res.data.data || []
            } else {
                permissions.value = []
                toaster.error(res.data.message || "Không lấy được phân quyền")
            }
        })
        .catch((err) => {
            permissions.value = []
            handleError(err, "Không lấy được phân quyền")
        })
}

function selectRole(role) {
    selectedRole.value = role
    getChiTietPhanQuyen()
}

const filteredRoles = computed(() => {
    const q = roleSearch.value.trim().toLowerCase()

    if (!q) return roles.value

    return roles.value.filter((role) => {
        return [
            role.ten_vai_tro,
            role.mo_ta,
            formatRoleName(role.ten_vai_tro),
        ]
            .join(" ")
            .toLowerCase()
            .includes(q)
    })
})

const filteredFunctions = computed(() => {
    const q = functionSearch.value.trim().toLowerCase()

    if (!q) return functions.value

    return functions.value.filter((fn) => {
        return [
            fn.ma_chuc_nang,
            fn.ten_chuc_nang,
            fn.mo_ta,
            formatFunctionStatus(fn.trang_thai),
        ]
            .join(" ")
            .toLowerCase()
            .includes(q)
    })
})

const grantedFunctionIds = computed(() => {
    return permissions.value.map((item) => Number(item.chuc_nang_id))
})

const activePermissions = computed(() => {
    return permissions.value.map((permission) => {
        const fn = functions.value.find((item) => {
            return Number(item.id) === Number(permission.chuc_nang_id)
        })

        return {
            permission,
            function: fn || {
                id: permission.chuc_nang_id,
                ma_chuc_nang: permission.ma_chuc_nang || "—",
                ten_chuc_nang: permission.ten_chuc_nang || "Chức năng không tồn tại",
                mo_ta: permission.mo_ta || "",
                trang_thai: permission.trang_thai || "hoat_dong",
            },
        }
    })
})

const isSelectedAdmin = computed(() => {
    return selectedRole.value?.ten_vai_tro === "admin" || Number(selectedRole.value?.id) === 1
})

function isGranted(functionId) {
    return grantedFunctionIds.value.includes(Number(functionId))
}

function grantPermission(fn) {
    if (!selectedRole.value) {
        toaster.error("Vui lòng chọn vai trò trước")
        return
    }

    if (isSelectedAdmin.value) {
        toaster.error("Admin mặc định có toàn quyền")
        return
    }

    if (fn.trang_thai !== "hoat_dong") {
        toaster.error("Chức năng đang tạm dừng, không thể cấp quyền")
        return
    }

    if (isGranted(fn.id)) {
        toaster.error("Vai trò này đã có chức năng này")
        return
    }

    axios
        .post(
            BASE_URL + "/phan-quyen/chi-tiet-phan-quyen/add-data",
            {
                vai_tro_id: selectedRole.value.id,
                chuc_nang_id: fn.id,
            },
            authHeader()
        )
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message || "Cấp quyền thành công")
                getChiTietPhanQuyen()
            } else {
                toaster.error(res.data.message || "Cấp quyền thất bại")
            }
        })
        .catch((err) => {
            handleError(err, "Cấp quyền thất bại")
        })
}

function revokePermission(permission) {
    if (!selectedRole.value) {
        toaster.error("Vui lòng chọn vai trò trước")
        return
    }

    if (isSelectedAdmin.value) {
        toaster.error("Admin mặc định có toàn quyền, không thể gỡ quyền")
        return
    }

    axios
        .post(
            BASE_URL + "/phan-quyen/chi-tiet-phan-quyen/delete",
            {
                vai_tro_id: selectedRole.value.id,
                chuc_nang_id: permission.chuc_nang_id,
            },
            authHeader()
        )
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message || "Gỡ quyền thành công")
                getChiTietPhanQuyen()
            } else {
                toaster.error(res.data.message || "Gỡ quyền thất bại")
            }
        })
        .catch((err) => {
            handleError(err, "Gỡ quyền thất bại")
        })
}
function openAddFunction() {
    isEditingFunction.value = false

    functionForm.value = {
        id: "",
        ma_chuc_nang: "",
        ten_chuc_nang: "",
        mo_ta: "",
        trang_thai: "hoat_dong",
    }

    functionErrors.value = {}
}
function openEditFunction(fn) {
    isEditingFunction.value = true

    functionForm.value = {
        id: fn.id,
        ma_chuc_nang: fn.ma_chuc_nang,
        ten_chuc_nang: fn.ten_chuc_nang,
        mo_ta: fn.mo_ta,
        trang_thai: fn.trang_thai,
    }

    functionErrors.value = {}
}
function openDeleteFunction(fn) {
    functionDeleteTarget.value = fn
}
function saveFunction() {
    functionErrors.value = {}

    const url = isEditingFunction.value
        ? BASE_URL + "/phan-quyen/chuc-nang/update"
        : BASE_URL + "/phan-quyen/chuc-nang/add-data"

    axios
        .post(url, functionForm.value, authHeader())
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message)

                bootstrap.Modal.getInstance(
                    document.getElementById("functionFormModal")
                ).hide()

                getChucNang()
            } else {
                toaster.error(res.data.message)
            }
        })
        .catch((err) => {
            if (err.response?.status === 422) {
                functionErrors.value = err.response.data.errors || {}
            } else {
                handleError(err, "Lưu chức năng thất bại")
            }
        })
}
function confirmDeleteFunction() {
    if (!functionDeleteTarget.value) return

    axios
        .post(
            BASE_URL + "/phan-quyen/chuc-nang/delete",
            {
                id: functionDeleteTarget.value.id,
            },
            authHeader()
        )
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message)

                bootstrap.Modal.getInstance(
                    document.getElementById("functionDeleteModal")
                ).hide()

                getChucNang()
            } else {
                toaster.error(res.data.message)
            }
        })
        .catch((err) => {
            handleError(err, "Xóa chức năng thất bại")
        })
}


function formatRoleName(value) {
    const map = {
        admin: "Admin",
        phong_qldt: "Phòng QLĐT",
        co_van: "Cố vấn học tập",
        lop_truong: "Lớp trưởng",
        bi_thu: "Bí thư",
        sinh_vien: "Sinh viên",
    }

    return map[value] || value || "—"
}

function getRoleShortName(value) {
    const map = {
        admin: "AD",
        phong_qldt: "QL",
        co_van: "CV",
        lop_truong: "LT",
        bi_thu: "BT",
        sinh_vien: "SV",
    }

    return map[value] || "?"
}

function formatFunctionStatus(value) {
    const map = {
        hoat_dong: "Hoạt động",
        tam_dung: "Tạm dừng",
    }

    return map[value] || "—"
}

function handleError(err, defaultMessage) {
    console.log(defaultMessage, err.response?.data || err)

    if (err.response?.data?.errors) {
        const errors = err.response.data.errors
        const firstKey = Object.keys(errors)[0]
        toaster.error(errors[firstKey][0] || "Dữ liệu không hợp lệ")
        return
    }

    toaster.error(err.response?.data?.message || defaultMessage)
}
</script>
<style scoped>
.rbac-layout {
    display: grid;
    grid-template-columns: 360px minmax(0, 1fr);
    gap: 22px;
}

/* CARD */
.rbac-card {
    background: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    border-radius: 24px;
    padding: 20px;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
}

/* HEADER */
.rbac-header,
.permission-top,
.section-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}

.rbac-header h2,
.permission-top h2,
.section-title-row h3,
.granted-box h3 {
    font-size: 20px;
    font-weight: 800;
    color: var(--text-primary);
    margin: 0;
}

.rbac-header p,
.permission-top p {
    color: var(--text-secondary);
    margin: 4px 0 0;
}

.permission-top h2 span {
    color: var(--accent);
}

/* LIST */
.role-list,
.function-list,
.granted-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ROLE */
.role-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 14px;
    cursor: pointer;
    transition: 0.2s;
}

.role-item:hover,
.role-item.active {
    border-color: rgba(220, 38, 38, 0.55);
    background: rgba(220, 38, 38, 0.08);
}

.role-avatar {
    width: 44px;
    height: 44px;
    border-radius: 999px;
    background: var(--accent);
    color: #fff;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.role-info {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.role-info strong {
    color: var(--text-primary);
}

.role-info span {
    color: var(--text-secondary);
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.role-actions,
.function-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* STATS */
.permission-stats {
    display: flex;
    align-items: center;
    gap: 12px;
}

.permission-stats div {
    min-width: 76px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 8px 10px;
    text-align: center;
}

.permission-stats strong {
    display: block;
    color: var(--accent);
    font-size: 20px;
    line-height: 1;
}

.permission-stats span {
    color: var(--text-secondary);
    font-size: 12px;
}

/* PERMISSION BODY */
.permission-body {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 18px;
}

.function-box,
.granted-box {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 16px;
}

/* FUNCTION ITEM */
.function-item {
    display: flex;
    align-items: center;
    gap: 12px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 14px;
    cursor: pointer;
    transition: 0.2s;
}

.function-item:hover {
    border-color: rgba(37, 99, 235, 0.45);
    background: rgba(37, 99, 235, 0.08);
}

.function-item.granted {
    border-color: rgba(34, 197, 94, 0.65);
    background: rgba(34, 197, 94, 0.12);
}

.function-item.paused {
    opacity: 0.65;
}

.function-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.function-info {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.function-info strong {
    color: var(--text-primary);
}

.function-info span {
    color: #2563eb;
    font-size: 13px;
    font-weight: 700;
}

.function-info small {
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.function-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* STATUS */
.status-pill {
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 700;
}

.st-hoat_dong {
    background: rgba(34, 197, 94, 0.16);
    color: #16a34a;
}

.st-tam_dung {
    background: rgba(245, 158, 11, 0.16);
    color: #d97706;
}

.granted-icon {
    color: #16a34a;
    font-size: 18px;
}

.add-icon {
    color: #2563eb;
    font-size: 18px;
}

/* GRANTED */
.granted-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-top: 4px solid #22c55e;
    border-radius: 16px;
    padding: 14px;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
}

.granted-item div {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.granted-item strong {
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.granted-item span {
    color: var(--text-secondary);
    font-size: 13px;
}

/* BUTTONS */
.mini-icon {
    width: 30px;
    height: 30px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-card);
    cursor: pointer;
    transition: 0.18s;
}

.mini-icon.edit {
    color: #d97706;
}

.mini-icon.del {
    color: #dc2626;
}

.mini-icon:hover {
    transform: translateY(-1px);
    background: rgba(148, 163, 184, 0.1);
}

.empty-box {
    border: 1px dashed var(--border-color);
    border-radius: 16px;
    padding: 18px;
    text-align: center;
    color: var(--text-secondary);
}

.btn-light-red {
    border: 0;
    border-radius: 12px;
    background: rgba(220, 38, 38, 0.12);
    color: var(--accent);
    padding: 9px 14px;
    font-weight: 800;
}

/* DARK THEME CỤ THỂ CHO PROJECT CŨ DÙNG html.dark-theme */
:global(html.dark-theme) .rbac-card,
:global(html.dark-theme) .role-item,
:global(html.dark-theme) .function-box,
:global(html.dark-theme) .granted-box,
:global(html.dark-theme) .function-item,
:global(html.dark-theme) .granted-item,
:global(html.dark-theme) .permission-stats div,
:global(html.dark-theme) .mini-icon {
    background: #1e293b;
    border-color: rgba(255, 255, 255, 0.08);
}

:global(html.dark-theme) .role-info strong,
:global(html.dark-theme) .function-info strong,
:global(html.dark-theme) .granted-item strong,
:global(html.dark-theme) .rbac-header h2,
:global(html.dark-theme) .permission-top h2,
:global(html.dark-theme) .section-title-row h3,
:global(html.dark-theme) .granted-box h3 {
    color: #f8fafc;
}

:global(html.dark-theme) .rbac-header p,
:global(html.dark-theme) .permission-top p,
:global(html.dark-theme) .role-info span,
:global(html.dark-theme) .function-info small,
:global(html.dark-theme) .granted-item span,
:global(html.dark-theme) .permission-stats span,
:global(html.dark-theme) .empty-box {
    color: #94a3b8;
}

:global(html.dark-theme) .role-item:hover,
:global(html.dark-theme) .role-item.active {
    background: rgba(220, 38, 38, 0.14);
    border-color: rgba(220, 38, 38, 0.7);
}

:global(html.dark-theme) .function-item:hover {
    background: rgba(37, 99, 235, 0.14);
    border-color: rgba(37, 99, 235, 0.7);
}

:global(html.dark-theme) .function-item.granted {
    background: rgba(34, 197, 94, 0.14);
    border-color: rgba(34, 197, 94, 0.75);
}

:global(html.dark-theme) .function-icon {
    background: rgba(37, 99, 235, 0.18);
    color: #93c5fd;
}

:global(html.dark-theme) .btn-light-red {
    background: rgba(220, 38, 38, 0.18);
    color: #fecaca;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .rbac-layout {
        grid-template-columns: 1fr;
    }

    .permission-body {
        grid-template-columns: 1fr;
    }
}
</style>