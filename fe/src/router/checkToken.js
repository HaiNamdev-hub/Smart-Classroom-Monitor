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
  return data?.nguoi_dung ?? data?.data ?? data;
}

/*
|--------------------------------------------------------------------------
| CHECK TOKEN
|--------------------------------------------------------------------------
*/
export default async function checkToken(to) {
  const token = getToken();

  if (!token) {
    clearAuth();
    return {
      path: "/dang-nhap",
      query: { redirect: to.fullPath },
    };
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

    if (!role || !roleRoutes[role]) {
      clearAuth();
      return "/dang-nhap";
    }

    if (!canAccessRoute(to.path, role)) {
      return roleRoutes[role];
    }

    return true;

  } catch (error) {
    console.error("Không thể xác thực tài khoản:", error);
    clearAuth();
    return {
      path: "/dang-nhap",
      query: { redirect: to.fullPath },
    };
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