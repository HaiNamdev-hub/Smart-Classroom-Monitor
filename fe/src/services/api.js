import axios from "axios";


/*
|--------------------------------------------------------------------------
| CLEAR AUTH
|--------------------------------------------------------------------------
|
| Xóa toàn bộ thông tin đăng nhập khỏi
| sessionStorage và localStorage.
|
*/

export function clearAuth() {
  const authKeys = [
    "auth",
    "auth_token",
    "auth_user",
    "auth_expires_at",
  ];

  const storages = [
    sessionStorage,
    localStorage,
  ];

  for (const storage of storages) {
    for (const key of authKeys) {
      storage.removeItem(key);
    }
  }
}


/*
|--------------------------------------------------------------------------
| AXIOS INSTANCE
|--------------------------------------------------------------------------
*/

const api = axios.create({
  baseURL: "/api",

  timeout: 15000,

  headers: {
    Accept: "application/json",
  },
});


/*
|--------------------------------------------------------------------------
| REQUEST INTERCEPTOR
|--------------------------------------------------------------------------
|
| Tự động lấy token từ sessionStorage
| hoặc localStorage và gửi lên Backend.
|
*/

api.interceptors.request.use(
  (config) => {
    const token =
      sessionStorage.getItem("auth_token") ||
      localStorage.getItem("auth_token");

    if (token) {
      config.headers.Authorization =
        `Bearer ${token}`;
    }

    return config;
  },

  (error) => {
    return Promise.reject(error);
  }
);


/*
|--------------------------------------------------------------------------
| RESPONSE INTERCEPTOR
|--------------------------------------------------------------------------
|
| Nếu Backend trả về 401:
| - Token hết hạn
| - Token không hợp lệ
| - User không còn đăng nhập
|
| => Xóa auth và chuyển về trang đăng nhập.
|
*/

api.interceptors.response.use(
  (response) => {
    return response;
  },

  (error) => {
    if (error.response?.status === 401) {
      clearAuth();

      /*
       * Tránh redirect lại nếu
       * đang ở trang đăng nhập.
       */
      if (
        window.location.pathname !==
        "/dang-nhap"
      ) {
        window.location.replace(
          "/dang-nhap"
        );
      }
    }

    return Promise.reject(error);
  }
);


/*
|--------------------------------------------------------------------------
| API ERROR
|--------------------------------------------------------------------------
|
| Chuyển lỗi Backend thành thông báo
| dễ hiển thị trên giao diện.
|
*/

export function apiError(error) {
  const status =
    error.response?.status;

  const data =
    error.response?.data;


  /*
   * Validation Error
   * Laravel HTTP 422
   */
  if (status === 422) {
    const errors =
      data?.errors || {};

    const messages =
      Object.values(errors)
        .flat()
        .filter(Boolean);

    if (messages.length > 0) {
      return messages.join(" ");
    }

    return (
      data?.message ||
      "Dữ liệu không hợp lệ."
    );
  }


  /*
   * Unauthorized
   */
  if (status === 401) {
    return "Phiên đăng nhập đã hết hạn.";
  }


  /*
   * Forbidden
   */
  if (status === 403) {
    return (
      data?.message ||
      "Bạn không có quyền thực hiện thao tác này."
    );
  }


  /*
   * Not Found
   */
  if (status === 404) {
    return (
      data?.message ||
      "Không tìm thấy dữ liệu yêu cầu."
    );
  }


  /*
   * Server Error
   */
  if (status >= 500) {
    return (
      "Máy chủ gặp lỗi. " +
      "Vui lòng thử lại sau."
    );
  }


  /*
   * Backend có trả message
   */
  if (data?.message) {
    return data.message;
  }


  /*
   * Không nhận được response
   * từ server.
   */
  if (error.request) {
    return (
      "Không thể kết nối máy chủ. " +
      "Kiểm tra kết nối và thử lại."
    );
  }


  /*
   * Lỗi khác
   */
  return (
    error.message ||
    "Đã xảy ra lỗi. Vui lòng thử lại."
  );
}


/*
|--------------------------------------------------------------------------
| ROLE LABEL
|--------------------------------------------------------------------------
*/

export const roleLabel = (role) => {
  const roles = {
    quan_tri_vien: "Quản trị viên",
    giang_vien: "Giảng viên",
    sinh_vien: "Sinh viên",
  };

  return roles[role] || role;
};


/*
|--------------------------------------------------------------------------
| STATUS LABEL
|--------------------------------------------------------------------------
*/

export const statusLabel = (status) => {
  const statuses = {
    hoat_dong: "Hoạt động",
    khong_hoat_dong: "Ngừng hoạt động",
    bi_khoa: "Bị khóa",
  };

  return statuses[status] || status;
};


/*
|--------------------------------------------------------------------------
| EXPORT API
|--------------------------------------------------------------------------
*/

export default api;