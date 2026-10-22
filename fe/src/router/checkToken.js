
const roleRoutes = {
  quan_tri_vien: "/admin/trang-chu",
  giang_vien: "/giang-vien/trang-chu",
  sinh_vien: "/sinh-vien/trang-chu",
};

function getAuth() {
  const storage = sessionStorage.getItem("auth_token")
    ? sessionStorage
    : localStorage;

  const token = storage.getItem("auth_token");
  const rawUser = storage.getItem("auth_user");

  let user = null;

  try {
    user = rawUser ? JSON.parse(rawUser) : null;
  } catch {
    user = null;
  }

  const expiresAt = Number(storage.getItem("auth_expires_at"));

  if (expiresAt && Date.now() >= expiresAt) {
    return null;
  }

  if (!token || !user?.role) return null;

  return { token, user };
}

export function redirectByRole() {
  const auth = getAuth();

  if (!auth) return "/dang-nhap";

  return roleRoutes[auth.user.role] || "/dang-nhap";
}

export default function checkToken(to) {
  const auth = getAuth();

  if (!auth) {
    return {
      path: "/dang-nhap",
      query: { redirect: to.fullPath },
    };
  }

  const allowedPath = roleRoutes[auth.user.role];

  if (!allowedPath) return "/dang-nhap";

  const rolePrefix = allowedPath.split("/").slice(0, 2).join("/");

  if (to.path !== rolePrefix && !to.path.startsWith(rolePrefix + "/")) {
    return allowedPath;
  }

  return true;
}
