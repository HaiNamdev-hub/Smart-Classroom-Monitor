<template>
  <div class="login-wrapper">
    <!-- LEFT: building image -->
    <div class="image-side">
      <img src="../../../assets/images/Login/logo.jpg" alt="Tòa nhà kiến trúc hiện đại lúc hoàng hôn" />
      <div class="image-overlay"></div>
    </div>

    <!-- RIGHT: glass register panel -->
    <div class="form-side">
      <div class="form-inner">
        <!-- Header -->
        <div class="header">
          <div class="logo-box">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
              <circle cx="9" cy="7" r="4" />
              <path d="M19 8v6M22 11h-6" />
            </svg>
          </div>
          <h1>Đăng ký</h1>
          <p class="subtitle">Tạo tài khoản sinh viên mới</p>
        </div>

        <form class="login-form" @submit.prevent="handleRegister">
          <!-- Họ và tên -->
          <div class="field">
            <label for="fullname">Họ và tên</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
              <input id="fullname" v-model="fullname" type="text" placeholder="Nhập họ và tên" />
            </div>
          </div>

          <!-- Mã sinh viên -->
          <div class="field">
            <label for="studentId">Mã sinh viên</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="16" rx="2" />
                <path d="M7 8h4M7 12h4M7 16h2" />
                <circle cx="16.5" cy="10" r="2" />
              </svg>
              <input id="studentId" v-model="studentId" type="text" placeholder="Nhập mã sinh viên" />
            </div>
          </div>

          <!-- Email -->
          <div class="field">
            <label for="email">Email</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="2" />
                <path d="m22 7-10 5L2 7" />
              </svg>
              <input id="email" v-model="email" type="email" placeholder="Nhập email của bạn" />
            </div>
          </div>

          <!-- Password -->
          <div class="field">
            <label for="password">Mật khẩu</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
              <input
                id="password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Tạo mật khẩu"
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

          <!-- Xác nhận mật khẩu -->
          <div class="field">
            <label for="confirmPassword">Xác nhận mật khẩu</label>
            <div class="input-wrap">
              <svg class="input-icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
              </svg>
              <input
                id="confirmPassword"
                v-model="confirmPassword"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Nhập lại mật khẩu"
                autocomplete="new-password"
              />
            </div>
          </div>

          <p v-if="error" class="error-msg">{{ error }}</p>
          <p v-if="success" class="success-msg">{{ success }}</p>

          <button type="submit" class="login-btn">
            <span>Đăng ký</span>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
          </button>

          <p class="register-row">
            Đã có tài khoản?
            <router-link to="/dang-nhap" class="register-link">Đăng nhập</router-link>
          </p>
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

const fullname = ref("")
const studentId = ref("")
const email = ref("")
const password = ref("")
const confirmPassword = ref("")
const showPassword = ref(false)
const error = ref("")
const success = ref("")

function handleRegister() {
  error.value = ""
  success.value = ""

  if (!fullname.value || !studentId.value || !email.value || !password.value || !confirmPassword.value) {
    error.value = "Vui lòng điền đầy đủ thông tin."
    return
  }

  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailPattern.test(email.value)) {
    error.value = "Email không hợp lệ."
    return
  }

  if (password.value.length < 6) {
    error.value = "Mật khẩu phải có ít nhất 6 ký tự."
    return
  }

  if (password.value !== confirmPassword.value) {
    error.value = "Mật khẩu xác nhận không khớp."
    return
  }

  // Đăng ký thành công (xử lý gọi API tại đây)
  console.log("[v0] Đăng ký:", { fullname: fullname.value, studentId: studentId.value, email: email.value })
  success.value = "Đăng ký thành công! Đang chuyển đến trang đăng nhập..."
  setTimeout(() => router.push("/"), 1500)
}
</script>

