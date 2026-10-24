<template>
    <div class="profile-page">
        <!-- ================= HEADER ================= -->
        <header class="page-header">
        


            <div class="header-content">
                <h1>Thông tin cá nhân</h1>
                <p>Xem thông tin tài khoản và thông tin sinh viên của bạn.</p>
            </div>
        </header>

        <!-- ================= MAIN ================= -->
        <main class="profile-layout">
            <!-- CARD PROFILE -->
            <aside class="profile-card">
                <div class="avatar-wrap">
                    <img
                        v-if="profile.avatar"
                        :src="profile.avatar"
                        alt="Ảnh đại diện"
                        class="avatar-img"
                    />

                    <div v-else class="avatar-fallback">
                        {{ initials }}
                    </div>
                </div>

                <h2>{{ profile.ho_ten || "Sinh viên" }}</h2>

                <p class="student-code">
                    {{ student.ma_sinh_vien || "Chưa có mã sinh viên" }}
                </p>

                <span class="role-badge">
                    {{ roleLabel }}
                </span>

                <!-- THÔNG TIN NHANH -->
                <div class="quick-info">
                    <div class="quick-row">
                        <div class="quick-icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2Z"
                                />
                                <path d="m22 6-10 7L2 6" />
                            </svg>
                        </div>

                        <span>
                            {{ profile.email || "Chưa cập nhật email" }}
                        </span>
                    </div>

                    <div class="quick-row">
                        <div class="quick-icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M22 16.92v3a2 2 0 0 1-2.18 2
                                    19.79 19.79 0 0 1-8.63-3.07
                                    19.5 19.5 0 0 1-6-6
                                    19.79 19.79 0 0 1-3.07-8.67
                                    A2 2 0 0 1 4.11 2h3
                                    a2 2 0 0 1 2 1.72
                                    c.12.9.33 1.78.62 2.63
                                    a2 2 0 0 1-.45 2.11L8 9.73
                                    a16 16 0 0 0 6 6l1.27-1.27
                                    a2 2 0 0 1 2.11-.45
                                    c.85.29 1.73.5 2.63.62
                                    A2 2 0 0 1 22 16.92Z"
                                />
                            </svg>
                        </div>

                        <span>
                            {{
                                profile.so_dien_thoai ||
                                "Chưa cập nhật số điện thoại"
                            }}
                        </span>
                    </div>

                    <div class="quick-row">
                        <div
                            class="quick-icon status-icon"
                            :class="{
                                active: profile.trang_thai === 'hoat_dong',
                                locked: profile.trang_thai === 'bi_khoa',
                            }"
                        >
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M22 11.08V12a10 10 0 1 1-5.93-9.14"
                                />
                                <path d="m9 11 3 3L22 4" />
                            </svg>
                        </div>

                        <span>{{ statusLabel }}</span>
                    </div>
                </div>

                <div class="profile-actions">
                    <button
                        type="button"
                        class="change-password-button"
                        @click="openChangePassword"
                    >
                        <svg viewBox="0 0 24 24">
                            <rect
                                x="3"
                                y="11"
                                width="18"
                                height="10"
                                rx="2"
                            />
                            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                        </svg>

                        <span>Đổi mật khẩu</span>
                    </button>
                </div>
            </aside>

            <!-- ================= RIGHT CONTENT ================= -->
            <section class="profile-content">
                <!-- THÔNG TIN TÀI KHOẢN -->
                <div class="info-panel">
                    <div class="panel-title">
                        <div class="panel-icon">
                            <svg viewBox="0 0 24 24">
                                <path
                                    d="M20 21a8 8 0 0 0-16 0"
                                />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </div>

                        <div>
                            <h3>Thông tin tài khoản</h3>
                            <p>Thông tin cơ bản của tài khoản</p>
                        </div>
                    </div>

                    <div class="info-grid">
                        <InfoField
                            label="Họ và tên"
                            :value="profile.ho_ten"
                        />

                        <InfoField
                            label="Email"
                            :value="profile.email"
                        />

                        <InfoField
                            label="Số điện thoại"
                            :value="profile.so_dien_thoai"
                            empty-text="Chưa cập nhật"
                        />

                        <InfoField
                            label="Ngày sinh"
                            :value="formatDate(profile.ngay_sinh)"
                            empty-text="Chưa cập nhật"
                        />

                        <InfoField
                            label="Vai trò"
                            :value="roleLabel"
                        />

                        <InfoField
                            label="Trạng thái"
                            :value="statusLabel"
                        />
                    </div>
                </div>

                <!-- THÔNG TIN SINH VIÊN -->
                <div class="info-panel">
                    <div class="panel-title">
                        <div class="panel-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="m2 10 10-5 10 5-10 5Z" />
                                <path d="M6 12v5c3 2 9 2 12 0v-5" />
                            </svg>
                        </div>

                        <div>
                            <h3>Thông tin sinh viên</h3>
                            <p>Thông tin học tập của sinh viên</p>
                        </div>
                    </div>

                    <div class="info-grid">
                        <InfoField
                            label="Mã sinh viên"
                            :value="student.ma_sinh_vien"
                            empty-text="Chưa cập nhật"
                        />

                        <InfoField
                            label="Lớp"
                            :value="classLabel"
                            empty-text="Chưa cập nhật"
                        />

                        <InfoField
                            label="Khoa"
                            :value="facultyLabel"
                            empty-text="Chưa cập nhật"
                        />

                        <InfoField
                            label="Ngành"
                            :value="majorLabel"
                            empty-text="Chưa cập nhật"
                        />

                        <InfoField
                            label="Năm trúng tuyển"
                            :value="student.nam_trung_tuyen"
                            empty-text="Chưa cập nhật"
                        />

                        <InfoField
                            label="Chức vụ lớp"
                            :value="positionLabel"
                        />
                    </div>
                </div>

                <!-- NOTE -->
                <div class="note-box">
                    <div class="note-icon">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 16v-4" />
                            <path d="M12 8h.01" />
                        </svg>
                    </div>

                    <div>
                        <strong>Lưu ý</strong>

                        <p>
                            Nếu thông tin cá nhân chưa chính xác, vui lòng
                            liên hệ cố vấn học tập hoặc quản trị viên để cập nhật.
                        </p>
                    </div>
                </div>
            </section>
        </main>

        <!-- ================= MODAL ĐỔI MẬT KHẨU ================= -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showChangePassword"
                    class="modal-overlay"
                    @click.self="closeChangePassword"
                >
                    <div class="password-modal">
                        <!-- HEADER -->
                        <div class="modal-header">
                            <div class="modal-title">
                                <div class="modal-title-icon">
                                    <svg viewBox="0 0 24 24">
                                        <rect
                                            x="3"
                                            y="11"
                                            width="18"
                                            height="10"
                                            rx="2"
                                        />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                </div>

                                <div>
                                    <h3>Đổi mật khẩu</h3>

                                    <p>
                                        Cập nhật mật khẩu mới để bảo vệ tài khoản.
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="modal-close"
                                aria-label="Đóng"
                                @click="closeChangePassword"
                            >
                                <svg viewBox="0 0 24 24">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="changePassword">
                            <!-- PASSWORD CURRENT -->
                            <div class="password-group">
                                <label>Mật khẩu hiện tại</label>

                                <div class="password-input">
                                    <input
                                        v-model="passwordForm.mat_khau_hien_tai"
                                        :type="
                                            showOldPassword
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="Nhập mật khẩu hiện tại"
                                        autocomplete="current-password"
                                    />

                                    <EyeButton
                                        :visible="showOldPassword"
                                        @toggle="
                                            showOldPassword =
                                                !showOldPassword
                                        "
                                    />
                                </div>
                            </div>

                            <!-- PASSWORD NEW -->
                            <div class="password-group">
                                <label>Mật khẩu mới</label>

                                <div class="password-input">
                                    <input
                                        v-model="passwordForm.mat_khau_moi"
                                        :type="
                                            showNewPassword
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="Nhập mật khẩu mới"
                                        autocomplete="new-password"
                                    />

                                    <EyeButton
                                        :visible="showNewPassword"
                                        @toggle="
                                            showNewPassword =
                                                !showNewPassword
                                        "
                                    />
                                </div>

                                <small>
                                    Mật khẩu mới phải có ít nhất 8 ký tự.
                                </small>
                            </div>

                            <!-- CONFIRM -->
                            <div class="password-group">
                                <label>Xác nhận mật khẩu mới</label>

                                <div class="password-input">
                                    <input
                                        v-model="
                                            passwordForm.mat_khau_moi_confirmation
                                        "
                                        :type="
                                            showConfirmPassword
                                                ? 'text'
                                                : 'password'
                                        "
                                        placeholder="Nhập lại mật khẩu mới"
                                        autocomplete="new-password"
                                    />

                                    <EyeButton
                                        :visible="showConfirmPassword"
                                        @toggle="
                                            showConfirmPassword =
                                                !showConfirmPassword
                                        "
                                    />
                                </div>
                            </div>

                            <!-- ERROR -->
                            <div
                                v-if="passwordError"
                                class="password-message error"
                            >
                                {{ passwordError }}
                            </div>

                            <!-- SUCCESS -->
                            <div
                                v-if="passwordSuccess"
                                class="password-message success"
                            >
                                {{ passwordSuccess }}
                            </div>

                            <!-- ACTION -->
                            <div class="modal-actions">
                                <button
                                    type="button"
                                    class="cancel-button"
                                    :disabled="changingPassword"
                                    @click="closeChangePassword"
                                >
                                    Hủy
                                </button>

                                <button
                                    type="submit"
                                    class="submit-button"
                                    :disabled="changingPassword"
                                >
                                    <span
                                        v-if="changingPassword"
                                        class="spinner"
                                    ></span>

                                    {{
                                        changingPassword
                                            ? "Đang cập nhật..."
                                            : "Đổi mật khẩu"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import axios from "axios"
import {
    computed,
    defineComponent,
    h,
    onMounted,
    reactive,
    ref,
} from "vue"
import { useRouter } from "vue-router"

const router = useRouter()

/* =====================================================
   COMPONENT: INFO FIELD
===================================================== */

const InfoField = defineComponent({
    props: {
        label: {
            type: String,
            required: true,
        },
        value: {
            type: [String, Number],
            default: "",
        },
        emptyText: {
            type: String,
            default: "Chưa cập nhật",
        },
    },

    setup(props) {
        return () =>
            h("div", { class: "form-group" }, [
                h("label", props.label),

                h(
                    "div",
                    {
                        class: [
                            "readonly-field",
                            !props.value && "empty-value",
                        ],
                    },
                    props.value || props.emptyText
                ),
            ])
    },
})

/* =====================================================
   COMPONENT: EYE BUTTON
===================================================== */

const EyeButton = defineComponent({
    props: {
        visible: Boolean,
    },

    emits: ["toggle"],

    setup(props, { emit }) {
        return () =>
            h(
                "button",
                {
                    type: "button",
                    class: "eye-button",
                    title: props.visible
                        ? "Ẩn mật khẩu"
                        : "Hiện mật khẩu",
                    "aria-label": props.visible
                        ? "Ẩn mật khẩu"
                        : "Hiện mật khẩu",
                    onClick: () => emit("toggle"),
                },
                [
                    h(
                        "svg",
                        {
                            viewBox: "0 0 24 24",
                            fill: "none",
                            stroke: "currentColor",
                            "stroke-width": "2",
                            "stroke-linecap": "round",
                            "stroke-linejoin": "round",
                        },
                        props.visible
                            ? [
                                  h("path", {
                                      d: "M3 3l18 18",
                                  }),
                                  h("path", {
                                      d: "M10.6 10.6a2 2 0 0 0 2.8 2.8",
                                  }),
                                  h("path", {
                                      d: "M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a18 18 0 0 1-2.1 3.2",
                                  }),
                                  h("path", {
                                      d: "M6.6 6.6C3.8 8.5 2 12 2 12s3.5 8 10 8a9.8 9.8 0 0 0 5.4-1.6",
                                  }),
                              ]
                            : [
                                  h("path", {
                                      d: "M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z",
                                  }),
                                  h("circle", {
                                      cx: "12",
                                      cy: "12",
                                      r: "3",
                                  }),
                              ]
                    ),
                ]
            )
    },
})

/* =====================================================
   DATA
===================================================== */

const profile = ref({
    id: null,
    ho_ten: "",
    email: "",
    so_dien_thoai: "",
    ngay_sinh: "",
    avatar: "",
    vai_tro_id: 3,
    trang_thai: "hoat_dong",
})

const student = ref({})

/* =====================================================
   AUTH
===================================================== */

const getToken = () =>
    localStorage.getItem("auth_token") ||
    sessionStorage.getItem("auth_token") ||
    localStorage.getItem("access_token") ||
    sessionStorage.getItem("access_token") ||
    localStorage.getItem("token") ||
    sessionStorage.getItem("token")

const clearAuth = () => {
    const keys = [
        "auth",
        "auth_token",
        "auth_user",
        "auth_expires_at",
        "access_token",
        "nguoi_dung",
        "token",
    ]

    keys.forEach((key) => {
        localStorage.removeItem(key)
        sessionStorage.removeItem(key)
    })
}

/* =====================================================
   GET PROFILE
===================================================== */

const getThongTinCaNhan = async () => {
    const token = getToken()

    if (!token) {
        router.replace("/dang-nhap")
        return
    }

    try {
        const response = await axios.get(
            "/api/sinh-vien/thong-tin-ca-nhan",
            {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json",
                },
            }
        )

        if (!response.data?.status) return

        const userData =
            response.data.data?.user || {}

        const studentData =
            response.data.data?.sinh_vien || {}

        profile.value = {
            ...profile.value,
            ...userData,
        }

        student.value = {
            ...studentData,

            ma_lop:
                studentData.lop?.ma_lop ||
                studentData.ma_lop ||
                "",

            ten_lop:
                studentData.lop?.ten_lop ||
                studentData.ten_lop ||
                "",

            khoa:
                studentData.khoa ||
                studentData.lop?.khoa ||
                {},

            nganh:
                studentData.nganh ||
                studentData.lop?.nganh ||
                {},

            ma_khoa:
                studentData.khoa?.ma_khoa ||
                studentData.lop?.khoa?.ma_khoa ||
                studentData.ma_khoa ||
                "",

            ten_khoa:
                studentData.khoa?.ten_khoa ||
                studentData.lop?.khoa?.ten_khoa ||
                studentData.ten_khoa ||
                "",

            ma_nganh:
                studentData.nganh?.ma_nganh ||
                studentData.lop?.nganh?.ma_nganh ||
                studentData.ma_nganh ||
                "",

            ten_nganh:
                studentData.nganh?.ten_nganh ||
                studentData.lop?.nganh?.ten_nganh ||
                studentData.ten_nganh ||
                "",
        }
    } catch (error) {
        console.error(
            "Lỗi lấy thông tin cá nhân:",
            error.response?.data || error
        )

        if (error.response?.status === 401) {
            clearAuth()
            router.replace("/dang-nhap")
        }
    }
}

/* =====================================================
   COMPUTED
===================================================== */

const initials = computed(() => {
    const name =
        profile.value.ho_ten || "Sinh viên"

    return name
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((word) => word.charAt(0))
        .slice(-2)
        .join("")
        .toUpperCase()
})

const roleLabel = computed(() => {
    const role =
        profile.value?.vai_tro?.ten_vai_tro

    if (role === "quan_tri_vien")
        return "Quản trị viên"

    if (role === "giang_vien")
        return "Giảng viên"

    if (role === "sinh_vien")
        return "Sinh viên"

    switch (Number(profile.value.vai_tro_id)) {
        case 1:
            return "Quản trị viên"

        case 2:
            return "Giảng viên"

        default:
            return "Sinh viên"
    }
})

const statusLabel = computed(() => {
    switch (profile.value.trang_thai) {
        case "hoat_dong":
            return "Đang hoạt động"

        case "khong_hoat_dong":
            return "Không hoạt động"

        case "bi_khoa":
            return "Bị khóa"

        default:
            return "Không xác định"
    }
})

const positionLabel = computed(() =>
    student.value?.chuc_vu || "Sinh viên"
)

const classLabel = computed(() =>
    student.value?.ten_lop
        ? `${student.value.ma_lop || ""} - ${student.value.ten_lop}`
              .replace(/^ - /, "")
        : student.value?.ma_lop || ""
)

const facultyLabel = computed(() =>
    student.value?.ten_khoa ||
    student.value?.khoa?.ten_khoa ||
    student.value?.ma_khoa ||
    ""
)

const majorLabel = computed(() =>
    student.value?.ten_nganh ||
    student.value?.nganh?.ten_nganh ||
    student.value?.ma_nganh ||
    ""
)

/* =====================================================
   DATE
===================================================== */

const formatDate = (date) => {
    if (!date) return ""

    const parsed = new Date(date)

    if (Number.isNaN(parsed.getTime())) {
        return date
    }

    return parsed.toLocaleDateString("vi-VN")
}

/* =====================================================
   BACK
===================================================== */

const goHome = () => {
    router.push("/sinh-vien/trang-chu")
}

/* =====================================================
   PASSWORD
===================================================== */

const showChangePassword = ref(false)

const showOldPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const changingPassword = ref(false)

const passwordError = ref("")
const passwordSuccess = ref("")

const passwordForm = reactive({
    mat_khau_hien_tai: "",
    mat_khau_moi: "",
    mat_khau_moi_confirmation: "",
})

const resetPasswordForm = () => {
    passwordForm.mat_khau_hien_tai = ""
    passwordForm.mat_khau_moi = ""
    passwordForm.mat_khau_moi_confirmation = ""

    passwordError.value = ""
    passwordSuccess.value = ""

    showOldPassword.value = false
    showNewPassword.value = false
    showConfirmPassword.value = false
}

const openChangePassword = () => {
    resetPasswordForm()
    showChangePassword.value = true
}

const closeChangePassword = () => {
    if (changingPassword.value) return

    showChangePassword.value = false
    resetPasswordForm()
}

const changePassword = async () => {
    passwordError.value = ""
    passwordSuccess.value = ""

    if (
        !passwordForm.mat_khau_hien_tai ||
        !passwordForm.mat_khau_moi ||
        !passwordForm.mat_khau_moi_confirmation
    ) {
        passwordError.value =
            "Vui lòng nhập đầy đủ thông tin."
        return
    }

    if (passwordForm.mat_khau_moi.length < 8) {
        passwordError.value =
            "Mật khẩu mới phải có ít nhất 8 ký tự."
        return
    }

    if (
        passwordForm.mat_khau_moi !==
        passwordForm.mat_khau_moi_confirmation
    ) {
        passwordError.value =
            "Xác nhận mật khẩu mới không khớp."
        return
    }

    if (
        passwordForm.mat_khau_hien_tai ===
        passwordForm.mat_khau_moi
    ) {
        passwordError.value =
            "Mật khẩu mới phải khác mật khẩu hiện tại."
        return
    }

    const token = getToken()

    if (!token) {
        passwordError.value =
            "Phiên đăng nhập không hợp lệ."
        return
    }

    changingPassword.value = true

    try {
        const response = await axios.post(
            "/api/doi-mat-khau",
            {
                mat_khau_hien_tai:
                    passwordForm.mat_khau_hien_tai,

                mat_khau_moi:
                    passwordForm.mat_khau_moi,

                mat_khau_moi_confirmation:
                    passwordForm.mat_khau_moi_confirmation,
            },
            {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json",
                },
            }
        )

        passwordSuccess.value =
            response.data?.message ||
            "Đổi mật khẩu thành công."

        passwordForm.mat_khau_hien_tai = ""
        passwordForm.mat_khau_moi = ""
        passwordForm.mat_khau_moi_confirmation = ""

        setTimeout(() => {
            showChangePassword.value = false
        }, 1200)
    } catch (error) {
        const data = error.response?.data

        if (data?.errors) {
            passwordError.value =
                Object.values(data.errors).flat()[0] ||
                "Đổi mật khẩu thất bại."
        } else {
            passwordError.value =
                data?.message ||
                "Đổi mật khẩu thất bại."
        }
    } finally {
        changingPassword.value = false
    }
}

onMounted(getThongTinCaNhan)
</script>

<style scoped>
/* =====================================================
   PAGE
===================================================== */

.profile-page {
    min-height: calc(100vh - 65px);
    box-sizing: border-box;
    padding: 28px 32px 40px;
    background: #f6f8fc;
    color: #0f172a;
}

/* =====================================================
   HEADER
===================================================== */

.page-header {
    position: relative;
    min-height: 88px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 28px;
    max-width: 1450px;
}

.header-content {
    text-align: center;
}

.header-content h1 {
    margin: 0;
    font-size: 30px;
    line-height: 1.2;
    font-weight: 800;
    letter-spacing: -0.5px;
}

.header-content p {
    margin: 8px 0 0;
    color: #64748b;
    font-size: 14px;
}

/* =====================================================
   BACK
===================================================== */

.back-button {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);

    width: 46px;
    height: 46px;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 0;

    border: 1px solid #dce3ed;
    border-radius: 13px;

    background: #ffffff;
    color: #334155;

    cursor: pointer;

    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04);

    transition: 0.2s ease;
}

.back-button svg {
    width: 21px;
    height: 21px;
}

.back-button:hover {
    color: #ffffff;
    border-color: #2563eb;
    background: #2563eb;

    transform:
        translateY(-50%)
        translateX(-2px);

    box-shadow:
        0 7px 18px rgba(37, 99, 235, 0.22);
}

/* =====================================================
   LAYOUT
===================================================== */

.profile-layout {
    width: 100%;
    max-width: 1450px;

    margin: 0 auto;

    display: grid;

    grid-template-columns:
        310px minmax(0, 1fr);

    gap: 24px;

    align-items: start;
}

/* =====================================================
   CARD
===================================================== */

.profile-card,
.info-panel {
    background: #ffffff;

    border:
        1px solid #e3e8f0;

    border-radius: 18px;

    box-shadow:
        0 5px 20px rgba(15, 23, 42, 0.045);
}

/* =====================================================
   PROFILE
===================================================== */

.profile-card {
    padding: 30px 25px 25px;
    text-align: center;
}

/* =====================================================
   AVATAR
===================================================== */

.avatar-wrap {
    display: flex;
    justify-content: center;

    margin-bottom: 18px;
}

.avatar-img,
.avatar-fallback {
    width: 108px;
    height: 108px;

    border-radius: 50%;
}

.avatar-img {
    object-fit: cover;

    border:
        4px solid #dbeafe;
}

.avatar-fallback {
    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    font-size: 33px;
    font-weight: 800;

    background:
        linear-gradient(
            135deg,
            #2563eb 0%,
            #06b6d4 100%
        );

    box-shadow:
        0 10px 25px rgba(37, 99, 235, 0.18);
}

.profile-card h2 {
    margin: 0;

    font-size: 21px;
    font-weight: 800;
}

.student-code {
    margin: 7px 0 13px;

    color: #64748b;

    font-size: 14px;
    font-weight: 600;
}

.role-badge {
    display: inline-flex;

    padding: 7px 14px;

    border-radius: 999px;

    color: #2563eb;
    background: #eff6ff;

    font-size: 12px;
    font-weight: 700;
}

/* =====================================================
   QUICK INFO
===================================================== */

.quick-info {
    display: grid;

    gap: 13px;

    margin-top: 25px;

    padding: 19px 0;

    border-top:
        1px solid #edf0f5;

    border-bottom:
        1px solid #edf0f5;

    text-align: left;
}

.quick-row {
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 11px;

    color: #475569;

    font-size: 13px;
}

.quick-row span {
    min-width: 0;

    overflow-wrap: anywhere;
}

.quick-icon {
    width: 19px;
    height: 19px;

    flex-shrink: 0;

    color: #3b82f6;
}

.quick-icon svg,
.panel-icon svg,
.note-icon svg,
.change-password-button svg,
.modal-title-icon svg,
.modal-close svg {
    width: 100%;
    height: 100%;

    fill: none;

    stroke: currentColor;
    stroke-width: 1.8;

    stroke-linecap: round;
    stroke-linejoin: round;
}

.status-icon.active {
    color: #16a34a;
}

.status-icon.locked {
    color: #dc2626;
}

/* =====================================================
   CHANGE PASSWORD
===================================================== */

.profile-actions {
    margin-top: 20px;
}

.change-password-button {
    width: 100%;
    min-height: 44px;

    display: flex;
    align-items: center;
    justify-content: center;

    gap: 9px;

    border: 1px solid #2563eb;
    border-radius: 11px;

    color: #ffffff;
    background: #2563eb;

    font-size: 13px;
    font-weight: 700;

    cursor: pointer;

    transition: 0.2s ease;
}

.change-password-button svg {
    width: 17px;
    height: 17px;
}

.change-password-button:hover {
    border-color: #1d4ed8;
    background: #1d4ed8;

    box-shadow:
        0 7px 16px rgba(37, 99, 235, 0.18);
}

/* =====================================================
   CONTENT
===================================================== */

.profile-content {
    min-width: 0;

    display: grid;

    gap: 20px;
}

.info-panel {
    padding: 25px 27px 27px;
}

/* =====================================================
   PANEL TITLE
===================================================== */

.panel-title {
    display: flex;
    align-items: center;

    gap: 12px;

    margin-bottom: 23px;
}

.panel-icon {
    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    border-radius: 11px;

    color: #2563eb;
    background: #eff6ff;
}

.panel-icon svg {
    width: 19px;
    height: 19px;
}

.panel-title h3 {
    margin: 0;

    font-size: 17px;
    font-weight: 800;
}

.panel-title p {
    margin: 4px 0 0;

    color: #94a3b8;

    font-size: 12px;
}

/* =====================================================
   GRID
===================================================== */

.info-grid {
    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 18px 20px;
}

.form-group {
    min-width: 0;

    display: grid;

    gap: 8px;
}

.form-group label {
    color: #475569;

    font-size: 13px;
    font-weight: 700;
}

.readonly-field {
    width: 100%;
    min-height: 48px;

    box-sizing: border-box;

    display: flex;
    align-items: center;

    padding: 0 14px;

    border:
        1px solid #dfe5ed;

    border-radius: 11px;

    color: #1e293b;
    background: #f8fafc;

    font-size: 14px;

    overflow-wrap: anywhere;
}

.empty-value {
    color: #94a3b8;
}

/* =====================================================
   NOTE
===================================================== */

.note-box {
    display: flex;
    align-items: flex-start;

    gap: 12px;

    padding: 16px 18px;

    border:
        1px solid #bfdbfe;

    border-radius: 14px;

    color: #334155;
    background: #eff6ff;
}

.note-icon {
    width: 19px;
    height: 19px;

    flex-shrink: 0;

    margin-top: 1px;

    color: #2563eb;
}

.note-box strong {
    display: block;

    margin-bottom: 3px;

    color: #1e3a8a;

    font-size: 13px;
}

.note-box p {
    margin: 0;

    color: #475569;

    font-size: 13px;
    line-height: 1.55;
}

/* =====================================================
   MODAL
===================================================== */

.modal-overlay {
    position: fixed;
    inset: 0;

    z-index: 99999;

    display: flex;
    align-items: center;
    justify-content: center;

    box-sizing: border-box;

    padding: 20px;

    background:
        rgba(15, 23, 42, 0.6);

    backdrop-filter: blur(5px);
}

.password-modal {
    width: 100%;
    max-width: 490px;

    box-sizing: border-box;

    padding: 25px;

    border:
        1px solid #e2e8f0;

    border-radius: 19px;

    background: #ffffff;

    box-shadow:
        0 25px 70px rgba(15, 23, 42, 0.3);
}

/* =====================================================
   MODAL HEADER
===================================================== */

.modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;

    gap: 15px;

    margin-bottom: 25px;
}

.modal-title {
    display: flex;

    gap: 12px;
}

.modal-title-icon {
    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    border-radius: 11px;

    color: #2563eb;
    background: #eff6ff;
}

.modal-title-icon svg {
    width: 19px;
    height: 19px;
}

.modal-header h3 {
    margin: 1px 0 0;

    color: #0f172a;

    font-size: 19px;
    font-weight: 800;
}

.modal-header p {
    margin: 5px 0 0;

    color: #64748b;

    font-size: 12px;
    line-height: 1.5;
}

.modal-close {
    width: 36px;
    height: 36px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    padding: 0;

    border: 0;
    border-radius: 10px;

    color: #64748b;
    background: #f1f5f9;

    cursor: pointer;

    transition: 0.2s;
}

.modal-close svg {
    width: 18px;
    height: 18px;
}

.modal-close:hover {
    color: #0f172a;
    background: #e2e8f0;
}

/* =====================================================
   PASSWORD
===================================================== */

.password-group {
    display: grid;

    gap: 8px;

    margin-bottom: 17px;
}

.password-group label {
    color: #475569;

    font-size: 13px;
    font-weight: 700;
}

.password-group small {
    color: #94a3b8;

    font-size: 11px;
}

.password-input {
    display: flex;
    align-items: stretch;

    overflow: hidden;

    border:
        1px solid #dbe3ed;

    border-radius: 11px;

    background: #f8fafc;

    transition: 0.2s ease;
}

.password-input:focus-within {
    border-color: #2563eb;

    box-shadow:
        0 0 0 3px rgba(37, 99, 235, 0.09);
}

.password-input input {
    min-width: 0;

    flex: 1;

    padding: 13px 14px;

    border: 0;
    outline: none;

    color: #0f172a;
    background: transparent;

    font-size: 14px;
}

.password-input input::placeholder {
    color: #94a3b8;
}

/* =====================================================
   EYE
===================================================== */

.eye-button {
    width: 48px;

    display: flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    padding: 0;

    border: 0;

    color: #94a3b8;
    background: transparent;

    cursor: pointer;

    transition: 0.2s ease;
}

.eye-button svg {
    width: 20px;
    height: 20px;
}

.eye-button:hover {
    color: #2563eb;
    background: #eff6ff;
}

/* =====================================================
   MESSAGE
===================================================== */

.password-message {
    margin-top: 4px;
    margin-bottom: 15px;

    padding: 11px 13px;

    border-radius: 10px;

    font-size: 12px;
    line-height: 1.5;
}

.password-message.error {
    border: 1px solid #fecaca;

    color: #dc2626;
    background: #fef2f2;
}

.password-message.success {
    border: 1px solid #bbf7d0;

    color: #15803d;
    background: #f0fdf4;
}

/* =====================================================
   MODAL ACTIONS
===================================================== */

.modal-actions {
    display: flex;
    justify-content: flex-end;

    gap: 10px;

    margin-top: 23px;
}

.modal-actions button {
    min-height: 41px;

    padding: 0 18px;

    border-radius: 10px;

    font-size: 13px;
    font-weight: 700;

    cursor: pointer;

    transition: 0.2s ease;
}

.cancel-button {
    border:
        1px solid #dbe3ed;

    color: #475569;
    background: #ffffff;
}

.cancel-button:hover {
    color: #0f172a;
    background: #f8fafc;
}

.submit-button {
    min-width: 130px;

    display: flex;
    align-items: center;
    justify-content: center;

    gap: 8px;

    border:
        1px solid #2563eb;

    color: #ffffff;
    background: #2563eb;
}

.submit-button:hover {
    border-color: #1d4ed8;
    background: #1d4ed8;
}

.modal-actions button:disabled {
    opacity: 0.6;

    cursor: not-allowed;
}

.spinner {
    width: 13px;
    height: 13px;

    border:
        2px solid rgba(255, 255, 255, 0.35);

    border-top-color: #ffffff;

    border-radius: 50%;

    animation: spin 0.7s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* =====================================================
   TRANSITION
===================================================== */

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.18s ease;
}

.modal-enter-active .password-modal,
.modal-leave-active .password-modal {
    transition:
        transform 0.18s ease,
        opacity 0.18s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .password-modal,
.modal-leave-to .password-modal {
    opacity: 0;

    transform:
        translateY(8px)
        scale(0.98);
}

/* =====================================================
   DARK MODE
===================================================== */

:global(html.dark-theme) .profile-page {
    color: #f8fafc;
    background: #1f2124;
}

:global(html.dark-theme) .header-content p {
    color: #94a3b8;
}

:global(html.dark-theme) .back-button {
    border-color:
        rgba(255, 255, 255, 0.13);

    color: #e2e8f0;
    background: #111214;

    box-shadow: none;
}

:global(html.dark-theme) .back-button:hover {
    border-color: #3b82f6;
    color: #ffffff;
    background: #2563eb;
}

:global(html.dark-theme) .profile-card,
:global(html.dark-theme) .info-panel {
    border-color:
        rgba(255, 255, 255, 0.11);

    background: #111214;

    box-shadow: none;
}

:global(html.dark-theme) .student-code {
    color: #94a3b8;
}

:global(html.dark-theme) .role-badge {
    color: #60a5fa;
    background:
        rgba(37, 99, 235, 0.13);
}

:global(html.dark-theme) .quick-info {
    border-color:
        rgba(255, 255, 255, 0.09);
}

:global(html.dark-theme) .quick-row {
    color: #cbd5e1;
}

:global(html.dark-theme) .panel-icon,
:global(html.dark-theme) .modal-title-icon {
    color: #60a5fa;

    background:
        rgba(37, 99, 235, 0.13);
}

:global(html.dark-theme) .panel-title p {
    color: #64748b;
}

:global(html.dark-theme) .form-group label {
    color: #cbd5e1;
}

:global(html.dark-theme) .readonly-field {
    border-color:
        rgba(255, 255, 255, 0.11);

    color: #f1f5f9;
    background: #1c1e21;
}

:global(html.dark-theme) .empty-value {
    color: #64748b;
}

:global(html.dark-theme) .note-box {
    border-color:
        rgba(59, 130, 246, 0.2);

    background:
        rgba(37, 99, 235, 0.08);
}

:global(html.dark-theme) .note-box strong {
    color: #93c5fd;
}

:global(html.dark-theme) .note-box p {
    color: #cbd5e1;
}

/* MODAL DARK */

:global(html.dark-theme) .password-modal {
    border-color:
        rgba(255, 255, 255, 0.12);

    background: #181a1e;
}

:global(html.dark-theme) .modal-header h3 {
    color: #ffffff;
}

:global(html.dark-theme) .modal-header p {
    color: #94a3b8;
}

:global(html.dark-theme) .modal-close {
    color: #cbd5e1;
    background: #25272b;
}

:global(html.dark-theme) .modal-close:hover {
    color: #ffffff;
    background: #303338;
}

:global(html.dark-theme) .password-group label {
    color: #e2e8f0;
}

:global(html.dark-theme) .password-input {
    border-color:
        rgba(255, 255, 255, 0.13);

    background: #111214;
}

:global(html.dark-theme) .password-input input {
    color: #ffffff;
}

:global(html.dark-theme) .eye-button {
    color: #94a3b8;
}

:global(html.dark-theme) .eye-button:hover {
    color: #60a5fa;

    background:
        rgba(37, 99, 235, 0.12);
}

:global(html.dark-theme) .cancel-button {
    border-color:
        rgba(255, 255, 255, 0.12);

    color: #cbd5e1;
    background: #25272b;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1000px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }

    .profile-card {
        max-width: none;
    }
}

@media (max-width: 700px) {
    .profile-page {
        padding: 18px 16px 30px;
    }

    .page-header {
        min-height: 100px;

        padding-left: 55px;
        padding-right: 55px;
    }

    .back-button {
        width: 42px;
        height: 42px;
    }

    .header-content h1 {
        font-size: 23px;
    }

    .header-content p {
        font-size: 12px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .profile-card {
        padding: 25px 20px;
    }

    .info-panel {
        padding: 21px 18px;
    }
}

@media (max-width: 480px) {
    .header-content p {
        display: none;
    }

    .page-header {
        min-height: 65px;
    }

    .password-modal {
        padding: 20px 17px;
    }

    .modal-title-icon {
        display: none;
    }

    .modal-actions {
        flex-direction: column-reverse;
    }

    .modal-actions button {
        width: 100%;
    }
}
</style>