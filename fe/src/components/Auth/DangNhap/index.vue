
<template>
  <main class="login-page">

    <!-- =========================================
         BÊN TRÁI - GIỚI THIỆU
    ========================================== -->
    <section class="intro-panel">

      <div class="brand">
        <div class="brand-icon">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <rect x="3" y="4" width="18" height="13" rx="3" />
            <path d="M8 21h8M12 17v4M7 12l3-3 3 2 4-4" />
          </svg>
        </div>

        <div class="brand-name">
          Smart Classroom
          <span>MONITOR</span>
        </div>
      </div>

      <div class="intro-content">

        <span class="eyebrow">
          <span class="online-dot"></span>
          KẾT NỐI LỚP HỌC THÔNG MINH
        </span>

        <h1>
          Lớp học kết nối.
          <br />
          Học tập hiệu quả.
        </h1>

        <p class="intro-description">
          Một không gian để quản lý lớp học,
          theo dõi hoạt động và đồng hành
          cùng sinh viên.
        </p>

        <!-- MINH HỌA PHÒNG HỌC -->
        <div class="classroom-illustration">

          <div class="illustration-top">
            <span class="window-dots">● ● ●</span>
            <span>Không gian lớp học</span>
            <span>···</span>
          </div>

          <div class="illustration-body">

            <div class="illustration-sidebar">
              <span class="sidebar-logo"></span>
              <i></i>
              <i></i>
              <i></i>
              <i></i>
            </div>

            <div class="illustration-main">

              <div class="illustration-heading">
                <div>
                  <strong>Tổng quan lớp học</strong>
                  <small>Cùng nhau tiến bộ mỗi ngày</small>
                </div>

                <span class="live-label">
                  ● Trực tuyến
                </span>
              </div>

              <div class="screen-grid">
                <div
                  v-for="screen in 6"
                  :key="screen"
                  class="student-screen"
                >
                  <div class="screen-content">
                    <span></span>
                    <i></i>
                    <i></i>
                  </div>

                  <div class="screen-footer">
                    <span></span>
                    <i></i>
                  </div>
                </div>
              </div>

              <div class="illustration-bottom">
                <span></span>
                <span></span>
                <span></span>
              </div>

            </div>
          </div>

          <div class="floating-note">
            <span class="check-icon">✓</span>

            <div>
              <strong>Mọi hoạt động, một góc nhìn</strong>
              <small>Dễ dàng theo dõi và quản lý</small>
            </div>
          </div>

        </div>

        <div class="feature-list">
          <span>✓ Quản lý tập trung</span>
          <span>✓ Kết nối dễ dàng</span>
        </div>

      </div>

      <p class="intro-footer">
        SMART CLASSROOM MONITOR
        <span>·</span>
        Không gian học tập số
      </p>

    </section>

    <!-- =========================================
         BÊN PHẢI - ĐĂNG NHẬP
    ========================================== -->
    <section class="form-panel">

      <div class="platform-label">
        CỔNG TRUY CẬP HỆ THỐNG
      </div>

      <div class="login-content">

        <div class="welcome-icon">
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
          >
            <rect x="5" y="10" width="14" height="11" rx="3" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" />
          </svg>
        </div>

        <h2>Chào mừng trở lại!</h2>

        <p class="form-description">
          Đăng nhập để bắt đầu buổi học của bạn.
        </p>

        <!-- FORM -->
        <form
          class="login-form"
          @submit.prevent="dangNhap"
          :aria-busy="loading"
        >

          <!-- EMAIL -->
          <div class="field-group">
            <label for="login-email">
              Email
            </label>

            <div class="input-wrap">
              <svg
                class="field-icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
              >
                <rect x="3" y="5" width="18" height="14" rx="3" />
                <path d="m4 7 8 6 8-6" />
              </svg>

              <input
                id="login-email"
                v-model.trim="email"
                name="email"
                type="email"
                placeholder="Nhập email của bạn"
                autocomplete="username"
                :disabled="loading"
                required
              />
            </div>
          </div>

          <!-- PASSWORD -->
          <div class="field-group">
            <label for="login-password">
              Mật khẩu
            </label>

            <div class="input-wrap">
              <svg
                class="field-icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
              >
                <rect x="5" y="10" width="14" height="11" rx="3" />
                <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" />
              </svg>

              <input
                id="login-password"
                v-model="password"
                name="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Nhập mật khẩu"
                autocomplete="current-password"
                :disabled="loading"
                required
              />

              <button
                type="button"
                class="password-toggle"
                :aria-label="
                  showPassword
                    ? 'Ẩn mật khẩu'
                    : 'Hiện mật khẩu'
                "
                @click="showPassword = !showPassword"
              >
                <svg
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.7"
                >
                  <path
                    d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"
                  />
                  <circle cx="12" cy="12" r="3" />
                  <path
                    v-if="showPassword"
                    d="m3 3 18 18"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- OPTIONS -->
          <div class="form-options">

            <label class="remember-label">
              <input
                v-model="remember"
                type="checkbox"
                :disabled="loading"
              />
              Ghi nhớ đăng nhập
            </label>

            <button
              type="button"
              class="text-button"
              @click="showHelp = !showHelp"
            >
              Quên mật khẩu?
            </button>

          </div>

          <!-- HELP -->
          <p
            v-if="showHelp"
            class="help-message"
          >
            Vui lòng liên hệ quản trị viên của trường
            để được hỗ trợ cấp lại mật khẩu.
          </p>

          <!-- ERROR -->
          <div
            v-if="errorMessage"
            class="login-feedback error"
            role="alert"
          >
            {{ errorMessage }}
          </div>

          <!-- SUCCESS -->
          <div
            v-if="successMessage"
            class="login-feedback success"
            role="status"
          >
            {{ successMessage }}
          </div>

          <!-- BUTTON -->
          <button
            type="submit"
            class="login-button"
            :disabled="loading"
          >
            <span v-if="loading" class="spinner"></span>

            {{
              loading
                ? "Đang đăng nhập..."
                : "Đăng nhập"
            }}

            <span v-if="!loading">→</span>
          </button>

        </form>

        <div class="account-note">
          <span>ⓘ</span>

          <p>
            Sử dụng tài khoản do nhà trường cung cấp.
            <br />
            Liên hệ quản trị viên nếu bạn chưa có tài khoản.
          </p>
        </div>

      </div>

      <footer class="form-footer">
        © {{ currentYear }} Smart Classroom Monitor
      </footer>

    </section>

  </main>
</template>

<script setup>
import { computed, ref } from "vue"
import { useRouter } from "vue-router"
import axios from "axios"

/* ==========================================
   ROUTER
========================================== */
const router = useRouter()

/* ==========================================
   FORM STATE
========================================== */
const email = ref("")
const password = ref("")
const showPassword = ref(false)
const showHelp = ref(false)
const remember = ref(false)
const loading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")

const currentYear = computed(() =>
  new Date().getFullYear()
)

/* ==========================================
   ROLE ROUTES
========================================== */
const roleRoutes = {
  quan_tri_vien: "/admin/trang-chu",
  giang_vien: "/giang-vien/trang-chu",
  sinh_vien: "/sinh-vien/trang-chu",
}

/* ==========================================
   XÁC ĐỊNH VAI TRÒ
========================================== */
function getUserRole(user) {
  if (!user) return null

  const rawRole =
    user.vai_tro?.ten_vai_tro ??
    user.vaiTro?.ten_vai_tro ??
    user.role ??
    user.vai_tro ??
    null

  let role =
    typeof rawRole === "string"
      ? rawRole.trim().toLowerCase()
      : ""

  role = role
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/đ/g, "d")
    .replace(/[\s-]+/g, "_")

  const roleMap = {
    admin: "quan_tri_vien",
    administrator: "quan_tri_vien",
    quan_tri_vien: "quan_tri_vien",

    giang_vien: "giang_vien",
    lecturer: "giang_vien",
    teacher: "giang_vien",

    sinh_vien: "sinh_vien",
    student: "sinh_vien",
  }

  if (roleMap[role]) {
    return roleMap[role]
  }

  // Mapping theo database hiện tại
  const roleId = Number(
    user.vai_tro_id ??
    user.vai_tro?.id ??
    user.vaiTro?.id
  )

  if (roleId === 1) return "quan_tri_vien"
  if (roleId === 2) return "giang_vien"
  if (roleId === 3) return "sinh_vien"

  return null
}

/* ==========================================
   XÓA PHIÊN ĐĂNG NHẬP CŨ
========================================== */
function clearAuthStorage() {
  const keys = [
    "auth",
    "auth_token",
    "auth_user",
    "auth_expires_at",
    "access_token",
    "nguoi_dung",
  ]

  for (const storage of [
    localStorage,
    sessionStorage,
  ]) {
    for (const key of keys) {
      storage.removeItem(key)
    }
  }
}

/* ==========================================
   LƯU PHIÊN ĐĂNG NHẬP
========================================== */
function saveAuthSession(token, user, expiresAt) {
  const storage = remember.value
    ? localStorage
    : sessionStorage

  clearAuthStorage()

  storage.setItem("auth_token", token)
  storage.setItem("access_token", token)

  storage.setItem(
    "auth_user",
    JSON.stringify(user)
  )

  storage.setItem(
    "nguoi_dung",
    JSON.stringify(user)
  )

  if (expiresAt) {
    storage.setItem(
      "auth_expires_at",
      String(expiresAt)
    )
  }
}

/* ==========================================
   ĐĂNG NHẬP
========================================== */
async function dangNhap() {
  if (loading.value) return

  errorMessage.value = ""
  successMessage.value = ""

  if (!email.value.trim()) {
    errorMessage.value = "Vui lòng nhập email."
    return
  }

  if (!password.value) {
    errorMessage.value = "Vui lòng nhập mật khẩu."
    return
  }

  loading.value = true

  try {
    /* GỌI API */
    const response = await axios.post(
      "/api/dang-nhap",
      {
        email: email.value.trim(),
        password: password.value,
      },
      {
        headers: {
          Accept: "application/json",
        },
        timeout: 15000,
      }
    )

    const result = response.data

    const token = result?.access_token
    const user = result?.nguoi_dung

    /* KIỂM TRA API */
    if (!token || !user) {
      throw new Error(
        "Dữ liệu đăng nhập không hợp lệ."
      )
    }

    /* XÁC ĐỊNH VAI TRÒ */
    const role = getUserRole(user)

    const destination = roleRoutes[role]

    if (!destination) {
      throw new Error(
        "Tài khoản không có vai trò hợp lệ."
      )
    }

    /* LƯU TOKEN + USER */
    saveAuthSession(
      token,
      user,
      result?.expires_at
    )

    /* XÓA PASSWORD */
    password.value = ""

    /* THÔNG BÁO THÀNH CÔNG */
    successMessage.value =
      `Đăng nhập thành công! Xin chào ${
        user.ho_ten || "người dùng"
      }.`

    /* CHUYỂN TRANG */
    await router.replace(destination)

  } catch (error) {
    console.error("Lỗi đăng nhập:", error)

    const status = error.response?.status
    const data = error.response?.data

    if (status === 401) {
      errorMessage.value =
        data?.message ||
        "Email hoặc mật khẩu không chính xác."

    } else if (status === 422) {
      errorMessage.value =
        Object.values(data?.errors || {})
          .flat()
          .join(" ") ||
        "Vui lòng kiểm tra email và mật khẩu."

    } else if (status === 403) {
      errorMessage.value =
        data?.message ||
        "Tài khoản không có quyền truy cập."

    } else if (status === 429) {
      errorMessage.value =
        "Bạn đã thử đăng nhập quá nhiều lần. " +
        "Vui lòng thử lại sau."

    } else if (status >= 500) {
      errorMessage.value =
        "Máy chủ đang gặp lỗi. Vui lòng thử lại sau."

    } else if (error.code === "ECONNABORTED") {
      errorMessage.value =
        "Máy chủ phản hồi quá lâu. Vui lòng thử lại."

    } else {
      errorMessage.value =
        data?.message ||
        error.message ||
        "Không thể kết nối tới server API."
    }

  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ==========================================
   ROOT
========================================== */
.login-page {
  --blue: #315de5;

  display: grid;
  grid-template-columns: 1fr 1fr;

  min-height: 100vh;
  min-height: 100dvh;

  background: #fff;
  color: #19233c;

  font-family: "Segoe UI", Arial, sans-serif;
  line-height: 1.5;
}

.login-page *,
.login-page *::before,
.login-page *::after {
  box-sizing: border-box;
}

.login-page button,
.login-page input {
  font: inherit;
}

.login-page button {
  cursor: pointer;
}

.login-page button:focus-visible,
.login-page input:focus-visible {
  outline: 3px solid #91b0ff;
  outline-offset: 3px;
}

/* ==========================================
   INTRO PANEL
========================================== */
.intro-panel {
  display: flex;
  flex-direction: column;

  padding: 42px clamp(30px, 5vw, 80px) 26px;

  background:
    radial-gradient(
      ellipse at 90% 80%,
      #315ad0 0,
      transparent 55%
    ),
    linear-gradient(145deg, #101f52, #233fa2);

  color: #fff;
  overflow: hidden;
}

/* BRAND */
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  align-self: flex-start;
}

.brand-icon {
  width: 43px;
  height: 43px;

  display: grid;
  place-items: center;

  border-radius: 12px;
  background: rgba(255,255,255,.08);
  border: 1px solid rgba(255,255,255,.2);
}

.brand-icon svg {
  width: 27px;
  height: 27px;
}

.brand-name {
  font-size: 19px;
  font-weight: 650;
}

.brand-name span {
  display: block;
  font-size: 9px;
  letter-spacing: 4px;
  color: #b8c9fb;
  margin-top: 2px;
}

/* INTRO CONTENT */
.intro-content {
  width: 100%;
  max-width: 490px;

  margin: auto;
  padding: 64px 0;
}

.eyebrow {
  display: flex;
  align-items: center;
  gap: 9px;

  font-size: 10px;
  font-weight: 600;

  letter-spacing: 1.6px;
  color: #c8d5ff;
}

.online-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #81c5ff;
  box-shadow: 0 0 0 4px #81c5ff15;
}

.intro-content h1 {
  font-size: clamp(32px, 3.3vw, 49px);
  line-height: 1.23;
  letter-spacing: -1.5px;

  margin: 23px 0 18px;
  font-weight: 650;
}

.intro-description {
  max-width: 395px;
  color: #c2cfee;
  font-size: 14px;
  line-height: 1.85;
  margin: 0;
}

/* ==========================================
   CLASSROOM ILLUSTRATION
========================================== */
.classroom-illustration {
  position: relative;

  margin: 38px 12px 38px 0;

  background: #f4f7ff;
  color: #273457;

  border: 1px solid #ffffff30;
  border-radius: 13px;

  box-shadow: 0 22px 45px #08153c40;

  transform: rotate(-2deg);
}

.illustration-top {
  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 10px 15px;

  font-size: 9px;
  border-bottom: 1px solid #e4e9f4;
}

.window-dots {
  color: #b0c0e1;
  font-size: 8px;
  letter-spacing: 2px;
}

.illustration-body {
  display: flex;
  min-height: 190px;
}

.illustration-sidebar {
  width: 40px;

  display: flex;
  flex-direction: column;
  align-items: center;

  gap: 15px;
  padding-top: 16px;

  border-right: 1px solid #e4e9f4;
}

.sidebar-logo {
  width: 17px;
  height: 17px;
  background: #4268e6;
  border-radius: 5px;
  margin-bottom: 7px;
}

.illustration-sidebar i {
  width: 11px;
  height: 6px;
  border-radius: 2px;
  background: #d4ddf2;
}

.illustration-main {
  flex: 1;
  min-width: 0;
  padding: 17px;
}

.illustration-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.illustration-heading strong {
  display: block;
  font-size: 10px;
}

.illustration-heading small {
  display: block;
  font-size: 7px;
  color: #8591ac;
}

.live-label {
  padding: 4px 7px;
  background: #e0f5ee;
  color: #298367;
  border-radius: 10px;
  font-size: 7px;
  white-space: nowrap;
}

.screen-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 9px;
  margin-top: 14px;
}

.student-screen {
  padding: 6px;
  background: #fff;
  border: 1px solid #e2e8f5;
  border-radius: 5px;
}

.screen-content {
  height: 32px;
  padding: 6px;
  background: #eaf0fe;
  border-radius: 3px;
}

.screen-content span {
  display: block;
  width: 45%;
  height: 4px;
  background: #9eb6f1;
  border-radius: 2px;
  margin-bottom: 5px;
}

.screen-content i {
  display: block;
  width: 78%;
  height: 2px;
  background: #c8d5f1;
  margin-top: 3px;
}

.student-screen:nth-child(2n) .screen-content {
  background: #e9f5f2;
}

.screen-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 5px;
}

.screen-footer span {
  width: 40%;
  height: 3px;
  background: #d2daea;
  border-radius: 3px;
}

.screen-footer i {
  width: 4px;
  height: 4px;
  background: #62be9b;
  border-radius: 50%;
}

.illustration-bottom {
  display: flex;
  gap: 7px;
  margin-top: 12px;
}

.illustration-bottom span {
  width: 30px;
  height: 4px;
  background: #dde5f6;
  border-radius: 3px;
}

.floating-note {
  position: absolute;
  right: -17px;
  bottom: -18px;

  display: flex;
  align-items: center;
  gap: 9px;

  padding: 12px 16px;
  border-radius: 10px;
  background: #fff;

  box-shadow: 0 8px 24px #10245526;
  transform: rotate(2deg);
}

.check-icon {
  display: grid;
  place-items: center;

  width: 29px;
  height: 29px;
  border-radius: 50%;

  color: #289a76;
  background: #e6f6ef;
}

.floating-note strong {
  display: block;
  font-size: 9px;
}

.floating-note small {
  display: block;
  margin-top: 2px;
  font-size: 8px;
  color: #8791a5;
}

.feature-list {
  display: flex;
  flex-wrap: wrap;
  gap: 22px;

  padding-top: 8px;
  color: #d1dcff;
  font-size: 11px;
}

.intro-footer {
  margin: 0;
  font-size: 9px;
  color: #9eafe1;
  letter-spacing: .5px;
}

.intro-footer span {
  margin: 0 7px;
}

/* ==========================================
   FORM PANEL
========================================== */
.form-panel {
  display: flex;
  flex-direction: column;
  padding: 46px clamp(26px, 5vw, 80px) 26px;
}

.platform-label {
  text-align: right;
  font-size: 9px;
  letter-spacing: 1.7px;
  color: #8b93a5;
}

.login-content {
  width: 100%;
  max-width: 390px;
  margin: auto;
  padding: 55px 0;
}

.welcome-icon {
  display: grid;
  place-items: center;

  width: 50px;
  height: 50px;

  border-radius: 14px;
  background: #edf2ff;
  color: var(--blue);

  margin-bottom: 23px;
}

.welcome-icon svg {
  width: 25px;
  height: 25px;
}

.login-content h2 {
  margin: 0 0 10px;
  font-size: clamp(25px, 2.4vw, 32px);
  letter-spacing: -.8px;
  line-height: 1.3;
  font-weight: 700;
}

.form-description {
  margin: 0;
  color: #82899a;
  font-size: 13px;
}

.login-form {
  margin-top: 33px;
}

/* INPUT */
.field-group {
  margin-bottom: 20px;
}

.field-group label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 8px;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.field-icon {
  position: absolute;
  left: 15px;

  width: 18px;
  height: 18px;

  color: #919aaf;
  pointer-events: none;
}

.input-wrap input {
  width: 100%;
  height: 49px;

  padding: 0 46px 0 44px;

  border: 1px solid #e0e4ed;
  border-radius: 8px;

  color: #25314e;
  background: #fcfdff;

  font-size: 13px;
  transition: border-color .15s, box-shadow .15s;
}

.input-wrap input::placeholder {
  color: #9aa2b2;
}

.input-wrap input:focus {
  outline: none;
  border-color: #7694ef;
  box-shadow: 0 0 0 3px #315de512;
}

.password-toggle {
  position: absolute;
  right: 4px;

  width: 40px;
  height: 40px;

  display: grid;
  place-items: center;

  border: 0;
  border-radius: 5px;
  background: transparent;
  color: #929bad;
}

.password-toggle svg {
  width: 18px;
  height: 18px;
}

/* OPTIONS */
.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;

  margin: 2px 0 25px;
  font-size: 11px;
}

.remember-label {
  display: inline-flex;
  align-items: center;
  gap: 7px;

  color: #6c7588;
  cursor: pointer;
}

.remember-label input {
  width: 15px;
  height: 15px;
  margin: 0;
  accent-color: var(--blue);
}

.text-button {
  border: 0;
  background: transparent;
  color: var(--blue);
  padding: 5px 0;
  font-weight: 600;
}

.text-button:hover {
  text-decoration: underline;
}

/* BUTTON */
.login-button {
  width: 100%;
  height: 49px;

  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;

  border: 0;
  border-radius: 8px;

  background: var(--blue);
  color: #fff;

  font-size: 13px;
  font-weight: 600;

  box-shadow: 0 5px 12px #315de522;
  transition: background .15s;
}

.login-button:hover:not(:disabled) {
  background: #244bc7;
}

.login-button:disabled {
  opacity: .65;
  cursor: wait;
}

.login-button span:not(.spinner) {
  font-size: 19px;
}

/* LOADING */
.spinner {
  width: 17px;
  height: 17px;
  border: 2px solid rgba(255,255,255,.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* FEEDBACK */
.login-feedback {
  margin: 0 0 16px;
  padding: 12px;
  border-radius: 8px;
  font-size: 12px;
}

.login-feedback.error {
  background: #fff0f0;
  color: #a52232;
}

.login-feedback.success {
  background: #eaf8ef;
  color: #23683e;
}

.help-message {
  margin: -8px 0 20px;
  padding: 12px;
  background: #f0f4ff;
  color: #45609e;
  border-radius: 6px;
  font-size: 12px;
}

/* FOOTER */
.account-note {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  gap: 9px;

  margin-top: 30px;
  padding-top: 23px;

  border-top: 1px solid #edf0f5;
  color: #9199aa;
}

.account-note > span {
  font-size: 16px;
}

.account-note p {
  margin: 0;
  font-size: 11px;
  line-height: 1.9;
}

.form-footer {
  color: #a1a8b6;
  text-align: center;
  font-size: 10px;
}

/* ==========================================
   RESPONSIVE
========================================== */
@media (max-width: 900px) {
  .intro-panel,
  .form-panel {
    padding-inline: 28px;
  }

  .feature-list {
    gap: 12px;
  }
}

@media (max-width: 700px) {
  .login-page {
    grid-template-columns: 1fr;
  }

  .intro-panel {
    padding: 23px 26px;
  }

  .brand-name {
    font-size: 17px;
  }

  .intro-content,
  .intro-footer {
    display: none;
  }

  .form-panel {
    padding: 24px 26px;
  }

  .platform-label {
    text-align: center;
    font-size: 8px;
  }

  .login-content {
    padding: 36px 0 44px;
  }

  .welcome-icon {
    margin-bottom: 20px;
  }

  .form-footer {
    padding-bottom: 4px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .login-page * {
    transition: none;
    animation: none;
  }
}
</style>
