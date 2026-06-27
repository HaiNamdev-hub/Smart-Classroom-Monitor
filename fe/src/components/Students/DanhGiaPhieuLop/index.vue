<template>
    <div class="admin-content training-page class-evaluation-detail-page">

        <template v-if="selectedPhieu">
            <!-- TIÊU ĐỀ PAGE -->
            <div class="page-heading detail-heading">
                <div>
                    <h1>Đánh giá phiếu rèn luyện sinh viên</h1>
                    <p>
                        Lớp trưởng/Bí thư xem phiếu tự đánh giá, minh chứng và nhập điểm lớp đánh giá.
                    </p>
                </div>

                <button type="button" class="btn-ghost" @click="goBack">
                    <i class="fa-solid fa-arrow-left"></i>
                    Quay lại danh sách
                </button>
            </div>

            <!-- THỐNG KÊ NHANH -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon bg-blue">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div>
                        <span>Sinh viên</span>
                        <strong>{{ selectedPhieu.sinh_vien.nguoi_dung.ho_ten }}</strong>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon bg-amber">
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
                        <strong>{{ totalClassScore }}/100</strong>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon bg-red">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div>
                        <span>Trạng thái</span>
                        <strong>{{ formatTrangThai(selectedPhieu.trang_thai) }}</strong>
                    </div>
                </div>
            </div>

            <!-- STATUS -->
            <div class="detail-status-row text-nowrap mb-3">
                <span class="status-dot" style="width: 80px; height: auto"
                    :class="'status-' + selectedPhieu.trang_thai">
                    <span class="ms-3">{{ formatTrangThai(selectedPhieu.trang_thai) }}</span>
                </span>

                <span v-if="canEditClassScore" class="readonly-note ms-5">
                    Bạn đang ở chế độ đánh giá lớp. Cột Điểm SV bị khóa, chỉ sửa được cột Điểm Lớp.
                </span>

                <span v-else class="readonly-note">
                    Chế độ xem, không thể chỉnh sửa điểm lớp.
                </span>
            </div>

            <!-- PHIẾU -->
            <div class="evaluation-paper">

                <!-- HEADER PHIẾU -->
                <div class="paper-top">
                    <div class="paper-agency">
                        <strong>HỌC VIỆN HÀNH CHÍNH<br />VÀ QUẢN TRỊ CÔNG</strong>
                        <span>
                            PHÂN HIỆU HỌC VIỆN<br />
                            HÀNH CHÍNH VÀ QUẢN TRỊ CÔNG<br />
                            TẠI TỈNH QUẢNG NAM
                        </span>
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
                <div class="student-info-form">
                    <div class="info-line">
                        <label>Năm trúng tuyển:</label>
                        <input v-model="selectedPhieu.sinh_vien.nam_trung_tuyen" disabled />
                    </div>

                    <div class="info-line">
                        <label>Ngành/chuyên ngành:</label>
                        <input v-model="selectedPhieu.sinh_vien.lop.nganh.ten_nganh" disabled />
                    </div>

                    <div class="info-line">
                        <label>Khoa:</label>
                        <input v-model="selectedPhieu.sinh_vien.lop.khoa.ten_khoa" disabled />
                    </div>

                    <div class="info-line">
                        <label>Lớp:</label>
                        <input v-model="selectedPhieu.sinh_vien.lop.ma_lop" disabled />
                    </div>

                    <div class="info-line">
                        <label>Mã sinh viên:</label>
                        <input v-model="selectedPhieu.sinh_vien.ma_sinh_vien" disabled />
                    </div>

                    <div class="info-line">
                        <label>Họ và tên:</label>
                        <input v-model="selectedPhieu.sinh_vien.nguoi_dung.ho_ten" disabled />
                    </div>

                    <div class="info-line">
                        <label>Ngày sinh:</label>
                        <input v-model="selectedPhieu.sinh_vien.nguoi_dung.ngay_sinh" disabled />
                    </div>

                    <div class="info-line">
                        <label>Số điện thoại:</label>
                        <input v-model="selectedPhieu.sinh_vien.nguoi_dung.so_dien_thoai" disabled />
                    </div>

                    <div class="info-line">
                        <label>Học kỳ đánh giá:</label>
                        <input :value="hocKyLabel" disabled />
                    </div>

                    <div class="info-line">
                        <label>Năm học:</label>
                        <input v-model="selectedPhieu.dot_danh_gia.nam_hoc" disabled />
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
                                <td class="text-center">
                                    {{ row.tt }}
                                </td>

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
                                        Có yêu cầu minh chứng
                                    </div>
                                </td>

                                <td class="text-center">
                                    {{ row.diem_toi_da_text }}
                                </td>

                                <!-- ĐIỂM SV: CHỈ XEM -->
                                <td class="text-center">
                                    <input v-if="row.row_type === 'criteria'" v-model.number="row.diem_sv" type="number"
                                        class="score-input" disabled />

                                    <span v-else>—</span>
                                </td>

                                <!-- ĐIỂM LỚP: ĐƯỢC SỬA KHI DA_GUI -->
                                <td class="text-center">
                                    <template v-if="row.row_type === 'criteria'">

                                        <!-- COMBOBOX: chỉ chọn mức điểm có sẵn -->
                                        <select v-if="row.loai_nhap === 'combobox'" v-model.number="row.lua_chon_lop_id"
                                            class="score-select" :disabled="!canEditClassScore"
                                            @change="onChangeClassChoice(row)">
                                            <option :value="null">Chọn</option>

                                            <option v-for="option in row.lua_chon" :key="option.id" :value="option.id">
                                                {{ option.diem_tuong_ung }} điểm
                                            </option>
                                        </select>

                                        <!-- CHECKBOX: chọn nhiều ý, tự cộng điểm nhưng không vượt điểm tối đa -->
                                        <div v-else-if="row.loai_nhap === 'checkbox'" class="score-checkbox-group">
                                            <label v-for="option in row.lua_chon" :key="option.id"
                                                class="score-checkbox-item">
                                                <input type="checkbox" :value="option.id"
                                                    :checked="isCheckedClassOption(row, option.id)"
                                                    :disabled="!canEditClassScore"
                                                    @change="onChangeClassCheckboxChoice(row, option, $event)" />

                                                <span>{{ option.diem_tuong_ung }}đ</span>
                                            </label>
                                        </div>

                                        <!-- TEXTBOX: nhập số, tự ép trong khoảng 0 -> điểm tối đa -->
                                        <input v-else v-model.number="row.diem_lop" type="number" class="score-input"
                                            :min="0" :max="row.diem_toi_da" :disabled="!canEditClassScore"
                                            @input="normalizeScore(row, 'diem_lop')"
                                            @blur="normalizeScore(row, 'diem_lop')" />
                                    </template>

                                    <span v-else>—</span>
                                </td>

                                <!-- GHI CHÚ / MINH CHỨNG -->
                                <td>
                                    <div v-if="row.row_type === 'criteria'" class="evidence-box">

                                        <div v-if="row.minh_chung && row.minh_chung.length" class="evidence-list">
                                            <div v-for="file in row.minh_chung" :key="file.id" class="evidence-item">
                                                <div class="evidence-name">
                                                    <i :class="fileIcon(file.ten_file)"></i>
                                                    <span>{{ file.ten_file }}</span>
                                                </div>

                                                <div class="evidence-actions">
                                                    <button type="button" class="mini-btn" data-bs-toggle="modal"
                                                        data-bs-target="#classEvidenceModal"
                                                        @click="openEvidence(file)">
                                                        Xem
                                                    </button>

                                                    <button type="button" class="mini-btn"
                                                        @click="downloadEvidence(file)">
                                                        Tải
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else class="no-evidence-text">
                                            Chưa có minh chứng.
                                        </div>

                                        <textarea v-model="row.ghi_chu" class="note-input"
                                            :disabled="!canEditClassScore"
                                            placeholder="Ghi chú đánh giá của lớp..."></textarea>
                                    </div>

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
                                    <strong>{{ totalClassScore }}</strong>
                                </td>

                                <td>
                                    <strong>
                                        Xếp loại lớp đánh giá: {{ classifyScore(totalClassScore) }}
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
                        2. Cột Điểm SV tự đánh giá do sinh viên nhập, lớp trưởng/bí thư không được chỉnh sửa.
                    </p>
                    <p>
                        3. Cột Điểm Lớp đánh giá do lớp trưởng/bí thư nhập dựa trên phiếu và minh chứng sinh viên cung
                        cấp.
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
                        <strong>{{ classifyScore(totalClassScore) }}</strong>
                    </div>
                </div>

                <!-- CHỮ KÝ -->
                <div class="signature-area">
                    <div>
                        <strong>T/M LỚP</strong>
                        <span>LỚP TRƯỞNG / BÍ THƯ</span>
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
                    <span>Tổng điểm lớp đánh giá:</span>
                    <strong>{{ totalClassScore }}/100</strong>
                    <small>{{ classifyScore(totalClassScore) }}</small>
                </div>

                <div class="action-buttons">
                    <button type="button" class="btn-ghost" :disabled="!canEditClassScore" @click="saveClassDraft">
                        Lưu nháp
                    </button>

                    <button type="button" class="btn-primary" :disabled="!canEditClassScore" @click="submitClassReview">
                        Hoàn tất đánh giá
                    </button>
                </div>
            </div>

            <!-- MODAL XEM MINH CHỨNG -->
            <div class="modal fade" id="classEvidenceModal" tabindex="-1" aria-hidden="true">
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
        </template>

        <div v-else class="not-found-card">
            <h2>Không tìm thấy phiếu đánh giá</h2>
            <p>Phiếu không tồn tại hoặc bạn không có quyền truy cập.</p>
            <button type="button" class="btn-primary" @click="goBack">
                Quay lại danh sách
            </button>
        </div>

    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import "../../../assets/css/admin.css"
import "../../../assets/css/dark-theme.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const route = useRoute()
const router = useRouter()

const selectedEvidence = ref(null)

/*
|--------------------------------------------------------------------------
| USER ĐANG ĐĂNG NHẬP
|--------------------------------------------------------------------------
| 4 = lớp trưởng
| 5 = bí thư
| 6 = sinh viên
|--------------------------------------------------------------------------
*/
const currentUser = reactive({
    id: 1,
    ho_ten: 'Nguyễn Văn A',
    email: 'sv001@student.edu.vn',
    vai_tro_id: 4,
    trang_thai: 'hoat_dong',
})

const lopDangQuanLy = reactive({
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
})

const currentSinhVien = reactive({
    id: 1,
    ma_sinh_vien: 'SV001',
    nam_trung_tuyen: 2023,
    user_id: currentUser.id,
    lop_id: lopDangQuanLy.id,
    nguoi_dung: currentUser,
    lop: lopDangQuanLy,
})

const dotDanhGia = reactive({
    id: 1,
    nam_hoc: '2025 - 2026',
    hoc_ky: 1,
    ngay_bat_dau: '2026-06-01 00:00:00',
    ngay_ket_thuc: '2026-06-30 23:59:59',
    trang_thai: 'dang_mo',
})

/*
|--------------------------------------------------------------------------
| OPTIONS THEO PHIẾU WORD
|--------------------------------------------------------------------------
*/
const optionSets = {
    tc1: [
        { id: 1, tieu_chi_id: 1, noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ ≥ 9', diem_tuong_ung: 6 },
        { id: 2, tieu_chi_id: 1, noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ từ 7 đến cận 9', diem_tuong_ung: 5 },
        { id: 3, tieu_chi_id: 1, noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ từ 5 đến cận 7', diem_tuong_ung: 4 },
        { id: 4, tieu_chi_id: 1, noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ từ 4 đến cận 5', diem_tuong_ung: 2 },
        { id: 5, tieu_chi_id: 1, noi_dung: 'Điểm TB đánh giá thường xuyên học kỳ từ 01 đến cận 04', diem_tuong_ung: 1 },
    ],

    tc2: [
        { id: 21, tieu_chi_id: 2, noi_dung: 'Tham gia đầy đủ hoạt động, sự kiện liên quan đến NCKH, học thuật, chuyên môn', diem_tuong_ung: 2 },
        { id: 22, tieu_chi_id: 2, noi_dung: 'Có công bố khoa học hoặc tham gia cuộc thi SV NCKH, Olympic', diem_tuong_ung: 2 },
        { id: 23, tieu_chi_id: 2, noi_dung: 'Đạt giải trong các cuộc thi SV NCKH, Olympic', diem_tuong_ung: 2 },
    ],

    tc3: [
        { id: 31, tieu_chi_id: 3, noi_dung: 'Loại xuất sắc', diem_tuong_ung: 8 },
        { id: 32, tieu_chi_id: 3, noi_dung: 'Loại Giỏi', diem_tuong_ung: 7 },
        { id: 33, tieu_chi_id: 3, noi_dung: 'Loại Khá', diem_tuong_ung: 6 },
        { id: 34, tieu_chi_id: 3, noi_dung: 'Loại Trung bình', diem_tuong_ung: 4 },
        { id: 35, tieu_chi_id: 3, noi_dung: 'Loại Yếu nhưng chưa bị cảnh báo kết quả học tập', diem_tuong_ung: 2 },
        { id: 36, tieu_chi_id: 3, noi_dung: 'Loại Yếu nhưng bị cảnh báo kết quả học tập lần 1', diem_tuong_ung: 1 },
    ],

    tc5: [
        { id: 51, tieu_chi_id: 5, noi_dung: 'Tham gia và chấp hành tốt các hoạt động', diem_tuong_ung: 5 },
        { id: 52, tieu_chi_id: 5, noi_dung: 'Vắng 01 buổi không có lý do', diem_tuong_ung: 3 },
        { id: 53, tieu_chi_id: 5, noi_dung: 'Vắng 02 buổi không có lý do', diem_tuong_ung: 2 },
        { id: 54, tieu_chi_id: 5, noi_dung: 'Vắng từ 02 buổi trở lên không có lý do hoặc không tham gia', diem_tuong_ung: 0 },
    ],

    tc7: [
        { id: 71, tieu_chi_id: 7, noi_dung: 'Chấp hành đúng và tham gia tuyên truyền tốt, được khen thưởng', diem_tuong_ung: 10 },
        { id: 72, tieu_chi_id: 7, noi_dung: 'Chấp hành đúng và tham gia tuyên truyền tốt', diem_tuong_ung: 8 },
        { id: 73, tieu_chi_id: 7, noi_dung: 'Chấp hành đúng các quy định', diem_tuong_ung: 5 },
        { id: 74, tieu_chi_id: 7, noi_dung: 'Bị nhắc nhở, lập biên bản do vi phạm các quy định', diem_tuong_ung: 0 },
    ],

    tc8: [
        { id: 81, tieu_chi_id: 8, noi_dung: 'Tích cực, nhiệt tình tham gia các hoạt động đạt hiệu quả được khen thưởng', diem_tuong_ung: 10 },
        { id: 82, tieu_chi_id: 8, noi_dung: 'Tham gia tích cực các hoạt động được lớp hoặc tập thể ghi nhận', diem_tuong_ung: 8 },
        { id: 83, tieu_chi_id: 8, noi_dung: 'Có ý thức tham gia hoặc hưởng ứng các hoạt động', diem_tuong_ung: 5 },
        { id: 84, tieu_chi_id: 8, noi_dung: 'Tham gia các hoạt động nhưng gây mất đoàn kết', diem_tuong_ung: 0 },
        { id: 85, tieu_chi_id: 8, noi_dung: 'Không tham gia', diem_tuong_ung: 0 },
    ],

    tc9: [
        { id: 91, tieu_chi_id: 9, noi_dung: 'Có ý thức xây dựng tập thể lớp đoàn kết, giữ gìn giảng đường, nơi cư trú văn minh, sạch đẹp', diem_tuong_ung: 5 },
        { id: 92, tieu_chi_id: 9, noi_dung: 'Bị nhắc nhở hoặc kiểm điểm 1 lần', diem_tuong_ung: 1 },
        { id: 93, tieu_chi_id: 9, noi_dung: 'Bị nhắc nhở hoặc kiểm điểm 2 lần', diem_tuong_ung: 0 },
    ],

    tc10: [
        { id: 101, tieu_chi_id: 10, noi_dung: 'Hoàn thành xuất sắc nhiệm vụ', diem_tuong_ung: 7 },
        { id: 102, tieu_chi_id: 10, noi_dung: 'Hoàn thành tốt nhiệm vụ', diem_tuong_ung: 6 },
        { id: 103, tieu_chi_id: 10, noi_dung: 'Hoàn thành nhiệm vụ', diem_tuong_ung: 4 },
        { id: 104, tieu_chi_id: 10, noi_dung: 'Không hoàn thành nhiệm vụ', diem_tuong_ung: 0 },
    ],
}

const phieuId = computed(() => {
    return Number(route.params.phieuId || 2)
})

const mode = computed(() => {
    return route.query.mode || 'edit'
})

const isOfficer = computed(() => {
    const roleId = Number(currentUser.vai_tro_id)

    return (
        [4, 5].includes(roleId) &&
        (
            currentSinhVien.id === lopDangQuanLy.lop_truong_id ||
            currentSinhVien.id === lopDangQuanLy.bi_thu_id
        )
    )
})

const phieuRenLuyenList = ref([
    {
        id: 2,
        sinh_vien_id: 2,
        dot_danh_gia_id: dotDanhGia.id,

        tong_diem_sv: 81,
        tong_diem_lop: 0,

        xep_loai_sv: 'tot',
        xep_loai_lop: null,

        trang_thai: 'da_gui',

        created_at: '2026-06-11 08:00:00',
        updated_at: '2026-06-12 10:30:00',

        sinh_vien: {
            id: 2,
            ma_sinh_vien: 'SV002',
            nam_trung_tuyen: 2023,
            user_id: 2,
            lop_id: 1,

            nguoi_dung: {
                id: 2,
                ho_ten: 'Trần Thị Bình',
                email: 'sv002@student.edu.vn',
                so_dien_thoai: '0912345678',
                ngay_sinh: '20/03/2005',
                vai_tro_id: 6,
                trang_thai: 'hoat_dong',
            },

            lop: lopDangQuanLy,
        },

        dot_danh_gia: dotDanhGia,

        evaluationRows: [
            {
                row_key: 'nhom-1',
                row_type: 'section',
                tt: 'I.',
                ten_hien_thi: 'Ý thức tham gia học tập',
                diem_toi_da_text: 'Từ 0÷20 điểm',
            },
            {
                row_key: 'ct-1',
                row_type: 'criteria',
                chi_tiet_id: 1,
                phieu_id: 2,
                tieu_chi_id: 1,

                tt: '1',
                ten_hien_thi: 'Ý thức và thái độ học tập, ý thức chuyên cần',

                diem_toi_da: 6,
                diem_toi_da_text: '6',

                loai_tieu_chi: 'cong',
                loai_nhap: 'combobox',
                bat_buoc_minh_chung: false,

                lua_chon_id: 1,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 6,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: optionSets.tc1,
                minh_chung: [],
            },
            {
                row_key: 'ct-2',
                row_type: 'criteria',
                chi_tiet_id: 2,
                phieu_id: 2,
                tieu_chi_id: 2,

                tt: '2',
                ten_hien_thi: 'Ý thức tham gia các hoạt động học thuật, NCKH, Olympic, cuộc thi chuyên môn nghiệp vụ từ cấp Khoa trở lên',

                diem_toi_da: 6,
                diem_toi_da_text: '6',

                loai_tieu_chi: 'cong',
                loai_nhap: 'checkbox',
                bat_buoc_minh_chung: true,

                lua_chon_id: null,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 6,
                diem_lop: 0,
                ghi_chu: 'Có tham gia hoạt động NCKH cấp khoa.',

                lua_chon: optionSets.tc2,

                minh_chung: [
                    {
                        id: 1,
                        chi_tiet_id: 2,
                        nguoi_tai_id: 2,
                        ten_file: 'giay_xac_nhan_nckh.pdf',
                        duong_dan: '/minh-chung/giay_xac_nhan_nckh.pdf',
                        trang_thai: 'cho_duyet',
                        created_at: '12/06/2026',
                        updated_at: '12/06/2026',
                    },
                ],
            },
            {
                row_key: 'ct-3',
                row_type: 'criteria',
                chi_tiet_id: 3,
                phieu_id: 2,
                tieu_chi_id: 3,

                tt: '3',
                ten_hien_thi: 'Xếp loại học tập học kỳ, căn cứ vào điểm trung bình chung học tập lần 1',

                diem_toi_da: 8,
                diem_toi_da_text: '8',

                loai_tieu_chi: 'cong',
                loai_nhap: 'combobox',
                bat_buoc_minh_chung: false,

                lua_chon_id: 31,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 8,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: optionSets.tc3,
                minh_chung: [],
            },

            {
                row_key: 'nhom-2',
                row_type: 'section',
                tt: 'II.',
                ten_hien_thi: 'Chấp hành tốt nội quy, quy chế và các quy định khác được áp dụng trong Học viện',
                diem_toi_da_text: 'Từ 0÷25 điểm',
            },
            {
                row_key: 'ct-4',
                row_type: 'criteria',
                chi_tiet_id: 4,
                phieu_id: 2,
                tieu_chi_id: 4,

                tt: '1',
                ten_hien_thi: 'Chấp hành tốt, không vi phạm',

                diem_toi_da: 25,
                diem_toi_da_text: '25',

                loai_tieu_chi: 'cong',
                loai_nhap: 'textbox',
                bat_buoc_minh_chung: false,

                lua_chon_id: null,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 25,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: [],
                minh_chung: [],
            },

            {
                row_key: 'nhom-3',
                row_type: 'section',
                tt: 'III.',
                ten_hien_thi: 'Ý thức và kết quả tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao, phòng chống tệ nạn xã hội',
                diem_toi_da_text: 'Từ 0÷20 điểm',
            },
            {
                row_key: 'ct-5',
                row_type: 'criteria',
                chi_tiet_id: 5,
                phieu_id: 2,
                tieu_chi_id: 5,

                tt: '1',
                ten_hien_thi: 'Tham gia đầy đủ, tích cực các hoạt động chính trị, xã hội, các hoạt động tại giảng đường',

                diem_toi_da: 5,
                diem_toi_da_text: '5',

                loai_tieu_chi: 'cong',
                loai_nhap: 'combobox',
                bat_buoc_minh_chung: false,

                lua_chon_id: 51,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 5,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: optionSets.tc5,
                minh_chung: [],
            },
            {
                row_key: 'ct-6',
                row_type: 'criteria',
                chi_tiet_id: 6,
                phieu_id: 2,
                tieu_chi_id: 6,

                tt: '5',
                ten_hien_thi: 'Được khen thưởng, biểu dương trong các hoạt động tại mục III',

                diem_toi_da: 2,
                diem_toi_da_text: '2',

                loai_tieu_chi: 'cong',
                loai_nhap: 'textbox',
                bat_buoc_minh_chung: true,

                lua_chon_id: null,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 1,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: [],

                minh_chung: [
                    {
                        id: 2,
                        chi_tiet_id: 6,
                        nguoi_tai_id: 2,
                        ten_file: 'giay_khen_hoat_dong_doan.jpg',
                        duong_dan: '/minh-chung/giay_khen_hoat_dong_doan.jpg',
                        trang_thai: 'cho_duyet',
                        created_at: '12/06/2026',
                        updated_at: '12/06/2026',
                    },
                ],
            },

            {
                row_key: 'nhom-4',
                row_type: 'section',
                tt: 'IV.',
                ten_hien_thi: 'Ý thức công dân trong quan hệ cộng đồng',
                diem_toi_da_text: 'Từ 0÷25 điểm',
            },
            {
                row_key: 'ct-7',
                row_type: 'criteria',
                chi_tiet_id: 7,
                phieu_id: 2,
                tieu_chi_id: 7,

                tt: '1',
                ten_hien_thi: 'Ý thức chấp hành và tham gia tuyên truyền chủ trương, đường lối của Đảng, chính sách pháp luật của Nhà nước',

                diem_toi_da: 10,
                diem_toi_da_text: '10',

                loai_tieu_chi: 'cong',
                loai_nhap: 'combobox',
                bat_buoc_minh_chung: false,

                lua_chon_id: 71,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 10,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: optionSets.tc7,
                minh_chung: [],
            },
            {
                row_key: 'ct-8',
                row_type: 'criteria',
                chi_tiet_id: 8,
                phieu_id: 2,
                tieu_chi_id: 8,

                tt: '2',
                ten_hien_thi: 'Tham gia các hoạt động nhân đạo, từ thiện vì cộng đồng, phong trào thanh niên tình nguyện',

                diem_toi_da: 10,
                diem_toi_da_text: '10',

                loai_tieu_chi: 'cong',
                loai_nhap: 'combobox',
                bat_buoc_minh_chung: true,

                lua_chon_id: 82,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 8,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: optionSets.tc8,
                minh_chung: [],
            },
            {
                row_key: 'ct-9',
                row_type: 'criteria',
                chi_tiet_id: 9,
                phieu_id: 2,
                tieu_chi_id: 9,

                tt: '3',
                ten_hien_thi: 'Ý thức xây dựng mối quan hệ đoàn kết với bạn bè và tập thể',

                diem_toi_da: 5,
                diem_toi_da_text: '5',

                loai_tieu_chi: 'cong',
                loai_nhap: 'combobox',
                bat_buoc_minh_chung: false,

                lua_chon_id: 91,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 5,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: optionSets.tc9,
                minh_chung: [],
            },

            {
                row_key: 'nhom-5',
                row_type: 'section',
                tt: 'V.',
                ten_hien_thi: 'Ý thức và kết quả tham gia Ban cán sự lớp, BCH Đoàn, Ban chủ nhiệm các Ban, CLB, Đội, Hội, Nhóm',
                diem_toi_da_text: 'Từ 0÷10 điểm',
            },
            {
                row_key: 'ct-10',
                row_type: 'criteria',
                chi_tiet_id: 10,
                phieu_id: 2,
                tieu_chi_id: 10,

                tt: '1',
                ten_hien_thi: 'Ý thức, tinh thần, thái độ, uy tín và hiệu quả công việc của sinh viên được phân công nhiệm vụ',

                diem_toi_da: 7,
                diem_toi_da_text: '7',

                loai_tieu_chi: 'cong',
                loai_nhap: 'combobox',
                bat_buoc_minh_chung: false,

                lua_chon_id: 103,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 4,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: optionSets.tc10,
                minh_chung: [],
            },
            {
                row_key: 'ct-11',
                row_type: 'criteria',
                chi_tiet_id: 11,
                phieu_id: 2,
                tieu_chi_id: 11,

                tt: '2',
                ten_hien_thi: 'Sinh viên tham gia đầy đủ các hoạt động, sinh hoạt của lớp, khoa, Học viện',

                diem_toi_da: 3,
                diem_toi_da_text: '3',

                loai_tieu_chi: 'cong',
                loai_nhap: 'textbox',
                bat_buoc_minh_chung: false,

                lua_chon_id: null,
                lua_chon_lop_id: null,
                lua_chon_lop_da_chon: [],

                gia_tri_nhap: null,
                diem_sv: 3,
                diem_lop: 0,
                ghi_chu: '',

                lua_chon: [],
                minh_chung: [],
            },
        ],
    },
])

const selectedPhieu = computed(() => {
    return phieuRenLuyenList.value.find((item) => item.id === phieuId.value) || null
})

const evaluationRows = computed(() => {
    return selectedPhieu.value?.evaluationRows || []
})

const hocKyLabel = computed(() => {
    const hocKy = selectedPhieu.value?.dot_danh_gia?.hoc_ky

    if (hocKy === 1) return 'Kỳ I'
    if (hocKy === 2) return 'Kỳ II'

    return hocKy ? `Kỳ ${hocKy}` : '—'
})

const canEditClassScore = computed(() => {
    return (
        isOfficer.value &&
        mode.value === 'edit' &&
        selectedPhieu.value?.trang_thai === 'da_gui'
    )
})

const totalStudentScore = computed(() => {
    const total = evaluationRows.value
        .filter((row) => row.row_type === 'criteria')
        .reduce((sum, row) => sum + getSignedScore(row, 'diem_sv'), 0)

    return Math.min(100, Math.max(0, total))
})

const totalClassScore = computed(() => {
    const total = evaluationRows.value
        .filter((row) => row.row_type === 'criteria')
        .reduce((sum, row) => sum + getSignedScore(row, 'diem_lop'), 0)

    return Math.min(100, Math.max(0, total))
})

function getSignedScore(row, field) {
    const score = Number(row[field] || 0)

    if (row.loai_tieu_chi === 'phat') {
        return -Math.abs(score)
    }

    return score
}

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

function onChangeClassChoice(row) {
    const options = Array.isArray(row.lua_chon) ? row.lua_chon : []
    const selected = options.find((item) => item.id === row.lua_chon_lop_id)

    if (!selected) {
        row.diem_lop = 0
        return
    }

    row.diem_lop = Math.min(
        Number(selected.diem_tuong_ung || 0),
        Number(row.diem_toi_da || 0)
    )
}

function isCheckedClassOption(row, optionId) {
    if (!Array.isArray(row.lua_chon_lop_da_chon)) {
        row.lua_chon_lop_da_chon = []
    }

    return row.lua_chon_lop_da_chon.includes(optionId)
}

function onChangeClassCheckboxChoice(row, option, event) {
    if (!Array.isArray(row.lua_chon_lop_da_chon)) {
        row.lua_chon_lop_da_chon = []
    }

    if (event.target.checked) {
        if (!row.lua_chon_lop_da_chon.includes(option.id)) {
            row.lua_chon_lop_da_chon.push(option.id)
        }
    } else {
        row.lua_chon_lop_da_chon = row.lua_chon_lop_da_chon.filter((id) => id !== option.id)
    }

    const options = Array.isArray(row.lua_chon) ? row.lua_chon : []

    const total = row.lua_chon_lop_da_chon.reduce((sum, optionId) => {
        const selected = options.find((item) => item.id === optionId)
        return sum + Number(selected?.diem_tuong_ung || 0)
    }, 0)

    row.diem_lop = Math.min(total, Number(row.diem_toi_da || 0))
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

function syncClassScore() {
    if (!selectedPhieu.value) return

    selectedPhieu.value.tong_diem_lop = totalClassScore.value
    selectedPhieu.value.xep_loai_lop = classifyKey(totalClassScore.value)
}

function saveClassDraft() {
    syncClassScore()

    console.log('Payload lưu nháp điểm lớp:', buildReviewPayload())

    alert('Đã lưu nháp điểm lớp đánh giá.')
}

function submitClassReview() {
    const ok = confirm('Bạn có chắc muốn hoàn tất đánh giá lớp cho phiếu này không?')

    if (!ok) return

    syncClassScore()

    selectedPhieu.value.trang_thai = 'lop_da_danh_gia'

    console.log('Payload hoàn tất đánh giá lớp:', buildReviewPayload())

    alert('Đã hoàn tất đánh giá lớp.')

    goBack()
}

function buildReviewPayload() {
    if (!selectedPhieu.value) return null

    return {
        phieu_ren_luyen: {
            id: selectedPhieu.value.id,
            sinh_vien_id: selectedPhieu.value.sinh_vien_id,
            dot_danh_gia_id: selectedPhieu.value.dot_danh_gia_id,
            tong_diem_sv: selectedPhieu.value.tong_diem_sv,
            tong_diem_lop: selectedPhieu.value.tong_diem_lop,
            xep_loai_sv: selectedPhieu.value.xep_loai_sv,
            xep_loai_lop: selectedPhieu.value.xep_loai_lop,
            trang_thai: selectedPhieu.value.trang_thai,
        },

        chi_tiet_danh_gia: evaluationRows.value
            .filter((row) => row.row_type === 'criteria')
            .map((row) => ({
                id: row.chi_tiet_id,
                phieu_id: row.phieu_id,
                tieu_chi_id: row.tieu_chi_id,

                lua_chon_id: row.lua_chon_id || null,
                lua_chon_lop_id: row.lua_chon_lop_id || null,
                lua_chon_lop_da_chon: row.lua_chon_lop_da_chon || [],

                gia_tri_nhap: row.gia_tri_nhap,
                diem_sv: Number(row.diem_sv || 0),
                diem_lop: Number(row.diem_lop || 0),
                ghi_chu: row.ghi_chu,
            })),

        lich_su_kiem_tra: {
            phieu_id: selectedPhieu.value.id,
            nguoi_kiem_tra_id: currentUser.id,
            vai_tro_kiem_tra:
                currentSinhVien.id === lopDangQuanLy.lop_truong_id
                    ? 'lop_truong'
                    : 'bi_thu',
            hanh_dong: 'danh_gia',
            ghi_chu: 'Lớp đã đánh giá phiếu rèn luyện.',
        },
    }
}

function goBack() {
    router.push("/sinh-vien/danh-sach-phieu-lop")
}
</script>
<style scoped>
.student-evaluation-page {
    padding-bottom: 110px;
}

/* fallback nếu hệ thống chỉ dùng html.dark-theme mà chưa set data-theme */
:global(html.dark-theme) .student-evaluation-page {
    --bg-main: #0b1120;
    --bg-sidebar: #111827;
    --bg-card: #1e293b;
    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
    --border-color: rgba(255, 255, 255, 0.08);
    --accent: #dc2626;
}

/* SUMMARY */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 18px;
}

.summary-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 18px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
}

.summary-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 18px;
}

.summary-card span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
    margin-bottom: 4px;
}

.summary-card strong {
    display: block;
    color: var(--text-primary);
    font-size: 22px;
    font-weight: 800;
}

.bg-blue {
    background: rgba(37, 99, 235, 0.12);
    color: #2563eb;
}

.bg-green {
    background: rgba(34, 197, 94, 0.12);
    color: #16a34a;
}

.bg-amber {
    background: rgba(245, 158, 11, 0.14);
    color: #d97706;
}

.bg-red {
    background: rgba(220, 38, 38, 0.12);
    color: #dc2626;
}

/* STATUS */
.detail-status-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}

.student-evaluation-page .status-dot {
    width: auto !important;
    height: auto !important;
    min-height: 34px;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 800;
    line-height: 1;
    white-space: nowrap;
    border: 1px solid transparent;
}

.status-nhap {
    background: rgba(99, 102, 241, 0.16);
    color: #4f46e5;
    border-color: rgba(99, 102, 241, 0.32);
}

.status-da_gui {
    background: rgba(245, 158, 11, 0.16);
    color: #b45309;
    border-color: rgba(245, 158, 11, 0.32);
}

.status-lop_da_danh_gia {
    background: rgba(59, 130, 246, 0.16);
    color: #2563eb;
    border-color: rgba(59, 130, 246, 0.32);
}

.status-co_van_da_kiem_tra {
    background: rgba(34, 197, 94, 0.16);
    color: #15803d;
    border-color: rgba(34, 197, 94, 0.32);
}

.status-hoan_thanh {
    background: rgba(20, 184, 166, 0.16);
    color: #0f766e;
    border-color: rgba(20, 184, 166, 0.32);
}

.status-tra_lai {
    background: rgba(239, 68, 68, 0.16);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, 0.32);
}

:global(html.dark-theme) .status-nhap,
:global([data-theme="dark"]) .status-nhap {
    background: rgba(129, 140, 248, 0.22);
    color: #c7d2fe;
    border-color: rgba(129, 140, 248, 0.42);
}

:global(html.dark-theme) .status-da_gui,
:global([data-theme="dark"]) .status-da_gui {
    background: rgba(251, 191, 36, 0.22);
    color: #fde68a;
    border-color: rgba(251, 191, 36, 0.42);
}

:global(html.dark-theme) .status-lop_da_danh_gia,
:global([data-theme="dark"]) .status-lop_da_danh_gia {
    background: rgba(96, 165, 250, 0.22);
    color: #bfdbfe;
    border-color: rgba(96, 165, 250, 0.42);
}

:global(html.dark-theme) .status-co_van_da_kiem_tra,
:global([data-theme="dark"]) .status-co_van_da_kiem_tra {
    background: rgba(74, 222, 128, 0.20);
    color: #bbf7d0;
    border-color: rgba(74, 222, 128, 0.42);
}

:global(html.dark-theme) .status-hoan_thanh,
:global([data-theme="dark"]) .status-hoan_thanh {
    background: rgba(45, 212, 191, 0.20);
    color: #99f6e4;
    border-color: rgba(45, 212, 191, 0.42);
}

:global(html.dark-theme) .status-tra_lai,
:global([data-theme="dark"]) .status-tra_lai {
    background: rgba(248, 113, 113, 0.22);
    color: #fecaca;
    border-color: rgba(248, 113, 113, 0.42);
}

.readonly-note {
    color: var(--text-secondary);
    font-size: 14px;
}

/* ALERT */
.student-alert {
    background: rgba(37, 99, 235, 0.10);
    border: 1px solid rgba(37, 99, 235, 0.20);
    color: var(--text-primary);
    border-radius: 16px;
    padding: 14px 16px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 18px;
    font-size: 14px;
    line-height: 1.55;
}

.student-alert i {
    margin-top: 3px;
    color: #2563eb;
}

/* PAPER */
.evaluation-paper {
    background: var(--bg-card);
    color: var(--text-primary);
    border-radius: 18px;
    padding: 28px;
    border: 1px solid var(--border-color);
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
    font-family: "Times New Roman", Times, serif;
}

/* HEADER PHIẾU */
.paper-top {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    text-align: center;
    margin-bottom: 20px;
}

.paper-agency,
.paper-party {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 15px;
    line-height: 1.35;
}

.paper-party strong {
    text-decoration: underline;
    text-underline-offset: 4px;
}

.paper-title {
    text-align: center;
    margin-bottom: 22px;
}

.paper-title p {
    margin: 0 0 6px;
    text-align: left;
    font-weight: 700;
}

.paper-title h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: var(--text-primary);
}

.paper-title span {
    display: block;
    margin-top: 6px;
    font-size: 14px;
    font-style: italic;
    color: var(--text-secondary);
}

/* THÔNG TIN SINH VIÊN */
.student-info-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px 24px;
    margin-bottom: 20px;
    font-size: 15px;
}

.info-line {
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-line label {
    min-width: 155px;
    font-weight: 700;
    color: var(--text-primary);
}

.info-line input {
    flex: 1;
    border: none;
    border-bottom: 1px dotted var(--text-secondary);
    border-radius: 0;
    padding: 3px 4px;
    background: transparent;
    color: var(--text-primary);
    font-family: "Times New Roman", Times, serif;
    outline: none;
}

.info-line input:disabled {
    opacity: 0.85;
    cursor: not-allowed;
}

/* TABLE */
.official-table-wrap {
    width: 100%;
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.official-table {
    width: 100%;
    min-width: 1120px;
    border-collapse: collapse;
    font-size: 14px;
    line-height: 1.35;
}

.official-table th,
.official-table td {
    border: 1px solid var(--border-color);
    padding: 7px 8px;
    vertical-align: top;
}

.official-table th {
    text-align: center;
    font-weight: 800;
    background: rgba(148, 163, 184, 0.10);
    color: var(--text-primary);
}

.col-tt {
    width: 58px;
}

.col-content {
    width: 430px;
}

.col-score {
    width: 105px;
}

.col-note {
    width: 300px;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.section-row td {
    background: rgba(148, 163, 184, 0.08);
    font-weight: 700;
}

.option-row td {
    font-size: 13.5px;
    color: var(--text-secondary);
}

.criteria-row td {
    background: transparent;
}

/* INPUT */
.score-input {
    width: 66px;
    height: 34px;
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-sidebar);
    color: var(--text-primary);
    font-weight: 800;
    outline: none;
}

.score-input:focus {
    border-color: rgba(220, 38, 38, 0.55);
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
}

.score-input:disabled,
.note-input:disabled {
    opacity: 0.68;
    cursor: not-allowed;
}

.note-input {
    width: 100%;
    min-height: 56px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 7px;
    resize: vertical;
    background: var(--bg-sidebar);
    color: var(--text-primary);
    font-family: Arial, sans-serif;
    font-size: 13px;
    outline: none;
}

.note-input::placeholder {
    color: var(--text-secondary);
}

.note-input:focus {
    border-color: rgba(220, 38, 38, 0.55);
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
}

/* MINH CHỨNG */
.evidence-box {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.evidence-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.evidence-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border: 1px dashed var(--border-color);
    border-radius: 8px;
    padding: 7px;
    background: rgba(148, 163, 184, 0.08);
    font-family: Arial, sans-serif;
    font-size: 13px;
}

.evidence-name {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
    color: var(--text-primary);
}

.evidence-name span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.evidence-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
    flex-wrap: wrap;
}

.mini-btn {
    border: 1px solid rgba(37, 99, 235, 0.45);
    color: #2563eb;
    background: rgba(37, 99, 235, 0.08);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.mini-btn:hover {
    background: #2563eb;
    color: #ffffff;
}

.mini-btn.danger {
    border-color: rgba(220, 38, 38, 0.45);
    color: #dc2626;
    background: rgba(220, 38, 38, 0.08);
}

.mini-btn.danger:hover {
    background: #dc2626;
    color: #ffffff;
}

.upload-mini {
    width: fit-content;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px dashed rgba(37, 99, 235, 0.55);
    color: #2563eb;
    background: rgba(37, 99, 235, 0.08);
    border-radius: 8px;
    padding: 6px 10px;
    font-family: Arial, sans-serif;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.upload-mini:hover {
    background: rgba(37, 99, 235, 0.14);
}

/* TOTAL */
.total-row td {
    background: rgba(220, 38, 38, 0.08);
    font-weight: 800;
}

/* NOTE CUỐI PHIẾU */
.paper-note {
    margin-top: 18px;
    font-size: 14px;
    line-height: 1.55;
    color: var(--text-primary);
}

.paper-note p {
    margin: 5px 0;
}

.rank-area {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 18px;
    font-size: 15px;
}

.signature-area {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-top: 32px;
    text-align: center;
    font-size: 15px;
}

.signature-area div {
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-height: 90px;
}

/* ACTION BAR */
.review-action-bar {
    position: sticky;
    bottom: 0;
    z-index: 20;
    margin-top: 18px;
    padding: 14px 18px;
    border-radius: 18px 18px 0 0;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    box-shadow: 0 -10px 30px rgba(15, 23, 42, 0.10);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    font-family: "Segoe UI", system-ui, sans-serif;
}

.review-action-bar span,
.review-action-bar small {
    color: var(--text-secondary);
}

.review-action-bar strong {
    margin: 0 8px;
    font-size: 24px;
    color: var(--text-primary);
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-primary:disabled,
.btn-ghost:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* MODAL PREVIEW */
.modal-subtitle {
    margin: 4px 0 0;
    color: var(--text-secondary);
    font-size: 13px;
}

.evidence-preview-wrapper {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.evidence-preview-frame {
    width: 100%;
    min-height: 520px;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    background: var(--bg-sidebar);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.evidence-image {
    max-width: 100%;
    max-height: 720px;
    object-fit: contain;
    display: block;
}

.evidence-pdf {
    width: 100%;
    height: 720px;
    border: none;
    background: #ffffff;
}

.no-preview-box {
    min-height: 360px;
    border-radius: 12px;
    border: 1px dashed var(--border-color);
    background: var(--bg-sidebar);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    text-align: center;
    padding: 24px;
}

.no-preview-box i {
    font-size: 54px;
    margin-bottom: 14px;
}

.no-preview-box h3 {
    font-size: 18px;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.preview-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.preview-meta div {
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 12px;
    background: var(--bg-sidebar);
}

.preview-meta span {
    display: block;
    color: var(--text-secondary);
    font-size: 13px;
    margin-bottom: 4px;
}

.preview-meta strong {
    color: var(--text-primary);
    font-size: 14px;
    word-break: break-word;
}

.score-select {
    width: 88px;
    height: 34px;
    text-align: center;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background: var(--bg-sidebar);
    color: var(--text-primary);
    font-weight: 800;
    outline: none;
}

.required-evidence {
    width: fit-content;
    margin-top: 6px;
    padding: 4px 8px;
    border-radius: 999px;
    background: rgba(220, 38, 38, 0.1);
    color: #dc2626;
    font-family: "Segoe UI", system-ui, sans-serif;
    font-size: 12px;
    font-weight: 800;
}

.score-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: flex-start;
}

.score-checkbox-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-family: "Segoe UI", system-ui, sans-serif;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-primary);
    cursor: pointer;
}

.score-checkbox-item input {
    width: 14px;
    height: 14px;
    cursor: pointer;
}

.score-checkbox-item input:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 992px) {

    .student-info-form,
    .paper-top,
    .rank-area,
    .signature-area {
        grid-template-columns: 1fr;
    }

    .review-action-bar {
        flex-direction: column;
        align-items: flex-start;
    }

    .action-buttons {
        width: 100%;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .preview-meta {
        grid-template-columns: 1fr;
    }

    .evidence-preview-frame {
        min-height: 360px;
    }

    .evidence-pdf {
        height: 520px;
    }
}

@media (max-width: 640px) {
    .summary-grid {
        grid-template-columns: 1fr;
    }

    .evaluation-paper {
        padding: 16px;
    }

    .info-line {
        flex-direction: column;
        align-items: flex-start;
    }

    .info-line label {
        min-width: unset;
    }

    .info-line input {
        width: 100%;
    }

    .action-buttons {
        justify-content: stretch;
    }

    .action-buttons button {
        flex: 1;
    }
}
</style>