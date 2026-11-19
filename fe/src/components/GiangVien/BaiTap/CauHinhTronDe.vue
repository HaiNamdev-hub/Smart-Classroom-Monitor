<template>
  <section class="shuffle-panel" aria-labelledby="shuffle-title">
    <div class="panel-heading">
      <div>
        <h2 id="shuffle-title">Cấu hình trộn đề kiểm tra</h2>
        <p>Thiết lập riêng cho bài kiểm tra này. Câu trả lời và chấm điểm vẫn dùng ID câu hỏi/đáp án.</p>
      </div>
      <span v-if="locked" class="state locked">Đã khóa</span>
      <span v-else-if="saved" class="state saved">Đã lưu</span>
    </div>

    <div v-if="error" class="message error" role="alert">{{ error }}</div>
    <div v-if="success" class="message success" role="status">{{ success }}</div>
    <div v-if="loading" class="loading" role="status">Đang tải cấu hình…</div>

    <template v-else>
      <div class="options">
        <label class="toggle-row">
          <input v-model="settings.tron_cau_hoi" type="checkbox" :disabled="locked || saving" @change="saved = false; success = ''" />
          <span class="toggle-copy"><strong>Trộn thứ tự câu hỏi</strong><small>Mỗi lượt làm nhận thứ tự câu hỏi riêng.</small></span>
        </label>
        <label class="toggle-row">
          <input v-model="settings.tron_dap_an" type="checkbox" :disabled="locked || saving" @change="saved = false; success = ''" />
          <span class="toggle-copy"><strong>Trộn thứ tự đáp án trắc nghiệm</strong><small>Không áp dụng cho câu tự luận.</small></span>
        </label>
      </div>

      <p v-if="lockedReason" class="lock-reason">{{ lockedReason }}</p>
      <p v-else class="hint">Mặc định tắt. Khi bật, Backend lưu thứ tự riêng theo lượt làm; tải lại trang không xáo trộn lại.</p>

      <button type="button" class="save-button" :disabled="locked || saving || loading" @click="save">
        {{ saving ? 'Đang lưu…' : 'Lưu cấu hình' }}
      </button>
    </template>
  </section>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import api from '../../../services/api'

const props = defineProps({
  baiTapId: { type: [String, Number], required: true },
})

const settings = reactive({ tron_cau_hoi: false, tron_dap_an: false })
const loading = ref(true)
const saving = ref(false)
const locked = ref(false)
const lockedReason = ref('')
const error = ref('')
const success = ref('')
const saved = ref(false)
let requestId = 0

function endpoint(id) {
  return `/giang-vien/bai-tap/${encodeURIComponent(id)}/cau-hinh-tron`
}

function apiMessage(err) {
  return Object.values(err.response?.data?.errors || {}).flat()[0]
    || err.response?.data?.message
    || 'Không thể kết nối máy chủ để lưu cấu hình.'
}

async function load(id) {
  const current = ++requestId
  loading.value = true
  error.value = ''
  success.value = ''
  saved.value = false
  try {
    const { data } = await api.get(endpoint(id))
    if (current !== requestId) return
    settings.tron_cau_hoi = data.data?.tron_cau_hoi === true || data.data?.tron_cau_hoi === 1
    settings.tron_dap_an = data.data?.tron_dap_an === true || data.data?.tron_dap_an === 1
    locked.value = Boolean(data.data?.locked)
    lockedReason.value = data.data?.locked_reason || ''
  } catch (err) {
    if (current === requestId) {
      error.value = apiMessage(err)
      locked.value = true
      lockedReason.value = 'Không tải được cấu hình từ máy chủ; tạm khóa chỉnh sửa đến khi tải lại thành công.'
    }
  } finally {
    if (current === requestId) loading.value = false
  }
}

async function save() {
  if (saving.value || locked.value) return
  saving.value = true
  error.value = ''
  success.value = ''
  saved.value = false
  try {
    const { data } = await api.put(endpoint(props.baiTapId), {
      tron_cau_hoi: settings.tron_cau_hoi,
      tron_dap_an: settings.tron_dap_an,
    })
    settings.tron_cau_hoi = Boolean(data.data?.tron_cau_hoi)
    settings.tron_dap_an = Boolean(data.data?.tron_dap_an)
    locked.value = Boolean(data.data?.locked)
    lockedReason.value = data.data?.locked_reason || ''
    success.value = data.message || 'Đã lưu cấu hình trộn đề.'
    saved.value = true
  } catch (err) {
    error.value = apiMessage(err)
    if (err.response?.status === 409) await load(props.baiTapId)
  } finally {
    saving.value = false
  }
}

watch(() => props.baiTapId, id => load(id), { immediate: true })
</script>

<style scoped>
.shuffle-panel{margin:0 0 20px;padding:24px;border:1px solid #334155;border-radius:16px;background:#111;color:#f8fafc}
.panel-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}
.panel-heading h2{margin:0 0 8px;font-size:18px}
.panel-heading p,.hint{margin:0;color:#94a3b8;font-size:12px;line-height:1.7}
.options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.toggle-row{display:flex;align-items:flex-start;gap:12px;padding:15px;border:1px solid #334155;border-radius:12px;background:#171a1f;cursor:pointer}
.toggle-row input{width:18px;height:18px;margin:2px 0 0;accent-color:#3b82f6}
.toggle-row input:disabled{cursor:not-allowed}
.toggle-copy{display:grid;gap:5px}.toggle-copy strong{font-size:13px}.toggle-copy small{color:#94a3b8;font-size:11px;line-height:1.5}
.hint,.lock-reason{margin:14px 0 0}.lock-reason{color:#fbbf24;font-size:12px;line-height:1.6}
.save-button{margin-top:15px;padding:11px 16px;border:0;border-radius:9px;background:#2563eb;color:white;font-weight:800;cursor:pointer}
.save-button:disabled{opacity:.5;cursor:not-allowed}
.state{flex:none;padding:7px 10px;border-radius:999px;font-size:11px;font-weight:800}.state.locked{background:#451a03;color:#fcd34d}.state.saved{background:#14532d;color:#86efac}
.message{margin-bottom:12px;padding:11px 13px;border-radius:9px;font-size:12px}.message.error{background:#450a0a;color:#fca5a5}.message.success{background:#14532d;color:#86efac}.loading{padding:15px;color:#94a3b8}
@media(max-width:650px){.shuffle-panel{padding:17px}.options{grid-template-columns:1fr}.panel-heading{flex-direction:column}}
</style>
