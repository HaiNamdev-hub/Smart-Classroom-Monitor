<template>
    <div class="admin-content training-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Quản lý tiêu chí đánh giá</h1>
            <p>Quản lý nhóm tiêu chí, tiêu chí và lựa chọn điểm trong phiếu rèn luyện.</p>
        </div>

        <!-- TABS -->
        <div class="training-tabs">
            <button v-for="tab in tabs" :key="tab.key" type="button" class="training-tab"
                :class="{ active: activeTab === tab.key }" @click="setTab(tab.key)">
                {{ tab.label }}
            </button>
        </div>

        <!-- PANEL -->
        <div class="training-panel">
            <div class="toolbar flex items-center gap-4 flex-wrap w-full pb-4">
                <div class="search-box flex items-center shrink-0 flex-1 min-w-[250px]">
                    <input v-model="search" type="text" :placeholder="currentMeta.searchPlaceholder"
                        class="outline-none bg-transparent w-full" />
                </div>

                <button class="btn-primary ml-auto" type="button" @click="openAdd" data-bs-toggle="modal"
                    data-bs-target="#criteriaFormModal">
                    Thêm {{ currentMeta.addLabel }}
                </button>
            </div>

            <!-- TABLE -->
            <div class="table-wrap">
                <table class="data-table w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b">
                            <th style="width:48px">STT</th>
                            <th v-for="col in currentMeta.columns" :key="col.key">
                                {{ col.label }}
                            </th>
                            <th style="width:180px">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="(row, index) in filteredRows" :key="row.id" class="border-b hover:bg-gray-50">
                            <td class="py-3 action-cell">{{ index + 1 }}</td>

                            <td v-for="col in currentMeta.columns" :key="col.key" class="py-3 action-cell">
                                {{ renderCell(row, col.key) }}
                            </td>

                            <td class="py-3 action-cell">
                                <div class="row-actions flex items-center gap-2 text-nowrap">
                                    <button class="icon-btn edit" title="Sửa" type="button" @click="openEdit(row)"
                                        data-bs-toggle="modal" data-bs-target="#criteriaFormModal">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button class="icon-btn del" title="Xóa" type="button" @click="openDelete(row)"
                                        data-bs-toggle="modal" data-bs-target="#criteriaDeleteModal">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredRows.length === 0">
                            <td :colspan="currentMeta.columns.length + 2"
                                class="empty-row text-center py-4 action-cell">
                                Không tìm thấy dữ liệu phù hợp.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL FORM THÊM/SỬA -->
        <div class="modal fade" id="criteriaFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form @submit.prevent="saveRow">
                        <div class="modal-header">
                            <div>
                                <h1 class="modal-title fs-5">
                                    {{ isEditing ? 'Sửa' : 'Thêm' }} {{ currentMeta.addLabel }}
                                </h1>
                                <p class="text-sm text-gray-500 mb-0">
                                    {{ currentMeta.description }}
                                </p>
                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-grid">
                                <div v-for="field in currentMeta.fields" :key="field.key" class="form-group">
                                    <label>
                                        {{ field.label }}
                                        <span v-if="field.required" class="req">*</span>
                                    </label>

                                    <input v-if="field.type === 'text'" v-model="form[field.key]" type="text"
                                        :placeholder="field.placeholder" />

                                    <input v-else-if="field.type === 'number'" v-model.number="form[field.key]"
                                        type="number" :min="field.min ?? 0" :placeholder="field.placeholder" />

                                    <select v-else-if="field.type === 'select'" v-model="form[field.key]">
                                        <option v-for="option in getOptions(field)" :key="option.value"
                                            :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>

                                    <textarea v-else-if="field.type === 'textarea'" v-model="form[field.key]"
                                        :placeholder="field.placeholder"></textarea>

                                    <label v-else-if="field.type === 'checkbox'" class="check-line">
                                        <input v-model="form[field.key]" type="checkbox" />
                                        <span>{{ field.checkLabel || field.label }}</span>
                                    </label>

                                    <span v-if="errors[field.key]" class="form-error">
                                        {{ errors[field.key] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Hủy
                            </button>
                            <button type="submit" class="btn btn-primary">
                                {{ isEditing ? 'Cập nhật' : 'Thêm mới' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL XÓA -->
        <div class="modal fade" id="criteriaDeleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5">Xóa {{ currentMeta.addLabel }}</h1>
                            <p class="text-sm text-gray-500 mb-0">Hành động này không thể hoàn tác.</p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>

                    <div class="modal-body">
                        <p>
                            Bạn có chắc muốn xóa
                            <strong>{{ deleteName }}</strong>
                            không?
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Hủy
                        </button>
                        <button type="button" class="btn btn-primary" @click="confirmDelete">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { computed, reactive, ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { createToaster } from '@meforma/vue-toaster'

import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const toaster = createToaster({
    position: 'top-right',
})

const route = useRoute()
const router = useRouter()

const API = 'http://localhost:8000/api/admin/quanlytieuchi'

const config = {
    headers: {
        Authorization: 'Bearer ' + sessionStorage.getItem('auth_token'),
    },
}

const tabs = [
    { key: 'nhom', label: 'Nhóm tiêu chí' },
    { key: 'tieu-chi', label: 'Tiêu chí' },
    { key: 'lua-chon', label: 'Lựa chọn' },
]

const rows = reactive({
    nhom: [],
    'tieu-chi': [],
    'lua-chon': [],
})

const activeTab = ref('nhom')
const search = ref('')
const form = reactive({})
const errors = reactive({})
const isEditing = ref(false)
const editTarget = ref(null)
const deleteTarget = ref(null)

const nhomOptions = computed(() => [
    { value: '', label: 'Chọn nhóm tiêu chí' },
    ...rows.nhom.map((item) => ({
        value: item.id,
        label: `${item.ma_nhom} - ${item.ten_nhom}`,
    })),
])

const parentOptions = computed(() => [
    { value: null, label: 'Không có tiêu chí cha' },
    ...rows['tieu-chi'].map((item) => ({
        value: item.id,
        label: `${item.ma_tieu_chi} - ${shortText(item.ten_tieu_chi, 60)}`,
    })),
])

const tieuChiOptions = computed(() => [
    { value: '', label: 'Chọn tiêu chí' },
    ...rows['tieu-chi'].map((item) => ({
        value: item.id,
        label: `${item.ma_tieu_chi} - ${shortText(item.ten_tieu_chi, 60)}`,
    })),
])

const metaMap = {
    nhom: {
        addLabel: 'nhóm tiêu chí',
        description: 'Quản lý nhóm tiêu chí lớn trong phiếu đánh giá rèn luyện.',
        searchPlaceholder: 'Tìm theo mã nhóm hoặc tên nhóm...',
        columns: [
            { key: 'ma_nhom', label: 'Mã nhóm' },
            { key: 'ten_nhom', label: 'Tên nhóm' },
            { key: 'diem_toi_da', label: 'Điểm tối đa' },
            { key: 'thu_tu', label: 'Thứ tự' },
        ],
        fields: [
            { key: 'ma_nhom', label: 'Mã nhóm', type: 'text', required: true, placeholder: 'VD: I' },
            { key: 'ten_nhom', label: 'Tên nhóm', type: 'textarea', required: true, placeholder: 'VD: Ý thức tham gia học tập' },
            { key: 'diem_toi_da', label: 'Điểm tối đa', type: 'number', required: true, min: 0, placeholder: 'VD: 20' },
            { key: 'thu_tu', label: 'Thứ tự', type: 'number', required: true, min: 1, placeholder: 'VD: 1' },
        ],
        blank: () => ({
            ma_nhom: '',
            ten_nhom: '',
            diem_toi_da: 0,
            thu_tu: 1,
        }),
    },

    'tieu-chi': {
        addLabel: 'tiêu chí',
        description: 'Quản lý từng tiêu chí con trong phiếu đánh giá rèn luyện.',
        searchPlaceholder: 'Tìm theo mã tiêu chí, tên tiêu chí, nhóm...',
        columns: [
            { key: 'nhom_id', label: 'Nhóm' },
            { key: 'parent_id', label: 'Tiêu chí cha' },
            { key: 'ma_tieu_chi', label: 'Mã tiêu chí' },
            { key: 'ten_tieu_chi', label: 'Tên tiêu chí' },
            { key: 'diem_toi_da', label: 'Điểm tối đa' },
            { key: 'loai_tieu_chi', label: 'Loại' },
            { key: 'loai_nhap', label: 'Loại nhập' },
            { key: 'bat_buoc_minh_chung', label: 'Minh chứng' },
            { key: 'thu_tu', label: 'Thứ tự' },
        ],
        fields: [
            { key: 'nhom_id', label: 'Nhóm tiêu chí', type: 'select', required: true, options: () => nhomOptions.value },
            { key: 'parent_id', label: 'Tiêu chí cha', type: 'select', required: false, options: () => parentOptions.value },
            { key: 'ma_tieu_chi', label: 'Mã tiêu chí', type: 'text', required: true, placeholder: 'VD: I.1' },
            { key: 'ten_tieu_chi', label: 'Tên tiêu chí', type: 'textarea', required: true, placeholder: 'Nhập nội dung tiêu chí...' },
            { key: 'diem_toi_da', label: 'Điểm tối đa', type: 'number', required: true, min: 0, placeholder: 'VD: 6' },
            {
                key: 'loai_tieu_chi',
                label: 'Loại tiêu chí',
                type: 'select',
                required: true,
                options: [
                    { value: 'cong', label: 'Cộng điểm' },
                    { value: 'tru', label: 'Trừ điểm' },
                ],
            },
            {
                key: 'loai_nhap',
                label: 'Loại nhập',
                type: 'select',
                required: true,
                options: [
                    { value: 'none', label: 'Không nhập' },
                    { value: 'textbox', label: 'Textbox' },
                    { value: 'checkbox', label: 'Checkbox' },
                    { value: 'combobox', label: 'Combobox' },
                ],
            },
            {
                key: 'bat_buoc_minh_chung',
                label: 'Bắt buộc minh chứng',
                type: 'checkbox',
                required: false,
                checkLabel: 'Yêu cầu sinh viên tải file minh chứng',
            },
            { key: 'thu_tu', label: 'Thứ tự', type: 'number', required: true, min: 1, placeholder: 'VD: 1' },
        ],
        blank: () => ({
            nhom_id: '',
            parent_id: null,
            ma_tieu_chi: '',
            ten_tieu_chi: '',
            diem_toi_da: 0,
            loai_tieu_chi: 'cong',
            loai_nhap: 'none',
            bat_buoc_minh_chung: false,
            thu_tu: 1,
        }),
    },

    'lua-chon': {
        addLabel: 'lựa chọn',
        description: 'Quản lý các lựa chọn điểm cho tiêu chí.',
        searchPlaceholder: 'Tìm theo tiêu chí, nội dung lựa chọn hoặc điểm...',
        columns: [
            { key: 'tieu_chi_id', label: 'Tiêu chí' },
            { key: 'noi_dung', label: 'Nội dung lựa chọn' },
            { key: 'diem_tuong_ung', label: 'Điểm tương ứng' },
            { key: 'thu_tu', label: 'Thứ tự' },
        ],
        fields: [
            { key: 'tieu_chi_id', label: 'Tiêu chí', type: 'select', required: true, options: () => tieuChiOptions.value },
            { key: 'noi_dung', label: 'Nội dung lựa chọn', type: 'textarea', required: true, placeholder: 'VD: Điểm TB >= 9' },
            { key: 'diem_tuong_ung', label: 'Điểm tương ứng', type: 'number', required: true, min: 0, placeholder: 'VD: 6' },
            { key: 'thu_tu', label: 'Thứ tự', type: 'number', required: true, min: 1, placeholder: 'VD: 1' },
        ],
        blank: () => ({
            tieu_chi_id: '',
            noi_dung: '',
            diem_tuong_ung: 0,
            thu_tu: 1,
        }),
    },
}

const currentMeta = computed(() => metaMap[activeTab.value])

const filteredRows = computed(() => {
    return rows[activeTab.value] || []
})

const deleteName = computed(() => {
    if (!deleteTarget.value) return ''

    if (activeTab.value === 'nhom') {
        return `${deleteTarget.value.ma_nhom} - ${deleteTarget.value.ten_nhom}`
    }

    if (activeTab.value === 'tieu-chi') {
        return `${deleteTarget.value.ma_tieu_chi} - ${shortText(deleteTarget.value.ten_tieu_chi, 80)}`
    }

    if (activeTab.value === 'lua-chon') {
        return shortText(deleteTarget.value.noi_dung, 80)
    }

    return ''
})

onMounted(() => {
    getAllData()
    clearForm()
})

function getAllData() {
    getNhomTieuChi()
    getTieuChi()
    getLuaChon()
}

function getNhomTieuChi() {
    axios.get(API + '/nhomtieuchi', config)
        .then((res) => {
            rows.nhom = res.data.data || []
        })
        .catch((err) => handleError(err, 'Không thể tải nhóm tiêu chí'))
}

function getTieuChi() {
    axios.get(API + '/tieuchi', config)
        .then((res) => {
            rows['tieu-chi'] = res.data.data || []
        })
        .catch((err) => handleError(err, 'Không thể tải tiêu chí'))
}

function getLuaChon() {
    axios.get(API + '/luachon', config)
        .then((res) => {
            rows['lua-chon'] = res.data.data || []
        })
        .catch((err) => handleError(err, 'Không thể tải lựa chọn'))
}

function setTab(key) {
    activeTab.value = key
    search.value = ''
    clearForm()

    router.replace({ query: { ...route.query, tab: key } })

    if (key === 'nhom') getNhomTieuChi()
    if (key === 'tieu-chi') getTieuChi()
    if (key === 'lua-chon') getLuaChon()
}

function timKiem() {
    const q = search.value.trim()

    if (q === '') {
        if (activeTab.value === 'nhom') getNhomTieuChi()
        if (activeTab.value === 'tieu-chi') getTieuChi()
        if (activeTab.value === 'lua-chon') getLuaChon()
        return
    }

    let url = ''

    if (activeTab.value === 'nhom') url = '/tim-nhomtieuchi'
    if (activeTab.value === 'tieu-chi') url = '/tim-tieuchi'
    if (activeTab.value === 'lua-chon') url = '/tim-luachon'

    axios.post(API + url, { noi_dung: q }, config)
        .then((res) => {
            rows[activeTab.value] = res.data.data || []
        })
        .catch((err) => handleError(err, 'Tìm kiếm thất bại'))
}

watch(search, () => {
    timKiem()
})

watch(
    () => route.query.tab,
    (value) => {
        const nextTab = typeof value === 'string' && metaMap[value] ? value : 'nhom'
        activeTab.value = nextTab
    },
    { immediate: true }
)

function getOptions(field) {
    if (!field.options) return []
    return typeof field.options === 'function' ? field.options() : field.options
}

function renderCell(row, key) {
    if (key === 'nhom_id') return getNhomName(row)
    if (key === 'parent_id') return getParentName(row[key])
    if (key === 'tieu_chi_id') return getTieuChiName(row)
    if (key === 'loai_tieu_chi') return formatLoaiTieuChi(row[key])
    if (key === 'loai_nhap') return formatLoaiNhap(row[key])
    if (key === 'bat_buoc_minh_chung') return Number(row[key]) === 1 || row[key] === true ? 'Bắt buộc' : 'Không'

    return row[key] ?? '—'
}

function getNhomName(row) {
    if (row.ma_nhom && row.ten_nhom) {
        return `${row.ma_nhom} - ${row.ten_nhom}`
    }

    const item = rows.nhom.find((x) => Number(x.id) === Number(row.nhom_id))
    return item ? `${item.ma_nhom} - ${item.ten_nhom}` : '—'
}

function getParentName(id) {
    if (!id) return '—'

    const item = rows['tieu-chi'].find((row) => Number(row.id) === Number(id))
    return item ? item.ma_tieu_chi : '—'
}

function getTieuChiName(row) {
    if (row.ma_tieu_chi && row.ten_tieu_chi) {
        return `${row.ma_tieu_chi} - ${shortText(row.ten_tieu_chi, 60)}`
    }

    const item = rows['tieu-chi'].find((x) => Number(x.id) === Number(row.tieu_chi_id))
    return item ? `${item.ma_tieu_chi} - ${shortText(item.ten_tieu_chi, 60)}` : '—'
}

function formatLoaiTieuChi(value) {
    const map = {
        cong: 'Cộng điểm',
        tru: 'Trừ điểm',
    }

    return map[value] || '—'
}

function formatLoaiNhap(value) {
    const map = {
        none: 'Không nhập',
        textbox: 'Textbox',
        checkbox: 'Checkbox',
        combobox: 'Combobox',
    }

    return map[value] || '—'
}

function shortText(value, max = 80) {
    const text = String(value || '')
    return text.length > max ? `${text.slice(0, max)}...` : text
}

function clearForm() {
    Object.keys(form).forEach((key) => delete form[key])
    Object.assign(form, currentMeta.value.blank())

    Object.keys(errors).forEach((key) => delete errors[key])

    isEditing.value = false
    editTarget.value = null
}

function openAdd() {
    clearForm()
}

function openEdit(row) {
    clearForm()
    isEditing.value = true
    editTarget.value = row

    Object.assign(form, JSON.parse(JSON.stringify(row)))

    if (activeTab.value === 'tieu-chi') {
        form.nhom_id = Number(form.nhom_id)
        form.parent_id = form.parent_id ? Number(form.parent_id) : null
        form.bat_buoc_minh_chung = Number(form.bat_buoc_minh_chung) === 1 || form.bat_buoc_minh_chung === true
    }

    if (activeTab.value === 'lua-chon') {
        form.tieu_chi_id = Number(form.tieu_chi_id)
    }
}

function openDelete(row) {
    deleteTarget.value = row
}

function validate() {
    Object.keys(errors).forEach((key) => delete errors[key])

    let ok = true

    for (const field of currentMeta.value.fields) {
        const value = form[field.key]

        if (field.required && (value === '' || value === null || value === undefined)) {
            errors[field.key] = `Vui lòng nhập ${field.label.toLowerCase()}`
            ok = false
        }
    }

    if (activeTab.value === 'tieu-chi') {
        if (form.parent_id && editTarget.value && Number(form.parent_id) === Number(editTarget.value.id)) {
            errors.parent_id = 'Tiêu chí cha không được là chính nó'
            ok = false
        }
    }

    return ok
}

function saveRow() {
    if (!validate()) return

    const payload = normalizeFormData(JSON.parse(JSON.stringify(form)))

    let url = ''

    if (activeTab.value === 'nhom') {
        url = isEditing.value ? '/sua-nhomtieuchi' : '/them-nhomtieuchi'
    }

    if (activeTab.value === 'tieu-chi') {
        url = isEditing.value ? '/sua-tieuchi' : '/them-tieuchi'
    }

    if (activeTab.value === 'lua-chon') {
        url = isEditing.value ? '/sua-luachon' : '/them-luachon'
    }

    axios.post(API + url, payload, config)
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message || 'Lưu thành công')
                reloadCurrentTab()
                getAllData()
                hideModal('criteriaFormModal')
                clearForm()
            } else {
                toaster.error(res.data.message || 'Thao tác thất bại')
            }
        })
        .catch((err) => handleError(err, 'Lưu thất bại'))
}

function normalizeFormData(data) {
    if (activeTab.value === 'nhom') {
        data.diem_toi_da = Number(data.diem_toi_da || 0)
        data.thu_tu = Number(data.thu_tu || 1)
    }

    if (activeTab.value === 'tieu-chi') {
        data.nhom_id = Number(data.nhom_id)
        data.parent_id = data.parent_id ? Number(data.parent_id) : null
        data.diem_toi_da = Number(data.diem_toi_da || 0)
        data.thu_tu = Number(data.thu_tu || 1)
        data.bat_buoc_minh_chung = data.bat_buoc_minh_chung ? 1 : 0
    }

    if (activeTab.value === 'lua-chon') {
        data.tieu_chi_id = Number(data.tieu_chi_id)
        data.diem_tuong_ung = Number(data.diem_tuong_ung || 0)
        data.thu_tu = Number(data.thu_tu || 1)
    }

    return data
}

function confirmDelete() {
    if (!deleteTarget.value) return

    let url = ''

    if (activeTab.value === 'nhom') url = '/xoa-nhomtieuchi'
    if (activeTab.value === 'tieu-chi') url = '/xoa-tieuchi'
    if (activeTab.value === 'lua-chon') url = '/xoa-luachon'

    axios.post(API + url, { id: deleteTarget.value.id }, config)
        .then((res) => {
            if (res.data.status) {
                toaster.success(res.data.message || 'Xóa thành công')
                reloadCurrentTab()
                getAllData()
                hideModal('criteriaDeleteModal')
                deleteTarget.value = null
            } else {
                toaster.error(res.data.message || 'Xóa thất bại')
            }
        })
        .catch((err) => handleError(err, 'Xóa thất bại'))
}

function reloadCurrentTab() {
    if (activeTab.value === 'nhom') getNhomTieuChi()
    if (activeTab.value === 'tieu-chi') getTieuChi()
    if (activeTab.value === 'lua-chon') getLuaChon()
}

function hideModal(id) {
    const el = document.getElementById(id)
    const ModalCtor = window.bootstrap?.Modal

    if (!el || !ModalCtor) return

    const instance = ModalCtor.getInstance(el) || ModalCtor.getOrCreateInstance(el)
    instance.hide()
}

function handleError(err, defaultMessage) {
    console.log(defaultMessage, err.response?.data || err)

    if (err.response?.data?.errors) {
        const backendErrors = err.response.data.errors
        const firstKey = Object.keys(backendErrors)[0]
        const firstMessage = backendErrors[firstKey][0]

        toaster.error(firstMessage || 'Dữ liệu không hợp lệ')
        return
    }

    toaster.error(err.response?.data?.message || defaultMessage)
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

.check-line {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
    font-weight: 500;
    color: #334155;
}

.check-line input {
    width: 16px;
    height: 16px;
}
</style>