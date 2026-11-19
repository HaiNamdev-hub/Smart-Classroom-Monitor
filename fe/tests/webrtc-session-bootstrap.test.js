import test from 'node:test'
import assert from 'node:assert/strict'
import { createWebRtcSessionBootstrap } from '../src/services/webrtc/sessionBootstrap.js'

function fakeInterval() {
  let callback
  let cleared = false
  return {
    setIntervalFn(fn, delay) {
      callback = fn
      assert.equal(delay, 5000)
      return 17
    },
    clearIntervalFn(id) {
      assert.equal(id, 17)
      cleared = true
    },
    tick() {
      callback?.()
    },
    get cleared() {
      return cleared
    },
  }
}

test('session bootstrap retries missing session independently and stops when identity becomes available', async () => {
  const timer = fakeInterval()
  let reads = 0
  const ready = []
  const bootstrap = createWebRtcSessionBootstrap({
    readContext: async () => ++reads === 1
      ? { phienId: null, sinhVienId: 4 }
      : { phienId: '22', sinhVienId: '4' },
    onReady: async context => ready.push(context),
    setIntervalFn: timer.setIntervalFn,
    clearIntervalFn: timer.clearIntervalFn,
  })

  bootstrap.start()
  await new Promise(resolve => setImmediate(resolve))
  assert.equal(bootstrap.active, true)
  timer.tick()
  await new Promise(resolve => setImmediate(resolve))

  assert.equal(reads, 2)
  assert.equal(ready.length, 1)
  assert.deepEqual(ready[0], { phienId: 22, sinhVienId: 4 })
  assert.equal(bootstrap.active, false)
  assert.equal(timer.cleared, true)
})

test('session bootstrap avoids overlapping status requests and ignores a result after stop', async () => {
  const timer = fakeInterval()
  let resolveRead
  let reads = 0
  let readyCount = 0
  const bootstrap = createWebRtcSessionBootstrap({
    readContext: () => {
      reads++
      return new Promise(resolve => { resolveRead = resolve })
    },
    onReady: () => { readyCount++ },
    setIntervalFn: timer.setIntervalFn,
    clearIntervalFn: timer.clearIntervalFn,
  })

  bootstrap.start()
  timer.tick()
  assert.equal(reads, 1)
  bootstrap.stop()
  resolveRead({ phienId: 22, sinhVienId: 4 })
  await new Promise(resolve => setImmediate(resolve))

  assert.equal(readyCount, 0)
  assert.equal(timer.cleared, true)
})

test('session bootstrap does not call API when camera mode no longer requires it', async () => {
  const timer = fakeInterval()
  let reads = 0
  const bootstrap = createWebRtcSessionBootstrap({
    readContext: async () => { reads++; return null },
    onReady: () => assert.fail('must not initialize camera for no-camera mode'),
    shouldRun: () => false,
    setIntervalFn: timer.setIntervalFn,
    clearIntervalFn: timer.clearIntervalFn,
  })

  bootstrap.start()
  timer.tick()
  await new Promise(resolve => setImmediate(resolve))
  bootstrap.stop()
  assert.equal(reads, 0)
})
