<template>
  <div class="class-page">
    <!-- HEADER -->
    <div class="page-header">
      <div>
        <p class="page-label">QUẢN LÝ HỆ THỐNG</p>
        <h1>Quản lý lớp học</h1>
        <p class="page-description">
          Quản lý thông tin lớp học và giảng viên phụ trách trong hệ thống.
        </p>
      </div>

      <button class="btn-add" @click="openCreateModal">
        <span>+</span>
        Thêm lớp học
      </button>
    </div>

    <!-- STATISTICS -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-icon blue">▣</div>
        <div>
          <span>Tổng lớp học</span>
          <h3>{{ totalClasses }}</h3>
          <p>Lớp học trong hệ thống</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon purple">♙</div>
        <div>
          <span>Giảng viên</span>
          <h3>{{ lecturers.length }}</h3>
          <p>Giảng viên đang hoạt động</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon green">♙</div>
        <div>
          <span>Sinh viên</span>
          <h3>{{ studentsOnCurrentPage }}</h3>
          <p>Trong các lớp ở trang hiện tại</p>
        </div>
      </div>
    </div>

    <!-- FILTER -->
    <div class="filter-card">
      <div class="search-box">
        <span>⌕</span>

        <input
          v-model="search"
          type="text"
          placeholder="Tìm theo mã lớp hoặc tên lớp..."
        />
      </div>

      <select v-model="lecturerFilter">
        <option value="">Tất cả giảng viên</option>

        <option
          v-for="lecturer in lecturers"
          :key="lecturer.id"
          :value="lecturer.id"
        >
          {{ lecturer.ho_ten }}
        </option>
      </select>

      <button
        v-if="hasFilter"
        class="btn-reset"
        @click="resetFilters"
      >
        Đặt lại
      </button>
    </div>

    <!-- TABLE -->
    <div class="table-card">
      <!-- Loading -->
      <div v-if="loading" class="state-message">
        Đang tải dữ liệu...
      </div>

      <!-- Error -->
      <div
        v-else-if="errorMessage"
        class="state-message error"
      >
        {{ errorMessage }}

        <button class="btn-retry" @click="getClasses">
          Thử lại
        </button>
      </div>

      <!-- Empty -->
      <div
        v-else-if="classes.length === 0"
        class="state-message"
      >
        Không tìm thấy lớp học.
      </div>

      <!-- Data -->
      <template v-else>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>LỚP HỌC</th>
                <th>MÃ LỚP</th>
                <th>GIẢNG VIÊN</th>
                <th>SINH VIÊN</th>
                <th>MÔ TẢ</th>
                <th>NGÀY TẠO</th>
                <th>THAO TÁC</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="classItem in classes"
                :key="classItem.id"
              >
                <!-- Lớp học -->
                <td>
                  <div class="class-info">
                    <div class="class-icon">▣</div>

                    <div>
                      <strong>
                        {{ classItem.ten_lop }}
                      </strong>

                      <span>
                        ID: #{{ classItem.id }}
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Mã lớp -->
                <td>
                  <span class="class-code">
                    {{ classItem.ma_lop }}
                  </span>
                </td>

                <!-- Giảng viên -->
                <td>
                  <div
                    v-if="getLecturer(classItem)"
                    class="lecturer"
                  >
                    <div class="avatar">
                      {{
                        getInitial(
                          getLecturer(classItem).ho_ten
                        )
                      }}
                    </div>

                    <div class="lecturer-info">
                      <strong>
                        {{ getLecturer(classItem).ho_ten }}
                      </strong>

                      <span v-if="getLecturer(classItem).email">
                        {{ getLecturer(classItem).email }}
                      </span>
                    </div>
                  </div>

                  <span v-else class="empty-text">
                    Chưa phân công
                  </span>
                </td>

                <!-- Sinh viên -->
                <td>
                  <span class="member-count">
                    {{ classItem.thanh_viens_count ?? 0 }}
                    sinh viên
                  </span>
                </td>

                <!-- Mô tả -->
                <td>
                  <span
                    class="description"
                    :title="classItem.mo_ta || ''"
                  >
                    {{
                      classItem.mo_ta ||
                      'Không có mô tả'
                    }}
                  </span>
                </td>

                <!-- Ngày tạo -->
                <td>
                  {{ formatDate(classItem.created_at) }}
                </td>

                <!-- Actions -->
                <td>
                  <div class="actions">
                    <button
                      class="btn-edit"
                      title="Chỉnh sửa lớp học"
                      @click="openEditModal(classItem)"
                    >
                      ✎
                    </button>

                    <button
                      class="btn-delete"
                      title="Xóa lớp học"
                      @click="deleteClass(classItem)"
                    >
                      ♲
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div class="pagination-container">
          <div class="pagination-info">
            Hiển thị
            <strong>{{ paginationFrom }}</strong>
            -
            <strong>{{ paginationTo }}</strong>
            trong
            <strong>{{ totalClasses }}</strong>
            lớp học
          </div>

          <div
            v-if="lastPage > 1"
            class="pagination"
          >
            <button
              :disabled="currentPage === 1"
              @click="changePage(currentPage - 1)"
            >
              ‹
            </button>

            <button
              v-for="page in paginationPages"
              :key="page"
              :class="{ active: page === currentPage }"
              @click="changePage(page)"
            >
              {{ page }}
            </button>

            <button
              :disabled="currentPage === lastPage"
              @click="changePage(currentPage + 1)"
            >
              ›
            </button>
          </div>
        </div>
      </template>
    </div>

    <!-- MODAL ADD / EDIT -->
    <div
      v-if="showModal"
      class="modal-overlay"
      @click.self="closeModal"
    >
      <div class="modal">
        <div class="modal-header">
          <div>
            <h2>
              {{
                editingClass
                  ? 'Chỉnh sửa lớp học'
                  : 'Thêm lớp học'
              }}
            </h2>

            <p>
              {{
                editingClass
                  ? 'Cập nhật thông tin lớp học.'
                  : 'Tạo lớp học mới trong hệ thống.'
              }}
            </p>
          </div>

          <button
            type="button"
            class="close-button"
            @click="closeModal"
          >
            ×
          </button>
        </div>

        <form @submit.prevent="saveClass">
          <!-- Mã lớp -->
          <div class="form-group">
            <label>
              Mã lớp
              <b>*</b>
            </label>

            <input
              v-model="form.ma_lop"
              type="text"
              maxlength="255"
              placeholder="Ví dụ: CS466"
              required
            />
          </div>

          <!-- Tên lớp -->
          <div class="form-group">
            <label>
              Tên lớp
              <b>*</b>
            </label>

            <input
              v-model="form.ten_lop"
              type="text"
              maxlength="255"
              placeholder="Nhập tên lớp học"
              required
            />
          </div>

          <!-- Giảng viên -->
          <div class="form-group">
            <label>
              Giảng viên phụ trách
              <b>*</b>
            </label>

            <select
              v-model="form.giang_vien_id"
              required
            >
              <option value="" disabled>
                Chọn giảng viên
              </option>

              <option
                v-for="lecturer in lecturers"
                :key="lecturer.id"
                :value="lecturer.id"
              >
                {{ lecturer.ho_ten }}
                - {{ lecturer.email }}
              </option>
            </select>
          </div>

          <!-- Mô tả -->
          <div class="form-group">
            <label>Mô tả</label>

            <textarea
              v-model="form.mo_ta"
              rows="4"
              maxlength="2000"
              placeholder="Nhập mô tả lớp học..."
            ></textarea>
          </div>

          <!-- Error -->
          <div
            v-if="formError"
            class="form-error"
          >
            {{ formError }}
          </div>

          <!-- Footer -->
          <div class="modal-footer">
            <button
              type="button"
              class="btn-cancel"
              :disabled="saving"
              @click="closeModal"
            >
              Hủy
            </button>

            <button
              type="submit"
              class="btn-save"
              :disabled="saving"
            >
              {{
                saving
                  ? 'Đang lưu...'
                  : editingClass
                    ? 'Lưu thay đổi'
                    : 'Thêm lớp học'
              }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api, { apiError } from '../../../services/api'

export default {
  data() {
    return {
      loading: true,
      saving: false,

      errorMessage: '',
      formError: '',

      // Data
      classes: [],
      lecturers: [],

      // Filter
      search: '',
      lecturerFilter: '',
      searchTimer: null,

      // Pagination
      currentPage: 1,
      lastPage: 1,
      totalClasses: 0,
      perPage: 9,

      // Modal
      showModal: false,
      editingClass: null,

      // Form
      form: {
        ma_lop: '',
        ten_lop: '',
        giang_vien_id: '',
        mo_ta: '',
      },
    }
  },

  computed: {
    hasFilter() {
      return Boolean(
        this.search ||
        this.lecturerFilter
      )
    },

    studentsOnCurrentPage() {
      return this.classes.reduce(
        (total, classItem) => {
          return (
            total +
            Number(
              classItem.thanh_viens_count ?? 0
            )
          )
        },
        0
      )
    },

    paginationPages() {
      const pages = []

      const start = Math.max(
        1,
        this.currentPage - 2
      )

      const end = Math.min(
        this.lastPage,
        this.currentPage + 2
      )

      for (
        let page = start;
        page <= end;
        page++
      ) {
        pages.push(page)
      }

      return pages
    },

    paginationFrom() {
      if (this.totalClasses === 0) {
        return 0
      }

      return (
        (this.currentPage - 1) *
          this.perPage +
        1
      )
    },

    paginationTo() {
      return Math.min(
        this.currentPage * this.perPage,
        this.totalClasses
      )
    },
  },

  watch: {
    search() {
      clearTimeout(this.searchTimer)

      this.searchTimer = setTimeout(() => {
        this.currentPage = 1
        this.getClasses()
      }, 400)
    },

    lecturerFilter() {
      this.currentPage = 1
      this.getClasses()
    },
  },

  mounted() {
    this.getData()
  },

  beforeUnmount() {
    clearTimeout(this.searchTimer)
  },

  methods: {
    /* =========================
       LOAD INITIAL DATA
    ========================== */

    async getData() {
      this.loading = true
      this.errorMessage = ''

      try {
        await Promise.all([
          this.getClasses(false),
          this.getLecturers(),
        ])
      } catch (error) {
        this.errorMessage = apiError(error)

        console.error(
          'Lỗi tải dữ liệu lớp học:',
          error
        )
      } finally {
        this.loading = false
      }
    },

    /* =========================
       GET CLASSES
    ========================== */

    async getClasses(showLoading = true) {
      if (showLoading) {
        this.loading = true
      }

      this.errorMessage = ''

      try {
        const params = {
          page: this.currentPage,
        }

        const keyword = this.search.trim()

        if (keyword) {
          params.search = keyword
        }

        if (this.lecturerFilter) {
          params.giang_vien_id =
            this.lecturerFilter
        }

        const { data } = await api.get(
          '/admin/lop-hoc',
          {
            params,
          }
        )

        this.classes = data.data ?? []

        this.currentPage =
          data.current_page ?? 1

        this.lastPage =
          data.last_page ?? 1

        this.totalClasses =
          data.total ?? 0

        this.perPage =
          data.per_page ?? 9
      } catch (error) {
        this.errorMessage = apiError(error)

        console.error(
          'Lỗi tải lớp học:',
          error
        )
      } finally {
        if (showLoading) {
          this.loading = false
        }
      }
    },

    /* =========================
       GET LECTURERS
    ========================== */

    async getLecturers() {
      try {
        const { data } = await api.get(
          '/admin/lop-hoc/giang-vien'
        )

        this.lecturers =
          data.data ?? []
      } catch (error) {
        console.error(
          'Lỗi tải giảng viên:',
          error
        )

        throw error
      }
    },

    /* =========================
       RESET FILTER
    ========================== */

    resetFilters() {
      clearTimeout(this.searchTimer)

      this.search = ''
      this.lecturerFilter = ''
      this.currentPage = 1

      this.getClasses()
    },

    /* =========================
       PAGINATION
    ========================== */

    changePage(page) {
      if (
        page < 1 ||
        page > this.lastPage ||
        page === this.currentPage
      ) {
        return
      }

      this.currentPage = page

      this.getClasses()
    },

    /* =========================
       CREATE MODAL
    ========================== */

    openCreateModal() {
      this.editingClass = null
      this.formError = ''

      this.form = {
        ma_lop: '',
        ten_lop: '',
        giang_vien_id: '',
        mo_ta: '',
      }

      this.showModal = true
    },

    /* =========================
       EDIT MODAL
    ========================== */

    openEditModal(classItem) {
      this.editingClass = classItem
      this.formError = ''

      const lecturer =
        this.getLecturer(classItem)

      this.form = {
        ma_lop:
          classItem.ma_lop ?? '',

        ten_lop:
          classItem.ten_lop ?? '',

        giang_vien_id:
          classItem.giang_vien_id ??
          lecturer?.id ??
          '',

        mo_ta:
          classItem.mo_ta ?? '',
      }

      this.showModal = true
    },

    /* =========================
       SAVE CLASS
    ========================== */

    async saveClass() {
      if (this.saving) {
        return
      }

      this.formError = ''

      const maLop =
        this.form.ma_lop.trim()

      const tenLop =
        this.form.ten_lop.trim()

      if (!maLop) {
        this.formError =
          'Vui lòng nhập mã lớp.'

        return
      }

      if (!tenLop) {
        this.formError =
          'Vui lòng nhập tên lớp.'

        return
      }

      if (!this.form.giang_vien_id) {
        this.formError =
          'Vui lòng chọn giảng viên.'

        return
      }

      this.saving = true

      try {
        const payload = {
          ma_lop: maLop,

          ten_lop: tenLop,

          giang_vien_id:
            this.form.giang_vien_id,

          mo_ta:
            this.form.mo_ta.trim() ||
            null,
        }

        // Sửa lớp
        if (this.editingClass) {
          await api.post(
            `/admin/lop-hoc/sua-lop-hoc/${this.editingClass.id}`,
            payload
          )
        }

        // Thêm lớp
        else {
          await api.post(
            '/admin/lop-hoc/them-lop-hoc',
            payload
          )

          this.currentPage = 1
        }

        this.closeModal()

        await this.getClasses()
      } catch (error) {
        this.formError = apiError(error)

        console.error(
          'Lỗi lưu lớp học:',
          error
        )
      } finally {
        this.saving = false
      }
    },

    /* =========================
       DELETE CLASS
    ========================== */

    async deleteClass(classItem) {
      const confirmed = window.confirm(
        `Bạn có chắc muốn xóa lớp "${classItem.ten_lop}" không?`
      )

      if (!confirmed) {
        return
      }

      try {
        await api.post(
          `/admin/lop-hoc/xoa-lop-hoc/${classItem.id}`
        )

        // Nếu xóa lớp cuối cùng của trang,
        // quay lại trang trước.
        if (
          this.classes.length === 1 &&
          this.currentPage > 1
        ) {
          this.currentPage--
        }

        await this.getClasses()
      } catch (error) {
        window.alert(
          apiError(error)
        )

        console.error(
          'Lỗi xóa lớp học:',
          error
        )
      }
    },

    /* =========================
       CLOSE MODAL
    ========================== */

    closeModal() {
      if (this.saving) {
        return
      }

      this.showModal = false
      this.editingClass = null
      this.formError = ''

      this.form = {
        ma_lop: '',
        ten_lop: '',
        giang_vien_id: '',
        mo_ta: '',
      }
    },

    /* =========================
       GET LECTURER
    ========================== */

    getLecturer(classItem) {
      return (
        classItem.giang_vien ||
        classItem.giangVien ||
        this.lecturers.find(
          (lecturer) =>
            Number(lecturer.id) ===
            Number(classItem.giang_vien_id)
        ) ||
        null
      )
    },

    /* =========================
       FORMAT
    ========================== */

    getInitial(name) {
      if (!name) {
        return 'G'
      }

      return name
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    formatDate(date) {
      if (!date) {
        return '-'
      }

      const parsed = new Date(date)

      if (
        Number.isNaN(
          parsed.getTime()
        )
      ) {
        return '-'
      }

      return parsed.toLocaleDateString(
        'vi-VN'
      )
    },
  },
}
</script>

<style scoped>
.class-page {
  width: 100%;
}

/* =========================
   HEADER
========================= */

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 20px;

  margin-bottom: 24px;
}

.page-label {
  margin: 0 0 7px;

  color: #315de5;

  font-size: 9px;
  font-weight: 700;

  letter-spacing: 1.4px;
}

.page-header h1 {
  margin: 0;

  color: #1e293b;

  font-size: 24px;
}

.page-description {
  margin: 7px 0 0;

  color: #8a94a6;

  font-size: 11px;
}

.btn-add {
  display: flex;
  align-items: center;
  justify-content: center;

  gap: 6px;

  border: none;
  border-radius: 9px;

  padding: 11px 17px;

  background: #315de5;

  color: white;

  cursor: pointer;

  font-size: 11px;
  font-weight: 600;

  transition: 0.2s;
}

.btn-add:hover {
  background: #274fc9;

  transform: translateY(-1px);
}

.btn-add span {
  font-size: 16px;
  line-height: 1;
}

/* =========================
   STATISTICS
========================= */

.stats {
  display: grid;

  grid-template-columns:
    repeat(3, minmax(0, 1fr));

  gap: 17px;

  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: center;

  min-width: 0;

  gap: 14px;

  padding: 18px;

  border: 1px solid #e9edf4;

  border-radius: 12px;

  background: white;

  box-shadow:
    0 4px 13px
    rgba(29, 46, 83, 0.03);
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 44px;
  min-width: 44px;
  height: 44px;

  border-radius: 11px;

  font-weight: 700;
}

.stat-card span {
  color: #818a9c;

  font-size: 10px;
}

.stat-card h3 {
  margin: 3px 0;

  color: #1e293b;

  font-size: 22px;
}

.stat-card p {
  margin: 0;

  color: #a0a7b5;

  font-size: 9px;
}

.blue {
  background: #edf2ff;
  color: #315de5;
}

.purple {
  background: #f3edff;
  color: #8055d9;
}

.green {
  background: #eaf8f1;
  color: #31966b;
}

/* =========================
   FILTER
========================= */

.filter-card {
  display: flex;
  align-items: stretch;

  gap: 12px;

  margin-bottom: 18px;

  padding: 17px;

  border: 1px solid #e9edf4;

  border-radius: 12px;

  background: white;
}

.search-box {
  display: flex;
  align-items: center;

  flex: 1;

  gap: 8px;

  padding: 0 12px;

  border: 1px solid #e2e8f0;

  border-radius: 8px;

  background: white;
}

.search-box:focus-within {
  border-color: #315de5;

  box-shadow:
    0 0 0 3px
    rgba(49, 93, 229, 0.07);
}

.search-box span {
  color: #94a3b8;

  font-size: 18px;
}

.search-box input {
  width: 100%;

  border: none;

  outline: none;

  padding: 10px 0;

  background: transparent;

  color: #334155;

  font-size: 11px;
}

.filter-card select {
  min-width: 210px;

  border: 1px solid #e2e8f0;

  border-radius: 8px;

  padding: 0 12px;

  background: white;

  color: #475569;

  outline: none;

  font-size: 10px;
}

.filter-card select:focus {
  border-color: #315de5;
}

.btn-reset {
  border: 1px solid #e2e8f0;

  border-radius: 8px;

  padding: 0 14px;

  background: #f8fafc;

  color: #64748b;

  cursor: pointer;

  font-size: 10px;
}

.btn-reset:hover {
  background: #f1f5f9;
}

/* =========================
   TABLE
========================= */

.table-card {
  overflow: hidden;

  border: 1px solid #e9edf4;

  border-radius: 12px;

  background: white;

  box-shadow:
    0 4px 13px
    rgba(29, 46, 83, 0.025);
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;

  border-collapse: collapse;
}

th {
  padding: 13px 17px;

  background: #f8fafc;

  color: #8490a3;

  text-align: left;

  font-size: 9px;
  font-weight: 700;

  letter-spacing: 0.4px;

  white-space: nowrap;
}

td {
  padding: 14px 17px;

  border-top: 1px solid #edf1f7;

  color: #475569;

  font-size: 10px;

  vertical-align: middle;
}

tbody tr {
  transition: background 0.15s;
}

tbody tr:hover {
  background: #fafcff;
}

/* =========================
   CLASS
========================= */

.class-info {
  display: flex;
  align-items: center;

  gap: 11px;

  min-width: 170px;
}

.class-icon {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 38px;
  min-width: 38px;
  height: 38px;

  border-radius: 10px;

  background: #edf2ff;

  color: #315de5;

  font-size: 14px;
}

.class-info strong {
  display: block;

  overflow: hidden;

  max-width: 190px;

  color: #334155;

  font-size: 11px;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.class-info span {
  display: block;

  margin-top: 3px;

  color: #9aa3b5;

  font-size: 8px;
}

.class-code {
  display: inline-block;

  border-radius: 6px;

  padding: 5px 8px;

  background: #f1f5f9;

  color: #475569;

  font-weight: 600;

  white-space: nowrap;
}

/* =========================
   LECTURER
========================= */

.lecturer {
  display: flex;
  align-items: center;

  gap: 8px;

  min-width: 145px;
}

.avatar {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 31px;
  min-width: 31px;
  height: 31px;

  border-radius: 50%;

  background: #f3edff;

  color: #8055d9;

  font-size: 9px;
  font-weight: 700;
}

.lecturer-info {
  min-width: 0;
}

.lecturer-info strong {
  display: block;

  overflow: hidden;

  max-width: 150px;

  color: #475569;

  font-size: 10px;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.lecturer-info span {
  display: block;

  overflow: hidden;

  max-width: 150px;

  margin-top: 2px;

  color: #94a3b8;

  font-size: 8px;

  text-overflow: ellipsis;
  white-space: nowrap;
}

.empty-text {
  color: #a0a7b5;

  font-style: italic;
}

/* =========================
   STUDENT COUNT
========================= */

.member-count {
  display: inline-flex;
  align-items: center;

  border-radius: 20px;

  padding: 5px 9px;

  background: #eaf8f1;

  color: #31966b;

  font-size: 9px;
  font-weight: 600;

  white-space: nowrap;
}

/* =========================
   DESCRIPTION
========================= */

.description {
  display: block;

  overflow: hidden;

  max-width: 230px;

  color: #64748b;

  text-overflow: ellipsis;
  white-space: nowrap;
}

/* =========================
   ACTIONS
========================= */

.actions {
  display: flex;

  gap: 7px;
}

.actions button {
  display: flex;
  align-items: center;
  justify-content: center;

  width: 30px;
  height: 30px;

  border: none;

  border-radius: 7px;

  cursor: pointer;

  transition: 0.2s;
}

.actions button:hover {
  transform: translateY(-1px);
}

.btn-edit {
  background: #edf2ff;

  color: #315de5;
}

.btn-edit:hover {
  background: #dfe7ff;
}

.btn-delete {
  background: #fff0f1;

  color: #d04444;
}

.btn-delete:hover {
  background: #ffe3e5;
}

/* =========================
   PAGINATION
========================= */

.pagination-container {
  display: flex;
  align-items: center;
  justify-content: space-between;

  gap: 15px;

  padding: 14px 17px;

  border-top: 1px solid #edf1f7;

  background: white;
}

.pagination-info {
  color: #94a3b8;

  font-size: 9px;
}

.pagination-info strong {
  color: #64748b;
}

.pagination {
  display: flex;
  align-items: center;

  gap: 6px;
}

.pagination button {
  min-width: 31px;
  height: 31px;

  border: 1px solid #e2e8f0;

  border-radius: 7px;

  padding: 0 8px;

  background: white;

  color: #64748b;

  cursor: pointer;

  font-size: 10px;

  transition: 0.15s;
}

.pagination button:hover:not(:disabled) {
  border-color: #315de5;

  color: #315de5;
}

.pagination button.active {
  border-color: #315de5;

  background: #315de5;

  color: white;
}

.pagination button:disabled {
  cursor: not-allowed;

  opacity: 0.4;
}

/* =========================
   STATES
========================= */

.state-message {
  padding: 45px 20px;

  color: #94a3b8;

  text-align: center;

  font-size: 11px;
}

.state-message.error {
  color: #d04444;

  background: #fffafb;
}

.btn-retry {
  display: block;

  margin: 12px auto 0;

  border: none;

  border-radius: 7px;

  padding: 8px 14px;

  background: #315de5;

  color: white;

  cursor: pointer;

  font-size: 9px;
}

/* =========================
   MODAL
========================= */

.modal-overlay {
  position: fixed;

  z-index: 9999;

  inset: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  padding: 20px;

  background:
    rgba(15, 23, 42, 0.42);

  backdrop-filter: blur(2px);
}

.modal {
  width: 100%;
  max-width: 520px;

  max-height: calc(100vh - 40px);

  overflow-y: auto;

  border-radius: 14px;

  padding: 24px;

  background: white;

  box-shadow:
    0 20px 50px
    rgba(15, 23, 42, 0.18);
}

.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;

  gap: 15px;

  margin-bottom: 22px;
}

.modal-header h2 {
  margin: 0;

  color: #1e293b;

  font-size: 18px;
}

.modal-header p {
  margin: 5px 0 0;

  color: #94a3b8;

  font-size: 10px;
}

.close-button {
  border: none;

  padding: 0;

  background: transparent;

  color: #94a3b8;

  cursor: pointer;

  font-size: 25px;
  line-height: 1;
}

.close-button:hover {
  color: #475569;
}

/* =========================
   FORM
========================= */

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;

  margin-bottom: 7px;

  color: #475569;

  font-size: 10px;
  font-weight: 600;
}

.form-group label b {
  color: #d04444;
}

.form-group input,
.form-group select,
.form-group textarea {
  box-sizing: border-box;

  width: 100%;

  border: 1px solid #e2e8f0;

  border-radius: 8px;

  padding: 10px 11px;

  background: white;

  color: #334155;

  outline: none;

  font-family: inherit;

  font-size: 11px;

  transition: 0.15s;
}

.form-group textarea {
  resize: vertical;

  min-height: 90px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  border-color: #315de5;

  box-shadow:
    0 0 0 3px
    rgba(49, 93, 229, 0.08);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
  color: #b0b7c4;
}

.form-error {
  margin-top: 5px;

  border-radius: 8px;

  padding: 10px 12px;

  background: #fff0f1;

  color: #c43d4b;

  font-size: 10px;
}

/* =========================
   MODAL FOOTER
========================= */

.modal-footer {
  display: flex;
  justify-content: flex-end;

  gap: 9px;

  margin-top: 24px;

  padding-top: 17px;

  border-top: 1px solid #edf1f7;
}

.btn-cancel,
.btn-save {
  border: none;

  border-radius: 8px;

  padding: 10px 16px;

  cursor: pointer;

  font-size: 10px;
  font-weight: 600;

  transition: 0.2s;
}

.btn-cancel {
  background: #f1f5f9;

  color: #64748b;
}

.btn-cancel:hover:not(:disabled) {
  background: #e2e8f0;
}

.btn-save {
  background: #315de5;

  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #274fc9;
}

.btn-save:disabled,
.btn-cancel:disabled {
  cursor: not-allowed;

  opacity: 0.6;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {
  .stats {
    grid-template-columns:
      repeat(2, 1fr);
  }
}

@media (max-width: 850px) {
  .page-header {
    align-items: flex-start;

    flex-direction: column;
  }

  .btn-add {
    width: 100%;
  }

  .filter-card {
    flex-direction: column;
  }

  .filter-card select,
  .btn-reset {
    width: 100%;

    min-height: 38px;
  }
}

@media (max-width: 600px) {
  .stats {
    grid-template-columns: 1fr;
  }

  .pagination-container {
    align-items: flex-start;

    flex-direction: column;
  }

  .pagination {
    flex-wrap: wrap;
  }

  .modal {
    padding: 18px;
  }
}
</style>