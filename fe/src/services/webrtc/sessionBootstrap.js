/**
 * Poll briefly at a low rate for the active monitoring session when a student
 * camera becomes ready before a session has been created. This is independent
 * of AI/protection alerts and stops as soon as both signaling identities exist.
 */
export function createWebRtcSessionBootstrap({
  readContext,
  onReady,
  shouldRun = () => true,
  onError = () => {},
  intervalMs = 5000,
  setIntervalFn = globalThis.setInterval,
  clearIntervalFn = globalThis.clearInterval,
}) {
  let active = false
  let inFlight = false
  let timer = null

  const stop = () => {
    active = false
    if (timer !== null) clearIntervalFn(timer)
    timer = null
  }

  const poll = async () => {
    if (!active || inFlight || !shouldRun()) return
    inFlight = true

    try {
      const context = await readContext()
      if (!active || !shouldRun()) return

      const phienId = Number(context?.phienId)
      const sinhVienId = Number(context?.sinhVienId)
      if (Number.isInteger(phienId) && phienId > 0 && Number.isInteger(sinhVienId) && sinhVienId > 0) {
        stop()
        await onReady({ ...context, phienId, sinhVienId })
      }
    } catch (error) {
      if (active) onError(error)
    } finally {
      inFlight = false
    }
  }

  return {
    start() {
      if (active) return
      active = true
      void poll()
      timer = setIntervalFn(() => void poll(), intervalMs)
    },
    stop,
    get active() {
      return active
    },
  }
}
