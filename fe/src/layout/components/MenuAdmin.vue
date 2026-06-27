<template>
  <aside class="admin-sidebar" :class="{ collapsed: collapsed }">
    <div class="sidebar-brand">
      <div class="sidebar-logo">
        <svg
          width="22"
          height="22"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M22 10v6M2 10l10-5 10 5-10 5z" />
          <path d="M6 12v5c3 3 9 3 12 0v-5" />
        </svg>
      </div>

      <div v-if="!collapsed" class="sidebar-brand-text">
        <h2>{{ brandTitle }}</h2>
        <span>{{ brandSubtitle }}</span>
      </div>

      <button
        class="collapse-btn"
        type="button"
        aria-label="Thu gọn menu"
        @click="$emit('toggle-sidebar')"
      >
        ☰
      </button>
    </div>

    <nav class="sidebar-nav">
      <template v-for="section in filteredSections" :key="section.title">
        <div class="nav-section">{{ section.title }}</div>

        <RouterLink
          v-for="item in section.items"
          :key="item.to"
          :to="item.to"
          :title="collapsed ? item.label : ''"
          class="nav-item"
          :class="{ active: isActive(item.to) }"
        >
          <i :class="item.icon" class="nav-icon"></i>
          <span v-if="!collapsed">{{ item.label }}</span>
        </RouterLink>
      </template>
    </nav>

    <div class="sidebar-footer">
      <button class="nav-item" @click="logout">
        <svg
          width="18"
          height="18"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" y1="12" x2="9" y2="12" />
        </svg>

        <span v-if="!collapsed">Đăng xuất</span>
      </button>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";

defineProps({
  collapsed: Boolean
});

defineEmits(["toggle-sidebar"]);

const route = useRoute();
const router = useRouter();

const currentUser = ref({
  ho_ten: "Người dùng",
  vai_tro: {
    ten_vai_tro: ""
  }
});

/*
  Role đang đúng theo bảng vai_tro:
  - admin
  - co_van
  - sinh_vien

  Thêm sẵn phong_dao_tao để sau này nếu DB bổ sung vai trò này thì FE không phải sửa nhiều.
*/
const ROLE_LABELS = {
  admin: "Quản trị viên",
  co_van: "Cố vấn học tập",
  sinh_vien: "Sinh viên",
  phong_dao_tao: "Phòng đào tạo"
};

const BRAND_BY_ROLE = {
  admin: {
    title: "Quản trị hệ thống",
    subtitle: "Đánh giá rèn luyện SV"
  },
  co_van: {
    title: "Không gian cố vấn",
    subtitle: "Kiểm tra phiếu rèn luyện"
  },
  phong_dao_tao: {
    title: "Phòng đào tạo",
    subtitle: "Quản lý đánh giá rèn luyện"
  }
};

const normalizeRole = (value) => {
  if (!value) return "";

  if (typeof value === "string") {
    return value;
  }

  if (typeof value === "object") {
    return (
      value.ten_vai_tro ||
      value.ma_vai_tro ||
      value.role ||
      value.name ||
      ""
    );
  }

  return "";
};

const roleKey = computed(() => {
  return normalizeRole(
    currentUser.value?.vai_tro ||
    currentUser.value?.ten_vai_tro ||
    currentUser.value?.role
  );
});

const roleLabel = computed(() => {
  return ROLE_LABELS[roleKey.value] || "Người dùng";
});

const brandTitle = computed(() => {
  return BRAND_BY_ROLE[roleKey.value]?.title || "Hệ thống đánh giá";
});

const brandSubtitle = computed(() => {
  return BRAND_BY_ROLE[roleKey.value]?.subtitle || roleLabel.value;
});

/*
  Menu này bám theo bảng chuc_nang.

  ma_chuc_nang nên seed trong DB:
  - dashboard
  - ql_nguoi_dung
  - ql_dao_tao
  - ql_dot_danh_gia
  - ql_tieu_chi
  - phan_quyen
  - ds_phieu_cho_duyet
*/
const menuSections = [
  {
    title: "Tổng quan",
    items: [
      {
        label: "Trang chủ",
        to: "/admin/trang-chu",
        icon: "fa-solid fa-house",
        ma_chuc_nang: "dashboard",
      }
    ]
  },
  {
    title: "Quản trị hệ thống",
    items: [
      {
        label: "Quản lý người dùng",
        to: "/admin/ql-nguoi-dung",
        icon: "fa-solid fa-users",
        ma_chuc_nang: "ql_nguoi_dung",
      },
      {
        label: "Phân quyền",
        to: "/admin/phan-quyen",
        icon: "fa-solid fa-user-shield",
        ma_chuc_nang: "phan_quyen",
      }
    ]
  },
  {
    title: "Quản lý đào tạo",
    items: [
      {
        label: "Khoa - Ngành - Lớp",
        to: "/admin/quan-ly-dao-tao",
        icon: "fa-solid fa-building-columns",
        ma_chuc_nang: "ql_dao_tao",
      }
    ]
  },
  {
    title: "Đánh giá rèn luyện",
    items: [
      {
        label: "Quản lý đợt đánh giá",
        to: "/admin/quan-ly-dot-danh-gia",
        icon: "fa-solid fa-calendar-days",
        ma_chuc_nang: "ql_dot_danh_gia",
      },
      {
        label: "Quản lý tiêu chí",
        to: "/admin/quan-ly-tieu-chi",
        icon: "fa-solid fa-list-check",
        ma_chuc_nang: "ql_tieu_chi",
      },
      {
        label: "Phiếu chờ duyệt",
        to: "/admin/ds-phieu-cho-duyet",
        icon: "fa-solid fa-clock-rotate-left",
        ma_chuc_nang: "ds_phieu_cho_duyet",
      }
    ]
  }
];

/*
  Nếu backend trả về quyền từ bảng phan_quyen/chuc_nang thì dùng.
  Hỗ trợ nhiều kiểu data:
  - user.chuc_nangs = [{ ma_chuc_nang: "ql_nguoi_dung" }]
  - user.quyen = ["ql_nguoi_dung", "phan_quyen"]
  - user.phan_quyen = [{ chuc_nang: { ma_chuc_nang: "ql_tieu_chi" } }]
*/
const permissionCodes = computed(() => {
  const result = new Set();

  const sources = [
    currentUser.value?.chuc_nangs,
    currentUser.value?.chuc_nang,
    currentUser.value?.quyen,
    currentUser.value?.permissions,
    currentUser.value?.phan_quyen
  ];

  sources.forEach((source) => {
    if (!Array.isArray(source)) return;

    source.forEach((item) => {
      if (typeof item === "string") {
        result.add(item);
        return;
      }

      const code =
        item?.ma_chuc_nang ||
        item?.code ||
        item?.chuc_nang?.ma_chuc_nang ||
        item?.chuc_nang?.code;

      if (code) {
        result.add(code);
      }
    });
  });

  return result;
});

const hasPermissionFromBackend = computed(() => {
  return permissionCodes.value.size > 0;
});

const canShowMenu = (item) => {
  /*
    Admin thấy tất cả chức năng nội bộ.
  */
  if (roleKey.value === "admin") {
    return true;
  }

  /*
    Nếu backend đã trả danh sách quyền từ bảng phan_quyen,
    ưu tiên kiểm tra theo ma_chuc_nang.
  */
  if (hasPermissionFromBackend.value && item.ma_chuc_nang) {
    return permissionCodes.value.has(item.ma_chuc_nang);
  }

  /*
    Khi chưa làm API phân quyền, fallback theo vai_tro.
  */
  if (!item.roles || item.roles.length === 0) {
    return true;
  }

  return item.roles.includes(roleKey.value);
};

const filteredSections = computed(() => {
  return menuSections
    .map((section) => {
      return {
        ...section,
        items: section.items.filter(canShowMenu)
      };
    })
    .filter((section) => section.items.length > 0);
});

const isActive = (path) => {
  return route.path === path || route.path.startsWith(path + "/");
};

const loadCurrentUser = () => {
  const rawUser =
    sessionStorage.getItem("auth_user") ||
    localStorage.getItem("auth_user") ||
    localStorage.getItem("user") ||
    localStorage.getItem("currentUser") ||
    localStorage.getItem("nguoi_dung");

  if (!rawUser) return;

  try {
    currentUser.value = JSON.parse(rawUser);
  } catch (error) {
    console.error("Không đọc được thông tin người dùng:", error);
  }
};

onMounted(() => {
  loadCurrentUser();
});

function logout() {
  sessionStorage.removeItem("auth_token");
  sessionStorage.removeItem("auth_user");

  localStorage.removeItem("auth_token");
  localStorage.removeItem("auth_user");
  localStorage.removeItem("user");
  localStorage.removeItem("currentUser");
  localStorage.removeItem("nguoi_dung");

  router.push("/dang-nhap");
}
</script>

<style>
.nav-icon {
  width: 18px;
  min-width: 18px;
  font-size: 16px;
  text-align: center;
}
</style>