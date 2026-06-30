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
                    <input v-model="student.nganh" disabled />
                </div>

                <div class="info-line">
                    <label>Khoa:</label>
                    <select v-model="student.khoa" disabled>
                        <option>Công nghệ thông tin</option>
                    </select>
                </div>

                <div class="info-line">
                    <label>Lớp:</label>
                    <input v-model="student.lop" disabled />
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
                    <input v-model="formInfo.nam_hoc" disabled />
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
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

import "../../../assets/css/admin.css"
import "../../../assets/css/dashboard.css"
import "../../../assets/css/user.css"

const route = useRoute()
const router = useRouter()

const API = 'http://127.0.0.1:8000/api/admin/phieu-cho-duyet'

const loading = ref(false)
const selectedEvidence = ref(null)
const rejectReason = ref('')
const rejectError = ref('')

const student = reactive({
    nam_trung_tuyen: '',
    nganh: '',
    khoa: '',
    lop: '',
    ma_sinh_vien: '',
    ho_ten: '',
    ngay_sinh: '',
    so_dien_thoai: '',
})

const formInfo = reactive({
    id: null,
    hoc_ky: '',
    nam_hoc: '',
    trang_thai: '',
    tong_diem_sv: 0,
    tong_diem_lop: 0,
    tong_diem_cuoi: 0,
    xep_loai: '',
})

const evaluationRows = reactive([])

function authHeader() {
    const token =
        sessionStorage.getItem('auth_token') ||
        localStorage.getItem('auth_token') ||
        localStorage.getItem('key_admin')

    return {
        headers: {
            Authorization: 'Bearer ' + token,
        },
    }
}

onMounted(() => {
    getChiTietPhieu()
})

watch(
    () => route.params.id,
    () => {
        getChiTietPhieu()
    }
)

function getChiTietPhieu() {
    const id = route.params.id

    if (!id) {
        alert('Không tìm thấy ID phiếu!')
        router.push({
            name: 'AdminDSPhieuChoDuyet',
        })
        return
    }

    loading.value = true

    axios
        .get(API + '/chi-tiet', {
            ...authHeader(),
            params: {
                id: id,
            },
        })
        .then((res) => {
            if (!res.data.status) {
                alert(res.data.message || 'Không lấy được chi tiết phiếu!')
                router.push({
                    name: 'AdminDSPhieuChoDuyet',
                })
                return
            }

            const phieu = res.data.phieu || {}
            const chiTiet = res.data.chi_tiet || []

            Object.assign(student, {
                nam_trung_tuyen: phieu.nam_trung_tuyen || '',
                nganh: phieu.ten_nganh || '',
                khoa: phieu.khoa || phieu.ten_khoa || '',
                lop: phieu.ten_lop || '',
                ma_sinh_vien: phieu.ma_sinh_vien || '',
                ho_ten: phieu.ho_ten || '',
                ngay_sinh: formatNgay(phieu.ngay_sinh),
                so_dien_thoai: phieu.so_dien_thoai || '',
            })

            Object.assign(formInfo, {
                id: phieu.id || id,
                hoc_ky: formatHocKyRaw(phieu.hoc_ky),
                nam_hoc: phieu.nam_hoc || '',
                trang_thai: phieu.trang_thai || '',
                tong_diem_sv: Number(phieu.tong_diem_sv || 0),
                tong_diem_lop: Number(phieu.tong_diem_lop || 0),
                tong_diem_cuoi: Number(phieu.tong_diem_cuoi || 0),
                xep_loai: phieu.xep_loai || '',
            })

            evaluationRows.splice(
                0,
                evaluationRows.length,
                ...chiTiet.map((item) => ({
                    id: item.id,
                    type: 'criteria',
                    tt: item.ma_tieu_chi || '',
                    content: item.ten_tieu_chi || '',
                    max_text: item.diem_toi_da ?? '',
                    max_score: Number(item.diem_toi_da || 0),
                    student_score: Number(item.diem_sv || 0),
                    class_score: Number(item.diem_lop || item.diem_sv || 0),
                    note: item.ghi_chu || '',
                    editable: true,
                    chi_tiet_id: item.id,
                    tieu_chi_id: item.tieu_chi_id,
                    evidences: item.minh_chung || item.minh_chungs || [],
                }))
            )
        })
        .catch((err) => {
            console.log('Lỗi lấy chi tiết phiếu:', err.response?.data || err)
            alert(err.response?.data?.message || 'Lỗi lấy chi tiết phiếu!')
        })
        .finally(() => {
            loading.value = false
        })
}

const isReadOnly = computed(() => {
    const mode = route.query.mode

    return (
        mode === 'view' ||
        formInfo.trang_thai === 'co_van_da_kiem_tra' ||
        formInfo.trang_thai === 'hoan_thanh' ||
        formInfo.trang_thai === 'tra_lai'
    )
})

const totalStudentScore = computed(() => {
    const total = evaluationRows
        .filter((row) => row.editable)
        .reduce((sum, row) => {
            return sum + Number(row.student_score || 0)
        }, 0)

    return Math.min(total, 100)
})

const totalClassScore = computed(() => {
    const total = evaluationRows
        .filter((row) => row.editable)
        .reduce((sum, row) => {
            return sum + Number(row.class_score || 0)
        }, 0)

    return Math.min(total, 100)
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

    if (score > Number(row.max_score || 0)) {
        row.class_score = Number(row.max_score || 0)
    }
}

function saveDraft() {
    alert('Hiện backend chưa có API lưu điểm lớp/CVHT. Nút này chưa lưu thật vào DB.')
}

function approveEvaluation() {
    if (!formInfo.id) {
        alert('Không tìm thấy phiếu!')
        return
    }

    if (!confirm('Bạn chắc chắn muốn duyệt phiếu này?')) {
        return
    }

    axios
        .post(
            API + '/duyet',
            {
                id: formInfo.id,
                ghi_chu: 'CVHT đã kiểm tra phiếu',
            },
            authHeader()
        )
        .then((res) => {
            if (!res.data.status) {
                alert(res.data.message || 'Duyệt phiếu thất bại!')
                return
            }

            alert(res.data.message || 'Duyệt phiếu thành công!')

            router.push({
                name: 'AdminDSPhieuChoDuyet',
            })
        })
        .catch((err) => {
            console.log('Lỗi duyệt phiếu:', err.response?.data || err)
            alert(err.response?.data?.message || 'Lỗi duyệt phiếu!')
        })
}

function rejectEvaluation() {
    rejectError.value = ''

    if (!rejectReason.value.trim()) {
        rejectError.value = 'Vui lòng nhập lý do trả lại.'
        return
    }

    if (!formInfo.id) {
        rejectError.value = 'Không tìm thấy phiếu!'
        return
    }

    axios
        .post(
            API + '/tra-lai',
            {
                id: formInfo.id,
                ghi_chu: rejectReason.value,
            },
            authHeader()
        )
        .then((res) => {
            if (!res.data.status) {
                alert(res.data.message || 'Trả lại phiếu thất bại!')
                return
            }

            hideModal('rejectModal')
            rejectReason.value = ''
            rejectError.value = ''

            alert(res.data.message || 'Trả lại phiếu thành công!')

            router.push({
                name: 'AdminDSPhieuChoDuyet',
            })
        })
        .catch((err) => {
            console.log('Lỗi trả lại phiếu:', err.response?.data || err)
            alert(err.response?.data?.message || 'Lỗi trả lại phiếu!')
        })
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

function formatHocKyRaw(value) {
    if (value === 1 || value === '1') return 'Kỳ I'
    if (value === 2 || value === '2') return 'Kỳ II'

    return value || '—'
}

function formatNgay(value) {
    if (!value) return ''

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) {
        return value
    }

    return date.toLocaleDateString('vi-VN')
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
        da_duyet: 'Đã duyệt',
        tu_choi: 'Từ chối',
        hop_le: 'Hợp lệ',
        khong_hop_le: 'Không hợp lệ',
    }

    return map[value] || '—'
}

function openEvidence(file) {
    selectedEvidence.value = file
}

function downloadEvidence(file) {
    if (!file || !file.duong_dan) {
        alert('Không tìm thấy đường dẫn file!')
        return
    }

    window.open(file.duong_dan, '_blank')
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