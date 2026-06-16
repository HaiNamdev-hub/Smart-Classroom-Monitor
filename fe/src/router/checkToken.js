import axios from "axios";
import { createToaster } from "@meforma/vue-toaster";

const toaster = createToaster({
  position: "top-right",
});

export default function (to, from, next) {
  const token = localStorage.getItem("auth_token");

  // chưa đăng nhập
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
      // token hết hạn
      if (!res.data.status) {
        localStorage.removeItem("auth_token");
        localStorage.removeItem("auth_user");

        toaster.error("Phiên đăng nhập đã hết hạn");

        return next("/dang-nhap");
      }

      /*
        Backend nên trả:

        {
           user:{
              role:"admin"
           }

           hoặc

           {
             user:{
               role:"co_van"
             }

           hoặc

           {
             user:{
               role:"sinh_vien"
             }
        }
      */

      const role = res.data.user.role;
      const path = to.path;

      /*
        Sinh viên không được vào admin
      */
      if (role === "sinh_vien" && path.startsWith("/admin")) {
        toaster.error("Bạn không có quyền truy cập");

        return next("/");
      }

      /*
        Admin + Cố vấn
        Không được vào giao diện sinh viên
      */
      if (
        (role === "admin" || role === "co_van") &&
        !path.startsWith("/admin")
      ) {
        return next("/admin");
      }

      next();
    })

    .catch(() => {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("auth_user");

      toaster.error("Lỗi xác thực");

      next("/dang-nhap");
    });
}