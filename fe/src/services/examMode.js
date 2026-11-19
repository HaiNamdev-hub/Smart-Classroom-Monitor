export const EXAM_MODE_ONLINE_CAMERA_REQUIRED = 'ONLINE_CAMERA_REQUIRED'
export const EXAM_MODE_LAB_NO_CAMERA = 'LAB_NO_CAMERA'

export function normalizeExamMode(value) {
  return value === EXAM_MODE_LAB_NO_CAMERA
    ? EXAM_MODE_LAB_NO_CAMERA
    : EXAM_MODE_ONLINE_CAMERA_REQUIRED
}

export function cameraRequiredForExam(examOrMode) {
  const value = typeof examOrMode === 'string'
    ? examOrMode
    : examOrMode?.hinhThucKiemTra

  return normalizeExamMode(value) !== EXAM_MODE_LAB_NO_CAMERA
}
