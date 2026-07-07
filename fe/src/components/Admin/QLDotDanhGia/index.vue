<template>
    <div class="admin-content training-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Quản lý đợt đánh giá</h1>
            <p>Quản lý năm học, học kỳ, thời gian mở/đóng đợt đánh giá rèn luyện.</p>
        </div>

        <!-- PANEL -->
        <div class="training-panel">
            <div class="toolbar flex items-center gap-4 flex-wrap w-full pb-4">
                <div class="search-box flex items-center shrink-0 flex-1 min-w-[250px]">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Tìm theo năm học, học kỳ hoặc trạng thái..."
                        class="outline-none bg-transparent w-full"
                    />
                </div>

                <button
                    class="btn-primary ml-auto"
                    type="button"
                    @click="openAdd"
                    data-bs-toggle="modal"
                    data-bs-target="#periodFormModal"
                >
                    Thêm đợt đánh giá
                </button>
            </div>

            <!-- TABLE -->
            <div class="table-wrap">
                <table class="data-table w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th style="width:48px">STT</th>
                            <th>Năm học</th>
                            <th>Học kỳ</th>
                            <th>Ngày bắt đầu</th>
                            <th>Ngày kết thúc</th>
                            <th>Trạng thái</th>
                            <th style="width:260px">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(row, index) in filteredPeriods" :key="row.id" class="border-b hover:bg-gray-50">
                            <td class="py-3 action-cell">{{ index + 1 }}</td>
                            <td class="py-3 action-cell">{{ row.nam_hoc }}</td>
                            <td class="py-3 action-cell">{{ formatHocKy(row.hoc_ky) }}</td>
                            <td class="py-3 action-cell">{{ formatDate(row.ngay_bat_dau) }}</td>
                            <td class="py-3 action-cell">{{ formatDate(row.ngay_ket_thuc) }}</td>
                            <td class="py-3 action-cell">
                                <span class="period-status" :class="statusClass(row.trang_thai)">
                                    {{ formatTrangThai(row.trang_thai) }}
                                </span>
                            </td>

                            <td class="py-3 action-cell">
                                <div class="row-actions flex items-center gap-2 text-nowrap">

                                    <button
                                        class="icon-btn edit"
                                        title="Sửa"
                                        type="button"
                                        @click="openEdit(row)"
                                        data-bs-toggle="modal"
                                        data-bs-target="#periodFormModal"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button
                                        v-if="row.trang_thai === 'nhap'"
                                        class="icon-btn open"
                                        title="Mở đợt"
                                        type="button"
                                        @click="openPeriod(row)"
                                    >
                                        <i class="fa-solid fa-play"></i>
                                    </button>

                                    <button
                                        v-if="row.trang_thai === 'dang_mo'"
                                        class="icon-btn close-period"
                                        title="Đóng đợt"
                                        type="button"
                                        @click="closePeriod(row)"
                                    >
                                        <i class="fa-solid fa-lock"></i>
                                    </button>

                                    <button
                                        class="icon-btn del"
                                        title="Xóa"
                                        type="button"
                                        @click="openDelete(row)"
                                        data-bs-toggle="modal"
                                        data-bs-target="#periodDeleteModal"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredPeriods.length === 0">
                            <td colspan="7" class="empty-row text-center py-4 action-cell">
                                Không tìm thấy dữ liệu phù hợp.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL FORM THÊM/SỬA -->
        <div class="modal fade" id="periodFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form @submit.prevent="savePeriod">
                        <div class="modal-header">
                            <div>
                                <h1 class="modal-title fs-5">
                                    {{ isEditing ? 'Sửa' : 'Thêm' }} đợt đánh giá
                                </h1>
                                <p class="text-sm text-gray-500 mb-0">
                                    Cấu hình năm học, học kỳ và thời gian đánh giá.
                                </p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-grid">

                                <div class="form-group">
                                    <label>Năm học <span class="req">*</span></label>
                                    <input
                                        v-model="form.nam_hoc"
                                        type="text"
                                        placeholder="VD: 2025-2026"
                                    />
                                    <span v-if="errors.nam_hoc" class="form-error">{{ errors.nam_hoc }}</span>
                                </div>

                                <div class="form-group">
                                    <label>Học kỳ <span class="req">*</span></label>
                                    <select v-model="form.hoc_ky">
                                        <option :value="1">Học kỳ 1</option>
                                        <option :value="2">Học kỳ 2</option>
                                        <option :value="3">Học kỳ Hè</option>
                                    </select>
                                    <span v-if="errors.hoc_ky" class="form-error">{{ errors.hoc_ky }}</span>
                                </div>

                                <div class="form-group">
                                    <label>Ngày bắt đầu <span class="req">*</span></label>
                                    <input v-model="form.ngay_bat_dau" type="date" />
                                    <span v-if="errors.ngay_bat_dau" class="form-error">{{ errors.ngay_bat_dau }}</span>
                                </div>

                                <div class="form-group">
                                    <label>Ngày kết thúc <span class="req">*</span></label>
                                    <input v-model="form.ngay_ket_thuc" type="date" />
                                    <span v-if="errors.ngay_ket_thuc" class="form-error">{{ errors.ngay_ket_thuc }}</span>
                                </div>

                                <div class="form-group">
                                    <label>Trạng thái <span class="req">*</span></label>
                                    <select v-model="form.trang_thai">
                                        <option value="nhap">Nháp</option>
                                        <option value="dang_mo">Đang mở</option>
                                        <option value="da_dong">Đã đóng</option>
                                    </select>
                                    <span v-if="errors.trang_thai" class="form-error">{{ errors.trang_thai }}</span>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">
                                {{ isEditing ? 'Cập nhật' : 'Thêm mới' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL XÓA -->
        <div class="modal fade" id="periodDeleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5">Xóa đợt đánh giá</h1>
                            <p class="text-sm text-gray-500 mb-0">Hành động này không thể hoàn tác.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>

                    <div class="modal-body">
                        <p>
                            Bạn có chắc muốn xóa đợt đánh giá
                            <strong>
                                {{ deleteTarget?.nam_hoc }} - {{ formatHocKy(deleteTarget?.hoc_ky) }}
                            </strong>
                            không?
                        </p>
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
import { computed, reactive, ref, onMounted, watch } from "vue";
import axios from "axios";
import { createToaster } from "@meforma/vue-toaster";

import "../../../assets/css/admin.css";
import "../../../assets/css/dashboard.css";
import "../../../assets/css/user.css";

const toaster = createToaster({
    position: "top-right",
});

const API = "http://127.0.0.1:8000/api/admin/dotdanhgia";

const token =
    sessionStorage.getItem("auth_token") ||
    localStorage.getItem("auth_token") ||
    localStorage.getItem("key_admin");

const config = {
    headers: {
        Authorization: "Bearer " + token,
    },
};

const periods = ref([]);
const search = ref("");

const isEditing = ref(false);
const editTarget = ref(null);
const deleteTarget = ref(null);

const form = reactive({
    id: "",
    nam_hoc: "",
    hoc_ky: 1,
    ngay_bat_dau: "",
    ngay_ket_thuc: "",
    trang_thai: "nhap",
});

const errors = reactive({});

const filteredPeriods = computed(() => {
    return periods.value;
});

onMounted(() => {
    getData();
});

function getData() {
    axios
        .get(API, config)
        .then((res) => {
            periods.value = res.data.data;
        })
        .catch(() => {
            toaster.error("Không thể tải danh sách đợt đánh giá");
        });
}

function timKiem() {
    axios
        .post(
            API + "/tim-dotdanhgia",
            {
                noi_dung: search.value,
            },
            config
        )
        .then((res) => {
            periods.value = res.data.data;
        })
        .catch(() => {
            toaster.error("Tìm kiếm thất bại");
        });
}

watch(search, () => {
    if (search.value.trim() === "") {
        getData();
    } else {
        timKiem();
    }
});

function blankForm() {
    return {
        id: "",
        nam_hoc: "",
        hoc_ky: 1,
        ngay_bat_dau: "",
        ngay_ket_thuc: "",
        trang_thai: "nhap",
    };
}

function clearErrors() {
    Object.keys(errors).forEach((key) => delete errors[key]);
}

function clearForm() {
    Object.assign(form, blankForm());
    clearErrors();
    isEditing.value = false;
    editTarget.value = null;
}

function openAdd() {
    clearForm();
}

function openEdit(row) {
    clearForm();

    isEditing.value = true;
    editTarget.value = row;

    Object.assign(form, {
        id: row.id,
        nam_hoc: row.nam_hoc,
        hoc_ky: Number(row.hoc_ky),
        ngay_bat_dau: formatDateInput(row.ngay_bat_dau),
        ngay_ket_thuc: formatDateInput(row.ngay_ket_thuc),
        trang_thai: row.trang_thai,
    });
}

function openDelete(row) {
    deleteTarget.value = row;
}

function validate() {
    clearErrors();

    let ok = true;

    if (!String(form.nam_hoc || "").trim()) {
        errors.nam_hoc = "Vui lòng nhập năm học";
        ok = false;
    }

    if (!form.hoc_ky) {
        errors.hoc_ky = "Vui lòng chọn học kỳ";
        ok = false;
    }

    if (!form.ngay_bat_dau) {
        errors.ngay_bat_dau = "Vui lòng chọn ngày bắt đầu";
        ok = false;
    }

    if (!form.ngay_ket_thuc) {
        errors.ngay_ket_thuc = "Vui lòng chọn ngày kết thúc";
        ok = false;
    }

    if (!form.trang_thai) {
        errors.trang_thai = "Vui lòng chọn trạng thái";
        ok = false;
    }

    if (form.ngay_bat_dau && form.ngay_ket_thuc) {
        const start = new Date(form.ngay_bat_dau);
        const end = new Date(form.ngay_ket_thuc);

        if (start > end) {
            errors.ngay_ket_thuc = "Ngày kết thúc phải sau hoặc bằng ngày bắt đầu";
            ok = false;
        }
    }

    return ok;
}

function savePeriod() {
    if (!validate()) return;

    if (isEditing.value) {
        suaDotDanhGia();
    } else {
        themDotDanhGia();
    }
}

function themDotDanhGia() {
    axios
        .post(API + "/them-dotdanhgia", form, config)
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message);
                getData();
                hideModal("periodFormModal");
                clearForm();
            } else {
                toaster.error(res.data.message);
            }
        })
        .catch((err) => {
            handleError(err);
        });
}

function suaDotDanhGia() {
    axios
        .post(API + "/sua-dotdanhgia", form, config)
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message);
                getData();
                hideModal("periodFormModal");
                clearForm();
            } else {
                toaster.error(res.data.message);
            }
        })
        .catch((err) => {
            handleError(err);
        });
}

function confirmDelete() {
    if (!deleteTarget.value) return;

    axios
        .post(
            API + "/xoa-dotdanhgia",
            {
                id: deleteTarget.value.id,
            },
            config
        )
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message);
                getData();
                hideModal("periodDeleteModal");
                deleteTarget.value = null;
            } else {
                toaster.error(res.data.message);
            }
        })
        .catch(() => {
            toaster.error("Xóa thất bại");
        });
}

function khoaDotDanhGia(row) {
    axios
        .post(
            API + "/khoa-dotdanhgia",
            {
                id: row.id,
            },
            config
        )
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message);
                getData();
            } else {
                toaster.error(res.data.message);
            }
        })
        .catch(() => {
            toaster.error("Cập nhật trạng thái thất bại");
        });
}

function openPeriod(row) {
    const ok = confirm(
        `Bạn có chắc muốn mở đợt đánh giá ${row.nam_hoc} - ${formatHocKy(row.hoc_ky)} không?`
    );

    if (!ok) return;

    khoaDotDanhGia(row);
}

function closePeriod(row) {
    const ok = confirm(
        `Bạn có chắc muốn đóng đợt đánh giá ${row.nam_hoc} - ${formatHocKy(row.hoc_ky)} không?`
    );

    if (!ok) return;

    khoaDotDanhGia(row);
}

function handleError(err) {
    clearErrors();

    if (err.response && err.response.status === 422) {
        const backendErrors = err.response.data.errors;

        Object.keys(backendErrors).forEach((key) => {
            errors[key] = backendErrors[key][0];
        });

        toaster.error("Vui lòng kiểm tra lại dữ liệu");
    } else {
        toaster.error("Có lỗi xảy ra");
    }
}

function formatHocKy(value) {
    if (Number(value) === 1) return "Học kỳ 1";
    if (Number(value) === 2) return "Học kỳ 2";
    if (Number(value) === 3) return "Học kỳ Hè";

    return "—";
}

function formatTrangThai(value) {
    const map = {
        nhap: "Nháp",
        dang_mo: "Đang mở",
        da_dong: "Đã đóng",
    };

    return map[value] || "—";
}

function statusClass(value) {
    const map = {
        nhap: "st-draft",
        dang_mo: "st-open",
        da_dong: "st-closed",
    };

    return map[value] || "";
}

function formatDate(value) {
    if (!value) return "—";

    const date = formatDateInput(value);
    const [year, month, day] = String(date).split("-");

    if (!year || !month || !day) return value;

    return `${day}/${month}/${year}`;
}

function formatDateInput(value) {
    if (!value) return "";

    return String(value).slice(0, 10);
}

function hideModal(id) {
    const el = document.getElementById(id);
    const ModalCtor = window.bootstrap?.Modal;

    if (!el || !ModalCtor) return;

    const instance = ModalCtor.getInstance(el) || ModalCtor.getOrCreateInstance(el);
    instance.hide();
}
</script>

<style scoped>
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

.period-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
}

.st-draft {
    color: #92400e;
    background: #fef3c7;
}

.st-open {
    color: #166534;
    background: #dcfce7;
}

.st-closed {
    color: #991b1b;
    background: #fee2e2;
}

.icon-btn.open {
    color: #16a34a;
}

.icon-btn.close-period {
    color: #dc2626;
}
</style>