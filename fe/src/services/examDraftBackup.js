const DB_NAME = 'smart-classroom-exam-drafts'
const DB_VERSION = 1
const STORE_NAME = 'drafts'
export const EXAM_DRAFT_TTL_MS = 24 * 60 * 60 * 1000
const MAX_BACKUP_BYTES = 128 * 1024

function asPositiveId(value) {
  const id = Number(value)
  return Number.isSafeInteger(id) && id > 0 ? String(id) : null
}

export function makeExamDraftKey({ studentId, examId, attemptStartedAt }) {
  const student = asPositiveId(studentId)
  const exam = asPositiveId(examId)
  const startedAt = Date.parse(attemptStartedAt)
  if (!student || !exam || !Number.isFinite(startedAt)) return null
  return `${student}:${exam}:${startedAt}`
}

export function fingerprintAnswers(answers) {
  if (!answers || typeof answers !== 'object' || Array.isArray(answers)) return '{}'
  const sorted = Object.fromEntries(
    Object.keys(answers).sort((a, b) => a.localeCompare(b, undefined, { numeric: true }))
      .map(key => [key, answers[key]])
  )
  return JSON.stringify(sorted)
}

function validateAnswers(answers) {
  if (!answers || typeof answers !== 'object' || Array.isArray(answers)) return false
  for (const [questionId, value] of Object.entries(answers)) {
    if (!/^\d+$/.test(questionId)) return false
    if (value !== null && typeof value !== 'string' && typeof value !== 'number') return false
    if (typeof value === 'number' && !Number.isFinite(value)) return false
    if (typeof value === 'string' && value.length > 20000) return false
  }
  return new TextEncoder().encode(JSON.stringify(answers)).byteLength <= MAX_BACKUP_BYTES
}

export function createExamDraftBackup(adapter, { now = () => Date.now(), ttlMs = EXAM_DRAFT_TTL_MS } = {}) {
  if (!adapter || typeof adapter.get !== 'function' || typeof adapter.put !== 'function' || typeof adapter.delete !== 'function') {
    throw new TypeError('Exam draft adapter must provide get, put, and delete methods.')
  }

  return {
    async save(record) {
      const key = makeExamDraftKey(record || {})
      if (!key || !validateAnswers(record.answers)) throw new TypeError('Exam draft backup data is invalid.')

      const savedAt = now()
      const safeRecord = {
        key,
        schemaVersion: 1,
        studentId: asPositiveId(record.studentId),
        examId: asPositiveId(record.examId),
        attemptStartedAt: new Date(Date.parse(record.attemptStartedAt)).toISOString(),
        answers: JSON.parse(JSON.stringify(record.answers)),
        baseServerUpdatedAt: typeof record.baseServerUpdatedAt === 'string' ? record.baseServerUpdatedAt : null,
        baseServerAnswersFingerprint: String(record.baseServerAnswersFingerprint || '{}').slice(0, MAX_BACKUP_BYTES),
        baseServerRevision: typeof record.baseServerRevision === 'string' ? record.baseServerRevision : null,
        savedAt,
        expiresAt: savedAt + ttlMs,
      }

      await adapter.put(safeRecord)
      return safeRecord
    },

    async load(identity) {
      const key = makeExamDraftKey(identity || {})
      if (!key) return null
      const record = await adapter.get(key)
      if (!record) return null
      if (
        record.schemaVersion !== 1 ||
        record.key !== key ||
        !validateAnswers(record.answers) ||
        !Number.isFinite(record.expiresAt) ||
        record.expiresAt <= now()
      ) {
        await adapter.delete(key)
        return null
      }
      return record
    },

    async remove(identity) {
      const key = makeExamDraftKey(identity || {})
      if (key) await adapter.delete(key)
    },
  }
}

function indexedDbAdapter(indexedDBRef = globalThis.indexedDB) {
  let dbPromise
  const open = () => {
    if (!indexedDBRef) return Promise.reject(new Error('IndexedDB is unavailable.'))
    if (!dbPromise) {
      dbPromise = new Promise((resolve, reject) => {
        const request = indexedDBRef.open(DB_NAME, DB_VERSION)
        request.onupgradeneeded = () => {
          const db = request.result
          if (!db.objectStoreNames.contains(STORE_NAME)) db.createObjectStore(STORE_NAME, { keyPath: 'key' })
        }
        request.onsuccess = () => {
          request.result.onversionchange = () => request.result.close()
          resolve(request.result)
        }
        request.onerror = () => reject(request.error || new Error('Could not open IndexedDB.'))
        request.onblocked = () => reject(new Error('IndexedDB upgrade is blocked.'))
      }).catch(error => {
        dbPromise = null
        throw error
      })
    }
    return dbPromise
  }

  async function request(method, value) {
    const db = await open()
    return new Promise((resolve, reject) => {
      const tx = db.transaction(STORE_NAME, method === 'readonly' ? 'readonly' : 'readwrite')
      const store = tx.objectStore(STORE_NAME)
      const operation = method === 'get'
        ? store.get(value)
        : method === 'put'
          ? store.put(value)
          : store.delete(value)
      operation.onsuccess = () => resolve(operation.result)
      operation.onerror = () => reject(operation.error || new Error('IndexedDB request failed.'))
      tx.onabort = () => reject(tx.error || new Error('IndexedDB transaction was aborted.'))
    })
  }

  return {
    get: key => request('get', key),
    put: record => request('put', record),
    delete: key => request('delete', key),
  }
}

export const examDraftBackup = createExamDraftBackup(indexedDbAdapter())
