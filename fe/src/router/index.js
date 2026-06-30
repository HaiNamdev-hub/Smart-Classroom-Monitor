import { createRouter, createWebHistory } from "vue-router"; // cài vue-router: npm install vue-router@next --save
import checkToken, { redirectByRole } from "./checkToken";
const routes = [
  {
    path: "/",
    beforeEnter: redirectByRole,
  },

  // ============= Auth ==================
  {
    path: "/dang-nhap",
    component: () => import("../components/Auth/DangNhap/index.vue"),
    meta: {
      layout: "default",
    },
  },
  {
    path: "/quen-mat-khau",
    component: () => import("../components/Auth/QuenMatKhau/index.vue"),
    meta: {
      layout: "default",
    },
  },

  // ============= Admin/GVCV ==================
  {
  path: "/admin",
  component: () => import("../layout/wrapper/admin.vue"),
  beforeEnter: checkToken,
  children: [
    {
      path: "",
      redirect: "/admin/trang-chu",
    },
    {
      path: "trang-chu",
      component: () => import("../components/Admin/TrangChu/index.vue"),
    },
    {
      path: "ql-nguoi-dung",
      component: () => import("../components/Admin/QLNguoiDung/index.vue"),
      meta: {
        chuc_nang: "quan_ly_nguoi_dung",
      },
    },
    {
      path: "quan-ly-dao-tao",
      component: () => import("../components/Admin/QLDaoTao/index.vue"),
      meta: {
        chuc_nang: "quan_ly_khoa",
      },
    },
    {
      path: "quan-ly-dot-danh-gia",
      component: () => import("../components/Admin/QLDotDanhGia/index.vue"),
      meta: {
        chuc_nang: "quan_ly_dot_danh_gia",
      },
    },
    {
      path: "quan-ly-tieu-chi",
      component: () => import("../components/Admin/QLTieuChi/index.vue"),
      meta: {
        chuc_nang: "quan_ly_tieu_chi",
      },
    },
    {
      path: "phan-quyen",
      component: () => import("../components/Admin/PhanQuyen/index.vue"),
      meta: {
        chuc_nang: "phan_quyen",
      },
    },
    {
      path: "ds-phieu-cho-duyet",
      name: "AdminDSPhieuChoDuyet",
      component: () => import("../components/Admin/DSPhieuChoDuyet/index.vue"),
      meta: {
        chuc_nang: "duyet_phieu_ren_luyen",
      },
    },
    {
      path: "chi-tiet-danh-gia/:id",
      name: "AdminChiTietPhieuDanhGia",
      component: () => import("../components/Admin/ChiTietPhieuDanhGia/index.vue"),
      meta: {
        chuc_nang: "duyet_phieu_ren_luyen",
      },
    },
    {
      path: "chi-tiet-danh-gia",
      redirect: "/admin/ds-phieu-cho-duyet",
    },
  ],
},
  // ============= SV/Lớp trưởng/Bí thư ==================
  {
    path: "/sinh-vien",
    component: () => import("../layout/wrapper/students.vue"),
    beforeEnter: checkToken,
    children: [
      {
        path: "",
        redirect: "/sinh-vien/trang-chu",
      },
      {
        path: "phieu-lop/:phieuId",
        component: () =>
          import("../components/Students/DanhGiaPhieuLop/index.vue"),
        name: "SinhVienDanhGiaPhieuLop",
      },
      {
        path: "trang-chu",
        component: () => import("../components/Students/TrangChu/index.vue"),
      },
      {
        path: "phieu-ren-luyen",
        component: () =>
          import("../components/Students/PhieuRenLuyen/index.vue"),
      },
      {
        path: "danh-sach-phieu-lop",
        name: "SinhVienDanhSachPhieuLop",
        component: () =>
          import("../components/Students/DanhSachPhieuLop/index.vue"),
      },
      {
        path: "ket-qua-ren-luyen",
        component: () =>
          import("../components/Students/KetQuaRenLuyen/index.vue"),
      },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes: routes,
  scrollBehavior(to, from, savedPosition) {
    // Luôn cuộn lên đầu trang
    return { top: 0 };
  },
});

export default router;
