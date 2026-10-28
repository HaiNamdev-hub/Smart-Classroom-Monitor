
<template>
  <section class="camera-alerts">
    <div class="alerts-header">
      <div>
        <h2>📷 Cảnh báo camera khi thi</h2>
        <p>
          Theo dõi cảnh báo khuôn mặt của Sinh viên
          trong các bài kiểm tra.
        </p>
      </div>

      <button
        type="button"
        :disabled="loading"
        @click="fetchAlerts"
      >
        {{ loading ? "Đang tải..." : "↻ Làm mới" }}
      </button>
    </div>

    <div class="alert-stats">
      <div>
        <span>Tổng cảnh báo</span>
        <strong>{{ alerts.length }}</strong>
      </div>

      <div>
        <span>Sinh viên có cảnh báo</span>
        <strong>{{ studentCount }}</strong>
      </div>
    </div>

    <div v-if="error" class="error-box">
      {{ error }}
    </div>

    <div
      v-if="!loading && alerts.length === 0 && !error"
      class="empty-box"
    >
      <span>✓</span>
      <h3>Chưa có cảnh báo camera</h3>
      <p>
        Cảnh báo sẽ xuất hiện khi hệ thống
        ghi nhận sự kiện từ Sinh viên.
      </p>
    </div>

    <div v-else class="alert-list">
      <article
        v-for="item in alerts"
        :key="item.id"
        class="alert-item"
      >
        <div class="alert-icon">⚠</div>

        <div class="alert-info">
          <strong>
            {{ getAlertName(item.loaiCanhBao) }}
          </strong>

          <p>
            {{ item.tenSinhVien }}
            · {{ item.mssv }}
          </p>

          <small>
            {{ item.tenBaiKiemTra }}
            · {{ item.tenLop }}
          </small>
        </div>

        <div class="alert-time">
          {{ formatDate(item.thoiGian) }}
        </div>
      </article>
    </div>
  </section>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
  onBeforeUnmount,
} from "vue"

import api from "../../../services/api"

const alerts = ref([])
const loading = ref(false)
const error = ref("")

let refreshTimer = null
let disposed = false

const studentCount = computed(() => {
  const ids = alerts.value.map(
    (item) => item.sinhVienId
  )

  return new Set(ids).size
})

function getAlertName(type) {
  const names = {
    khong_co_khuon_mat:
      "Không phát hiện khuôn mặt",

    nhieu_khuon_mat:
      "Phát hiện từ 2 khuôn mặt trở lên",

    camera_ngat:
      "Camera bị ngắt",
  }

  return names[type] || "Cảnh báo camera"
}

function formatDate(value) {
  if (!value) return "Chưa cập nhật"

  const date = new Date(
    String(value).replace(" ", "T")
  )

  if (Number.isNaN(date.getTime())) {
    return String(value)
  }

  return date.toLocaleString("vi-VN")
}

async function fetchAlerts() {
  if (loading.value || disposed) return

  loading.value = true

  try {
    const response = await api.get(
      "/giang-vien/canh-bao-camera"
    )

    if (!disposed) {
      alerts.value = response.data?.data ?? []
      error.value = ""
    }
  } catch (err) {
    if (!disposed) {
      error.value =
        err.response?.data?.message ||
        "Không thể tải cảnh báo camera."
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  disposed = false

  fetchAlerts()

  refreshTimer = setInterval(() => {
    fetchAlerts()
  }, 10000)
})

onBeforeUnmount(() => {
  disposed = true

  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
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
}

.alerts-header button {
  padding: 10px 14px;
  border: none;
  border-radius: 9px;
  background: #2563eb;
  color: white;
  font-weight: 700;
  cursor: pointer;
}

.alerts-header button:disabled {
  opacity: 0.5;
}

.alert-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-bottom: 22px;
}

.alert-stats > div {
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
  background: rgba(220, 38, 38, 0.15);
  font-size: 20px;
}

.alert-info {
  flex: 1;
}

.alert-info strong {
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

.alert-time {
  font-size: 11px;
  color: #94a3b8;
  white-space: nowrap;
}

.empty-box {
  text-align: center;
  padding: 36px;
}

.empty-box > span {
  color: #4ade80;
  font-size: 35px;
}

.empty-box h3 {
  font-size: 15px;
}

.empty-box p {
  color: #94a3b8;
  font-size: 12px;
}

.error-box {
  padding: 12px;
  background: rgba(220, 38, 38, 0.12);
  border-radius: 10px;
  color: #fca5a5;
  font-size: 12px;
}

@media (max-width: 650px) {
  .alerts-header,
  .alert-item {
    flex-direction: column;
  }

  .alert-time {
    white-space: normal;
  }
}
</style>
