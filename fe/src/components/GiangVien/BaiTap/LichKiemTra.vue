
<template>
  <section class="schedule-panel">
    <div class="heading">
      <div>
        <h2>🕒 Lịch kiểm tra tự động</h2>
        <p>
          Đặt thời gian bắt đầu và kết thúc.
          Hệ thống tự mở bài thi đúng giờ.
        </p>
      </div>

      <span
        class="status"
        :class="status"
      >
        {{ statusLabel }}
      </span>
    </div>

    <div v-if="error" class="message error">
      {{ error }}
    </div>

    <div v-if="success" class="message success">
      {{ success }}
    </div>

    <div v-if="loading" class="loading">
      Đang tải lịch kiểm tra...
    </div>

    <form v-else @submit.prevent="saveSchedule">
      <div class="fields">
        <div class="field">
          <label for="exam-start">
            Thời gian bắt đầu
          </label>

          <input
            id="exam-start"
            v-model="startTime"
            type="datetime-local"
            required
            :disabled="!canChange"
          />
        </div>

        <div class="field">
          <label for="exam-end">
            Thời gian kết thúc
          </label>

          <input
            id="exam-end"
            v-model="endTime"
            type="datetime-local"
            required
            :disabled="!canChange"
          />
        </div>
      </div>

      <p class="hint">
        Múi giờ Việt Nam (UTC+7).
        Sau giờ kết thúc, Sinh viên không thể làm tiếp.
      </p>

      <button
        type="submit"
        class="save-button"
        :disabled="saving || !canChange"
      >
        {{
          saving
            ? "Đang lưu lịch..."
            : "Lưu lịch & hẹn giờ mở bài"
        }}
      </button>
    </form>
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

const props = defineProps({
  baiTapId: {
    type: [String, Number],
    required: true,
  },
})

const loading = ref(true)
const saving = ref(false)

const error = ref("")
const success = ref("")

const startTime = ref("")
const endTime = ref("")
const status = ref("sap_dien_ra")
const currentTime = ref(Date.now())

let clock = null

const endpoint = computed(() =>
  `/giang-vien/bai-tap/${
    encodeURIComponent(props.baiTapId)
  }/lich-kiem-tra`
)

function toLocalInput(value) {
  if (!value) return ""

  // Laravel/MySQL trả về: 2026-10-28 08:00:00
  // Chuyển sang định dạng datetime-local.
  const text = String(value).replace(" ", "T")

  if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/.test(text)) {
    return text.slice(0, 16)
  }

  return ""
}

function toSqlDatetime(value) {
  if (!value) return ""

  return value.replace("T", " ") + ":00"
}

const canChange = computed(() => {
  if (status.value !== "sap_dien_ra") {
    return false
  }

  if (!startTime.value) return true

  const start = new Date(startTime.value).getTime()

  return Number.isFinite(start) &&
    start > currentTime.value
})

const statusLabel = computed(() => {
  if (status.value === "dang_dien_ra") {
    return "Đang diễn ra"
  }

  if (status.value === "da_ket_thuc") {
    return "Đã kết thúc"
  }

  return "Sắp diễn ra"
})

function getError(err) {
  const errors = err.response?.data?.errors

  return (
    (errors && Object.values(errors).flat()[0]) ||
    err.response?.data?.message ||
    err.message ||
    "Không thể thực hiện yêu cầu."
  )
}

async function loadSchedule() {
  loading.value = true
  error.value = ""

  try {
    const response = await api.get(endpoint.value)

    const data = response.data?.data

    if (!data) {
      throw new Error("Không tải được lịch kiểm tra.")
    }

    startTime.value = toLocalInput(
      data.thoi_gian_bat_dau
    )

    endTime.value = toLocalInput(
      data.thoi_gian_ket_thuc
    )

    status.value = data.trang_thai
  } catch (err) {
    error.value = getError(err)
  } finally {
    loading.value = false
  }
}

async function saveSchedule() {
  error.value = ""
  success.value = ""

  if (!startTime.value || !endTime.value) {
    error.value = "Vui lòng chọn đủ thời gian."
    return
  }

  const start = new Date(startTime.value).getTime()
  const end = new Date(endTime.value).getTime()

  if (
    !Number.isFinite(start) ||
    start <= Date.now()
  ) {
    error.value =
      "Thời gian bắt đầu phải ở tương lai."
    return
  }

  if (!Number.isFinite(end) || end <= start) {
    error.value =
      "Thời gian kết thúc phải sau thời gian bắt đầu."
    return
  }

  saving.value = true

  try {
    const response = await api.put(
      endpoint.value,
      {
        thoi_gian_bat_dau:
          toSqlDatetime(startTime.value),
        thoi_gian_ket_thuc:
          toSqlDatetime(endTime.value),
      }
    )

    if (response.data?.status === false) {
      throw new Error(response.data.message)
    }

    success.value =
      "Đã đặt lịch thành công! Bài thi sẽ tự mở đúng giờ."

    await loadSchedule()
  } catch (err) {
    error.value = getError(err)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadSchedule()

  clock = setInterval(() => {
    currentTime.value = Date.now()

    if (
      startTime.value &&
      currentTime.value >=
      new Date(startTime.value).getTime() &&
      status.value === "sap_dien_ra"
    ) {
      loadSchedule()
    }
  }, 10000)
})

onBeforeUnmount(() => {
  clearInterval(clock)
})
</script>

<style scoped>
.schedule-panel {
  margin-bottom: 20px;
  padding: 24px;
  border: 1px solid #334155;
  border-radius: 16px;
  background: #111;
  color: #f8fafc;
}

.heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

h2 {
  margin: 0 0 8px;
  font-size: 18px;
}

.heading p,
.hint {
  color: #94a3b8;
  font-size: 12px;
  line-height: 1.7;
}

.status {
  flex-shrink: 0;
  padding: 8px 12px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  background: #1e293b;
  color: #93c5fd;
}

.status.dang_dien_ra {
  background: #14532d;
  color: #86efac;
}

.status.da_ket_thuc {
  background: #450a0a;
  color: #fca5a5;
}

.fields {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.field label {
  display: block;
  margin-bottom: 9px;
  font-size: 12px;
  font-weight: 800;
}

.field input {
  width: 100%;
  padding: 13px;
  border: 1px solid #475569;
  border-radius: 10px;
  background: #202327;
  color: #f8fafc;
  font: inherit;
}

.field input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.save-button {
  margin-top: 16px;
  padding: 12px 18px;
  border: none;
  border-radius: 10px;
  background: #2563eb;
  color: #fff;
  font-weight: 800;
  cursor: pointer;
}

.save-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.message {
  padding: 12px;
  margin-bottom: 15px;
  border-radius: 10px;
  font-size: 12px;
}

.error {
  color: #fca5a5;
  background: #450a0a;
}

.success {
  color: #86efac;
  background: #14532d;
}

.loading {
  padding: 20px;
  color: #94a3b8;
  text-align: center;
}

@media (max-width: 650px) {
  .fields {
    grid-template-columns: 1fr;
  }

  .heading {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
