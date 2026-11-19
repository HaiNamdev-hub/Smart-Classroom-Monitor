
<template>
  <div class="page">
    <header class="heading">
      <div>
        <span class="eyebrow">
          GIẢNG VIÊN · SMART CLASSROOM MONITOR
        </span>
        <h1>Quản lý câu hỏi kiểm tra</h1>
        <p>
          Bài tập #{{ baiTapId }} · Nhập thủ công hoặc tải Word,
          Excel, PDF
        </p>
      </div>

      <button
        class="btn light"
        @click="router.push('/giang-vien/bai-tap')"
      >
        ← Quay lại Bài tập
      </button>
    </header>

    <div v-if="error" class="alert bad" role="alert">
      {{ error }}
    </div>

    <div v-if="success" class="alert good" role="status">
      {{ success }}
    </div>
    <LichKiemTra :bai-tap-id="baiTapId" />
    <HinhThucKiemTra :bai-tap-id="baiTapId" />
    <CauHinhTronDe :bai-tap-id="baiTapId" />
    <section class="stats">
      <div>
        <span>Tổng số câu</span>
        <strong>{{ questions.length }}</strong>
      </div>

      <div>
        <span>Trắc nghiệm</span>
        <strong>
          {{
            questions.filter(
              q => q.loai_cau_hoi === "trac_nghiem"
            ).length
          }}
        </strong>
      </div>

      <div>
        <span>Tự luận</span>
        <strong>
          {{
            questions.filter(
              q => q.loai_cau_hoi === "tu_luan"
            ).length
          }}
        </strong>
      </div>

      <div>
        <span>Điểm tự luận</span>
        <strong>{{ formatPoints(scoreSummary.essay) }}</strong>
      </div>
      <div>
        <span>Điểm trắc nghiệm</span>
        <strong>{{ formatPoints(scoreSummary.multipleChoice) }}</strong>
      </div>
      <div>
        <span>Tổng điểm bài</span>
        <strong>{{ formatPoints(scoreSummary.total) }} / 10</strong>
      </div>
      <div>
        <span>Điểm còn lại</span>
        <strong>{{ formatPoints(scoreSummary.remaining) }}</strong>
      </div>
    </section>

    <!-- CHỈ CÓ 2 CHẾ ĐỘ -->
    <nav class="tabs" aria-label="Cách tạo câu hỏi">
      <button
        type="button"
        :class="{ active: mode === 'manual' }"
        @click="mode = 'manual'"
      >
        ✎ Nhập thủ công
      </button>

      <button
        type="button"
        :class="{ active: mode === 'file' }"
        @click="mode = 'file'"
      >
        ⇧ Tải file Word / Excel / PDF
      </button>
    </nav>

    <div class="columns">
      <section class="panel">
        <!-- NHẬP THỦ CÔNG -->
        <template v-if="mode === 'manual'">
          <h2>
            {{
              editingId
                ? "Chỉnh sửa câu hỏi"
                : "Thêm câu hỏi mới"
            }}
          </h2>

          <form @submit.prevent="saveManual">
            <div class="field">
              <label>Loại câu hỏi</label>

              <div class="choice-type">
                <button
                  type="button"
                  :class="{
                    chosen: form.loai_cau_hoi === 'trac_nghiem'
                  }"
                  @click="form.loai_cau_hoi = 'trac_nghiem'"
                >
                  Trắc nghiệm
                </button>

                <button
                  type="button"
                  :class="{
                    chosen: form.loai_cau_hoi === 'tu_luan'
                  }"
                  @click="form.loai_cau_hoi = 'tu_luan'"
                >
                  Tự luận
                </button>
              </div>
            </div>

            <div class="field">
              <label>Nội dung câu hỏi *</label>
              <textarea
                v-model="form.noi_dung"
                rows="4"
                maxlength="10000"
                required
                placeholder="Nhập nội dung câu hỏi..."
              ></textarea>
            </div>

            <div v-if="form.loai_cau_hoi === 'tu_luan'" class="field">
              <label>Điểm *</label>
              <input
                v-model.number="form.diem"
                type="number"
                min="0.01"
                max="10"
                step="0.01"
                required
              />
            </div>
            <p v-else class="hint">Điểm trắc nghiệm được hệ thống tự chia từ phần điểm còn lại.</p>

            <!-- TRẮC NGHIỆM -->
            <template v-if="form.loai_cau_hoi === 'trac_nghiem'">
              <label>Nhập các lựa chọn và chọn đáp án đúng</label>

              <div
                v-for="(option, i) in form.dap_an"
                :key="i"
                class="option-row"
              >
                <input
                  type="radio"
                  name="manual-correct"
                  :checked="form.dap_an_dung_index === i"
                  :aria-label="'Chọn đáp án ' + letter(i)"
                  @change="form.dap_an_dung_index = i"
                />

                <b>{{ letter(i) }}</b>

                <input
                  v-model="form.dap_an[i]"
                  type="text"
                  :placeholder="'Đáp án ' + letter(i)"
                  maxlength="5000"
                  required
                />

                <button
                  type="button"
                  class="x"
                  :disabled="form.dap_an.length <= 2"
                  @click="removeOption(i)"
                >
                  ×
                </button>
              </div>

              <button
                type="button"
                class="btn light"
                :disabled="form.dap_an.length >= 6"
                @click="form.dap_an.push('')"
              >
                + Thêm lựa chọn
              </button>
            </template>

            <!-- TỰ LUẬN -->
            <div v-else class="field">
              <label>Đáp án mẫu / Hướng dẫn chấm *</label>
              <textarea
                v-model="form.dap_an_mau"
                rows="7"
                maxlength="20000"
                required
                placeholder="Nhập đáp án mẫu cho câu tự luận..."
              ></textarea>

              <p class="hint">
                Đáp án mẫu chỉ dành cho Giảng viên,
                không hiển thị cho Sinh viên.
              </p>
            </div>

            <div class="actions">
              <button
                v-if="editingId"
                type="button"
                class="btn light"
                @click="resetForm"
              >
                Hủy sửa
              </button>

              <button
                type="submit"
                class="btn primary"
                :disabled="busy"
              >
                {{
                  busy
                    ? "Đang lưu..."
                    : editingId
                      ? "Lưu thay đổi"
                      : "+ Thêm câu hỏi"
                }}
              </button>
            </div>
          </form>
        </template>

        <!-- TẢI FILE -->
        <template v-else>
          <h2>Nhập câu hỏi từ tài liệu</h2>

          <p class="hint">
            Chọn file .docx, .xlsx hoặc .pdf (tối đa 10 MB).
            Hệ thống sẽ đọc nội dung và hiển thị câu hỏi
            để bạn kiểm tra trước khi lưu.
          </p>

          <input
            ref="fileInput"
            class="upload"
            type="file"
            accept=".docx,.xlsx,.pdf"
            @change="onFileChange"
          />

          <button
            type="button"
            class="btn primary"
            :disabled="busy || !selectedFile"
            @click="previewFile"
          >
            {{
              busy
                ? "Đang đọc file..."
                : "Đọc file & xem trước"
            }}
          </button>

          <div class="format-note">
            <strong>Mẫu nội dung Word / PDF:</strong>

            <pre>Câu 1: HTML viết tắt của từ gì?
A. HyperText Markup Language
B. HighText Machine Language
C. Home Text Markup Language
D. Hyper Transfer Main Link
Đáp án: A
Điểm: 1

Câu 2: Trình bày tác dụng của HTTPS.
Loại: Tự luận
Đáp án mẫu: HTTPS dùng TLS để bảo vệ dữ liệu.
Điểm: 2</pre>

            <strong>Mẫu Excel:</strong>

            <p>
              Mỗi dòng là một câu hỏi, với tiêu đề cột:
              <code>
                loai_cau_hoi, noi_dung, A, B, C, D, E, F,
                dap_an_dung, dap_an_mau, diem
              </code>
            </p>

            <p>
              Trắc nghiệm: đáp án đúng A/B/C/D/E/F.
              Tự luận: điền cột dap_an_mau.
            </p>
          </div>

          <!-- DANH SÁCH XEM TRƯỚC -->
          <div v-if="previewQuestions.length" class="preview">
            <p class="hint">
              Tự luận {{ formatPoints(previewSummary.essay) }} ·
              Trắc nghiệm {{ formatPoints(previewSummary.multipleChoice) }} ·
              Tổng {{ formatPoints(previewSummary.total) }}/10 ·
              Còn {{ formatPoints(previewSummary.remaining) }} điểm
            </p>
            <div class="preview-heading">
              <h3>
                Xem trước {{ previewQuestions.length }} câu
              </h3>

              <button
                type="button"
                class="btn primary"
                :disabled="busy"
                @click="saveImport"
              >
                {{
                  busy
                    ? "Đang lưu..."
                    : "Lưu tất cả câu hợp lệ"
                }}
              </button>
            </div>

            <article
              v-for="(q, i) in previewQuestions"
              :key="q._key"
              class="preview-item"
            >
              <div class="preview-heading">
                <strong>Câu {{ i + 1 }}</strong>

              <button
                  type="button"
                  class="btn danger"
                @click="removePreviewQuestion(i)"
                >
                  Bỏ câu
                </button>
              </div>

              <select
                v-model="q.loai_cau_hoi"
                @change="fixPreviewType(q)"
              >
                <option value="trac_nghiem">
                  Trắc nghiệm
                </option>
                <option value="tu_luan">
                  Tự luận
                </option>
              </select>

              <textarea
                v-model="q.noi_dung"
                rows="3"
                placeholder="Nội dung câu hỏi"
              ></textarea>

              <label v-if="q.loai_cau_hoi === 'tu_luan'">
                Điểm tối đa câu tự luận (0,01–10)
                <input
                  v-model.number="q.diem"
                  type="number"
                  step="0.01"
                  min="0.01"
                  max="10"
                  @input="allocatePreviewPoints"
                />
              </label>
              <p v-else class="hint">Điểm trắc nghiệm sẽ được chia đều từ phần điểm còn lại.</p>

              <template v-if="q.loai_cau_hoi === 'trac_nghiem'">
                <div
                  v-for="(option, j) in q.dap_an"
                  :key="j"
                  class="option-row"
                >
                  <input
                    type="radio"
                    :name="'preview-correct-' + q._key"
                    :checked="q.dap_an_dung_index === j"
                    @change="q.dap_an_dung_index = j"
                  />

                  <b>{{ letter(j) }}</b>

                  <input
                    v-model="q.dap_an[j]"
                    type="text"
                  />

                  <button
                    type="button"
                    class="x"
                    :disabled="q.dap_an.length <= 2"
                    @click="removePreviewOption(q, j)"
                  >
                    ×
                  </button>
                </div>

                <button
                  type="button"
                  class="btn light"
                  :disabled="q.dap_an.length >= 6"
                  @click="q.dap_an.push('')"
                >
                  + Đáp án
                </button>
              </template>

              <div v-else class="field">
                <label>Đáp án mẫu</label>
                <textarea
                  v-model="q.dap_an_mau"
                  rows="4"
                ></textarea>
              </div>
            </article>
          </div>
        </template>
      </section>

      <!-- CÂU HỎI ĐÃ LƯU -->
      <section class="panel">
        <div class="list-head">
          <h2>Danh sách câu hỏi đã lưu</h2>

          <button
            type="button"
            class="btn light"
            :disabled="loading"
            @click="loadQuestions"
          >
            ↻ Làm mới
          </button>
        </div>

        <p v-if="loading" class="hint">
          Đang tải câu hỏi...
        </p>

        <p v-else-if="!questions.length" class="hint">
          Chưa có câu hỏi nào trong bài kiểm tra này.
        </p>

        <article
          v-for="(q, i) in questions"
          :key="q.id"
          class="saved-question"
        >
          <div class="list-head">
            <strong>
              Câu {{ i + 1 }} ·
              {{
                q.loai_cau_hoi === "trac_nghiem"
                  ? "Trắc nghiệm"
                  : "Tự luận"
              }}
            </strong>

            <span>{{ formatPoints(q._allocatedPoints ?? q.diem) }} điểm</span>
          </div>

          <p class="question-text">
            {{ q.noi_dung }}
          </p>

          <template v-if="q.loai_cau_hoi === 'trac_nghiem'">
            <div
              v-for="(option, j) in q.dap_an || []"
              :key="option.id"
              class="saved-answer"
              :class="{
                correct: truthy(option.la_dap_an_dung)
              }"
            >
              {{ letter(j) }}. {{ option.noi_dung }}

              <b v-if="truthy(option.la_dap_an_dung)">
                ✓ Đúng
              </b>
            </div>
          </template>

          <div v-else class="essay">
            <strong>Đáp án mẫu:</strong>
            <p>{{ q.dap_an_mau }}</p>
          </div>

          <div class="actions">
            <button
              type="button"
              class="btn light"
              @click="editQuestion(q)"
            >
              Chỉnh sửa
            </button>

            <button
              type="button"
              class="btn danger"
              :disabled="busy"
              @click="deleteQuestion(q)"
            >
              Xóa
            </button>
          </div>
        </article>
      </section>
    </div>
  </div>
</template>

<script setup>
import {
  ref,
  computed,
  onMounted,
} from "vue"

import {
  useRoute,
  useRouter,
} from "vue-router"

import api from "../../../services/api"
import LichKiemTra from "./LichKiemTra.vue"
import CauHinhTronDe from "./CauHinhTronDe.vue"
import HinhThucKiemTra from "./HinhThucKiemTra.vue"

const route = useRoute()
const router = useRouter()

const baiTapId = computed(() =>
  String(route.params.baiTapId || "")
)

const base = computed(() =>
  `/giang-vien/bai-tap/${
    encodeURIComponent(baiTapId.value)
  }/cau-hoi`
)

const mode = ref("manual")
const error = ref("")
const success = ref("")
const busy = ref(false)
const loading = ref(false)

const questions = ref([])
const editingId = ref(null)

const selectedFile = ref(null)
const fileInput = ref(null)
const previewQuestions = ref([])

const letter = i =>
  String.fromCharCode(65 + i)

const truthy = value =>
  value === true ||
  value === 1 ||
  value === "1"

function blankForm() {
  return {
    loai_cau_hoi: "trac_nghiem",
    noi_dung: "",
    diem: 1,
    dap_an: ["", "", "", ""],
    dap_an_dung_index: null,
    dap_an_mau: "",
  }
}

const form = ref(blankForm())

const toCents = value => Math.round((Number(value) || 0) * 100)
const formatPoints = cents => (Number(cents || 0) / 100).toFixed(2)

const scoreSummary = computed(() => {
  const essay = questions.value
    .filter(q => q.loai_cau_hoi === 'tu_luan')
    .reduce((sum, q) => sum + toCents(q.diem), 0)
  const multipleChoice = questions.value
    .filter(q => q.loai_cau_hoi === 'trac_nghiem')
    .reduce((sum, q) => sum + toCents(q.diem), 0)
  const total = essay + multipleChoice
  return {
    essay,
    multipleChoice,
    total,
    remaining: multipleChoice ? 0 : Math.max(0, 1000 - total),
  }
})

const previewSummary = computed(() => {
  const all = [...questions.value, ...previewQuestions.value]
  const essay = all.filter(q => q.loai_cau_hoi === 'tu_luan')
    .reduce((sum, q) => sum + toCents(q.diem), 0)
  const multipleChoice = all.filter(q => q.loai_cau_hoi === 'trac_nghiem')
    .reduce((sum, q) => sum + toCents(q._allocatedPoints ?? q.diem), 0)
  const total = essay + multipleChoice
  return { essay, multipleChoice, total, remaining: Math.max(0, 1000 - total) }
})

function message(err) {
  const errors = err.response?.data?.errors

  return (
    (
      errors &&
      Object.values(errors).flat()[0]
    ) ||
    err.response?.data?.message ||
    err.message ||
    "Có lỗi xảy ra."
  )
}

function resetForm() {
  editingId.value = null
  form.value = blankForm()
}

function removeOption(index) {
  if (form.value.dap_an.length <= 2) return

  form.value.dap_an.splice(index, 1)

  const selected = form.value.dap_an_dung_index

  form.value.dap_an_dung_index =
    selected === index
      ? null
      : selected > index
        ? selected - 1
        : selected
}

function removePreviewOption(q, index) {
  if (q.dap_an.length <= 2) return

  q.dap_an.splice(index, 1)

  const selected = q.dap_an_dung_index

  q.dap_an_dung_index =
    selected === index
      ? null
      : selected > index
        ? selected - 1
        : selected
}

function fixPreviewType(q) {
  if (
    q.loai_cau_hoi === "trac_nghiem" &&
    q.dap_an.length < 2
  ) {
    q.dap_an = ["", ""]
    q.dap_an_dung_index = null
  }
  allocatePreviewPoints()
}

function removePreviewQuestion(index) {
  previewQuestions.value.splice(index, 1)
  allocatePreviewPoints()
}

function allocatePreviewPoints() {
  const all = [...questions.value, ...previewQuestions.value]
  const essays = all.filter(q => q.loai_cau_hoi === 'tu_luan')
  const multipleChoice = all.filter(q => q.loai_cau_hoi === 'trac_nghiem')
  const essayCents = essays.reduce((sum, q) => sum + toCents(q.diem), 0)
  const remaining = Math.max(0, 1000 - essayCents)
  const base = multipleChoice.length ? Math.floor(remaining / multipleChoice.length) : 0
  const remainder = multipleChoice.length ? remaining % multipleChoice.length : 0

  multipleChoice.forEach((question, index) => {
    question._allocatedPoints = base + (index < remainder ? 1 : 0)
  })
}

function validate(q) {
  if (!String(q.noi_dung || "").trim()) {
    return "Nội dung câu hỏi không được trống."
  }

  if (q.loai_cau_hoi === 'tu_luan' &&
    (!(Number(q.diem) > 0 && Number(q.diem) <= 10) ||
      Math.abs(Number(q.diem) * 100 - Math.round(Number(q.diem) * 100)) > 0.000001)) {
    return "Điểm tự luận phải lớn hơn 0, tối đa 10 và không quá 2 chữ số thập phân."
  }

  if (q.loai_cau_hoi === "trac_nghiem") {
    if (
      q.dap_an.length < 2 ||
      q.dap_an.length > 6 ||
      q.dap_an.some(
        x => !String(x).trim()
      )
    ) {
      return "Câu trắc nghiệm cần 2–6 đáp án không rỗng."
    }

    if (
      !Number.isInteger(q.dap_an_dung_index) ||
      q.dap_an_dung_index < 0 ||
      q.dap_an_dung_index >= q.dap_an.length
    ) {
      return "Hãy chọn đáp án đúng."
    }
  } else if (!String(q.dap_an_mau || "").trim()) {
    return "Câu tự luận cần đáp án mẫu."
  }

  return ""
}

function payload(q) {
  return {
    loai_cau_hoi: q.loai_cau_hoi,
    noi_dung: String(q.noi_dung).trim(),
    diem: q.loai_cau_hoi === 'trac_nghiem'
      ? Number(q._allocatedPoints ?? toCents(q.diem)) / 100
      : Number(q.diem),

    dap_an:
      q.loai_cau_hoi === "trac_nghiem"
        ? q.dap_an.map(x => x.trim())
        : [],

    dap_an_dung_index:
      q.loai_cau_hoi === "trac_nghiem"
        ? q.dap_an_dung_index
        : null,

    dap_an_mau:
      q.loai_cau_hoi === "tu_luan"
        ? q.dap_an_mau.trim()
        : null,
  }
}

async function loadQuestions() {
  loading.value = true

  try {
    const response = await api.get(base.value)

    if (response.data?.status === false) {
      throw new Error(response.data.message)
    }

    questions.value =
      Array.isArray(response.data?.data)
        ? response.data.data
        : []
  } catch (err) {
    error.value = message(err)
  } finally {
    loading.value = false
  }
}

async function saveManual() {
  error.value = ""
  success.value = ""

  const validation = validate(form.value)

  if (validation) {
    error.value = validation
    return
  }

  const currentEssayCents = questions.value
    .filter(q => q.loai_cau_hoi === 'tu_luan' && q.id !== editingId.value)
    .reduce((sum, q) => sum + toCents(q.diem), 0)
  const essayTotal = currentEssayCents +
    (form.value.loai_cau_hoi === 'tu_luan' ? toCents(form.value.diem) : 0)
  if (essayTotal > 1000) {
    error.value = "Tổng điểm tự luận không được vượt quá 10."
    return
  }

  busy.value = true

  try {
    const response = editingId.value
      ? await api.put(
          `${base.value}/${editingId.value}`,
          payload(form.value)
        )
      : await api.post(
          base.value,
          payload(form.value)
        )

    if (response.data?.status === false) {
      throw new Error(response.data.message)
    }

    success.value = editingId.value
      ? "Đã cập nhật câu hỏi."
      : "Đã thêm câu hỏi."

    resetForm()
    await loadQuestions()
  } catch (err) {
    error.value = message(err)
  } finally {
    busy.value = false
  }
}

function editQuestion(q) {
  mode.value = "manual"
  editingId.value = q.id

  const options = q.dap_an || []

  const correctIndex = options.findIndex(
    x => truthy(x.la_dap_an_dung)
  )

  form.value = {
    loai_cau_hoi: q.loai_cau_hoi,
    noi_dung: q.noi_dung,
    diem: Number(q.diem),

    dap_an:
      q.loai_cau_hoi === "trac_nghiem"
        ? options.map(x => x.noi_dung)
        : ["", "", "", ""],

    dap_an_dung_index:
      correctIndex >= 0 ? correctIndex : null,

    dap_an_mau: q.dap_an_mau || "",
  }

  window.scrollTo({
    top: 0,
    behavior: "smooth",
  })
}

async function deleteQuestion(q) {
  if (!window.confirm("Xóa câu hỏi này?")) return

  busy.value = true
  error.value = ""

  try {
    const response = await api.delete(
      `${base.value}/${q.id}`
    )

    if (response.data?.status === false) {
      throw new Error(response.data.message)
    }

    success.value = "Đã xóa câu hỏi."

    if (editingId.value === q.id) {
      resetForm()
    }

    await loadQuestions()
  } catch (err) {
    error.value = message(err)
  } finally {
    busy.value = false
  }
}

/* ==================================
   NHẬP CÂU HỎI TỪ FILE
================================== */

function onFileChange(event) {
  selectedFile.value =
    event.target.files?.[0] || null

  previewQuestions.value = []
  error.value = ""
  success.value = ""
}

async function previewFile() {
  if (!selectedFile.value) return

  error.value = ""
  success.value = ""
  busy.value = true

  try {
    const formData = new FormData()
    formData.append("file", selectedFile.value)

    const response = await api.post(
      `${base.value}/import-preview`,
      formData
    )

    if (response.data?.status === false) {
      throw new Error(response.data.message)
    }

    const data = response.data?.data || []

    previewQuestions.value = data.map(
      (q, index) => ({
        ...q,
        _key: `${Date.now()}-${index}`,
      })
    )
    allocatePreviewPoints()

    success.value = data.length
      ? `Đọc được ${data.length} câu. Kiểm tra và sửa trước khi lưu.`
      : ""

    if (!data.length) {
      error.value =
        "Không trích xuất được câu hỏi. Kiểm tra định dạng file."
    }
  } catch (err) {
    error.value = message(err)
  } finally {
    busy.value = false
  }
}

async function saveImport() {
  error.value = ""
  success.value = ""

  for (
    let index = 0;
    index < previewQuestions.value.length;
    index++
  ) {
    const validation = validate(
      previewQuestions.value[index]
    )

    if (validation) {
      error.value =
        `Câu ${index + 1}: ${validation}`
      return
    }
  }

  const essayPoints = previewQuestions.value
    .filter(q => q.loai_cau_hoi === 'tu_luan')
    .reduce((sum, q) => sum + toCents(q.diem), 0)
  const savedEssayPoints = questions.value
    .filter(q => q.loai_cau_hoi === 'tu_luan')
    .reduce((sum, q) => sum + toCents(q.diem), 0)
  if (essayPoints + savedEssayPoints > 1000) {
    error.value = "Tổng điểm tự luận không được vượt quá 10."
    return
  }

  busy.value = true

  try {
    const response = await api.post(
      `${base.value}/import-save`,
      {
        questions: previewQuestions.value.map(payload),
      }
    )

    if (response.data?.status === false) {
      throw new Error(response.data.message)
    }

    success.value =
      `Đã lưu ${previewQuestions.value.length} câu hỏi.`

    previewQuestions.value = []
    selectedFile.value = null

    if (fileInput.value) {
      fileInput.value.value = ""
    }

    await loadQuestions()
  } catch (err) {
    error.value = message(err)
  } finally {
    busy.value = false
  }
}

onMounted(loadQuestions)
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.page {
  min-height: 100vh;
  padding: 28px;
  background: #f5f7fb;
  color: #172033;
  font-family: Inter, system-ui, sans-serif;
}

.heading,
.list-head,
.preview-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
}

.heading {
  margin-bottom: 22px;
}

.eyebrow {
  color: #2563eb;
  letter-spacing: 1px;
  font-size: 10px;
  font-weight: 800;
}

h1 {
  font-size: 28px;
  margin: 8px 0;
}

h2 {
  font-size: 18px;
  margin: 0 0 19px;
}

.heading p,
.hint {
  color: #64748b;
  font-size: 12px;
  line-height: 1.8;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(145px, 1fr));
  gap: 14px;
  margin-bottom: 19px;
}

.stats > div {
  border: 1px solid #e2e8f0;
  border-radius: 13px;
  padding: 19px;
  background: #fff;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stats span {
  font-size: 10px;
  color: #64748b;
}

.stats strong {
  font-size: 24px;
}

.tabs {
  display: flex;
  gap: 9px;
  margin-bottom: 19px;
}

.tabs button,
.choice-type button {
  padding: 12px 15px;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #fff;
  color: #475569;
  cursor: pointer;
  font-weight: 750;
}

.tabs .active,
.choice-type .chosen {
  background: #eff6ff;
  color: #2563eb;
  border-color: #2563eb;
}

.columns {
  display: grid;
  grid-template-columns:
    minmax(320px, 1fr)
    minmax(0, 1fr);
  gap: 18px;
  align-items: start;
}

.panel {
  padding: 22px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #fff;
  min-width: 0;
}

.field {
  margin: 16px 0;
}

.field label,
label {
  display: block;
  margin-bottom: 8px;
  font-size: 12px;
  font-weight: 700;
}

.field textarea,
.field input,
.option-row input:not([type="radio"]),
.preview-item textarea,
.preview-item select,
.preview-item input[type="number"] {
  width: 100%;
  padding: 11px;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  background: #fff;
  color: #172033;
  font: inherit;
  font-size: 12px;
}

.choice-type {
  display: flex;
  gap: 9px;
}

.choice-type button {
  flex: 1;
}

.option-row {
  display: flex;
  align-items: center;
  gap: 9px;
  margin: 10px 0;
}

.option-row input[type="radio"] {
  accent-color: #2563eb;
}

.option-row b {
  display: grid;
  place-items: center;
  width: 30px;
  height: 30px;
  flex-shrink: 0;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 8px;
}

.x {
  border: 0;
  background: transparent;
  color: #dc2626;
  font-size: 22px;
  cursor: pointer;
}

.x:disabled {
  opacity: 0.25;
}

.btn {
  border: 1px solid transparent;
  border-radius: 9px;
  padding: 10px 14px;
  cursor: pointer;
  font-size: 11px;
  font-weight: 800;
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn.primary {
  background: #2563eb;
  color: white;
}

.btn.light {
  border-color: #cbd5e1;
  background: #fff;
  color: #334155;
}

.btn.danger {
  background: #fef2f2;
  color: #dc2626;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 9px;
  margin-top: 17px;
}

.alert {
  padding: 13px;
  margin-bottom: 15px;
  border-radius: 9px;
  font-size: 12px;
}

.alert.bad {
  color: #991b1b;
  background: #fee2e2;
}

.alert.good {
  color: #166534;
  background: #dcfce7;
}

.saved-question,
.preview-item {
  margin-top: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  padding: 15px;
}

.question-text,
.essay p {
  white-space: pre-wrap;
  line-height: 1.7;
  font-size: 12px;
}

.saved-answer {
  padding: 9px 11px;
  font-size: 11px;
  border-radius: 7px;
  background: #f8fafc;
  margin-bottom: 6px;
}

.saved-answer.correct {
  background: #dcfce7;
  color: #15803d;
}

.saved-answer b {
  float: right;
}

.essay {
  padding: 11px;
  background: #f8fafc;
  border-radius: 9px;
  font-size: 11px;
}

.upload {
  display: block;
  width: 100%;
  margin: 17px 0;
  padding: 13px;
  border: 1px dashed #93c5fd;
  border-radius: 10px;
}

.format-note {
  margin-top: 20px;
  color: #475569;
  font-size: 11px;
  line-height: 1.7;
}

.format-note pre {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  background: #eff6ff;
  border-radius: 9px;
  padding: 12px;
  color: #1e40af;
}

.preview {
  margin-top: 25px;
}

.preview-item > textarea,
.preview-item > select {
  margin: 8px 0;
}

.preview-item label input {
  margin-top: 7px;
}

.list-head h2 {
  margin: 0;
}

.list-head {
  margin-bottom: 12px;
}

/* DARK MODE */
:global(html.dark-theme) .page {
  background: #1f2124;
  color: #fff;
}

:global(html.dark-theme) .panel,
:global(html.dark-theme) .stats > div,
:global(html.dark-theme) .saved-question,
:global(html.dark-theme) .preview-item {
  background: #111;
  border-color: #343942;
}

:global(html.dark-theme) .field textarea,
:global(html.dark-theme) .field input,
:global(html.dark-theme) .option-row input:not([type="radio"]),
:global(html.dark-theme) .preview-item textarea,
:global(html.dark-theme) .preview-item select,
:global(html.dark-theme) .preview-item input,
:global(html.dark-theme) .tabs button,
:global(html.dark-theme) .choice-type button,
:global(html.dark-theme) .btn.light {
  background: #202327;
  color: #fff;
  border-color: #404653;
}

:global(html.dark-theme) .tabs .active,
:global(html.dark-theme) .choice-type .chosen {
  background: #1e3a5f;
  color: #93c5fd;
}

:global(html.dark-theme) .saved-answer,
:global(html.dark-theme) .essay {
  background: #202327;
  color: #e2e8f0;
}

:global(html.dark-theme) .saved-answer.correct {
  background: #123d2c;
  color: #86efac;
}

/* RESPONSIVE */
@media (max-width: 1050px) {
  .columns {
    grid-template-columns: 1fr;
  }

  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 650px) {
  .page {
    padding: 15px;
  }

  .heading {
    flex-direction: column;
    align-items: flex-start;
  }

  .tabs {
    flex-wrap: wrap;
  }

  .stats {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }
}
</style>
