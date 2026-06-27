import axios from "axios";
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({
  position: "top-right",
});

const roleMap = {
  1: "admin",
  2: "phong_qldt",
  3: "co_van",
  4: "lop_truong",
  5: "bi_thu",
  6: "sinh_vien",
};

const adminRoles = ["admin", "phong_qldt", "co_van"];
const studentRoles = ["lop_truong", "bi_thu", "sinh_vien"];

function clearAuth() {
  sessionStorage.removeItem("auth_token");
  sessionStorage.removeItem("auth_user");
  sessionStorage.removeItem("sinh_vien");
}

function getRoleFromUser(user) {
  return roleMap[Number(user?.vai_tro_id)];
}

function getHomePathByRole(role) {
  if (adminRoles.includes(role)) {
    return "/admin/trang-chu";
  }

  if (studentRoles.includes(role)) {
    return "/sinh-vien/trang-chu";
  }

  return "/dang-nhap";
}

function checkTokenApi(token) {
  return axios.get("http://127.0.0.1:8000/api/check-token", {
    headers: {
      Authorization: "Bearer " + token,
    },
  });
}

// Dùng cho path "/"
export function redirectByRole(to, from, next) {
  const token = sessionStorage.getItem("auth_token");

  if (!token) {
    return next("/dang-nhap");
  }

  checkTokenApi(token)
    .then((res) => {
      if (!res.data.status) {
        clearAuth();
        return next("/dang-nhap");
      }

      const user = res.data.user;
      const role = getRoleFromUser(user);

      if (!role) {
        clearAuth();
        return next("/dang-nhap");
      }

      sessionStorage.setItem("auth_user", JSON.stringify(user));

      return next(getHomePathByRole(role));
    })
    .catch(() => {
      clearAuth();
      return next("/dang-nhap");
    });
}

// Dùng cho /admin và /sinh-vien
export default function checkToken(to, from, next) {
  const token = sessionStorage.getItem("auth_token");

  if (!token) {
    toaster.error("Bạn cần đăng nhập");
    return next("/dang-nhap");
  }

  checkTokenApi(token)
    .then((res) => {
      if (!res.data.status) {
        clearAuth();

        toaster.error("Phiên đăng nhập đã hết hạn");
        return next("/dang-nhap");
      }

      const user = res.data.user;
      const role = getRoleFromUser(user);
      const path = to.path;

      if (!role) {
        clearAuth();

        toaster.error("Vai trò không hợp lệ");
        return next("/dang-nhap");
      }

      sessionStorage.setItem("auth_user", JSON.stringify(user));

      if (adminRoles.includes(role) && path.startsWith("/sinh-vien")) {
        return next("/admin/trang-chu");
      }

      if (studentRoles.includes(role) && path.startsWith("/admin")) {
        toaster.error("Bạn không có quyền truy cập");
        return next("/sinh-vien/trang-chu");
      }

      if (adminRoles.includes(role) && !path.startsWith("/admin")) {
        return next("/admin/trang-chu");
      }

      if (studentRoles.includes(role) && !path.startsWith("/sinh-vien")) {
        return next("/sinh-vien/trang-chu");
      }

      return next();
    })
    .catch(() => {
      clearAuth();

      toaster.error("Lỗi xác thực");
      return next("/dang-nhap");
    });
}