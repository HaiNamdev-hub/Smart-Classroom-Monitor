/** Serializes draft writes and coalesces edits made while a write is in flight. */
export function createDraftSaveQueue({
  send,
  getVersion,
  getSnapshot,
  canSave = () => true,
  onSaving = () => {},
  onSaved = () => {},
  onFailure = () => {},
}) {
  let worker = null
  let requested = false
  let forceRequested = false
  let lastSavedVersion = -1

  async function run() {
    let latestResult = { saved: true, skipped: true }

    while (requested) {
      requested = false
      const force = forceRequested
      forceRequested = false
      if (!canSave(force)) {
        latestResult = { saved: false, skipped: true }
        continue
      }

      const version = getVersion()
      if (version === lastSavedVersion) continue
      const snapshot = getSnapshot()
      onSaving(version, snapshot)

      let response
      try {
        response = await send(snapshot, version)
      } catch (error) {
        latestResult = { saved: false, version, error }
        try {
          await onFailure(snapshot, version, error)
        } catch {
          // A UI/local-backup callback must never reject the autosave worker.
        }
        // A newer edit queued during the failed request gets one attempt with
        // its latest snapshot. A failure of the same version never loops.
        if (!requested || getVersion() === version) break
        continue
      }

      lastSavedVersion = version
      latestResult = { saved: true, version, response }
      try {
        await onSaved(snapshot, version, response)
      } catch (callbackError) {
        // The server write is already confirmed; local cleanup must not turn
        // a successful save into a rejected autosave operation.
        latestResult.callbackError = callbackError
      }
    }

    return latestResult
  }

  function flush(force = false) {
    if (!canSave(force)) return Promise.resolve({ saved: false, skipped: true })
    requested = true
    forceRequested ||= force
    if (worker) return worker

    worker = run().finally(() => {
      worker = null
      if (requested) void flush(forceRequested)
    })
    return worker
  }

  return {
    flush,
    get lastSavedVersion() { return lastSavedVersion },
    get inFlight() { return worker },
  }
}
