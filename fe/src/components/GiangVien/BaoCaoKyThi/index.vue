<template>
  <main class="report-page">
    <header class="page-head">
      <div><small>BÁO CÁO VÀ ĐỐI SOÁT</small>
        <h1>Báo cáo kỳ thi</h1>
        <p>Tổng hợp tham gia, bài nộp, điểm và sự kiện giám sát theo lớp và bài kiểm tra.</p>
      </div><button class="primary" :disabled="busy || !report" @click="download">{{ busy ? 'Đang chuẩn bị…' : 'Xuất Excel' }}</button>
    </header>
    <section class="filters">
      <label>Lớp học<select v-model="filters.class_id" @change="loadExams">
          <option value="">Chọn lớp</option>
          <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.tenLop || c.ten_lop }}</option>
        </select></label>
      <label>Bài kiểm tra<select v-model="filters.exam_id" :disabled="!filters.class_id" @change="refresh">
          <option value="">Chọn bài kiểm tra</option>
          <option v-for="e in exams" :key="e.id" :value="e.id">{{ e.tieuDe }}</option>
        </select></label>
      <label>Phiên giám sát<select v-model="filters.session_id" @change="refresh">
          <option value="">Tất cả phiên</option>
          <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.tenPhien || s.ten_phien }} (#{{ s.id }})</option>
        </select></label>
      <label>Từ ngày<input v-model="filters.from" type="date" @change="refresh"></label><label>Đến ngày<input
          v-model="filters.to" type="date" @change="refresh"></label>
      <label>Tham gia<select v-model="filters.participation" @change="refresh">
          <option value="">Tất cả</option>
          <option>Đã tham gia</option>
          <option>Chưa tham gia</option>
        </select></label>
      <label>Bài nộp<select v-model="filters.submission" @change="refresh">
          <option value="">Tất cả</option>
          <option value="submitted">Đã nộp</option>
          <option value="unsubmitted">Chưa nộp</option>
        </select></label>
    </section>
    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-if="busy && !report" class="muted">Đang tải báo cáo…</p>
    <template v-if="report">
      <section class="stats">
        <article v-for="item in cards" :key="item[0]"><span>{{ item[0] }}</span><strong>{{ item[1] }}</strong></article>
      </section>
      <section class="sheet-picker"><strong>Các trang tính sẽ xuất</strong><label v-for="s in sheetOptions"
          :key="s.value"><input v-model="selectedSheets" type="checkbox" :value="s.value"> {{ s.label }}</label>
      </section>
      <section class="table-wrap">
        <h2>Sinh viên và kết quả</h2>
        <table>
          <thead>
            <tr>
              <th>MSSV</th>
              <th>Họ tên</th>
              <th>Tham gia</th>
              <th>Bài nộp</th>
              <th>Chấm điểm</th>
              <th>Điểm</th>
              <th>Cảnh báo</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in report.students" :key="s.user_id">
              <td>{{ s.student_code || '—' }}</td>
              <td>{{ s.name }}</td>
              <td>{{ s.participation }}</td>
              <td>{{ s.submission_status }}</td>
              <td>{{ s.grading_status }}</td>
              <td>{{ s.score ?? '—' }}</td>
              <td>{{ s.alert_count }}</td>
            </tr>
            <tr v-if="!report.students.length">
              <td colspan="7" class="empty">Không có sinh viên khớp bộ lọc.</td>
            </tr>
          </tbody>
        </table>
      </section>
      <section class="table-wrap">
        <h2>Sự kiện cảnh báo</h2>
        <table>
          <thead>
            <tr>
              <th>Loại</th>
              <th>Sinh viên</th>
              <th>MSSV</th>
              <th>Thời gian</th>
              <th>Nguồn</th>
              <th>Rà soát</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in report.alerts" :key="`${a.source}:${a.event_id || a.id}`">
              <td>{{ a.type }}</td>
              <td>{{ a.ho_ten }}</td>
              <td>{{ a.ma_sinh_vien || '—' }}</td>
              <td>{{ formatDate(a.happened_at) }}</td>
              <td>{{ a.source }}</td>
              <td>{{ a.review_status }}</td>
            </tr>
            <tr v-if="!report.alerts.length">
              <td colspan="6" class="empty">Không có sự kiện trong bộ lọc.</td>
            </tr>
          </tbody>
        </table>
      </section>
    </template>
  </main>
</template>
<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import api from '../../../services/api'
const classes = ref([]), exams = ref([]), sessions = ref([]), report = ref(null), busy = ref(false), error = ref('')
const filters = reactive({ class_id: '', exam_id: '', session_id: '', from: '', to: '', participation: '', submission: '' })
const sheetOptions = [{ value: 'overview', label: 'Tổng quan' }, { value: 'students', label: 'Danh sách sinh viên' }, { value: 'results', label: 'Kết quả và điểm' }, { value: 'alerts', label: 'Cảnh báo' }, { value: 'review', label: 'Rà soát giảng viên' }, { value: 'statistics', label: 'Thống kê' }]
const selectedSheets = ref(sheetOptions.map(s => s.value))
const cards = computed(() => { const s = report.value?.summary || {}; return [['Đủ điều kiện', s.eligible ?? 0], ['Đã tham gia', s.participated ?? 0], ['Đã nộp', s.submitted ?? 0], ['Chưa nộp', s.unsubmitted ?? 0], ['Đã chấm', s.graded ?? 0], ['Điểm trung bình', s.mean_score ?? '—'], ['Tổng cảnh báo', s.alert_total ?? 0], ['SV có cảnh báo', s.students_with_alert ?? 0]] })
const query = () => Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''))
async function onMountedLoad() { try { const { data } = await api.get('/giang-vien/lop-hoc'); classes.value = data.data || data || [] } catch { error.value = 'Không thể tải danh sách lớp. Kiểm tra quyền hoặc kết nối.' } }
async function loadExams() { exams.value = []; sessions.value = []; report.value = null; filters.exam_id = ''; filters.session_id = ''; if (!filters.class_id) return; try { const [a, b] = await Promise.all([api.get('/giang-vien/bai-tap', { params: { lopId: filters.class_id } }), api.get('/giang-vien/phien-giam-sat')]); exams.value = a.data.data || []; sessions.value = (b.data.data || []).filter(s => String(s.lopId ?? s.lop_hoc_id) === String(filters.class_id)) } catch { error.value = 'Không thể tải bài kiểm tra hoặc phiên giám sát.' } }
let requestSequence = 0
async function refresh() { if (!filters.class_id || !filters.exam_id) { report.value = null; return } const sequence = ++requestSequence; busy.value = true; error.value = ''; try { const { data } = await api.get('/giang-vien/bao-cao-ky-thi', { params: query() }); if (sequence === requestSequence) report.value = data.data } catch (e) { if (sequence === requestSequence) { report.value = null; error.value = e.response?.data?.message || 'Không tải được báo cáo.' } } finally { if (sequence === requestSequence) busy.value = false } }
async function download() { if (!selectedSheets.value.length) { error.value = 'Chọn ít nhất một trang tính để xuất.'; return } busy.value = true; error.value = ''; let objectUrl; try { const response = await api.post('/giang-vien/bao-cao-ky-thi/xuat-excel', { ...query(), sheets: selectedSheets.value }, { responseType: 'blob' }); objectUrl = URL.createObjectURL(response.data); const link = document.createElement('a'); link.href = objectUrl; link.download = filenameFromHeader(response.headers['content-disposition']) || 'bao-cao-ky-thi.xlsx'; link.click(); link.remove() } catch (e) { error.value = e.response?.status === 403 ? 'Bạn không có quyền xuất báo cáo này.' : e.response?.data?.message || 'Xuất Excel thất bại.' } finally { if (objectUrl) URL.revokeObjectURL(objectUrl); busy.value = false } }
function filenameFromHeader(value = '') { const utf = value.match(/filename\*=UTF-8''([^;]+)/i); const plain = value.match(/filename="?([^";]+)"?/i); try { return decodeURIComponent(utf?.[1] || plain?.[1] || '') } catch { return '' } }
function formatDate(value) { return value ? new Date(value).toLocaleString('vi-VN') : '—' }
onMounted(onMountedLoad)
</script>
<style scoped>
.report-page {
  padding: 28px;
  display: grid;
  gap: 20px;
  color: #172033
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px
}

.page-head small {
  color: #2563eb;
  font-weight: 700;
  letter-spacing: .08em
}

.page-head h1 {
  margin: 6px 0;
  font-size: 30px
}

.page-head p,
.muted {
  color: #64748b;
  margin: 0
}

.primary {
  border: 0;
  border-radius: 9px;
  padding: 11px 18px;
  background: #2563eb;
  color: white;
  font-weight: 700;
  cursor: pointer
}

.primary:disabled {
  opacity: .55
}

.filters,
.sheet-picker,
.stats article,
.table-wrap {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px
}

.filters {
  padding: 16px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
  gap: 12px
}

.filters label {
  display: grid;
  gap: 6px;
  font-size: 13px;
  font-weight: 600
}

.filters select,
.filters input {
  border: 1px solid #cbd5e1;
  border-radius: 7px;
  padding: 9px;
  background: white;
  min-width: 0
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 12px
}

.stats article {
  padding: 14px;
  display: grid;
  gap: 8px
}

.stats span {
  font-size: 13px;
  color: #64748b
}

.stats strong {
  font-size: 23px
}

.sheet-picker {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  padding: 14px
}

.sheet-picker label {
  font-size: 13px
}

.table-wrap {
  padding: 18px;
  overflow: auto
}

.table-wrap h2 {
  font-size: 18px;
  margin: 0 0 14px
}

table {
  border-collapse: collapse;
  width: 100%;
  font-size: 13px
}

th,
td {
  text-align: left;
  padding: 10px;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap
}

th {
  background: #f8fafc;
  color: #475569
}

.empty {
  text-align: center;
  color: #64748b;
  padding: 24px
}

.error {
  background: #fff1f2;
  color: #be123c;
  border-radius: 8px;
  padding: 12px;
  margin: 0
}

@media(max-width:700px) {
  .report-page {
    padding: 16px
  }

  .page-head {
    align-items: flex-start;
    flex-direction: column
  }
}
</style>
