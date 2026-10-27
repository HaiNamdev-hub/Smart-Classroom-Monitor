
<template>
  <div class="class-page">

    <!-- HEADER -->
    <div class="page-header">
      <div>
        <div class="eyebrow">QUẢN LÝ GIẢNG DẠY</div>
        <h1>📚 Quản lý lớp học</h1>
        <p>
          Theo dõi và quản lý những lớp học mà bạn
          đang được phân công giảng dạy.
        </p>
      </div>

      <button
        class="refresh-btn"
        type="button"
        :disabled="loading"
        @click="fetchData"
      >
        <span :class="{ spinning: loading }">↻</span>
        {{ loading ? "Đang cập nhật..." : "Làm mới" }}
      </button>
    </div>

    <!-- SUMMARY -->
    <div class="summary-grid">
      <div class="summary-card">
        <div class="summary-icon blue">LH</div>
        <div>
          <span>Tổng số lớp</span>
          <strong>{{ loading && !hasLoaded ? "..." : danhSachLop.length }}</strong>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon green">SV</div>
        <div>
          <span>Tổng lượt thành viên đang học</span>
          <strong>{{ tongThanhVienText }}</strong>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-icon purple">TK</div>
        <div>
          <span>Kết quả tìm kiếm</span>
          <strong>{{ danhSachHienThi.length }}</strong>
        </div>
      </div>
    </div>

    <!-- FILTER -->
    <section class="toolbar">
      <div class="toolbar-heading">
        <h2>Danh sách lớp học</h2>
        <p>
          Các lớp học được phân công cho tài khoản
          giảng viên hiện tại
        </p>
      </div>

      <div class="search-field">
        <span class="search-symbol">⌕</span>
        <input
          v-model="searchKeyword"
          type="search"
          placeholder="Tìm theo tên hoặc mã lớp..."
          aria-label="Tìm kiếm lớp học"
        />
      </div>
    </section>

    <!-- ERROR -->
    <div v-if="error" class="error-box" role="alert">
      <div>
        <strong>Không thể tải danh sách lớp học</strong>
        <p>{{ error }}</p>
      </div>

      <button type="button" @click="fetchData">
        Thử lại
      </button>
    </div>

    <!-- LOADING -->
    <div v-if="loading && !hasLoaded" class="state-box">
      <div class="spinner"></div>
      <strong>Đang tải lớp học...</strong>
      <p>Hệ thống đang lấy dữ liệu từ máy chủ.</p>
    </div>

    <!-- NO CLASSES -->
    <div
      v-else-if="hasLoaded && danhSachLop.length === 0"
      class="state-box"
    >
      <div class="state-icon">📚</div>
      <strong>Chưa có lớp học được phân công</strong>
      <p>
        Khi được phân công giảng dạy, lớp học của bạn
        sẽ xuất hiện tại đây.
      </p>
    </div>

    <!-- NO SEARCH RESULTS -->
    <div
      v-else-if="hasLoaded && danhSachHienThi.length === 0"
      class="state-box"
    >
      <div class="state-icon">⌕</div>
      <strong>Không tìm thấy lớp học</strong>
      <p>Hãy thử tìm kiếm bằng tên hoặc mã lớp khác.</p>

      <button
        type="button"
        class="clear-btn"
        @click="searchKeyword = ''"
      >
        Xóa tìm kiếm
      </button>
    </div>

    <!-- CLASS LIST -->
    <div v-else-if="hasLoaded" class="classes-grid">
      <article
        v-for="lop in danhSachHienThi"
        :key="lop.id"
        class="class-card"
      >
        <div class="card-top">
          <div class="class-icon">📖</div>

          <span class="class-code">
            {{ lop.ma_lop || "Chưa có mã lớp" }}
          </span>
        </div>

        <div class="class-content">
          <h3>{{ lop.ten_lop || "Chưa có tên lớp" }}</h3>

          <p class="class-description">
            {{ lop.mo_ta || "Chưa có mô tả cho lớp học này." }}
          </p>
        </div>

        <div class="card-divider"></div>

        <div class="class-details">
          <div class="detail">
            <span>Thành viên đang học</span>
            <strong>
              {{ lop.si_so === null ? "Chưa có dữ liệu" : lop.si_so }}
            </strong>
          </div>

          <div class="detail">
            <span>Mã lớp</span>
            <strong>{{ lop.ma_lop || "—" }}</strong>
          </div>
        </div>

        <div class="card-actions">
          <router-link
            :to="{
              path: '/giang-vien/thanh-vien-lop',
              query: { lopId: lop.id }
            }"
            class="view-btn"
          >
            Xem thành viên
            <span>→</span>
          </router-link>
        </div>
      </article>
    </div>

    <!-- FOOTER -->
    <div class="page-footer">
      <span>
        Hiển thị {{ danhSachHienThi.length }}
        / {{ danhSachLop.length }} lớp học
      </span>

      <span v-if="lastUpdatedAt">
        Cập nhật lần cuối: {{ lastUpdatedAt }}
      </span>
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

import api from "../../../services/api"

/* STATE */
const danhSachLop = ref([])
const searchKeyword = ref("")
const loading = ref(false)
const hasLoaded = ref(false)
const error = ref("")
const lastUpdatedAt = ref("")

let requestId = 0

/* ĐƯA DỮ LIỆU BACKEND VỀ CÙNG CẤU TRÚC */
function normalizeLop(item) {
  const rawSiSo =
    item.si_so ??
    item.siSo ??
    item.thanh_vien_dang_hoc_count ??
    null

  const siSo =
    rawSiSo !== null &&
    rawSiSo !== "" &&
    Number.isInteger(Number(rawSiSo)) &&
    Number(rawSiSo) >= 0
      ? Number(rawSiSo)
      : null

  return {
    id: item.id,
    ma_lop: item.ma_lop ?? item.maLop ?? "",
    ten_lop: item.ten_lop ?? item.tenLop ?? "",
    mo_ta: item.mo_ta ?? item.moTa ?? "",
    si_so: siSo,
  }
}

/* TÌM KIẾM */
const danhSachHienThi = computed(() => {
  const keyword = searchKeyword.value
    .trim()
    .toLocaleLowerCase("vi-VN")

  if (!keyword) {
    return danhSachLop.value
  }

  return danhSachLop.value.filter((lop) => {
    const tenLop = String(lop.ten_lop)
      .toLocaleLowerCase("vi-VN")

    const maLop = String(lop.ma_lop)
      .toLocaleLowerCase("vi-VN")

    return (
      tenLop.includes(keyword) ||
      maLop.includes(keyword)
    )
  })
})

/* TỔNG LƯỢT THÀNH VIÊN CÁC LỚP */
const tongThanhVienText = computed(() => {
  if (!hasLoaded.value) return "—"

  if (
    danhSachLop.value.some(
      (lop) => lop.si_so === null
    )
  ) {
    return "—"
  }

  return danhSachLop.value
    .reduce((sum, lop) => sum + lop.si_so, 0)
    .toLocaleString("vi-VN")
})

/* GỌI API */
async function fetchData() {
  if (loading.value) return

  const currentRequest = ++requestId

  loading.value = true
  error.value = ""

  try {
    const response = await api.get(
      "/giang-vien/lop-hoc"
    )

    if (currentRequest !== requestId) return

    const body = response.data

    if (body?.status === false) {
      throw new Error(
        body.message || "API trả về lỗi"
      )
    }

    const data = body?.data ?? body

    if (!Array.isArray(data)) {
      throw new Error(
        "API cần trả về một mảng danh sách lớp học."
      )
    }

    danhSachLop.value = data.map(normalizeLop)
    hasLoaded.value = true

    lastUpdatedAt.value =
      new Date().toLocaleString("vi-VN")

  } catch (err) {
    if (currentRequest !== requestId) return

    console.error("Lỗi tải lớp học:", err)

    const status = err.response?.status

    if (status === 401) {
      error.value =
        "Phiên đăng nhập đã hết hạn."
    } else if (status === 403) {
      error.value =
        "Bạn không có quyền xem lớp học."
    } else if (status === 404) {
      error.value =
        "Không tìm thấy API danh sách lớp học."
    } else if (status >= 500) {
      error.value =
        "Máy chủ gặp lỗi khi xử lý dữ liệu."
    } else {
      error.value =
        err.message ||
        "Không thể tải danh sách lớp học."
    }
  } finally {
    if (currentRequest === requestId) {
      loading.value = false
    }
  }
}

onMounted(fetchData)

onBeforeUnmount(() => {
  requestId++
})
</script>

<style scoped>
.class-page {
  min-height: calc(100vh - 80px);
  width: 100%;
  box-sizing: border-box;
  padding: 28px;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, system-ui, sans-serif;
}

/* HEADER */
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
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
  font-size: 13px;
  color: #64748b;
  line-height: 1.6;
}

.refresh-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
  border: 0;
  border-radius: 10px;
  padding: 11px 16px;
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
  opacity: 0.65;
  cursor: not-allowed;
}

.refresh-btn span {
  font-size: 18px;
}

.spinning {
  animation: spin 0.8s linear infinite;
}

/* SUMMARY */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.summary-card {
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 15px;
  padding: 19px;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.summary-icon {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  border-radius: 13px;
  font-size: 12px;
  font-weight: 900;
}

.blue {
  color: #2563eb;
  background: #eff6ff;
}

.green {
  color: #16a34a;
  background: #f0fdf4;
}

.purple {
  color: #9333ea;
  background: #faf5ff;
}

.summary-card > div:last-child {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.summary-card span {
  color: #64748b;
  font-size: 11px;
}

.summary-card strong {
  color: #172033;
  font-size: 26px;
  font-weight: 800;
}

/* TOOLBAR */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 18px;
  margin-bottom: 18px;
}

.toolbar-heading h2 {
  margin: 0 0 6px;
  font-size: 17px;
  font-weight: 800;
}

.toolbar-heading p {
  margin: 0;
  color: #64748b;
  font-size: 11px;
}

.search-field {
  display: flex;
  align-items: center;
  gap: 10px;
  width: min(100%, 320px);
  padding: 0 13px;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  background: #fff;
}

.search-field:focus-within {
  border-color: #60a5fa;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08);
}

.search-symbol {
  font-size: 24px;
  color: #94a3b8;
}

.search-field input {
  width: 100%;
  min-width: 0;
  border: 0;
  outline: 0;
  padding: 12px 0;
  background: transparent;
  color: #172033;
  font-size: 12px;
}

/* CARDS */
.classes-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}

.class-card {
  min-width: 0;
  display: flex;
  flex-direction: column;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  transition: transform 0.2s, box-shadow 0.2s;
}

.class-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
}

.card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 17px;
}

.class-icon {
  display: grid;
  place-items: center;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: #eff6ff;
  font-size: 22px;
}

.class-code {
  padding: 7px 10px;
  border-radius: 8px;
  background: #f1f5f9;
  color: #475569;
  font-size: 10px;
  font-weight: 700;
}

.class-content {
  flex: 1;
}

.class-content h3 {
  margin: 0 0 9px;
  color: #172033;
  font-size: 17px;
  font-weight: 800;
  line-height: 1.45;
}

.class-description {
  margin: 0;
  color: #64748b;
  font-size: 12px;
  line-height: 1.7;
  overflow-wrap: anywhere;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-divider {
  height: 1px;
  background: #e5e7eb;
  margin: 20px 0 15px;
}

.class-details {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
}

.detail {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.detail span {
  color: #94a3b8;
  font-size: 10px;
}

.detail strong {
  color: #334155;
  font-size: 12px;
  overflow-wrap: anywhere;
}

/* CARD ACTION */
.card-actions {
  margin-top: 20px;
}

.view-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 14px;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 800;
  text-decoration: none;
}

.view-btn:hover {
  background: #dbeafe;
}

.view-btn span {
  font-size: 17px;
}

/* STATES */
.state-box {
  min-height: 290px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 12px;
  padding: 25px;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  background: #fff;
  text-align: center;
}

.state-icon {
  display: grid;
  place-items: center;
  width: 62px;
  height: 62px;
  border-radius: 50%;
  background: #eff6ff;
  font-size: 28px;
}

.state-box strong {
  font-size: 15px;
}

.state-box p {
  margin: 0;
  color: #64748b;
  font-size: 12px;
}

.spinner {
  width: 30px;
  height: 30px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.clear-btn {
  margin-top: 10px;
  padding: 10px 14px;
  border: 0;
  border-radius: 8px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.error-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  padding: 16px;
  border: 1px solid #fecaca;
  border-radius: 12px;
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
  padding: 9px 14px;
  border: 1px solid #fca5a5;
  border-radius: 8px;
  background: #fff;
  color: #b91c1c;
  cursor: pointer;
}

.page-footer {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 25px;
  padding: 16px 0;
  border-top: 1px solid #e5e7eb;
  color: #94a3b8;
  font-size: 11px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* DARK MODE */
:global(html.dark-theme) .class-page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .toolbar-heading h2,
:global(html.dark-theme) .summary-card strong,
:global(html.dark-theme) .class-content h3,
:global(html.dark-theme) .detail strong,
:global(html.dark-theme) .state-box strong {
  color: #fff;
}

:global(html.dark-theme) .summary-card,
:global(html.dark-theme) .class-card,
:global(html.dark-theme) .search-field,
:global(html.dark-theme) .state-box {
  background: #111;
  border-color: rgba(255, 255, 255, 0.15);
}

:global(html.dark-theme) .search-field input {
  color: #fff;
}

:global(html.dark-theme) .card-divider,
:global(html.dark-theme) .page-footer {
  border-color: rgba(255, 255, 255, 0.15);
}

:global(html.dark-theme) .card-divider {
  background: rgba(255, 255, 255, 0.15);
}

:global(html.dark-theme) .class-code {
  background: #24272d;
  color: #cbd5e1;
}

:global(html.dark-theme) .view-btn {
  background: rgba(37, 99, 235, 0.15);
  color: #60a5fa;
}

:global(html.dark-theme) .blue {
  background: rgba(37, 99, 235, 0.15);
  color: #60a5fa;
}

:global(html.dark-theme) .green {
  background: rgba(22, 163, 74, 0.15);
  color: #4ade80;
}

:global(html.dark-theme) .purple {
  background: rgba(147, 51, 234, 0.15);
  color: #c084fc;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .classes-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 850px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 650px) {
  .class-page {
    padding: 16px;
  }

  .page-header {
    flex-direction: column;
  }

  .page-header h1 {
    font-size: 23px;
  }

  .classes-grid {
    grid-template-columns: 1fr;
  }

  .search-field {
    width: 100%;
  }

  .error-box {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
