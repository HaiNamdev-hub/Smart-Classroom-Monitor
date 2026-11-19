import test from 'node:test'
import assert from 'node:assert/strict'
import {
  cameraRequiredForExam,
  EXAM_MODE_LAB_NO_CAMERA,
  EXAM_MODE_ONLINE_CAMERA_REQUIRED,
  normalizeExamMode,
} from '../src/services/examMode.js'

test('only the explicit lab mode disables camera; missing and unknown legacy values keep camera required', () => {
  assert.equal(normalizeExamMode(EXAM_MODE_LAB_NO_CAMERA), EXAM_MODE_LAB_NO_CAMERA)
  assert.equal(normalizeExamMode(EXAM_MODE_ONLINE_CAMERA_REQUIRED), EXAM_MODE_ONLINE_CAMERA_REQUIRED)
  assert.equal(normalizeExamMode(undefined), EXAM_MODE_ONLINE_CAMERA_REQUIRED)
  assert.equal(normalizeExamMode('unknown-mode'), EXAM_MODE_ONLINE_CAMERA_REQUIRED)
  assert.equal(cameraRequiredForExam({ hinhThucKiemTra: EXAM_MODE_LAB_NO_CAMERA }), false)
  assert.equal(cameraRequiredForExam({ hinhThucKiemTra: EXAM_MODE_ONLINE_CAMERA_REQUIRED }), true)
  assert.equal(cameraRequiredForExam({}), true)
})
