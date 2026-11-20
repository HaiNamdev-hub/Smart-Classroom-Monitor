import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// Custom Metrics
const loginTrend = new Trend('auth_login_duration_ms');
const examStartTrend = new Trend('exam_start_duration_ms');
const draftSaveTrend = new Trend('draft_save_duration_ms');
const submitTrend = new Trend('exam_submit_duration_ms');
const errorRate = new Rate('system_error_rate');
const successfulSubmissions = new Counter('successful_submissions');

export const options = {
  stages: [
    { duration: '30s', target: __ENV.TARGET_VUS ? parseInt(__ENV.TARGET_VUS) : 5 },
    { duration: '1m', target: __ENV.TARGET_VUS ? parseInt(__ENV.TARGET_VUS) : 5 },
    { duration: '20s', target: 0 },
  ],
  thresholds: {
    'http_req_failed': ['rate<0.05'], // < 5% failure
    'http_req_duration': ['p(95)<3000'], // 95% under 3s
    'system_error_rate': ['rate<0.05'],
  },
};

const BASE_URL = __ENV.TARGET_URL || 'https://smart-classroom-monitor.onrender.com';
const FIXTURES = JSON.parse(__ENV.STUDENTS_FIXTURE || '[]');

export default function () {
  const vuIndex = (__VU - 1) % (FIXTURES.length || 1);
  const student = FIXTURES[vuIndex] || {
    email: `sv${String((__VU % 20) + 1).padStart(2, '0')}@example.com`,
    password: __ENV.STUDENT_PASSWORD || '1234567',
    baiTapId: __ENV.EXAM_ID || 1,
  };

  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  // 1. ĐĂNG NHẬP
  const loginStart = Date.now();
  const loginRes = http.post(
    `${BASE_URL}/api/dang-nhap`,
    JSON.stringify({ email: student.email, password: student.password }),
    { headers }
  );
  loginTrend.add(Date.now() - loginStart);

  const loginOk = check(loginRes, {
    'login status is 200': (r) => r.status === 200,
    'has access_token': (r) => r.json('access_token') !== undefined,
  });

  if (!loginOk) {
    errorRate.add(1);
    sleep(1);
    return;
  }

  const token = loginRes.json('access_token');
  const authHeaders = {
    ...headers,
    'Authorization': `Bearer ${token}`,
  };

  sleep(1 + Math.random() * 2); // Think time

  // 2. LẤY DANH SÁCH BÀI TẬP / KIỂM TRA
  const examsRes = http.get(`${BASE_URL}/api/sinh-vien/bai-tap`, { headers: authHeaders });
  check(examsRes, {
    'exams list is 200': (r) => r.status === 200,
  });

  // 3. MỞ CHI TIẾT BÀI KIỂM TRA
  const examDetailRes = http.get(
    `${BASE_URL}/api/sinh-vien/bai-kiem-tra/${student.baiTapId}`,
    { headers: authHeaders }
  );

  if (examDetailRes.status === 409) {
    // Đã nộp bài trước đó
    sleep(1);
    return;
  }

  // 4. BẮT ĐẦU BÀI KIỂM TRA
  const startExamStart = Date.now();
  const startExamRes = http.post(
    `${BASE_URL}/api/sinh-vien/bai-kiem-tra/${student.baiTapId}/bat-dau`,
    JSON.stringify({ fullscreen: true, cameraReady: true }),
    { headers: authHeaders }
  );
  examStartTrend.add(Date.now() - startExamStart);

  // 5. LÀM BÀI VÀ LƯU NHÁP (Mô phỏng 2 lần lưu nháp)
  for (let round = 1; round <= 2; round++) {
    sleep(2 + Math.random() * 3); // Suy nghĩ và chọn đáp án
    const draftStart = Date.now();
    const draftRes = http.put(
      `${BASE_URL}/api/sinh-vien/bai-kiem-tra/${student.baiTapId}/nhap`,
      JSON.stringify({
        baseDraftRevision: round - 1,
        answers: { '1': 'A', '2': 'B' },
      }),
      { headers: authHeaders }
    );
    draftSaveTrend.add(Date.now() - draftStart);
  }

  sleep(2);

  // 6. NỘP BÀI KIỂM TRA
  const submitStart = Date.now();
  const submitRes = http.post(
    `${BASE_URL}/api/sinh-vien/bai-kiem-tra/${student.baiTapId}/nop`,
    JSON.stringify({
      answers: { '1': 'A', '2': 'B' },
      fullscreen_exit_count: 0,
      focus_lost_count: 0,
    }),
    { headers: authHeaders }
  );
  submitTrend.add(Date.now() - submitStart);

  const submitOk = check(submitRes, {
    'submit status is 200': (r) => r.status === 200,
  });

  if (submitOk) {
    successfulSubmissions.add(1);
  } else {
    errorRate.add(1);
  }

  sleep(1);
}
