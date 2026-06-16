<template>
  <div class="login-wrapper">
    <!-- LEFT: building image -->
    <div class="image-side">
      <img src="../../../assets/images/Login/logo.jpg" alt="Tòa nhà kiến trúc hiện đại lúc hoàng hôn" />
      <div class="image-overlay"></div>
    </div>

    <!-- RIGHT: glass panel -->
    <div class="form-side">
      <div class="form-inner">
        <router-link to="/dang-nhap" class="back-link">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          Quay lại đăng nhập
        </router-link>

        <!-- Header -->
        <div class="header">
          <div class="logo-box">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2" />
              <path d="M7 11V7a5 5 0 0 1 9.9-1" />
            </svg>
          </div>
          <h1>Quên mật khẩu</h1>
          <p class="subtitle">
            {{ step === 1 ? "Nhập email để nhận mã xác thực" : "Nhập mã xác thực và mật khẩu mới" }}
          </p>
        </div>

        <!-- STEP 1: nhập email -->
        <form v-if="step === 1" class="login-form" @submit.prevent="handleSendCode">
          <div class="field">
            <label for="email">Email đăng ký</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2" />
                <path d="m22 7-10 5L2 7" />
              </svg>
              <input id="email" v-model="email" type="email" placeholder="Nhập email của bạn" />
            </div>
          </div>

          <p v-if="error" class="error-msg">{{ error }}</p>
          <p v-if="success" class="success-msg">{{ success }}</p>

          <button type="submit" class="login-btn">
            <span>Gửi mã xác thực</span>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
          </button>
        </form>

        <!-- STEP 2: nhập mã + mật khẩu mới -->
        <form v-else class="login-form" @submit.prevent="handleReset">
          <div class="field">
            <label for="code">Mã xác thực</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 12h6M9 16h6M9 8h6" />
                <rect x="4" y="3" width="16" height="18" rx="2" />
              </svg>
              <input id="code" v-model="code" type="text" placeholder="Nhập mã 6 chữ số" />
            </div>
          </div>

          <div class="field">
            <label for="newPassword">Mật khẩu mới</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
              <input
                id="newPassword"
                v-model="newPassword"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Nhập mật khẩu mới"
                autocomplete="new-password"
              />
              <button type="button" class="toggle-pass" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'">
                <svg v-if="showPassword" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
                <svg v-else viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
                  <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
                  <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
                  <path d="m2 2 20 20" />
                </svg>
              </button>
            </div>
          </div>

          <div class="field">
            <label for="confirmNewPassword">Xác nhận mật khẩu mới</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
              <input
                id="confirmNewPassword"
                v-model="confirmNewPassword"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Nhập lại mật khẩu mới"
                autocomplete="new-password"
              />
            </div>
          </div>

          <p v-if="error" class="error-msg">{{ error }}</p>
          <p v-if="success" class="success-msg">{{ success }}</p>

          <button type="submit" class="login-btn">
            <span>Đặt lại mật khẩu</span>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
          </button>
        </form>

        <p class="footer">© Hệ thống Đánh giá Rèn luyện Sinh viên</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import "../../../assets/css/auth.css";

const router = useRouter()

const step = ref(1)
const email = ref("")
const code = ref("")
const newPassword = ref("")
const confirmNewPassword = ref("")
const showPassword = ref(false)
const error = ref("")
const success = ref("")

function handleSendCode() {
  error.value = ""
  success.value = ""

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!email.value || !emailPattern.test(email.value)) {
    error.value = "Vui lòng nhập email hợp lệ."
    return
  }

  // Gửi mã xác thực (xử lý gọi API tại đây)
  console.log("[v0] Gửi mã xác thực đến:", email.value)
  success.value = "Đã gửi mã xác thực đến email của bạn."
  setTimeout(() => {
    success.value = ""
    step.value = 2
  }, 1200)
}

function handleReset() {
  error.value = ""
  success.value = ""

  if (!code.value || !newPassword.value || !confirmNewPassword.value) {
    error.value = "Vui lòng điền đầy đủ thông tin."
    return
  }

  if (newPassword.value.length < 6) {
    error.value = "Mật khẩu phải có ít nhất 6 ký tự."
    return
  }

  if (newPassword.value !== confirmNewPassword.value) {
    error.value = "Mật khẩu xác nhận không khớp."
    return
  }

  // Đặt lại mật khẩu (xử lý gọi API tại đây)
  console.log("[v0] Đặt lại mật khẩu cho:", email.value)
  success.value = "Đặt lại mật khẩu thành công! Đang chuyển đến trang đăng nhập..."
  setTimeout(() => router.push("/"), 1500)
}
</script>
