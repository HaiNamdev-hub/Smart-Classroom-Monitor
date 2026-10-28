<template>
  <section class="camera-alerts">
    <div class="alerts-header">
      <div>
        <h2>📷 Cảnh báo camera khi thi</h2>
        <p>Theo dõi khuôn mặt, điện thoại và tình trạng khóa bài kiểm tra của Sinh viên.</p>
      </div>
      <button type="button" :disabled="loading" @click="fetchAlerts">
        {{ loading ? 'Đang tải...' : '↻ Làm mới' }}
      </button>
    </div>

    <div class="alert-stats">
      <div><span>Tổng cảnh báo camera</span><strong>{{ alerts.length }}</strong></div>
      <div><span>📱 Cảnh báo điện thoại</span><strong>{{ phoneAlertCount }}</strong></div>
      <div><span>Sinh viên có cảnh báo</span><strong>{{ studentCount }}</strong></div>
      <div><span>🔒 Bài thi bị khóa</span><strong>{{ lockedExamCount }}</strong></div>
    </div>

    <div class="toolbar">
      <input v-model.trim="search" type="search" placeholder="Tìm tên, MSSV, bài kiểm tra..."
        aria-label="Tìm cảnh báo" />
      <select v-model="typeFilter" aria-label="Lọc loại cảnh báo">
        <option value="ALL">Tất cả cảnh báo</option>
        <option value="phat_hien_dien_thoai">📱 Phát hiện điện thoại</option>
        <option value="khong_co_khuon_mat">Không thấy khuôn mặt</option>
        <option value="nhieu_khuon_mat">Nhiều khuôn mặt</option>
        <option value="camera_ngat">Camera bị ngắt</option>
      </select>
      <select v-model="lockFilter" aria-label="Lọc trạng thái khóa">
        <option value="ALL">Tất cả trạng thái</option>
        <option value="LOCKED">Đã khóa bài</option>
        <option value="UNLOCKED">Chưa khóa</option>
      </select>
    </div>

    <div v-if="error" class="error-box" role="alert">
      {{ error }}
      <button type="button" @click="fetchAlerts">Thử lại</button>
    </div>

    <div v-if="loading && !hasLoaded" class="empty-box">Đang tải cảnh báo...</div>
    <div v-else-if="hasLoaded && alerts.length === 0" class="empty-box">
      <span>✓</span>
      <h3>Chưa có cảnh báo camera</h3>
      <p>Cảnh báo sẽ xuất hiện khi Laravel ghi nhận sự kiện từ Sinh viên.</p>
    </div>
    <div v-else-if="hasLoaded && filteredAlerts.length === 0" class="empty-box">
      <h3>Không tìm thấy cảnh báo phù hợp</h3>
      <p>Hãy thay đổi từ khóa hoặc bộ lọc.</p>
    </div>

    <div v-else-if="hasLoaded" class="alert-list">
      <article v-for="item in filteredAlerts" :key="item.id" class="alert-item">
        <div class="alert-icon" :class="{ 'phone-icon': item.loaiCanhBao === 'phat_hien_dien_thoai' }">
          {{ getAlertIcon(item.loaiCanhBao) }}
        </div>
        <div class="alert-info">
          <strong>{{ getAlertName(item.loaiCanhBao) }}</strong>
          <p>{{ item.tenSinhVien || 'Chưa cập nhật' }} · {{ item.mssv || 'Chưa có MSSV' }}</p>
          <small>{{ item.tenBaiKiemTra || 'Bài kiểm tra' }} · {{ item.tenLop || 'Chưa có lớp' }}</small>
          <div class="counters">
            <span>📱 Điện thoại: {{ item.soLanDienThoai }}/3</span>
            <span>📷 Camera khác: {{ item.soLanCamera }}/5</span>
            <span>↗ Rời tab: {{ item.soLanViPham }}/3</span>
          </div>
          <span v-if="item.biKhoa" class="lock-status locked">🔒 Đã khóa bài: {{ getLockReason(item.lyDoKhoa) }}</span>
          <span v-else class="lock-status unlocked">Chưa khóa bài</span>
        </div>
        <div class="alert-time">{{ formatDate(item.thoiGian) }}</div>
      </article>
    </div>

    <div class="alert-footer">
      <span>Hiển thị {{ filteredAlerts.length }}/{{ alerts.length }} cảnh báo</span>
      <span v-if="lastUpdated">Cập nhật: {{ lastUpdated }}</span>
    </div>
    <p class="note">Cảnh báo từ AI là tín hiệu cần xem xét, không tự chứng minh Sinh viên gian lận. Trạng thái khóa được
      tính theo dữ liệu Backend.</p>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import api from '../../../services/api'

const alerts = ref([])
const loading = ref(false)
const hasLoaded = ref(false)
const error = ref('')
const lastUpdated = ref('')
const search = ref('')
const typeFilter = ref('ALL')
const lockFilter = ref('ALL')
let refreshTimer = null
let disposed = false
let requestVersion = 0

const phoneAlertCount = computed(() =>
  alerts.value.filter(item => item.loaiCanhBao === 'phat_hien_dien_thoai').length
)

const studentCount = computed(() => {
  const ids = alerts.value
    .map(item => item.sinhVienId != null ? `id:${item.sinhVienId}` : item.mssv ? `mssv:${item.mssv}` : null)
    .filter(Boolean)
  return new Set(ids).size
})

const lockedExamCount = computed(() => {
  const keys = new Set()
  for (const item of alerts.value) {
    if (item.biKhoa && item.baiTapId != null && item.sinhVienId != null) {
      keys.add(`${item.baiTapId}:${item.sinhVienId}`)
    }
  }
  return keys.size
})

const filteredAlerts = computed(() => {
  const keyword = search.value.toLocaleLowerCase('vi-VN')
  return alerts.value.filter(item => {
    const text = [item.tenSinhVien, item.mssv, item.tenBaiKiemTra, item.tenLop, getAlertName(item.loaiCanhBao)]
      .join(' ').toLocaleLowerCase('vi-VN')
    return text.includes(keyword)
      && (typeFilter.value === 'ALL' || item.loaiCanhBao === typeFilter.value)
      && (lockFilter.value === 'ALL' || (lockFilter.value === 'LOCKED' ? item.biKhoa : !item.biKhoa))
  })
})

function getAlertName(type) {
  return ({
    khong_co_khuon_mat: 'Không phát hiện khuôn mặt',
    nhieu_khuon_mat: 'Phát hiện từ 2 khuôn mặt trở lên',
    camera_ngat: 'Camera bị ngắt',
    phat_hien_dien_thoai: 'Phát hiện điện thoại',
  })[type] || type || 'Cảnh báo camera'
}

function getAlertIcon(type) {
  return ({
    khong_co_khuon_mat: '📷',
    nhieu_khuon_mat: '👥',
    camera_ngat: '📹',
    phat_hien_dien_thoai: '📱',
  })[type] || '⚠'
}

function getLockReason(reason) {
  return ({
    dien_thoai: 'phát hiện điện thoại 3 lần',
    camera: '5 cảnh báo camera khác',
    roi_tab: 'rời tab 3 lần',
  })[reason] || 'đạt ngưỡng vi phạm'
}

function formatDate(value) {
  if (!value) return 'Chưa cập nhật'
  const date = new Date(typeof value === 'string' ? value.replace(' ', 'T') : value)
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleString('vi-VN')
}

function normalizeAlert(item) {
  return {
    id: item.id,
    baiTapId: item.baiTapId ?? item.bai_tap_id ?? null,
    sinhVienId: item.sinhVienId ?? item.sinh_vien_id ?? null,
    tenSinhVien: item.tenSinhVien ?? item.ho_ten ?? '',
    mssv: item.mssv ?? item.ma_sinh_vien ?? '',
    tenBaiKiemTra: item.tenBaiKiemTra ?? item.tieu_de ?? '',
    tenLop: item.tenLop ?? item.ten_lop ?? '',
    loaiCanhBao: item.loaiCanhBao ?? item.loai_canh_bao ?? '',
    thoiGian: item.thoiGian ?? item.created_at ?? null,
    soLanDienThoai: Number(item.soLanDienThoai ?? 0),
    soLanCamera: Number(item.soLanCamera ?? 0),
    soLanViPham: Number(item.soLanViPham ?? 0),
    biKhoa: item.biKhoa === true || item.biKhoa === 1 || item.biKhoa === '1',
    lyDoKhoa: item.lyDoKhoa ?? null,
  }
}

async function fetchAlerts() {
  if (loading.value || disposed) return
  const current = ++requestVersion
  loading.value = true
  try {
    const response = await api.get('/giang-vien/canh-bao-camera')
    if (disposed || current !== requestVersion) return
    if (response.data?.status === false || response.data?.success === false) {
      throw new Error(response.data?.message || 'Backend không tải được cảnh báo.')
    }
    const list = response.data?.data ?? response.data
    if (!Array.isArray(list)) throw new Error('API cảnh báo camera không trả về danh sách.')
    alerts.value = list.map(normalizeAlert)
    alerts.value.sort((a, b) => (Date.parse(String(b.thoiGian).replace(' ', 'T')) || 0) - (Date.parse(String(a.thoiGian).replace(' ', 'T')) || 0))
    hasLoaded.value = true
    lastUpdated.value = new Date().toLocaleString('vi-VN')
    error.value = ''
  } catch (err) {
    if (!disposed && current === requestVersion) {
      error.value = err.response?.data?.message || err.message || 'Không thể tải cảnh báo camera.'
    }
  } finally {
    if (current === requestVersion) loading.value = false
  }
}

onMounted(() => {
  disposed = false
  fetchAlerts()
  refreshTimer = window.setInterval(fetchAlerts, 10000)
})

onBeforeUnmount(() => {
  disposed = true
  requestVersion++
  if (refreshTimer !== null) window.clearInterval(refreshTimer)
  refreshTimer = null
})
</script>

<style scoped>
.camera-alerts {
  padding: 22px;
  margin: 20px 0;
  border-radius: 16px;
  background: #111315;
  border: 1px solid #35383e;
  color: #f8fafc;
}

.alerts-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 22px;
}

.alerts-header h2 {
  margin: 0 0 8px;
  font-size: 18px;
}

.alerts-header p {
  margin: 0;
  color: #94a3b8;
  font-size: 12px;
  line-height: 1.6;
}

.alerts-header button,
.error-box button {
  padding: 10px 14px;
  border: 0;
  border-radius: 9px;
  background: #2563eb;
  color: #fff;
  font-weight: 700;
  cursor: pointer;
}

.alerts-header button:disabled {
  opacity: .5;
  cursor: not-allowed;
}

.alert-stats {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 22px;
}

.alert-stats>div {
  display: flex;
  flex-direction: column;
  gap: 9px;
  padding: 18px;
  border-radius: 12px;
  background: #1e2228;
  border: 1px solid #343a42;
}

.alert-stats span {
  color: #94a3b8;
  font-size: 12px;
}

.alert-stats strong {
  font-size: 27px;
}

.toolbar {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 16px;
}

.toolbar input,
.toolbar select {
  border: 1px solid #343a42;
  background: #1e2228;
  color: #f8fafc;
  border-radius: 9px;
  padding: 11px;
  min-width: 170px;
}

.toolbar input {
  flex: 1;
  min-width: 220px;
}

.alert-list {
  display: flex;
  flex-direction: column;
}

.alert-item {
  display: flex;
  align-items: flex-start;
  gap: 15px;
  padding: 17px 0;
  border-top: 1px solid #35383e;
}

.alert-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  color: #fca5a5;
  background: rgba(220, 38, 38, .15);
  font-size: 20px;
}

.phone-icon {
  background: rgba(245, 158, 11, .18);
}

.alert-info {
  flex: 1;
  min-width: 0;
}

.alert-info>strong {
  font-size: 13px;
  color: #fca5a5;
}

.alert-info p {
  margin: 9px 0;
  font-size: 12px;
}

.alert-info small {
  font-size: 11px;
  color: #94a3b8;
}

.counters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 11px;
}

.counters span {
  font-size: 11px;
  border-radius: 7px;
  background: #252930;
  padding: 6px 9px;
}

.alert-time {
  font-size: 11px;
  color: #94a3b8;
  white-space: nowrap;
}

.lock-status {
  display: inline-block;
  margin-top: 12px;
  padding: 7px 10px;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 700;
}

.locked {
  color: #fecaca;
  background: rgba(220, 38, 38, .18);
}

.unlocked {
  color: #86efac;
  background: rgba(22, 163, 74, .12);
}

.alert-footer {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  border-top: 1px solid #35383e;
  padding-top: 14px;
  color: #94a3b8;
  font-size: 11px;
}

.note {
  color: #94a3b8;
  font-size: 11px;
  line-height: 1.6;
}

.empty-box {
  text-align: center;
  padding: 36px;
  color: #94a3b8;
}

.empty-box>span {
  color: #4ade80;
  font-size: 35px;
}

.empty-box h3 {
  font-size: 15px;
  color: #fff;
}

.empty-box p {
  font-size: 12px;
}

.error-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px;
  margin-bottom: 14px;
  background: rgba(220, 38, 38, .12);
  border-radius: 10px;
  color: #fca5a5;
  font-size: 12px;
}

@media(max-width:1050px) {
  .alert-stats {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media(max-width:650px) {

  .alerts-header,
  .alert-item {
    flex-direction: column;
  }

  .alert-time {
    white-space: normal;
  }

  .alert-stats {
    grid-template-columns: 1fr;
  }
}
</style>
