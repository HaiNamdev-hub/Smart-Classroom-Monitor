<template>
    <div class="admin-content training-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Quản lý đào tạo</h1>
            <p>Quản lý khoa, ngành, lớp và danh sách lớp.</p>
        </div>

        <!-- TOOLBAR -->
        <div class="training-tabs">
            <button v-for="tab in tabs" :key="tab.key" type="button" class="training-tab"
                :class="{ active: activeTab === tab.key }" @click="setTab(tab.key)">
                {{ tab.label }}
            </button>
        </div>

        <!-- TABLE -->
        <div class="training-panel">
            <div class="toolbar flex items-center gap-4 flex-wrap w-full pb-4">
                <div class="search-box flex items-center shrink-0 flex-1 min-w-[250px]">
                    <!-- INPUT TÌM KIẾM -->
                    <input v-model="search" type="text" :placeholder="searchPlaceholder"
                        class="outline-none bg-transparent w-full" />
                </div>

                <!-- BUTTON THÊM FILE DANH SÁCH LỚP -->
                <button v-if="activeTab === 'danh-sach-lop'" class="btn-primary ml-auto" type="button"
                    @click="openImport(null)" data-bs-toggle="modal" data-bs-target="#importClassListModal">
                    Tải file Excel/Word
                </button>

                <!-- BUTTON THÊM KHOA, NGÀNH, LỚP -->
                <button v-else class="btn-primary ml-auto" type="button" @click="openAdd" data-bs-toggle="modal"
                    data-bs-target="#trainingFormModal">
                    Thêm {{ currentMeta.addLabel }}
                </button>
            </div>

            <!-- TABLE -->
            <div class="table-wrap">
                <table class="data-table w-full text-left border-collapse">

                    <!-- TABLE HEAD -->
                    <thead>
                        <tr class="border-b">
                            <th style="width:48px">STT</th>
                            <th v-for="col in currentMeta.columns" :key="col.key">{{ col.label }}</th>
                            <th style="width:220px">Thao tác</th>
                        </tr>
                    </thead>

                    <!-- TABLE BODY -->
                    <tbody>
                        <tr v-for="(row, index) in filteredRows" :key="row.id" class="border-b hover:bg-gray-50">
                            <td class="py-3 action-cell">{{ index + 1 }}</td>
                            <td v-for="col in currentMeta.columns" :key="col.key" class="py-3 action-cell">
                                {{ renderCell(row, col.key) }}
                            </td>
                            <td class="py-3 action-cell">
                                <div v-if="activeTab === 'danh-sach-lop'"
                                    class="row-actions flex items-center gap-2 text-nowrap">
                                    <button class="icon-btn view" title="Xem sinh viên" type="button"
                                        @click="openStudents(row)" data-bs-toggle="modal"
                                        data-bs-target="#studentListModal">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <button class="icon-btn upload" title="Tải lại file" type="button"
                                        @click="openImport(row)" data-bs-toggle="modal"
                                        data-bs-target="#importClassListModal">
                                        <i class="fa-solid fa-upload"></i>
                                    </button>

                                    <button class="icon-btn export" title="Xuất Excel" type="button"
                                        @click="exportExcel(row)">
                                        <i class="fa-solid fa-download"></i>
                                    </button>

                                    <button class="icon-btn del" title="Xóa" type="button" @click="openDelete(row)"
                                        data-bs-toggle="modal" data-bs-target="#trainingDeleteModal">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>

                                <div v-else class="row-actions flex items-center gap-2 text-nowrap">
                                    <button class="icon-btn edit" title="Sửa" type="button" @click="openEdit(row)"
                                        data-bs-toggle="modal" data-bs-target="#trainingFormModal">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <button class="icon-btn del" title="Xóa" type="button" @click="openDelete(row)"
                                        data-bs-toggle="modal" data-bs-target="#trainingDeleteModal">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="filteredRows.length === 0">
                            <td :colspan="currentMeta.columns.length + 2"
                                class="empty-row text-center py-4 action-cell">Không tìm
                                thấy dữ liệu phù hợp.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL FORM THÊM SỬA CHUYÊN NGÀNH  -->
        <div class="modal fade" id="trainingFormModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form @submit.prevent="saveRow">
                        <div class="modal-header">
                            <div>
                                <h1 class="modal-title fs-5">{{ isEditing ? 'Sửa' : 'Thêm' }} {{ currentMeta.addLabel }}
                                </h1>
                                <p class="text-sm text-gray-500 mb-0">{{ currentMeta.description }}</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-grid">
                                <div v-for="field in currentMeta.fields" :key="field.key" class="form-group">
                                    <label>{{ field.label }} <span v-if="field.required" class="req">*</span></label>
                                    <input v-if="field.type === 'text'" v-model="form[field.key]" type="text"
                                        :placeholder="field.placeholder" />
                                    <select v-else-if="field.type === 'select'" v-model="form[field.key]">
                                        <option v-for="option in field.options" :key="option.value"
                                            :value="option.value">{{ option.label }}</option>
                                    </select>
                                    <textarea v-else-if="field.type === 'textarea'" v-model="form[field.key]"
                                        :placeholder="field.placeholder"></textarea>
                                    <span v-if="errors[field.key]" class="form-error">{{ errors[field.key] }}</span>
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

        <!-- MODAL XOÁ CHUYÊN NGÀNH  -->
        <div class="modal fade" id="trainingDeleteModal" tabindex="-1" aria-hidden="true">
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
                        <p>Bạn có chắc muốn xóa <strong>{{ deleteTarget?.name || deleteTarget?.ten_khoa ||
                            deleteTarget?.ten_nganh || deleteTarget?.ten_lop }}</strong> không?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-primary" @click="confirmDelete">Xóa</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL IMPORT CLASS LIST -->
        <div class="modal fade" id="importClassListModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form @submit.prevent="submitImport">
                        <div class="modal-header">
                            <div>
                                <h1 class="modal-title fs-5">Import danh sách lớp</h1>
                                <p class="text-sm text-gray-500 mb-0">Chọn lớp và tải file Excel/Word theo mẫu.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label>Khoa <span class="req">*</span></label>
                                <select v-model="importForm.khoa">
                                    <option value="">Chọn khoa</option>
                                    <option v-for="k in rows.khoa" :key="k.id" :value="k.ten_khoa">
                                        {{ k.ten_khoa }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Ngành <span class="req">*</span></label>
                                <select v-model="importForm.nganh">
                                    <option value="">Chọn ngành</option>
                                    <option v-for="n in rows.nganh" :key="n.id" :value="n.ten_nganh">
                                        {{ n.ten_nganh }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Lớp <span class="req">*</span></label>
                                <select v-model="importForm.ten_lop">
                                    <option value="">Chọn lớp</option>
                                    <option v-for="l in rows.lop" :key="l.id" :value="l.ten_lop">
                                        {{ l.ten_lop }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>File danh sách <span class="req">*</span></label>
                                <input type="file" accept=".xlsx,.xls,.doc,.docx" @change="handleFileChange" />
                                <small>Chỉ chấp nhận file Excel hoặc Word.</small>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-primary">Import</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL STUDENT LIST -->
        <div class="modal fade" id="studentListModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5">Danh sách sinh viên - {{ selectedClass?.ten_lop }}</h1>
                            <p class="text-sm text-gray-500 mb-0">Danh sách sinh viên đã import vào hệ thống.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="student-table-scroll">
                            <table class="data-table w-full">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã sinh viên</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th>Số điện thoại</th>
                                        <th>Ngày sinh</th>
                                        <th>Năm trúng tuyển</th>
                                        <th>Lớp</th>
                                        <th>Trạng thái</th>
                                        <th>Điểm SV</th>
                                        <th>Xếp loại SV</th>
                                        <th>Điểm lớp</th>
                                        <th>Xếp loại lớp</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="(sv, index) in importedStudents" :key="sv.ma_sinh_vien">
                                        <td>{{ index + 1 }}</td>
                                        <td>{{ sv.ma_sinh_vien || '—' }}</td>
                                        <td>{{ sv.ho_ten || '—' }}</td>
                                        <td>{{ sv.email || '—' }}</td>
                                        <td>{{ sv.so_dien_thoai || '—' }}</td>
                                        <td>{{ sv.ngay_sinh || '—' }}</td>
                                        <td>{{ sv.nam_trung_tuyen || '—' }}</td>
                                        <td>{{ sv.ten_lop || selectedClass?.ten_lop || '—' }}</td>
                                        <td>{{ formatTrangThai(sv.trang_thai) }}</td>
                                        <td>{{ sv.tong_diem_sv ?? '—' }}</td>
                                        <td>{{ formatXepLoai(sv.xep_loai_sv) }}</td>
                                        <td>{{ sv.tong_diem_lop ?? '—' }}</td>
                                        <td>{{ formatXepLoai(sv.xep_loai_lop) }}</td>
                                    </tr>

                                    <tr v-if="importedStudents.length === 0">
                                        <td colspan="13" class="text-center py-4">
                                            Chưa có sinh viên nào trong lớp này.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="exportExcel(selectedClass)">
                            Xuất Excel
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
    { key: 'khoa', label: 'Khoa' },
    { key: 'nganh', label: 'Ngành' },
    { key: 'lop', label: 'Lớp' },
    { key: 'danh-sach-lop', label: 'Danh sách lớp' },
]

const metaMap = {
    khoa: {
        addLabel: 'khoa',
        description: 'Quản lý danh mục khoa của nhà trường.',
        searchPlaceholder: 'Tìm theo mã hoặc tên khoa...',
        columns: [
            { key: 'ma_khoa', label: 'Mã khoa' },
            { key: 'ten_khoa', label: 'Tên khoa' },
        ],
        fields: [
            { key: 'ma_khoa', label: 'Mã khoa', type: 'text', required: true, placeholder: 'VD: CNTT' },
            { key: 'ten_khoa', label: 'Tên khoa', type: 'text', required: true, placeholder: 'VD: Công nghệ thông tin' },
        ],
        blank: () => ({ ma_khoa: '', ten_khoa: '' }),
    },
    nganh: {
        addLabel: 'ngành',
        description: 'Quản lý danh mục ngành theo từng khoa.',
        searchPlaceholder: 'Tìm theo mã hoặc tên ngành...',
        columns: [
            { key: 'ma_nganh', label: 'Mã ngành' },
            { key: 'ten_nganh', label: 'Tên ngành' },
            { key: 'khoa', label: 'Khoa' },
        ],
        fields: [
            { key: 'ma_nganh', label: 'Mã ngành', type: 'text', required: true, placeholder: 'VD: KTPM' },
            { key: 'ten_nganh', label: 'Tên ngành', type: 'text', required: true, placeholder: 'VD: Kỹ thuật phần mềm' },
            { key: 'khoa', label: 'Khoa', type: 'text', required: true, placeholder: 'VD: Công nghệ thông tin' },
        ],
        blank: () => ({ ma_nganh: '', ten_nganh: '', khoa: '' }),
    },
    lop: {
        addLabel: 'lớp',
        description: 'Quản lý lớp học, lớp trưởng và bí thư.',
        searchPlaceholder: 'Tìm theo mã, tên lớp hoặc ngành...',
        columns: [
            { key: 'ma_lop', label: 'Mã lớp' },
            { key: 'ten_lop', label: 'Tên lớp' },
            { key: 'khoa', label: 'Khoa' },
            { key: 'nganh', label: 'Ngành' },
            { key: 'co_van_hoc_tap', label: 'Cố vấn học tập' },
            { key: 'lop_truong', label: 'Lớp trưởng' },
            { key: 'bi_thu', label: 'Bí thư' },
        ],
        fields: [
            { key: 'ma_lop', label: 'Mã lớp', type: 'text', required: true, placeholder: 'VD: D23CQCN01' },
            { key: 'ten_lop', label: 'Tên lớp', type: 'text', required: true, placeholder: 'VD: D23CQCN01' },
            { key: 'khoa', label: 'Khoa', type: 'text', required: true, placeholder: 'VD: Công nghệ thông tin' },
            { key: 'nganh', label: 'Ngành', type: 'text', required: true, placeholder: 'VD: Kỹ thuật phần mềm' },
            { key: 'co_van_hoc_tap', label: 'Cố vấn học tập', type: 'text', required: false, placeholder: 'VD: ThS. Nguyễn Văn A' },
            { key: 'lop_truong', label: 'Lớp trưởng', type: 'text', required: false, placeholder: 'VD: Nguyễn Văn An' },
            { key: 'bi_thu', label: 'Bí thư', type: 'text', required: false, placeholder: 'VD: Phạm Thị Dung' },
        ],
        blank: () => ({
            ma_lop: '',
            ten_lop: '',
            khoa: '',
            nganh: '',
            co_van_hoc_tap: '',
            lop_truong: '',
            bi_thu: ''
        }),
    },
    'danh-sach-lop': {
        addLabel: 'danh sách lớp',
        description: 'Quản lý danh sách sinh viên theo lớp và import từ file.',
        searchPlaceholder: 'Tìm theo khoa, ngành hoặc lớp...',
        columns: [
            { key: 'khoa', label: 'Khoa' },
            { key: 'nganh', label: 'Ngành' },
            { key: 'ten_lop', label: 'Lớp' },
            { key: 'ten_file', label: 'File danh sách' },
            { key: 'so_sv', label: 'Số SV' },
        ],
        fields: [
            { key: 'khoa', label: 'Khoa', type: 'text', required: true, placeholder: 'VD: Công nghệ thông tin' },
            { key: 'nganh', label: 'Ngành', type: 'text', required: true, placeholder: 'VD: Kỹ thuật phần mềm' },
            { key: 'ten_lop', label: 'Lớp', type: 'text', required: true, placeholder: 'VD: D23CQCN01' },
            { key: 'ten_file', label: 'Tên file danh sách', type: 'text', required: true, placeholder: 'VD: danh_sach_D23CQCN01.xlsx' },
            { key: 'so_sv', label: 'Số sinh viên', type: 'text', required: false, placeholder: 'VD: 42' },
        ],
        blank: () => ({ khoa: '', nganh: '', ten_lop: '', ten_file: '', so_sv: '' }),
    },
}

const rows = reactive({
    khoa: [
        { id: 1, ma_khoa: 'CNTT', ten_khoa: 'Công nghệ thông tin' },
        { id: 2, ma_khoa: 'KT', ten_khoa: 'Kinh tế' },
    ],
    nganh: [
        { id: 1, ma_nganh: 'KTPM', ten_nganh: 'Kỹ thuật phần mềm', khoa: 'Công nghệ thông tin' },
        { id: 2, ma_nganh: 'HTTT', ten_nganh: 'Hệ thống thông tin', khoa: 'Công nghệ thông tin' },
    ],
    lop: [
        {
            id: 1,
            ma_lop: 'D23CQCN01',
            ten_lop: 'D23CQCN01',
            khoa: 'Công nghệ thông tin',
            nganh: 'Kỹ thuật phần mềm',
            co_van_hoc_tap: 'ThS. Nguyễn Văn Minh',
            lop_truong: 'Nguyễn Văn An',
            bi_thu: 'Phạm Thị Dung'
        },
        {
            id: 2,
            ma_lop: 'D23CQCN02',
            ten_lop: 'D23CQCN02',
            khoa: 'Công nghệ thông tin',
            nganh: 'Hệ thống thông tin',
            co_van_hoc_tap: 'ThS. Trần Thị Hoa',
            lop_truong: 'Đỗ Văn Hùng',
            bi_thu: '—'
        },
    ],
    'danh-sach-lop': [
        {
            id: 1,
            khoa: 'Công nghệ thông tin',
            nganh: 'Kỹ thuật phần mềm',
            ten_lop: 'D23CQCN01',
            ten_file: 'danh_sach_D23CQCN01.xlsx',
            so_sv: '42',
        },
        {
            id: 2,
            khoa: 'Công nghệ thông tin',
            nganh: 'Hệ thống thông tin',
            ten_lop: 'D23CQCN02',
            ten_file: 'danh_sach_D23CQCN02.docx',
            so_sv: '40',
        },
    ],


})

const importForm = reactive({
    khoa: '',
    nganh: '',
    ten_lop: '',
    file: null,
})

const importTarget = ref(null)

const selectedClass = ref(null)

const importedStudents = ref([
    {
        ma_sinh_vien: 'SV001',
        ho_ten: 'Nguyễn Văn An',
        email: 'sv001@example.com',
        so_dien_thoai: '0901234567',
        ngay_sinh: '2005-01-15',
        nam_trung_tuyen: 2023,
        ten_lop: 'D23CQCN01',
        trang_thai: 'hoat_dong',
        tong_diem_sv: null,
        xep_loai_sv: null,
        tong_diem_lop: null,
        xep_loai_lop: null,
    },
    {
        ma_sinh_vien: 'SV002',
        ho_ten: 'Trần Thị Bình',
        email: '',
        so_dien_thoai: '',
        ngay_sinh: '',
        nam_trung_tuyen: 2023,
        ten_lop: 'D23CQCN01',
        trang_thai: 'hoat_dong',
        tong_diem_sv: null,
        xep_loai_sv: null,
        tong_diem_lop: null,
        xep_loai_lop: null,
    },
    {
        ma_sinh_vien: 'SV003',
        ho_ten: 'Lê Văn Cường',
        email: '',
        so_dien_thoai: '',
        ngay_sinh: '',
        nam_trung_tuyen: 2023,
        ten_lop: 'D23CQCN01',
        trang_thai: 'hoat_dong',
        tong_diem_sv: null,
        xep_loai_sv: null,
        tong_diem_lop: null,
        xep_loai_lop: null,
    },
])

function handleFileChange(event) {
    importForm.file = event.target.files?.[0] || null
}

function openImport(row = null) {
    importTarget.value = row

    if (row) {
        importForm.khoa = row.khoa
        importForm.nganh = row.nganh
        importForm.ten_lop = row.ten_lop
    } else {
        importForm.khoa = ''
        importForm.nganh = ''
        importForm.ten_lop = ''
    }

    importForm.file = null
}

function submitImport() {
    if (!importForm.khoa || !importForm.nganh || !importForm.ten_lop || !importForm.file) {
        alert('Vui lòng chọn đầy đủ thông tin và file danh sách.')
        return
    }

    // Kiểm tra định dạng file
    const allowedExtensions = ['xlsx', 'xls', 'doc', 'docx']
    const fileName = importForm.file.name
    const extension = fileName.split('.').pop().toLowerCase()

    if (!allowedExtensions.includes(extension)) {
        alert('Chỉ chấp nhận file Excel hoặc Word.')
        return
    }

    const data = {
        khoa: importForm.khoa,
        nganh: importForm.nganh,
        ten_lop: importForm.ten_lop,
        ten_file: importForm.file.name,
        so_sv: 'Đang chờ BE',
        ngay_import: new Date().toLocaleDateString('vi-VN'),
    }

    if (importTarget.value) {
        Object.assign(importTarget.value, data)
    } else {
        rows['danh-sach-lop'].unshift({
            id: Date.now(),
            ...data,
        })
    }

    importTarget.value = null
    hideModal('importClassListModal')
}

function openStudents(row) {
    selectedClass.value = row
}

function exportExcel(row) {
    alert(`Xuất Excel danh sách lớp ${row?.ten_lop || ''}`)
}

const route = useRoute()
const router = useRouter()
const activeTab = ref('khoa')
const search = ref('')
const form = reactive({})
const errors = reactive({})
const isEditing = ref(false)
const editTarget = ref(null)
const deleteTarget = ref(null)

const currentMeta = computed(() => metaMap[activeTab.value])
const searchPlaceholder = computed(() => currentMeta.value.searchPlaceholder)

const filteredRows = computed(() => {
    const q = search.value.trim().toLowerCase()
    return rows[activeTab.value].filter((row) => {
        if (!q) return true
        return Object.values(row).some((value) => String(value || '').toLowerCase().includes(q))
    })
})

function setTab(key) {
    activeTab.value = key
    search.value = ''
    clearForm()
    router.replace({ query: { ...route.query, tab: key } })
}

function renderCell(row, key) {
    return row[key] ?? '—'
}

function formatTrangThai(value) {
    const map = {
        hoat_dong: 'Hoạt động',
        khong_hoat_dong: 'Không hoạt động',
        bi_khoa: 'Bị khóa',
    }

    return map[value] || '—'
}

function formatXepLoai(value) {
    const map = {
        xuat_sac: 'Xuất sắc',
        tot: 'Tốt',
        kha: 'Khá',
        trung_binh: 'Trung bình',
        yeu: 'Yếu',
        kem: 'Kém',
    }

    return map[value] || '—'
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
        if (field.required && !String(form[field.key] || '').trim()) {
            errors[field.key] = `Vui lòng nhập ${field.label.toLowerCase()}`
            ok = false
        }
    }
    return ok
}

function saveRow() {
    if (!validate()) return
    const key = activeTab.value
    if (isEditing.value && editTarget.value) {
        Object.assign(editTarget.value, form)
    } else {
        rows[key].unshift({ id: Date.now(), ...JSON.parse(JSON.stringify(form)) })
    }
    hideModal('trainingFormModal')
    clearForm()
}

function confirmDelete() {
    if (!deleteTarget.value) return
    rows[activeTab.value] = rows[activeTab.value].filter((row) => row.id !== deleteTarget.value.id)
    deleteTarget.value = null
    hideModal('trainingDeleteModal')
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
        const nextTab = typeof value === 'string' && metaMap[value] ? value : 'khoa'
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

.student-table-scroll {
    width: 100%;
    overflow-x: auto;
}

.student-table-scroll table {
    min-width: 1200px;
}
</style>