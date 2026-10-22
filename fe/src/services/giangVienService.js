import api from './api'

export default {
  // 1. Cảnh báo
  getCanhBao(params) {
    return api.get('/giang-vien/canh-bao', { params })
  },
  xuLyCanhBao(id) {
    return api.put(`/giang-vien/canh-bao/${id}/xu-ly`)
  },

  // 2. Phiên giám sát
  getPhienGiamSat() {
    return api.get('/giang-vien/phien-giam-sat')
  },
  createPhienGiamSat(data) {
    return api.post('/giang-vien/phien-giam-sat', data)
  },
  ketThucPhien(id) {
  return api.put(`/giang-vien/phien-giam-sat/${id}/ket-thuc`)
  },

  // 3. Ảnh màn hình
  getAnhManHinh(params) {
    return api.get('/giang-vien/anh-man-hinh', { params })
  },

  // 4. Hoạt động
  getHoatDong(params) {
    return api.get('/giang-vien/hoat-dong', { params })
  },

  // 5. Trang chủ (Dashboard)
  getDashboardStats() {
    return api.get('/giang-vien/dashboard/stats')
  },

  // 6. Quản lý Lớp học & Thành viên
  getLopHoc() {
    return api.get('/giang-vien/lop-hoc')
  },
  getThanhVienLop(lopId) {
    return api.get(`/giang-vien/lop-hoc/${lopId}/thanh-vien`)
  },

  // 7. Bài tập & Bài nộp
  getBaiTap(params) {
    return api.get('/giang-vien/bai-tap', { params })
  },
  createBaiTap(data) {
    return api.post('/giang-vien/bai-tap', data)
  },
  getBaiNop(baiTapId) {
    return api.get(`/giang-vien/bai-tap/${baiTapId}/bai-nop`)
  },

  // 8. Quản lý Điểm & Sinh viên
  getBangDiem(lopId) {
    return api.get(`/giang-vien/diem`, { params: { lopId } })
  },
  getDanhSachSinhVien(params) {
    return api.get('/giang-vien/sinh-vien', { params })
  }
}