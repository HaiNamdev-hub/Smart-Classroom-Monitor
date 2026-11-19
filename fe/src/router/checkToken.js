import api, { clearAuth } from "../services/api";

/*
|--------------------------------------------------------------------------
| ROUTE THEO VAI TRÒ
|--------------------------------------------------------------------------
*/
const roleRoutes = {
  quan_tri_vien: "/admin/trang-chu",
  giang_vien: "/giang-vien/trang-chu",
  sinh_vien: "/sinh-vien/trang-chu",
};

/*
|--------------------------------------------------------------------------
| LẤY TOKEN
|--------------------------------------------------------------------------
*/
function getToken() {
  return (
    sessionStorage.getItem("auth_token") ||
    localStorage.getItem("auth_token")
  );
}

/*
|--------------------------------------------------------------------------
| LẤY USER ĐÃ LƯU
|--------------------------------------------------------------------------
*/
function getStoredUser() {
  const raw =
    sessionStorage.getItem("auth_user") ||
    localStorage.getItem("auth_user");

  if (!raw) return null;

  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

/*
|--------------------------------------------------------------------------
| LƯU USER
|--------------------------------------------------------------------------
*/
function saveUser(user) {
  if (sessionStorage.getItem("auth_token")) {
    sessionStorage.setItem("auth_user", JSON.stringify(user));
    return;
  }

  localStorage.setItem("auth_user", JSON.stringify(user));
}

/*
|--------------------------------------------------------------------------
| LẤY ROLE
|--------------------------------------------------------------------------
*/

function getRole(user) {
  if (!user) return null;

  const rawRole =
    user.vai_tro?.ten_vai_tro ??
    user.vaiTro?.ten_vai_tro ??
    user.role ??
    null;

  if (typeof rawRole === "string") {
    const role = rawRole
      .trim()
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/đ/g, "d")
      .replace(/[\s-]+/g, "_");

    const roleMap = {
      admin: "quan_tri_vien",
      administrator: "quan_tri_vien",
      quan_tri_vien: "quan_tri_vien",
      giang_vien: "giang_vien",
      lecturer: "giang_vien",
      teacher: "giang_vien",
      sinh_vien: "sinh_vien",
      student: "sinh_vien",
    };

    if (roleMap[role]) {
      return roleMap[role];
    }
  }

  const roleId = Number(user.vai_tro_id);

  if (roleId === 1) return "quan_tri_vien";
  if (roleId === 2) return "giang_vien";
  if (roleId === 3) return "sinh_vien";

  return null;
}


/*
|--------------------------------------------------------------------------
| KIỂM TRA QUYỀN TRUY CẬP ROUTE
|--------------------------------------------------------------------------
*/
function canAccessRoute(path, role) {
  if (path.startsWith("/admin")) {
    return role === "quan_tri_vien";
  }

  if (path.startsWith("/giang-vien")) {
    return role === "giang_vien";
  }

  if (path.startsWith("/sinh-vien")) {
    return role === "sinh_vien";
  }

  return true;
}

/*
|--------------------------------------------------------------------------
| LẤY USER TỪ SERVER
|--------------------------------------------------------------------------
*/
async function fetchCurrentUser() {
  const { data } = await api.get("/thong-tin");
  return data?.nguoi_dung ?? data?.data ?? data;
}

/*
|--------------------------------------------------------------------------
| CHECK TOKEN
|--------------------------------------------------------------------------
*/

export default async function checkToken(to) {
  const token = getToken();

  // Chưa đăng nhập
  if (!token) {
    clearAuth();

    return {
      path: "/dang-nhap",
      query: {
        redirect: to.fullPath,
      },
    };
  }

  try {
    // Kiểm tra token với backend
    await api.get("/check-token");

    // Lấy thông tin người dùng
    const user = await fetchCurrentUser();

    if (!user?.id) {
      clearAuth();
      return "/dang-nhap";
    }

    // Lưu thông tin mới nhất
    saveUser(user);

    // Xác định vai trò
    const role = getRole(user);

    if (!role || !roleRoutes[role]) {
      clearAuth();
      return "/dang-nhap";
    }

    // Chỉ chuyển hướng nếu sai quyền
    if (!canAccessRoute(to.path, role)) {
      return roleRoutes[role];
    }

    return true;

  } catch (error) {
    console.error(
      "Lỗi kiểm tra đăng nhập:",
      error
    );

    const status = error.response?.status;

    // Token hết hạn hoặc không có quyền
    if (status === 401 || status === 403) {
      clearAuth();

      return {
        path: "/dang-nhap",
        query: {
          redirect: to.fullPath,
        },
      };
    }

    // Lỗi mạng/server:
    // Không xóa token, nhưng cũng không cho truy cập
    // trang bảo vệ khi chưa xác minh được.
    return false;
  }
}


/*
|--------------------------------------------------------------------------
| REDIRECT THEO ROLE
|--------------------------------------------------------------------------
*/
export async function redirectByRole() {
  const token = getToken();

  if (!token) {
    return "/dang-nhap";
  }

  try {
    await api.get("/check-token");
    const user = await fetchCurrentUser();

    if (!user?.id) {
      clearAuth();
      return "/dang-nhap";
    }

    saveUser(user);
    const role = getRole(user);

    if (roleRoutes[role]) {
      return roleRoutes[role];
    }

    clearAuth();
    return "/dang-nhap";

  } catch (error) {
    console.error("Không thể xác thực tài khoản:", error);
    clearAuth();
    return "/dang-nhap";
  }
}

export { getToken, getStoredUser, getRole };