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
import { computed, reactive, ref } from 'vue'

import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"
import "../../../assets/css/phieurenluyen.css"

const currentUser = reactive({
    id: 1,
    ho_ten: 'Nguyễn Văn A',
    email: 'sv001@student.edu.vn',
    so_dien_thoai: '0901234567',
    ngay_sinh: '15/01/2005',
    vai_tro_id: 6,
    trang_thai: 'hoat_dong',
})

const sinhVien = reactive({
    id: 1,
    ma_sinh_vien: 'SV001',
    nam_trung_tuyen: 2023,
    user_id: 1,
    lop_id: 1,

    nguoi_dung: currentUser,

    lop: {
        id: 1,
        ma_lop: 'D23CQCN01',
        ten_lop: 'Công nghệ thông tin 01',
        khoa_id: 1,
        nganh_id: 1,
        co_van_id: 2,
        lop_truong_id: 1,
        bi_thu_id: 3,

        khoa: {
            id: 1,
            ma_khoa: 'CNTT',
            ten_khoa: 'Công nghệ thông tin',
        },

        nganh: {
            id: 1,
            ma_nganh: 'KTPM',
            ten_nganh: 'Kỹ thuật phần mềm',
        },
    },
})

const dotDanhGia = reactive({
    id: 1,
    nam_hoc: '2025 - 2026',
    hoc_ky: 1,
    ngay_bat_dau: '2026-06-01 00:00:00',
    ngay_ket_thuc: '2026-06-30 23:59:59',
    trang_thai: 'dang_mo',
})

const phieu = reactive({
    id: 1,
    sinh_vien_id: sinhVien.id,
    dot_danh_gia_id: dotDanhGia.id,

    tong_diem_sv: 0,
    tong_diem_lop: 0,

    xep_loai_sv: null,
    xep_loai_lop: null,

    trang_thai: 'nhap',
})

const selectedEvidence = ref(null)

const hocKyLabel = computed(() => {
    if (dotDanhGia.hoc_ky === 1) return 'Kỳ I'
    if (dotDanhGia.hoc_ky === 2) return 'Kỳ II'
    return `Kỳ ${dotDanhGia.hoc_ky}`
})

/*
|--------------------------------------------------------------------------
| evaluationRows
|--------------------------------------------------------------------------
| Đây là dữ liệu đã map từ:
| - nhom_tieu_chi
| - tieu_chi
| - tieu_chi_lua_chon
| - chi_tiet_danh_gia
| - minh_chung
|--------------------------------------------------------------------------
*/

const evaluationRows = reactive([
    {
        row_key: 'nhom-1',
        row_type: 'section',
        nhom_id: 1,
        ma_hien_thi: 'I.',
        tt: 'I.',
        ten_hien_thi: 'Ý thức tham gia học tập',
        diem_toi_da_text: 'Từ 0÷20 điểm',
        editable: false,
    },

    {
        row_key: 'ct-1',
        row_type: 'criteria',

        chi_tiet_id: 1,
        phieu_id: phieu.id,
        tieu_chi_id: 1,

        nhom_id: 1,
        parent_id: null,

        ma_tieu_chi: 'I.1',
        tt: '1',
        ten_hien_thi: 'Ý thức và thái độ học tập, ý thức chuyên cần',

        diem_toi_da: 6,
        diem_toi_da_text: '6',

        loai_tieu_chi: 'cong',
        loai_nhap: 'combobox',
        bat_buoc_minh_chung: false,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,

        lua_chon: [
            {
                id: 1,
                tieu_chi_id: 1,
                noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ ≥ 9',
                diem_tuong_ung: 6,
            },
            {
                id: 2,
                tieu_chi_id: 1,
                noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ từ 7 đến cận 9',
                diem_tuong_ung: 5,
            },
            {
                id: 3,
                tieu_chi_id: 1,
                noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ từ 5 đến cận 7',
                diem_tuong_ung: 4,
            },
        ],

        minh_chung: [],
    },

    {
        row_key: 'lc-1-1',
        row_type: 'option',
        tt: 'a)',
        ten_hien_thi: 'Điểm TB đánh giá thường xuyên học kỳ ≥ 9',
        diem_toi_da_text: '6 điểm',
        editable: false,
    },

    {
        row_key: 'lc-1-2',
        row_type: 'option',
        tt: 'b)',
        ten_hien_thi: 'Điểm TB đánh giá thường xuyên học kỳ từ 7 đến cận 9',
        diem_toi_da_text: '5 điểm',
        editable: false,
    },

    {
        row_key: 'lc-1-3',
        row_type: 'option',
        tt: 'c)',
        ten_hien_thi: 'Điểm TB đánh giá thường xuyên học kỳ từ 5 đến cận 7',
        diem_toi_da_text: '4 điểm',
        editable: false,
    },

    {
        row_key: 'ct-2',
        row_type: 'criteria',

        chi_tiet_id: 2,
        phieu_id: phieu.id,
        tieu_chi_id: 2,

        nhom_id: 1,
        parent_id: null,

        ma_tieu_chi: 'I.2',
        tt: '2',
        ten_hien_thi: 'Ý thức và thái độ tham gia các hoạt động học thuật, NCKH, Olympic, cuộc thi chuyên môn nghiệp vụ từ cấp Khoa trở lên',

        diem_toi_da: 6,
        diem_toi_da_text: '6',

        loai_tieu_chi: 'cong',
        loai_nhap: 'textbox',
        bat_buoc_minh_chung: true,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,
        lua_chon: [],

        minh_chung: [],
    },

    {
        row_key: 'ct-3',
        row_type: 'criteria',

        chi_tiet_id: 3,
        phieu_id: phieu.id,
        tieu_chi_id: 3,

        nhom_id: 1,
        parent_id: null,

        ma_tieu_chi: 'I.3',
        tt: '3',
        ten_hien_thi: 'Xếp loại học tập học kỳ, căn cứ vào điểm trung bình chung học tập lần 1',

        diem_toi_da: 8,
        diem_toi_da_text: '8',

        loai_tieu_chi: 'cong',
        loai_nhap: 'combobox',
        bat_buoc_minh_chung: false,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,

        lua_chon: [
            {
                id: 4,
                tieu_chi_id: 3,
                noi_dung: 'Loại xuất sắc',
                diem_tuong_ung: 8,
            },
            {
                id: 5,
                tieu_chi_id: 3,
                noi_dung: 'Loại giỏi',
                diem_tuong_ung: 7,
            },
            {
                id: 6,
                tieu_chi_id: 3,
                noi_dung: 'Loại khá',
                diem_tuong_ung: 6,
            },
        ],

        minh_chung: [],
    },

    {
        row_key: 'nhom-2',
        row_type: 'section',
        nhom_id: 2,
        ma_hien_thi: 'II.',
        tt: 'II.',
        ten_hien_thi: 'Chấp hành tốt nội quy, quy chế và các quy định khác được áp dụng trong Học viện',
        diem_toi_da_text: 'Từ 0÷25 điểm',
        editable: false,
    },

    {
        row_key: 'ct-4',
        row_type: 'criteria',

        chi_tiet_id: 4,
        phieu_id: phieu.id,
        tieu_chi_id: 4,

        nhom_id: 2,
        parent_id: null,

        ma_tieu_chi: 'II.1',
        tt: '1',
        ten_hien_thi: 'Chấp hành tốt, không vi phạm',

        diem_toi_da: 25,
        diem_toi_da_text: '25',

        loai_tieu_chi: 'cong',
        loai_nhap: 'textbox',
        bat_buoc_minh_chung: false,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,
        lua_chon: [],
        minh_chung: [],
    },

    {
        row_key: 'nhom-3',
        row_type: 'section',
        nhom_id: 3,
        ma_hien_thi: 'III.',
        tt: 'III.',
        ten_hien_thi: 'Ý thức và kết quả tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao, phòng chống tệ nạn xã hội',
        diem_toi_da_text: 'Từ 0÷20 điểm',
        editable: false,
    },

    {
        row_key: 'ct-5',
        row_type: 'criteria',

        chi_tiet_id: 5,
        phieu_id: phieu.id,
        tieu_chi_id: 5,

        nhom_id: 3,
        parent_id: null,

        ma_tieu_chi: 'III.1',
        tt: '1',
        ten_hien_thi: 'Tham gia đầy đủ, tích cực các hoạt động chính trị, xã hội, các hoạt động tại giảng đường',

        diem_toi_da: 5,
        diem_toi_da_text: '5',

        loai_tieu_chi: 'cong',
        loai_nhap: 'combobox',
        bat_buoc_minh_chung: false,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,

        lua_chon: [
            {
                id: 7,
                tieu_chi_id: 5,
                noi_dung: 'Tham gia và chấp hành tốt các hoạt động',
                diem_tuong_ung: 5,
            },
            {
                id: 8,
                tieu_chi_id: 5,
                noi_dung: 'Vắng 01 buổi không có lý do',
                diem_tuong_ung: 3,
            },
            {
                id: 9,
                tieu_chi_id: 5,
                noi_dung: 'Không tham gia',
                diem_tuong_ung: 0,
            },
        ],

        minh_chung: [],
    },

    {
        row_key: 'ct-6',
        row_type: 'criteria',

        chi_tiet_id: 6,
        phieu_id: phieu.id,
        tieu_chi_id: 6,

        nhom_id: 3,
        parent_id: null,

        ma_tieu_chi: 'III.5',
        tt: '5',
        ten_hien_thi: 'Được khen thưởng, biểu dương trong các hoạt động tại mục III',

        diem_toi_da: 2,
        diem_toi_da_text: '2',

        loai_tieu_chi: 'cong',
        loai_nhap: 'textbox',
        bat_buoc_minh_chung: true,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,
        lua_chon: [],

        minh_chung: [],
    },

    {
        row_key: 'nhom-4',
        row_type: 'section',
        nhom_id: 4,
        ma_hien_thi: 'IV.',
        tt: 'IV.',
        ten_hien_thi: 'Ý thức công dân trong quan hệ cộng đồng',
        diem_toi_da_text: 'Từ 0÷25 điểm',
        editable: false,
    },

    {
        row_key: 'ct-7',
        row_type: 'criteria',

        chi_tiet_id: 7,
        phieu_id: phieu.id,
        tieu_chi_id: 7,

        nhom_id: 4,
        parent_id: null,

        ma_tieu_chi: 'IV.1',
        tt: '1',
        ten_hien_thi: 'Ý thức chấp hành và tham gia tuyên truyền chủ trương, đường lối của Đảng, chính sách pháp luật của Nhà nước, quy định nơi cư trú, an ninh trật tự, an toàn giao thông',

        diem_toi_da: 10,
        diem_toi_da_text: '10',

        loai_tieu_chi: 'cong',
        loai_nhap: 'combobox',
        bat_buoc_minh_chung: false,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,

        lua_chon: [
            {
                id: 10,
                tieu_chi_id: 7,
                noi_dung: 'Chấp hành đúng và tham gia tuyên truyền tốt, được khen thưởng',
                diem_tuong_ung: 10,
            },
            {
                id: 11,
                tieu_chi_id: 7,
                noi_dung: 'Chấp hành đúng và tham gia tuyên truyền tốt',
                diem_tuong_ung: 8,
            },
            {
                id: 12,
                tieu_chi_id: 7,
                noi_dung: 'Chấp hành đúng các quy định',
                diem_tuong_ung: 5,
            },
        ],

        minh_chung: [],
    },

    {
        row_key: 'ct-8',
        row_type: 'criteria',

        chi_tiet_id: 8,
        phieu_id: phieu.id,
        tieu_chi_id: 8,

        nhom_id: 4,
        parent_id: null,

        ma_tieu_chi: 'IV.2',
        tt: '2',
        ten_hien_thi: 'Tham gia các hoạt động nhân đạo, từ thiện vì cộng đồng, phong trào thanh niên tình nguyện',

        diem_toi_da: 10,
        diem_toi_da_text: '10',

        loai_tieu_chi: 'cong',
        loai_nhap: 'textbox',
        bat_buoc_minh_chung: true,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,
        lua_chon: [],
        minh_chung: [],
    },

    {
        row_key: 'ct-9',
        row_type: 'criteria',

        chi_tiet_id: 9,
        phieu_id: phieu.id,
        tieu_chi_id: 9,

        nhom_id: 4,
        parent_id: null,

        ma_tieu_chi: 'IV.3',
        tt: '3',
        ten_hien_thi: 'Ý thức xây dựng mối quan hệ đoàn kết với bạn bè và tập thể; xây dựng, bảo vệ cảnh quan giảng đường, nơi cư trú văn minh, sạch đẹp, văn hóa học đường',

        diem_toi_da: 5,
        diem_toi_da_text: '5',

        loai_tieu_chi: 'cong',
        loai_nhap: 'textbox',
        bat_buoc_minh_chung: false,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,
        lua_chon: [],
        minh_chung: [],
    },

    {
        row_key: 'nhom-5',
        row_type: 'section',
        nhom_id: 5,
        ma_hien_thi: 'V.',
        tt: 'V.',
        ten_hien_thi: 'Ý thức và kết quả tham gia Ban cán sự lớp, BCH Đoàn, Ban chủ nhiệm các Ban, CLB, Đội, Hội, Nhóm được thành lập theo quy định',
        diem_toi_da_text: 'Từ 0÷10 điểm',
        editable: false,
    },

    {
        row_key: 'ct-10',
        row_type: 'criteria',

        chi_tiet_id: 10,
        phieu_id: phieu.id,
        tieu_chi_id: 10,

        nhom_id: 5,
        parent_id: null,

        ma_tieu_chi: 'V.1',
        tt: '1',
        ten_hien_thi: 'Ý thức, tinh thần, thái độ, uy tín và hiệu quả công việc của sinh viên được phân công nhiệm vụ',

        diem_toi_da: 7,
        diem_toi_da_text: '7',

        loai_tieu_chi: 'cong',
        loai_nhap: 'textbox',
        bat_buoc_minh_chung: true,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,
        lua_chon: [],
        minh_chung: [],
    },

    {
        row_key: 'ct-11',
        row_type: 'criteria',

        chi_tiet_id: 11,
        phieu_id: phieu.id,
        tieu_chi_id: 11,

        nhom_id: 5,
        parent_id: null,

        ma_tieu_chi: 'V.2',
        tt: '2',
        ten_hien_thi: 'Sinh viên tham gia đầy đủ các hoạt động, sinh hoạt của lớp, khoa, Học viện, có ý kiến tham gia xây dựng tập thể vững mạnh',

        diem_toi_da: 3,
        diem_toi_da_text: '3',

        loai_tieu_chi: 'cong',
        loai_nhap: 'textbox',
        bat_buoc_minh_chung: false,

        lua_chon_id: null,
        gia_tri_nhap: null,

        diem_sv: 0,
        diem_lop: 0,
        ghi_chu: '',

        editable: true,
        lua_chon: [],
        minh_chung: [],
    },
])

const canEditStudentScore = computed(() => {
    return ['nhap', 'tra_lai'].includes(phieu.trang_thai)
})

const currentRoleId = computed(() => {
    return Number(currentUser.vai_tro_id)
})

const isNormalStudent = computed(() => {
    return currentRoleId.value === 6
})

const isClassOfficer = computed(() => {
    return [4, 5].includes(currentRoleId.value)
})

const canEditStudentInfo = computed(() => {
    return isNormalStudent.value && ['nhap', 'tra_lai'].includes(phieu.trang_thai)
})

const canManageEvidence = computed(() => {
    return ['nhap', 'tra_lai'].includes(phieu.trang_thai)
})

const hasClassScore = computed(() => {
    return [
        'lop_da_danh_gia',
        'co_van_da_kiem_tra',
        'hoan_thanh',
    ].includes(phieu.trang_thai)
})

const totalStudentScore = computed(() => {
    return evaluationRows
        .filter((row) => row.row_type === 'criteria')
        .reduce((sum, row) => sum + Number(row.diem_sv || 0), 0)
})

const totalClassScore = computed(() => {
    return evaluationRows
        .filter((row) => row.row_type === 'criteria')
        .reduce((sum, row) => sum + Number(row.diem_lop || 0), 0)
})

function rowClass(row) {
    return {
        'section-row': row.row_type === 'section',
        'criteria-row': row.row_type === 'criteria',
        'option-row': row.row_type === 'option',
    }
}

function normalizeScore(row, field) {
    let score = Number(row[field])
    const maxScore = Number(row.diem_toi_da || 0)

    if (Number.isNaN(score) || score < 0) {
        row[field] = 0
        return
    }

    if (score > maxScore) {
        row[field] = maxScore
        return
    }

    row[field] = score
}

function isCheckedOption(row, optionId) {
    if (!Array.isArray(row.lua_chon_da_chon)) {
        row.lua_chon_da_chon = []
    }

    return row.lua_chon_da_chon.includes(optionId)
}

function onChangeCheckboxChoice(row, option, event) {
    if (!Array.isArray(row.lua_chon_da_chon)) {
        row.lua_chon_da_chon = []
    }

    if (event.target.checked) {
        row.lua_chon_da_chon.push(option.id)
    } else {
        row.lua_chon_da_chon = row.lua_chon_da_chon.filter((id) => id !== option.id)
    }

    const total = row.lua_chon_da_chon.reduce((sum, optionId) => {
        const selected = row.lua_chon.find((item) => item.id === optionId)
        return sum + Number(selected?.diem_tuong_ung || 0)
    }, 0)

    row.diem_sv = Math.min(total, Number(row.diem_toi_da || 0))
}

function onChangeStudentChoice(row) {
    const selected = row.lua_chon.find((item) => item.id === row.lua_chon_id)

    if (!selected) {
        row.diem_sv = 0
        return
    }

    row.diem_sv = Math.min(
        Number(selected.diem_tuong_ung || 0),
        Number(row.diem_toi_da || 0)
    )
}

function classifyScore(score) {
    if (score >= 90) return 'Xuất sắc'
    if (score >= 80) return 'Tốt'
    if (score >= 65) return 'Khá'
    if (score >= 50) return 'Trung bình'
    if (score >= 35) return 'Yếu'
    return 'Kém'
}

function classifyKey(score) {
    if (score >= 90) return 'xuat_sac'
    if (score >= 80) return 'tot'
    if (score >= 65) return 'kha'
    if (score >= 50) return 'trung_binh'
    if (score >= 35) return 'yeu'
    return 'kem'
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
        hop_le: 'Hợp lệ',
        khong_hop_le: 'Không hợp lệ',
    }

    return map[value] || '—'
}

function fileIcon(fileName) {
    const value = String(fileName || '').toLowerCase()

    if (value.endsWith('.pdf')) return 'fa-solid fa-file-pdf'
    if (value.endsWith('.doc') || value.endsWith('.docx')) return 'fa-solid fa-file-word'
    if (value.endsWith('.xls') || value.endsWith('.xlsx')) return 'fa-solid fa-file-excel'

    if (
        value.endsWith('.jpg') ||
        value.endsWith('.jpeg') ||
        value.endsWith('.png') ||
        value.endsWith('.webp')
    ) {
        return 'fa-solid fa-file-image'
    }

    return 'fa-solid fa-file'
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

function uploadEvidence(event, row) {
    const file = event.target.files?.[0]

    if (!file) return

    if (!row.chi_tiet_id) {
        alert('Dòng tiêu chí này chưa có chi_tiet_id, không thể upload minh chứng.')
        event.target.value = ''
        return
    }

    if (!row.minh_chung) {
        row.minh_chung = []
    }

    row.minh_chung.push({
        id: Date.now(),

        chi_tiet_id: row.chi_tiet_id,
        nguoi_tai_id: currentUser.id,

        ten_file: file.name,
        duong_dan: URL.createObjectURL(file),

        trang_thai: 'cho_duyet',

        created_at: new Date().toLocaleDateString('vi-VN'),
        updated_at: new Date().toLocaleDateString('vi-VN'),

        raw_file: file,
    })

    event.target.value = ''
}

function removeEvidence(row, fileId) {
    if (!row.minh_chung) return

    const ok = confirm('Bạn có chắc muốn xóa minh chứng này không?')

    if (!ok) return

    row.minh_chung = row.minh_chung.filter((file) => file.id !== fileId)
}

function openEvidence(file) {
    selectedEvidence.value = file
}

function downloadEvidence(file) {
    if (!file) return

    if (!file.duong_dan) {
        alert(`Chưa có đường dẫn file: ${file.ten_file}`)
        return
    }

    const link = document.createElement('a')
    link.href = file.duong_dan
    link.download = file.ten_file
    link.click()
}

function syncPhieuScore() {
    phieu.tong_diem_sv = totalStudentScore.value
    phieu.xep_loai_sv = classifyKey(totalStudentScore.value)
}

function saveDraft() {
    syncPhieuScore()

    phieu.trang_thai = 'nhap'

    console.log('Payload lưu nháp đúng DB:', buildSavePayload())

    alert('Đã lưu nháp phiếu tự đánh giá.')
}

function submitEvaluation() {
    const ok = confirm(
        'Sau khi gửi, bạn sẽ không thể chỉnh sửa phiếu cho đến khi được trả lại. Bạn muốn gửi phiếu không?'
    )

    if (!ok) return

    syncPhieuScore()

    phieu.trang_thai = 'da_gui'

    console.log('Payload gửi phiếu đúng DB:', buildSavePayload())

    alert('Đã gửi phiếu đánh giá rèn luyện.')
}

function buildSavePayload() {
    return {
        phieu_ren_luyen: {
            id: phieu.id,
            sinh_vien_id: phieu.sinh_vien_id,
            dot_danh_gia_id: phieu.dot_danh_gia_id,
            tong_diem_sv: phieu.tong_diem_sv,
            tong_diem_lop: phieu.tong_diem_lop,
            xep_loai_sv: phieu.xep_loai_sv,
            xep_loai_lop: phieu.xep_loai_lop,
            trang_thai: phieu.trang_thai,
        },

        chi_tiet_danh_gia: evaluationRows
            .filter((row) => row.row_type === 'criteria')
            .map((row) => ({
                id: row.chi_tiet_id,
                phieu_id: row.phieu_id,
                tieu_chi_id: row.tieu_chi_id,
                lua_chon_id: row.lua_chon_id,
                gia_tri_nhap: row.gia_tri_nhap,
                diem_sv: row.diem_sv,
                diem_lop: row.diem_lop,
                ghi_chu: row.ghi_chu,
            })),

        minh_chung: evaluationRows
            .filter((row) => row.row_type === 'criteria')
            .flatMap((row) => row.minh_chung || []),
    }
}
</script>
