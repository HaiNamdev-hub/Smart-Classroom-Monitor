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

<script>
import axios from 'axios'
import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

export default {
  data() {
    return {
      tabs: [
        { key: 'khoa', label: 'Khoa' },
        { key: 'nganh', label: 'Ngành' },
        { key: 'lop', label: 'Lớp' },
        { key: 'danh-sach-lop', label: 'Danh sách lớp' },
      ],

      activeTab: 'khoa',
      search: '',
      isEditing: false,
      editTarget: null,
      deleteTarget: null,
      errors: {},

      rows: {
        khoa: [],
        nganh: [],
        lop: [],
        'danh-sach-lop': [],
      },

      form: {},

      importForm: {
        khoa: '',
        nganh: '',
        ten_lop: '',
        file: null,
      },

      importTarget: null,
      selectedClass: null,
      importedStudents: [],

      metaMap: {
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
          description: 'Quản lý danh mục ngành.',
          searchPlaceholder: 'Tìm theo mã hoặc tên ngành...',
          columns: [
            { key: 'ma_nganh', label: 'Mã ngành' },
            { key: 'ten_nganh', label: 'Tên ngành' },
          ],
          fields: [
            { key: 'ma_nganh', label: 'Mã ngành', type: 'text', required: true, placeholder: 'VD: KTPM' },
            { key: 'ten_nganh', label: 'Tên ngành', type: 'text', required: true, placeholder: 'VD: Kỹ thuật phần mềm' },
          ],
          blank: () => ({ ma_nganh: '', ten_nganh: '' }),
        },

        lop: {
          addLabel: 'lớp',
          description: 'Quản lý lớp học.',
          searchPlaceholder: 'Tìm theo mã hoặc tên lớp...',
          columns: [
            { key: 'ma_lop', label: 'Mã lớp' },
            { key: 'ten_lop', label: 'Tên lớp' },
            { key: 'khoa_id', label: 'Khoa' },
            { key: 'nganh_id', label: 'Ngành' },
            { key: 'co_van_id', label: 'Cố vấn' },
            { key: 'lop_truong_id', label: 'Lớp trưởng' },
            { key: 'bi_thu_id', label: 'Bí thư' },
          ],
          fields: [
            { key: 'ma_lop', label: 'Mã lớp', type: 'text', required: true, placeholder: 'VD: D23CQCN01' },
            { key: 'ten_lop', label: 'Tên lớp', type: 'text', required: true, placeholder: 'VD: D23CQCN01' },
            { key: 'khoa_id', label: 'Khoa ID', type: 'text', required: true, placeholder: 'VD: 1' },
            { key: 'nganh_id', label: 'Ngành ID', type: 'text', required: true, placeholder: 'VD: 1' },
            { key: 'co_van_id', label: 'Cố vấn ID', type: 'text', required: false, placeholder: 'Có thể bỏ trống' },
            { key: 'lop_truong_id', label: 'Lớp trưởng ID', type: 'text', required: false, placeholder: 'Có thể bỏ trống' },
            { key: 'bi_thu_id', label: 'Bí thư ID', type: 'text', required: false, placeholder: 'Có thể bỏ trống' },
          ],
          blank: () => ({
            ma_lop: '',
            ten_lop: '',
            khoa_id: '',
            nganh_id: '',
            co_van_id: '',
            lop_truong_id: '',
            bi_thu_id: '',
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
          fields: [],
          blank: () => ({ khoa: '', nganh: '', ten_lop: '', ten_file: '', so_sv: '' }),
        },
      },
    }
  },

  mounted() {
    this.clearForm()
    this.getKhoa()
    this.getNganh()
    this.getLop()
  },

  computed: {
    currentMeta() {
      return this.metaMap[this.activeTab]
    },

    searchPlaceholder() {
      return this.currentMeta.searchPlaceholder
    },

    filteredRows() {
      const keyword = this.search.trim().toLowerCase()

      return this.rows[this.activeTab].filter((row) => {
        if (!keyword) return true

        return Object.values(row).some((value) =>
          String(value || '').toLowerCase().includes(keyword)
        )
      })
    },
  },

  methods: {
    authHeader() {
      return {
        Authorization: 'Bearer ' + sessionStorage.getItem('auth_token'),
      }
    },

    getKhoa() {
      axios
        .get('http://localhost:8000/api/admin/khoa', {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.rows.khoa = res.data.data || []
        })
        .catch((err) => {
          console.error('GET KHOA ERROR:', err.response?.data || err)
          this.$toast?.error('Lấy danh sách khoa thất bại.')
        })
    },

    getNganh() {
      axios
        .get('http://localhost:8000/api/admin/nganh', {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.rows.nganh = res.data.data || []
        })
        .catch((err) => {
          console.error('GET NGANH ERROR:', err.response?.data || err)
          this.$toast?.error('Lấy danh sách ngành thất bại.')
        })
    },

    getLop() {
      axios
        .get('http://localhost:8000/api/admin/lop', {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.rows.lop = res.data.data || []
        })
        .catch((err) => {
          console.error('GET LOP ERROR:', err.response?.data || err)
          this.$toast?.error('Lấy danh sách lớp thất bại.')
        })
    },

    setTab(key) {
      this.activeTab = key
      this.search = ''
      this.clearForm()
    },

    renderCell(row, key) {
      if (key === 'khoa_id') {
        const khoa = this.rows.khoa.find((item) => Number(item.id) === Number(row.khoa_id))
        return khoa ? khoa.ten_khoa : row.khoa_id || '—'
      }

      if (key === 'nganh_id') {
        const nganh = this.rows.nganh.find((item) => Number(item.id) === Number(row.nganh_id))
        return nganh ? nganh.ten_nganh : row.nganh_id || '—'
      }

      return row[key] ?? '—'
    },

    clearForm() {
      this.form = this.currentMeta.blank()
      this.errors = {}
      this.isEditing = false
      this.editTarget = null
    },

    openAdd() {
      this.clearForm()
    },

    openEdit(row) {
      this.isEditing = true
      this.editTarget = row
      this.form = JSON.parse(JSON.stringify(row))
    },

    openDelete(row) {
      this.deleteTarget = row
    },

    validate() {
      this.errors = {}
      let ok = true

      this.currentMeta.fields.forEach((field) => {
        if (field.required && !String(this.form[field.key] || '').trim()) {
          this.errors[field.key] = `Vui lòng nhập ${field.label.toLowerCase()}`
          ok = false
        }
      })

      return ok
    },

    saveRow() {
      if (!this.validate()) return

      if (this.activeTab === 'khoa') {
        this.saveKhoa()
      }

      if (this.activeTab === 'nganh') {
        this.saveNganh()
      }

      if (this.activeTab === 'lop') {
        this.saveLop()
      }
    },

    saveKhoa() {
      const url = this.isEditing
        ? 'http://localhost:8000/api/admin/khoa/sua-khoa'
        : 'http://localhost:8000/api/admin/khoa/them-khoa'

      axios
        .post(url, this.form, {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.$toast?.success(res.data.message || 'Lưu khoa thành công.')
          this.getKhoa()
          this.hideModal('trainingFormModal')
          this.clearForm()
        })
        .catch((err) => {
          this.showError(err, 'Lưu khoa thất bại.')
        })
    },

    saveNganh() {
      const url = this.isEditing
        ? 'http://localhost:8000/api/admin/nganh/sua-nganh'
        : 'http://localhost:8000/api/admin/nganh/them-nganh'

      axios
        .post(url, this.form, {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.$toast?.success(res.data.message || 'Lưu ngành thành công.')
          this.getNganh()
          this.hideModal('trainingFormModal')
          this.clearForm()
        })
        .catch((err) => {
          this.showError(err, 'Lưu ngành thất bại.')
        })
    },

    saveLop() {
      const payload = {
        ...this.form,
        co_van_id: this.form.co_van_id || null,
        lop_truong_id: this.form.lop_truong_id || null,
        bi_thu_id: this.form.bi_thu_id || null,
      }

      const url = this.isEditing
        ? 'http://localhost:8000/api/admin/lop/sua-lop'
        : 'http://localhost:8000/api/admin/lop/them-lop'

      axios
        .post(url, payload, {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.$toast?.success(res.data.message || 'Lưu lớp thành công.')
          this.getLop()
          this.hideModal('trainingFormModal')
          this.clearForm()
        })
        .catch((err) => {
          this.showError(err, 'Lưu lớp thất bại.')
        })
    },

    confirmDelete() {
      if (!this.deleteTarget) return

      if (this.activeTab === 'khoa') {
        this.deleteKhoa()
      }

      if (this.activeTab === 'nganh') {
        this.deleteNganh()
      }

      if (this.activeTab === 'lop') {
        this.deleteLop()
      }
    },

    deleteKhoa() {
      axios
        .post('http://localhost:8000/api/admin/khoa/xoa-khoa', { id: this.deleteTarget.id }, {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.$toast?.success(res.data.message || 'Xóa khoa thành công.')
          this.getKhoa()
          this.hideModal('trainingDeleteModal')
          this.deleteTarget = null
        })
        .catch((err) => {
          this.showError(err, 'Xóa khoa thất bại.')
        })
    },

    deleteNganh() {
      axios
        .post('http://localhost:8000/api/admin/nganh/xoa-nganh', { id: this.deleteTarget.id }, {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.$toast?.success(res.data.message || 'Xóa ngành thành công.')
          this.getNganh()
          this.hideModal('trainingDeleteModal')
          this.deleteTarget = null
        })
        .catch((err) => {
          this.showError(err, 'Xóa ngành thất bại.')
        })
    },

    deleteLop() {
      axios
        .post('http://localhost:8000/api/admin/lop/xoa-lop', { id: this.deleteTarget.id }, {
          headers: this.authHeader(),
        })
        .then((res) => {
          this.$toast?.success(res.data.message || 'Xóa lớp thành công.')
          this.getLop()
          this.hideModal('trainingDeleteModal')
          this.deleteTarget = null
        })
        .catch((err) => {
          this.showError(err, 'Xóa lớp thất bại.')
        })
    },

    handleFileChange(event) {
      this.importForm.file = event.target.files?.[0] || null
    },

    openImport(row = null) {
      this.importTarget = row

      if (row) {
        this.importForm.khoa = row.khoa
        this.importForm.nganh = row.nganh
        this.importForm.ten_lop = row.ten_lop
      } else {
        this.importForm.khoa = ''
        this.importForm.nganh = ''
        this.importForm.ten_lop = ''
      }

      this.importForm.file = null
    },

    submitImport() {
      this.$toast?.error('Chức năng import file làm sau.')
    },

    openStudents(row) {
      this.selectedClass = row
    },

    exportExcel(row) {
      alert(`Xuất Excel danh sách lớp ${row?.ten_lop || ''}`)
    },

    formatTrangThai(value) {
      const map = {
        hoat_dong: 'Hoạt động',
        khong_hoat_dong: 'Không hoạt động',
        bi_khoa: 'Bị khóa',
      }

      return map[value] || '—'
    },

    formatXepLoai(value) {
      const map = {
        xuat_sac: 'Xuất sắc',
        tot: 'Tốt',
        kha: 'Khá',
        trung_binh: 'Trung bình',
        yeu: 'Yếu',
        kem: 'Kém',
      }

      return map[value] || '—'
    },

    hideModal(id) {
      const el = document.getElementById(id)
      const ModalCtor = window.bootstrap?.Modal

      if (!el || !ModalCtor) return

      const instance = ModalCtor.getInstance(el) || ModalCtor.getOrCreateInstance(el)
      instance.hide()
    },

    showError(err, defaultMessage) {
      console.error(err.response?.data || err)

      if (err.response?.data?.errors) {
        const errors = err.response.data.errors
        const firstKey = Object.keys(errors)[0]
        const firstMessage = errors[firstKey][0]

        this.$toast?.error(firstMessage || 'Dữ liệu không hợp lệ.')
        return
      }

      this.$toast?.error(err.response?.data?.message || defaultMessage)
    },
  },
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

.student-table-scroll {
    width: 100%;
    overflow-x: auto;
}

.student-table-scroll table {
    min-width: 1200px;
}
</style>