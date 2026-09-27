import { createRouter, createWebHistory } from "vue-router";

import checkToken, { redirectByRole } from "./checkToken";

const routes = [

  // =====================================================
  // ROOT
  // =====================================================

  {
    path: "/",
    beforeEnter: redirectByRole,
  },


  // =====================================================
  // AUTH
  // =====================================================

  {
    path: "/dang-nhap",
    name: "DangNhap",
    component: () =>
      import("../components/Auth/DangNhap/index.vue"),
    meta: {
      layout: "default",
    },
  },


  // =====================================================
  // ADMIN
  // =====================================================

  {
    path: "/admin",

    component: () =>
      import("../layout/wrapper/admin.vue"),

    beforeEnter: checkToken,

    children: [

      // ---------------------------
      // Redirect
      // ---------------------------

      {
        path: "",
        redirect: "/admin/trang-chu",
      },


      // ---------------------------
      // Trang chủ
      // ---------------------------

      {
        path: "trang-chu",

        name: "AdminTrangChu",

        component: () =>
          import("../components/Admin/TrangChu/index.vue"),
      },


      // ---------------------------
      // Quản lý người dùng
      // ---------------------------

      {
        path: "ql-nguoi-dung",

        name: "AdminQLNguoiDung",

        component: () =>
          import("../components/Admin/QLNguoiDung/index.vue"),
      },


      // ---------------------------
      // Quản lý lớp học
      // ---------------------------

      {
        path: "ql-lop-hoc",

        name: "AdminQLLopHoc",

        component: () =>
          import("../components/Admin/QLLopHoc/index.vue"),
      },


      // ---------------------------
      // Quản lý vai trò
      // ---------------------------

      {
        path: "ql-vai-tro",

        name: "AdminQLVaiTro",

        component: () =>
          import("../components/Admin/QLVaiTro/index.vue"),
      },

    ],
  },


  // =====================================================
  // GIẢNG VIÊN
  // =====================================================

  {
    path: "/giang-vien",

    component: () =>
      import("../layout/wrapper/lecturer.vue"),

    beforeEnter: checkToken,

    children: [

      // ---------------------------
      // Redirect
      // ---------------------------

      {
        path: "",
        redirect: "/giang-vien/trang-chu",
      },


      // ---------------------------
      // Trang chủ
      // ---------------------------

      {
        path: "trang-chu",

        name: "GiangVienTrangChu",

        component: () =>
          import("../components/GiangVien/TrangChu/index.vue"),
      },


      // ---------------------------
      // Lớp học
      // ---------------------------

      {
        path: "lop-hoc",

        name: "GiangVienLopHoc",

        component: () =>
          import("../components/GiangVien/LopHoc/index.vue"),
      },


      // ---------------------------
      // Thành viên lớp
      // ---------------------------

      {
        path: "thanh-vien-lop",

        name: "GiangVienThanhVienLop",

        component: () =>
          import("../components/GiangVien/ThanhVienLop/index.vue"),
      },


      // ---------------------------
      // Bài tập
      // ---------------------------

      {
        path: "bai-tap",

        name: "GiangVienBaiTap",

        component: () =>
          import("../components/GiangVien/BaiTap/index.vue"),
      },


      // ---------------------------
      // Bài nộp
      // ---------------------------

      {
        path: "bai-nop",

        name: "GiangVienBaiNop",

        component: () =>
          import("../components/GiangVien/BaiNop/index.vue"),
      },


      // ---------------------------
      // Điểm
      // ---------------------------

      {
        path: "diem",

        name: "GiangVienDiem",

        component: () =>
          import("../components/GiangVien/Diem/index.vue"),
      },


      // ---------------------------
      // Phiên giám sát
      // ---------------------------

      {
        path: "phien-giam-sat",

        name: "GiangVienPhienGiamSat",

        component: () =>
          import("../components/GiangVien/PhienGiamSat/index.vue"),
      },


      // ---------------------------
      // Hoạt động
      // ---------------------------

      {
        path: "hoat-dong",

        name: "GiangVienHoatDong",

        component: () =>
          import("../components/GiangVien/HoatDong/index.vue"),
      },


      // ---------------------------
      // Ảnh màn hình
      // ---------------------------

      {
        path: "anh-man-hinh",

        name: "GiangVienAnhManHinh",

        component: () =>
          import("../components/GiangVien/AnhManHinh/index.vue"),
      },


      // ---------------------------
      // Cảnh báo
      // ---------------------------

      {
        path: "canh-bao",

        name: "GiangVienCanhBao",

        component: () =>
          import("../components/GiangVien/CanhBao/index.vue"),
      },

    ],
  },


  // =====================================================
  // SINH VIÊN
  // =====================================================

  // =====================================================
// SINH VIÊN
// =====================================================

{
  path: "/sinh-vien",

  component: () =>
    import("../layout/wrapper/index.vue"),

  beforeEnter: checkToken,

  children: [
    {
      path: "",
      redirect: "/sinh-vien/trang-chu",
    },

    {
      path: "trang-chu",
      name: "SinhVienTrangChu",
      component: () =>
        import("../components/SinhVien/TrangChu/index.vue"),
    },

    {
      path: "lop-hoc",
      name: "SinhVienLopHoc",
      component: () =>
        import("../components/SinhVien/LopHoc/index.vue"),
    },

    {
      path: "bai-tap",
      name: "SinhVienBaiTap",
      component: () =>
        import("../components/SinhVien/BaiTap/index.vue"),
    },

    {
      path: "bai-nop",
      name: "SinhVienBaiNop",
      component: () =>
        import("../components/SinhVien/BaiNop/index.vue"),
    },

    {
      path: "diem",
      name: "SinhVienDiem",
      component: () =>
        import("../components/SinhVien/Diem/index.vue"),
    },
  ],
},


  // =====================================================
  // 404
  // =====================================================

  {
    path: "/:pathMatch(.*)*",
    redirect: "/",
  },

];


// =====================================================
// CREATE ROUTER
// =====================================================

const router = createRouter({

  history: createWebHistory(),

  routes: routes,

  scrollBehavior() {

    return {
      top: 0,
    };

  },

});


export default router;