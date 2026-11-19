import http from 'k6/http'
import { check, fail } from 'k6'
import { Counter } from 'k6/metrics'

/*
 * Backend-only exam flow. This script writes drafts and submits attempts.
 * It refuses to run unless pointed at a dedicated loopback test instance and
 * explicitly confirmed as disposable. It does not create fixture users/data.
 */
const baseUrl = (__ENV.PERF_BASE_URL || '').replace(/\/$/, '')
const vus = Number(__ENV.PERF_VUS || 0)
const draftWriteCount = Number(__ENV.PERF_DRAFT_WRITES || 3)
const examReadCount = Number(__ENV.PERF_READ_REPEATS || 3)
const confirmed = __ENV.PERF_ISOLATED_DATASET === 'YES'
const fixturePath = __ENV.PERF_USERS_FILE || ''
const draftSaves = new Counter('draft_saves_successful')
const submissions = new Counter('submissions_successful')

if (!baseUrl || !fixturePath || !confirmed || !Number.isInteger(vus) || vus < 1 || vus > 200
  || !Number.isInteger(draftWriteCount) || draftWriteCount < 1 || draftWriteCount > 10
  || !Number.isInteger(examReadCount) || examReadCount < 1 || examReadCount > 10) {
  throw new Error('Set PERF_BASE_URL, PERF_USERS_FILE, PERF_VUS (1-200), PERF_DRAFT_WRITES (1-10), PERF_READ_REPEATS (1-10), and PERF_ISOLATED_DATASET=YES.')
}

const target = new URL(baseUrl)
if (!['127.0.0.1', 'localhost', '[::1]'].includes(target.hostname) || target.port !== '8001') {
  throw new Error('Safety guard: PERF_BASE_URL must target a dedicated loopback instance on port 8001; live app ports are prohibited.')
}

const users = JSON.parse(open(fixturePath))
if (!Array.isArray(users) || users.length < vus) {
  throw new Error('Fixture must contain at least PERF_VUS isolated student accounts.')
}
const emails = users.slice(0, vus).map(user => user.email)
if (emails.some(email => typeof email !== 'string') || new Set(emails).size !== vus) {
  throw new Error('Each VU must have a distinct fixture account.')
}

export const options = {
  scenarios: {
    exam_flow: {
      executor: 'per-vu-iterations',
      vus,
      iterations: 1,
      maxDuration: '10m',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
    'http_req_duration{step:exam_read}': ['p(95)<500'],
    'http_req_duration{step:draft_save}': ['p(95)<1000'],
    'http_req_duration{step:submit}': ['p(95)<2000'],
  },
}

function json(response, step) {
  try {
    return response.json()
  } catch {
    fail(`${step}: response is not JSON (HTTP ${response.status})`)
  }
}

function requireOk(response, step, acceptedStatuses = [200]) {
  check(response, { [`${step} status accepted`]: r => acceptedStatuses.includes(r.status) }) ||
    fail(`${step}: HTTP ${response.status}`)
  return json(response, step)
}

export default function () {
  const profile = users[__VU - 1]
  const common = { headers: { 'Content-Type': 'application/json', Accept: 'application/json' } }
  const login = http.post(`${baseUrl}/api/dang-nhap`, JSON.stringify({ email: profile.email, password: profile.password }), { ...common, tags: { step: 'login' } })
  const loginData = requireOk(login, 'login')
  const token = loginData.access_token
  check(loginData, { 'login returns bearer token': data => typeof data.access_token === 'string' && data.access_token.length > 0 }) ||
    fail('login: access_token missing; fixture or API contract mismatch')

  const auth = { ...common, headers: { ...common.headers, Authorization: `Bearer ${token}` } }
  const examUrl = `${baseUrl}/api/sinh-vien/bai-kiem-tra/${encodeURIComponent(profile.baiTapId)}`
  let exam
  for (let read = 0; read < examReadCount; read++) {
    exam = requireOk(http.get(examUrl, { ...auth, tags: { step: 'exam_read' } }), 'exam_read')
  }
  check(exam, { 'exam read returns question list': data => Array.isArray(data.data?.questions) && data.data.questions.length > 0 }) ||
    fail('exam_read: isolated assignment has no questions')
  if (!profile.answers || typeof profile.answers !== 'object' || Array.isArray(profile.answers)) {
    fail('fixture answers must be an object keyed by question ID')
  }

  const start = http.post(`${examUrl}/bat-dau`, JSON.stringify({ fullscreen: true, cameraReady: true }), { ...auth, tags: { step: 'start' } })
  const startData = requireOk(start, 'start', [200, 201])
  check(startData, { 'start confirms active attempt': data => data.status === true && data.data?.trangThaiLamBai === 'IN_PROGRESS' }) ||
    fail('start: backend did not confirm active attempt')

  let draftRevision = exam.data.draftRevision ?? null
  for (let write = 0; write < draftWriteCount; write++) {
    const variants = profile.draftVariants || []
    const draftAnswers = write === draftWriteCount - 1 || variants.length === 0
      ? profile.answers
      : variants[write % variants.length]
    const draftPayload = { answers: draftAnswers, baseDraftRevision: draftRevision }
    const draft = http.put(`${examUrl}/nhap`, JSON.stringify(draftPayload), { ...auth, tags: { step: 'draft_save' } })
    const draftData = requireOk(draft, 'draft_save')
    check(draftData, { 'draft write confirms server revision': data => data.status === true && typeof data.data?.draftRevision === 'string' && data.data.draftRevision.length === 64 }) ||
      fail('draft_save: backend did not confirm persisted revision')
    draftRevision = draftData.data.draftRevision
    draftSaves.add(1)
  }

  // One unique isolated account/attempt per VU; this intentionally consumes it.
  const submit = http.post(`${examUrl}/nop`, JSON.stringify({ answers: profile.answers }), { ...auth, tags: { step: 'submit' } })
  const submitData = requireOk(submit, 'submit', [200, 201])
  check(submitData, { 'submit confirms persisted submission': data => data.status === true && data.data?.daNop === true }) ||
    fail('submit: backend did not confirm submission')
  submissions.add(1)
}
