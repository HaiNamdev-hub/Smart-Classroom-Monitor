import { onBeforeUnmount, onMounted, ref, unref } from 'vue'

const EDITABLE_SELECTOR = 'textarea, select, input:not([type="radio"]):not([type="checkbox"]):not([type="button"]):not([type="submit"]), [contenteditable=""], [contenteditable="true"], [role="textbox"]'

function makeEventId() {
  if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID()
  if (!globalThis.crypto?.getRandomValues) return null

  const bytes = globalThis.crypto.getRandomValues(new Uint8Array(16))
  bytes[6] = (bytes[6] & 0x0f) | 0x40
  bytes[8] = (bytes[8] & 0x3f) | 0x80
  const hex = [...bytes].map(value => value.toString(16).padStart(2, '0')).join('')
  return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`
}

export function useExamProtection({ active, protectedElement, onEvent }) {
  const fullscreenActive = ref(false)
  const notice = ref('')
  const noticeError = ref('')
  const lastEventAt = new Map()
  const screenshotSignalsDown = new Set()
  let blurTimer = null
  let noticeTimer = null
  let hiddenAt = 0
  let lastFullscreenExitAt = 0
  let mounted = false

  const isActive = () => Boolean(unref(active))
  const getProtectedElement = () => unref(protectedElement)

  function showNotice(message, isError = false) {
    notice.value = message
    noticeError.value = isError ? message : ''
    if (noticeTimer !== null) window.clearTimeout(noticeTimer)
    noticeTimer = window.setTimeout(() => {
      notice.value = ''
      noticeError.value = ''
      noticeTimer = null
    }, 6000)
  }

  function report(eventType, message, signalInfo = null) {
    if (!isActive()) return

    const now = Date.now()
    const lastAt = lastEventAt.get(eventType) || 0
    if (now - lastAt < 1500) return
    lastEventAt.set(eventType, now)
    showNotice(message)

    const eventId = makeEventId()
    if (!eventId || typeof onEvent !== 'function') return
    Promise.resolve(onEvent({
      eventId,
      eventType,
      occurredAt: new Date(now).toISOString(),
      ...(signalInfo ? { signal: signalInfo.signal, source: signalInfo.source } : {}),
    }))
      .catch(error => console.warn('[Exam protection] Could not submit event:', error))
  }

  function getScreenshotSignal(event) {
    const key = String(event.key || '').toLowerCase()
    if (key === 'printscreen' || key === 'snapshot' || event.code === 'PrintScreen') {
      return 'PRINT_SCREEN'
    }
    if (event.metaKey && event.shiftKey && ['s', '3', '4', '5'].includes(key)) {
      return `META_SHIFT_${key.toUpperCase()}`
    }
    return null
  }

  function onScreenshotKeydown(event, signal) {
    // Browser key repeat has repeat=true. Keep the signal marked until keyup,
    // so a long key hold cannot become a second event after an arbitrary timer.
    if (event.repeat) return
    screenshotSignalsDown.add(signal)

    if (event.cancelable) event.preventDefault()
    report(
      'SCREENSHOT_SHORTCUT_DETECTED',
      'Trình duyệt nhận được tín hiệu phím có thể liên quan đến chụp màn hình. Hệ thống không thể xác nhận ảnh đã được chụp.',
      { signal, source: 'keydown' },
    )
  }

  function isEditableTarget(target) {
    if (!(target instanceof Element)) return false
    return target.isContentEditable || Boolean(target.closest(EDITABLE_SELECTOR))
  }

  function targetIsProtected(target) {
    const root = getProtectedElement()
    return Boolean(root && target instanceof Node && root.contains(target))
  }

  function selectionTouchesProtected() {
    const root = getProtectedElement()
    const selection = window.getSelection?.()
    if (!root || !selection?.rangeCount) return false
    try {
      return selection.getRangeAt(0).intersectsNode(root)
    } catch {
      return false
    }
  }

  function preventAction(event, eventType, message) {
    if (!isActive() || isEditableTarget(event.target)) return
    if (!targetIsProtected(event.target) && !selectionTouchesProtected()) return
    if (event.cancelable) event.preventDefault()
    report(eventType, message)
  }

  function onKeydown(event) {
    if (!isActive()) return
    const key = String(event.key || '').toLowerCase()
    const screenshotSignal = getScreenshotSignal(event)

    if (screenshotSignal) {
      onScreenshotKeydown(event, screenshotSignal)
      return
    }

    if (!(event.ctrlKey || event.metaKey) || !['c', 'x', 'a'].includes(key)) return
    if (isEditableTarget(event.target)) return
    if (!targetIsProtected(event.target) && !selectionTouchesProtected()) return
    if (event.cancelable) event.preventDefault()

    const events = {
      c: ['copy_blocked', 'Đã chặn thao tác sao chép trong vùng câu hỏi và đáp án.'],
      x: ['cut_blocked', 'Đã chặn thao tác cắt trong vùng câu hỏi và đáp án.'],
      a: ['select_all_blocked', 'Đã chặn thao tác chọn toàn bộ nội dung trong vùng câu hỏi và đáp án.'],
    }
    report(...events[key])
  }

  function onKeyup(event) {
    if (!isActive()) return
    const signal = getScreenshotSignal(event)
    if (!signal) return

    const wasDown = screenshotSignalsDown.delete(signal)

    // Some browsers expose a PrintScreen keyup without exposing its keydown.
    // The event-type debounce in report() prevents a keydown/keyup duplicate.
    if (!wasDown) {
      report(
        'SCREENSHOT_SHORTCUT_DETECTED',
        'Trình duyệt nhận được tín hiệu phím có thể liên quan đến chụp màn hình. Hệ thống không thể xác nhận ảnh đã được chụp.',
        { signal, source: 'keyup' },
      )
    }
  }

  function onCopy(event) {
    preventAction(event, 'copy_blocked', 'Đã chặn thao tác sao chép trong vùng câu hỏi và đáp án.')
  }

  function onCut(event) {
    preventAction(event, 'cut_blocked', 'Đã chặn thao tác cắt trong vùng câu hỏi và đáp án.')
  }

  function onContextMenu(event) {
    preventAction(event, 'context_menu_blocked', 'Đã chặn menu chuột phải trong vùng câu hỏi và đáp án.')
  }

  function onDragStart(event) {
    preventAction(event, 'drag_blocked', 'Đã chặn kéo nội dung khỏi vùng câu hỏi và đáp án.')
  }

  function onSelectStart(event) {
    if (!isActive() || isEditableTarget(event.target)) return
    if (targetIsProtected(event.target) && event.cancelable) event.preventDefault()
  }

  function onVisibilityChange() {
    if (isActive() && document.hidden) {
      hiddenAt = Date.now()
      if (blurTimer !== null) window.clearTimeout(blurTimer)
      blurTimer = null
      showNotice('Tab bài thi đã bị ẩn. Sự kiện rời tab được ghi nhận bởi cơ chế giám sát hiện có.')
    }
  }

  function onWindowBlur() {
    // Capture the state at event time. A blur from the camera permission
    // dialog or preparation screen must not become reportable later.
    if (!isActive()) return
    if (blurTimer !== null) window.clearTimeout(blurTimer)
    blurTimer = window.setTimeout(() => {
      blurTimer = null
      if (isActive() && !document.hidden && Date.now() - lastFullscreenExitAt > 1500) {
        report('focus_lost', 'Cửa sổ bài thi mất focus. Đây chỉ là tín hiệu cần xem xét, không tự kết luận gian lận.')
      }
    }, 250)
  }

  function onWindowFocus() {
    if (blurTimer !== null) window.clearTimeout(blurTimer)
    blurTimer = null
  }

  function onFullscreenChange() {
    const wasFullscreen = fullscreenActive.value
    fullscreenActive.value = Boolean(document.fullscreenElement)
    if (wasFullscreen && !fullscreenActive.value && Date.now() - hiddenAt > 1500) {
      lastFullscreenExitAt = Date.now()
      report('fullscreen_exit', 'Đã thoát toàn màn hình. Đây là cảnh báo cần xem xét, không tự kết luận gian lận.')
    } else if (!wasFullscreen && fullscreenActive.value && isActive()) {
      showNotice('Đã bật chế độ toàn màn hình.')
    }
  }

  async function requestFullscreen(element) {
    if (!element?.requestFullscreen) {
      showNotice('Trình duyệt không hỗ trợ yêu cầu toàn màn hình. Hãy dùng trình duyệt tương thích để bắt đầu.', true)
      return false
    }
    try {
      await element.requestFullscreen()
      fullscreenActive.value = Boolean(document.fullscreenElement)
      return fullscreenActive.value
    } catch {
      showNotice('Không thể bật toàn màn hình. Hãy cho phép thao tác trong trình duyệt rồi thử lại.', true)
      return false
    }
  }

  async function exitFullscreen() {
    if (!document.fullscreenElement || !document.exitFullscreen) return
    try {
      await document.exitFullscreen()
    } catch {
      // The browser may already be changing fullscreen state.
    }
  }

  onMounted(() => {
    mounted = true
    fullscreenActive.value = Boolean(document.fullscreenElement)
    document.addEventListener('keydown', onKeydown, true)
    document.addEventListener('keyup', onKeyup, true)
    document.addEventListener('copy', onCopy, true)
    document.addEventListener('cut', onCut, true)
    document.addEventListener('visibilitychange', onVisibilityChange)
    document.addEventListener('fullscreenchange', onFullscreenChange)
    document.addEventListener('contextmenu', onContextMenu, true)
    document.addEventListener('dragstart', onDragStart, true)
    document.addEventListener('selectstart', onSelectStart, true)
    window.addEventListener('blur', onWindowBlur)
    window.addEventListener('focus', onWindowFocus)
  })

  onBeforeUnmount(() => {
    if (!mounted) return
    document.removeEventListener('keydown', onKeydown, true)
    document.removeEventListener('keyup', onKeyup, true)
    document.removeEventListener('copy', onCopy, true)
    document.removeEventListener('cut', onCut, true)
    document.removeEventListener('visibilitychange', onVisibilityChange)
    document.removeEventListener('fullscreenchange', onFullscreenChange)
    document.removeEventListener('contextmenu', onContextMenu, true)
    document.removeEventListener('dragstart', onDragStart, true)
    document.removeEventListener('selectstart', onSelectStart, true)
    window.removeEventListener('blur', onWindowBlur)
    window.removeEventListener('focus', onWindowFocus)
    if (blurTimer !== null) window.clearTimeout(blurTimer)
    if (noticeTimer !== null) window.clearTimeout(noticeTimer)
    screenshotSignalsDown.clear()
    blurTimer = null
    noticeTimer = null
    lastEventAt.clear()
  })

  return { fullscreenActive, notice, noticeError, requestFullscreen, exitFullscreen }
}
