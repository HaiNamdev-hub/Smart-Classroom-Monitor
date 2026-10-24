<template>
  <div class="assignment-page">

    <!-- HEADER -->
<div class="page-header">

  <div class="header-content">
    <span class="eyebrow">SINH VIÊN</span>

    <h1>Bài tập</h1>

    <p>
      Xem và theo dõi các bài tập được giao trong các lớp học của bạn.
    </p>
  </div>

</div>


    <!-- THỐNG KÊ -->
    <div class="stats-grid">

      <div class="stat-card">
        <div class="stat-icon blue">
          BT
        </div>

        <div class="stat-content">
          <span>Tổng bài tập</span>
          <strong>{{ statistics.total }}</strong>
        </div>
      </div>


      <div class="stat-card">
        <div class="stat-icon orange">
          CN
        </div>

        <div class="stat-content">
          <span>Chưa nộp</span>
          <strong>{{ statistics.pending }}</strong>
        </div>
      </div>


      <div class="stat-card">
        <div class="stat-icon green">
          DN
        </div>

        <div class="stat-content">
          <span>Đã nộp</span>
          <strong>{{ statistics.submitted }}</strong>
        </div>
      </div>


      <div class="stat-card">
        <div class="stat-icon red">
          QH
        </div>

        <div class="stat-content">
          <span>Quá hạn</span>
          <strong>{{ statistics.overdue }}</strong>
        </div>
      </div>

    </div>


    <!-- TOOLBAR -->
    <section class="toolbar">

      <!-- SEARCH -->
      <div class="search-box">
        <span class="search-icon">⌕</span>

        <input
          v-model="search"
          type="text"
          placeholder="Tìm kiếm bài tập..."
        />
      </div>


      <!-- LỌC LỚP -->
      <select
        v-model="selectedClass"
        class="filter-select"
      >
        <option value="">
          Tất cả lớp học
        </option>

        <option
          v-for="item in classOptions"
          :key="item"
          :value="item"
        >
          {{ item }}
        </option>
      </select>


      <!-- LỌC TRẠNG THÁI -->
      <select
        v-model="selectedStatus"
        class="filter-select"
      >
        <option value="">
          Tất cả trạng thái
        </option>

        <option value="pending">
          Chưa nộp
        </option>

        <option value="submitted">
          Đã nộp
        </option>

        <option value="overdue">
          Quá hạn
        </option>
      </select>

    </section>


    <!-- LOADING -->
    <div
      v-if="loading"
      class="state-box"
    >
      <div class="loading-circle"></div>

      <strong>Đang tải bài tập...</strong>
    </div>


    <!-- KHÔNG CÓ DỮ LIỆU -->
    <div
      v-else-if="filteredAssignments.length === 0"
      class="state-box"
    >
      <div class="empty-icon">
        BT
      </div>

      <strong>Không có bài tập</strong>

      <span>
        Không tìm thấy bài tập phù hợp.
      </span>
    </div>


    <!-- DANH SÁCH -->
    <div
      v-else
      class="assignment-list"
    >

      <article
        v-for="item in filteredAssignments"
        :key="item.id"
        class="assignment-card"
      >

        <!-- TRÁI -->
        <div class="assignment-icon orange">
          BT
        </div>


        <!-- NỘI DUNG -->
        <div class="assignment-content">

          <div class="assignment-title-row">

            <div>
              <h3>
                {{ item.tieu_de }}
              </h3>

              <div class="class-name">
                {{ item.ten_lop || "Lớp học" }}

                <span v-if="item.ma_lop">
                  • {{ item.ma_lop }}
                </span>
              </div>
            </div>


            <span
              class="status"
              :class="getAssignmentStatus(item).class"
            >
              {{ getAssignmentStatus(item).text }}
            </span>

          </div>


          <p
            v-if="item.mo_ta"
            class="description"
          >
            {{ item.mo_ta }}
          </p>


          <div class="assignment-info">

            <div class="info-item">
              <span>Bắt đầu</span>

              <strong>
                {{ formatDate(item.thoi_gian_bat_dau) }}
              </strong>
            </div>


            <div class="info-item">
              <span>Hạn nộp</span>

              <strong>
                {{ formatDate(item.han_nop) }}
              </strong>
            </div>


            <div class="info-item">
              <span>Nộp trễ</span>

              <strong>
                {{
                  item.cho_phep_nop_tre
                    ? "Cho phép"
                    : "Không cho phép"
                }}
              </strong>
            </div>

          </div>


          <!-- FOOTER -->
          <div class="assignment-footer">

            <span class="deadline">
              Hạn nộp:
              <strong>
                {{ formatDate(item.han_nop) }}
              </strong>
            </span>


            <button
              type="button"
              class="detail-button"
              @click="viewAssignment(item)"
            >
              Xem bài tập
              <span>›</span>
            </button>

          </div>

        </div>

      </article>

    </div>


    <!-- ERROR -->
    <div
      v-if="errorMessage"
      class="error-message"
    >
      {{ errorMessage }}
    </div>

  </div>
</template>


<script setup>
import {
  computed,
  onMounted,
  ref,
} from "vue";

import { useRouter } from "vue-router";

import api from "../../../services/api";


/* =====================================================
   ROUTER
===================================================== */

const router = useRouter();


/* =====================================================
   DATA
===================================================== */

const loading = ref(false);

const assignments = ref([]);

const errorMessage = ref("");

const search = ref("");

const selectedClass = ref("");

const selectedStatus = ref("");


/* =====================================================
   LOAD DATA
===================================================== */

onMounted(() => {
  loadAssignments();
});


async function loadAssignments() {

  loading.value = true;

  errorMessage.value = "";

  try {

    const response = await api.get(
      "/sinh-vien/bai-tap"
    );

    /*
      API có thể trả:

      {
        status: true,
        data: [...]
      }

      hoặc:

      {
        status: true,
        data: {
          bai_tap: [...]
        }
      }
    */

    const data = response.data?.data;

    if (Array.isArray(data)) {

      assignments.value = data;

    } else {

      assignments.value =
        data?.bai_tap || [];

    }

  } catch (error) {

    console.error(
      "Không thể tải danh sách bài tập:",
      error.response?.data || error
    );

    errorMessage.value =
      error.response?.data?.message ||
      "Không thể tải danh sách bài tập.";

  } finally {

    loading.value = false;

  }

}


/* =====================================================
   TRẠNG THÁI
===================================================== */

function getAssignmentStatus(item) {

  /*
    Backend có thể trả trực tiếp da_nop.
  */

  if (
    item.da_nop === true ||
    item.da_nop === 1
  ) {

    return {
      text: "Đã nộp",
      class: "submitted",
      value: "submitted",
    };

  }


  /*
    Nếu chưa có hạn nộp
  */

  if (!item.han_nop) {

    return {
      text: "Chưa nộp",
      class: "pending",
      value: "pending",
    };

  }


  const deadline =
    new Date(item.han_nop);


  /*
    Đã quá hạn
  */

  if (deadline < new Date()) {

    return {
      text: "Quá hạn",
      class: "overdue",
      value: "overdue",
    };

  }


  return {
    text: "Chưa nộp",
    class: "pending",
    value: "pending",
  };

}


/* =====================================================
   THỐNG KÊ
===================================================== */

const statistics = computed(() => {

  let pending = 0;

  let submitted = 0;

  let overdue = 0;


  assignments.value.forEach((item) => {

    const status =
      getAssignmentStatus(item).value;


    if (status === "submitted") {

      submitted++;

    } else if (status === "overdue") {

      overdue++;

    } else {

      pending++;

    }

  });


  return {

    total:
      assignments.value.length,

    pending,

    submitted,

    overdue,

  };

});


/* =====================================================
   DANH SÁCH LỚP
===================================================== */

const classOptions = computed(() => {

  const classes =
    assignments.value
      .map((item) => item.ten_lop)
      .filter(Boolean);


  return [
    ...new Set(classes),
  ];

});


/* =====================================================
   FILTER
===================================================== */

const filteredAssignments = computed(() => {

  const keyword =
    search.value
      .trim()
      .toLowerCase();


  return assignments.value.filter((item) => {

    const title =
      String(item.tieu_de || "")
        .toLowerCase();

    const className =
      String(item.ten_lop || "")
        .toLowerCase();

    const classCode =
      String(item.ma_lop || "")
        .toLowerCase();


    const matchSearch =
      !keyword ||
      title.includes(keyword) ||
      className.includes(keyword) ||
      classCode.includes(keyword);


    const matchClass =
      !selectedClass.value ||
      item.ten_lop === selectedClass.value;


    const status =
      getAssignmentStatus(item).value;


    const matchStatus =
      !selectedStatus.value ||
      status === selectedStatus.value;


    return (
      matchSearch &&
      matchClass &&
      matchStatus
    );

  });

});


/* =====================================================
   FORMAT DATE
===================================================== */

function formatDate(value) {

  if (!value) {

    return "Chưa có";

  }


  const date =
    new Date(value);


  if (Number.isNaN(date.getTime())) {

    return "Chưa có";

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
  );

}


/* =====================================================
   XEM BÀI TẬP
===================================================== */

function viewAssignment(item) {

  /*
    Sau này khi tạo trang chi tiết bài tập,
    có thể đổi thành:

    router.push(
      `/sinh-vien/bai-tap/${item.id}`
    );

    Hiện tại đưa sang trang Bài nộp.
  */

  router.push({
    path: "/sinh-vien/bai-nop",
    query: {
      bai_tap_id: item.id,
    },
  });

}
</script>


<style scoped>

/* =====================================================
   PAGE
===================================================== */

.assignment-page {
  min-height: calc(100vh - 65px);

  box-sizing: border-box;

  padding: 27px;

  background: #f5f7fb;

  color: #172033;

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
  position: relative;

  min-height: 90px;

  display: flex;
  align-items: center;
  justify-content: center;

  margin-bottom: 25px;
}


/* NÚT TRANG CHỦ BÊN TRÁI */
.back-button {
  position: absolute;

  left: 0;
  top: 50%;

  transform: translateY(-50%);

  padding: 10px 14px;

  background: #ffffff;

  border: 1px solid #e5e7eb;
  border-radius: 11px;

  color: #475569;

  font-size: 11px;
  font-weight: 700;

  text-decoration: none;

  transition: 0.18s ease;
}

.back-button:hover {
  border-color: #bfdbfe;
  color: #2563eb;
}


/* NỘI DUNG CHÍNH GIỮA */
.header-content {
  text-align: center;
}

.eyebrow {
  color: #2563eb;

  font-size: 10px;
  font-weight: 900;

  letter-spacing: 1px;
}

.header-content h1 {
  margin: 7px 0 5px;

  color: #172033;

  font-size: 29px;
  font-weight: 800;
}

.header-content p {
  margin: 0;

  color: #64748b;

  font-size: 13px;
}


.back-button {
  padding: 10px 14px;

  background: #ffffff;

  border: 1px solid #e5e7eb;
  border-radius: 11px;

  color: #475569;

  font-size: 11px;
  font-weight: 700;

  text-decoration: none;

  transition: 0.18s ease;
}


.back-button:hover {
  border-color: #bfdbfe;

  color: #2563eb;
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

  background: #ffffff;

  border: 1px solid #e5e7eb;
  border-radius: 15px;

  box-shadow:
    0 2px 8px rgba(15, 23, 42, 0.04);
}


.stat-icon {
  width: 47px;
  height: 47px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 12px;

  font-size: 10px;
  font-weight: 900;
}


.blue {
  background: #eff6ff;
  color: #2563eb;
}


.orange {
  background: #fff7ed;
  color: #ea580c;
}


.green {
  background: #f0fdf4;
  color: #16a34a;
}


.red {
  background: #fef2f2;
  color: #dc2626;
}


.stat-content {
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
   TOOLBAR
===================================================== */

.toolbar {
  display: grid;

  grid-template-columns:
    minmax(220px, 1fr)
    200px
    190px;

  gap: 12px;

  margin-bottom: 18px;

  padding: 16px;

  background: #ffffff;

  border: 1px solid #e5e7eb;
  border-radius: 15px;
}


.search-box {
  display: flex;
  align-items: center;

  gap: 8px;

  padding: 0 12px;

  background: #f8fafc;

  border: 1px solid #e5e7eb;
  border-radius: 10px;
}


.search-icon {
  color: #94a3b8;

  font-size: 18px;
}


.search-box input {
  width: 100%;

  padding: 11px 0;

  background: transparent;

  border: 0;
  outline: none;

  color: #172033;

  font-size: 11px;
}


.search-box input::placeholder {
  color: #94a3b8;
}


.filter-select {
  padding: 0 12px;

  background: #ffffff;

  border: 1px solid #e5e7eb;
  border-radius: 10px;

  outline: none;

  color: #475569;

  font-size: 11px;

  cursor: pointer;
}


/* =====================================================
   ASSIGNMENT LIST
===================================================== */

.assignment-list {
  display: flex;

  flex-direction: column;

  gap: 12px;
}


.assignment-card {
  display: flex;

  align-items: flex-start;

  gap: 15px;

  box-sizing: border-box;

  padding: 20px;

  background: #ffffff;

  border: 1px solid #e5e7eb;
  border-radius: 16px;

  box-shadow:
    0 2px 8px rgba(15, 23, 42, 0.04);

  transition: 0.18s ease;
}


.assignment-card:hover {
  transform: translateY(-1px);

  border-color: #bfdbfe;

  box-shadow:
    0 7px 20px rgba(15, 23, 42, 0.06);
}


.assignment-icon {
  width: 45px;
  height: 45px;

  flex-shrink: 0;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 12px;

  font-size: 10px;
  font-weight: 900;
}


.assignment-content {
  min-width: 0;

  flex: 1;
}


.assignment-title-row {
  display: flex;

  align-items: flex-start;
  justify-content: space-between;

  gap: 15px;
}


.assignment-title-row h3 {
  margin: 0;

  color: #172033;

  font-size: 14px;
  font-weight: 800;
}


.class-name {
  margin-top: 5px;

  color: #64748b;

  font-size: 10px;
}


.description {
  margin: 13px 0 0;

  color: #64748b;

  font-size: 11px;
  line-height: 1.6;
}


/* =====================================================
   STATUS
===================================================== */

.status {
  flex-shrink: 0;

  padding: 6px 10px;

  border-radius: 999px;

  font-size: 8px;
  font-weight: 800;
}


.status.submitted {
  background: #f0fdf4;

  color: #16a34a;
}


.status.pending {
  background: #fff7ed;

  color: #ea580c;
}


.status.overdue {
  background: #fef2f2;

  color: #dc2626;
}


/* =====================================================
   INFO
===================================================== */

.assignment-info {
  display: grid;

  grid-template-columns:
    repeat(3, minmax(0, 1fr));

  gap: 10px;

  margin-top: 15px;
}


.info-item {
  display: flex;

  flex-direction: column;

  gap: 4px;

  padding: 10px 12px;

  background: #f8fafc;

  border-radius: 10px;
}


.info-item span {
  color: #94a3b8;

  font-size: 8px;
}


.info-item strong {
  color: #475569;

  font-size: 9px;
  font-weight: 700;
}


/* =====================================================
   FOOTER
===================================================== */

.assignment-footer {
  display: flex;

  align-items: center;
  justify-content: space-between;

  gap: 15px;

  margin-top: 15px;

  padding-top: 14px;

  border-top: 1px solid #e5e7eb;
}


.deadline {
  color: #64748b;

  font-size: 9px;
}


.deadline strong {
  color: #475569;
}


.detail-button {
  display: flex;
  align-items: center;

  gap: 8px;

  padding: 8px 12px;

  background: #eff6ff;

  border: 1px solid #bfdbfe;
  border-radius: 9px;

  color: #2563eb;

  font-size: 9px;
  font-weight: 800;

  cursor: pointer;

  transition: 0.18s ease;
}


.detail-button:hover {
  background: #dbeafe;
}


/* =====================================================
   EMPTY / LOADING
===================================================== */

.state-box {
  min-height: 300px;

  display: flex;
  flex-direction: column;

  align-items: center;
  justify-content: center;

  padding: 30px;

  background: #ffffff;

  border: 1px solid #e5e7eb;
  border-radius: 16px;

  text-align: center;
}


.state-box strong {
  margin-top: 10px;

  color: #172033;

  font-size: 12px;
}


.state-box span {
  margin-top: 5px;

  color: #94a3b8;

  font-size: 10px;
}


.empty-icon {
  width: 48px;
  height: 48px;

  display: flex;
  align-items: center;
  justify-content: center;

  background: #f1f5f9;

  border-radius: 50%;

  color: #64748b;

  font-size: 10px;
  font-weight: 900;
}


.loading-circle {
  width: 28px;
  height: 28px;

  border: 3px solid #e5e7eb;
  border-top-color: #2563eb;
  border-radius: 50%;

  animation: spin 0.8s linear infinite;
}


@keyframes spin {

  to {
    transform: rotate(360deg);
  }

}


.error-message {
  margin-top: 15px;

  padding: 12px 15px;

  background: #fef2f2;

  border: 1px solid #fecaca;
  border-radius: 10px;

  color: #dc2626;

  font-size: 10px;
}


/* =====================================================
   DARK MODE
===================================================== */

:global(html.dark-theme) .assignment-page {
  background: #1f2124;

  color: #ffffff;
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


:global(html.dark-theme) .back-button,
:global(html.dark-theme) .stat-card,
:global(html.dark-theme) .toolbar,
:global(html.dark-theme) .assignment-card,
:global(html.dark-theme) .state-box {
  background: #111111;

  border-color:
    rgba(255, 255, 255, 0.16);

  box-shadow: none;
}


:global(html.dark-theme) .page-header h1,
:global(html.dark-theme) .stat-content strong,
:global(html.dark-theme) .assignment-title-row h3,
:global(html.dark-theme) .state-box strong {
  color: #ffffff;
}


:global(html.dark-theme) .stat-content span,
:global(html.dark-theme) .class-name,
:global(html.dark-theme) .description,
:global(html.dark-theme) .deadline {
  color: #94a3b8;
}


:global(html.dark-theme) .search-box,
:global(html.dark-theme) .info-item {
  background: #202327;

  border-color:
    rgba(255, 255, 255, 0.08);
}


:global(html.dark-theme) .search-box input {
  color: #ffffff;
}


:global(html.dark-theme) .filter-select {
  background: #202327;

  border-color:
    rgba(255, 255, 255, 0.12);

  color: #cbd5e1;
}


:global(html.dark-theme) .assignment-footer {
  border-top-color:
    rgba(255, 255, 255, 0.08);
}


:global(html.dark-theme) .info-item strong,
:global(html.dark-theme) .deadline strong {
  color: #cbd5e1;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 900px) {

  .stats-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }


  .toolbar {
    grid-template-columns: 1fr;
  }

}


@media (max-width: 650px) {

  .assignment-page {
    padding: 15px;
  }


  .page-header {
    flex-direction: column;
  }


  .stats-grid {
    grid-template-columns: 1fr;
  }


  .assignment-info {
    grid-template-columns: 1fr;
  }


  .assignment-title-row {
    flex-direction: column;
  }


  .assignment-footer {
    align-items: flex-start;

    flex-direction: column;
  }

}
</style>