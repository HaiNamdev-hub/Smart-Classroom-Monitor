<template>
  <div class="dashboard">
    <!-- =========================
         WELCOME
    ========================== -->
    <div class="welcome">
      <div>
        <p class="welcome-label">
          TỔNG QUAN HỆ THỐNG
        </p>

        <h2>
          Xin chào,
          {{ currentUser?.ho_ten || 'Quản trị viên' }}!
        </h2>

        <p>
          Theo dõi và quản lý hoạt động của hệ thống tại đây.
        </p>
      </div>

      <div class="welcome-icon">
        ◫
      </div>
    </div>

    <!-- =========================
         LOADING
    ========================== -->
    <div
      v-if="loading"
      class="loading"
    >
      Đang tải dữ liệu...
    </div>

    <!-- =========================
         ERROR
    ========================== -->
    <div
      v-else-if="errorMessage"
      class="error"
    >
      {{ errorMessage }}
    </div>

    <template v-else>
      <!-- =========================
           STATISTICS
      ========================== -->
      <div class="stats">
        <!-- Tổng người dùng -->
        <div class="stat-card">
          <div class="stat-icon blue">
            ♙
          </div>

          <div>
            <span>Tổng người dùng</span>

            <h3>
              {{ thongKe.nguoi_dung }}
            </h3>

            <p>Tài khoản trong hệ thống</p>
          </div>
        </div>

        <!-- Tổng lớp học -->
        <div class="stat-card">
          <div class="stat-icon purple">
            ▣
          </div>

          <div>
            <span>Tổng lớp học</span>

            <h3>
              {{ thongKe.lop_hoc }}
            </h3>

            <p>Lớp học đang quản lý</p>
          </div>
        </div>

        <!-- Giảng viên -->
        <div class="stat-card">
          <div class="stat-icon green">
            ♙
          </div>

          <div>
            <span>Giảng viên</span>

            <h3>
              {{ thongKe.giang_vien }}
            </h3>

            <p>Tài khoản giảng viên</p>
          </div>
        </div>

        <!-- Phiên đang diễn ra -->
        <div class="stat-card">
          <div class="stat-icon orange">
            ◉
          </div>

          <div>
            <span>Phiên đang diễn ra</span>

            <h3>
              {{ thongKe.phien_dang_dien_ra }}
            </h3>

            <p>Phiên giám sát đang hoạt động</p>
          </div>
        </div>

        <!-- Cảnh báo chưa xử lý -->
        <div class="stat-card warning-card">
          <div class="stat-icon red">
            !
          </div>

          <div>
            <span>Cảnh báo chưa xử lý</span>

            <h3>
              {{ thongKe.canh_bao_chua_xu_ly }}
            </h3>

            <p>Cảnh báo cần kiểm tra</p>
          </div>
        </div>
      </div>

      <!-- =========================
           RECENT DATA
      ========================== -->
      <div class="recent-grid">
        <!-- =========================
             LỚP HỌC GẦN ĐÂY
        ========================== -->
        <div class="recent-card">
          <div class="recent-header">
            <div>
              <h3>Lớp học gần đây</h3>

              <p>
                Các lớp học được tạo gần nhất
              </p>
            </div>

            <a
              href="/admin/lop-hoc"
              @click.prevent="
                $emit(
                  'navigate',
                  '/admin/lop-hoc'
                )
              "
            >
              Xem tất cả
            </a>
          </div>

          <!-- Không có dữ liệu -->
          <div
            v-if="lopHocMoi.length === 0"
            class="empty-state"
          >
            Chưa có lớp học.
          </div>

          <!-- Danh sách lớp -->
          <div
            v-else
            class="recent-list"
          >
            <div
              v-for="lop in lopHocMoi"
              :key="lop.id"
              class="recent-item"
            >
              <div class="recent-icon purple">
                ▣
              </div>

              <div class="recent-info">
                <strong>
                  {{
                    lop.ten_lop ||
                    lop.ten_lop_hoc ||
                    'Chưa có tên lớp'
                  }}
                </strong>

                <span>
                  Giảng viên:
                  {{
                    lop.giang_vien?.ho_ten ||
                    'Chưa phân công'
                  }}
                </span>
              </div>

              <div class="recent-meta">
                {{
                  lop.thanh_viens_count ?? 0
                }}
                thành viên
              </div>
            </div>
          </div>
        </div>

        <!-- =========================
             NGƯỜI DÙNG GẦN ĐÂY
        ========================== -->
        <div class="recent-card">
          <div class="recent-header">
            <div>
              <h3>Người dùng gần đây</h3>

              <p>
                Các tài khoản được tạo gần nhất
              </p>
            </div>

            <a
              href="/admin/nguoi-dung"
              @click.prevent="
                $emit(
                  'navigate',
                  '/admin/nguoi-dung'
                )
              "
            >
              Xem tất cả
            </a>
          </div>

          <!-- Không có dữ liệu -->
          <div
            v-if="nguoiDungMoi.length === 0"
            class="empty-state"
          >
            Chưa có người dùng.
          </div>

          <!-- Danh sách người dùng -->
          <div
            v-else
            class="recent-list"
          >
            <div
              v-for="nguoiDung in nguoiDungMoi"
              :key="nguoiDung.id"
              class="recent-item"
            >
              <div class="user-avatar">
                {{
                  layChuCaiDau(
                    nguoiDung.ho_ten
                  )
                }}
              </div>

              <div class="recent-info">
                <strong>
                  {{
                    nguoiDung.ho_ten ||
                    'Chưa có tên'
                  }}
                </strong>

                <span>
                  {{ nguoiDung.email }}
                </span>
              </div>

              <div class="role-badge">
                {{
                  formatRole(
                    nguoiDung.vai_tro
                      ?.ten_vai_tro
                  )
                }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- =========================
           QUICK ACTIONS
      ========================== -->
      <div class="section-card">
        <div class="section-header">
          <h3>Quản lý nhanh</h3>

          <p>
            Truy cập nhanh các chức năng quản trị
          </p>
        </div>

        <div class="quick-grid">
          <!-- Người dùng -->
          <a
            href="/admin/nguoi-dung"
            class="quick-item"
            @click.prevent="
              $emit(
                'navigate',
                '/admin/nguoi-dung'
              )
            "
          >
            <div class="quick-icon blue">
              ♙
            </div>

            <div>
              <strong>
                Quản lý người dùng
              </strong>

              <span>
                Thêm, chỉnh sửa và quản lý tài khoản
              </span>
            </div>

            <b>›</b>
          </a>

          <!-- Lớp học -->
          <a
            href="/admin/lop-hoc"
            class="quick-item"
            @click.prevent="
              $emit(
                'navigate',
                '/admin/lop-hoc'
              )
            "
          >
            <div class="quick-icon purple">
              ▣
            </div>

            <div>
              <strong>
                Quản lý lớp học
              </strong>

              <span>
                Tạo và quản lý các lớp học
              </span>
            </div>

            <b>›</b>
          </a>

          <!-- Vai trò -->
          <a
            href="/admin/vai-tro"
            class="quick-item"
            @click.prevent="
              $emit(
                'navigate',
                '/admin/vai-tro'
              )
            "
          >
            <div class="quick-icon green">
              ♢
            </div>

            <div>
              <strong>
                Quản lý vai trò
              </strong>

              <span>
                Quản lý quyền và vai trò người dùng
              </span>
            </div>

            <b>›</b>
          </a>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import api, {
  apiError,
} from '../../../services/api'

export default {
  props: {
    currentUser: {
      type: Object,
      default: null,
    },
  },

  emits: [
    'navigate',
  ],

  data() {
    return {
      loading: true,

      errorMessage: '',

      /* Thống kê */
      thongKe: {
        nguoi_dung: 0,
        lop_hoc: 0,
        giang_vien: 0,
        phien_dang_dien_ra: 0,
        canh_bao_chua_xu_ly: 0,
      },

      /* Dữ liệu gần đây */
      lopHocMoi: [],

      nguoiDungMoi: [],
    }
  },

  mounted() {
    this.getData()
  },

  methods: {
    /* =========================
       LẤY DỮ LIỆU DASHBOARD
    ========================== */
    async getData() {
      this.loading = true
      this.errorMessage = ''

      try {
        const { data } = await api.get(
          '/admin/trang-chu/get-data'
        )

        /* Thống kê */
        this.thongKe = {
          nguoi_dung:
            data.thong_ke?.nguoi_dung ?? 0,

          lop_hoc:
            data.thong_ke?.lop_hoc ?? 0,

          giang_vien:
            data.thong_ke?.giang_vien ?? 0,

          phien_dang_dien_ra:
            data.thong_ke
              ?.phien_dang_dien_ra ?? 0,

          canh_bao_chua_xu_ly:
            data.thong_ke
              ?.canh_bao_chua_xu_ly ?? 0,
        }

        /* Lớp học gần đây */
        this.lopHocMoi =
          data.lop_hoc ?? []

        /* Người dùng gần đây */
        this.nguoiDungMoi =
          data.nguoi_dung ?? []

      } catch (error) {
        this.errorMessage =
          apiError(error)

        console.error(
          'Lỗi tải Dashboard:',
          error
        )
      } finally {
        this.loading = false
      }
    },

    /* =========================
       LẤY CHỮ CÁI ĐẦU
    ========================== */
    layChuCaiDau(hoTen) {
      if (!hoTen) {
        return 'U'
      }

      return hoTen
        .trim()
        .charAt(0)
        .toUpperCase()
    },

    /* =========================
       HIỂN THỊ TÊN VAI TRÒ
    ========================== */
    formatRole(role) {
      const roles = {
        quan_tri_vien:
          'Quản trị viên',

        giang_vien:
          'Giảng viên',

        sinh_vien:
          'Sinh viên',

        tro_giang:
          'Trợ giảng',
      }

      return (
        roles[role] ||
        role ||
        'Chưa có vai trò'
      )
    },
  },
}
</script>

<style scoped>
/* =========================
   DASHBOARD
========================= */

.dashboard {
  width: 100%;
}


/* =========================
   WELCOME
========================= */

.welcome {
  min-height: 150px;

  background: linear-gradient(
    110deg,
    #233f9e,
    #315de5
  );

  border-radius: 15px;

  padding: 30px 35px;

  color: white;

  display: flex;

  justify-content: space-between;

  align-items: center;

  box-shadow:
    0 8px 25px
    rgba(49, 93, 229, 0.16);
}

.welcome-label {
  margin: 0 0 10px;

  color: #c8d5ff;

  font-size: 9px;

  letter-spacing: 1.5px;
}

.welcome h2 {
  margin: 0 0 8px;

  font-size: 25px;
}

.welcome p:last-child {
  margin: 0;

  color: #dbe4ff;

  font-size: 12px;
}

.welcome-icon {
  width: 80px;

  height: 80px;

  border-radius: 20px;

  display: flex;

  align-items: center;

  justify-content: center;

  background:
    rgba(255, 255, 255, 0.12);

  font-size: 38px;
}


/* =========================
   STATISTICS
========================= */

.stats {
  margin-top: 24px;

  display: grid;

  grid-template-columns:
    repeat(
      5,
      minmax(0, 1fr)
    );

  gap: 17px;
}

.stat-card {
  min-width: 0;

  background: white;

  border:
    1px solid #e9edf4;

  border-radius: 12px;

  padding: 20px;

  display: flex;

  align-items: center;

  gap: 15px;

  box-shadow:
    0 4px 13px
    rgba(29, 46, 83, 0.03);
}

.stat-icon {
  min-width: 44px;

  width: 44px;

  height: 44px;

  border-radius: 11px;

  display: flex;

  align-items: center;

  justify-content: center;

  font-weight: bold;
}

.stat-card span {
  color: #818a9c;

  font-size: 10px;
}

.stat-card h3 {
  margin: 3px 0;

  font-size: 23px;
}

.stat-card p {
  margin: 0;

  color: #a0a7b5;

  font-size: 9px;
}


/* =========================
   COLORS
========================= */

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

.orange {
  background: #fff4e6;

  color: #d78828;
}

.red {
  background: #fff0f1;

  color: #d04444;
}


/* =========================
   RECENT
========================= */

.recent-grid {
  margin-top: 24px;

  display: grid;

  grid-template-columns:
    repeat(
      2,
      minmax(0, 1fr)
    );

  gap: 20px;
}

.recent-card {
  min-width: 0;

  background: #ffffff;

  border:
    1px solid #e9edf4;

  border-radius: 12px;

  padding: 22px;

  box-shadow:
    0 4px 13px
    rgba(29, 46, 83, 0.03);
}


/* HEADER */

.recent-header {
  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 15px;

  margin-bottom: 18px;
}

.recent-header h3 {
  margin: 0;

  color: #1e293b;

  font-size: 15px;
}

.recent-header p {
  margin: 5px 0 0;

  color: #939bac;

  font-size: 10px;
}

.recent-header a {
  flex-shrink: 0;

  color: #315de5;

  text-decoration: none;

  font-size: 10px;

  font-weight: 600;
}

.recent-header a:hover {
  text-decoration: underline;
}


/* LIST */

.recent-list {
  display: flex;

  flex-direction: column;
}

.recent-item {
  min-height: 65px;

  display: flex;

  align-items: center;

  gap: 12px;

  padding: 12px 0;

  border-bottom:
    1px solid #edf1f7;
}

.recent-item:last-child {
  border-bottom: none;
}


/* ICON */

.recent-icon {
  width: 40px;

  height: 40px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 10px;

  font-size: 16px;
}


/* USER AVATAR */

.user-avatar {
  width: 40px;

  height: 40px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 50%;

  background: #edf2ff;

  color: #315de5;

  font-size: 13px;

  font-weight: 700;
}


/* INFORMATION */

.recent-info {
  min-width: 0;

  flex: 1;
}

.recent-info strong {
  display: block;

  overflow: hidden;

  color: #334155;

  font-size: 11px;

  font-weight: 600;

  text-overflow: ellipsis;

  white-space: nowrap;
}

.recent-info span {
  display: block;

  margin-top: 4px;

  overflow: hidden;

  color: #929bad;

  font-size: 9px;

  text-overflow: ellipsis;

  white-space: nowrap;
}


/* META */

.recent-meta {
  flex-shrink: 0;

  color: #929bad;

  font-size: 9px;

  white-space: nowrap;
}


/* ROLE */

.role-badge {
  flex-shrink: 0;

  padding: 5px 9px;

  border-radius: 20px;

  background: #edf2ff;

  color: #315de5;

  font-size: 9px;

  font-weight: 600;

  white-space: nowrap;
}


/* EMPTY */

.empty-state {
  padding: 30px 10px;

  color: #9aa3b5;

  text-align: center;

  font-size: 11px;
}


/* =========================
   QUICK ACTION
========================= */

.section-card {
  margin-top: 24px;

  background: white;

  border:
    1px solid #e9edf4;

  border-radius: 12px;

  padding: 23px;
}

.section-header h3 {
  margin: 0;

  font-size: 15px;
}

.section-header p {
  margin: 5px 0 0;

  color: #939bac;

  font-size: 10px;
}

.quick-grid {
  margin-top: 20px;

  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 15px;
}

.quick-item {
  min-height: 83px;

  border:
    1px solid #e8edf5;

  border-radius: 10px;

  padding: 15px;

  display: flex;

  align-items: center;

  gap: 12px;

  color: inherit;

  text-decoration: none;

  transition: 0.2s;
}

.quick-item:hover {
  border-color: #aebff1;

  transform:
    translateY(-2px);

  box-shadow:
    0 5px 15px
    rgba(33, 58, 120, 0.07);
}

.quick-icon {
  width: 42px;

  height: 42px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 9px;

  font-size: 17px;
}

.quick-item div:nth-child(2) {
  flex: 1;
}

.quick-item strong {
  display: block;

  font-size: 11px;
}

.quick-item span {
  display: block;

  margin-top: 4px;

  color: #9199aa;

  font-size: 9px;
}

.quick-item b {
  color: #a2aabc;

  font-size: 21px;
}


/* =========================
   LOADING / ERROR
========================= */

.loading,
.error {
  margin-top: 20px;

  background: white;

  padding: 18px;

  border-radius: 9px;

  font-size: 12px;
}

.error {
  color: #b33140;

  background: #fff0f1;
}


/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1350px) {
  .stats {
    grid-template-columns:
      repeat(3, 1fr);
  }
}

@media (max-width: 1100px) {
  .stats {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .recent-grid {
    grid-template-columns: 1fr;
  }

  .quick-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 750px) {
  .stats {
    grid-template-columns: 1fr;
  }

  .welcome {
    padding: 25px 20px;
  }

  .welcome-icon {
    display: none;
  }

  .recent-header {
    align-items: flex-start;
  }

  .recent-meta {
    display: none;
  }
}
</style>