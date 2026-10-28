<template>
  <div class="submission-page">
    <!-- HEADER -->
    <header class="page-header">
      <div>
        <span class="eyebrow">QUẢN LÝ GIẢNG DẠY</span>
        <h1>📥 Bài nộp & Chấm điểm</h1>
        <p>
          Theo dõi bài làm, đánh giá kết quả và phản hồi
          cho sinh viên.
        </p>
      </div>

      <button type="button" class="primary-button" :disabled="busy || !selectedBaiTapId" @click="fetchBaiNop">
        ↻ {{ loading ? "Đang tải..." : "Làm mới" }}
      </button>
    </header>

    <!-- CHỌN LỚP VÀ BÀI TẬP -->
    <section class="filter-panel">
      <div class="field">
        <label for="lop">Lớp học</label>
        <select id="lop" v-model="selectedLopId" :disabled="loadingLop" @change="changeLop">
          <option value="">-- Chọn lớp học --</option>
          <option v-for="lop in danhSachLop" :key="lop.id" :value="String(lop.id)">
            {{ lop.ten_lop }} ({{ lop.ma_lop || "—" }})
          </option>
        </select>
      </div>

      <div class="field">
        <label for="bai-tap">Bài tập</label>
        <select id="bai-tap" v-model="selectedBaiTapId" :disabled="!selectedLopId || loadingBaiTap"
          @change="changeBaiTap">
          <option value="">-- Chọn bài tập --</option>
          <option v-for="bt in danhSachBaiTap" :key="bt.id" :value="String(bt.id)">
            {{ bt.tieuDe }}
          </option>
        </select>
      </div>

      <router-link to="/giang-vien/bai-tap" class="back-link">
        Quản lý bài tập →
      </router-link>
    </section>

    <!-- THỐNG KÊ -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon blue">BN</div>
        <div>
          <span>Tổng bài nộp</span>
          <strong>{{ statText(danhSach.length) }}</strong>
          <small>Thuộc bài tập đang xem</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon green">✓</div>
        <div>
          <span>Nộp đúng hạn</span>
          <strong>{{ statText(soDungHan) }}</strong>
          <small>Theo dữ liệu bài nộp</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon orange">!</div>
        <div>
          <span>Nộp trễ hạn</span>
          <strong>{{ statText(soTreHan) }}</strong>
          <small>Cần lưu ý khi đánh giá</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon purple">Đ</div>
        <div>
          <span>Đã chấm điểm</span>
          <strong>{{ statText(soDaCham) }}</strong>
          <small>Đã có bản ghi điểm</small>
        </div>
      </div>
    </div>

    <!-- DANH SÁCH -->
    <section class="table-panel">
      <div class="panel-heading">
        <div>
          <h2>Danh sách bài nộp</h2>
          <p>
            {{
              selectedBaiTap?.tieuDe ||
              "Chọn lớp và bài tập để xem bài nộp"
            }}
          </p>
        </div>
        <span class="count-tag">
          {{ danhSachHienThi.length }} kết quả
        </span>
      </div>

      <div class="toolbar">
        <div class="search-box">
          <span>⌕</span>
          <input v-model="keyword" type="search" placeholder="Tìm tên hoặc MSSV..." :disabled="!hasLoaded" />
        </div>

        <select v-model="statusFilter" :disabled="!hasLoaded">
          <option value="all">Tất cả bài nộp</option>
          <option value="dung_han">Đúng hạn</option>
          <option value="tre_han">Trễ hạn</option>
        </select>

        <select v-model="gradingFilter" :disabled="!hasLoaded">
          <option value="all">Tất cả điểm</option>
          <option value="graded">Đã chấm</option>
          <option value="ungraded">Chưa chấm</option>
        </select>
      </div>

      <!-- THÔNG BÁO -->
      <div v-if="error" class="message error-message" role="alert">
        <span>{{ error }}</span>
        <button type="button" @click="refreshData">Thử lại</button>
      </div>

      <div v-if="success" class="message success-message" role="status">
        <span>✓ {{ success }}</span>
        <button type="button" @click="success = ''">×</button>
      </div>

      <div v-if="busy" class="empty-state" role="status">
        <div class="spinner"></div>
        <strong>Đang tải dữ liệu...</strong>
        <p>Hệ thống đang lấy thông tin bài nộp.</p>
      </div>

      <div v-else-if="!selectedLopId" class="empty-state">
        <div class="empty-icon">📚</div>
        <strong>Hãy chọn lớp học</strong>
        <p>Chọn lớp để tải danh sách bài tập.</p>
      </div>

      <div v-else-if="danhSachBaiTap.length === 0" class="empty-state">
        <div class="empty-icon">📝</div>
        <strong>Chưa có bài tập</strong>
        <p>Lớp được chọn chưa có bài tập nào.</p>
      </div>

      <div v-else-if="!selectedBaiTapId" class="empty-state">
        <div class="empty-icon">▤</div>
        <strong>Hãy chọn bài tập</strong>
        <p>Chọn bài tập để xem và chấm bài nộp.</p>
      </div>

      <div v-else-if="hasLoaded && danhSach.length === 0" class="empty-state">
        <div class="empty-icon">📥</div>
        <strong>Chưa có bài nộp</strong>
        <p>Hiện chưa có sinh viên nộp bài tập này.</p>
      </div>

      <div v-else-if="hasLoaded && danhSachHienThi.length === 0" class="empty-state">
        <div class="empty-icon">⌕</div>
        <strong>Không tìm thấy kết quả</strong>
        <p>Hãy thử thay đổi từ khóa hoặc bộ lọc.</p>
      </div>

      <!-- BẢNG BÀI NỘP -->
      <div v-else-if="hasLoaded" class="table-scroll">
        <table class="submission-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>SINH VIÊN</th>
              <th>THỜI GIAN NỘP</th>
              <th>TRẠNG THÁI</th>
              <th>ĐIỂM</th>
              <th>BÀI LÀM</th>
              <th>THAO TÁC</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(item, index) in danhSachHienThi" :key="item.id">
              <td class="muted">{{ index + 1 }}</td>

              <td>
                <div class="student-cell">
                  <span class="avatar">
                    {{ initials(item.tenSinhVien) }}
                  </span>
                  <div>
                    <strong>
                      {{ item.tenSinhVien || "Chưa cập nhật" }}
                    </strong>
                    <small>
                      {{ item.mssv || "Chưa có MSSV" }}
                    </small>
                  </div>
                </div>
              </td>

              <td class="muted">
                {{ formatDate(item.thoiGianNop) }}
              </td>

              <td>
                <span class="status-pill" :class="submissionStatus(item).className">
                  {{ submissionStatus(item).text }}
                </span>
              </td>

              <td>
                <span v-if="item.diem !== null" class="score-pill">
                  {{ formatScore(item.diem) }} / 10
                </span>
                <span v-else class="muted">Chưa chấm</span>
              </td>

              <td>
                <a v-if="item.fileUrl" :href="item.fileUrl" target="_blank" rel="noopener noreferrer" class="file-link">
                  Xem tệp ↗
                </a>
                <button v-else-if="item.loaiFile === 'online_exam'" type="button" class="file-link online-link"
                  @click="openGrade(item)">Xem bài trực tuyến →</button>
                <span v-else class="muted">Chưa có liên kết</span>
              </td>

              <td>
                <button type="button" class="grade-btn" @click="openGrade(item)">
                  {{ item.diem !== null ? "Xem điểm" : "Xem & chấm" }}
                  →
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer class="table-footer">
        <span>
          Hiển thị {{ danhSachHienThi.length }} /
          {{ danhSach.length }} bài nộp
        </span>
        <span v-if="lastUpdatedAt">
          Cập nhật: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>

    <!-- MODAL CHẤM ĐIỂM -->
    <div v-if="showGradeModal" class="modal-overlay" @click.self="closeGrade">
      <div class="grade-modal" role="dialog" aria-modal="true" aria-labelledby="grade-title">
        <header class="modal-header">
          <div>
            <span class="eyebrow">ĐÁNH GIÁ BÀI LÀM</span>
            <h2 id="grade-title">Bài làm & chấm điểm</h2>
            <p>
              {{ gradingItem?.tenSinhVien }} ·
              {{ gradingItem?.mssv || "Chưa có MSSV" }}
            </p>
          </div>

          <button type="button" class="close-btn" :disabled="saving" @click="closeGrade">
            ×
          </button>
        </header>

        <form @submit.prevent="saveGrade">
          <div class="modal-body">
            <div class="assignment-summary">
              <div>
                <small>Bài tập</small>
                <strong>
                  {{ selectedBaiTap?.tieuDe || "Bài tập" }}
                </strong>
              </div>
              <div>
                <small>Trạng thái nộp</small>
                <span>
                  {{ submissionStatus(gradingItem).text }}
                </span>
              </div>
            </div>

            <!-- Chi tiết câu hỏi lấy từ API có xác thực quyền giảng viên -->
            <div v-if="detailLoading" class="exam-detail-state">Đang tải câu trả lời của sinh viên...</div>
            <div v-else-if="detailError" class="grade-error">{{ detailError }}</div>
            <template v-else-if="examDetail">
              <div v-if="examDetail.laBaiTrucTuyen" class="exam-detail-summary">
                <strong>{{ isPureMultipleChoice ? 'Bài trắc nghiệm' : 'Bài kiểm tra trực tuyến' }}</strong>
                <span v-if="isPureMultipleChoice">Điểm hệ thống tính: <b>{{ formatScore(examDetail.diemTracNghiem)
                    }}/10</b></span>
                <span v-else>Điểm trắc nghiệm đã đạt: <b>{{ formatScore(examDetail.diemTracNghiem) }}/10</b> (chưa gồm
                  tự luận)</span>
                <p v-if="isPureMultipleChoice && gradingItem?.diem === null" class="muted">Bài cũ chưa có bản ghi điểm.
                  Hãy dùng chức năng chấm lại ở Backend, không nhập điểm thủ công thay kết quả tự động.</p>
                <p v-if="!isPureMultipleChoice && examDetail.coTuLuan" class="muted">Giảng viên cần đánh giá câu tự luận
                  rồi nhập điểm cuối cùng.</p>
              </div>
              <div v-if="examDetail.cauHoi?.length" class="question-review">
                <h3>Chi tiết câu trả lời</h3>
                <article v-for="(q, index) in examDetail.cauHoi" :key="q.id" class="review-question">
                  <div class="review-heading"><strong>Câu {{ index + 1 }} · {{ q.diem }} điểm</strong><span
                      :class="q.loaiCauHoi === 'trac_nghiem' ? (q.dung ? 'correct-answer' : 'incorrect-answer') : ''">{{
                        q.loaiCauHoi === 'trac_nghiem' ? (q.dung ? '✓ Đúng' : '✕ Sai / chưa trả lời') : 'Tự luận · chờ  chấm' }}</span></div>
                  <p>{{ q.noiDung }}</p>
                  <template v-if="q.loaiCauHoi === 'trac_nghiem'">
                    <div v-for="(a, k) in q.dapAn" :key="a.id" class="review-option"
                      :class="{ chosen: a.daChon, correct: a.dung }">
                      <span>{{ String.fromCharCode(65 + k) }}.</span> {{ a.noiDung }} <strong v-if="a.dung">✓ Đáp án
                        đúng</strong><em v-if="a.daChon">(Sinh viên chọn)</em>
                    </div>
                  </template>
                  <div v-else class="essay-answer">{{ q.traLoiTuLuan || 'Sinh viên chưa trả lời câu này.' }}</div>
                </article>
              </div>
              <p v-else-if="examDetail.laBaiTrucTuyen" class="muted">Chưa có câu hỏi trong bài kiểm tra.</p>
            </template>

            <template v-if="!detailLoading && !detailError && (!examDetail?.laBaiTrucTuyen || !isPureMultipleChoice)">
              <div class="form-field">
                <label for="score">Điểm số (0–10) *</label>
                <input id="score" v-model="gradeForm.diem" type="number" min="0" max="10" step="0.01"
                  placeholder="Nhập điểm, ví dụ 8.5" required :disabled="saving" />
              </div>

              <div class="form-field">
                <label for="result">Kết quả đánh giá</label>
                <select id="result" v-model="gradeForm.ketQua" :disabled="saving">
                  <option value="">Chưa đánh giá</option>
                  <option value="dat">Đạt</option>
                  <option value="khong_dat">Không đạt</option>
                </select>
              </div>

              <div class="form-field">
                <label for="comment">Nhận xét của giảng viên</label>
                <textarea id="comment" v-model="gradeForm.nhanXet" rows="5" placeholder="Nhập nhận xét về bài làm..."
                  :disabled="saving"></textarea>
              </div>

            </template>

            <div v-if="gradeError" class="grade-error">
              {{ gradeError }}
            </div>
          </div>

          <footer class="modal-footer">
            <button type="button" class="secondary-button" :disabled="saving" @click="closeGrade">
              Hủy
            </button>
            <button type="submit" class="primary-button" :disabled="saving || detailLoading || !!detailError"
              v-if="!detailLoading && !detailError && (!examDetail?.laBaiTrucTuyen || !isPureMultipleChoice)">
              {{ saving ? "Đang lưu..." : "Lưu điểm" }}
            </button>
          </footer>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
} from "vue"
import { useRoute } from "vue-router"
import api from "../../../services/api"

const route = useRoute()

const danhSachLop = ref([])
const danhSachBaiTap = ref([])
const danhSach = ref([])

const selectedLopId = ref("")
const selectedBaiTapId = ref("")

const loadingLop = ref(false)
const loadingBaiTap = ref(false)
const loading = ref(false)
const hasLoaded = ref(false)

const error = ref("")
const success = ref("")
const lastUpdatedAt = ref("")

const keyword = ref("")
const statusFilter = ref("all")
const gradingFilter = ref("all")

const showGradeModal = ref(false)
const gradingItem = ref(null)
const saving = ref(false)
const gradeError = ref("")
const detailLoading = ref(false)
const detailError = ref("")
const examDetail = ref(null)
let detailRequestId = 0
const isPureMultipleChoice = computed(() => {
  const detail = examDetail.value
  return !!detail?.laBaiTrucTuyen && !!detail.cauHoi?.length &&
    detail.cauHoi.every(q => q.loaiCauHoi === 'trac_nghiem')
})

const gradeForm = ref({
  diem: "",
  nhanXet: "",
  ketQua: "",
})

let lopRequestId = 0
let baiTapRequestId = 0
let baiNopRequestId = 0

const busy = computed(
  () => loadingLop.value ||
    loadingBaiTap.value ||
    loading.value
)

const selectedBaiTap = computed(() =>
  danhSachBaiTap.value.find(
    (bt) => String(bt.id) === selectedBaiTapId.value
  ) || null
)

function extractList(response) {
  const body = response?.data

  if (body?.status === false || body?.success === false) {
    throw new Error(body.message || "API trả về lỗi")
  }

  const data = body?.data ?? body

  if (Array.isArray(data)) return data
  if (Array.isArray(data?.data)) return data.data

  throw new Error("Dữ liệu danh sách API không hợp lệ")
}

function normalizeLop(item) {
  return {
    id: item.id,
    ten_lop:
      item.ten_lop ??
      item.tenLop ??
      `Lớp ${item.id}`,
    ma_lop:
      item.ma_lop ??
      item.maLop ??
      item.maHocPhan ??
      "",
  }
}

function normalizeBaiTap(item) {
  return {
    id: item.id,
    lopId: item.lopId ?? item.lop_hoc_id,
    tieuDe: item.tieuDe ?? item.tieu_de ?? "",
  }
}

function normalizeBaiNop(item) {
  const rawDiem = item.diem ?? item.diem_so ?? null
  const number = rawDiem === null || rawDiem === ""
    ? null
    : Number(rawDiem)

  return {
    id: item.id,
    tenSinhVien:
      item.tenSinhVien ??
      item.ho_ten ??
      "",
    mssv:
      item.mssv ??
      item.maSinhVien ??
      item.ma_sinh_vien ??
      "",
    thoiGianNop:
      item.thoiGianNop ??
      item.thoi_gian_nop ??
      null,
    trangThai:
      item.trangThai ??
      item.trang_thai ??
      null,
    diem: number !== null && Number.isFinite(number)
      ? number
      : null,
    nhanXet:
      item.nhanXet ??
      item.nhan_xet ??
      "",
    ketQua:
      item.ketQua ??
      item.ket_qua ??
      "",
    fileUrl:
      item.fileUrl ??
      item.file_url ??
      null,
    loaiFile: item.loaiFile ?? item.loai_file ?? null,
  }
}

function initials(name) {
  const words = String(name || "").trim().split(/\s+/)
  if (!name || !words.length) return "SV"
  return words.slice(-2)
    .map((w) => w.charAt(0).toLocaleUpperCase("vi-VN"))
    .join("")
}

function formatDate(value) {
  if (!value) return "Chưa cập nhật"
  const date = new Date(
    typeof value === "string"
      ? value.replace(" ", "T")
      : value
  )

  if (Number.isNaN(date.getTime())) {
    return "Chưa cập nhật"
  }

  return date.toLocaleString("vi-VN")
}

function formatScore(value) {
  return Number(value).toLocaleString("vi-VN", {
    maximumFractionDigits: 2,
  })
}

function submissionStatus(item) {
  const value = item?.trangThai

  if (value === "dung_han" || value === "on_time") {
    return { text: "Đúng hạn", className: "on-time" }
  }

  if (value === "tre_han" || value === "late") {
    return { text: "Trễ hạn", className: "late" }
  }

  return { text: "Chưa xác định", className: "unknown" }
}

const soDungHan = computed(() =>
  danhSach.value.filter(
    (item) => item.trangThai === "dung_han" ||
      item.trangThai === "on_time"
  ).length
)

const soTreHan = computed(() =>
  danhSach.value.filter(
    (item) => item.trangThai === "tre_han" ||
      item.trangThai === "late"
  ).length
)

const soDaCham = computed(() =>
  danhSach.value.filter((item) => item.diem !== null).length
)

function statText(value) {
  return hasLoaded.value ? String(value) : "—"
}

const danhSachHienThi = computed(() => {
  const search = keyword.value.trim().toLocaleLowerCase("vi-VN")

  return danhSach.value.filter((item) => {
    const matchedSearch = [
      item.tenSinhVien,
      item.mssv,
    ].some((value) =>
      String(value || "")
        .toLocaleLowerCase("vi-VN")
        .includes(search)
    )

    const matchedStatus =
      statusFilter.value === "all" ||
      item.trangThai === statusFilter.value

    const matchedGrading =
      gradingFilter.value === "all" ||
      (
        gradingFilter.value === "graded"
          ? item.diem !== null
          : item.diem === null
      )

    return matchedSearch && matchedStatus && matchedGrading
  })
})

function getError(err, fallback) {
  const status = err.response?.status

  if (status === 401) return "Phiên đăng nhập đã hết hạn."
  if (status === 403) return "Bạn không có quyền truy cập dữ liệu."
  if (status === 404) return "Không tìm thấy dữ liệu hoặc API."
  if (status === 422) return "Thông tin gửi lên không hợp lệ."
  if (status >= 500) return "Backend gặp lỗi xử lý dữ liệu."

  return err.message || fallback
}

/* LẤY CÁC LỚP GIẢNG VIÊN PHỤ TRÁCH */
async function fetchLopHoc() {
  const current = ++lopRequestId
  loadingLop.value = true
  error.value = ""

  try {
    const response = await api.get("/giang-vien/lop-hoc")
    if (current !== lopRequestId) return

    danhSachLop.value = extractList(response).map(normalizeLop)

    // Cho phép mở từ trang Quản lý Bài tập:
    // /giang-vien/bai-nop?baiTapId=123
    const routeBaiTapId = String(route.query.baiTapId ?? "")

    if (routeBaiTapId) {
      const allResponse = await api.get("/giang-vien/bai-tap")
      if (current !== lopRequestId) return

      const found = extractList(allResponse)
        .map(normalizeBaiTap)
        .find((bt) => String(bt.id) === routeBaiTapId)

      if (found && danhSachLop.value.some(
        (lop) => String(lop.id) === String(found.lopId)
      )) {
        selectedLopId.value = String(found.lopId)
        await fetchBaiTap(routeBaiTapId)
      }
    }
  } catch (err) {
    if (current !== lopRequestId) return
    console.error("Lỗi tải lớp:", err)
    error.value = getError(err, "Không thể tải lớp học.")
  } finally {
    if (current === lopRequestId) {
      loadingLop.value = false
    }
  }
}

/* LẤY BÀI TẬP THEO LỚP */
async function fetchBaiTap(autoSelectId = "") {
  const current = ++baiTapRequestId
  ++baiNopRequestId

  selectedBaiTapId.value = ""
  danhSachBaiTap.value = []
  danhSach.value = []
  hasLoaded.value = false
  loading.value = false
  error.value = ""

  if (!selectedLopId.value) {
    loadingBaiTap.value = false
    return
  }

  loadingBaiTap.value = true

  try {
    const response = await api.get("/giang-vien/bai-tap", {
      params: { lopId: selectedLopId.value },
    })

    if (current !== baiTapRequestId) return

    danhSachBaiTap.value =
      extractList(response).map(normalizeBaiTap)

    if (
      autoSelectId &&
      danhSachBaiTap.value.some(
        (bt) => String(bt.id) === String(autoSelectId)
      )
    ) {
      selectedBaiTapId.value = String(autoSelectId)
      await fetchBaiNop()
    }
  } catch (err) {
    if (current !== baiTapRequestId) return
    console.error("Lỗi tải bài tập:", err)
    error.value = getError(err, "Không thể tải bài tập.")
  } finally {
    if (current === baiTapRequestId) {
      loadingBaiTap.value = false
    }
  }
}

/* LẤY BÀI NỘP */
async function fetchBaiNop() {
  const current = ++baiNopRequestId
  const id = selectedBaiTapId.value

  danhSach.value = []
  hasLoaded.value = false
  error.value = ""

  if (!id) {
    loading.value = false
    return
  }

  loading.value = true

  try {
    const response = await api.get(
      `/giang-vien/bai-tap/${encodeURIComponent(id)}/bai-nop`
    )

    if (current !== baiNopRequestId) return

    danhSach.value =
      extractList(response).map(normalizeBaiNop)

    hasLoaded.value = true
    lastUpdatedAt.value = new Date().toLocaleString("vi-VN")
  } catch (err) {
    if (current !== baiNopRequestId) return
    console.error("Lỗi tải bài nộp:", err)
    error.value = getError(err, "Không thể tải bài nộp.")
  } finally {
    if (current === baiNopRequestId) {
      loading.value = false
    }
  }
}

function changeLop() {
  keyword.value = ""
  statusFilter.value = "all"
  gradingFilter.value = "all"
  fetchBaiTap()
}

function changeBaiTap() {
  keyword.value = ""
  statusFilter.value = "all"
  gradingFilter.value = "all"
  fetchBaiNop()
}

function refreshData() {
  if (selectedBaiTapId.value) {
    fetchBaiNop()
  } else if (selectedLopId.value) {
    fetchBaiTap()
  } else {
    fetchLopHoc()
  }
}

/* POPUP CHẤM ĐIỂM / XEM BÀI LÀM */
async function loadExamDetail(item) {
  const current = ++detailRequestId
  detailLoading.value = true
  detailError.value = ''
  examDetail.value = null
  try {
    const response = await api.get(`/giang-vien/bai-nop/${encodeURIComponent(item.id)}/chi-tiet`)
    if (current !== detailRequestId) return
    if (response.data?.status === false) throw new Error(response.data.message || 'Không thể tải bài làm')
    examDetail.value = response.data?.data ?? null
  } catch (err) {
    if (current !== detailRequestId) return
    detailError.value = err.response?.data?.message || err.message || 'Không thể tải chi tiết bài làm.'
  } finally {
    if (current === detailRequestId) detailLoading.value = false
  }
}
function openGrade(item) {
  gradingItem.value = item
  gradeForm.value = {
    diem: item.diem === null ? "" : String(item.diem),
    nhanXet: item.nhanXet || "",
    ketQua: item.ketQua || "",
  }
  gradeError.value = ""
  showGradeModal.value = true
  loadExamDetail(item)
}

function closeGrade() {
  if (saving.value) return
  ++detailRequestId
  examDetail.value = null
  detailError.value = ''
  detailLoading.value = false
  showGradeModal.value = false
  gradingItem.value = null
  gradeError.value = ""
}

/* GỬI ĐIỂM VỀ BACKEND */
async function saveGrade() {
  gradeError.value = ""
  if (detailLoading.value || detailError.value) return
  if (isPureMultipleChoice.value) {
    gradeError.value = 'Đề 100% trắc nghiệm phải được chấm tự động ở Backend.'
    return
  }

  if (!gradingItem.value || gradeForm.value.diem === "") {
    gradeError.value = "Vui lòng nhập điểm."
    return
  }

  const diem = Number(gradeForm.value.diem)

  if (!Number.isFinite(diem) || diem < 0 || diem > 10) {
    gradeError.value = "Điểm phải nằm trong khoảng từ 0 đến 10."
    return
  }

  saving.value = true

  try {
    const response = await api.put(
      `/giang-vien/bai-nop/${gradingItem.value.id}/cham-diem`,
      {
        diem,
        nhanXet: gradeForm.value.nhanXet.trim() || null,
        ketQua: gradeForm.value.ketQua || null,
      }
    )

    if (
      response.data?.status === false ||
      response.data?.success === false
    ) {
      throw new Error(response.data?.message || "Lưu điểm thất bại")
    }

    showGradeModal.value = false
    gradingItem.value = null
    success.value = "Đã lưu điểm thành công."

    await fetchBaiNop()
  } catch (err) {
    console.error("Lỗi lưu điểm:", err)

    if (err.response?.status === 422) {
      const errors = err.response.data?.errors
      gradeError.value =
        (errors ? Object.values(errors).flat()[0] : null) ||
        "Điểm hoặc dữ liệu đánh giá không hợp lệ."
    } else {
      gradeError.value = getError(err, "Không thể lưu điểm.")
    }
  } finally {
    saving.value = false
  }
}

onMounted(fetchLopHoc)

onBeforeUnmount(() => {
  lopRequestId++
  baiTapRequestId++
  baiNopRequestId++
  detailRequestId++
})
</script>

<style scoped>
.submission-page {
  min-height: calc(100vh - 80px);
  width: 100%;
  box-sizing: border-box;
  padding: 28px;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, system-ui, sans-serif;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 18px;
  margin-bottom: 24px;
}

.eyebrow {
  color: #2563eb;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 1px;
}

.page-header h1 {
  margin: 8px 0;
  font-size: 28px;
  font-weight: 800;
}

.page-header p {
  margin: 0;
  color: #64748b;
  font-size: 13px;
}

.primary-button,
.secondary-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 11px 17px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.primary-button {
  background: #2563eb;
  color: #fff;
  border: 1px solid #2563eb;
}

.primary-button:hover:not(:disabled) {
  background: #1d4ed8;
}

.secondary-button {
  background: #fff;
  color: #334155;
  border: 1px solid #e2e8f0;
}

button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.filter-panel {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) auto;
  align-items: end;
  gap: 18px;
  padding: 21px;
  margin-bottom: 23px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
}

.field label {
  display: block;
  margin-bottom: 8px;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
}

.field select,
.toolbar select,
.form-field input,
.form-field select,
.form-field textarea {
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
  padding: 12px 13px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #172033;
  font: inherit;
  font-size: 12px;
  outline: none;
}

.field select:focus,
.toolbar select:focus,
.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
  border-color: #60a5fa;
}

.back-link {
  margin-bottom: 12px;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 15px;
  margin-bottom: 23px;
}

.summary-card {
  display: flex;
  gap: 14px;
  align-items: center;
  min-width: 0;
  padding: 18px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
}

.summary-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 43px;
  height: 43px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 900;
}

.summary-card>div:last-child {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 5px;
}

.summary-card span {
  font-size: 11px;
  color: #64748b;
}

.summary-card strong {
  color: #172033;
  font-size: 25px;
  font-weight: 800;
}

.summary-card small {
  color: #94a3b8;
  font-size: 10px;
}

.blue {
  background: #eff6ff;
  color: #2563eb;
}

.green {
  background: #f0fdf4;
  color: #16a34a;
}

.orange {
  background: #fff7ed;
  color: #ea580c;
}

.purple {
  background: #faf5ff;
  color: #9333ea;
}

.table-panel {
  overflow: hidden;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
}

.panel-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 22px;
}

.panel-heading h2 {
  margin: 0 0 6px;
  font-size: 17px;
  font-weight: 800;
}

.panel-heading p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
}

.count-tag {
  padding: 7px 12px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  padding: 0 22px 20px;
}

.search-box {
  flex: 1;
  min-width: 220px;
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0 12px;
}

.search-box span {
  font-size: 23px;
  color: #94a3b8;
}

.search-box input {
  width: 100%;
  padding: 12px 0;
  border: 0;
  outline: 0;
  background: transparent;
  color: #172033;
  font-size: 12px;
}

.toolbar select {
  width: 165px;
}

.table-scroll {
  width: 100%;
  overflow-x: auto;
}

.submission-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  white-space: nowrap;
}

.submission-table thead {
  background: #f8fafc;
}

.submission-table th {
  padding: 15px 18px;
  font-size: 10px;
  color: #64748b;
  letter-spacing: 0.3px;
}

.submission-table td {
  padding: 16px 18px;
  border-top: 1px solid #f1f5f9;
  font-size: 12px;
}

.submission-table tbody tr:hover {
  background: #f8fafc;
}

.student-cell {
  display: flex;
  align-items: center;
  gap: 11px;
}

.avatar {
  display: grid;
  place-items: center;
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
}

.student-cell>div {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.student-cell strong {
  color: #172033;
}

.student-cell small,
.muted {
  color: #94a3b8;
  font-size: 11px;
}

.status-pill,
.score-pill {
  display: inline-flex;
  padding: 7px 10px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 800;
}

.on-time {
  background: #dcfce7;
  color: #15803d;
}

.late {
  background: #fee2e2;
  color: #b91c1c;
}

.unknown {
  background: #fef3c7;
  color: #92400e;
}

.score-pill {
  background: #eff6ff;
  color: #2563eb;
}

.file-link {
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  text-decoration: none;
}

.grade-btn {
  padding: 9px 12px;
  border: 0;
  border-radius: 9px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.grade-btn:hover {
  background: #dbeafe;
}

.table-footer {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 17px 22px;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

.empty-state {
  display: flex;
  min-height: 275px;
  padding: 25px;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 11px;
  text-align: center;
}

.empty-icon {
  font-size: 32px;
}

.empty-state strong {
  font-size: 14px;
}

.empty-state p {
  margin: 0;
  color: #94a3b8;
  font-size: 11px;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: rotate 0.8s linear infinite;
}

.message {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin: 0 22px 16px;
  padding: 13px 15px;
  border-radius: 10px;
  font-size: 12px;
}

.message button {
  border: 0;
  background: transparent;
  color: inherit;
  font-weight: 800;
  cursor: pointer;
}

.error-message {
  background: #fef2f2;
  color: #b91c1c;
}

.success-message {
  background: #f0fdf4;
  color: #15803d;
}

/* MODAL */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(3px);
}

.grade-modal {
  width: 100%;
  max-width: 560px;
  max-height: 92vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 20px 70px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 23px 25px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 7px 0;
  font-size: 21px;
}

.modal-header p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
}

.close-btn {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 9px;
  background: #f1f5f9;
  color: #475569;
  font-size: 23px;
  cursor: pointer;
}

.modal-body {
  padding: 25px;
}

.assignment-summary {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  padding: 15px;
  margin-bottom: 21px;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  background: #f8fafc;
}

.assignment-summary>div {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.assignment-summary small {
  color: #64748b;
  font-size: 10px;
}

.assignment-summary strong,
.assignment-summary span {
  font-size: 12px;
}

.form-field {
  margin-bottom: 18px;
}

.form-field label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 700;
}

.form-field textarea {
  resize: vertical;
}

.grade-error {
  padding: 12px;
  border-radius: 9px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 12px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 17px 25px;
  border-top: 1px solid #e5e7eb;
}

@keyframes rotate {
  to {
    transform: rotate(360deg);
  }
}

/* DARK MODE */
:global(html.dark-theme) .submission-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .summary-card strong,
:global(html.dark-theme) .panel-heading h2,
:global(html.dark-theme) .student-cell strong,
:global(html.dark-theme) .modal-header h2 {
  color: #fff;
}

:global(html.dark-theme) .filter-panel,
:global(html.dark-theme) .summary-card,
:global(html.dark-theme) .table-panel,
:global(html.dark-theme) .grade-modal {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .field select,
:global(html.dark-theme) .toolbar select,
:global(html.dark-theme) .search-box,
:global(html.dark-theme) .form-field input,
:global(html.dark-theme) .form-field select,
:global(html.dark-theme) .form-field textarea,
:global(html.dark-theme) .assignment-summary {
  background: #202327;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .search-box input {
  color: #fff;
}

:global(html.dark-theme) .submission-table thead,
:global(html.dark-theme) .submission-table tbody tr:hover {
  background: #202327;
}

:global(html.dark-theme) .submission-table td,
:global(html.dark-theme) .table-footer,
:global(html.dark-theme) .modal-header,
:global(html.dark-theme) .modal-footer {
  border-color: rgba(255, 255, 255, 0.12);
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 850px) {
  .filter-panel {
    grid-template-columns: 1fr;
  }

  .back-link {
    margin-bottom: 0;
  }
}

@media (max-width: 650px) {
  .submission-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .page-header h1 {
    font-size: 23px;
  }

  .toolbar {
    flex-direction: column;
  }

  .search-box,
  .toolbar select {
    width: 100%;
    box-sizing: border-box;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }
}

/* ONLINE EXAM DETAIL */
.online-link {
  border: 0;
  background: none;
  cursor: pointer;
  padding: 0;
}

.exam-detail-state {
  padding: 20px 12px;
  text-align: center;
  color: #64748b;
}

.exam-detail-summary {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 14px;
  margin-bottom: 17px;
  border: 1px solid #bfdbfe;
  border-radius: 11px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 13px;
}

.exam-detail-summary p {
  margin: 0;
  line-height: 1.6;
}

.question-review {
  margin-bottom: 18px;
}

.question-review h3 {
  margin: 12px 0;
  font-size: 15px;
}

.review-question {
  padding: 14px;
  margin-bottom: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
}

.review-heading {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  font-size: 12px;
}

.review-question p {
  font-size: 13px;
  line-height: 1.65;
  overflow-wrap: anywhere;
}

.review-option {
  padding: 9px;
  margin: 6px 0;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.5;
}

.review-option.chosen {
  border-color: #60a5fa;
}

.review-option.correct {
  background: #dcfce7;
  border-color: #86efac;
  color: #166534;
}

.review-option strong,
.review-option em {
  margin-left: 8px;
  font-size: 11px;
}

.correct-answer {
  color: #16a34a;
}

.incorrect-answer {
  color: #dc2626;
}

.essay-answer {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  padding: 14px;
  background: #f1f5f9;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.7;
}

:global(html.dark-theme) .exam-detail-summary {
  background: #1e293b;
  border-color: #334155;
  color: #bfdbfe;
}

:global(html.dark-theme) .review-question,
:global(html.dark-theme) .review-option {
  border-color: #334155;
}

:global(html.dark-theme) .review-option.correct {
  background: #153b29;
  border-color: #166534;
  color: #86efac;
}

:global(html.dark-theme) .essay-answer {
  background: #202327;
  color: #f8fafc;
}
</style>
