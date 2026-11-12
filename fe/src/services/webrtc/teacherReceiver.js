import echo from '../reverb'
import api from '../api'
import { inspectSdp, logSdpDiagnostics, prepareRemoteDescription } from './sdpDiagnostics'

const RTC_CONFIG = {
  iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
    { urls: 'stun:stun2.l.google.com:19302' },
  ],
}

// Log debug an toàn (không ghi token, không ghi full SDP)
function debugLog(...args) {
  if (import.meta.env.DEV) {
    console.log('[WebRTC Receiver Debug]', ...args)
  }
}

/**
 * Quản lý phía Giảng viên (WebRTC Receiver)
 * Nhận MediaStream trực tiếp từ nhiều Sinh viên trong một phiên thi
 */
export class TeacherReceiver {
  constructor() {
    this.phienId = null
    this.connections = new Map()
  }

  setPhienId(phienId) {
    this.phienId = Number(phienId)
  }

  /**
   * Theo dõi webcam và nhận cảnh báo của một Sinh viên
   */
  watchStudent(sinhVienId, { onTrack, onStateChange, onAlert } = {}) {
    if (!this.phienId || !sinhVienId) {
      console.warn('[WebRTC Receiver] Thiếu phienId hoặc sinhVienId')
      return
    }

    const sId = Number(sinhVienId)

    if (this.connections.has(sId)) {
      this.stopWatching(sId)
    }

    const conn = {
      sinhVienId: sId,
      pc: null,
      channel: null,
      pendingCandidates: [],
      offerQueue: Promise.resolve(),
      lastOfferChecksum: null,
      seenOfferChecksums: new Set(),
      onTrack,
      onStateChange,
      onAlert,
      connectionState: 'idle',
      retryTimer: null,
      retryCount: 0,
      subscribed: false,
    }

    this.connections.set(sId, conn)

    const channelName = `exam-monitor.${this.phienId}.student.${sId}`

    try {
      conn.channel = echo.private(channelName)

      // Lắng nghe tín hiệu WebRTC từ Sinh viên
      conn.channel.listen('.webrtc.signal', (event) => {
        if (event.senderRole !== 'student') return
        debugLog(`Nhận signal '${event.type}' từ SV #${sId}`)
        this.handleStudentSignal(sId, event)
      })

      // Lắng nghe cảnh báo thi theo thời gian thực
      conn.channel.listen('.exam.violation', (event) => {
        debugLog(`Nhận cảnh báo thi từ SV #${sId}: ${event.loaiCanhBao}`)
        if (typeof conn.onAlert === 'function') {
          conn.onAlert(event)
        }
      })

      conn.channel.listen('.exam.protection-alert', (event) => {
        if (typeof conn.onAlert === 'function') {
          conn.onAlert({ ...event, canXemXet: true, daXacMinh: false })
        }
      })

      // Khi kênh đã xác thực và đăng ký thành công trên Reverb
      conn.channel.subscribed(() => {
        debugLog(`Kênh đã subscribed thành công: ${channelName}`)
        conn.subscribed = true
        this.sendRequestOfferWithRetry(sId)
      })

      conn.channel.error?.((error) => {
        conn.subscribed = false
        clearTimeout(conn.retryTimer)
        conn.connectionState = 'failed'
        console.error(`[WebRTC Receiver] Không subscribe được kênh SV #${sId}:`, error)
        conn.onStateChange?.('failed')
      })
    } catch (err) {
      console.error(`[WebRTC Receiver] Lỗi kết nối kênh SV #${sId}:`, err)
    }
  }

  /**
   * Gửi request_offer kèm retry nếu Sinh viên chưa phản hồi
   */
  sendRequestOfferWithRetry(sinhVienId, restart = false) {
    const conn = this.connections.get(sinhVienId)
    if (!conn) return

    clearTimeout(conn.retryTimer)
    conn.connectionState = 'connecting'
    conn.onStateChange?.('connecting')

    // Bắt đầu gửi yêu cầu Offer
    this.sendSignal(sinhVienId, 'request_offer', restart ? { restart: true } : null)

    // Nếu sau 4s chưa nhận được Offer hoặc P2P chưa connected, thử lại tối đa 3 lần
    if (conn.retryCount < 3 && conn.connectionState !== 'connected') {
      conn.retryTimer = setTimeout(() => {
        if (conn.connectionState !== 'connected' && this.connections.has(sinhVienId)) {
          conn.retryCount++
          debugLog(`Retry gửi request_offer lần ${conn.retryCount} cho SV #${sinhVienId}`)
          this.sendRequestOfferWithRetry(sinhVienId, restart)
        }
      }, 4000)
    } else if (conn.connectionState !== 'connected') {
      conn.connectionState = 'disconnected'
      conn.onStateChange?.('disconnected')
    }
  }

  /**
   * Xử lý tín hiệu nhận được từ Sinh viên
   */
  async handleStudentSignal(sinhVienId, event) {
    const conn = this.connections.get(sinhVienId)
    if (!conn) return

    const { type, data } = event

    switch (type) {
      case 'offer':
        debugLog(`Đang xử lý Offer từ SV #${sinhVienId}`)
        clearTimeout(conn.retryTimer)
        conn.offerQueue = conn.offerQueue
          .then(() => this.handleOffer(sinhVienId, data))
          .catch((error) => console.error(`[WebRTC Receiver] Offer queue SV #${sinhVienId}:`, error))
        await conn.offerQueue
        break

      case 'candidate':
        await this.handleRemoteCandidate(sinhVienId, data)
        break

      case 'camera_ready':
        debugLog(`SV #${sinhVienId} camera sẵn sàng. Yêu cầu tạo Offer...`)
        if (typeof conn.onStateChange === 'function') {
          conn.onStateChange('connecting')
        }
        conn.retryCount = 0
        this.sendRequestOfferWithRetry(sinhVienId)
        break

      case 'camera_stopped':
        debugLog(`SV #${sinhVienId} camera đã tắt`)
        clearTimeout(conn.retryTimer)
        conn.retryTimer = null
        conn.retryCount = 0
        this.closePeerConnection(sinhVienId)
        if (typeof conn.onStateChange === 'function') {
          conn.onStateChange('camera_stopped')
        }
        break

      default:
        break
    }
  }

  /**
   * Khởi tạo RTCPeerConnection cho Receiver
   * QUAN TRỌNG: Không gọi addTransceiver trước setRemoteDescription(offer)
   */
  setupPeerConnection(sinhVienId) {
    const conn = this.connections.get(sinhVienId)
    if (!conn) return null

    this.closePeerConnection(sinhVienId)

    const pc = new RTCPeerConnection(RTC_CONFIG)
    conn.pc = pc
    conn.pendingCandidates = []

    // Lắng nghe nhận remote video track từ Sinh viên
    pc.ontrack = (event) => {
      debugLog(`ĐÃ NHẬN remote video track từ SV #${sinhVienId}`)
      if (typeof conn.onTrack === 'function') {
        const remoteStream = event.streams?.[0] || new MediaStream([event.track])
        conn.onTrack(remoteStream)
      }
    }

    // ICE Candidate của Giảng viên gửi ngược lại Sinh viên
    pc.onicecandidate = (event) => {
      if (event.candidate && event.candidate.candidate) {
        this.sendSignal(sinhVienId, 'candidate', event.candidate.toJSON())
      }
    }

    // Theo dõi trạng thái kết nối P2P
    pc.onconnectionstatechange = () => {
      const state = pc.connectionState
      debugLog(`P2P ConnectionState SV #${sinhVienId}: ${state}`)
      conn.connectionState = state

      if (state === 'connected') {
        clearTimeout(conn.retryTimer)
      }

      if (typeof conn.onStateChange === 'function') {
        conn.onStateChange(state)
      }
    }

    pc.oniceconnectionstatechange = () => {
      debugLog(`ICE ConnectionState SV #${sinhVienId}: ${pc.iceConnectionState}`)
    }

    return pc
  }

  /**
   * Xử lý Offer từ Sinh viên và tạo Answer phản hồi
   */
  async handleOffer(sinhVienId, offerData) {
    const conn = this.connections.get(sinhVienId)
    if (!conn) return

    let diagnostics
    let remoteDescription
    try {
      if (offerData?.type !== 'offer') {
        throw new Error('payload phải là object RTCSessionDescriptionInit có type="offer".')
      }
      const prepared = await prepareRemoteDescription(offerData, `Offer nhận từ Sinh viên #${sinhVienId}`)
      diagnostics = prepared.diagnostics
      remoteDescription = prepared.description
      logSdpDiagnostics(`Receiver SV #${sinhVienId}`, 'incoming offer', diagnostics)
    } catch (err) {
      console.error(`[WebRTC Receiver] Offer không hợp lệ từ SV #${sinhVienId}: ${err.message}`, err.diagnostics || {})
      conn.onStateChange?.('failed')
      return
    }

    if (conn.seenOfferChecksums.has(diagnostics.checksum)) {
      debugLog(`Bỏ qua Offer trùng checksum từ SV #${sinhVienId}`)
      return
    }

    const pc = this.setupPeerConnection(sinhVienId)
    if (!pc) return

    try {
      // 1. Gán Remote Description (Offer của Sinh viên)
      await pc.setRemoteDescription(new RTCSessionDescription(remoteDescription))
      if (conn.pc !== pc) {
        debugLog(`Bỏ qua Offer đến trễ của PeerConnection cũ SV #${sinhVienId}`)
        return
      }
      debugLog(`Đã setRemoteDescription(offer) cho SV #${sinhVienId}`)

      // 2. Tạo Answer và gán Local Description NGAY LẬP TỨC
      const answer = await pc.createAnswer()
      await pc.setLocalDescription(answer)
      if (conn.pc !== pc) {
        debugLog(`Bỏ qua Answer của PeerConnection cũ SV #${sinhVienId}`)
        return
      }
      debugLog(`Đã tạo Answer cho SV #${sinhVienId}`)

      // 3. Gửi Answer sang Sinh viên
      const localDescription = pc.localDescription
      const answerDiagnostics = await inspectSdp(localDescription, `Answer phía Giảng viên #${sinhVienId}`)
      logSdpDiagnostics(`Receiver SV #${sinhVienId}`, 'outgoing answer', answerDiagnostics)
      const answerSent = await this.sendSignal(sinhVienId, 'answer', localDescription.toJSON())
      if (!answerSent || conn.pc !== pc) return
      conn.lastOfferChecksum = diagnostics.checksum
      conn.seenOfferChecksums.add(diagnostics.checksum)
      if (conn.seenOfferChecksums.size > 8) {
        const oldest = conn.seenOfferChecksums.values().next().value
        conn.seenOfferChecksums.delete(oldest)
      }

      // 4. Xả các ICE Candidate đến trước khi có Remote Description
      while (conn.pendingCandidates.length > 0) {
        const candidate = conn.pendingCandidates.shift()
        if (candidate && candidate.candidate) {
          try {
            await pc.addIceCandidate(new RTCIceCandidate(candidate))
          } catch (candErr) {
            console.warn(`[WebRTC Receiver] Bỏ qua ICE candidate lỗi:`, candErr.message)
          }
        }
      }
    } catch (err) {
      if (conn.pc !== pc) {
        debugLog(`Bỏ qua lỗi từ PeerConnection cũ SV #${sinhVienId}: ${err.message}`)
        return
      }
      console.error(`[WebRTC Receiver] Lỗi xử lý Offer của SV #${sinhVienId}:`, err)
      if (typeof conn.onStateChange === 'function') {
        conn.onStateChange('failed')
      }
    }
  }

  /**
   * Thêm ICE Candidate từ Sinh viên
   */
  async handleRemoteCandidate(sinhVienId, candidateData) {
    const conn = this.connections.get(sinhVienId)
    if (!conn || !candidateData || !candidateData.candidate) return

    try {
      if (conn.pc && conn.pc.remoteDescription) {
        await conn.pc.addIceCandidate(new RTCIceCandidate(candidateData))
      } else {
        conn.pendingCandidates.push(candidateData)
      }
    } catch (err) {
      console.warn(`[WebRTC Receiver] Lỗi thêm ICE Candidate của SV #${sinhVienId}:`, err.message)
    }
  }

  /**
   * Gửi tín hiệu signaling tới Sinh viên qua API Sanctum
   */
  async sendSignal(sinhVienId, type, data) {
    if (!this.phienId || !sinhVienId) return

    try {
      await api.post('/webrtc/signal', {
        phienId: this.phienId,
        sinhVienId,
        type,
        data,
      })
      return true
    } catch (err) {
      console.warn(`[WebRTC Receiver] Lỗi gửi signal '${type}' tới SV #${sinhVienId}:`, err.message)
      return false
    }
  }

  /**
   * Yêu cầu kết nối lại webcam của 1 sinh viên
   */
  retryStudent(sinhVienId) {
    debugLog(`Thủ công yêu cầu kết nối lại SV #${sinhVienId}...`)
    const conn = this.connections.get(sinhVienId)
    if (!conn || !conn.subscribed) {
      conn?.onStateChange?.('failed')
      return false
    }
    conn.retryCount = 0
    this.closePeerConnection(sinhVienId)
    this.sendRequestOfferWithRetry(sinhVienId, true)
    return true
  }

  /**
   * Đóng PeerConnection của 1 sinh viên
   */
  closePeerConnection(sinhVienId) {
    const conn = this.connections.get(sinhVienId)
    if (conn?.pc) {
      conn.pc.onicecandidate = null
      conn.pc.ontrack = null
      conn.pc.onconnectionstatechange = null
      conn.pc.oniceconnectionstatechange = null
      conn.pc.close()
      conn.pc = null
    }
    if (conn) {
      conn.pendingCandidates = []
      conn.connectionState = 'disconnected'
    }
  }

  /**
   * Dừng theo dõi một Sinh viên
   */
  stopWatching(sinhVienId) {
    const sId = Number(sinhVienId)
    const conn = this.connections.get(sId)
    if (!conn) return

    clearTimeout(conn.retryTimer)
    this.closePeerConnection(sId)

    if (conn.channel) {
      const channelName = `exam-monitor.${this.phienId}.student.${sId}`
      echo.leave(channelName)
      conn.channel = null
    }

    this.connections.delete(sId)
  }

  /**
   * Dừng toàn bộ các kết nối khi Giảng viên thoát trang giám sát
   */
  stopAll() {
    for (const sId of this.connections.keys()) {
      this.stopWatching(sId)
    }
    this.connections.clear()
    this.phienId = null
  }
}

export default new TeacherReceiver()
