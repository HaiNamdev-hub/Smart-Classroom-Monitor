<template>
    <div class="admin-content training-page student-evaluation-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Phiếu đánh giá cá nhân</h1>
            <p>
                Sinh viên tự đánh giá điểm rèn luyện và upload minh chứng theo từng tiêu chí.
            </p>
        </div>

        <!-- THỐNG KÊ NHANH -->
        <div class="summary-grid">
            <div class="summary-card">
                <div class="summary-icon bg-blue">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>

                <div>
                    <span>Điểm SV tự đánh giá</span>
                    <strong>{{ totalStudentScore }}/100</strong>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon bg-green">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div>
                    <span>Điểm lớp đánh giá</span>
                    <strong>
                        {{ hasClassScore ? `${totalClassScore}/100` : '—' }}
                    </strong>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon bg-amber">
                    <i class="fa-solid fa-award"></i>
                </div>

                <div>
                    <span>Xếp loại tạm tính</span>
                    <strong>{{ classifyScore(totalStudentScore) }}</strong>
                </div>
            </div>

            <div class="summary-card">
                <div class="summary-icon bg-red">
                    <i class="fa-solid fa-circle-info"></i>
                </div>

                <div>
                    <span>Trạng thái</span>
                    <strong>{{ formatTrangThai(phieu.trang_thai) }}</strong>
                </div>
            </div>
        </div>

        <!-- TRẠNG THÁI -->
        <div class="detail-status-row">
            <span class="status-dot" :class="'status-' + phieu.trang_thai">
                {{ formatTrangThai(phieu.trang_thai) }}
            </span>

            <span v-if="canEditStudentScore" class="readonly-note">
                Bạn có thể chỉnh sửa cột Điểm SV tự đánh giá và quản lý minh chứng.

                <template v-if="canEditStudentInfo">
                    Thông tin cá nhân cũng có thể chỉnh sửa.
                </template>

                <template v-else-if="isClassOfficer">
                    Tài khoản cán sự lớp không được sửa thông tin cá nhân tại phiếu này.
                </template>
            </span>

            <span v-else class="readonly-note">
                Phiếu đã gửi, hiện chỉ có thể xem thông tin.
            </span>
        </div>

        <!-- CẢNH BÁO -->
        <div class="student-alert">
            <i class="fa-solid fa-circle-exclamation"></i>

            <div>
                <strong>Lưu ý:</strong>
                Sinh viên chỉ được sửa cột <b>Điểm SV tự đánh giá</b> và upload minh chứng
                khi phiếu ở trạng thái <b>Nháp</b> hoặc <b>Trả lại</b>.
                Cột <b>Điểm Lớp đánh giá</b> do lớp trưởng/bí thư hoặc CVHT thực hiện.
            </div>
        </div>

        <!-- PHIẾU -->
        <div class="evaluation-paper">

            <!-- HEADER PHIẾU -->
            <div class="paper-top">
                <div class="paper-agency">
                    <span>HỌC VIỆN HÀNH CHÍNH<br />VÀ QUẢN TRỊ CÔNG</span>
                    <strong>
                        PHÂN HIỆU HỌC VIỆN<br />
                        HÀNH CHÍNH VÀ QUẢN TRỊ CÔNG<br />
                        TẠI TỈNH QUẢNG NAM
                    </strong>
                    <span>*</span>
                </div>

                <div class="paper-party">
                    <strong>ĐẢNG CỘNG SẢN VIỆT NAM</strong>
                </div>
            </div>

            <div class="paper-title">
                <p>Form:</p>
                <h2>PHIẾU ĐÁNH GIÁ KẾT QUẢ RÈN LUYỆN CỦA SINH VIÊN</h2>
                <span>
                    Kèm theo Quyết định số 4185/QĐ - HCQG ngày 08 tháng 9 năm 2023
                    của Giám đốc Học viện Hành chính Quốc gia
                </span>
            </div>

            <!-- THÔNG TIN SINH VIÊN -->
            <div v-if="canEditStudentInfo" class="editable-info-note">
                <i class="fa-solid fa-circle-info"></i>
                Bạn có thể chỉnh sửa một số thông tin cá nhân trên phiếu.
            </div>

            <div v-else-if="isClassOfficer" class="locked-info-note">
                <i class="fa-solid fa-lock"></i>
                Tài khoản cán sự lớp chỉ được xem thông tin cá nhân tại phiếu này.
            </div>

            <div class="student-info-form">
                <div class="info-line">
                    <label>Năm trúng tuyển:</label>
                    <input v-model="sinhVien.nam_trung_tuyen" disabled />
                </div>

                <div class="info-line">
                    <label>Ngành/chuyên ngành:</label>
                    <input v-model="sinhVien.lop.nganh.ten_nganh" disabled />
                </div>

                <div class="info-line">
                    <label>Khoa:</label>
                    <input v-model="sinhVien.lop.khoa.ten_khoa" disabled />
                </div>

                <div class="info-line">
                    <label>Lớp:</label>
                    <input v-model="sinhVien.lop.ma_lop" disabled />
                </div>

                <div class="info-line">
                    <label>Mã sinh viên:</label>
                    <input v-model="sinhVien.ma_sinh_vien" disabled />
                </div>

                <div class="info-line" :class="{ 'editable-info-line': canEditStudentInfo }">
                    <label>
                        Họ và tên:
                        <i v-if="canEditStudentInfo" class="fa-solid fa-pen editable-pencil"></i>
                    </label>
                    <input v-model="sinhVien.nguoi_dung.ho_ten" :disabled="!canEditStudentInfo" />
                </div>

                <div class="info-line" :class="{ 'editable-info-line': canEditStudentInfo }">
                    <label>
                        Ngày sinh:
                        <i v-if="canEditStudentInfo" class="fa-solid fa-pen editable-pencil"></i>
                    </label>
                    <input v-model="sinhVien.nguoi_dung.ngay_sinh" :disabled="!canEditStudentInfo" />
                </div>

                <div class="info-line" :class="{ 'editable-info-line': canEditStudentInfo }">
                    <label>
                        Số điện thoại:
                        <i v-if="canEditStudentInfo" class="fa-solid fa-pen editable-pencil"></i>
                    </label>
                    <input v-model="sinhVien.nguoi_dung.so_dien_thoai" :disabled="!canEditStudentInfo" />
                </div>

                <div class="info-line">
                    <label>Học kỳ đánh giá:</label>
                    <input :value="hocKyLabel" disabled />
                </div>

                <div class="info-line">
                    <label>Năm học:</label>
                    <input v-model="dotDanhGia.nam_hoc" disabled />
                </div>
            </div>

            <!-- BẢNG ĐÁNH GIÁ -->
            <div class="official-table-wrap">
                <table class="official-table">
                    <thead>
                        <tr>
                            <th class="col-tt">TT</th>
                            <th class="col-content">Nội dung đánh giá</th>
                            <th class="col-score">Thang điểm</th>
                            <th class="col-score">Điểm SV tự đánh giá</th>
                            <th class="col-score">Điểm Lớp đánh giá</th>
                            <th class="col-note">
                                Ghi chú<br />
                                Minh chứng các hoạt động
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="row in evaluationRows" :key="row.row_key" :class="rowClass(row)">
                            <!-- TT -->
                            <td class="text-center">
                                {{ row.tt }}
                            </td>

                            <!-- NỘI DUNG -->
                            <td>
                                <strong v-if="row.row_type === 'section'">
                                    {{ row.ten_hien_thi }}
                                </strong>

                                <span v-else>
                                    {{ row.ten_hien_thi }}
                                </span>

                                <div v-if="row.row_type === 'criteria' && row.bat_buoc_minh_chung"
                                    class="required-evidence">
                                    <i class="fa-solid fa-paperclip"></i>
                                    Bắt buộc minh chứng
                                </div>
                            </td>

                            <!-- THANG ĐIỂM -->
                            <td class="text-center">
                                {{ row.diem_toi_da_text }}
                            </td>

                            <!-- ĐIỂM SV -->
                            <td class="text-center">
                                <template v-if="row.row_type === 'criteria'">

                                    <!-- COMBOBOX -->
                                    <select v-if="row.loai_nhap === 'combobox'" v-model.number="row.lua_chon_id"
                                        class="score-select" :disabled="!canEditStudentScore"
                                        @change="onChangeStudentChoice(row)">
                                        <option :value="null">Chọn</option>

                                        <option v-for="option in row.lua_chon" :key="option.id" :value="option.id">
                                            {{ option.diem_tuong_ung }} điểm
                                        </option>
                                    </select>

                                    <!-- CHECKBOX -->
                                    <div v-else-if="row.loai_nhap === 'checkbox'" class="score-checkbox-group">
                                        <label v-for="option in row.lua_chon" :key="option.id"
                                            class="score-checkbox-item">
                                            <input type="checkbox" :value="option.id"
                                                :checked="isCheckedOption(row, option.id)"
                                                :disabled="!canEditStudentScore"
                                                @change="onChangeCheckboxChoice(row, option, $event)" />

                                            <span>{{ option.diem_tuong_ung }}đ</span>
                                        </label>
                                    </div>

                                    <!-- TEXTBOX -->
                                    <input v-else v-model.number="row.diem_sv" type="number" class="score-input"
                                        :min="0" :max="row.diem_toi_da" :disabled="!canEditStudentScore"
                                        @input="normalizeScore(row, 'diem_sv')"
                                        @blur="normalizeScore(row, 'diem_sv')" />

                                </template>

                                <span v-else>—</span>
                            </td>

                            <!-- ĐIỂM LỚP -->
                            <td class="text-center">
                                <input v-if="row.row_type === 'criteria' && hasClassScore" v-model.number="row.diem_lop"
                                    type="number" class="score-input" :min="0" :max="row.diem_toi_da" disabled
                                    @input="normalizeScore(row, 'diem_lop')" @blur="normalizeScore(row, 'diem_lop')" />

                                <span v-else>—</span>
                            </td>

                            <!-- GHI CHÚ / MINH CHỨNG -->
                            <td>
                                <div v-if="row.row_type === 'criteria'" class="evidence-box">

                                    <!-- DANH SÁCH MINH CHỨNG -->
                                    <div v-if="row.minh_chung && row.minh_chung.length" class="evidence-list">
                                        <div v-for="file in row.minh_chung" :key="file.id" class="evidence-item">
                                            <div class="evidence-name">
                                                <i :class="fileIcon(file.ten_file)"></i>
                                                <span>{{ file.ten_file }}</span>
                                            </div>

                                            <div class="evidence-actions">
                                                <button type="button" class="mini-btn" data-bs-toggle="modal"
                                                    data-bs-target="#studentEvidenceModal" @click="openEvidence(file)">
                                                    Xem
                                                </button>

                                                <button type="button" class="mini-btn" @click="downloadEvidence(file)">
                                                    Tải
                                                </button>

                                                <button v-if="canManageEvidence" type="button" class="mini-btn danger"
                                                    @click="removeEvidence(row, file.id)">
                                                    Xóa
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- UPLOAD MINH CHỨNG -->
                                    <label v-if="canManageEvidence" class="upload-mini">
                                        <i class="fa-solid fa-upload"></i>
                                        <span>Upload minh chứng</span>

                                        <input type="file" hidden accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                                            @change="uploadEvidence($event, row)" />
                                    </label>

                                    <!-- GHI CHÚ -->
                                    <textarea v-model="row.ghi_chu" class="note-input" :disabled="!canEditStudentScore"
                                        placeholder="Ghi chú / mô tả minh chứng..."></textarea>
                                </div>

                                <span v-else-if="row.ghi_chu">
                                    {{ row.ghi_chu }}
                                </span>

                                <span v-else>—</span>
                            </td>
                        </tr>

                        <!-- TỔNG -->
                        <tr class="total-row">
                            <td colspan="2" class="text-right">
                                <strong>Tổng cộng:</strong>
                            </td>

                            <td class="text-center">
                                <strong>100 điểm</strong>
                            </td>

                            <td class="text-center">
                                <strong>{{ totalStudentScore }}</strong>
                            </td>

                            <td class="text-center">
                                <strong>{{ hasClassScore ? totalClassScore : '—' }}</strong>
                            </td>

                            <td>
                                <strong>
                                    Xếp loại tạm tính: {{ classifyScore(totalStudentScore) }}
                                </strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- GHI CHÚ CUỐI PHIẾU -->
            <div class="paper-note">
                <p><strong>Ghi chú:</strong></p>
                <p>1. Điểm rèn luyện là tổng của 5 mục đánh giá trên bằng 100 điểm.</p>
                <p>
                    2. Kết quả rèn luyện được phân thành các loại:
                    Xuất sắc, Tốt, Khá, Trung bình, Yếu, Kém.
                </p>
                <p>
                    3. Sinh viên không thực hiện đánh giá hoặc nộp phiếu tự đánh giá không đúng hạn
                    sẽ bị xếp loại rèn luyện loại yếu, kém.
                </p>
            </div>

            <!-- XẾP LOẠI -->
            <div class="rank-area">
                <div>
                    Sinh viên tự đánh giá, xếp loại:
                    <strong>{{ classifyScore(totalStudentScore) }}</strong>
                </div>

                <div>
                    Lớp đánh giá, xếp loại:
                    <strong>
                        {{ hasClassScore ? classifyScore(totalClassScore) : 'Chưa đánh giá' }}
                    </strong>
                </div>
            </div>

            <!-- CHỮ KÝ -->
            <div class="signature-area">
                <div>
                    <strong>T/M LỚP</strong>
                    <span>LỚP TRƯỞNG</span>
                </div>

                <div>
                    <span>Quảng Nam, ngày .... tháng .... năm ......</span>
                    <strong>SINH VIÊN</strong>
                </div>
            </div>
        </div>

        <!-- ACTION BAR -->
        <div class="review-action-bar">
            <div>
                <span>Tổng điểm SV tự đánh giá:</span>
                <strong>{{ totalStudentScore }}/100</strong>
                <small>{{ classifyScore(totalStudentScore) }}</small>
            </div>

            <div class="action-buttons">
                <button type="button" class="btn-ghost" :disabled="!canEditStudentScore" @click="saveDraft">
                    Lưu nháp
                </button>

                <button type="button" class="btn-primary" :disabled="!canEditStudentScore" @click="submitEvaluation">
                    Gửi phiếu
                </button>
            </div>
        </div>

        <!-- MODAL XEM MINH CHỨNG -->
        <div class="modal fade" id="studentEvidenceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5">Xem minh chứng</h1>
                            <p class="modal-subtitle">
                                {{ selectedEvidence?.ten_file }}
                            </p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div v-if="selectedEvidence" class="evidence-preview-wrapper">

                            <div v-if="isImageFile(selectedEvidence)" class="evidence-preview-frame">
                                <img :src="selectedEvidence.duong_dan" :alt="selectedEvidence.ten_file"
                                    class="evidence-image" />
                            </div>

                            <div v-else-if="isPdfFile(selectedEvidence)" class="evidence-preview-frame">
                                <iframe :src="selectedEvidence.duong_dan" class="evidence-pdf"
                                    title="PDF preview"></iframe>
                            </div>

                            <div v-else class="no-preview-box">
                                <i :class="fileIcon(selectedEvidence.ten_file)"></i>

                                <h3>{{ selectedEvidence.ten_file }}</h3>

                                <p>
                                    File này không hỗ trợ xem trực tiếp.
                                    Vui lòng tải về để xem.
                                </p>
                            </div>

                            <div class="preview-meta">
                                <div>
                                    <span>Tên file</span>
                                    <strong>{{ selectedEvidence.ten_file }}</strong>
                                </div>

                                <div>
                                    <span>Ngày upload</span>
                                    <strong>{{ selectedEvidence.created_at }}</strong>
                                </div>

                                <div>
                                    <span>Trạng thái</span>
                                    <strong>
                                        {{ formatTrangThaiMinhChung(selectedEvidence.trang_thai) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-ghost" data-bs-dismiss="modal">
                            Đóng
                        </button>

                        <button type="button" class="btn-primary" @click="downloadEvidence(selectedEvidence)">
                            Tải file
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from 'axios'
import { createToaster } from '@meforma/vue-toaster'

import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"
import "../../../assets/css/phieurenluyen.css"

const toaster = createToaster({
    position: 'top-right',
})

const BASE_URL = 'http://127.0.0.1:8000/api'

function authHeader() {
    const token =
        localStorage.getItem('key_admin') ||
        localStorage.getItem('auth_token') ||
        sessionStorage.getItem('auth_token')

    return {
        headers: {
            Authorization: 'Bearer ' + token,
        },
    }
}

const loading = ref(false)
const selectedEvidence = ref(null)

const currentUser = reactive({
    id: null,
    ho_ten: '',
    email: '',
    so_dien_thoai: '',
    ngay_sinh: '',
    vai_tro_id: null,
    trang_thai: '',
})

const sinhVien = reactive({
    id: null,
    ma_sinh_vien: '',
    nam_trung_tuyen: '',
    user_id: null,
    lop_id: null,

    nguoi_dung: {
        id: null,
        ho_ten: '',
        ngay_sinh: '',
        so_dien_thoai: '',
    },

    lop: {
        id: null,
        ma_lop: '',
        ten_lop: '',
        lop_truong_id: null,
        bi_thu_id: null,

        khoa: {
            ma_khoa: '',
            ten_khoa: '',
        },

        nganh: {
            ma_nganh: '',
            ten_nganh: '',
        },
    },
})

const dotDanhGia = reactive({
    id: null,
    nam_hoc: '',
    hoc_ky: '',
    ngay_bat_dau: '',
    ngay_ket_thuc: '',
    trang_thai: '',
})

const phieu = reactive({
    id: null,
    sinh_vien_id: null,
    dot_danh_gia_id: null,
    tong_diem_sv: 0,
    tong_diem_lop: 0,
    tong_diem_cuoi: 0,
    xep_loai: null,
    trang_thai: 'nhap',
})

const evaluationRows = reactive([])

onMounted(() => {
    getData()
})

function getData() {
    loading.value = true

    axios
        .get(BASE_URL + '/sinh-vien/phieu-ren-luyen/get-data', authHeader())
        .then((res) => {
            if (!res.data.status) {
                toaster.error(res.data.message || 'Không lấy được dữ liệu phiếu!')
                return
            }

            const data = res.data.data

            Object.assign(currentUser, data.current_user || {})
            Object.assign(sinhVien, data.sinh_vien || {})
            Object.assign(dotDanhGia, data.dot_danh_gia || {})
            Object.assign(phieu, data.phieu || {})

            evaluationRows.splice(
                0,
                evaluationRows.length,
                ...(data.evaluation_rows || [])
            )
        })
        .catch((err) => {
            console.log(err.response?.data || err)
            toaster.error(err.response?.data?.message || 'Lỗi tải phiếu rèn luyện!')
        })
        .finally(() => {
            loading.value = false
        })
}

const hocKyLabel = computed(() => {
    if (Number(dotDanhGia.hoc_ky) === 1) return 'Kỳ I'
    if (Number(dotDanhGia.hoc_ky) === 2) return 'Kỳ II'
    return dotDanhGia.hoc_ky ? `Kỳ ${dotDanhGia.hoc_ky}` : '—'
})

const roleId = computed(() => {
    return Number(currentUser.vai_tro_id)
})

const isClassOfficer = computed(() => {
    return [4, 5].includes(roleId.value)
})

const canEditStudentInfo = computed(() => {
    return roleId.value === 6 && ['nhap', 'tra_lai'].includes(phieu.trang_thai)
})

const canEditStudentScore = computed(() => {
    return ['nhap', 'tra_lai'].includes(phieu.trang_thai)
})

const canManageEvidence = computed(() => {
    return canEditStudentScore.value
})

const hasClassScore = computed(() => {
    return [
        'lop_da_danh_gia',
        'co_van_da_kiem_tra',
        'hoan_thanh',
    ].includes(phieu.trang_thai)
})

const totalStudentScore = computed(() => {
    const total = evaluationRows
        .filter((row) => row.row_type === 'criteria')
        .reduce((sum, row) => sum + Number(row.diem_sv || 0), 0)

    return Math.min(total, 100)
})

const totalClassScore = computed(() => {
    const total = evaluationRows
        .filter((row) => row.row_type === 'criteria')
        .reduce((sum, row) => sum + Number(row.diem_lop || 0), 0)

    return Math.min(total, 100)
})

function rowClass(row) {
    return {
        'section-row': row.row_type === 'section',
        'criteria-row': row.row_type === 'criteria',
        'option-row': row.row_type === 'option',
    }
}

function normalizeScore(row, field) {
    let value = Number(row[field] || 0)

    if (value < 0) value = 0

    if (row.diem_toi_da !== undefined && row.diem_toi_da !== null) {
        value = Math.min(value, Number(row.diem_toi_da))
    }

    row[field] = value
}

function onChangeStudentChoice(row) {
    const option = row.lua_chon?.find((item) => {
        return Number(item.id) === Number(row.lua_chon_id)
    })

    row.diem_sv = option ? Number(option.diem_tuong_ung) : 0
    row.gia_tri_nhap = option ? option.noi_dung : null
    normalizeScore(row, 'diem_sv')
}

function isCheckedOption(row, optionId) {
    if (!row.lua_chon_id) return false

    const selected = String(row.lua_chon_id)
        .split(',')
        .map((id) => Number(id))

    return selected.includes(Number(optionId))
}

function onChangeCheckboxChoice(row, option, event) {
    let selected = []

    if (row.lua_chon_id) {
        selected = String(row.lua_chon_id)
            .split(',')
            .filter((id) => id !== '')
            .map((id) => Number(id))
    }

    if (event.target.checked) {
        if (!selected.includes(Number(option.id))) {
            selected.push(Number(option.id))
        }
    } else {
        selected = selected.filter((id) => id !== Number(option.id))
    }

    row.lua_chon_id = selected.join(',')

    row.diem_sv = row.lua_chon
        .filter((item) => selected.includes(Number(item.id)))
        .reduce((sum, item) => sum + Number(item.diem_tuong_ung || 0), 0)

    normalizeScore(row, 'diem_sv')
}

function buildPayload() {
    return {
        phieu_id: phieu.id,
        chi_tiet_danh_gia: evaluationRows
            .filter((row) => row.row_type === 'criteria')
            .map((row) => ({
                tieu_chi_id: row.tieu_chi_id,
                lua_chon_id: row.lua_chon_id,
                gia_tri_nhap: row.gia_tri_nhap,
                diem_sv: Number(row.diem_sv || 0),
                diem_lop: Number(row.diem_lop || 0),
                ghi_chu: row.ghi_chu,
            })),
    }
}

function saveDraft() {
    if (!phieu.id) {
        toaster.error('Không tìm thấy phiếu!')
        return
    }

    axios
        .post(
            BASE_URL + '/sinh-vien/phieu-ren-luyen/luu-nhap',
            buildPayload(),
            authHeader()
        )
        .then((res) => {
            if (!res.data.status) {
                toaster.error(res.data.message || 'Lưu nháp thất bại!')
                return
            }

            Object.assign(phieu, res.data.data || {})
            toaster.success(res.data.message || 'Lưu nháp thành công!')
        })
        .catch((err) => {
            console.log(err.response?.data || err)
            toaster.error(err.response?.data?.message || 'Lỗi lưu nháp!')
        })
}

function submitEvaluation() {
    if (!phieu.id) {
        toaster.error('Không tìm thấy phiếu!')
        return
    }

    const invalidEvidenceRow = evaluationRows.find((row) => {
        return (
            row.row_type === 'criteria' &&
            row.bat_buoc_minh_chung &&
            Number(row.diem_sv || 0) > 0 &&
            (!row.minh_chung || row.minh_chung.length === 0)
        )
    })

    if (invalidEvidenceRow) {
        toaster.error(`Tiêu chí "${invalidEvidenceRow.ten_hien_thi}" bắt buộc có minh chứng!`)
        return
    }

    if (!confirm('Bạn chắc chắn muốn gửi phiếu? Sau khi gửi sẽ không thể chỉnh sửa.')) {
        return
    }

    axios
        .post(
            BASE_URL + '/sinh-vien/phieu-ren-luyen/gui-phieu',
            buildPayload(),
            authHeader()
        )
        .then((res) => {
            if (!res.data.status) {
                toaster.error(res.data.message || 'Gửi phiếu thất bại!')
                return
            }

            Object.assign(phieu, res.data.data || {})
            toaster.success(res.data.message || 'Gửi phiếu thành công!')
            getData()
        })
        .catch((err) => {
            console.log(err.response?.data || err)
            toaster.error(err.response?.data?.message || 'Lỗi gửi phiếu!')
        })
}

function uploadEvidence(event, row) {
    const file = event.target.files[0]

    if (!file) return

    if (!row.chi_tiet_id) {
        toaster.error('Không tìm thấy chi tiết tiêu chí!')
        event.target.value = ''
        return
    }

    const formData = new FormData()
    formData.append('chi_tiet_id', row.chi_tiet_id)
    formData.append('file', file)

    axios
        .post(
            BASE_URL + '/sinh-vien/phieu-ren-luyen/upload-minh-chung',
            formData,
            {
                ...authHeader(),
                headers: {
                    ...authHeader().headers,
                    'Content-Type': 'multipart/form-data',
                },
            }
        )
        .then((res) => {
            if (!res.data.status) {
                toaster.error(res.data.message || 'Upload minh chứng thất bại!')
                return
            }

            if (!row.minh_chung) {
                row.minh_chung = []
            }

            row.minh_chung.push(res.data.data)
            toaster.success(res.data.message || 'Upload minh chứng thành công!')
        })
        .catch((err) => {
            console.log(err.response?.data || err)
            toaster.error(err.response?.data?.message || 'Lỗi upload minh chứng!')
        })
        .finally(() => {
            event.target.value = ''
        })
}

function removeEvidence(row, fileId) {
    if (!confirm('Bạn có chắc muốn xóa minh chứng này?')) {
        return
    }

    axios
        .post(
            BASE_URL + '/sinh-vien/phieu-ren-luyen/xoa-minh-chung',
            { id: fileId },
            authHeader()
        )
        .then((res) => {
            if (!res.data.status) {
                toaster.error(res.data.message || 'Xóa minh chứng thất bại!')
                return
            }

            row.minh_chung = row.minh_chung.filter((file) => {
                return Number(file.id) !== Number(fileId)
            })

            toaster.success(res.data.message || 'Xóa minh chứng thành công!')
        })
        .catch((err) => {
            console.log(err.response?.data || err)
            toaster.error(err.response?.data?.message || 'Lỗi xóa minh chứng!')
        })
}

function openEvidence(file) {
    selectedEvidence.value = file
}

function downloadEvidence(file) {
    if (!file || !file.duong_dan) {
        toaster.error('Không tìm thấy đường dẫn file!')
        return
    }

    window.open(file.duong_dan, '_blank')
}

function isImageFile(file) {
    const name = String(file?.ten_file || '').toLowerCase()

    return (
        name.endsWith('.jpg') ||
        name.endsWith('.jpeg') ||
        name.endsWith('.png') ||
        name.endsWith('.webp')
    )
}

function isPdfFile(file) {
    const name = String(file?.ten_file || '').toLowerCase()

    return name.endsWith('.pdf')
}

function fileIcon(fileName) {
    const name = String(fileName || '').toLowerCase()

    if (name.endsWith('.pdf')) return 'fa-solid fa-file-pdf'
    if (name.endsWith('.doc') || name.endsWith('.docx')) return 'fa-solid fa-file-word'
    if (
        name.endsWith('.jpg') ||
        name.endsWith('.jpeg') ||
        name.endsWith('.png') ||
        name.endsWith('.webp')
    ) {
        return 'fa-solid fa-file-image'
    }

    return 'fa-solid fa-file'
}

function formatTrangThai(value) {
    const map = {
        nhap: 'Nháp',
        da_gui: 'Đã gửi',
        lop_da_danh_gia: 'Lớp đã đánh giá',
        co_van_da_kiem_tra: 'CVHT đã kiểm tra',
        hoan_thanh: 'Hoàn thành',
        tra_lai: 'Trả lại',
    }

    return map[value] || '—'
}

function formatTrangThaiMinhChung(value) {
    const map = {
        cho_duyet: 'Chờ duyệt',
        da_duyet: 'Đã duyệt',
        tu_choi: 'Từ chối',
    }

    return map[value] || '—'
}

function classifyScore(score) {
    const value = Number(score || 0)

    if (value >= 90) return 'Xuất sắc'
    if (value >= 80) return 'Tốt'
    if (value >= 65) return 'Khá'
    if (value >= 50) return 'Trung bình'
    if (value >= 35) return 'Yếu'

    return 'Kém'
}
</script>