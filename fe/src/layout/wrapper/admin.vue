<script>
import api, {
  clearAuth,
  apiError,
} from "../../services/api"

import AdminIcon from "../../components/Admin/AdminIcon.vue"

export default {
  name: "AdminLayout",

  components: {
    AdminIcon,
  },

  data() {
    return {
      user: null,
      loading: true,
      error: "",
      menuOpen: false,
      loggingOut: false,
    }
  },

  computed: {
    pages() {
      return [
        {
          path: "/admin/trang-chu",
          label: "Tổng quan",
          icon: "overview",
        },
        {
          path: "/admin/ql-nguoi-dung",
          label: "Quản lý người dùng",
          icon: "users",
        },
        {
          path: "/admin/ql-lop-hoc",
          label: "Quản lý lớp học",
          icon: "classes",
        },
        {
          path: "/admin/ql-vai-tro",
          label: "Vai trò hệ thống",
          icon: "roles",
        },
      ]
    },

    currentPage() {
      return (
        this.pages.find(
          (page) => page.path === this.$route.path
        ) || this.pages[0]
      )
    },
  },

  mounted() {
    this.loadUser()
  },

  methods: {
    navigate(path) {
      this.menuOpen = false

      if (this.$route.path !== path) {
        this.$router.push(path)
      }
    },

    async loadUser() {
      this.loading = true
      this.error = ""

      try {
        const { data } = await api.get("/thong-tin")

        const user =
          data?.nguoi_dung ??
          data?.data ??
          data

        if (!user) {
          this.error =
            "Không lấy được thông tin tài khoản."
          return
        }

        const role =
          user?.vai_tro?.ten_vai_tro ||
          user?.vaiTro?.ten_vai_tro ||
          (
            Number(user?.vai_tro_id) === 1
              ? "quan_tri_vien"
              : Number(user?.vai_tro_id) === 2
                ? "giang_vien"
                : Number(user?.vai_tro_id) === 3
                  ? "sinh_vien"
                  : null
          )

        if (role !== "quan_tri_vien") {
          this.error =
            "Tài khoản này không có quyền truy cập trang quản trị."
          return
        }

        this.user = user
      } catch (error) {
        console.error(
          "Lỗi lấy thông tin Admin:",
          error
        )

        this.error = apiError(error)
      } finally {
        this.loading = false
      }
    },

    async logout() {
      if (this.loggingOut) return

      this.loggingOut = true

      try {
        await api.post("/dang-xuat")
      } catch (error) {
        console.error(
          "Lỗi đăng xuất:",
          error
        )
      } finally {
        clearAuth()

        localStorage.removeItem("access_token")
        localStorage.removeItem("nguoi_dung")

        sessionStorage.removeItem("access_token")
        sessionStorage.removeItem("nguoi_dung")

        this.loggingOut = false

        this.$router.replace("/dang-nhap")
      }
    },
  },
}
</script>

<template>
  <div class="admin-app">

    <!-- ==============================
         MOBILE BACKDROP
    =============================== -->
    <button
      v-if="menuOpen"
      class="sidebar-backdrop"
      aria-label="Đóng menu"
      @click="menuOpen = false"
    ></button>


    <!-- ==============================
         SIDEBAR
    =============================== -->
    <aside
      class="admin-sidebar"
      :class="{ open: menuOpen }"
    >

      <!-- Logo -->
      <a
        class="admin-brand"
        href="/admin/trang-chu"
        @click.prevent="
          navigate('/admin/trang-chu')
        "
      >
        <span class="brand-icon">
          <AdminIcon name="classes" />
        </span>

        <span>
          Smart Classroom
          <small>MONITOR</small>
        </span>
      </a>


      <!-- Workspace -->
      <div class="workspace">
        <span class="avatar">
          A
        </span>

        <div>
          Không gian quản trị

          <small>
            Administrator workspace
          </small>
        </div>

        <i class="dot"></i>
      </div>


      <!-- Navigation -->
      <p class="nav-caption">
        QUẢN LÝ HỆ THỐNG
      </p>

      <nav aria-label="Điều hướng admin">

        <a
          v-for="page in pages"
          :key="page.path"
          :href="page.path"
          :class="{
            active:
              $route.path === page.path,
          }"
          @click.prevent="
            navigate(page.path)
          "
        >
          <AdminIcon
            :name="page.icon"
          />

          {{ page.label }}

          <span
            v-if="
              $route.path === page.path
            "
            class="nav-dot"
          ></span>
        </a>

      </nav>


      <!-- Bottom -->
      <div class="sidebar-bottom">

        <div class="sidebar-note">
          <span>✧</span>

          <b>
            Một góc nhìn, mọi lớp học
          </b>

          <p>
            Kết nối và quản lý không gian
            học tập hiệu quả hơn mỗi ngày.
          </p>
        </div>


        <!-- User -->
        <div
          v-if="user"
          class="profile"
        >
          <span class="avatar">
            {{
              user?.ho_ten
                ?.slice(0, 1)
                ?.toUpperCase() || "A"
            }}
          </span>

          <div>
            <b>
              {{
                user?.ho_ten ||
                "Quản trị viên"
              }}
            </b>

            <small>
              Quản trị hệ thống
            </small>
          </div>
        </div>


        <!-- Logout -->
        <button
          class="logout-btn"
          :disabled="loggingOut"
          @click="logout"
        >
          <AdminIcon name="logout" />

          {{
            loggingOut
              ? "Đang đăng xuất…"
              : "Đăng xuất"
          }}
        </button>

      </div>
    </aside>


    <!-- ==============================
         MAIN
    =============================== -->
    <div class="admin-body">

      <!-- Header -->
      <header class="admin-topbar">

        <div>
          <button
            class="menu-toggle icon-btn"
            aria-label="Mở menu"
            :aria-expanded="menuOpen"
            @click="
              menuOpen = !menuOpen
            "
          >
            <AdminIcon name="menu" />
          </button>

          <span class="breadcrumb">
            Không gian quản trị
            <span>/</span>
          </span>

          <b>
            {{ currentPage.label }}
          </b>
        </div>


        <span class="header-badge">
          <i class="dot"></i>

          Smart Classroom Monitor
        </span>

      </header>


      <!-- ==============================
           CONTENT
      =============================== -->
      <main class="admin-content">

        <!-- Loading -->
        <div
          v-if="loading"
          class="state-panel"
          role="status"
        >
          Đang xác thực tài khoản…
        </div>


        <!-- Error -->
        <div
          v-else-if="error"
          class="alert error"
          role="alert"
        >
          {{ error }}

          <button
            class="text-btn"
            @click="loadUser"
          >
            Thử lại
          </button>
        </div>


        <!--
          QUAN TRỌNG:
          Trang con của Vue Router
          được hiển thị tại đây.
        -->
        <router-view
          v-else-if="user"
          :current-user="user"
        />

      </main>


      <!-- Footer -->
      <footer class="admin-footer">

        <span>
          © {{ new Date().getFullYear() }}
          Smart Classroom Monitor
        </span>

        <span>
          Không gian học tập kết nối
        </span>

      </footer>

    </div>
  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

/* ================================
   APP
================================ */

.admin-app {
  min-height: 100vh;
  background: #f6f8fc;
  color: #1e293b;
  font-family: "Segoe UI", Arial, sans-serif;
}


/* ================================
   SIDEBAR
================================ */

.admin-sidebar {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;

  width: 255px;
  min-height: 100vh;

  padding: 24px 16px;

  display: flex;
  flex-direction: column;

  background: #ffffff;
  border-right: 1px solid #e8edf5;

  z-index: 100;
}


/* Logo */

.admin-brand {
  display: flex;
  align-items: center;
  gap: 12px;

  padding: 0 8px 24px;

  border-bottom: 1px solid #edf1f7;

  color: #1e293b;
  text-decoration: none;

  font-weight: 700;
}

.brand-icon {
  width: 42px;
  height: 42px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 11px;

  background: #315de5;
  color: #ffffff;
}

.admin-brand > span:last-child {
  display: flex;
  flex-direction: column;

  font-size: 15px;
}

.admin-brand small {
  margin-top: 3px;

  color: #8791a7;

  font-size: 8px;
  font-weight: 500;

  letter-spacing: 2px;
}


/* ================================
   WORKSPACE
================================ */

.workspace {
  display: flex;
  align-items: center;
  gap: 10px;

  margin-top: 20px;
  padding: 12px;

  background: #f7f9fd;

  border-radius: 10px;

  font-size: 12px;
  font-weight: 600;
}

.workspace small {
  display: block;

  margin-top: 3px;

  color: #929bad;

  font-size: 9px;
  font-weight: 400;
}

.avatar {
  width: 36px;
  height: 36px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 50%;

  background: #eaf0ff;
  color: #315de5;

  font-weight: 700;
}

.dot {
  width: 7px;
  height: 7px;

  display: inline-block;

  margin-left: auto;

  border-radius: 50%;

  background: #22c55e;
}


/* ================================
   NAVIGATION
================================ */

.nav-caption {
  margin: 25px 12px 10px;

  color: #9aa3b5;

  font-size: 9px;

  letter-spacing: 1.4px;
}

.admin-sidebar nav {
  display: flex;
  flex-direction: column;

  gap: 5px;
}

.admin-sidebar nav a {
  min-height: 46px;

  display: flex;
  align-items: center;

  gap: 12px;

  padding: 0 13px;

  position: relative;

  border-radius: 8px;

  color: #697386;

  text-decoration: none;

  font-size: 13px;
  font-weight: 500;

  transition: 0.2s;
}

.admin-sidebar nav a:hover {
  background: #f1f5ff;
  color: #315de5;
}

.admin-sidebar nav a.active {
  background: #edf2ff;
  color: #315de5;
}

.admin-sidebar nav svg {
  flex-shrink: 0;
}

.nav-dot {
  width: 6px;
  height: 6px;

  position: absolute;

  right: 12px;

  border-radius: 50%;

  background: #315de5;
}


/* ================================
   SIDEBAR BOTTOM
================================ */

.sidebar-bottom {
  margin-top: auto;

  padding-top: 15px;

  border-top: 1px solid #edf1f7;
}

.sidebar-note {
  margin-bottom: 15px;
  padding: 13px;

  border-radius: 10px;

  background: #f7f9fd;

  font-size: 10px;
}

.sidebar-note b {
  display: block;

  margin-top: 5px;

  font-size: 10px;
}

.sidebar-note p {
  margin: 5px 0 0;

  color: #929bad;

  font-size: 9px;
  line-height: 1.5;
}

.profile {
  display: flex;
  align-items: center;

  gap: 10px;

  padding: 10px 5px;
}

.profile b {
  display: block;

  font-size: 11px;
}

.profile small {
  display: block;

  margin-top: 2px;

  color: #929bad;

  font-size: 9px;
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

  font-size: 12px;
}

.logout-btn:hover {
  background: #fff1f1;
  color: #d04444;
}

.logout-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}


/* ================================
   MAIN BODY
================================ */

.admin-body {
  min-height: 100vh;

  margin-left: 255px;

  display: flex;
  flex-direction: column;
}


/* ================================
   TOPBAR
================================ */

.admin-topbar {
  height: 70px;

  padding: 0 35px;

  background: #ffffff;

  border-bottom: 1px solid #e9edf4;

  display: flex;
  align-items: center;
  justify-content: space-between;
}

.admin-topbar > div {
  display: flex;
  align-items: center;

  gap: 7px;

  font-size: 12px;
}

.breadcrumb {
  color: #8c95a6;
}

.breadcrumb span {
  margin-left: 5px;

  color: #c2c8d2;
}

.header-badge {
  display: flex;
  align-items: center;

  gap: 7px;

  padding: 7px 11px;

  background: #f6f8fc;

  border-radius: 20px;

  color: #697386;

  font-size: 10px;
}

.header-badge .dot {
  margin: 0;
}


/* ================================
   BUTTON
================================ */

.menu-toggle {
  display: none;
}

.icon-btn {
  border: 0;

  background: transparent;

  cursor: pointer;
}


/* ================================
   CONTENT
================================ */

.admin-content {
  flex: 1;

  padding: 30px 35px;

  background: #f6f8fc;
}

.state-panel {
  padding: 20px;

  background: #ffffff;

  border-radius: 10px;
}

.alert.error {
  padding: 15px;

  border-radius: 10px;

  background: #fff0f1;
  color: #b33140;
}

.text-btn {
  margin-left: 10px;

  border: 0;

  background: transparent;

  color: #315de5;

  cursor: pointer;

  font-weight: 600;
}


/* ================================
   FOOTER
================================ */

.admin-footer {
  padding: 18px 35px;

  border-top: 1px solid #e9edf4;

  background: #ffffff;

  display: flex;
  justify-content: space-between;

  color: #929bad;

  font-size: 9px;
}


/* ================================
   MOBILE BACKDROP
================================ */

.sidebar-backdrop {
  display: none;
}


/* ================================
   RESPONSIVE
================================ */

@media (max-width: 800px) {

  .admin-sidebar {
    transform: translateX(-100%);

    transition: transform 0.25s ease;
  }

  .admin-sidebar.open {
    transform: translateX(0);
  }

  .admin-body {
    margin-left: 0;
  }

  .menu-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .sidebar-backdrop {
    display: block;

    position: fixed;
    inset: 0;

    z-index: 90;

    border: 0;

    background: rgba(
      15,
      23,
      42,
      0.35
    );
  }

  .admin-topbar {
    padding: 0 18px;
  }

  .admin-content {
    padding: 20px 18px;
  }

  .header-badge {
    display: none;
  }

  .admin-footer {
    padding: 15px 18px;
  }
}
</style>