import axios from "axios";
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({
  position: "top-right",
});

export default function (to, from, next) {
  const token = sessionStorage.getItem("auth_token");

  if (!token) {
    toaster.error("Bạn cần đăng nhập");
    return next("/dang-nhap");
  }

  axios
    .get("http://127.0.0.1:8000/api/check-token", {
      headers: {
        Authorization: "Bearer " + token,
      },
    })
    .then((res) => {
      if (!res.data.status) {
        sessionStorage.removeItem("auth_token");
        sessionStorage.removeItem("auth_user");

        toaster.error("Phiên đăng nhập đã hết hạn");
        return next("/dang-nhap");
      }

      const roleMap = {
        1: "admin",
        2: "phong_qldt",
        3: "co_van",
        4: "lop_truong",
        5: "bi_thu",
        6: "sinh_vien",
      };

      const role = roleMap[Number(res.data.user.vai_tro_id)];
      const path = to.path;

      if (!role) {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("auth_user");

        toaster.error("Vai trò không hợp lệ");
        return next("/dang-nhap");
      }

      if (role === "sinh_vien" && path.startsWith("/admin")) {
        toaster.error("Bạn không có quyền truy cập");
        return next("/");
      }

      if (
        (role === "admin" ||
          role === "phong_qldt" ||
          role === "co_van" ||
          role === "lop_truong" ||
          role === "bi_thu") &&
        !path.startsWith("/admin")
      ) {
        return next("/admin/trang-chu");
      }

      return next();
    })
    .catch(() => {
      sessionStorage.removeItem("auth_token");
      sessionStorage.removeItem("auth_user");

      toaster.error("Lỗi xác thực");
      return next("/dang-nhap");
    });
}
