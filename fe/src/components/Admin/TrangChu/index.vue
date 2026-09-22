<template>
  <div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="brand">
        <div class="brand-icon">SC</div>

        <div>
          <h2>Smart Classroom</h2>
          <span>MONITOR</span>
        </div>
      </div>

      <nav class="menu">
        <p class="menu-title">QUẢN LÝ</p>

        <router-link to="/admin/trang-chu" class="menu-item">
          <span class="icon">⌂</span>
          Trang chủ
        </router-link>

        <router-link to="/admin/nguoi-dung" class="menu-item">
          <span class="icon">♙</span>
          Quản lý người dùng
        </router-link>

        <router-link to="/admin/lop-hoc" class="menu-item">
          <span class="icon">▣</span>
          Quản lý lớp học
        </router-link>

        <router-link to="/admin/vai-tro" class="menu-item">
          <span class="icon">♢</span>
          Quản lý vai trò
        </router-link>
      </nav>

      <div class="sidebar-bottom">
        <button class="logout-btn" @click="dangXuat">
          <span>↪</span>
          Đăng xuất
        </button>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">

      <!-- HEADER -->
      <header class="header">
        <div>
          <h1>Trang quản trị</h1>
          <p>Quản lý và theo dõi hệ thống Smart Classroom Monitor</p>
        </div>

        <div class="admin-profile">
          <div class="avatar">
            {{ chuCaiDau }}
          </div>

          <div>
            <strong>{{ admin?.ho_ten || 'Quản trị viên' }}</strong>
            <span>{{ admin?.email || '' }}</span>
          </div>
        </div>
      </header>

      <!-- CONTENT -->
      <section class="content">

        <div class="welcome">
          <div>
            <p class="welcome-label">TỔNG QUAN HỆ THỐNG</p>

            <h2>
              Xin chào,
              {{ admin?.ho_ten || 'Quản trị viên' }}!
            </h2>

            <p>
              Theo dõi và quản lý hoạt động của hệ thống tại đây.
            </p>
          </div>

          <div class="welcome-icon">
            ◫
          </div>
        </div>

        <!-- LOADING -->
        <div v-if="loading" class="loading">
          Đang tải dữ liệu...
        </div>

        <!-- ERROR -->
        <div v-else-if="errorMessage" class="error">
          {{ errorMessage }}
        </div>

        <template v-else>

          <!-- STAT CARDS -->
          <div class="stats">

            <div class="stat-card">
              <div class="stat-icon blue">
                ♙
              </div>

              <div>
                <span>Tổng người dùng</span>
                <h3>{{ thongKe.tong_nguoi_dung }}</h3>
                <p>Tài khoản trong hệ thống</p>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon purple">
                ▣
              </div>

              <div>
                <span>Tổng lớp học</span>
                <h3>{{ thongKe.tong_lop_hoc }}</h3>
                <p>Lớp học đang quản lý</p>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon green">
                ✓
              </div>

              <div>
                <span>Đang hoạt động</span>
                <h3>{{ thongKe.dang_hoat_dong }}</h3>
                <p>Tài khoản hoạt động</p>
              </div>
            </div>

            <div class="stat-card">
              <div class="stat-icon orange">
                ♢
              </div>

              <div>
                <span>Vai trò</span>
                <h3>{{ thongKe.tong_vai_tro }}</h3>
                <p>Vai trò trong hệ thống</p>
              </div>
            </div>

          </div>

          <!-- QUICK ACTION -->
          <div class="section-card">

            <div class="section-header">
              <div>
                <h3>Quản lý nhanh</h3>
                <p>Truy cập nhanh các chức năng quản trị</p>
              </div>
            </div>

            <div class="quick-grid">

              <router-link
                to="/admin/nguoi-dung"
                class="quick-item"
              >
                <div class="quick-icon blue">
                  ♙
                </div>

                <div>
                  <strong>Quản lý người dùng</strong>
                  <span>
                    Thêm, chỉnh sửa và quản lý tài khoản
                  </span>
                </div>

                <b>›</b>
              </router-link>

              <router-link
                to="/admin/lop-hoc"
                class="quick-item"
              >
                <div class="quick-icon purple">
                  ▣
                </div>

                <div>
                  <strong>Quản lý lớp học</strong>
                  <span>
                    Tạo và quản lý các lớp học
                  </span>
                </div>

                <b>›</b>
              </router-link>

              <router-link
                to="/admin/vai-tro"
                class="quick-item"
              >
                <div class="quick-icon green">
                  ♢
                </div>

                <div>
                  <strong>Quản lý vai trò</strong>
                  <span>
                    Quản lý quyền và vai trò người dùng
                  </span>
                </div>

                <b>›</b>
              </router-link>

            </div>

          </div>

        </template>

      </section>

    </main>

  </div>
</template>

<script>
import axios from 'axios'

export default {

  data() {
    return {
      loading: true,

      errorMessage: '',

      admin: null,

      thongKe: {
        tong_nguoi_dung: 0,
        tong_lop_hoc: 0,
        dang_hoat_dong: 0,
        tong_vai_tro: 0,
      },
    }
  },

  computed: {

    chuCaiDau() {
      if (!this.admin?.ho_ten) return 'A'

      return this.admin.ho_ten
        .trim()
        .charAt(0)
        .toUpperCase()
    },

  },

  mounted() {

    this.layThongTinDangNhap()

    this.getData()

  },

  methods: {

    layToken() {

      return (
        localStorage.getItem('auth_token') ||
        sessionStorage.getItem('auth_token')
      )

    },

    layThongTinDangNhap() {

      const user =
        localStorage.getItem('auth_user') ||
        sessionStorage.getItem('auth_user')

      if (user) {

        try {
          this.admin = JSON.parse(user)
        } catch {
          this.admin = null
        }

      }

    },

    async getData() {

      this.loading = true
      this.errorMessage = ''

      const token = this.layToken()

      if (!token) {

        window.location.assign('/dang-nhap')
        return

      }

      try {

        const response = await axios.get(
          '/api/admin/trang-chu/get-data',
          {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: 'application/json',
            },
          }
        )

        const data = response.data

        /*
         * Đổi các field này nếu AdminController
         * của bạn đang trả tên khác.
         */

        this.thongKe = {

          tong_nguoi_dung:
            data.tong_nguoi_dung ?? 0,

          tong_lop_hoc:
            data.tong_lop_hoc ?? 0,

          dang_hoat_dong:
            data.dang_hoat_dong ?? 0,

          tong_vai_tro:
            data.tong_vai_tro ?? 0,

        }

      } catch (error) {

        if (
          error.response?.status === 401 ||
          error.response?.status === 403
        ) {

          this.xoaPhien()

          window.location.assign('/dang-nhap')

          return
        }

        this.errorMessage =
          error.response?.data?.message ||
          'Không thể tải dữ liệu trang quản trị.'

      } finally {

        this.loading = false

      }

    },

    xoaPhien() {

      const keys = [
        'auth_token',
        'auth_user',
        'auth_expires_at',
      ]

      for (const storage of [
        localStorage,
        sessionStorage,
      ]) {

        keys.forEach(key => {
          storage.removeItem(key)
        })

      }

    },

    async dangXuat() {

      const token = this.layToken()

      try {

        if (token) {

          await axios.post(
            '/api/dang-xuat',
            {},
            {
              headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
              },
            }
          )

        }

      } catch (error) {

        console.error(error)

      } finally {

        this.xoaPhien()

        window.location.assign('/dang-nhap')

      }

    },

  },

}
</script>

<style scoped>

* {
  box-sizing: border-box;
}

.admin-layout {
  min-height: 100vh;
  display: flex;
  background: #f6f8fc;
  color: #1e293b;
  font-family: "Segoe UI", Arial, sans-serif;
}


/* SIDEBAR */

.sidebar {
  width: 255px;
  min-height: 100vh;
  background: #ffffff;
  border-right: 1px solid #e8edf5;
  padding: 24px 16px;

  display: flex;
  flex-direction: column;

  position: fixed;
  left: 0;
  top: 0;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;

  padding: 0 8px 25px;
  border-bottom: 1px solid #edf1f7;
}

.brand-icon {
  width: 42px;
  height: 42px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 11px;

  background: #315de5;
  color: white;

  font-size: 13px;
  font-weight: 700;
}

.brand h2 {
  font-size: 15px;
  margin: 0;
}

.brand span {
  display: block;

  margin-top: 2px;

  color: #8791a7;

  font-size: 8px;
  letter-spacing: 2px;
}


/* MENU */

.menu {
  margin-top: 25px;
  flex: 1;
}

.menu-title {
  padding: 0 12px;

  color: #9aa3b5;

  font-size: 9px;
  letter-spacing: 1.4px;

  margin-bottom: 10px;
}

.menu-item {
  height: 46px;

  padding: 0 13px;

  display: flex;
  align-items: center;
  gap: 12px;

  margin-bottom: 5px;

  border-radius: 8px;

  text-decoration: none;

  color: #697386;

  font-size: 13px;
  font-weight: 500;

  transition: .2s;
}

.menu-item:hover {
  background: #f1f5ff;
  color: #315de5;
}

.menu-item.router-link-active {
  background: #edf2ff;
  color: #315de5;
}

.icon {
  width: 21px;

  text-align: center;

  font-size: 18px;
}


/* LOGOUT */

.sidebar-bottom {
  padding-top: 15px;
  border-top: 1px solid #edf1f7;
}

.logout-btn {
  width: 100%;

  border: 0;
  background: transparent;

  padding: 12px;

  border-radius: 8px;

  color: #7b8497;

  display: flex;
  align-items: center;
  gap: 12px;

  cursor: pointer;

  font-size: 13px;
}

.logout-btn:hover {
  background: #fff1f1;
  color: #d04444;
}


/* MAIN */

.main {
  flex: 1;
  margin-left: 255px;
}


/* HEADER */

.header {
  height: 83px;

  background: white;

  border-bottom: 1px solid #e9edf4;

  padding: 0 35px;

  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header h1 {
  font-size: 19px;
  margin: 0;
}

.header p {
  margin: 5px 0 0;

  color: #8c95a6;

  font-size: 11px;
}


/* PROFILE */

.admin-profile {
  display: flex;
  align-items: center;
  gap: 11px;
}

.avatar {
  width: 39px;
  height: 39px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #eaf0ff;
  color: #315de5;

  font-weight: 700;
}

.admin-profile strong {
  display: block;

  font-size: 12px;
}

.admin-profile span {
  display: block;

  color: #929bad;

  margin-top: 2px;

  font-size: 10px;
}


/* CONTENT */

.content {
  padding: 30px 35px;
}


/* WELCOME */

.welcome {
  min-height: 150px;

  background:
    linear-gradient(
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

  box-shadow: 0 8px 25px
    rgba(49, 93, 229, .16);
}

.welcome-label {
  font-size: 9px;

  letter-spacing: 1.5px;

  color: #c8d5ff;

  margin: 0 0 10px;
}

.welcome h2 {
  font-size: 25px;

  margin: 0 0 8px;
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

  background: rgba(255,255,255,.12);

  font-size: 38px;
}


/* STAT */

.stats {
  margin-top: 24px;

  display: grid;

  grid-template-columns:
    repeat(4, minmax(0, 1fr));

  gap: 17px;
}

.stat-card {
  background: white;

  border: 1px solid #e9edf4;

  border-radius: 12px;

  padding: 20px;

  display: flex;
  gap: 15px;

  box-shadow:
    0 4px 13px rgba(29, 46, 83, .03);
}

.stat-icon {
  min-width: 44px;
  height: 44px;

  border-radius: 11px;

  display: flex;
  align-items: center;
  justify-content: center;

  font-weight: bold;
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

.orange {
  background: #fff4e6;
  color: #d78828;
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


/* SECTION */

.section-card {
  margin-top: 24px;

  background: white;

  border: 1px solid #e9edf4;

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


/* QUICK */

.quick-grid {
  margin-top: 20px;

  display: grid;

  grid-template-columns:
    repeat(3, 1fr);

  gap: 15px;
}

.quick-item {
  min-height: 83px;

  border: 1px solid #e8edf5;

  border-radius: 10px;

  padding: 15px;

  display: flex;
  align-items: center;
  gap: 12px;

  text-decoration: none;

  color: inherit;

  transition: .2s;
}

.quick-item:hover {
  border-color: #aebff1;

  transform: translateY(-2px);

  box-shadow:
    0 5px 15px rgba(33, 58, 120, .07);
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


/* MESSAGE */

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


/* RESPONSIVE */

@media (max-width: 1100px) {

  .stats {
    grid-template-columns:
      repeat(2, 1fr);
  }

  .quick-grid {
    grid-template-columns: 1fr;
  }

}

@media (max-width: 750px) {

  .sidebar {
    width: 70px;
    padding-inline: 10px;
  }

  .brand > div:not(.brand-icon),
  .menu-title,
  .menu-item:not(.router-link-active) {
  }

  .brand h2,
  .brand span,
  .menu-item {
    font-size: 0;
  }

  .menu-item {
    justify-content: center;
  }

  .icon {
    font-size: 19px;
  }

  .main {
    margin-left: 70px;
  }

  .header {
    padding: 0 18px;
  }

  .header > div:first-child p,
  .admin-profile > div:last-child {
    display: none;
  }

  .content {
    padding: 20px 18px;
  }

  .stats {
    grid-template-columns: 1fr;
  }

}
</style>