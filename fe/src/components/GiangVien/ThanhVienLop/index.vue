
<template>
  <div class="member-page">
    <!-- HEADER -->
    <header class="page-header">
      <div>
        <span class="eyebrow">QUẢN LÝ GIẢNG DẠY</span>
        <h1>👥 Thành viên lớp học</h1>
        <p>
          Theo dõi danh sách sinh viên, tình trạng học tập
          và kết nối Agent trong từng lớp.
        </p>
      </div>

      <button
        type="button"
        class="refresh-btn"
        :disabled="loadingLop || loading"
        @click="refreshData"
      >
        <span :class="{ spinning: loading || loadingLop }">↻</span>
        {{ loading || loadingLop ? "Đang cập nhật..." : "Làm mới" }}
      </button>
    </header>

    <!-- CLASS SELECTOR -->
    <section class="selector-panel">
      <div class="selector-icon">LH</div>

      <div class="selector-content">
        <label for="select-class">Lớp học đang xem</label>
        <select
          id="select-class"
          v-model="selectedLopId"
          :disabled="loadingLop || danhSachLop.length === 0"
          @change="changeClass"
        >
          <option value="">-- Chọn lớp học --</option>
          <option
            v-for="lop in danhSachLop"
            :key="lop.id"
            :value="String(lop.id)"
          >
            {{ lop.ten_lop }} ({{ lop.ma_lop || "Chưa có mã" }})
          </option>
        </select>
      </div>

      <router-link
        to="/giang-vien/lop-hoc"
        class="back-link"
      >
        Quản lý lớp học →
      </router-link>
    </section>

    <!-- SUMMARY -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon blue">SV</div>
        <div class="summary-info">
          <span>Tổng thành viên</span>
          <strong>{{ summaryValue(danhSach.length) }}</strong>
          <small>Trong lớp đã chọn</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon green">✓</div>
        <div class="summary-info">
          <span>Đang học</span>
          <strong>{{ summaryValue(soDangHoc) }}</strong>
          <small>Thành viên còn trong lớp</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon orange">↗</div>
        <div class="summary-info">
          <span>Đã rời lớp</span>
          <strong>{{ summaryValue(soDaRoiLop) }}</strong>
          <small>Theo trạng thái thành viên</small>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon purple">AG</div>
        <div class="summary-info">
          <span>Agent kết nối</span>
          <strong>{{ agentConnectedText }}</strong>
          <small>Theo dữ liệu Backend</small>
        </div>
      </div>
    </div>

    <!-- TABLE PANEL -->
    <section class="table-panel">
      <div class="panel-heading">
        <div>
          <h2>Danh sách sinh viên</h2>
          <p>
            {{ selectedClass
              ? selectedClass.ten_lop + " · " + (selectedClass.ma_lop || "Chưa có mã lớp")
              : "Chọn một lớp học để xem danh sách sinh viên" }}
          </p>
        </div>
        <span class="result-count">
          {{ danhSachHienThi.length }} kết quả
        </span>
      </div>

      <!-- FILTERS -->
      <div class="filters">
        <div class="search-box">
          <span class="search-icon">⌕</span>
          <input
            v-model="searchKeyword"
            type="search"
            placeholder="Tìm tên, MSSV hoặc email..."
            aria-label="Tìm kiếm sinh viên"
            :disabled="!selectedLopId"
          />
        </div>

        <select
          v-model="statusFilter"
          class="filter-select"
          aria-label="Lọc trạng thái thành viên"
          :disabled="!selectedLopId"
        >
          <option value="all">Tất cả trạng thái</option>
          <option value="dang_hoc">Đang học</option>
          <option value="da_roi_lop">Đã rời lớp</option>
        </select>

        <button
          v-if="searchKeyword || statusFilter !== 'all'"
          type="button"
          class="reset-btn"
          @click="resetFilters"
        >
          Xóa bộ lọc
        </button>
      </div>

      <!-- ERROR -->
      <div v-if="error" class="error-box" role="alert">
        <div>
          <strong>Không thể tải dữ liệu</strong>
          <p>{{ error }}</p>
        </div>
        <button type="button" @click="refreshData">
          Thử lại
        </button>
      </div>

      <!-- LOADING -->
      <div v-if="loadingLop || loading" class="state-box" role="status">
        <div class="spinner"></div>
        <strong>Đang tải danh sách sinh viên...</strong>
        <p>Hệ thống đang đồng bộ dữ liệu từ máy chủ.</p>
      </div>

      <!-- NO CLASS -->
      <div
        v-else-if="!selectedLopId"
        class="state-box"
      >
        <div class="state-icon">📚</div>
        <strong>Hãy chọn một lớp học</strong>
        <p>Chọn lớp ở phía trên để xem danh sách sinh viên.</p>
      </div>

      <!-- NO MEMBERS -->
      <div
        v-else-if="hasLoadedMembers && danhSach.length === 0"
        class="state-box"
      >
        <div class="state-icon">👥</div>
        <strong>Chưa có thành viên</strong>
        <p>Lớp học này hiện chưa có sinh viên trong danh sách.</p>
      </div>

      <!-- NO FILTER RESULTS -->
      <div
        v-else-if="hasLoadedMembers && danhSachHienThi.length === 0"
        class="state-box"
      >
        <div class="state-icon">⌕</div>
        <strong>Không tìm thấy sinh viên</strong>
        <p>Hãy thử từ khóa hoặc trạng thái khác.</p>
        <button type="button" class="clear-btn" @click="resetFilters">
          Xóa bộ lọc
        </button>
      </div>

      <!-- MEMBERS TABLE -->
      <div v-else-if="hasLoadedMembers" class="table-scroll">
        <table class="members-table">
          <thead>
            <tr>
              <th>STT</th>
              <th>SINH VIÊN</th>
              <th>MÃ SINH VIÊN</th>
              <th>EMAIL</th>
              <th>TRẠNG THÁI HỌC</th>
              <th>KẾT NỐI AGENT</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(sv, index) in danhSachHienThi"
              :key="sv.id ?? `${sv.ma_sinh_vien}-${index}`"
            >
              <td class="row-number">{{ index + 1 }}</td>

              <td>
                <div class="student-cell">
                  <div class="avatar">
                    {{ getInitials(sv.ho_ten) }}
                  </div>
                  <div class="student-info">
                    <strong>{{ sv.ho_ten || "Chưa cập nhật" }}</strong>
                    <small>Sinh viên</small>
                  </div>
                </div>
              </td>

              <td>
                <span class="student-code">
                  {{ sv.ma_sinh_vien || "—" }}
                </span>
              </td>

              <td class="email-cell">
                {{ sv.email || "Chưa cập nhật" }}
              </td>

              <td>
                <span
                  class="status-pill"
                  :class="classStatus(sv.trang_thai).className"
                >
                  <span class="status-dot"></span>
                  {{ classStatus(sv.trang_thai).text }}
                </span>
              </td>

              <td>
                <span
                  class="status-pill"
                  :class="agentStatus(sv.trang_thai_agent).className"
                >
                  <span class="status-dot"></span>
                  {{ agentStatus(sv.trang_thai_agent).text }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer class="panel-footer">
        <span>
          Hiển thị {{ danhSachHienThi.length }}
          / {{ danhSach.length }} sinh viên
        </span>

        <span v-if="lastUpdatedAt">
          Cập nhật lần cuối: {{ lastUpdatedAt }}
        </span>
      </footer>
    </section>

    <!-- NOTE -->
    <div class="info-note">
      <span class="note-icon">i</span>
      <p>
        Trạng thái học tập được lấy từ bảng
        <strong>thanh_vien_lop</strong>. Trạng thái Agent chỉ hiển thị
        khi Backend cung cấp dữ liệu kết nối; nếu không, hệ thống sẽ
        ghi <strong>Chưa xác định</strong>.
      </p>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
  watch,
} from "vue"
import { useRoute } from "vue-router"
import api from "../../../services/api"

const route = useRoute()

const danhSachLop = ref([])
const selectedLopId = ref("")
const danhSach = ref([])

const loadingLop = ref(false)
const loading = ref(false)
const hasLoadedMembers = ref(false)
const error = ref("")
const lastUpdatedAt = ref("")

const searchKeyword = ref("")
const statusFilter = ref("all")

let classRequestId = 0
let memberRequestId = 0

/* CHUẨN HÓA DANH SÁCH API */
function extractList(response) {
  const body = response?.data

  if (body?.status === false) {
    throw new Error(body.message || "API trả về lỗi")
  }

  const value = body?.data ?? body

  if (Array.isArray(value)) return value
  if (Array.isArray(value?.data)) return value.data

  throw new Error("API không trả về danh sách hợp lệ")
}

/* CHUẨN HÓA LỚP */
function normalizeLop(item) {
  return {
    id: item.id,
    ten_lop: item.ten_lop ?? item.tenLop ?? `Lớp ${item.id}`,
    ma_lop: item.ma_lop ?? item.maLop ?? "",
  }
}

/* CHUẨN HÓA THÀNH VIÊN */
function normalizeMember(item) {
  // Hỗ trợ dữ liệu phẳng và quan hệ Eloquent lồng nhau.
  const sinhVien = item.sinh_vien ?? item.sinhVien ?? {}
  const nguoiDung =
    sinhVien.nguoi_dung ??
    sinhVien.nguoiDung ??
    item.nguoi_dung ??
    item.nguoiDung ??
    {}

  return {
    id: item.id ?? sinhVien.id,
    ma_sinh_vien:
      item.ma_sinh_vien ??
      item.mssv ??
      sinhVien.ma_sinh_vien ??
      "",
    ho_ten:
      item.ho_ten ??
      item.hoTen ??
      nguoiDung.ho_ten ??
      "",
    email:
      item.email ??
      nguoiDung.email ??
      "",
    trang_thai:
      item.trang_thai ??
      item.trangThai ??
      null,
    trang_thai_agent:
      item.trang_thai_agent ??
      item.trangThaiAgent ??
      null,
  }
}

const selectedClass = computed(() =>
  danhSachLop.value.find(
    (lop) => String(lop.id) === selectedLopId.value
  )
)

/* THỐNG KÊ */
const canShowMemberStats = computed(
  () => Boolean(selectedLopId.value) && hasLoadedMembers.value
)

function summaryValue(value) {
  return canShowMemberStats.value
    ? Number(value).toLocaleString("vi-VN")
    : "—"
}

const soDangHoc = computed(() =>
  danhSach.value.filter(
    (sv) => sv.trang_thai === "dang_hoc"
  ).length
)

const soDaRoiLop = computed(() =>
  danhSach.value.filter(
    (sv) => sv.trang_thai === "da_roi_lop"
  ).length
)

const agentConnectedText = computed(() => {
  if (!canShowMemberStats.value) return "—"

  const allKnown = danhSach.value.every(
    (sv) => agentStatus(sv.trang_thai_agent).known
  )

  if (!allKnown) return "—"

  return danhSach.value
    .filter(
      (sv) => agentStatus(sv.trang_thai_agent).connected
    )
    .length.toLocaleString("vi-VN")
})

/* TÌM KIẾM & LỌC */
const danhSachHienThi = computed(() => {
  const keyword = searchKeyword.value
    .trim()
    .toLocaleLowerCase("vi-VN")

  return danhSach.value.filter((sv) => {
    const matchKeyword = [
      sv.ho_ten,
      sv.ma_sinh_vien,
      sv.email,
    ].some((value) =>
      String(value || "")
        .toLocaleLowerCase("vi-VN")
        .includes(keyword)
    )

    const matchStatus =
      statusFilter.value === "all" ||
      sv.trang_thai === statusFilter.value

    return matchKeyword && matchStatus
  })
})

function resetFilters() {
  searchKeyword.value = ""
  statusFilter.value = "all"
}

/* TRẠNG THÁI THÀNH VIÊN */
function classStatus(status) {
  if (status === "dang_hoc") {
    return {
      text: "Đang học",
      className: "success",
    }
  }

  if (status === "da_roi_lop") {
    return {
      text: "Đã rời lớp",
      className: "neutral",
    }
  }

  return {
    text: "Chưa xác định",
    className: "unknown",
  }
}

/* TRẠNG THÁI AGENT */
function agentStatus(status) {
  if (
    status === "connected" ||
    status === "online" ||
    status === true
  ) {
    return {
      text: "Đã kết nối",
      className: "success",
      known: true,
      connected: true,
    }
  }

  if (
    status === "disconnected" ||
    status === "offline" ||
    status === false
  ) {
    return {
      text: "Chưa kết nối",
      className: "neutral",
      known: true,
      connected: false,
    }
  }

  return {
    text: "Chưa xác định",
    className: "unknown",
    known: false,
    connected: false,
  }
}

/* AVATAR */
function getInitials(name) {
  const words = String(name || "")
    .trim()
    .split(/\s+/)
    .filter(Boolean)

  if (words.length === 0) return "SV"

  return words
    .slice(-2)
    .map((word) => word.charAt(0).toLocaleUpperCase("vi-VN"))
    .join("")
}

/* LOAD LỚP HỌC */
async function fetchLopHoc() {
  const currentRequest = ++classRequestId

  loadingLop.value = true
  error.value = ""

  try {
    const response = await api.get("/giang-vien/lop-hoc")

    if (currentRequest !== classRequestId) return

    danhSachLop.value = extractList(response).map(normalizeLop)

    const fromUrl = String(route.query.lopId ?? "")
    const previousId = selectedLopId.value

    const validClass = (id) =>
      danhSachLop.value.some(
        (lop) => String(lop.id) === id
      )

    if (fromUrl && validClass(fromUrl)) {
      selectedLopId.value = fromUrl
    } else if (previousId && validClass(previousId)) {
      selectedLopId.value = previousId
    } else {
      selectedLopId.value = ""
      danhSach.value = []
      hasLoadedMembers.value = false
    }

    if (selectedLopId.value) {
      await fetchThanhVien()
    }
  } catch (err) {
    if (currentRequest !== classRequestId) return

    console.error("Lỗi tải lớp học:", err)
    error.value = getErrorMessage(
      err,
      "Không thể tải danh sách lớp học."
    )
  } finally {
    if (currentRequest === classRequestId) {
      loadingLop.value = false
    }
  }
}

/* LOAD THÀNH VIÊN */
async function fetchThanhVien() {
  const currentRequest = ++memberRequestId
  const lopId = selectedLopId.value

  error.value = ""
  hasLoadedMembers.value = false
  danhSach.value = []

  if (!lopId) {
    loading.value = false
    return
  }

  loading.value = true

  try {
    const response = await api.get(
      `/giang-vien/lop-hoc/${encodeURIComponent(lopId)}/thanh-vien`
    )

    if (currentRequest !== memberRequestId) return

    danhSach.value = extractList(response).map(normalizeMember)
    hasLoadedMembers.value = true
    lastUpdatedAt.value = new Date().toLocaleString("vi-VN")
  } catch (err) {
    if (currentRequest !== memberRequestId) return

    console.error("Lỗi tải thành viên:", err)

    error.value = getErrorMessage(
      err,
      "Không thể tải danh sách thành viên của lớp."
    )
  } finally {
    if (currentRequest === memberRequestId) {
      loading.value = false
    }
  }
}

function getErrorMessage(err, fallback) {
  const status = err.response?.status

  if (status === 401) {
    return "Phiên đăng nhập không hợp lệ hoặc đã hết hạn."
  }
  if (status === 403) {
    return "Bạn không có quyền xem dữ liệu của lớp này."
  }
  if (status === 404) {
    return "Không tìm thấy lớp học hoặc API tương ứng."
  }
  if (status >= 500) {
    return "Máy chủ gặp lỗi khi xử lý dữ liệu."
  }

  return err.message || fallback
}

function changeClass() {
  resetFilters()
  fetchThanhVien()
}

function refreshData() {
  if (loading.value || loadingLop.value) return
  fetchLopHoc()
}

watch(
  () => route.query.lopId,
  (newId) => {
    const id = String(newId ?? "")

    if (
      id &&
      danhSachLop.value.some((lop) => String(lop.id) === id) &&
      selectedLopId.value !== id
    ) {
      selectedLopId.value = id
      resetFilters()
      fetchThanhVien()
    }
  }
)

onMounted(fetchLopHoc)

onBeforeUnmount(() => {
  classRequestId++
  memberRequestId++
})
</script>

<style scoped>
.member-page {
  width: 100%;
  min-height: calc(100vh - 80px);
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
  gap: 20px;
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
  line-height: 1.7;
}

.refresh-btn {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 11px 16px;
  border: 0;
  border-radius: 11px;
  background: #2563eb;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.refresh-btn:hover:not(:disabled) {
  background: #1d4ed8;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.refresh-btn span {
  font-size: 18px;
}

/* SELECT CLASS */
.selector-panel {
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 20px;
  margin-bottom: 22px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
}

.selector-icon {
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 900;
}

.selector-content {
  flex: 1;
  min-width: 0;
}

.selector-content label {
  display: block;
  margin-bottom: 7px;
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
}

.selector-content select,
.filter-select {
  width: 100%;
  padding: 11px 13px;
  outline: none;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #172033;
  font-size: 12px;
}

.selector-content select:focus,
.filter-select:focus {
  border-color: #60a5fa;
}

.back-link {
  color: #2563eb;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
}

/* SUMMARY */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 15px;
  margin-bottom: 22px;
}

.summary-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 19px;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: #fff;
}

.summary-icon {
  width: 43px;
  height: 43px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 11px;
  font-size: 12px;
  font-weight: 900;
}

.summary-info {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: 5px;
}

.summary-info > span {
  color: #64748b;
  font-size: 11px;
}

.summary-info strong {
  color: #172033;
  font-size: 25px;
  font-weight: 800;
}

.summary-info small {
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

/* MAIN TABLE */
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
  gap: 14px;
  padding: 21px;
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

.result-count {
  border-radius: 999px;
  padding: 7px 12px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.filters {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  padding: 0 21px 20px;
}

.search-box {
  flex: 1;
  min-width: 210px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
}

.search-box:focus-within {
  border-color: #60a5fa;
}

.search-icon {
  font-size: 23px;
  color: #94a3b8;
}

.search-box input {
  width: 100%;
  padding: 11px 0;
  border: none;
  outline: none;
  background: transparent;
  color: #172033;
  font-size: 12px;
}

.filter-select {
  width: 195px;
}

.reset-btn,
.clear-btn {
  padding: 11px 13px;
  border: 0;
  border-radius: 9px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  cursor: pointer;
}

.table-scroll {
  width: 100%;
  overflow-x: auto;
}

.members-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
  white-space: nowrap;
}

.members-table thead {
  background: #f8fafc;
}

.members-table th {
  padding: 15px 19px;
  color: #64748b;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.4px;
}

.members-table td {
  padding: 15px 19px;
  border-top: 1px solid #f1f5f9;
  font-size: 12px;
}

.members-table tbody tr:hover {
  background: #f8fafc;
}

.row-number {
  color: #94a3b8;
}

.student-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 37px;
  height: 37px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
}

.student-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.student-info strong {
  color: #172033;
  font-size: 12px;
}

.student-info small {
  color: #94a3b8;
  font-size: 10px;
}

.student-code {
  color: #2563eb;
  font-weight: 800;
}

.email-cell {
  color: #64748b;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 11px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-pill.success {
  background: #dcfce7;
  color: #15803d;
}

.status-pill.neutral {
  background: #f1f5f9;
  color: #64748b;
}

.status-pill.unknown {
  background: #fef3c7;
  color: #92400e;
}

.panel-footer {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  padding: 17px 21px;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

/* EMPTY + ERRORS */
.state-box {
  min-height: 270px;
  padding: 25px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 11px;
  text-align: center;
}

.state-box strong {
  font-size: 14px;
}

.state-box p {
  margin: 0;
  color: #94a3b8;
  font-size: 11px;
}

.state-icon {
  font-size: 30px;
}

.spinner {
  width: 29px;
  height: 29px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.error-box {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  margin: 0 20px 15px;
  padding: 14px;
  border: 1px solid #fecaca;
  border-radius: 10px;
  background: #fef2f2;
  color: #b91c1c;
}

.error-box strong {
  font-size: 12px;
}

.error-box p {
  margin: 5px 0 0;
  font-size: 11px;
}

.error-box button {
  border: 1px solid #fca5a5;
  border-radius: 8px;
  padding: 9px 12px;
  background: #fff;
  color: #b91c1c;
  cursor: pointer;
}

/* NOTE */
.info-note {
  display: flex;
  align-items: flex-start;
  gap: 11px;
  margin-top: 18px;
  padding: 15px;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  background: #eff6ff;
}

.note-icon {
  width: 23px;
  height: 23px;
  flex-shrink: 0;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #2563eb;
  color: white;
  font-size: 12px;
  font-weight: 800;
}

.info-note p {
  margin: 2px 0 0;
  color: #475569;
  font-size: 11px;
  line-height: 1.7;
}

/* DARK MODE */
:global(html.dark-theme) .member-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .summary-info strong,
:global(html.dark-theme) .panel-heading h2,
:global(html.dark-theme) .student-info strong {
  color: #fff;
}

:global(html.dark-theme) .selector-panel,
:global(html.dark-theme) .summary-card,
:global(html.dark-theme) .table-panel {
  background: #111;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .selector-content select,
:global(html.dark-theme) .filter-select,
:global(html.dark-theme) .search-box {
  background: #202327;
  color: #fff;
  border-color: rgba(255, 255, 255, 0.14);
}

:global(html.dark-theme) .search-box input {
  color: #fff;
}

:global(html.dark-theme) .members-table thead {
  background: #202327;
}

:global(html.dark-theme) .members-table td,
:global(html.dark-theme) .panel-footer {
  border-color: rgba(255, 255, 255, 0.12);
}

:global(html.dark-theme) .members-table tbody tr:hover {
  background: #202327;
}

:global(html.dark-theme) .status-pill.neutral {
  background: #2b3038;
  color: #cbd5e1;
}

:global(html.dark-theme) .info-note {
  background: rgba(37, 99, 235, 0.12);
  border-color: rgba(96, 165, 250, 0.3);
}

:global(html.dark-theme) .info-note p {
  color: #cbd5e1;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .summary-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .member-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .page-header h1 {
    font-size: 23px;
  }

  .selector-panel {
    flex-wrap: wrap;
  }

  .selector-content {
    min-width: 200px;
  }

  .back-link {
    width: 100%;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }

  .filter-select {
    width: 100%;
  }
}
</style>
