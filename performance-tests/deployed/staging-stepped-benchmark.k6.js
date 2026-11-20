import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

/**
 * ============================================================================
 * K6 STEPPED BENCHMARK CHO SMART CLASSROOM MONITOR (STAGING POSTGRESQL ONLY)
 * ============================================================================
 * Các giai đoạn tăng dần: 1 VU -> 5 VUs -> 10 VUs -> 20 VUs -> 30 VUs -> 50 VUs
 * CẢNH BÁO: CHỈ CHẠY TRÊN MÔI TRƯỜNG STAGING VỚI DỮ LIỆU GIẢ VÀ TÀI KHOẢN MẪU.
 * KHÔNG CHẠY TẢI TRÊN PRODUCTION RENDER HOẶC NEON DATABASE THẬT.
 * ============================================================================
 */

// Custom Metrics
const loginLatency = new Trend('auth_login_ms', true);
const startExamLatency = new Trend('exam_start_ms', true);
const draftSaveLatency = new Trend('exam_draft_save_ms', true);
const submitLatency = new Trend('exam_submit_ms', true);
const errorRate = new Rate('failure_rate');
const submitSuccessCounter = new Counter('successful_submits');

export const options = {
  // Stepped progression: 1 -> 5 -> 10 -> 20 -> 30 -> 50 VUs
  stages: [
    // Stage 1: 1 VU (Smoke test & Functional baseline)
    { duration: '30s', target: 1 },
    // Stage 2: 5 VUs (Light concurrent load)
    { duration: '45s', target: 5 },
    // Stage 3: 10 VUs (Moderate load)
    { duration: '1m', target: 10 },
    // Stage 4: 20 VUs (Typical small classroom)
    { duration: '1m30s', target: 20 },
    // Stage 5: 30 VUs (Standard classroom target)
    { duration: '2m', target: 30 },
    // Stage 6: 50 VUs (Peak concurrent target)
    { duration: '2m', target: 50 },
    // Ramp-down
    { duration: '30s', target: 0 },
  ],

  // Safety Gate & Circuit Breaker Thresholds
  thresholds: {
    // Nếu tỷ lệ lỗi HTTP >= 5%, test tự động đánh dấu thất bại
    'http_req_failed': [{ threshold: 'rate<0.05', abortOnFail: true }],
    // Nếu p95 độ trễ toàn hệ thống vượt quá 10,000ms (10s), tự ngắt để bảo vệ server
    'http_req_duration': [{ threshold: 'p(95)<10000', abortOnFail: true }],
    'failure_rate': [{ threshold: 'rate<0.05', abortOnFail: true }],
  },
};

const BASE_URL = __ENV.STAGING_URL || 'http://localhost:8000';
const EXAM_ID = parseInt(__ENV.EXAM_ID || '1');

export default function () {
  const studentNum = ((__VU - 1) % 50) + 1;
  const email = `staging.student${String(studentNum).padStart(2, '0')}@example.test`;
  const password = __ENV.STAGING_STUDENT_PASSWORD || 'password123';

  const baseHeaders = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  // --------------------------------------------------------------------------
  // BƯỚC 1: ĐĂNG NHẬP
  // --------------------------------------------------------------------------
  const t0 = Date.now();
  const loginRes = http.post(
    `${BASE_URL}/api/dang-nhap`,
    JSON.stringify({ email, password }),
    { headers: baseHeaders }
  );
  loginLatency.add(Date.now() - t0);

  const loginOk = check(loginRes, {
    'login status 200': (r) => r.status === 200,
    'has token': (r) => Boolean(r.json('access_token')),
  });

  if (!loginOk) {
    errorRate.add(1);
    sleep(2);
    return;
  }

  const token = loginRes.json('access_token');
  const authHeaders = {
    ...baseHeaders,
    'Authorization': `Bearer ${token}`,
  };

  // Pacing giãn cách thực tế
  sleep(1 + Math.random() * 2);

  // --------------------------------------------------------------------------
  // BƯỚC 2: TẢI ĐỀ THI & LẤY DANH SÁCH CÂU HỎI
  // --------------------------------------------------------------------------
  const examRes = http.get(`${BASE_URL}/api/sinh-vien/bai-kiem-tra/${EXAM_ID}`, {
    headers: authHeaders,
  });

  if (examRes.status === 409) {
    // Sinh viên đã nộp bài trước đó
    sleep(1);
    return;
  }

  // --------------------------------------------------------------------------
  // BƯỚC 3: BẮT ĐẦU BÀI KIỂM TRA (startExam)
  // --------------------------------------------------------------------------
  const tStart = Date.now();
  const startRes = http.post(
    `${BASE_URL}/api/sinh-vien/bai-kiem-tra/${EXAM_ID}/bat-dau`,
    JSON.stringify({ fullscreen: true, cameraReady: true }),
    { headers: authHeaders }
  );
  startExamLatency.add(Date.now() - tStart);

  check(startRes, {
    'start exam 200 or 201': (r) => r.status === 200 || r.status === 201,
  });

  const questions = startRes.json('data.questions') || [];

  // --------------------------------------------------------------------------
  // BƯỚC 4: LÀM BÀI & LƯU NHÁP (Autosave - Chu kỳ 15s-20s mô phỏng)
  // --------------------------------------------------------------------------
  const sampleAnswers = {};
  if (Array.isArray(questions)) {
    questions.slice(0, 5).forEach((q) => {
      if (q.dapAn && q.dapAn.length > 0) {
        sampleAnswers[String(q.id)] = q.dapAn[0].id;
      }
    });
  }

  sleep(2 + Math.random() * 3);

  const tDraft = Date.now();
  const draftRes = http.put(
    `${BASE_URL}/api/sinh-vien/bai-kiem-tra/${EXAM_ID}/nhap`,
    JSON.stringify({
      answers: sampleAnswers,
      baseDraftRevision: null,
    }),
    { headers: authHeaders }
  );
  draftSaveLatency.add(Date.now() - tDraft);

  check(draftRes, {
    'draft save 200 or 409': (r) => r.status === 200 || r.status === 409,
  });

  // --------------------------------------------------------------------------
  // BƯỚC 5: NỘP BÀI THI (submit - sử dụng Batch Insert mới)
  // --------------------------------------------------------------------------
  sleep(3 + Math.random() * 4);

  const tSubmit = Date.now();
  const submitRes = http.post(
    `${BASE_URL}/api/sinh-vien/bai-kiem-tra/${EXAM_ID}/nop`,
    JSON.stringify({
      answers: sampleAnswers,
      tuDongNop: false,
    }),
    { headers: authHeaders }
  );
  submitLatency.add(Date.now() - tSubmit);

  const submitOk = check(submitRes, {
    'submit status 201 or already submitted': (r) => r.status === 201 || r.status === 409 || r.status === 422,
  });

  if (submitRes.status === 201) {
    submitSuccessCounter.add(1);
  } else if (!submitOk) {
    errorRate.add(1);
  }

  sleep(2);
}
