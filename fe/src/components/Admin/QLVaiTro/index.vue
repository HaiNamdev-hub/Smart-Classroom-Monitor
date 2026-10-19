<template>
    <div class="permission-page">
        <!-- ================= HEADER ================= -->
        <div class="page-heading">
            <div>
                <span class="eyebrow">QUẢN TRỊ HỆ THỐNG</span>
                <h1>Phân quyền chức năng</h1>
                <p>
                    Chọn vai trò và thiết lập các chức năng được phép sử dụng
                    trong hệ thống.
                </p>
            </div>

            <button
                class="refresh-btn"
                type="button"
                :disabled="loading"
                @click="loadAll"
            >
                <span :class="{ spin: loading }">↻</span>
                Làm mới
            </button>
        </div>

        <!-- ================= CONTENT ================= -->
        <div class="permission-layout">
            <!-- ============ ROLE SIDEBAR ============ -->
            <section class="role-panel">
                <div class="panel-title">
                    <div>
                        <h2>Vai trò</h2>
                        <p>Chọn vai trò cần phân quyền</p>
                    </div>

                    <span class="count-badge">
                        {{ roles.length }}
                    </span>
                </div>

                <div v-if="loadingRoles" class="loading-box">
                    <div class="spinner"></div>
                    Đang tải vai trò...
                </div>

                <div v-else class="role-list">
                    <button
                        v-for="role in roles"
                        :key="role.id"
                        type="button"
                        class="role-item"
                        :class="{
                            active: selectedRole?.id === role.id
                        }"
                        @click="selectRole(role)"
                    >
                        <div
                            class="role-avatar"
                            :class="roleClass(role.ten_vai_tro)"
                        >
                            {{ roleShortName(role.ten_vai_tro) }}
                        </div>

                        <div class="role-content">
                            <strong>
                                {{ roleDisplayName(role.ten_vai_tro) }}
                            </strong>

                            <span>
                                {{ role.ten_vai_tro }}
                            </span>
                        </div>

                        <div class="role-arrow">›</div>
                    </button>

                    <div
                        v-if="roles.length === 0"
                        class="empty-small"
                    >
                        Chưa có vai trò.
                    </div>
                </div>
            </section>

            <!-- ============ MAIN ============ -->
            <section class="permission-main">
                <!-- Chưa chọn role -->
                <div
                    v-if="!selectedRole"
                    class="select-role-empty"
                >
                    <div class="empty-circle">✓</div>

                    <h3>Chọn một vai trò</h3>

                    <p>
                        Chọn vai trò ở bên trái để bắt đầu thiết lập
                        quyền chức năng.
                    </p>
                </div>

                <template v-else>
                    <!-- Selected role -->
                    <div class="selected-role-header">
                        <div class="selected-role-left">
                            <div
                                class="selected-avatar"
                                :class="
                                    roleClass(
                                        selectedRole.ten_vai_tro
                                    )
                                "
                            >
                                {{
                                    roleShortName(
                                        selectedRole.ten_vai_tro
                                    )
                                }}
                            </div>

                            <div>
                                <span>ĐANG PHÂN QUYỀN CHO</span>

                                <h2>
                                    {{
                                        roleDisplayName(
                                            selectedRole.ten_vai_tro
                                        )
                                    }}
                                </h2>
                            </div>
                        </div>

                        <div class="permission-total">
                            <strong>{{ assignedPermissions.length }}</strong>
                            <span>quyền đang bật</span>
                        </div>
                    </div>

                    <!-- ADMIN INFO -->
                    <div
                        v-if="isAdminRole"
                        class="admin-notice"
                    >
                        <div class="notice-icon">!</div>

                        <div>
                            <strong>
                                Quản trị viên có toàn quyền
                            </strong>

                            <p>
                                Vai trò Quản trị viên được backend cấp toàn
                                quyền mặc định nên không cần lưu từng quyền
                                trong bảng phân quyền.
                            </p>
                        </div>
                    </div>

                    <!-- Permission columns -->
                    <div
                        class="permission-columns"
                        :class="{ disabled: isAdminRole }"
                    >
                        <!-- ========== FUNCTION STORE ========== -->
                        <div class="function-column">
                            <div class="column-heading">
                                <div>
                                    <h3>Kho chức năng</h3>
                                    <p>
                                        Chọn chức năng để cấp quyền
                                    </p>
                                </div>

                                <span>
                                    {{ availableFunctions.length }}
                                </span>
                            </div>

                            <div class="search-box">
                                <span>⌕</span>

                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Tìm chức năng..."
                                />
                            </div>

                            <div
                                v-if="loadingFunctions"
                                class="loading-box"
                            >
                                <div class="spinner"></div>
                                Đang tải chức năng...
                            </div>

                            <div
                                v-else
                                class="function-list"
                            >
                                <button
                                    v-for="func in filteredAvailableFunctions"
                                    :key="func.id"
                                    type="button"
                                    class="function-item"
                                    :disabled="
                                        processing ||
                                        isAdminRole ||
                                        func.trang_thai === 'tam_dung'
                                    "
                                    @click="addPermission(func)"
                                >
                                    <div class="function-icon">
                                        {{ functionInitial(func.ten_chuc_nang) }}
                                    </div>

                                    <div class="function-info">
                                        <strong>
                                            {{ func.ten_chuc_nang }}
                                        </strong>

                                        <span>
                                            {{ func.ma_chuc_nang }}
                                        </span>

                                        <p v-if="func.mo_ta">
                                            {{ func.mo_ta }}
                                        </p>
                                    </div>

                                    <div
                                        v-if="func.trang_thai === 'tam_dung'"
                                        class="paused-badge"
                                    >
                                        Tạm dừng
                                    </div>

                                    <div
                                        v-else
                                        class="add-icon"
                                    >
                                        +
                                    </div>
                                </button>

                                <div
                                    v-if="
                                        filteredAvailableFunctions.length === 0
                                    "
                                    class="empty-permission"
                                >
                                    <div>✓</div>

                                    <strong>
                                        Không còn chức năng
                                    </strong>

                                    <p>
                                        Vai trò đã được cấp toàn bộ chức năng
                                        khả dụng hoặc không có kết quả tìm kiếm.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ARROW -->
                        <div class="middle-arrow">
                            →
                        </div>

                        <!-- ========== ASSIGNED ========== -->
                        <div class="assigned-column">
                            <div class="column-heading">
                                <div>
                                    <h3>Quyền đang kích hoạt</h3>
                                    <p>
                                        Các chức năng vai trò được phép sử dụng
                                    </p>
                                </div>

                                <span>
                                    {{ assignedPermissions.length }}
                                </span>
                            </div>

                            <div
                                v-if="loadingPermissions"
                                class="loading-box"
                            >
                                <div class="spinner"></div>
                                Đang tải quyền...
                            </div>

                            <div
                                v-else
                                class="assigned-list"
                            >
                                <div
                                    v-for="permission in assignedPermissions"
                                    :key="permission.chuc_nang_id"
                                    class="assigned-item"
                                >
                                    <div class="assigned-check">
                                        ✓
                                    </div>

                                    <div class="function-info">
                                        <strong>
                                            {{ permission.ten_chuc_nang }}
                                        </strong>

                                        <span>
                                            {{ permission.ma_chuc_nang }}
                                        </span>

                                        <p v-if="permission.mo_ta">
                                            {{ permission.mo_ta }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="remove-btn"
                                        title="Gỡ quyền"
                                        :disabled="
                                            processing ||
                                            isAdminRole
                                        "
                                        @click="
                                            removePermission(
                                                permission
                                            )
                                        "
                                    >
                                        ×
                                    </button>
                                </div>

                                <div
                                    v-if="
                                        assignedPermissions.length === 0
                                    "
                                    class="empty-permission"
                                >
                                    <div>−</div>

                                    <strong>
                                        Chưa có quyền
                                    </strong>

                                    <p>
                                        Chọn chức năng ở bên trái để cấp quyền
                                        cho vai trò này.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </section>
        </div>
    </div>
</template>

<script setup>
import axios from "axios"
import {
    computed,
    onMounted,
    ref,
} from "vue"
import { createToaster } from "@meforma/vue-toaster"

const toaster = createToaster({
    position: "top-right",
})

const BASE_URL = "/api/admin"

/* =========================
   STATE
========================= */

const roles = ref([])
const functions = ref([])
const assignedPermissions = ref([])

const selectedRole = ref(null)

const search = ref("")

const loading = ref(false)
const loadingRoles = ref(false)
const loadingFunctions = ref(false)
const loadingPermissions = ref(false)

const processing = ref(false)

/* =========================
   AUTH
========================= */

function authHeader() {
    const token =
        sessionStorage.getItem("auth_token") ||
        localStorage.getItem("auth_token") ||
        localStorage.getItem("access_token")

    return {
        headers: {
            Authorization: `Bearer ${token}`,
            Accept: "application/json",
        },
    }
}

/* =========================
   COMPUTED
========================= */

const isAdminRole = computed(() => {
    return selectedRole.value?.ten_vai_tro === "quan_tri_vien"
})

const assignedIds = computed(() => {
    return new Set(
        assignedPermissions.value.map(
            (item) => Number(item.chuc_nang_id)
        )
    )
})

const availableFunctions = computed(() => {
    return functions.value.filter((func) => {
        return !assignedIds.value.has(Number(func.id))
    })
})

const filteredAvailableFunctions = computed(() => {
    const keyword = search.value
        .trim()
        .toLowerCase()

    if (!keyword) {
        return availableFunctions.value
    }

    return availableFunctions.value.filter((func) => {
        const name =
            func.ten_chuc_nang
                ?.toLowerCase() || ""

        const code =
            func.ma_chuc_nang
                ?.toLowerCase() || ""

        const description =
            func.mo_ta
                ?.toLowerCase() || ""

        return (
            name.includes(keyword) ||
            code.includes(keyword) ||
            description.includes(keyword)
        )
    })
})

/* =========================
   ON MOUNT
========================= */

onMounted(async () => {
    await loadAll()
})

/* =========================
   LOAD ALL
========================= */

async function loadAll() {
    loading.value = true

    try {
        await Promise.all([
            getRoles(),
            getFunctions(),
        ])

        if (selectedRole.value) {
            const current = roles.value.find(
                (item) =>
                    item.id === selectedRole.value.id
            )

            if (current) {
                selectedRole.value = current

                await getPermissions()
            }
        }
    } finally {
        loading.value = false
    }
}

/* =========================
   GET ROLES
========================= */

async function getRoles() {
    loadingRoles.value = true

    try {
        const response = await axios.get(
            BASE_URL + "/vai-tro/get-data",
            authHeader()
        )

        roles.value =
            response.data?.data || []
    } catch (error) {
        handleError(
            error,
            "Không lấy được danh sách vai trò."
        )
    } finally {
        loadingRoles.value = false
    }
}

/* =========================
   GET FUNCTIONS
========================= */

async function getFunctions() {
    loadingFunctions.value = true

    try {
        const response = await axios.get(
            BASE_URL +
                "/phan-quyen/chuc-nang/get-data",
            authHeader()
        )

        functions.value =
            response.data?.data || []
    } catch (error) {
        handleError(
            error,
            "Không lấy được danh sách chức năng."
        )
    } finally {
        loadingFunctions.value = false
    }
}

/* =========================
   SELECT ROLE
========================= */

async function selectRole(role) {
    if (processing.value) {
        return
    }

    selectedRole.value = role
    search.value = ""
    assignedPermissions.value = []

    /*
        Admin được backend cấp toàn quyền mặc định.
        Không cần lấy bảng phan_quyen.
    */
    if (role.ten_vai_tro === "quan_tri_vien") {
        return
    }

    await getPermissions()
}

/* =========================
   GET PERMISSIONS
========================= */

async function getPermissions() {
    if (!selectedRole.value) {
        return
    }

    if (isAdminRole.value) {
        assignedPermissions.value = []
        return
    }

    loadingPermissions.value = true

    try {
        const response = await axios.post(
            BASE_URL +
                "/phan-quyen/chi-tiet-phan-quyen/get-data",
            {
                vai_tro_id:
                    selectedRole.value.id,
            },
            authHeader()
        )

        assignedPermissions.value =
            response.data?.data || []
    } catch (error) {
        assignedPermissions.value = []

        handleError(
            error,
            "Không lấy được danh sách quyền."
        )
    } finally {
        loadingPermissions.value = false
    }
}

/* =========================
   ADD PERMISSION
========================= */

async function addPermission(func) {
    if (
        !selectedRole.value ||
        processing.value ||
        isAdminRole.value
    ) {
        return
    }

    processing.value = true

    try {
        const response = await axios.post(
            BASE_URL +
                "/phan-quyen/chi-tiet-phan-quyen/add-data",
            {
                vai_tro_id:
                    selectedRole.value.id,

                chuc_nang_id:
                    func.id,
            },
            authHeader()
        )

        if (response.data?.status === false) {
            toaster.error(
                response.data?.message ||
                    "Không thể cấp quyền."
            )

            return
        }

        toaster.success(
            response.data?.message ||
                "Đã cấp quyền thành công."
        )

        await getPermissions()
    } catch (error) {
        handleError(
            error,
            "Cấp quyền thất bại."
        )
    } finally {
        processing.value = false
    }
}

/* =========================
   REMOVE PERMISSION
========================= */

async function removePermission(permission) {
    if (
        !selectedRole.value ||
        processing.value ||
        isAdminRole.value
    ) {
        return
    }

    const accepted = window.confirm(
        `Bạn có chắc muốn gỡ quyền "${permission.ten_chuc_nang}" khỏi vai trò "${roleDisplayName(selectedRole.value.ten_vai_tro)}"?`
    )

    if (!accepted) {
        return
    }

    processing.value = true

    try {
        const response = await axios.post(
            BASE_URL +
                "/phan-quyen/chi-tiet-phan-quyen/delete",
            {
                vai_tro_id:
                    selectedRole.value.id,

                chuc_nang_id:
                    permission.chuc_nang_id,
            },
            authHeader()
        )

        if (response.data?.status === false) {
            toaster.error(
                response.data?.message ||
                    "Không thể gỡ quyền."
            )

            return
        }

        toaster.success(
            response.data?.message ||
                "Đã gỡ quyền thành công."
        )

        await getPermissions()
    } catch (error) {
        handleError(
            error,
            "Gỡ quyền thất bại."
        )
    } finally {
        processing.value = false
    }
}

/* =========================
   DISPLAY HELPERS
========================= */

function roleDisplayName(value) {
    const map = {
        quan_tri_vien: "Quản trị viên",
        giang_vien: "Giảng viên",
        sinh_vien: "Sinh viên",
    }

    return map[value] || value || "Không xác định"
}

function roleShortName(value) {
    const map = {
        quan_tri_vien: "QT",
        giang_vien: "GV",
        sinh_vien: "SV",
    }

    return map[value] || "VT"
}

function roleClass(value) {
    const map = {
        quan_tri_vien: "admin-role",
        giang_vien: "lecturer-role",
        sinh_vien: "student-role",
    }

    return map[value] || ""
}

function functionInitial(value) {
    if (!value) {
        return "CN"
    }

    const words = value
        .trim()
        .split(/\s+/)
        .filter(Boolean)

    if (words.length === 1) {
        return words[0]
            .substring(0, 2)
            .toUpperCase()
    }

    return (
        words[0][0] +
        words[words.length - 1][0]
    ).toUpperCase()
}

/* =========================
   ERROR
========================= */

function handleError(error, fallback) {
    console.error(
        fallback,
        error.response?.data || error
    )

    if (error.response?.status === 401) {
        toaster.error(
            "Phiên đăng nhập đã hết hạn."
        )
        return
    }

    if (error.response?.status === 403) {
        toaster.error(
            error.response?.data?.message ||
                "Bạn không có quyền thực hiện chức năng này."
        )
        return
    }

    if (error.response?.status === 422) {
        const errors =
            error.response?.data?.errors

        if (errors) {
            const first =
                Object.keys(errors)[0]

            if (first) {
                toaster.error(
                    errors[first]?.[0] ||
                        "Dữ liệu không hợp lệ."
                )
                return
            }
        }
    }

    toaster.error(
        error.response?.data?.message ||
            fallback
    )
}
</script>

<style scoped>
.permission-page {
    --page: #f6f7fb;
    --card: #ffffff;
    --soft: #f8fafc;
    --border: #e6e9ef;

    --text: #172033;
    --secondary: #697386;
    --muted: #98a2b3;

    --primary: #dc2626;
    --blue: #2563eb;
    --green: #16a34a;

    min-height: 100%;
    box-sizing: border-box;

    padding: 28px;

    background: var(--page);
    color: var(--text);

    font-family:
        Inter,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
}

/* HEADER */

.page-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;

    gap: 20px;

    margin-bottom: 22px;
}

.eyebrow {
    display: block;

    margin-bottom: 6px;

    color: var(--primary);

    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1.2px;
}

.page-heading h1 {
    margin: 0;

    color: var(--text);

    font-size: 29px;
    font-weight: 850;
}

.page-heading p {
    margin: 7px 0 0;

    color: var(--secondary);

    font-size: 14px;
}

.refresh-btn {
    min-height: 41px;

    display: flex;
    align-items: center;

    gap: 7px;

    padding: 0 15px;

    border: 0;
    border-radius: 11px;

    background: rgba(37, 99, 235, 0.1);
    color: var(--blue);

    font-size: 13px;
    font-weight: 800;

    cursor: pointer;
}

.refresh-btn span {
    font-size: 19px;
}

.refresh-btn:disabled {
    opacity: 0.6;
}

.spin {
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* MAIN LAYOUT */

.permission-layout {
    display: grid;

    grid-template-columns:
        290px minmax(0, 1fr);

    gap: 18px;

    align-items: stretch;
}

/* PANELS */

.role-panel,
.permission-main {
    background: var(--card);

    border: 1px solid var(--border);
    border-radius: 20px;

    box-shadow:
        0 8px 30px rgba(15, 23, 42, 0.05);
}

.role-panel {
    padding: 18px;

    align-self: start;
}

.permission-main {
    min-height: 570px;

    padding: 20px;
}

/* PANEL TITLE */

.panel-title,
.column-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;

    gap: 12px;
}

.panel-title {
    padding-bottom: 15px;

    border-bottom: 1px solid var(--border);
}

.panel-title h2,
.column-heading h3 {
    margin: 0;

    color: var(--text);

    font-size: 17px;
    font-weight: 850;
}

.panel-title p,
.column-heading p {
    margin: 4px 0 0;

    color: var(--secondary);

    font-size: 11px;
}

.count-badge,
.column-heading > span {
    min-width: 26px;
    height: 26px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 0 7px;

    border-radius: 999px;

    background: rgba(220, 38, 38, 0.09);
    color: var(--primary);

    font-size: 11px;
    font-weight: 900;
}

/* ROLE */

.role-list {
    display: flex;
    flex-direction: column;

    gap: 8px;

    margin-top: 14px;
}

.role-item {
    width: 100%;

    display: flex;
    align-items: center;

    gap: 11px;

    padding: 12px;

    border: 1px solid transparent;
    border-radius: 14px;

    background: transparent;

    text-align: left;

    cursor: pointer;

    transition: 0.18s;
}

.role-item:hover {
    background: var(--soft);

    border-color: var(--border);
}

.role-item.active {
    background: rgba(220, 38, 38, 0.07);

    border-color: rgba(220, 38, 38, 0.25);
}

.role-avatar,
.selected-avatar {
    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    color: white;

    font-size: 12px;
    font-weight: 900;
}

.role-avatar {
    width: 43px;
    height: 43px;
}

.selected-avatar {
    width: 49px;
    height: 49px;
}

.admin-role {
    background: #dc2626;
}

.lecturer-role {
    background: #2563eb;
}

.student-role {
    background: #16a34a;
}

.role-content {
    min-width: 0;
    flex: 1;

    display: flex;
    flex-direction: column;
}

.role-content strong {
    overflow: hidden;

    color: var(--text);

    font-size: 13px;

    text-overflow: ellipsis;
    white-space: nowrap;
}

.role-content span {
    margin-top: 2px;

    color: var(--secondary);

    font-size: 10px;
}

.role-arrow {
    color: var(--muted);

    font-size: 23px;
}

.role-item.active .role-arrow {
    color: var(--primary);
}

/* SELECTED ROLE */

.selected-role-header {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 15px;

    padding-bottom: 18px;

    border-bottom: 1px solid var(--border);
}

.selected-role-left {
    display: flex;
    align-items: center;

    gap: 12px;
}

.selected-role-left span {
    color: var(--secondary);

    font-size: 9px;
    font-weight: 900;
    letter-spacing: 1px;
}

.selected-role-left h2 {
    margin: 3px 0 0;

    color: var(--text);

    font-size: 20px;
}

.permission-total {
    display: flex;
    align-items: baseline;

    gap: 5px;

    padding: 8px 12px;

    border-radius: 12px;

    background: var(--soft);
}

.permission-total strong {
    color: var(--primary);

    font-size: 19px;
}

.permission-total span {
    color: var(--secondary);

    font-size: 10px;
}

/* ADMIN NOTICE */

.admin-notice {
    display: flex;

    gap: 11px;

    margin-top: 17px;
    padding: 13px;

    border: 1px solid rgba(37, 99, 235, 0.17);
    border-radius: 13px;

    background: rgba(37, 99, 235, 0.06);
}

.notice-icon {
    width: 27px;
    height: 27px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: rgba(37, 99, 235, 0.12);
    color: var(--blue);

    font-weight: 900;
}

.admin-notice strong {
    color: var(--text);

    font-size: 12px;
}

.admin-notice p {
    margin: 3px 0 0;

    color: var(--secondary);

    font-size: 11px;
    line-height: 1.5;
}

/* PERMISSION COLUMNS */

.permission-columns {
    display: grid;

    grid-template-columns:
        minmax(0, 1fr)
        40px
        minmax(0, 1fr);

    gap: 12px;

    margin-top: 18px;
}

.permission-columns.disabled {
    opacity: 0.55;

    pointer-events: none;
}

.function-column,
.assigned-column {
    min-width: 0;

    padding: 16px;

    background: var(--soft);

    border: 1px solid var(--border);
    border-radius: 16px;
}

.middle-arrow {
    display: flex;
    align-items: center;
    justify-content: center;

    color: var(--muted);

    font-size: 24px;
}

/* SEARCH */

.search-box {
    height: 39px;

    display: flex;
    align-items: center;

    gap: 7px;

    margin: 14px 0;

    padding: 0 11px;

    border: 1px solid var(--border);
    border-radius: 10px;

    background: var(--card);
}

.search-box span {
    color: var(--muted);

    font-size: 18px;
}

.search-box input {
    width: 100%;

    border: 0;
    outline: 0;

    background: transparent;
    color: var(--text);

    font-size: 12px;
}

/* LISTS */

.function-list,
.assigned-list {
    display: flex;
    flex-direction: column;

    gap: 8px;

    max-height: 430px;

    overflow-y: auto;

    padding-right: 3px;
}

.function-item,
.assigned-item {
    display: flex;
    align-items: flex-start;

    gap: 10px;

    padding: 12px;

    border: 1px solid var(--border);
    border-radius: 13px;

    background: var(--card);

    transition: 0.17s;
}

.function-item {
    width: 100%;

    text-align: left;

    cursor: pointer;
}

.function-item:hover:not(:disabled) {
    transform: translateY(-1px);

    border-color: rgba(37, 99, 235, 0.3);

    box-shadow:
        0 6px 16px rgba(15, 23, 42, 0.05);
}

.function-item:disabled {
    cursor: not-allowed;
}

.function-icon,
.assigned-check {
    width: 35px;
    height: 35px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    font-size: 10px;
    font-weight: 900;
}

.function-icon {
    background: rgba(37, 99, 235, 0.09);
    color: var(--blue);
}

.assigned-check {
    background: rgba(22, 163, 74, 0.1);
    color: var(--green);

    font-size: 15px;
}

.function-info {
    min-width: 0;
    flex: 1;

    display: flex;
    flex-direction: column;
}

.function-info strong {
    color: var(--text);

    font-size: 12px;
    line-height: 1.4;
}

.function-info span {
    margin-top: 2px;

    color: var(--muted);

    font-size: 9px;
}

.function-info p {
    margin: 5px 0 0;

    color: var(--secondary);

    font-size: 10px;
    line-height: 1.45;
}

.add-icon {
    width: 26px;
    height: 26px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 8px;

    background: rgba(37, 99, 235, 0.08);
    color: var(--blue);

    font-size: 18px;
}

.remove-btn {
    width: 28px;
    height: 28px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 0;
    border-radius: 8px;

    background: rgba(220, 38, 38, 0.08);
    color: var(--primary);

    font-size: 18px;

    cursor: pointer;
}

.remove-btn:hover:not(:disabled) {
    background: rgba(220, 38, 38, 0.15);
}

.paused-badge {
    flex-shrink: 0;

    padding: 5px 7px;

    border-radius: 999px;

    background: rgba(245, 158, 11, 0.1);
    color: #d97706;

    font-size: 8px;
    font-weight: 800;
}

/* EMPTY */

.select-role-empty {
    min-height: 500px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    text-align: center;
}

.empty-circle {
    width: 58px;
    height: 58px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: rgba(37, 99, 235, 0.08);
    color: var(--blue);

    font-size: 22px;
}

.select-role-empty h3 {
    margin: 14px 0 5px;

    color: var(--text);

    font-size: 17px;
}

.select-role-empty p {
    max-width: 330px;

    margin: 0;

    color: var(--secondary);

    font-size: 12px;
    line-height: 1.6;
}

.empty-permission {
    min-height: 145px;

    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    padding: 15px;

    color: var(--secondary);

    text-align: center;
}

.empty-permission > div {
    width: 36px;
    height: 36px;

    display: flex;
    align-items: center;
    justify-content: center;

    margin-bottom: 8px;

    border-radius: 50%;

    background: rgba(148, 163, 184, 0.1);
}

.empty-permission strong {
    color: var(--text);

    font-size: 11px;
}

.empty-permission p {
    max-width: 230px;

    margin: 4px 0 0;

    font-size: 10px;
    line-height: 1.5;
}

.empty-small {
    padding: 20px;

    color: var(--secondary);

    text-align: center;

    font-size: 12px;
}

/* LOADING */

.loading-box {
    min-height: 100px;

    display: flex;
    align-items: center;
    justify-content: center;

    gap: 8px;

    color: var(--secondary);

    font-size: 11px;
}

.spinner {
    width: 17px;
    height: 17px;

    border: 2px solid var(--border);
    border-top-color: var(--blue);
    border-radius: 50%;

    animation: spin 0.7s linear infinite;
}

/* DARK MODE */

:global(html.dark-theme) .permission-page {
    --page: #111315;
    --card: #1c1f22;
    --soft: #24282c;

    --border: rgba(255, 255, 255, 0.08);

    --text: #f8fafc;
    --secondary: #a3adba;
    --muted: #7f8997;
}

:global(html.dark-theme) .search-box {
    background: #1c1f22;
}

/* RESPONSIVE */

@media (max-width: 1100px) {
    .permission-layout {
        grid-template-columns: 240px minmax(0, 1fr);
    }

    .permission-columns {
        grid-template-columns: 1fr;
    }

    .middle-arrow {
        transform: rotate(90deg);
    }
}

@media (max-width: 760px) {
    .permission-page {
        padding: 16px;
    }

    .page-heading {
        flex-direction: column;
    }

    .refresh-btn {
        width: 100%;

        justify-content: center;
    }

    .permission-layout {
        grid-template-columns: 1fr;
    }

    .role-list {
        display: grid;

        grid-template-columns:
            repeat(3, minmax(0, 1fr));
    }

    .role-item {
        flex-direction: column;

        text-align: center;
    }

    .role-arrow {
        display: none;
    }

    .selected-role-header {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 520px) {
    .role-list {
        grid-template-columns: 1fr;
    }
}
</style>