<template>
  <section class="exam-mode-panel" aria-labelledby="exam-mode-title">
    <div class="panel-heading">
      <div>
        <span class="eyebrow">CẤU HÌNH BÀI KIỂM TRA</span>
        <h2 id="exam-mode-title">Hình thức kiểm tra</h2>
        <p>Đề cũ chưa cấu hình tiếp tục yêu cầu camera như trước.</p>
      </div>
      <span class="mode-state" :class="{ locked }">{{ locked ? 'Đã khóa' : 'Có thể chỉnh sửa' }}</span>
    </div>

    <div v-if="loading" class="mode-message">Đang tải cấu hình...</div>
    <div v-else class="mode-options">
      <label class="mode-option" :class="{ selected: selectedMode === ONLINE }">
        <input v-model="selectedMode" type="radio" name="exam-mode" :value="ONLINE" :disabled="locked || saving" />
        <span><strong>Thi trực tuyến</strong><small>Camera AI bắt buộc. Sinh viên cần cấp quyền camera; MediaPipe và WebRTC hoạt động như hiện tại.</small></span>
      </label>
      <label class="mode-option" :class="{ selected: selectedMode === LAB }">
        <input v-model="selectedMode" type="radio" name="exam-mode" :value="LAB" :disabled="locked || saving" />
        <span><strong>Thi tại phòng máy</strong><small>Không yêu cầu camera. Bảo vệ bài thi, ghi nhận rời tab và autosave vẫn hoạt động.</small></span>
      </label>
      <div class="mode-actions">
        <p v-if="locked" class="locked-reason">{{ lockedReason || 'Không thể đổi sau khi đã có lượt làm hoặc bài nộp.' }}</p>
        <button v-else type="button" class="save-mode" :disabled="saving || !dirty" @click="save">
          {{ saving ? 'Đang lưu...' : 'Lưu hình thức' }}
        </button>
      </div>
    </div>
    <p v-if="error" class="mode-error" role="alert">{{ error }}</p>
    <p v-if="success" class="mode-success" role="status">{{ success }}</p>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import api from '../../../services/api'

const props = defineProps({ baiTapId: { type: [String, Number], required: true } })
const ONLINE = 'ONLINE_CAMERA_REQUIRED'
const LAB = 'LAB_NO_CAMERA'
const loading = ref(false)
const saving = ref(false)
const selectedMode = ref(ONLINE)
const savedMode = ref(ONLINE)
const locked = ref(false)
const lockedReason = ref('')
const error = ref('')
const success = ref('')
const dirty = computed(() => selectedMode.value !== savedMode.value)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get(`/giang-vien/bai-tap/${encodeURIComponent(props.baiTapId)}/hinh-thuc-kiem-tra`)
    const mode = data?.data?.hinh_thuc === LAB ? LAB : ONLINE
    selectedMode.value = savedMode.value = mode
    locked.value = Boolean(data?.data?.co_the_chinh_sua === false)
    lockedReason.value = data?.data?.ly_do_khoa || ''
  } catch (err) {
    error.value = err.response?.data?.message || 'Không thể tải cấu hình hình thức kiểm tra.'
  } finally {
    loading.value = false
  }
}

async function save() {
  if (saving.value || locked.value || !dirty.value) return
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const { data } = await api.put(`/giang-vien/bai-tap/${encodeURIComponent(props.baiTapId)}/hinh-thuc-kiem-tra`, { hinh_thuc: selectedMode.value })
    savedMode.value = data.data.hinh_thuc
    locked.value = data.data.co_the_chinh_sua === false
    success.value = 'Đã lưu hình thức kiểm tra.'
  } catch (err) {
    error.value = err.response?.data?.message || 'Không thể lưu hình thức kiểm tra.'
    if (err.response?.status === 409) await load()
  } finally {
    saving.value = false
  }
}

watch(() => props.baiTapId, load, { immediate: true })
</script>

<style scoped>
.exam-mode-panel{margin:18px 0;padding:20px;border:1px solid rgba(148,163,184,.22);border-radius:16px;background:linear-gradient(145deg,#172033,#111827);color:#e5e7eb;box-shadow:0 12px 28px rgba(0,0,0,.2)}
.panel-heading{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}.eyebrow{font-size:11px;letter-spacing:.12em;color:#60a5fa;font-weight:700}.panel-heading h2{margin:5px 0;font-size:20px;color:#f8fafc}.panel-heading p,.mode-option small{margin:0;color:#9ca3af;font-size:13px;line-height:1.5}.mode-state{padding:6px 10px;border-radius:999px;background:#123b2c;color:#86efac;font-size:12px;white-space:nowrap}.mode-state.locked{background:#3b2c12;color:#fcd34d}.mode-options{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:18px}.mode-option{display:flex;gap:12px;align-items:flex-start;padding:15px;border:1px solid #374151;border-radius:12px;background:#1f2937;cursor:pointer}.mode-option.selected{border-color:#3b82f6;background:#172d4a}.mode-option input{margin-top:4px;accent-color:#3b82f6}.mode-option span{display:grid;gap:6px}.mode-option strong{color:#f9fafb}.mode-actions{grid-column:1/-1;display:flex;justify-content:flex-end;align-items:center}.save-mode{border:0;border-radius:9px;padding:10px 16px;background:#2563eb;color:#fff;font-weight:700;cursor:pointer}.save-mode:disabled{opacity:.45;cursor:not-allowed}.locked-reason,.mode-error,.mode-success{font-size:13px}.locked-reason,.mode-error{color:#fca5a5}.mode-success{color:#86efac}.mode-message{padding:18px;color:#9ca3af}@media(max-width:700px){.mode-options{grid-template-columns:1fr}.mode-actions{grid-column:auto}.panel-heading{flex-direction:column}}
</style>
