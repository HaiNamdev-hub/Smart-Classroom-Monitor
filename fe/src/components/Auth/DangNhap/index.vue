<template>
  <div class="login-wrapper">
    <!-- LEFT: building image -->
    <div class="image-side">
      <img src="../../../assets/images/Login/logo.jpg" alt="Tòa nhà kiến trúc hiện đại lúc hoàng hôn" />
      <div class="image-overlay"></div>
    </div>

    <!-- RIGHT: glass login panel -->
    <div class="form-side">
      <div class="form-inner">
        <!-- Header -->
        <div class="header">
          <div class="logo-box">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
              <path d="M6 12v5c3 3 9 3 12 0v-5" />
            </svg>
          </div>
          <h1>Đăng nhập</h1>
          <p class="subtitle">Cổng thông tin đánh giá rèn luyện sinh viên</p>
        </div>

        <form class="login-form" @submit.prevent="dangNhap">
          <!-- Email -->
          <div class="field">
            <label for="email">Email hoặc mã sinh viên</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2" />
                <path d="m22 7-10 5L2 7" />
              </svg>
              <input id="email" v-model="email" type="text" placeholder="Nhập email của bạn" autocomplete="username" />
            </div>
          </div>

          <!-- Password -->
          <div class="field">
            <label for="password">Mật khẩu</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
              <input id="password" v-model="password" :type="showPassword ? 'text' : 'password'"
                placeholder="Nhập mật khẩu" autocomplete="current-password" />
              <button type="button" class="toggle-pass" @click="showPassword = !showPassword"
                :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'">
                <svg v-if="showPassword" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <svg v-else viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                  <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                  <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                  <path d="m2 2 20 20" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Captcha -->
          <div class="field">
            <label for="captcha">Mã xác nhận</label>
            <div class="captcha-row">
              <div class="input-wrap captcha-input">
                <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 12h6M9 16h6M9 8h6" />
                  <rect x="4" y="3" width="16" height="18" rx="2" />
                </svg>
                <input class="captcha-text" id="captcha" v-model="captcha" type="text" placeholder="Nhập mã" @input="captcha = captcha.toUpperCase()"/>
              </div>
              <button type="button" class="captcha-box" @click="refreshCaptcha" title="Bấm để làm mới mã">
                {{ captchaCode }}
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                  stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
                  <path d="M21 3v5h-5M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
                  <path d="M8 16H3v5" />
                </svg>
              </button>
            </div>
          </div>

          <div class="actions-row">
            <router-link to="/quen-mat-khau" class="forgot-link">Quên mật khẩu?</router-link>
          </div>

          <p v-if="error" class="error-msg">{{ error }}</p>

          <button type="submit" class="login-btn">
            <span>Đăng nhập</span>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
              stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
          </button>
        </form>

        <p class="footer">© Hệ thống Đánh giá Rèn luyện Sinh viên</p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios"
import "../../../assets/css/auth.css";

export default {
  data() {
    return {
      email: "",
      password: "",
      showPassword: false,
      captcha: "",
      captchaCode: this.generateCaptcha(),
      error: "",
    }
  },
  mounted() {
    // Nếu đã có token thì redirect luôn
    const token = sessionStorage.getItem("auth_token")
    if (token) {
      const user = JSON.parse(sessionStorage.getItem("auth_user") || "{}")
      const role = user.vai_tro_id
      if (role == 1) {
        this.$router.replace("/admin")
        return
      }
      else if (role == 2) {
        this.$router.replace("/staff")
        return
      }
      else {
        this.$router.replace("/")
        return
      }
    }
  },
  methods: {
    generateCaptcha() {
      const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"
      let code = ""
      for (let i = 0; i < 4; i++) {
        code += chars[Math.floor(Math.random() * chars.length)]
      }
      return code
    },

    refreshCaptcha() {
      this.captchaCode = this.generateCaptcha()
      this.captcha = ""
    },

    dangNhap() {
      this.error = ""

      // Validation
      if (!this.email) {
        this.error = "Vui lòng nhập email hoặc mã sinh viên."
        return
      }

      if (!this.password) {
        this.error = "Vui lòng nhập mật khẩu."
        return
      }

      if (!this.captcha) {
        this.error = "Vui lòng nhập mã xác nhận."
        return
      }

      if (this.captcha.toUpperCase() !== this.captchaCode) {
        this.error = "Mã xác nhận không chính xác."
        this.refreshCaptcha()
        return
      }

      // Gọi API đăng nhập
      axios
        .post("http://127.0.0.1:8000/api/dang-nhap", {
          email: this.email,
          password: this.password,
        })
        .then((res) => {
          if (!res.data.status) {
            this.error = res.data.message || "Email hoặc mật khẩu không chính xác."
            this.refreshCaptcha()
            return
          }

          sessionStorage.setItem("auth_token", res.data.token);
sessionStorage.setItem("auth_user", JSON.stringify(res.data.user));

          if (this.$toast) {
            this.$toast.success("Đăng nhập thành công")
          }

          const role = res.data.user.vai_tro_id

          setTimeout(() => {
            if (role == 1) {
              this.$router.replace("/admin/trang-chu")
            } else if (role == 2) {
              this.$router.replace("/staff")
            } else {
              this.$router.replace("/")
            }
          }, 500)
        })
        .catch((err) => {
          if (err.response && err.response.data && err.response.data.message) {
            this.error = err.response.data.message
          } else {
            this.error = "Lỗi kết nối tới server API"
          }
          this.refreshCaptcha()
        })
    },
  },
}
</script>
