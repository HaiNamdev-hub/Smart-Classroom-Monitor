import test from 'node:test'
import assert from 'node:assert/strict'
import { createDraftSaveQueue } from '../src/services/draftSaveQueue.js'
import {
  createExamDraftBackup,
  fingerprintAnswers,
  makeExamDraftKey,
} from '../src/services/examDraftBackup.js'

function deferred() {
  let resolve
  let reject
  const promise = new Promise((res, rej) => {
    resolve = res
    reject = rej
  })
  return { promise, resolve, reject }
}

test('failed draft request does not poison the next save after reconnect', async () => {
  let connected = false
  let version = 1
  let answers = { 12: 'offline answer' }
  const calls = []
  const queue = createDraftSaveQueue({
    getVersion: () => version,
    getSnapshot: () => ({ ...answers }),
    send: async snapshot => {
      calls.push(snapshot)
      if (!connected) throw new Error('network offline')
      return { data: snapshot }
    },
  })

  const failed = await queue.flush(true)
  assert.equal(failed.saved, false)

  connected = true
  version++
  answers = { 12: 'edited after reconnect' }
  const saved = await queue.flush(true)

  assert.equal(saved.saved, true)
  assert.deepEqual(calls, [
    { 12: 'offline answer' },
    { 12: 'edited after reconnect' },
  ])
})

test('saves are serialized and coalesce edits made during an in-flight request', async () => {
  let version = 1
  let answers = { 2: 'first' }
  let activeRequests = 0
  let peakRequests = 0
  const first = deferred()
  const sent = []
  const queue = createDraftSaveQueue({
    getVersion: () => version,
    getSnapshot: () => ({ ...answers }),
    send: async snapshot => {
      activeRequests++
      peakRequests = Math.max(peakRequests, activeRequests)
      sent.push(snapshot)
      try {
        if (sent.length === 1) await first.promise
        return { data: snapshot }
      } finally {
        activeRequests--
      }
    },
  })

  const firstFlush = queue.flush(true)
  await Promise.resolve()
  version = 2
  answers = { 2: 'latest' }
  const joinedFlush = queue.flush(true)
  assert.equal(peakRequests, 1)
  first.resolve()
  await Promise.all([firstFlush, joinedFlush])

  assert.equal(peakRequests, 1)
  assert.deepEqual(sent, [{ 2: 'first' }, { 2: 'latest' }])
})

test('local backup is scoped to user, exam and attempt, expires, and contains only answer data', async () => {
  const records = new Map()
  const adapter = {
    get: async key => records.get(key) || null,
    put: async record => records.set(record.key, record),
    delete: async key => records.delete(key),
  }
  let now = 10_000
  const backup = createExamDraftBackup(adapter, { now: () => now, ttlMs: 100 })
  const identity = { studentId: 17, examId: 29, attemptStartedAt: '2026-11-14T10:00:00+07:00' }
  const record = await backup.save({
    ...identity,
    answers: { 3: 'essay text', 4: 'option-id' },
    baseServerUpdatedAt: null,
    baseServerAnswersFingerprint: '{}',
    token: 'must not be stored',
    correctAnswers: { 4: 'hidden' },
  })

  assert.equal(record.key, makeExamDraftKey(identity))
  assert.deepEqual(Object.keys(record).sort(), [
    'answers', 'attemptStartedAt', 'baseServerAnswersFingerprint', 'baseServerUpdatedAt', 'baseServerRevision',
    'expiresAt', 'key', 'savedAt', 'schemaVersion', 'studentId', 'examId',
  ].sort())
  assert.deepEqual(await backup.load(identity), record)
  assert.equal(await backup.load({ ...identity, studentId: 18 }), null)
  assert.equal(fingerprintAnswers({ 4: 'x', 3: 'y' }), fingerprintAnswers({ 3: 'y', 4: 'x' }))

  now += 101
  assert.equal(await backup.load(identity), null)
  assert.equal(records.size, 0)
})

test('backup rejects non-answer payloads and oversized drafts', async () => {
  const backup = createExamDraftBackup({ get: async () => null, put: async () => {}, delete: async () => {} })
  const identity = { studentId: 1, examId: 2, attemptStartedAt: '2026-11-14T10:00:00Z' }
  await assert.rejects(() => backup.save({ ...identity, answers: { correctAnswers: {} } }), /invalid/i)
  await assert.rejects(() => backup.save({ ...identity, answers: { 1: 'x'.repeat(130_000) } }), /invalid/i)
})
