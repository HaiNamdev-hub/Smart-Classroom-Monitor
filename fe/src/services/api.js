import axios from 'axios'

export function clearAuth() {
  for (const storage of [sessionStorage, localStorage]) {
    for (const key of ['auth', 'auth_token', 'auth_user', 'auth_expires_at']) storage.removeItem(key)
  }
}

const api = axios.create({ baseURL: '/api', timeout: 15000, headers: { Accept: 'application/json' } })
api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('auth_token') || localStorage.getItem('auth_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})
api.interceptors.response.use(response => response, error => {
  if (error.response?.status === 401 && location.pathname.startsWith('/admin')) {
    clearAuth()
    location.replace('/dang-nhap')
  }
  return Promise.reject(error)
})

export function apiError(error) {
  if (error.response?.status === 422) {
    return Object.values(error.response.data.errors || {}).flat().join(' ') || error.response.data.message
  }
  if (error.response?.status >= 500) return 'Máy chủ gặp lỗi. Vui lòng thử lại sau.'
  return error.response?.data?.message || 'Không thể kết nối máy chủ. Kiểm tra kết nối và thử lại.'
}

export const roleLabel = role => ({ quan_tri_vien: 'Quản trị viên', giang_vien: 'Giảng viên', sinh_vien: 'Sinh viên' }[role] || role)
export const statusLabel = status => ({ hoat_dong: 'Hoạt động', khong_hoat_dong: 'Ngừng hoạt động', bi_khoa: 'Bị khóa' }[status] || status)
export default api
