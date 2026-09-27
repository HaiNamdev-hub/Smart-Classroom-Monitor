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

  if (!raw) {
    return null;
  }

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
  /*
   * Token nằm ở storage nào thì
   * lưu user vào storage đó.
   */

  if (sessionStorage.getItem("auth_token")) {
    sessionStorage.setItem(
      "auth_user",
      JSON.stringify(user)
    );

    return;
  }

  localStorage.setItem(
    "auth_user",
    JSON.stringify(user)
  );
}


/*
|--------------------------------------------------------------------------
| LẤY ROLE
|--------------------------------------------------------------------------
*/

function getRole(user) {
  if (user?.vai_tro?.ten_vai_tro) {
    return user.vai_tro.ten_vai_tro;
  }

  if (user?.vaiTro?.ten_vai_tro) {
    return user.vaiTro.ten_vai_tro;
  }

  const roleId = Number(user?.vai_tro_id);

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

  /*
   * Hỗ trợ cả:
   *
   * {
   *   nguoi_dung: {...}
   * }
   *
   * hoặc server trả trực tiếp {...}
   */

  return data?.nguoi_dung ?? data?.data ?? data;
}


/*
|--------------------------------------------------------------------------
| CHECK TOKEN
|--------------------------------------------------------------------------
|
| Router:
|
| beforeEnter: checkToken
|
*/

export default async function checkToken(to) {
  const token = getToken();

  /*
   * Không có token
   */
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
    /*
     * Kiểm tra token với Backend.
     */
    await api.get("/check-token");

    /*
     * Lấy thông tin mới nhất của user.
     */
    const user = await fetchCurrentUser();

    if (!user?.id) {
      clearAuth();

      return "/dang-nhap";
    }

    /*
     * Lưu lại user mới nhất.
     */
    saveUser(user);

    /*
     * Lấy vai trò.
     */
    const role = getRole(user);

    /*
     * Không xác định được role.
     */
    if (!role || !roleRoutes[role]) {
      clearAuth();

      return "/dang-nhap";
    }

    /*
     * Kiểm tra user có đang truy cập
     * đúng khu vực của mình không.
     */
    if (!canAccessRoute(to.path, role)) {
      return roleRoutes[role];
    }

    /*
     * Hợp lệ -> cho vào.
     */
    return true;

  } catch (error) {
    console.error(
      "Không thể xác thực tài khoản:",
      error
    );

    clearAuth();

    return {
      path: "/dang-nhap",
      query: {
        redirect: to.fullPath,
      },
    };
  }
}


/*
|--------------------------------------------------------------------------
| REDIRECT THEO ROLE
|--------------------------------------------------------------------------
|
| Dùng cho:
|
| {
|   path: "/",
|   beforeEnter: redirectByRole
| }
|
*/

export async function redirectByRole() {
  const token = getToken();

  /*
   * Chưa đăng nhập.
   */
  if (!token) {
    return "/dang-nhap";
  }

  try {
    /*
     * Kiểm tra token.
     */
    await api.get("/check-token");

    /*
     * Lấy user mới nhất.
     */
    const user = await fetchCurrentUser();

    if (!user?.id) {
      clearAuth();

      return "/dang-nhap";
    }

    saveUser(user);

    /*
     * Xác định role.
     */
    const role = getRole(user);

    /*
     * Chuyển về trang chủ tương ứng.
     */
    if (roleRoutes[role]) {
      return roleRoutes[role];
    }

    clearAuth();

    return "/dang-nhap";

  } catch (error) {
    console.error(
      "Không thể xác thực tài khoản:",
      error
    );

    clearAuth();

    return "/dang-nhap";
  }
}


/*
|--------------------------------------------------------------------------
| EXPORT HELPER
|--------------------------------------------------------------------------
*/

export {
  getToken,
  getStoredUser,
  getRole,
};