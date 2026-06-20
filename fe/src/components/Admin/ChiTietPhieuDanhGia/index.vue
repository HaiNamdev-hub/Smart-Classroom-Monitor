<template>
    <div class="admin-content training-page evaluation-detail-page">

        <!-- TIÊU ĐỀ PAGE -->
        <div class="page-heading">
            <h1>Chi tiết phiếu đánh giá</h1>
            <p>Kiểm tra phiếu rèn luyện của sinh viên theo mẫu Phụ lục 01.</p>
        </div>

        <!-- Quay lại ds -->
        <div class="detail-toolbar">
            <button type="button" class="btn btn-primary" @click="router.push({ name: 'AdminDSPhieuChoDuyet' })">
                Quay lại danh sách
            </button>
        </div>

        <!-- THỐNG KÊ NHANH -->
        <div class="detail-status-row">
            <span class="status-dot" :class="'st-' + formInfo.trang_thai">
                {{ formatTrangThai(formInfo.trang_thai) }}
            </span>

            <span v-if="isReadOnly" class="readonly-note">
                Chế độ xem, không thể chỉnh sửa điểm.
            </span>
        </div>

        <!-- PHIẾU GIẤY -->
        <div class="evaluation-paper">

            <!-- HEADER PHIẾU -->
            <div class="paper-top">
                <div class="paper-agency">
                    <strong>HỌC VIỆN HÀNH CHÍNH<br />VÀ QUẢN TRỊ CÔNG</strong>
                    <span>PHÂN HIỆU HỌC VIỆN<br />HÀNH CHÍNH VÀ QUẢN TRỊ CÔNG<br />TẠI TỈNH QUẢNG NAM</span>
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
                    (Kèm theo Quyết định số 4185/QĐ - HCQG ngày 08 tháng 9 năm 2023
                    của Giám đốc Học viện Hành chính Quốc gia)
                </span>
            </div>

            <!-- THÔNG TIN SINH VIÊN -->
            <div class="student-info-form">
                <div class="info-line">
                    <label>Năm trúng tuyển:</label>
                    <select v-model="student.nam_trung_tuyen" disabled>
                        <option>2023</option>
                        <option>2024</option>
                        <option>2025</option>
                    </select>
                </div>

                <div class="info-line">
                    <label>Ngành/chuyên ngành:</label>
                    <select v-model="student.nganh" disabled>
                        <option>Kỹ thuật phần mềm</option>
                        <option>Hệ thống thông tin</option>
                    </select>
                </div>

                <div class="info-line">
                    <label>Khoa:</label>
                    <select v-model="student.khoa" disabled>
                        <option>Công nghệ thông tin</option>
                    </select>
                </div>

                <div class="info-line">
                    <label>Lớp:</label>
                    <select v-model="student.lop" disabled>
                        <option>D23CQCN01</option>
                        <option>D23CQCN02</option>
                    </select>
                </div>

                <div class="info-line">
                    <label>Mã sinh viên:</label>
                    <input v-model="student.ma_sinh_vien" disabled />
                </div>

                <div class="info-line">
                    <label>Họ và tên:</label>
                    <input v-model="student.ho_ten" disabled />
                </div>

                <div class="info-line">
                    <label>Ngày sinh:</label>
                    <input v-model="student.ngay_sinh" disabled />
                </div>

                <div class="info-line">
                    <label>Số điện thoại:</label>
                    <input v-model="student.so_dien_thoai" disabled />
                </div>

                <div class="info-line">
                    <label>Đánh giá kết quả rèn luyện học kỳ:</label>
                    <select v-model="formInfo.hoc_ky" disabled>
                        <option>Kỳ I</option>
                        <option>Kỳ II</option>
                    </select>
                </div>

                <div class="info-line">
                    <label>Năm học:</label>
                    <select v-model="formInfo.nam_hoc" disabled>
                        <option>2025 - 2026</option>
                        <option>2026 - 2027</option>
                    </select>
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
                            <th class="col-note">Ghi chú<br />(minh chứng các hoạt động)</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="row in evaluationRows" :key="row.id" :class="rowClass(row)">
                            <td class="text-center">{{ row.tt }}</td>

                            <td>
                                <strong v-if="row.type === 'section'">{{ row.content }}</strong>
                                <span v-else>{{ row.content }}</span>
                            </td>

                            <td class="text-center">
                                {{ row.max_text }}
                            </td>

                            <td class="text-center">
                                <span v-if="row.editable">{{ row.student_score }}</span>
                            </td>

                            <td class="text-center">
                                <input v-if="row.editable" v-model.number="row.class_score" type="number"
                                    class="score-input" :min="0" :max="row.max_score" :disabled="isReadOnly"
                                    @input="normalizeScore(row)" />

                                <span v-else-if="row.type === 'section'">—</span>
                            </td>

                            <td>
                                <div v-if="row.evidences && row.evidences.length" class="evidence-list">
                                    <div v-for="file in row.evidences" :key="file.id" class="evidence-item">
                                        <div class="evidence-name">
                                            <i :class="fileIcon(file.ten_file)"></i>
                                            <span>{{ file.ten_file }}</span>
                                        </div>

                                        <div class="evidence-actions">
                                            <button type="button" class="mini-btn" @click="openEvidence(file)"
                                                data-bs-toggle="modal" data-bs-target="#evidenceModal">
                                                Xem
                                            </button>

                                            <button type="button" class="mini-btn" @click="downloadEvidence(file)">
                                                Tải
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <textarea v-if="row.editable" v-model="row.note" class="note-input"
                                    :disabled="isReadOnly" placeholder="Ghi chú của CVHT/lớp..."></textarea>

                                <span v-if="!row.editable && row.note">{{ row.note }}</span>
                            </td>
                        </tr>

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
                                <strong>Xếp loại: {{ classifyScore(totalClassScore) }}</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- GHI CHÚ -->
            <div class="paper-note">
                <p><strong>Ghi chú:</strong></p>
                <p>
                    1. Điểm rèn luyện là tổng của 5 mục đánh giá trên bằng 100 điểm.
                </p>
                <p>
                    2. Kết quả rèn luyện được phân thành các loại:
                    Xuất sắc, Tốt, Khá, Trung bình, Yếu, Kém.
                </p>
                <p>
                    3. Sinh viên không thực hiện đánh giá hoặc nộp phiếu tự đánh giá không đúng hạn
                    sẽ bị xếp loại rèn luyện loại yếu, kém.
                </p>
            </div>

            <!-- XẾP LOẠI CUỐI PHIẾU -->
            <div class="rank-area">
                <div>
                    Sinh viên tự đánh giá, xếp loại:
                    <strong>{{ classifyScore(totalStudentScore) }}</strong>
                </div>

                <div>
                    Lớp/CVHT đánh giá, xếp loại:
                    <strong>{{ classifyScore(totalClassScore) }}</strong>
                </div>
            </div>

            <!-- CHỮ KÝ -->
            <div class="signature-area">
                <div>
                    <strong>T/M LỚP</strong>
                    <span>LỚP TRƯỞNG</span>
                </div>

                <div>
                    <span>Hà Nội, ngày .... tháng .... năm ......</span>
                    <strong>SINH VIÊN</strong>
                </div>
            </div>
        </div>

        <!-- THANH NÚT CUỐI FORM -->
        <div v-if="!isReadOnly" class="review-action-bar">
            <div>
                <span>Tổng điểm lớp/CVHT:</span>
                <strong>{{ totalClassScore }}/100</strong>
                <small>{{ classifyScore(totalClassScore) }}</small>
            </div>

            <div class="action-buttons">
                <button type="button" class="btn btn-secondary" @click="saveDraft">
                    Lưu nháp
                </button>

                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#rejectModal">
                    Trả lại
                </button>

                <button type="button" class="btn btn-primary" @click="approveEvaluation">
                    Duyệt phiếu
                </button>
            </div>
        </div>

        <!-- MODAL XEM MINH CHỨNG -->
        <div class="modal fade" id="evidenceModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h1 class="modal-title fs-5">Xem minh chứng</h1>
                            <p class="text-sm text-gray-500 mb-0">
                                {{ selectedEvidence?.ten_file }}
                            </p>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div v-if="selectedEvidence" class="evidence-preview-wrapper">

                            <!-- ẢNH -->
                            <div v-if="isImageFile(selectedEvidence)" class="evidence-preview-frame">
                                <img :src="selectedEvidence.duong_dan" :alt="selectedEvidence.ten_file"
                                    class="evidence-image" />
                            </div>

                            <!-- PDF -->
                            <div v-else-if="isPdfFile(selectedEvidence)" class="evidence-preview-frame">
                                <iframe :src="selectedEvidence.duong_dan" class="evidence-pdf"
                                    title="PDF preview"></iframe>
                            </div>

                            <!-- FILE KHÁC -->
                            <div v-else class="no-preview-box">
                                <i :class="fileIcon(selectedEvidence.ten_file)"></i>

                                <h3>{{ selectedEvidence.ten_file }}</h3>

                                <p>
                                    File này không hỗ trợ xem trực tiếp trong modal.
                                    Vui lòng tải về để xem.
                                </p>
                            </div>

                            <!-- INFO FILE -->
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
                                    <strong>{{ formatTrangThaiMinhChung(selectedEvidence.trang_thai) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Đóng
                        </button>

                        <button type="button" class="btn btn-primary" @click="downloadEvidence(selectedEvidence)">
                            Tải file
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL Trả lại -->
        <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form @submit.prevent="rejectEvaluation">
                        <div class="modal-header">
                            <div>
                                <h1 class="modal-title fs-5">Trả lại phiếu đánh giá</h1>
                                <p class="text-sm text-gray-500 mb-0">
                                    Nhập lý do để sinh viên chỉnh sửa lại phiếu.
                                </p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="form-group">
                                <label>Lý do Trả lại <span class="req">*</span></label>
                                <textarea v-model="rejectReason"
                                    placeholder="VD: Minh chứng tiêu chí III.5 chưa hợp lệ..."></textarea>
                                <span v-if="rejectError" class="form-error">{{ rejectError }}</span>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Hủy
                            </button>

                            <button type="submit" class="btn btn-primary">
                                Xác nhận Trả lại
                            </button>
                        </div>
                    </form>
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

const route = useRoute()
const router = useRouter()

const student = reactive({
    nam_trung_tuyen: '2023',
    nganh: 'Kỹ thuật phần mềm',
    khoa: 'Công nghệ thông tin',
    lop: 'D23CQCN01',
    ma_sinh_vien: 'SV001',
    ho_ten: 'Nguyễn Văn A',
    ngay_sinh: '15/01/2005',
    so_dien_thoai: '0901234567',
})

const formInfo = reactive({
    hoc_ky: 'Kỳ I',
    nam_hoc: '2025 - 2026',
    trang_thai: 'lop_da_danh_gia',
})

const isReadOnly = computed(() => {
    const mode = route.query.mode

    return (
        mode === 'view' ||
        formInfo.trang_thai === 'co_van_da_kiem_tra' ||
        formInfo.trang_thai === 'hoan_thanh' ||
        formInfo.trang_thai === 'tra_lai'
    )
})

const fakeEvaluationDetails = {
    1: {
        student: {
            nam_trung_tuyen: '2023',
            nganh: 'Kỹ thuật phần mềm',
            khoa: 'Công nghệ thông tin',
            lop: 'D23CQCN01',
            ma_sinh_vien: 'SV001',
            ho_ten: 'Nguyễn Văn A',
            ngay_sinh: '15/01/2005',
            so_dien_thoai: '0901234567',
        },
        formInfo: {
            hoc_ky: 'Kỳ I',
            nam_hoc: '2025 - 2026',
            trang_thai: 'lop_da_danh_gia',
        },
    },

    2: {
        student: {
            nam_trung_tuyen: '2023',
            nganh: 'Kỹ thuật phần mềm',
            khoa: 'Công nghệ thông tin',
            lop: 'D23CQCN01',
            ma_sinh_vien: 'SV002',
            ho_ten: 'Trần Thị Bình',
            ngay_sinh: '20/03/2005',
            so_dien_thoai: '0912345678',
        },
        formInfo: {
            hoc_ky: 'Kỳ I',
            nam_hoc: '2025 - 2026',
            trang_thai: 'da_gui',
        },
    },

    3: {
        student: {
            nam_trung_tuyen: '2023',
            nganh: 'Hệ thống thông tin',
            khoa: 'Công nghệ thông tin',
            lop: 'D23CQCN02',
            ma_sinh_vien: 'SV003',
            ho_ten: 'Lê Văn Cường',
            ngay_sinh: '11/07/2005',
            so_dien_thoai: '0987654321',
        },
        formInfo: {
            hoc_ky: 'Kỳ I',
            nam_hoc: '2025 - 2026',
            trang_thai: 'lop_da_danh_gia',
        },
    },

    4: {
        student: {
            nam_trung_tuyen: '2023',
            nganh: 'Hệ thống thông tin',
            khoa: 'Công nghệ thông tin',
            lop: 'D23CQCN02',
            ma_sinh_vien: 'SV004',
            ho_ten: 'Phạm Thị Dung',
            ngay_sinh: '03/02/2005',
            so_dien_thoai: '0934567890',
        },
        formInfo: {
            hoc_ky: 'Kỳ I',
            nam_hoc: '2025 - 2026',
            trang_thai: 'co_van_da_kiem_tra',
        },
    },

    5: {
        student: {
            nam_trung_tuyen: '2023',
            nganh: 'Kỹ thuật phần mềm',
            khoa: 'Công nghệ thông tin',
            lop: 'D23CQCN01',
            ma_sinh_vien: 'SV005',
            ho_ten: 'Đỗ Văn Hùng',
            ngay_sinh: '09/09/2005',
            so_dien_thoai: '0967891234',
        },
        formInfo: {
            hoc_ky: 'Kỳ I',
            nam_hoc: '2025 - 2026',
            trang_thai: 'tra_lai',
        },
    },
}

function loadEvaluationDetail() {
    const id = route.params.id
    const detail = fakeEvaluationDetails[id]

    if (!detail) {
        router.push({
            name: 'AdminDSPhieuChoDuyet',
        })
        return
    }

    Object.assign(student, detail.student)
    Object.assign(formInfo, detail.formInfo)
}

watch(
    () => route.params.id,
    () => {
        loadEvaluationDetail()
    },
    { immediate: true }
)

const selectedEvidence = ref(null)
const rejectReason = ref('')
const rejectError = ref('')

const evaluationRows = reactive([
    {
        id: 'I',
        type: 'section',
        tt: 'I.',
        content: 'Ý thức tham gia học tập (căn cứ vào số tín chỉ trong 01 học kỳ ≥ số tín chỉ tối thiểu theo quy định của Học viện)',
        max_text: 'Từ 0÷20 điểm',
        note: 'Tổng: 1 + 2 + 3 = 20',
    },
    {
        id: 'I-1',
        type: 'criteria',
        tt: '1',
        content: 'Ý thức và thái độ học tập (ý thức chuyên cần)',
        max_text: '6',
        max_score: 6,
        student_score: 6,
        class_score: 6,
        editable: true,
        note: '',
        evidences: [],
    },
    {
        id: 'I-1-a',
        type: 'option',
        tt: 'a)',
        content: 'Điểm TB đánh giá thường xuyên học kỳ ≥ 9 (6 điểm)',
        max_text: '6',
    },
    {
        id: 'I-1-b',
        type: 'option',
        tt: 'b)',
        content: 'Điểm TB đánh giá thường xuyên học kỳ từ 7 đến cận 9 (5 điểm)',
        max_text: '5',
    },
    {
        id: 'I-1-c',
        type: 'option',
        tt: 'c)',
        content: 'Điểm TB đánh giá thường xuyên học kỳ từ 5 đến cận 7 (4 điểm)',
        max_text: '4',
    },
    {
        id: 'I-1-d',
        type: 'option',
        tt: 'd)',
        content: 'Điểm TB đánh giá thường xuyên học kỳ từ 4 đến cận 5 (2 điểm)',
        max_text: '2',
    },
    {
        id: 'I-1-e',
        type: 'option',
        tt: 'e)',
        content: 'Điểm TB đánh giá thường xuyên học kỳ từ 01 đến cận 04 (1 điểm)',
        max_text: '1',
    },
    {
        id: 'I-2',
        type: 'criteria',
        tt: '2',
        content: 'Ý thức và thái độ tham gia các hoạt động học thuật, hoạt động NCKH, thi Olympic các cấp và các cuộc thi chuyên môn nghiệp vụ từ cấp Khoa trở lên.',
        max_text: '6',
        max_score: 6,
        student_score: 6,
        class_score: 5,
        editable: true,
        note: 'Có minh chứng.',
        evidences: [
            {
                id: 1,
                ten_file: 'giay_xac_nhan_nckh.pdf',
                duong_dan: '/minh-chung/giay_xac_nhan_nckh.pdf',
                trang_thai: 'cho_duyet',
                created_at: '10/06/2026',
            },
        ],
    },
    {
        id: 'I-2-a',
        type: 'option',
        tt: 'a)',
        content: 'Tham gia đầy đủ hoạt động, sự kiện liên quan đến NCKH, học thuật, chuyên môn',
        max_text: '2',
        note: 'Có minh chứng file đính kèm',
    },
    {
        id: 'I-2-b',
        type: 'option',
        tt: 'b)',
        content: 'Có công bố khoa học tại Hội nghị hoặc tạp chí khoa học chuyên ngành hoặc tham gia các cuộc thi SV NCKH, Olympic',
        max_text: '2',
        note: 'Có minh chứng file đính kèm',
    },
    {
        id: 'I-2-c',
        type: 'option',
        tt: 'c)',
        content: 'Đạt giải trong các cuộc thi SV NCKH, Olympic',
        max_text: '2',
        note: 'Có minh chứng file đính kèm',
    },
    {
        id: 'I-3',
        type: 'criteria',
        tt: '3',
        content: 'Xếp loại học tập học kỳ, căn cứ vào điểm trung bình chung học tập lần 1',
        max_text: '8',
        max_score: 8,
        student_score: 8,
        class_score: 8,
        editable: true,
        note: '',
        evidences: [],
    },

    {
        id: 'II',
        type: 'section',
        tt: 'II.',
        content: 'Chấp hành tốt nội quy, quy chế và các quy định khác được áp dụng trong Học viện',
        max_text: 'Từ 0÷25 điểm',
    },
    {
        id: 'II-1',
        type: 'criteria',
        tt: '1',
        content: 'Chấp hành tốt, không vi phạm',
        max_text: '25',
        max_score: 25,
        student_score: 25,
        class_score: 25,
        editable: true,
        note: 'Sinh viên tự nhập vào.',
        evidences: [],
    },
    {
        id: 'II-2',
        type: 'option',
        tt: '2',
        content: 'Phần trừ điểm: Sinh viên vi phạm một số lỗi trong nội quy, quy chế, quy định',
        max_text: '',
        note: 'Mỗi mục tạo 01 textbox để sinh viên nhập điểm.',
    },
    {
        id: 'II-2-a',
        type: 'option',
        tt: '-',
        content: 'Không tham gia học tập đầy đủ, nghiêm túc nghị quyết, nội quy, quy chế, tuần sinh hoạt công dân - sinh viên hoặc bài thu hoạch không đạt.',
        max_text: 'Trừ 10 điểm',
    },
    {
        id: 'II-2-b',
        type: 'option',
        tt: '-',
        content: 'Nghỉ không lý do các chuyên đề tuần sinh hoạt công dân - sinh viên đầu khóa, đầu năm, cuối khóa.',
        max_text: 'Trừ 3 điểm/1 buổi',
    },
    {
        id: 'II-2-c',
        type: 'option',
        tt: '-',
        content: 'Không tham gia các buổi sinh hoạt lớp, họp, hội nghị, giao ban, tập huấn và các hoạt động khác do Học viện yêu cầu.',
        max_text: 'Trừ 5 điểm/1 buổi',
    },
    {
        id: 'II-2-d',
        type: 'option',
        tt: '-',
        content: 'Vi phạm quy chế thi ở mức cảnh cáo hoặc trừ điểm thi nhưng chưa đến mức bị đình chỉ thi.',
        max_text: 'Trừ 10 điểm/1 lần',
    },

    {
        id: 'III',
        type: 'section',
        tt: 'III',
        content: 'Đánh giá về ý thức và kết quả tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao, phòng chống tệ nạn xã hội',
        max_text: 'Từ 0÷20 điểm',
    },
    {
        id: 'III-1',
        type: 'criteria',
        tt: '1',
        content: 'Tham gia đầy đủ, tích cực các hoạt động chính trị, xã hội, các hoạt động tại giảng đường.',
        max_text: '5',
        max_score: 5,
        student_score: 5,
        class_score: 5,
        editable: true,
        note: '',
        evidences: [],
    },
    {
        id: 'III-2',
        type: 'criteria',
        tt: '2',
        content: 'Ý thức tham gia các hoạt động văn hóa, văn nghệ, thể thao do Học viện/Phân viện, các tổ chức đoàn thể phát động.',
        max_text: '5',
        max_score: 5,
        student_score: 5,
        class_score: 4,
        editable: true,
        note: '',
        evidences: [
            {
                id: 2,
                ten_file: 'minh_chung_van_nghe.jpg',
                duong_dan: '/minh-chung/minh_chung_van_nghe.jpg',
                trang_thai: 'cho_duyet',
                created_at: '11/06/2026',
            },
        ],
    },
    {
        id: 'III-3',
        type: 'criteria',
        tt: '3',
        content: 'Tham gia các câu lạc bộ, Đội, Nhóm được tổ chức theo quy định.',
        max_text: '5',
        max_score: 5,
        student_score: 3,
        class_score: 3,
        editable: true,
        note: '',
        evidences: [],
    },
    {
        id: 'III-4',
        type: 'criteria',
        tt: '4',
        content: 'Tham gia tuyên truyền, phòng chống tội phạm và các TNXH.',
        max_text: '3',
        max_score: 3,
        student_score: 3,
        class_score: 3,
        editable: true,
        note: '',
        evidences: [],
    },
    {
        id: 'III-5',
        type: 'criteria',
        tt: '5',
        content: 'Được khen thưởng, biểu dương trong các hoạt động tại mục III.',
        max_text: '2',
        max_score: 2,
        student_score: 2,
        class_score: 2,
        editable: true,
        note: 'File đính kèm.',
        evidences: [
            {
                id: 3,
                ten_file: 'giay_khen_hoat_dong_doan.pdf',
                duong_dan: '/minh-chung/giay_khen_hoat_dong_doan.pdf',
                trang_thai: 'cho_duyet',
                created_at: '11/06/2026',
            },
        ],
    },

    {
        id: 'IV',
        type: 'section',
        tt: 'IV',
        content: 'Đánh giá về ý thức công dân trong quan hệ cộng đồng',
        max_text: 'Từ 0÷25 điểm',
    },
    {
        id: 'IV-1',
        type: 'criteria',
        tt: '1',
        content: 'Ý thức chấp hành và tham gia tuyên truyền chủ trương, đường lối của Đảng, chính sách pháp luật của Nhà nước, quy định nơi cư trú, an ninh trật tự, an toàn giao thông.',
        max_text: '10',
        max_score: 10,
        student_score: 10,
        class_score: 8,
        editable: true,
        note: '',
        evidences: [],
    },
    {
        id: 'IV-2',
        type: 'criteria',
        tt: '2',
        content: 'Tham gia các hoạt động nhân đạo, từ thiện vì cộng đồng, phong trào thanh niên tình nguyện.',
        max_text: '10',
        max_score: 10,
        student_score: 10,
        class_score: 10,
        editable: true,
        note: 'File đính kèm.',
        evidences: [
            {
                id: 4,
                ten_file: 'xac_nhan_tinh_nguyen.pdf',
                duong_dan: '/minh-chung/xac_nhan_tinh_nguyen.pdf',
                trang_thai: 'cho_duyet',
                created_at: '12/06/2026',
            },
        ],
    },
    {
        id: 'IV-3',
        type: 'criteria',
        tt: '3',
        content: 'Ý thức xây dựng mối quan hệ đoàn kết với bạn bè và tập thể; xây dựng, bảo vệ cảnh quan giảng đường, nơi cư trú văn minh, sạch đẹp, văn hóa học đường.',
        max_text: '5',
        max_score: 5,
        student_score: 5,
        class_score: 5,
        editable: true,
        note: '',
        evidences: [],
    },

    {
        id: 'V',
        type: 'section',
        tt: 'V.',
        content: 'Đánh giá về ý thức và kết quả tham gia Ban cán sự lớp, BCH Đoàn, Ban chủ nhiệm các Ban, CLB, Đội, Hội, Nhóm được thành lập theo quy định.',
        max_text: 'Từ 0÷10 điểm',
        note: 'Mỗi sinh viên chỉ được tối đa 10 điểm cho mục này.',
    },
    {
        id: 'V-1',
        type: 'option',
        tt: '1',
        content: 'BCS lớp, BCH các tổ chức Đảng, Đoàn thanh niên, Hội sinh viên, chi bộ sinh viên, các CLB và các tổ chức khác.',
        max_text: '7',
    },
    {
        id: 'V-2-a',
        type: 'criteria',
        tt: '2a',
        content: 'Sinh viên tham gia đầy đủ các hoạt động, sinh hoạt của lớp, khoa, Học viện, có ý kiến tham gia xây dựng tập thể vững mạnh.',
        max_text: '3',
        max_score: 3,
        student_score: 3,
        class_score: 3,
        editable: true,
        note: '',
        evidences: [],
    },
    {
        id: 'V-2-b',
        type: 'criteria',
        tt: '2b',
        content: 'Sinh viên đạt được các thành tích đặc biệt trong học tập, rèn luyện, dũng cảm cứu người được cấp giấy chứng nhận hoặc có giấy khen.',
        max_text: '7',
        max_score: 7,
        student_score: 5,
        class_score: 5,
        editable: true,
        note: 'File đính kèm.',
        evidences: [
            {
                id: 5,
                ten_file: 'giay_khen_cap_khoa.pdf',
                duong_dan: '/minh-chung/giay_khen_cap_khoa.pdf',
                trang_thai: 'cho_duyet',
                created_at: '12/06/2026',
            },
        ],
    },
])

const totalStudentScore = computed(() => {
    return evaluationRows
        .filter((row) => row.editable)
        .reduce((sum, row) => sum + Number(row.student_score || 0), 0)
})

const totalClassScore = computed(() => {
    return evaluationRows
        .filter((row) => row.editable)
        .reduce((sum, row) => sum + Number(row.class_score || 0), 0)
})

function rowClass(row) {
    return {
        'section-row': row.type === 'section',
        'criteria-row': row.type === 'criteria',
        'option-row': row.type === 'option',
    }
}

function normalizeScore(row) {
    let score = Number(row.class_score)

    if (Number.isNaN(score) || score < 0) {
        row.class_score = 0
        return
    }

    if (score > row.max_score) {
        row.class_score = row.max_score
    }
}

function classifyScore(score) {
    if (score >= 90) return 'Xuất sắc'
    if (score >= 80) return 'Tốt'
    if (score >= 65) return 'Khá'
    if (score >= 50) return 'Trung bình'
    if (score >= 35) return 'Yếu'
    return 'Kém'
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

function formatTrangThaiMinhChung(value) {
    const map = {
        cho_duyet: 'Chờ duyệt',
        hop_le: 'Hợp lệ',
        khong_hop_le: 'Không hợp lệ',
    }

    return map[value] || '—'
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

function saveDraft() {
    // DB không có trạng thái lưu nháp riêng cho CVHT.
    // Khi nối BE, hàm này chỉ lưu diem_lop và ghi_chu trong chi_tiet_danh_gia.
    alert('Đã lưu nháp điểm và ghi chú.')
}

function approveEvaluation() {
    formInfo.trang_thai = 'co_van_da_kiem_tra'

    alert('CVHT đã kiểm tra phiếu đánh giá.')

    router.push({
        name: 'AdminDSPhieuChoDuyet',
    })
}

function rejectEvaluation() {
    rejectError.value = ''

    if (!rejectReason.value.trim()) {
        rejectError.value = 'Vui lòng nhập lý do trả lại.'
        return
    }

    formInfo.trang_thai = 'tra_lai'

    hideModal('rejectModal')
    rejectReason.value = ''

    alert('Đã trả lại phiếu cho sinh viên chỉnh sửa.')

    router.push({
        name: 'AdminDSPhieuChoDuyet',
    })
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
.evaluation-detail-page {
    padding-bottom: 100px;
}

/* KHUNG GIẤY */
.evaluation-paper {
    background: #ffffff;
    color: #111827;
    border-radius: 10px;
    padding: 28px;
    border: 1px solid #d1d5db;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
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
}

.paper-title span {
    display: block;
    margin-top: 6px;
    font-size: 14px;
    font-style: italic;
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
}

.info-line input,
.info-line select {
    flex: 1;
    border: none;
    border-bottom: 1px dotted #111827;
    border-radius: 0;
    padding: 3px 4px;
    background: transparent;
    color: #111827;
    font-family: "Times New Roman", Times, serif;
}

/* TABLE GIỐNG PHỤ LỤC */
.official-table-wrap {
    width: 100%;
    overflow-x: auto;
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
    border: 1px solid #111827;
    padding: 7px 8px;
    vertical-align: top;
}

.official-table th {
    text-align: center;
    font-weight: 800;
    background: #f3f4f6;
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
    width: 280px;
}

.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

/* DÒNG MỤC */
.section-row td {
    background: #f8fafc;
    font-weight: 700;
}

.option-row td {
    font-size: 13.5px;
}

.criteria-row td {
    background: #ffffff;
}

/* INPUT ĐIỂM */
.score-input {
    width: 64px;
    height: 34px;
    text-align: center;
    border: 1px solid #9ca3af;
    border-radius: 6px;
    font-weight: 700;
}

.note-input {
    width: 100%;
    min-height: 52px;
    margin-top: 6px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 6px;
    resize: vertical;
    font-family: Arial, sans-serif;
    font-size: 13px;
}

/* MINH CHỨNG */
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
    border: 1px dashed #cbd5e1;
    border-radius: 6px;
    padding: 6px;
    font-family: Arial, sans-serif;
    font-size: 13px;
}

.evidence-name {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
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
}

.mini-btn {
    border: 1px solid #2563eb;
    color: #2563eb;
    background: #ffffff;
    border-radius: 5px;
    padding: 3px 8px;
    font-size: 12px;
    cursor: pointer;
}

.mini-btn:hover {
    background: #2563eb;
    color: #ffffff;
}

.total-row td {
    background: #fef3c7;
    font-weight: 800;
}

/* GHI CHÚ CUỐI PHIẾU */
.paper-note {
    margin-top: 18px;
    font-size: 14px;
    line-height: 1.55;
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

/* LỊCH SỬ */
.history-panel {
    margin-top: 18px;
}

.history-title {
    margin-bottom: 12px;
}

.history-title h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
}

.history-title p {
    margin: 5px 0 0;
    color: #6b7280;
}

/* ACTION BAR */
.review-action-bar {
    position: sticky;
    bottom: 0;
    z-index: 20;
    margin-top: 18px;
    padding: 14px 18px;
    border-radius: 18px 18px 0 0;
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.25);
    box-shadow: 0 -10px 30px rgba(15, 23, 42, 0.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    font-family: Arial, sans-serif;
}

.review-action-bar span,
.review-action-bar small {
    color: #6b7280;
}

.review-action-bar strong {
    margin: 0 8px;
    font-size: 24px;
    color: #111827;
}

.action-buttons {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* MODAL MINH CHỨNG */
.evidence-preview {
    text-align: center;
    padding: 20px;
}

.evidence-preview i {
    font-size: 46px;
    margin-bottom: 12px;
}

.evidence-preview h3 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 14px;
}

.evidence-preview p {
    margin: 6px 0;
}

/* STATUS */
.status-dot {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: 13px;
    font-weight: 700;
    white-space: nowrap;
}

.st-nhap {
    background: rgba(99, 102, 241, 0.12);
    color: #4f46e5;
}

.st-da_gui {
    background: rgba(245, 158, 11, 0.12);
    color: #b45309;
}

.st-lop_da_danh_gia {
    background: rgba(59, 130, 246, 0.12);
    color: #2563eb;
}

.st-co_van_da_kiem_tra {
    background: rgba(34, 197, 94, 0.12);
    color: #15803d;
}

.st-hoan_thanh {
    background: rgba(20, 184, 166, 0.12);
    color: #0f766e;
}

.st-tra_lai {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
}

.detail-status-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.readonly-note {
    color: #6b7280;
    font-size: 14px;
}

.score-input:disabled,
.note-input:disabled {
    background: #f3f4f6;
    cursor: not-allowed;
    color: #6b7280;
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
    border: 1px solid #d1d5db;
    background: #f8fafc;
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
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    text-align: center;
    padding: 24px;
}

.no-preview-box i {
    font-size: 54px;
    margin-bottom: 14px;
}

.no-preview-box h3 {
    font-size: 18px;
    color: #111827;
    margin-bottom: 8px;
}

.preview-meta {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.preview-meta div {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px;
    background: #ffffff;
}

.preview-meta span {
    display: block;
    color: #6b7280;
    font-size: 13px;
    margin-bottom: 4px;
}

.preview-meta strong {
    color: #111827;
    font-size: 14px;
    word-break: break-word;
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

/* RESPONSIVE */
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

@media (max-width: 640px) {
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

    .info-line input,
    .info-line select {
        width: 100%;
    }

    .action-buttons {
        justify-content: stretch;
    }

    .action-buttons .btn {
        flex: 1;
    }
}

.detail-toolbar {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 16px;
}
</style>