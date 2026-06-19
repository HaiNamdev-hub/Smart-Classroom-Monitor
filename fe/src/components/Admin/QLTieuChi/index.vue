<template>
    <div class="admin-content training-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Quản lý tiêu chí đánh giá</h1>
            <p>Quản lý nhóm tiêu chí, tiêu chí và lựa chọn điểm trong phiếu rèn luyện.</p>
        </div>

        <!-- TABS -->
        <div class="training-tabs">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="training-tab"
                :class="{ active: activeTab === tab.key }"
                @click="setTab(tab.key)"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- PANEL -->
        <div class="training-panel">
            <div class="toolbar flex items-center gap-4 flex-wrap w-full pb-4">
                <div class="search-box flex items-center shrink-0 flex-1 min-w-[250px]">
                    <input
                        v-model="search"
                        type="text"
                        :placeholder="currentMeta.searchPlaceholder"
                        class="outline-none bg-transparent w-full"
                    />
                </div>

                <button
                    class="btn-primary ml-auto"
                    type="button"
                    @click="openAdd"
                    data-bs-toggle="modal"
                    data-bs-target="#criteriaFormModal"
                >
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
                        <tr
                            v-for="(row, index) in filteredRows"
                            :key="row.id"
                            class="border-b hover:bg-gray-50"
                        >
                            <td class="py-3 action-cell">{{ index + 1 }}</td>

                            <td
                                v-for="col in currentMeta.columns"
                                :key="col.key"
                                class="py-3 action-cell"
                            >
                                {{ renderCell(row, col.key) }}
                            </td>

                            <td class="py-3 action-cell">
                                <div class="row-actions flex items-center gap-2 text-nowrap">
                                    <button
                                        class="icon-btn edit"
                                        title="Sửa"
                                        type="button"
                                        @click="openEdit(row)"
                                        data-bs-toggle="modal"
                                        data-bs-target="#criteriaFormModal"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button
                                        class="icon-btn del"
                                        title="Xóa"
                                        type="button"
                                        @click="openDelete(row)"
                                        data-bs-toggle="modal"
                                        data-bs-target="#criteriaDeleteModal"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="filteredRows.length === 0">
                            <td
                                :colspan="currentMeta.columns.length + 2"
                                class="empty-row text-center py-4 action-cell"
                            >
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

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Đóng"
                            ></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-grid">
                                <div
                                    v-for="field in currentMeta.fields"
                                    :key="field.key"
                                    class="form-group"
                                >
                                    <label>
                                        {{ field.label }}
                                        <span v-if="field.required" class="req">*</span>
                                    </label>

                                    <input
                                        v-if="field.type === 'text'"
                                        v-model="form[field.key]"
                                        type="text"
                                        :placeholder="field.placeholder"
                                    />

                                    <input
                                        v-else-if="field.type === 'number'"
                                        v-model.number="form[field.key]"
                                        type="number"
                                        :min="field.min ?? 0"
                                        :placeholder="field.placeholder"
                                    />

                                    <select
                                        v-else-if="field.type === 'select'"
                                        v-model="form[field.key]"
                                    >
                                        <option
                                            v-for="option in getOptions(field)"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </option>
                                    </select>

                                    <textarea
                                        v-else-if="field.type === 'textarea'"
                                        v-model="form[field.key]"
                                        :placeholder="field.placeholder"
                                    ></textarea>

                                    <label v-else-if="field.type === 'checkbox'" class="check-line">
                                        <input
                                            v-model="form[field.key]"
                                            type="checkbox"
                                        />
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

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Đóng"
                        ></button>
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
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const tabs = [
    { key: 'nhom', label: 'Nhóm tiêu chí' },
    { key: 'tieu-chi', label: 'Tiêu chí' },
    { key: 'lua-chon', label: 'Lựa chọn' },
]

const rows = reactive({
    nhom: [
        {
            id: 1,
            ma_nhom: 'I',
            ten_nhom: 'Ý thức tham gia học tập',
            diem_toi_da: 20,
            thu_tu: 1,
        },
        {
            id: 2,
            ma_nhom: 'II',
            ten_nhom: 'Chấp hành nội quy, quy chế và các quy định khác',
            diem_toi_da: 25,
            thu_tu: 2,
        },
        {
            id: 3,
            ma_nhom: 'III',
            ten_nhom: 'Hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao',
            diem_toi_da: 20,
            thu_tu: 3,
        },
        {
            id: 4,
            ma_nhom: 'IV',
            ten_nhom: 'Ý thức công dân trong quan hệ cộng đồng',
            diem_toi_da: 25,
            thu_tu: 4,
        },
        {
            id: 5,
            ma_nhom: 'V',
            ten_nhom: 'Ban cán sự lớp, BCH Đoàn, CLB, Đội, Hội, Nhóm',
            diem_toi_da: 10,
            thu_tu: 5,
        },
    ],

    'tieu-chi': [
        {
            id: 1,
            nhom_id: 1,
            parent_id: null,
            ma_tieu_chi: 'I.1',
            ten_tieu_chi: 'Ý thức và thái độ học tập',
            diem_toi_da: 6,
            loai_tieu_chi: 'cong',
            loai_nhap: 'combobox',
            bat_buoc_minh_chung: false,
            thu_tu: 1,
        },
        {
            id: 2,
            nhom_id: 1,
            parent_id: null,
            ma_tieu_chi: 'I.2',
            ten_tieu_chi: 'Ý thức và thái độ tham gia hoạt động học thuật, NCKH, thi Olympic',
            diem_toi_da: 6,
            loai_tieu_chi: 'cong',
            loai_nhap: 'none',
            bat_buoc_minh_chung: false,
            thu_tu: 2,
        },
        {
            id: 3,
            nhom_id: 1,
            parent_id: 2,
            ma_tieu_chi: 'I.2.a',
            ten_tieu_chi: 'Tham gia đầy đủ hoạt động, sự kiện liên quan đến NCKH, học thuật, chuyên môn',
            diem_toi_da: 2,
            loai_tieu_chi: 'cong',
            loai_nhap: 'checkbox',
            bat_buoc_minh_chung: true,
            thu_tu: 1,
        },
        {
            id: 4,
            nhom_id: 1,
            parent_id: 2,
            ma_tieu_chi: 'I.2.b',
            ten_tieu_chi: 'Có công bố khoa học hoặc tham gia cuộc thi SV NCKH, Olympic',
            diem_toi_da: 2,
            loai_tieu_chi: 'cong',
            loai_nhap: 'checkbox',
            bat_buoc_minh_chung: true,
            thu_tu: 2,
        },
        {
            id: 5,
            nhom_id: 1,
            parent_id: null,
            ma_tieu_chi: 'I.3',
            ten_tieu_chi: 'Xếp loại học tập học kỳ',
            diem_toi_da: 8,
            loai_tieu_chi: 'cong',
            loai_nhap: 'combobox',
            bat_buoc_minh_chung: false,
            thu_tu: 3,
        },
        {
            id: 6,
            nhom_id: 2,
            parent_id: null,
            ma_tieu_chi: 'II.1',
            ten_tieu_chi: 'Chấp hành tốt, không vi phạm',
            diem_toi_da: 25,
            loai_tieu_chi: 'cong',
            loai_nhap: 'textbox',
            bat_buoc_minh_chung: false,
            thu_tu: 1,
        },
    ],

    'lua-chon': [
        {
            id: 1,
            tieu_chi_id: 1,
            noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ ≥ 9',
            diem_tuong_ung: 6,
            thu_tu: 1,
        },
        {
            id: 2,
            tieu_chi_id: 1,
            noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ từ 7 đến cận 9',
            diem_tuong_ung: 5,
            thu_tu: 2,
        },
        {
            id: 3,
            tieu_chi_id: 1,
            noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ từ 5 đến cận 7',
            diem_tuong_ung: 4,
            thu_tu: 3,
        },
        {
            id: 4,
            tieu_chi_id: 5,
            noi_dung: 'Loại xuất sắc',
            diem_tuong_ung: 8,
            thu_tu: 1,
        },
        {
            id: 5,
            tieu_chi_id: 5,
            noi_dung: 'Loại giỏi',
            diem_tuong_ung: 7,
            thu_tu: 2,
        },
        {
            id: 6,
            tieu_chi_id: 5,
            noi_dung: 'Loại khá',
            diem_tuong_ung: 6,
            thu_tu: 3,
        },
    ],
})

const route = useRoute()
const router = useRouter()

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

const tieuChiComboboxOptions = computed(() => [
    { value: '', label: 'Chọn tiêu chí' },
    ...rows['tieu-chi']
        .filter((item) => item.loai_nhap === 'combobox')
        .map((item) => ({
            value: item.id,
            label: `${item.ma_tieu_chi} - ${shortText(item.ten_tieu_chi, 60)}`,
        })),
])

const metaMap = {
    nhom: {
        addLabel: 'nhóm tiêu chí',
        description: 'Quản lý 5 nhóm tiêu chí lớn trong phiếu đánh giá rèn luyện.',
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
        description: 'Quản lý các lựa chọn điểm cho tiêu chí dạng combobox.',
        searchPlaceholder: 'Tìm theo tiêu chí, nội dung lựa chọn hoặc điểm...',
        columns: [
            { key: 'tieu_chi_id', label: 'Tiêu chí' },
            { key: 'noi_dung', label: 'Nội dung lựa chọn' },
            { key: 'diem_tuong_ung', label: 'Điểm tương ứng' },
            { key: 'thu_tu', label: 'Thứ tự' },
        ],
        fields: [
            { key: 'tieu_chi_id', label: 'Tiêu chí', type: 'select', required: true, options: () => tieuChiComboboxOptions.value },
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
    const q = search.value.trim().toLowerCase()
    const list = rows[activeTab.value] || []

    if (!q) return list

    return list.filter((row) => {
        const text = currentMeta.value.columns
            .map((col) => renderCell(row, col.key))
            .join(' ')
            .toLowerCase()

        return text.includes(q)
    })
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

function setTab(key) {
    activeTab.value = key
    search.value = ''
    clearForm()
    router.replace({ query: { ...route.query, tab: key } })
}

function getOptions(field) {
    if (!field.options) return []
    return typeof field.options === 'function' ? field.options() : field.options
}

function renderCell(row, key) {
    if (key === 'nhom_id') return getNhomName(row[key])
    if (key === 'parent_id') return getParentName(row[key])
    if (key === 'tieu_chi_id') return getTieuChiName(row[key])
    if (key === 'loai_tieu_chi') return formatLoaiTieuChi(row[key])
    if (key === 'loai_nhap') return formatLoaiNhap(row[key])
    if (key === 'bat_buoc_minh_chung') return row[key] ? 'Bắt buộc' : 'Không'

    return row[key] ?? '—'
}

function getNhomName(id) {
    const item = rows.nhom.find((row) => Number(row.id) === Number(id))
    return item ? `${item.ma_nhom} - ${item.ten_nhom}` : '—'
}

function getParentName(id) {
    if (!id) return '—'

    const item = rows['tieu-chi'].find((row) => Number(row.id) === Number(id))
    return item ? item.ma_tieu_chi : '—'
}

function getTieuChiName(id) {
    const item = rows['tieu-chi'].find((row) => Number(row.id) === Number(id))
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

    if (activeTab.value === 'nhom') {
        const duplicated = rows.nhom.some((row) => {
            const sameId = isEditing.value && editTarget.value && row.id === editTarget.value.id

            return !sameId && String(row.ma_nhom).trim().toLowerCase() === String(form.ma_nhom).trim().toLowerCase()
        })

        if (duplicated) {
            errors.ma_nhom = 'Mã nhóm đã tồn tại'
            ok = false
        }
    }

    if (activeTab.value === 'tieu-chi') {
        const duplicated = rows['tieu-chi'].some((row) => {
            const sameId = isEditing.value && editTarget.value && row.id === editTarget.value.id

            return !sameId
                && Number(row.nhom_id) === Number(form.nhom_id)
                && String(row.ma_tieu_chi).trim().toLowerCase() === String(form.ma_tieu_chi).trim().toLowerCase()
        })

        if (duplicated) {
            errors.ma_tieu_chi = 'Mã tiêu chí trong nhóm này đã tồn tại'
            ok = false
        }

        if (form.parent_id && Number(form.parent_id) === Number(editTarget.value?.id)) {
            errors.parent_id = 'Tiêu chí cha không được là chính nó'
            ok = false
        }
    }

    if (activeTab.value === 'lua-chon') {
        const duplicated = rows['lua-chon'].some((row) => {
            const sameId = isEditing.value && editTarget.value && row.id === editTarget.value.id

            return !sameId
                && Number(row.tieu_chi_id) === Number(form.tieu_chi_id)
                && String(row.noi_dung).trim().toLowerCase() === String(form.noi_dung).trim().toLowerCase()
        })

        if (duplicated) {
            errors.noi_dung = 'Lựa chọn này đã tồn tại trong tiêu chí'
            ok = false
        }
    }

    return ok
}

function saveRow() {
    if (!validate()) return

    const key = activeTab.value
    const data = normalizeFormData(JSON.parse(JSON.stringify(form)))

    if (isEditing.value && editTarget.value) {
        Object.assign(editTarget.value, data)
    } else {
        rows[key].unshift({
            id: Date.now(),
            ...data,
        })
    }

    hideModal('criteriaFormModal')
    clearForm()
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
        data.bat_buoc_minh_chung = Boolean(data.bat_buoc_minh_chung)
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

    rows[activeTab.value] = rows[activeTab.value].filter((row) => row.id !== deleteTarget.value.id)

    deleteTarget.value = null
    hideModal('criteriaDeleteModal')
}

function hideModal(id) {
    const el = document.getElementById(id)
    const ModalCtor = window.bootstrap?.Modal

    if (!el || !ModalCtor) return

    const instance = ModalCtor.getInstance(el) || ModalCtor.getOrCreateInstance(el)
    instance.hide()
}

watch(
    () => route.query.tab,
    (value) => {
        const nextTab = typeof value === 'string' && metaMap[value] ? value : 'nhom'
        activeTab.value = nextTab
    },
    { immediate: true }
)
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