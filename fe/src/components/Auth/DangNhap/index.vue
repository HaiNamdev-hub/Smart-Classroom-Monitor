
<template>
    <main class="login-page">
        <section class="intro-panel" aria-labelledby="intro-title">
            <a class="brand" href="#" aria-label="Smart Classroom Monitor">
                <span class="brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="3" y="4" width="18" height="13" rx="3" />
                        <path d="M8 21h8M12 17v4M7 12l3-3 3 2 4-4" />
                    </svg>
                </span>
                <span>Smart Classroom<span class="brand-caption">MONITOR</span></span>
            </a>

            <div class="intro-content">
                <span class="eyebrow"><span></span> KẾT NỐI LỚP HỌC THÔNG MINH</span>
                <h1 id="intro-title">Lớp học kết nối.<br />Học tập hiệu quả.</h1>
                <p>Một không gian để quản lý lớp học, theo dõi hoạt động và đồng hành cùng sinh viên.</p>

                <div class="classroom-illustration" aria-hidden="true">
                    <div class="illustration-top"><span class="window-dots">● ● ●</span><span>Không gian lớp
                            học</span><span>···</span></div>
                    <div class="illustration-body">
                        <div class="illustration-sidebar"><span class="sidebar-logo"></span><i></i><i></i><i></i><i></i>
                        </div>
                        <div class="illustration-main">
                            <div class="illustration-heading">
                                <div><b>Tổng quan lớp học</b><span>Cùng nhau tiến bộ mỗi ngày</span></div><span
                                    class="live-label">● Trực tuyến</span>
                            </div>
                            <div class="screen-grid">
                                <div v-for="screen in 6" :key="screen" class="student-screen">
                                    <div class="screen-content"><span></span><i></i><i></i></div>
                                    <div class="screen-footer"><span></span><i></i></div>
                                </div>
                            </div>
                            <div class="illustration-bottom"><span></span><span></span><span></span></div>
                        </div>
                    </div>
                    <div class="floating-note"><span class="check-icon">✓</span>
                        <div><b>Mọi hoạt động, một góc nhìn</b><span>Dễ dàng theo dõi và quản lý</span></div>
                    </div>
                </div>

                <div class="feature-list"><span><i>✓</i> Quản lý tập trung</span><span><i>✓</i> Kết nối dễ dàng</span>
                </div>
            </div>
            <p class="intro-footer">SMART CLASSROOM MONITOR <span>·</span> Không gian học tập số</p>
        </section>

        <section class="form-panel" aria-labelledby="login-title">
            <span class="platform-label">CỔNG TRUY CẬP HỆ THỐNG</span>
            <div class="login-content">
                <div class="welcome-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <rect x="5" y="10" width="14" height="11" rx="3" />
                        <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" />
                    </svg>
                </div>
                <h2 id="login-title">Chào mừng trở lại!</h2>
                <p class="form-description">Đăng nhập để bắt đầu buổi học của bạn.</p>

                <form class="login-form" @submit.prevent="dangNhap" :aria-busy="loading">
                    <div class="field-group">
                        <label for="login-email">Email</label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.6" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="3" />
                                <path d="m4 7 8 6 8-6" />
                            </svg>
                            <input id="login-email" v-model="email" :disabled="loading" name="email" type="email" placeholder="Nhập email của bạn"
                                autocomplete="username" required />
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="login-password">Mật khẩu</label>
                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.6" aria-hidden="true">
                                <rect x="5" y="10" width="14" height="11" rx="3" />
                                <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" />
                            </svg>
                            <input id="login-password" v-model="password" :disabled="loading" name="password" :type="showPassword ? 'text' : 'password'"
                                placeholder="Nhập mật khẩu" autocomplete="current-password" required />
                            <button class="password-toggle" type="button"
                                :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
                                :aria-pressed="showPassword" @click="showPassword = !showPassword">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                                    aria-hidden="true">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                    <path v-if="showPassword" d="m3 3 18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-options">
                        <label class="remember-label"><input v-model="remember" :disabled="loading" type="checkbox" name="remember" /> Ghi nhớ đăng
                            nhập</label>
                        <button class="text-button" type="button" :aria-expanded="showHelp" aria-controls="account-help"
                            @click="showHelp = !showHelp">Quên mật khẩu?</button>
                    </div>
                    <p v-if="showHelp" id="account-help" class="help-message" role="status">Vui lòng liên hệ quản trị
                        viên của trường để được hỗ trợ cấp lại mật khẩu.</p>
                    <p v-if="errorMessage" class="login-feedback error" role="alert">{{ errorMessage }}</p>
                    <p v-if="successMessage" class="login-feedback success" role="status">{{ successMessage }}</p>
                    <button class="login-button" type="submit" :disabled="loading">{{ loading ? 'Đang đăng nhập...' : 'Đăng nhập' }} <span aria-hidden="true">→</span></button>
                </form>

                <div class="account-note"><span aria-hidden="true">ⓘ</span>
                    <p>Sử dụng tài khoản do nhà trường cung cấp.<br />Liên hệ quản trị viên nếu bạn chưa có tài khoản.
                    </p>
                </div>
            </div>
            <footer class="form-footer">© {{ new Date().getFullYear() }} Smart Classroom Monitor</footer>
        </section>
    </main>
</template>

<style scoped>
.login-feedback { margin: 0; padding: 12px; border-radius: 8px; font-size: 14px; }
.login-feedback.error { background: #fff0f0; color: #a52232; }
.login-feedback.success { background: #eaf8ef; color: #23683e; }
.login-button:disabled { opacity: .65; cursor: wait; }

:global(body) {
    margin: 0;
}

.login-page {
    --blue: #315de5;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #19233c;
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
    min-height: 100dvh;
    background: #fff;
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
.login-page a:focus-visible,
.login-page input:focus-visible {
    outline: 3px solid #91b0ff;
    outline-offset: 4px;
}

.intro-panel {
    padding: 42px clamp(30px, 5vw, 80px) 26px;
    background: radial-gradient(ellipse at 90% 80%, #315ad0 0, transparent 55%), linear-gradient(145deg, #101f52, #233fa2);
    color: white;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.brand {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    color: inherit;
    text-decoration: none;
    font-size: 19px;
    font-weight: 650;
    align-self: flex-start;
}

.brand-icon {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    background: #ffffff14;
    border: 1px solid #ffffff30;
    border-radius: 12px;
}

.brand-icon svg {
    width: 27px;
    height: 27px;
}

.brand-caption {
    display: block;
    font-size: 9px;
    letter-spacing: 4px;
    color: #b8c9fb;
    margin-top: 2px;
}

.intro-content {
    max-width: 490px;
    width: 100%;
    margin: auto;
    padding: 64px 0;
}

.eyebrow {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 10px;
    letter-spacing: 1.6px;
    font-weight: 600;
    color: #c8d5ff;
}

.eyebrow>span {
    width: 7px;
    height: 7px;
    background: #81c5ff;
    border-radius: 50%;
    box-shadow: 0 0 0 4px #81c5ff15;
}

h1 {
    font-size: clamp(32px, 3.3vw, 49px);
    line-height: 1.23;
    letter-spacing: -1.5px;
    margin: 23px 0 18px;
    font-weight: 650;
}

.intro-content>p {
    max-width: 395px;
    color: #c2cfee;
    font-size: 14px;
    line-height: 1.85;
    margin: 0;
}

.classroom-illustration {
    position: relative;
    margin: 38px 12px 38px 0;
    border: 1px solid #ffffff30;
    border-radius: 13px;
    background: #f4f7ff;
    box-shadow: 0 22px 45px #08153c40;
    transform: rotate(-2deg);
    color: #273457;
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
    border-right: 1px solid #e4e9f4;
    display: flex;
    align-items: center;
    flex-direction: column;
    gap: 15px;
    padding-top: 16px;
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
    background: #d4ddf2;
    border-radius: 2px;
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

.illustration-heading b {
    font-size: 10px;
    display: block;
}

.illustration-heading div>span {
    font-size: 7px;
    color: #8591ac;
}

.live-label {
    background: #e0f5ee;
    color: #298367;
    border-radius: 10px;
    font-size: 7px;
    padding: 4px 7px;
    white-space: nowrap;
}

.screen-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 9px;
    margin-top: 14px;
}

.student-screen {
    background: white;
    border: 1px solid #e2e8f5;
    border-radius: 5px;
    padding: 6px;
}

.screen-content {
    height: 32px;
    background: #eaf0fe;
    border-radius: 3px;
    padding: 6px;
}

.screen-content span {
    display: block;
    height: 4px;
    width: 45%;
    background: #9eb6f1;
    border-radius: 2px;
    margin-bottom: 5px;
}

.screen-content i {
    display: block;
    height: 2px;
    width: 78%;
    background: #c8d5f1;
    margin-top: 3px;
}

.student-screen:nth-child(2n) .screen-content {
    background: #e9f5f2;
}

.screen-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
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
    border-radius: 50%;
    background: #62be9b;
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
    bottom: -18px;
    right: -17px;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 12px 16px;
    background: white;
    border-radius: 10px;
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

.floating-note b {
    display: block;
    font-size: 9px;
}

.floating-note div>span {
    display: block;
    font-size: 8px;
    color: #8791a5;
    margin-top: 2px;
}

.feature-list {
    display: flex;
    flex-wrap: wrap;
    gap: 22px;
    font-size: 11px;
    color: #d1dcff;
    padding-top: 8px;
}

.feature-list i {
    font-style: normal;
    color: #9bbaff;
    margin-right: 6px;
}

.intro-footer {
    font-size: 9px;
    color: #9eafe1;
    letter-spacing: .5px;
    margin: 0;
}

.intro-footer span {
    margin: 0 7px;
}

.form-panel {
    padding: 46px clamp(26px, 5vw, 80px) 26px;
    display: flex;
    flex-direction: column;
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
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: #edf2ff;
    color: var(--blue);
    margin-bottom: 23px;
}

.welcome-icon svg {
    width: 25px;
    height: 25px;
}

h2 {
    font-size: clamp(25px, 2.4vw, 32px);
    letter-spacing: -.8px;
    line-height: 1.3;
    margin: 0 0 10px;
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

.field-group {
    margin-bottom: 20px;
}

.field-group>label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
}

.input-wrap {
    display: flex;
    align-items: center;
    position: relative;
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
    border: 1px solid #e0e4ed;
    border-radius: 8px;
    padding: 0 46px 0 44px;
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
    background: transparent;
    border: 0;
    border-radius: 5px;
    color: #929bad;
}

.password-toggle svg {
    width: 18px;
    height: 18px;
}

.form-options {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
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
    font-weight: 600 !important;
}

.text-button:hover {
    text-decoration: underline;
}

.login-button {
    width: 100%;
    border: 0;
    border-radius: 8px;
    background: var(--blue);
    color: white;
    height: 49px;
    font-size: 13px !important;
    font-weight: 600 !important;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    box-shadow: 0 5px 12px #315de522;
    transition: background .15s;
}

.login-button:hover {
    background: #244bc7;
}

.login-button span {
    font-size: 19px;
    font-weight: 400;
}

.help-message {
    font-size: 12px;
    background: #f0f4ff;
    border-radius: 6px;
    color: #45609e;
    padding: 12px;
    margin: -8px 0 20px;
}

.account-note {
    margin-top: 30px;
    padding-top: 23px;
    border-top: 1px solid #edf0f5;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    gap: 9px;
    color: #9199aa;
}

.account-note>span {
    font-size: 16px;
}

.account-note p {
    font-size: 11px;
    line-height: 1.9;
    margin: 0;
}

.form-footer {
    color: #a1a8b6;
    text-align: center;
    font-size: 10px;
}

@media (min-width: 1600px) {
    .intro-content {
        max-width: 530px;
    }
}

@media (max-width: 900px) {
    .intro-panel {
        padding-inline: 28px;
    }

    .form-panel {
        padding-inline: 28px;
    }

    .feature-list {
        gap: 12px;
    }

    .intro-footer {
        font-size: 8px;
    }
}

@media (max-width: 700px) {
    .login-page {
        grid-template-columns: 1fr;
    }

    .intro-panel {
        padding: 23px 26px;
    }

    .brand {
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
    }
}
</style>
<script>
import axios from 'axios'

export default {
    data() {
        return {
            email: '',
            password: '',
            showPassword: false,
            showHelp: false,
            remember: false,
            loading: false,
            errorMessage: '',
            successMessage: '',
        }
    },

    methods: {
        dangNhap() {
            if (this.loading) return
            this.errorMessage = ''
            this.successMessage = ''

            if (!this.email.trim()) {
                this.errorMessage = 'Vui lòng nhập email.'
                return
            }
            if (!this.password) {
                this.errorMessage = 'Vui lòng nhập mật khẩu.'
                return
            }

            this.loading = true

            return axios
                .post('/api/dang-nhap', {
                    email: this.email.trim(),
                    password: this.password,
                }, {
                    headers: { Accept: 'application/json' },
                    timeout: 15000,
                })
                .then((res) => {
                    const token = res.data?.access_token
                    const user = res.data?.nguoi_dung

                    if (!token || !user) {
                        this.errorMessage = 'Dữ liệu đăng nhập không hợp lệ.'
                        return
                    }

                    this.password = ''

                    try {
                        // Xóa phiên cũ ở cả hai bộ nhớ trước khi lưu tài khoản mới.
                        for (const storage of [sessionStorage, localStorage]) {
                            for (const key of ['auth', 'auth_token', 'auth_user', 'auth_expires_at']) {
                                storage.removeItem(key)
                            }
                        }

                        const storage = this.remember ? localStorage : sessionStorage
                        storage.setItem('auth_user', JSON.stringify(user))
                        storage.setItem('auth_expires_at', res.data.expires_at)
                        storage.setItem('auth_token', token)
                    } catch {
                        this.errorMessage = 'Đăng nhập hợp lệ nhưng không lưu được phiên. Vui lòng bật bộ nhớ trang web và thử lại.'
                        return
                    }

                    this.successMessage = `Đăng nhập thành công! Xin chào ${user.ho_ten}.`
                    if (user.vai_tro?.ten_vai_tro === 'quan_tri_vien') {
                        window.location.assign('/admin/trang-chu')
                    }
                })
                .catch((err) => {
                    const status = err.response?.status
                    const data = err.response?.data

                    if (status === 422) {
                        this.errorMessage = Object.values(data?.errors || {}).flat().join(' ')
                            || 'Vui lòng kiểm tra email và mật khẩu.'
                    } else if (status === 429) {
                        this.errorMessage = 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau một phút.'
                    } else if (status >= 500) {
                        this.errorMessage = 'Máy chủ đang gặp lỗi. Vui lòng thử lại sau.'
                    } else if (err.code === 'ECONNABORTED') {
                        this.errorMessage = 'Máy chủ phản hồi quá lâu. Vui lòng thử lại.'
                    } else {
                        this.errorMessage = data?.message || 'Không thể kết nối tới server API.'
                    }
                })
                .finally(() => {
                    this.loading = false
                })
        },
    },
}
</script>
