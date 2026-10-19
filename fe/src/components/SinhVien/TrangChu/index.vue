<template>
  <div class="dashboard">

    <!-- =====================================================
         HEADER
    ====================================================== -->
    <div class="page-header">

      <div>
        <span class="eyebrow">
          SINH VIÊN
        </span>

        <h1>
          Xin chào, {{ userName }} 👋
        </h1>

        <p>
          Theo dõi lớp học, bài tập và kết quả học tập của bạn.
        </p>
      </div>


      <div class="date-box">
        {{ currentDate }}
      </div>

    </div>


    <!-- =====================================================
         THỐNG KÊ
    ====================================================== -->
    <div class="stats-grid">

      <!-- LỚP HỌC -->
      <router-link
        to="/sinh-vien/lop-hoc"
        class="stat-card"
      >
        <div class="stat-icon blue">
          LH
        </div>

        <div class="stat-content">
          <span>Lớp đang học</span>

          <strong>
            {{ stats.lop_hoc }}
          </strong>
        </div>
      </router-link>


      <!-- BÀI TẬP -->
      <router-link
        to="/sinh-vien/bai-tap"
        class="stat-card"
      >
        <div class="stat-icon orange">
          BT
        </div>

        <div class="stat-content">
          <span>Bài tập</span>

          <strong>
            {{ stats.bai_tap }}
          </strong>
        </div>
      </router-link>


      <!-- BÀI NỘP -->
      <router-link
        to="/sinh-vien/bai-nop"
        class="stat-card"
      >
        <div class="stat-icon green">
          BN
        </div>

        <div class="stat-content">
          <span>Đã nộp</span>

          <strong>
            {{ stats.bai_nop }}
          </strong>
        </div>
      </router-link>


      <!-- ĐIỂM -->
      <router-link
        to="/sinh-vien/diem"
        class="stat-card"
      >
        <div class="stat-icon purple">
          Đ
        </div>

        <div class="stat-content">
          <span>Đã có điểm</span>

          <strong>
            {{ stats.diem }}
          </strong>
        </div>
      </router-link>

    </div>


    <!-- =====================================================
         LOADING
    ====================================================== -->
    <div
      v-if="loading"
      class="loading-box"
    >
      <div class="spinner"></div>

      <span>
        Đang tải dữ liệu...
      </span>
    </div>


    <!-- =====================================================
         NỘI DUNG
    ====================================================== -->
    <div
      v-else
      class="content-grid"
    >

      <!-- =================================================
           BÀI TẬP GẦN ĐÂY
      ================================================== -->
      <section class="panel">

        <div class="panel-header">

          <div>
            <h2>
              Bài tập gần đây
            </h2>

            <p>
              Các bài tập bạn cần theo dõi
            </p>
          </div>


          <router-link
            to="/sinh-vien/bai-tap"
            class="view-all"
          >
            Xem tất cả
          </router-link>

        </div>


        <!-- CHƯA CÓ BÀI TẬP -->
        <div
          v-if="assignments.length === 0"
          class="empty"
        >

          <div class="empty-icon">
            ✓
          </div>

          <strong>
            Chưa có bài tập
          </strong>

          <span>
            Hiện tại bạn chưa có bài tập nào.
          </span>

        </div>


        <!-- DANH SÁCH BÀI TẬP -->
        <div
          v-else
          class="list"
        >

          <router-link
            v-for="item in assignments"
            :key="item.id"
            to="/sinh-vien/bai-tap"
            class="list-item"
          >

            <div class="item-icon orange">
              BT
            </div>


            <div class="item-info">

              <strong>
                {{ item.tieu_de }}
              </strong>

              <span>
                {{ item.ten_lop || "Lớp học" }}
              </span>

              <small>
                Hạn nộp:
                {{ formatDate(item.han_nop) }}
              </small>

            </div>


            <span
              class="assignment-status"
              :class="getAssignmentStatus(item).class"
            >
              {{ getAssignmentStatus(item).text }}
            </span>

          </router-link>

        </div>

      </section>


      <!-- =================================================
           LỚP HỌC CỦA TÔI
      ================================================== -->
      <section class="panel">

        <div class="panel-header">

          <div>
            <h2>
              Lớp học của tôi
            </h2>

            <p>
              Các lớp bạn đang tham gia
            </p>
          </div>


          <router-link
            to="/sinh-vien/lop-hoc"
            class="view-all"
          >
            Xem tất cả
          </router-link>

        </div>


        <!-- CHƯA CÓ LỚP -->
        <div
          v-if="classes.length === 0"
          class="empty"
        >

          <div class="empty-icon">
            LH
          </div>

          <strong>
            Chưa tham gia lớp học
          </strong>

          <span>
            Bạn chưa có lớp học nào.
          </span>

        </div>


        <!-- DANH SÁCH LỚP -->
        <div
          v-else
          class="list"
        >

          <router-link
            v-for="item in classes"
            :key="item.id"
            to="/sinh-vien/lop-hoc"
            class="list-item class-link"
          >

            <div class="item-icon blue">
              LH
            </div>


            <div class="item-info">

              <strong>
                {{ item.ten_lop }}
              </strong>

              <span>
                {{ item.ma_lop }}
              </span>

              <small>
                Giảng viên:
                {{
                  item.giang_vien?.ho_ten ||
                  item.ten_giang_vien ||
                  "Chưa cập nhật"
                }}
              </small>

            </div>


            <span class="list-arrow">
              ›
            </span>

          </router-link>

        </div>

      </section>

    </div>

  </div>
</template>


<script setup>
import {
  computed,
  onMounted,
  ref,
} from "vue"

import api from "../../../services/api"


/* =====================================================
   USER
===================================================== */

const user = computed(() => {

  const raw =
    sessionStorage.getItem("auth_user") ||
    localStorage.getItem("auth_user") ||
    sessionStorage.getItem("nguoi_dung") ||
    localStorage.getItem("nguoi_dung")

  if (!raw) {
    return null
  }

  try {

    return JSON.parse(raw)

  } catch (error) {

    console.error(
      "Không đọc được thông tin người dùng:",
      error
    )

    return null
  }

})


/* =====================================================
   TÊN SINH VIÊN
===================================================== */

const userName = computed(() => {

  return (
    user.value?.ho_ten ||
    "Sinh viên"
  )

})


/* =====================================================
   NGÀY HIỆN TẠI
===================================================== */

const currentDate = computed(() => {

  return new Intl.DateTimeFormat(
    "vi-VN",
    {
      weekday: "long",
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    }
  ).format(new Date())

})


/* =====================================================
   LOADING
===================================================== */

const loading = ref(false)


/* =====================================================
   THỐNG KÊ
===================================================== */

const stats = ref({

  lop_hoc: 0,

  bai_tap: 0,

  bai_nop: 0,

  diem: 0,

})


/* =====================================================
   BÀI TẬP GẦN ĐÂY
===================================================== */

const assignments = ref([])


/* =====================================================
   LỚP HỌC
===================================================== */

const classes = ref([])


/* =====================================================
   LOAD DATA KHI MỞ TRANG
===================================================== */

onMounted(() => {

  loadDashboard()

})


/* =====================================================
   API TRANG CHỦ SINH VIÊN
===================================================== */

async function loadDashboard() {

  loading.value = true

  try {

    /*
     * api.js của bạn đã cấu hình baseURL /api
     *
     * URL thực tế:
     * GET /api/sinh-vien/trang-chu/get-data
     */

    const response = await api.get(
      "/sinh-vien/trang-chu/get-data"
    )


    /*
     * Backend trả:
     *
     * {
     *   status: true,
     *   message: "...",
     *   data: {
     *      thong_ke: {...},
     *      bai_tap_gan_day: [...],
     *      lop_hoc: [...]
     *   }
     * }
     */

    const data =
      response.data?.data || {}


    /* ============================
       THỐNG KÊ
    ============================ */

    stats.value = {

      lop_hoc:
        Number(
          data.thong_ke?.lop_hoc ?? 0
        ),

      bai_tap:
        Number(
          data.thong_ke?.bai_tap ?? 0
        ),

      bai_nop:
        Number(
          data.thong_ke?.bai_nop ?? 0
        ),

      diem:
        Number(
          data.thong_ke?.diem ?? 0
        ),

    }


    /* ============================
       BÀI TẬP GẦN ĐÂY
    ============================ */

    assignments.value =
      Array.isArray(
        data.bai_tap_gan_day
      )
        ? data.bai_tap_gan_day
        : []


    /* ============================
       LỚP HỌC
    ============================ */

    classes.value =
      Array.isArray(
        data.lop_hoc
      )
        ? data.lop_hoc
        : []

  } catch (error) {

    console.error(
      "Không thể tải dữ liệu trang chủ sinh viên:",
      error.response?.data ||
      error.message ||
      error
    )


    /*
     * Nếu API lỗi thì reset dữ liệu
     */

    stats.value = {
      lop_hoc: 0,
      bai_tap: 0,
      bai_nop: 0,
      diem: 0,
    }

    assignments.value = []

    classes.value = []

  } finally {

    loading.value = false

  }

}


/* =====================================================
   FORMAT NGÀY
===================================================== */

function formatDate(value) {

  if (!value) {
    return "Chưa có"
  }


  const date =
    new Date(value)


  /*
   * Kiểm tra ngày không hợp lệ
   */

  if (
    Number.isNaN(
      date.getTime()
    )
  ) {

    return "Chưa có"

  }


  return date.toLocaleString(
    "vi-VN",
    {
      hour: "2-digit",
      minute: "2-digit",
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    }
  )

}


/* =====================================================
   TRẠNG THÁI BÀI TẬP
===================================================== */

function getAssignmentStatus(item) {

  /*
   * Đã nộp
   */

  if (item?.da_nop) {

    return {
      text: "Đã nộp",
      class: "submitted",
    }

  }


  /*
   * Không có hạn nộp
   */

  if (!item?.han_nop) {

    return {
      text: "Chưa nộp",
      class: "pending",
    }

  }


  /*
   * Kiểm tra hạn nộp
   */

  const deadline =
    new Date(item.han_nop)


  /*
   * Nếu ngày không hợp lệ
   */

  if (
    Number.isNaN(
      deadline.getTime()
    )
  ) {

    return {
      text: "Chưa nộp",
      class: "pending",
    }

  }


  /*
   * Quá hạn
   */

  if (
    deadline.getTime() <
    Date.now()
  ) {

    return {
      text: "Quá hạn",
      class: "late",
    }

  }


  /*
   * Còn hạn nhưng chưa nộp
   */

  return {
    text: "Chưa nộp",
    class: "pending",
  }

}
</script>


<style scoped>

/* =====================================================
   DASHBOARD
===================================================== */

.dashboard {
  width: 100%;

  min-height: calc(100vh - 65px);

  box-sizing: border-box;

  padding: 27px;

  color: #172033;

  background: #f5f7fb;

  font-family:
    Inter,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}


/* =====================================================
   HEADER
===================================================== */

.page-header {
  display: flex;

  align-items: flex-start;

  justify-content: space-between;

  gap: 20px;

  margin-bottom: 26px;
}


.eyebrow {
  color: #2563eb;

  font-size: 10px;

  font-weight: 900;

  letter-spacing: 1px;
}


.page-header h1 {
  margin: 7px 0 5px;

  color: #172033;

  font-size: 29px;

  font-weight: 800;
}


.page-header p {
  margin: 0;

  color: #64748b;

  font-size: 13px;
}


.date-box {
  flex-shrink: 0;

  padding: 10px 14px;

  border: 1px solid #e5e7eb;

  border-radius: 11px;

  color: #64748b;

  background: #ffffff;

  font-size: 11px;

  font-weight: 600;

  box-shadow:
    0 2px 5px
    rgba(15, 23, 42, 0.03);
}


/* =====================================================
   STATS
===================================================== */

.stats-grid {
  display: grid;

  grid-template-columns:
    repeat(4, minmax(0, 1fr));

  gap: 15px;

  margin-bottom: 20px;
}


.stat-card {
  min-height: 92px;

  display: flex;

  align-items: center;

  gap: 13px;

  box-sizing: border-box;

  padding: 18px;

  border: 1px solid #e5e7eb;

  border-radius: 15px;

  color: #172033;

  background: #ffffff;

  text-decoration: none;

  box-shadow:
    0 2px 8px
    rgba(15, 23, 42, 0.04);

  transition: 0.18s ease;
}


.stat-card:hover {
  transform: translateY(-2px);

  border-color: #bfdbfe;

  box-shadow:
    0 8px 20px
    rgba(15, 23, 42, 0.07);
}


.stat-icon {
  width: 47px;

  height: 47px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 12px;

  font-size: 11px;

  font-weight: 900;
}


.stat-content {
  min-width: 0;

  display: flex;

  flex-direction: column;
}


.stat-content span {
  color: #64748b;

  font-size: 10px;
}


.stat-content strong {
  margin-top: 3px;

  color: #172033;

  font-size: 22px;

  font-weight: 800;
}


/* =====================================================
   ICON COLORS
===================================================== */

.blue {
  color: #2563eb;

  background: #eff6ff;
}


.orange {
  color: #ea580c;

  background: #fff7ed;
}


.green {
  color: #16a34a;

  background: #f0fdf4;
}


.purple {
  color: #9333ea;

  background: #faf5ff;
}


/* =====================================================
   CONTENT
===================================================== */

.content-grid {
  display: grid;

  grid-template-columns:
    minmax(0, 1.2fr)
    minmax(0, 0.9fr);

  gap: 18px;
}


.panel {
  min-height: 330px;

  box-sizing: border-box;

  padding: 20px;

  border: 1px solid #e5e7eb;

  border-radius: 16px;

  background: #ffffff;

  box-shadow:
    0 2px 8px
    rgba(15, 23, 42, 0.04);
}


.panel-header {
  display: flex;

  align-items: flex-start;

  justify-content: space-between;

  gap: 15px;

  padding-bottom: 16px;

  border-bottom: 1px solid #e5e7eb;
}


.panel-header h2 {
  margin: 0;

  color: #172033;

  font-size: 15px;

  font-weight: 800;
}


.panel-header p {
  margin: 5px 0 0;

  color: #64748b;

  font-size: 10px;
}


.view-all {
  flex-shrink: 0;

  color: #2563eb;

  font-size: 10px;

  font-weight: 700;

  text-decoration: none;
}


.view-all:hover {
  text-decoration: underline;
}


/* =====================================================
   EMPTY
===================================================== */

.empty {
  min-height: 230px;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  text-align: center;
}


.empty-icon {
  width: 45px;

  height: 45px;

  display: flex;

  align-items: center;

  justify-content: center;

  margin-bottom: 10px;

  border-radius: 50%;

  color: #64748b;

  background: #f1f5f9;

  font-size: 10px;

  font-weight: 800;
}


.empty strong {
  color: #172033;

  font-size: 11px;
}


.empty span {
  margin-top: 5px;

  color: #94a3b8;

  font-size: 9px;
}


/* =====================================================
   LIST
===================================================== */

.list {
  display: flex;

  flex-direction: column;

  gap: 8px;

  margin-top: 13px;
}


.list-item {
  display: flex;

  align-items: center;

  gap: 10px;

  box-sizing: border-box;

  padding: 11px;

  border: 1px solid #e5e7eb;

  border-radius: 11px;

  color: inherit;

  background: #ffffff;

  text-decoration: none;

  transition: 0.18s ease;
}


.class-link:hover,
.list-item:hover {
  border-color: #bfdbfe;

  background: #f8fafc;
}


.item-icon {
  width: 37px;

  height: 37px;

  flex-shrink: 0;

  display: flex;

  align-items: center;

  justify-content: center;

  border-radius: 9px;

  font-size: 9px;

  font-weight: 900;
}


.item-info {
  min-width: 0;

  flex: 1;

  display: flex;

  flex-direction: column;
}


.item-info strong {
  overflow: hidden;

  color: #172033;

  font-size: 10px;

  text-overflow: ellipsis;

  white-space: nowrap;
}


.item-info span {
  margin-top: 3px;

  color: #64748b;

  font-size: 8px;
}


.item-info small {
  margin-top: 4px;

  color: #94a3b8;

  font-size: 8px;
}


.list-arrow {
  flex-shrink: 0;

  color: #94a3b8;

  font-size: 21px;
}


/* =====================================================
   ASSIGNMENT STATUS
===================================================== */

.assignment-status {
  flex-shrink: 0;

  padding: 5px 8px;

  border-radius: 999px;

  font-size: 8px;

  font-weight: 800;
}


.assignment-status.submitted {
  color: #16a34a;

  background: #f0fdf4;
}


.assignment-status.pending {
  color: #ea580c;

  background: #fff7ed;
}


.assignment-status.late {
  color: #dc2626;

  background: #fef2f2;
}


/* =====================================================
   LOADING
===================================================== */

.loading-box {
  min-height: 350px;

  display: flex;

  flex-direction: column;

  align-items: center;

  justify-content: center;

  gap: 12px;

  color: #64748b;

  font-size: 12px;
}


.spinner {
  width: 28px;

  height: 28px;

  border: 3px solid #dbeafe;

  border-top-color: #2563eb;

  border-radius: 50%;

  animation: spin 0.7s linear infinite;
}


@keyframes spin {

  to {
    transform: rotate(360deg);
  }

}


/* =====================================================
   DARK MODE
===================================================== */

:global(html.dark-theme) .dashboard {
  color: #ffffff;

  background: #1f2124;
}


:global(html.dark-theme) .page-header h1 {
  color: #ffffff;
}


:global(html.dark-theme) .page-header p {
  color: #94a3b8;
}


:global(html.dark-theme) .eyebrow {
  color: #60a5fa;
}


:global(html.dark-theme) .date-box {
  color: #94a3b8;

  border-color:
    rgba(255, 255, 255, 0.15);

  background: #111111;

  box-shadow: none;
}


:global(html.dark-theme) .stat-card {
  color: #ffffff;

  border-color:
    rgba(255, 255, 255, 0.16);

  background: #111111;

  box-shadow: none;
}


:global(html.dark-theme) .stat-card:hover {
  border-color:
    rgba(96, 165, 250, 0.4);
}


:global(html.dark-theme) .stat-content span {
  color: #94a3b8;
}


:global(html.dark-theme) .stat-content strong {
  color: #ffffff;
}


:global(html.dark-theme) .blue {
  color: #60a5fa;

  background:
    rgba(37, 99, 235, 0.13);
}


:global(html.dark-theme) .orange {
  color: #f97316;

  background:
    rgba(234, 88, 12, 0.13);
}


:global(html.dark-theme) .green {
  color: #22c55e;

  background:
    rgba(22, 163, 74, 0.13);
}


:global(html.dark-theme) .purple {
  color: #c084fc;

  background:
    rgba(147, 51, 234, 0.13);
}


:global(html.dark-theme) .panel {
  border-color:
    rgba(255, 255, 255, 0.16);

  background: #111111;

  box-shadow: none;
}


:global(html.dark-theme) .panel-header {
  border-bottom-color:
    rgba(255, 255, 255, 0.16);
}


:global(html.dark-theme) .panel-header h2 {
  color: #ffffff;
}


:global(html.dark-theme) .panel-header p {
  color: #94a3b8;
}


:global(html.dark-theme) .view-all {
  color: #60a5fa;
}


:global(html.dark-theme) .empty-icon {
  color: #94a3b8;

  background: #202327;
}


:global(html.dark-theme) .empty strong {
  color: #ffffff;
}


:global(html.dark-theme) .empty span {
  color: #94a3b8;
}


:global(html.dark-theme) .list-item {
  border-color:
    rgba(255, 255, 255, 0.08);

  background: transparent;
}


:global(html.dark-theme) .list-item:hover {
  border-color:
    rgba(96, 165, 250, 0.25);

  background:
    rgba(255, 255, 255, 0.03);
}


:global(html.dark-theme) .item-info strong {
  color: #ffffff;
}


:global(html.dark-theme) .item-info span {
  color: #94a3b8;
}


:global(html.dark-theme) .item-info small {
  color: #64748b;
}


:global(html.dark-theme)
.assignment-status.submitted {
  color: #22c55e;

  background:
    rgba(22, 163, 74, 0.13);
}


:global(html.dark-theme)
.assignment-status.pending {
  color: #f97316;

  background:
    rgba(234, 88, 12, 0.13);
}


:global(html.dark-theme)
.assignment-status.late {
  color: #f87171;

  background:
    rgba(220, 38, 38, 0.13);
}


:global(html.dark-theme) .loading-box {
  color: #94a3b8;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {

  .stats-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }

}


@media (max-width: 900px) {

  .content-grid {
    grid-template-columns: 1fr;
  }

}


@media (max-width: 600px) {

  .dashboard {
    padding: 15px;
  }


  .page-header {
    flex-direction: column;
  }


  .stats-grid {
    grid-template-columns: 1fr;
  }


  .date-box {
    width: 100%;

    box-sizing: border-box;
  }

}
</style>