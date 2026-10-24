<template>
    <div class="class-page">

        <!-- ================= HEADER ================= -->
        <header class="page-header">


            <div class="header-content">
                <h1>Lớp học của tôi</h1>

                <p>
                    Xem và theo dõi các lớp học bạn đang tham gia.
                </p>
            </div>

        </header>


        <!-- ================= THỐNG KÊ ================= -->
        <section class="statistics">

            <div class="stat-card">

                <div class="stat-icon blue">
                    <svg viewBox="0 0 24 24">
                        <path d="m2 10 10-5 10 5-10 5Z" />
                        <path d="M6 12v5c3 2 9 2 12 0v-5" />
                    </svg>
                </div>

                <div>
                    <span>Tổng lớp học</span>
                    <strong>{{ classes.length }}</strong>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon green">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9" />
                        <path d="m8 12 2.5 2.5L16 9" />
                    </svg>
                </div>

                <div>
                    <span>Đang học</span>
                    <strong>{{ activeCount }}</strong>
                </div>

            </div>

        </section>


        <!-- ================= TOOLBAR ================= -->
        <section class="toolbar">

            <div class="search-box">

                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7" />
                    <path d="m20 20-4-4" />
                </svg>

                <input
                    v-model="keyword"
                    type="text"
                    placeholder="Tìm theo mã lớp, tên lớp hoặc giảng viên..."
                />

                <button
                    v-if="keyword"
                    type="button"
                    class="clear-search"
                    @click="keyword = ''"
                >
                    ×
                </button>

            </div>


            <select v-model="statusFilter">
                <option value="all">
                    Tất cả trạng thái
                </option>

                <option value="dang_hoc">
                    Đang học
                </option>

                <option value="da_roi_lop">
                    Đã rời lớp
                </option>
            </select>

        </section>


        <!-- ================= LOADING ================= -->
        <div
            v-if="loading"
            class="state-box"
        >
            <div class="spinner"></div>

            <strong>Đang tải lớp học...</strong>

            <p>
                Vui lòng chờ trong giây lát.
            </p>
        </div>


        <!-- ================= ERROR ================= -->
        <div
            v-else-if="errorMessage"
            class="state-box"
        >
            <div class="state-icon error-icon">
                !
            </div>

            <strong>Không thể tải lớp học</strong>

            <p>{{ errorMessage }}</p>

            <button
                class="retry-button"
                @click="getLopHoc"
            >
                Thử lại
            </button>
        </div>


        <!-- ================= EMPTY ================= -->
        <div
            v-else-if="filteredClasses.length === 0"
            class="state-box"
        >
            <div class="state-icon">

                <svg viewBox="0 0 24 24">
                    <path d="m2 10 10-5 10 5-10 5Z" />
                    <path d="M6 12v5c3 2 9 2 12 0v-5" />
                </svg>

            </div>

            <strong>
                {{
                    keyword
                        ? "Không tìm thấy lớp học"
                        : "Chưa có lớp học"
                }}
            </strong>

            <p>
                {{
                    keyword
                        ? "Không có lớp học phù hợp với từ khóa tìm kiếm."
                        : "Bạn chưa tham gia lớp học nào."
                }}
            </p>

        </div>


        <!-- ================= CLASS GRID ================= -->
        <section
            v-else
            class="class-grid"
        >

            <article
                v-for="item in filteredClasses"
                :key="item.id"
                class="class-card"
            >

                <!-- TOP -->
                <div class="card-top">

                    <div class="class-icon">
                        LH
                    </div>

                    <span
                        class="status-badge"
                        :class="{
                            active:
                                item.trang_thai_tham_gia ===
                                'dang_hoc',

                            inactive:
                                item.trang_thai_tham_gia ===
                                'da_roi_lop',
                        }"
                    >
                        <span class="status-dot"></span>

                        {{
                            getStatusLabel(
                                item.trang_thai_tham_gia
                            )
                        }}
                    </span>

                </div>


                <!-- CONTENT -->
                <div class="card-content">

                    <span class="class-code">
                        {{ item.ma_lop }}
                    </span>

                    <h2>
                        {{ item.ten_lop }}
                    </h2>

                    <p class="description">
                        {{
                            item.mo_ta ||
                            "Chưa có mô tả cho lớp học này."
                        }}
                    </p>

                </div>


                <!-- INFORMATION -->
                <div class="class-info">

                    <div class="info-row">

                        <div class="info-icon">

                            <svg viewBox="0 0 24 24">
                                <circle
                                    cx="12"
                                    cy="8"
                                    r="4"
                                />

                                <path
                                    d="M4 21a8 8 0 0 1 16 0"
                                />
                            </svg>

                        </div>

                        <div>
                            <span>Giảng viên</span>

                            <strong>
                                {{
                                    item.giang_vien?.ho_ten ||
                                    "Chưa cập nhật"
                                }}
                            </strong>
                        </div>

                    </div>


                    <div class="info-row">

                        <div class="info-icon">

                            <svg viewBox="0 0 24 24">
                                <rect
                                    x="3"
                                    y="5"
                                    width="18"
                                    height="16"
                                    rx="2"
                                />

                                <path d="M16 3v4M8 3v4M3 11h18" />
                            </svg>

                        </div>

                        <div>
                            <span>Ngày tham gia</span>

                            <strong>
                                {{ formatDate(item.created_at) }}
                            </strong>
                        </div>

                    </div>

                </div>


                <!-- FOOTER -->
                <div class="card-footer">

                    <button
                        type="button"
                        class="view-button"
                        @click="viewClass(item)"
                    >
                        <span>Xem lớp học</span>

                        <svg viewBox="0 0 24 24">
                            <path d="M5 12h14" />
                            <path d="m13 6 6 6-6 6" />
                        </svg>
                    </button>

                </div>

            </article>

        </section>

    </div>
</template>


<script setup>
import axios from "axios"
import {
    computed,
    onMounted,
    ref,
} from "vue"

import { useRouter } from "vue-router"


const router = useRouter()


/* =====================================================
   DATA
===================================================== */

const classes = ref([])

const loading = ref(false)

const errorMessage = ref("")

const keyword = ref("")

const statusFilter = ref("all")


/* =====================================================
   TOKEN
===================================================== */

const getToken = () => {

    return (
        localStorage.getItem("auth_token") ||
        sessionStorage.getItem("auth_token") ||
        localStorage.getItem("access_token") ||
        sessionStorage.getItem("access_token") ||
        localStorage.getItem("token") ||
        sessionStorage.getItem("token")
    )

}


/* =====================================================
   CLEAR AUTH
===================================================== */

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
   GET CLASS
===================================================== */

const getLopHoc = async () => {

    const token = getToken()


    if (!token) {

        router.replace("/dang-nhap")

        return

    }


    loading.value = true

    errorMessage.value = ""


    try {

        const response = await axios.get(
            "/api/sinh-vien/lop-hoc",
            {
                headers: {

                    Authorization:
                        `Bearer ${token}`,

                    Accept:
                        "application/json",

                },
            }
        )


        if (response.data?.status) {

            classes.value =
                response.data.data || []

        } else {

            classes.value = []

            errorMessage.value =
                response.data?.message ||
                "Không thể lấy danh sách lớp học."

        }

    } catch (error) {

        console.error(
            "Lỗi lấy lớp học:",
            error.response?.data || error
        )


        if (error.response?.status === 401) {

            clearAuth()

            router.replace("/dang-nhap")

            return

        }


        classes.value = []

        errorMessage.value =
            error.response?.data?.message ||
            "Không thể kết nối đến máy chủ."

    } finally {

        loading.value = false

    }

}


/* =====================================================
   ACTIVE COUNT
===================================================== */

const activeCount = computed(() => {

    return classes.value.filter(
        (item) =>
            item.trang_thai_tham_gia ===
            "dang_hoc"
    ).length

})


/* =====================================================
   FILTER
===================================================== */

const filteredClasses = computed(() => {

    const search =
        keyword.value
            .trim()
            .toLowerCase()


    return classes.value.filter((item) => {

        /* STATUS */

        const matchStatus =
            statusFilter.value === "all" ||
            item.trang_thai_tham_gia ===
                statusFilter.value


        /* SEARCH */

        const teacher =
            item.giang_vien?.ho_ten || ""


        const matchSearch =
            !search ||

            item.ma_lop
                ?.toLowerCase()
                .includes(search) ||

            item.ten_lop
                ?.toLowerCase()
                .includes(search) ||

            teacher
                .toLowerCase()
                .includes(search)


        return (
            matchStatus &&
            matchSearch
        )

    })

})


/* =====================================================
   STATUS
===================================================== */

const getStatusLabel = (status) => {

    if (status === "dang_hoc") {

        return "Đang học"

    }


    if (status === "da_roi_lop") {

        return "Đã rời lớp"

    }


    return "Không xác định"

}


/* =====================================================
   DATE
===================================================== */

const formatDate = (date) => {

    if (!date) {

        return "Chưa cập nhật"

    }


    const value = new Date(date)


    if (Number.isNaN(value.getTime())) {

        return "Chưa cập nhật"

    }


    return value.toLocaleDateString(
        "vi-VN"
    )

}


/* =====================================================
   BACK HOME
===================================================== */

const goHome = () => {

    router.push(
        "/sinh-vien/trang-chu"
    )

}


/* =====================================================
   VIEW CLASS
===================================================== */

const viewClass = (item) => {

    router.push(
        `/sinh-vien/lop-hoc/${item.id}`
    )

}


/* =====================================================
   INIT
===================================================== */

onMounted(() => {

    getLopHoc()

})
</script>


<style scoped>

/* =====================================================
   PAGE
===================================================== */

.class-page {
    min-height: calc(100vh - 65px);

    box-sizing: border-box;

    padding:
        28px 32px 45px;

    color: #0f172a;

    background: #f6f8fc;
}


/* =====================================================
   HEADER
===================================================== */

.page-header {
    position: relative;

    max-width: 1450px;

    min-height: 88px;

    margin:
        0 auto 25px;

    display: flex;

    align-items: center;

    justify-content: center;
}


.header-content {
    text-align: center;
}


.header-content h1 {
    margin: 0;

    font-size: 30px;

    font-weight: 800;

    letter-spacing: -0.5px;
}


.header-content p {
    margin:
        8px 0 0;

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

    transform:
        translateY(-50%);

    width: 46px;

    height: 46px;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 0;

    border:
        1px solid #dce3ed;

    border-radius: 13px;

    color: #334155;

    background: #ffffff;

    cursor: pointer;

    transition: 0.2s;
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
}


/* =====================================================
   STATISTICS
===================================================== */

.statistics {
    width: 100%;

    max-width: 1450px;

    margin:
        0 auto 20px;

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 18px;
}


.stat-card {
    display: flex;

    align-items: center;

    gap: 14px;

    padding: 18px;

    border:
        1px solid #e3e8f0;

    border-radius: 15px;

    background: #ffffff;
}


.stat-icon {
    width: 48px;

    height: 48px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;
}


.stat-icon svg {
    width: 23px;

    height: 23px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;
}


.stat-icon.blue {
    color: #2563eb;

    background: #eff6ff;
}


.stat-icon.green {
    color: #16a34a;

    background: #f0fdf4;
}


.stat-card div:last-child {
    display: flex;

    flex-direction: column;

    gap: 3px;
}


.stat-card span {
    color: #64748b;

    font-size: 12px;
}


.stat-card strong {
    font-size: 23px;

    font-weight: 800;
}


/* =====================================================
   TOOLBAR
===================================================== */

.toolbar {
    width: 100%;

    max-width: 1450px;

    margin:
        0 auto 23px;

    display: flex;

    gap: 12px;
}


.search-box {
    position: relative;

    flex: 1;

    display: flex;

    align-items: center;

    border:
        1px solid #dce3ed;

    border-radius: 12px;

    background: #ffffff;

    overflow: hidden;
}


.search-box > svg {
    width: 19px;

    height: 19px;

    margin-left: 15px;

    flex-shrink: 0;

    fill: none;

    stroke: #94a3b8;

    stroke-width: 2;

    stroke-linecap: round;
}


.search-box input {
    width: 100%;

    padding:
        13px 42px 13px 11px;

    border: 0;

    outline: none;

    color: #0f172a;

    background: transparent;

    font-size: 13px;
}


.clear-search {
    position: absolute;

    right: 10px;

    border: 0;

    color: #94a3b8;

    background: transparent;

    font-size: 21px;

    cursor: pointer;
}


.toolbar select {
    min-width: 180px;

    padding:
        0 13px;

    border:
        1px solid #dce3ed;

    border-radius: 12px;

    outline: none;

    color: #334155;

    background: #ffffff;
}


/* =====================================================
   GRID
===================================================== */

.class-grid {
    width: 100%;

    max-width: 1450px;

    margin: 0 auto;

    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(0, 1fr)
        );

    gap: 20px;
}


/* =====================================================
   CLASS CARD
===================================================== */

.class-card {
    min-width: 0;

    display: flex;

    flex-direction: column;

    padding: 21px;

    border:
        1px solid #e2e8f0;

    border-radius: 17px;

    background: #ffffff;

    transition:
        transform 0.2s,
        box-shadow 0.2s,
        border-color 0.2s;
}


.class-card:hover {
    transform:
        translateY(-3px);

    border-color: #bfdbfe;

    box-shadow:
        0 12px 30px
        rgba(15, 23, 42, 0.08);
}


/* =====================================================
   CARD TOP
===================================================== */

.card-top {
    display: flex;

    align-items: center;

    justify-content:
        space-between;

    gap: 10px;
}


.class-icon {
    width: 45px;

    height: 45px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    color: #2563eb;

    background: #eff6ff;

    font-size: 13px;

    font-weight: 800;
}


.status-badge {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding:
        6px 10px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 700;
}


.status-badge.active {
    color: #15803d;

    background: #f0fdf4;
}


.status-badge.inactive {
    color: #64748b;

    background: #f1f5f9;
}


.status-dot {
    width: 6px;

    height: 6px;

    border-radius: 50%;

    background: currentColor;
}


/* =====================================================
   CONTENT
===================================================== */

.card-content {
    margin-top: 18px;
}


.class-code {
    color: #2563eb;

    font-size: 12px;

    font-weight: 800;
}


.card-content h2 {
    min-height: 48px;

    margin:
        5px 0 7px;

    font-size: 18px;

    line-height: 1.35;

    font-weight: 800;
}


.description {
    min-height: 42px;

    margin: 0;

    color: #64748b;

    font-size: 12px;

    line-height: 1.6;

    display: -webkit-box;

    -webkit-line-clamp: 2;

    -webkit-box-orient: vertical;

    overflow: hidden;
}


/* =====================================================
   INFO
===================================================== */

.class-info {
    display: grid;

    gap: 13px;

    margin-top: 19px;

    padding:
        17px 0;

    border-top:
        1px solid #edf0f5;

    border-bottom:
        1px solid #edf0f5;
}


.info-row {
    display: flex;

    align-items: center;

    gap: 10px;
}


.info-icon {
    width: 32px;

    height: 32px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    color: #64748b;

    background: #f8fafc;
}


.info-icon svg {
    width: 16px;

    height: 16px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;
}


.info-row > div:last-child {
    min-width: 0;

    display: flex;

    flex-direction: column;

    gap: 2px;
}


.info-row span {
    color: #94a3b8;

    font-size: 10px;
}


.info-row strong {
    overflow: hidden;

    color: #334155;

    font-size: 12px;

    font-weight: 700;

    white-space: nowrap;

    text-overflow: ellipsis;
}


/* =====================================================
   FOOTER
===================================================== */

.card-footer {
    margin-top: auto;

    padding-top: 18px;
}


.view-button {
    width: 100%;

    min-height: 42px;

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 8px;

    border:
        1px solid #dbeafe;

    border-radius: 10px;

    color: #2563eb;

    background: #eff6ff;

    font-size: 12px;

    font-weight: 800;

    cursor: pointer;

    transition: 0.2s;
}


.view-button svg {
    width: 16px;

    height: 16px;

    fill: none;

    stroke: currentColor;

    stroke-width: 2;

    stroke-linecap: round;

    stroke-linejoin: round;
}


.view-button:hover {
    color: #ffffff;

    border-color: #2563eb;

    background: #2563eb;
}


/* =====================================================
   EMPTY / LOADING
===================================================== */

.state-box {
    max-width: 1450px;

    min-height: 320px;

    box-sizing: border-box;

    margin: 0 auto;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    padding: 30px;

    border:
        1px solid #e2e8f0;

    border-radius: 17px;

    text-align: center;

    background: #ffffff;
}


.state-icon {
    width: 58px;

    height: 58px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-bottom: 15px;

    border-radius: 50%;

    color: #2563eb;

    background: #eff6ff;
}


.state-icon svg {
    width: 27px;

    height: 27px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;
}


.error-icon {
    color: #dc2626;

    background: #fef2f2;

    font-size: 24px;

    font-weight: 800;
}


.state-box strong {
    font-size: 16px;
}


.state-box p {
    margin:
        7px 0 0;

    color: #64748b;

    font-size: 12px;
}


.retry-button {
    margin-top: 16px;

    padding:
        9px 18px;

    border: 0;

    border-radius: 9px;

    color: #ffffff;

    background: #2563eb;

    cursor: pointer;
}


.spinner {
    width: 30px;

    height: 30px;

    margin-bottom: 16px;

    border:
        3px solid #dbeafe;

    border-top-color: #2563eb;

    border-radius: 50%;

    animation:
        spin 0.7s linear infinite;
}


@keyframes spin {

    to {
        transform:
            rotate(360deg);
    }

}


/* =====================================================
   DARK MODE
===================================================== */

:global(html.dark-theme)
.class-page {
    color: #f8fafc;

    background: #1f2124;
}


:global(html.dark-theme)
.header-content p {
    color: #94a3b8;
}


:global(html.dark-theme)
.back-button,
:global(html.dark-theme)
.stat-card,
:global(html.dark-theme)
.class-card,
:global(html.dark-theme)
.state-box,
:global(html.dark-theme)
.search-box {
    border-color:
        rgba(
            255,
            255,
            255,
            0.11
        );

    background: #111214;
}


:global(html.dark-theme)
.back-button {
    color: #e2e8f0;
}


:global(html.dark-theme)
.back-button:hover {
    color: #ffffff;

    border-color: #2563eb;

    background: #2563eb;
}


:global(html.dark-theme)
.toolbar select {
    color: #e2e8f0;

    border-color:
        rgba(
            255,
            255,
            255,
            0.11
        );

    background: #111214;
}


:global(html.dark-theme)
.search-box input {
    color: #ffffff;
}


:global(html.dark-theme)
.class-info {
    border-color:
        rgba(
            255,
            255,
            255,
            0.09
        );
}


:global(html.dark-theme)
.info-icon {
    color: #94a3b8;

    background: #1c1e21;
}


:global(html.dark-theme)
.info-row strong {
    color: #e2e8f0;
}


:global(html.dark-theme)
.description,
:global(html.dark-theme)
.state-box p {
    color: #94a3b8;
}


:global(html.dark-theme)
.class-icon {
    color: #60a5fa;

    background:
        rgba(
            37,
            99,
            235,
            0.13
        );
}


:global(html.dark-theme)
.status-badge.active {
    color: #4ade80;

    background:
        rgba(
            34,
            197,
            94,
            0.1
        );
}


:global(html.dark-theme)
.status-badge.inactive {
    color: #94a3b8;

    background: #1c1e21;
}


:global(html.dark-theme)
.view-button {
    color: #60a5fa;

    border-color:
        rgba(
            59,
            130,
            246,
            0.2
        );

    background:
        rgba(
            37,
            99,
            235,
            0.1
        );
}


:global(html.dark-theme)
.view-button:hover {
    color: #ffffff;

    border-color: #2563eb;

    background: #2563eb;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (
    max-width: 1100px
) {

    .class-grid {
        grid-template-columns:
            repeat(
                2,
                minmax(0, 1fr)
            );
    }

}


@media (
    max-width: 700px
) {

    .class-page {
        padding:
            18px 16px 30px;
    }


    .page-header {
        padding:
            0 55px;
    }


    .header-content h1 {
        font-size: 23px;
    }


    .statistics {
        grid-template-columns: 1fr;
    }


    .toolbar {
        flex-direction: column;
    }


    .toolbar select {
        min-height: 45px;
    }


    .class-grid {
        grid-template-columns: 1fr;
    }

}


@media (
    max-width: 480px
) {

    .header-content p {
        display: none;
    }


    .page-header {
        min-height: 65px;
    }

}

</style>