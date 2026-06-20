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
                        <p>Chọn vai trò để cấp quyền.</p>
                    </div>

                    <button
                        class="btn-primary"
                        type="button"
                        @click="openAddRole"
                        data-bs-toggle="modal"
                        data-bs-target="#roleFormModal"
                    >
                        Thêm
                    </button>
                </div>

                <div class="search-box mb-3">
                    <input
                        v-model="roleSearch"
                        type="text"
                        placeholder="Tìm vai trò..."
                        class="outline-none bg-transparent w-full"
                    />
                </div>

                <div class="role-list">
                    <div
                        v-for="role in filteredRoles"
                        :key="role.id"
                        class="role-item"
                        :class="{ active: selectedRole?.id === role.id }"
                        @click="selectRole(role)"
                    >
                        <div class="role-avatar">
                            {{ getRoleShortName(role.ten_vai_tro) }}
                        </div>

                        <div class="role-info">
                            <strong>{{ formatRoleName(role.ten_vai_tro) }}</strong>
                            <span>{{ role.mo_ta || '—' }}</span>
                        </div>

                        <div class="role-actions">
                            <button
                                class="mini-icon edit"
                                title="Sửa vai trò"
                                type="button"
                                @click.stop="openEditRole(role)"
                                data-bs-toggle="modal"
                                data-bs-target="#roleFormModal"
                            >
                                <i class="fa-solid fa-pen"></i>
                            </button>

                            <button
                                class="mini-icon del"
                                title="Xóa vai trò"
                                type="button"
                                @click.stop="openDeleteRole(role)"
                                data-bs-toggle="modal"
                                data-bs-target="#roleDeleteModal"
                            >
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
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

                            <button
                                class="btn-light-red"
                                type="button"
                                @click="openAddFunction"
                                data-bs-toggle="modal"
                                data-bs-target="#functionFormModal"
                            >
                                Thêm chức năng
                            </button>
                        </div>

                        <div class="search-box mb-3">
                            <input
                                v-model="functionSearch"
                                type="text"
                                placeholder="Tìm chức năng..."
                                class="outline-none bg-transparent w-full"
                            />
                        </div>

                        <div class="function-list">
                            <div
                                v-for="fn in filteredFunctions"
                                :key="fn.id"
                                class="function-item"
                                :class="{
                                    granted: isGranted(fn.id),
                                    paused: fn.trang_thai === 'tam_dung'
                                }"
                                @click="grantPermission(fn)"
                            >
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
                                        <button
                                            class="mini-icon edit"
                                            title="Sửa chức năng"
                                            type="button"
                                            @click.stop="openEditFunction(fn)"
                                            data-bs-toggle="modal"
                                            data-bs-target="#functionFormModal"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </button>

                                        <button
                                            class="mini-icon del"
                                            title="Xóa chức năng"
                                            type="button"
                                            @click.stop="openDeleteFunction(fn)"
                                            data-bs-toggle="modal"
                                            data-bs-target="#functionDeleteModal"
                                        >
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>

                                    <i
                                        v-if="isGranted(fn.id)"
                                        class="fa-solid fa-circle-check granted-icon"
                                        title="Đã cấp quyền"
                                    ></i>

                                    <i
                                        v-else
                                        class="fa-solid fa-circle-plus add-icon"
                                        title="Cấp quyền"
                                    ></i>
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
                            <div
                                v-for="item in activePermissions"
                                :key="item.permission.id"
                                class="granted-item"
                            >
                                <div>
                                    <strong>{{ item.function.ten_chuc_nang }}</strong>
                                    <span>{{ item.function.ma_chuc_nang }}</span>
                                </div>

                                <button
                                    class="mini-icon del"
                                    title="Gỡ quyền"
                                    type="button"
                                    @click="revokePermission(item.permission)"
                                >
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

        <!-- MODAL ROLE FORM -->
        <div class="modal fade" id="roleFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form @submit.prevent="saveRole">
                        <div class="modal-header">
                            <div>
                                <h1 class="modal-title fs-5">
                                    {{ isEditingRole ? 'Sửa' : 'Thêm' }} vai trò
                                </h1>
                                <p class="text-sm text-gray-500 mb-0">
                                    Vai trò dùng để phân nhóm người dùng.
                                </p>
                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label>Tên vai trò <span class="req">*</span></label>
                                <input
                                    v-model="roleForm.ten_vai_tro"
                                    type="text"
                                    placeholder="VD: admin, co_van, sinh_vien"
                                />
                                <span v-if="roleErrors.ten_vai_tro" class="form-error">
                                    {{ roleErrors.ten_vai_tro }}
                                </span>
                            </div>

                            <div class="form-group">
                                <label>Mô tả</label>
                                <textarea
                                    v-model="roleForm.mo_ta"
                                    placeholder="Nhập mô tả vai trò..."
                                ></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Hủy
                            </button>
                            <button type="submit" class="btn btn-primary">
                                {{ isEditingRole ? 'Cập nhật' : 'Thêm mới' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL ROLE DELETE -->
        <div class="modal fade" id="roleDeleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5">Xóa vai trò</h1>
                            <p class="text-sm text-gray-500 mb-0">Hành động này không thể hoàn tác.</p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <p>
                            Bạn có chắc muốn xóa vai trò
                            <strong>{{ roleDeleteTarget ? formatRoleName(roleDeleteTarget.ten_vai_tro) : '' }}</strong>
                            không?
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Hủy
                        </button>
                        <button type="button" class="btn btn-primary" @click="confirmDeleteRole">
                            Xóa
                        </button>
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
                                    <input
                                        v-model="functionForm.ma_chuc_nang"
                                        type="text"
                                        placeholder="VD: quan_ly_user"
                                    />
                                    <span v-if="functionErrors.ma_chuc_nang" class="form-error">
                                        {{ functionErrors.ma_chuc_nang }}
                                    </span>
                                </div>

                                <div class="form-group">
                                    <label>Tên chức năng <span class="req">*</span></label>
                                    <input
                                        v-model="functionForm.ten_chuc_nang"
                                        type="text"
                                        placeholder="VD: Quản lý người dùng"
                                    />
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
                                    <textarea
                                        v-model="functionForm.mo_ta"
                                        placeholder="Nhập mô tả chức năng..."
                                    ></textarea>
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
import { computed, reactive, ref } from 'vue'
import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const roles = ref([
    {
        id: 1,
        ten_vai_tro: 'admin',
        mo_ta: 'Quản trị viên hệ thống',
    },
    {
        id: 2,
        ten_vai_tro: 'co_van',
        mo_ta: 'Cố vấn học tập',
    },
    {
        id: 3,
        ten_vai_tro: 'sinh_vien',
        mo_ta: 'Sinh viên',
    },
])

const functions = ref([
    {
        id: 1,
        ma_chuc_nang: 'quan_ly_user',
        ten_chuc_nang: 'Quản lý người dùng',
        mo_ta: 'Tạo, sửa, xóa tài khoản người dùng',
        trang_thai: 'hoat_dong',
    },
    {
        id: 2,
        ma_chuc_nang: 'quan_ly_dao_tao',
        ten_chuc_nang: 'Quản lý đào tạo',
        mo_ta: 'Quản lý khoa, ngành, lớp, danh sách lớp',
        trang_thai: 'hoat_dong',
    },
    {
        id: 3,
        ma_chuc_nang: 'quan_ly_dot_danh_gia',
        ten_chuc_nang: 'Quản lý đợt đánh giá',
        mo_ta: 'Tạo và quản lý các đợt đánh giá rèn luyện',
        trang_thai: 'hoat_dong',
    },
    {
        id: 4,
        ma_chuc_nang: 'quan_ly_tieu_chi',
        ten_chuc_nang: 'Quản lý tiêu chí',
        mo_ta: 'Quản lý nhóm tiêu chí, tiêu chí và lựa chọn điểm',
        trang_thai: 'hoat_dong',
    },
    {
        id: 5,
        ma_chuc_nang: 'nhap_phieu_ren_luyen',
        ten_chuc_nang: 'Nhập phiếu rèn luyện',
        mo_ta: 'Sinh viên nhập phiếu tự đánh giá',
        trang_thai: 'hoat_dong',
    },
    {
        id: 6,
        ma_chuc_nang: 'danh_gia_phieu_lop',
        ten_chuc_nang: 'Đánh giá phiếu lớp',
        mo_ta: 'Lớp trưởng/Bí thư đánh giá phiếu sinh viên',
        trang_thai: 'hoat_dong',
    },
    {
        id: 7,
        ma_chuc_nang: 'kiem_tra_phieu',
        ten_chuc_nang: 'Kiểm tra phiếu',
        mo_ta: 'Cố vấn học tập kiểm tra phiếu rèn luyện',
        trang_thai: 'hoat_dong',
    },
])

const permissions = ref([
    { id: 1, vai_tro_id: 2, chuc_nang_id: 7 },
    { id: 2, vai_tro_id: 3, chuc_nang_id: 5 },
])

const selectedRole = ref(roles.value.find(role => role.ten_vai_tro !== 'admin') || null)

const roleSearch = ref('')
const functionSearch = ref('')

const roleForm = reactive({
    ten_vai_tro: '',
    mo_ta: '',
})

const functionForm = reactive({
    ma_chuc_nang: '',
    ten_chuc_nang: '',
    mo_ta: '',
    trang_thai: 'hoat_dong',
})

const roleErrors = reactive({})
const functionErrors = reactive({})

const isEditingRole = ref(false)
const roleEditTarget = ref(null)
const roleDeleteTarget = ref(null)

const isEditingFunction = ref(false)
const functionEditTarget = ref(null)
const functionDeleteTarget = ref(null)

const filteredRoles = computed(() => {
    const q = roleSearch.value.trim().toLowerCase()

    const list = roles.value.filter((role) => role.ten_vai_tro !== 'admin')

    if (!q) return list

    return list.filter((role) => {
        return [
            role.ten_vai_tro,
            role.mo_ta,
        ].join(' ').toLowerCase().includes(q)
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
        ].join(' ').toLowerCase().includes(q)
    })
})

const grantedFunctionIds = computed(() => {
    if (!selectedRole.value) return []

    return permissions.value
        .filter((item) => Number(item.vai_tro_id) === Number(selectedRole.value.id))
        .map((item) => Number(item.chuc_nang_id))
})

const activePermissions = computed(() => {
    if (!selectedRole.value) return []

    return permissions.value
        .filter((permission) => Number(permission.vai_tro_id) === Number(selectedRole.value.id))
        .map((permission) => {
            const fn = functions.value.find((item) => Number(item.id) === Number(permission.chuc_nang_id))

            return {
                permission,
                function: fn || {
                    ten_chuc_nang: 'Chức năng không tồn tại',
                    ma_chuc_nang: '—',
                },
            }
        })
})

function selectRole(role) {
    selectedRole.value = role
}

function isGranted(functionId) {
    return grantedFunctionIds.value.includes(Number(functionId))
}

function grantPermission(fn) {
    if (!selectedRole.value) {
        alert('Vui lòng chọn vai trò trước.')
        return
    }

    if (fn.trang_thai !== 'hoat_dong') {
        alert('Chức năng đang tạm dừng, không thể cấp quyền.')
        return
    }

    if (isGranted(fn.id)) {
        alert('Vai trò này đã có chức năng này.')
        return
    }

    permissions.value.push({
        id: Date.now(),
        vai_tro_id: selectedRole.value.id,
        chuc_nang_id: fn.id,
    })
}

function revokePermission(permission) {
    permissions.value = permissions.value.filter((item) => item.id !== permission.id)
}

function openAddRole() {
    clearRoleForm()
}

function openEditRole(role) {
    clearRoleForm()
    isEditingRole.value = true
    roleEditTarget.value = role

    roleForm.ten_vai_tro = role.ten_vai_tro
    roleForm.mo_ta = role.mo_ta || ''
}

function openDeleteRole(role) {
    roleDeleteTarget.value = role
}

function saveRole() {
    if (!validateRole()) return

    const data = {
        ten_vai_tro: roleForm.ten_vai_tro.trim(),
        mo_ta: roleForm.mo_ta.trim() || null,
    }

    if (isEditingRole.value && roleEditTarget.value) {
        Object.assign(roleEditTarget.value, data)

        if (selectedRole.value?.id === roleEditTarget.value.id) {
            selectedRole.value = roleEditTarget.value
        }
    } else {
        const newRole = {
            id: Date.now(),
            ...data,
        }

        roles.value.unshift(newRole)
        selectedRole.value = newRole
    }

    hideModal('roleFormModal')
    clearRoleForm()
}

function confirmDeleteRole() {
    if (!roleDeleteTarget.value) return

    const id = roleDeleteTarget.value.id

    roles.value = roles.value.filter((role) => role.id !== id)
    permissions.value = permissions.value.filter((item) => item.vai_tro_id !== id)

    if (selectedRole.value?.id === id) {
        selectedRole.value = roles.value[0] || null
    }

    roleDeleteTarget.value = null
    hideModal('roleDeleteModal')
}

function validateRole() {
    clearObject(roleErrors)

    let ok = true

    if (!roleForm.ten_vai_tro.trim()) {
        roleErrors.ten_vai_tro = 'Vui lòng nhập tên vai trò'
        ok = false
    }

    const duplicated = roles.value.some((role) => {
        const sameId = isEditingRole.value && roleEditTarget.value && role.id === roleEditTarget.value.id

        return !sameId
            && role.ten_vai_tro.trim().toLowerCase() === roleForm.ten_vai_tro.trim().toLowerCase()
    })

    if (duplicated) {
        roleErrors.ten_vai_tro = 'Tên vai trò đã tồn tại'
        ok = false
    }

    return ok
}

function clearRoleForm() {
    roleForm.ten_vai_tro = ''
    roleForm.mo_ta = ''

    clearObject(roleErrors)

    isEditingRole.value = false
    roleEditTarget.value = null
}

function openAddFunction() {
    clearFunctionForm()
}

function openEditFunction(fn) {
    clearFunctionForm()

    isEditingFunction.value = true
    functionEditTarget.value = fn

    functionForm.ma_chuc_nang = fn.ma_chuc_nang
    functionForm.ten_chuc_nang = fn.ten_chuc_nang
    functionForm.mo_ta = fn.mo_ta || ''
    functionForm.trang_thai = fn.trang_thai
}

function openDeleteFunction(fn) {
    functionDeleteTarget.value = fn
}

function saveFunction() {
    if (!validateFunction()) return

    const data = {
        ma_chuc_nang: functionForm.ma_chuc_nang.trim(),
        ten_chuc_nang: functionForm.ten_chuc_nang.trim(),
        mo_ta: functionForm.mo_ta.trim() || null,
        trang_thai: functionForm.trang_thai,
    }

    if (isEditingFunction.value && functionEditTarget.value) {
        Object.assign(functionEditTarget.value, data)
    } else {
        functions.value.unshift({
            id: Date.now(),
            ...data,
        })
    }

    hideModal('functionFormModal')
    clearFunctionForm()
}

function confirmDeleteFunction() {
    if (!functionDeleteTarget.value) return

    const id = functionDeleteTarget.value.id

    functions.value = functions.value.filter((fn) => fn.id !== id)
    permissions.value = permissions.value.filter((item) => item.chuc_nang_id !== id)

    functionDeleteTarget.value = null
    hideModal('functionDeleteModal')
}

function validateFunction() {
    clearObject(functionErrors)

    let ok = true

    if (!functionForm.ma_chuc_nang.trim()) {
        functionErrors.ma_chuc_nang = 'Vui lòng nhập mã chức năng'
        ok = false
    }

    if (!functionForm.ten_chuc_nang.trim()) {
        functionErrors.ten_chuc_nang = 'Vui lòng nhập tên chức năng'
        ok = false
    }

    const duplicated = functions.value.some((fn) => {
        const sameId = isEditingFunction.value && functionEditTarget.value && fn.id === functionEditTarget.value.id

        return !sameId
            && fn.ma_chuc_nang.trim().toLowerCase() === functionForm.ma_chuc_nang.trim().toLowerCase()
    })

    if (duplicated) {
        functionErrors.ma_chuc_nang = 'Mã chức năng đã tồn tại'
        ok = false
    }

    return ok
}

function clearFunctionForm() {
    functionForm.ma_chuc_nang = ''
    functionForm.ten_chuc_nang = ''
    functionForm.mo_ta = ''
    functionForm.trang_thai = 'hoat_dong'

    clearObject(functionErrors)

    isEditingFunction.value = false
    functionEditTarget.value = null
}

function formatRoleName(value) {
    const map = {
        admin: 'Admin',
        co_van: 'Cố vấn học tập',
        sinh_vien: 'Sinh viên',
    }

    return map[value] || value || '—'
}

function getRoleShortName(value) {
    if (!value) return '?'

    const map = {
        admin: 'AD',
        co_van: 'CV',
        sinh_vien: 'SV',
    }

    return map[value] || String(value).charAt(0).toUpperCase()
}

function formatFunctionStatus(value) {
    const map = {
        hoat_dong: 'Hoạt động',
        tam_dung: 'Tạm dừng',
    }

    return map[value] || '—'
}

function clearObject(obj) {
    Object.keys(obj).forEach((key) => delete obj[key])
}

function hideModal(id) {
    const el = document.getElementById(id)
    const ModalCtor = window.bootstrap?.Modal

    if (!el || !ModalCtor) return

    const instance = ModalCtor.getInstance(el) || ModalCtor.getOrCreateInstance(el)
    instance.hide()
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