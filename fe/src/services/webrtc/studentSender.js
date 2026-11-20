import echo from '../reverb'
import api from '../api'
import { inspectSdp, logSdpDiagnostics, prepareRemoteDescription } from './sdpDiagnostics'
import { getRtcConfiguration } from './iceConfig'

function debugLog(...args) {
  if (import.meta.env.DEV) {
    console.log('[WebRTC Sender Debug]', ...args)
  }
}

/**
 * Quản lý phía Sinh viên (WebRTC Sender)
 * Truyền trực tiếp MediaStream của webcam sang Giảng viên qua WebRTC P2P
 */
export class StudentSender {
  constructor() {
    this.phienId = null
    this.sinhVienId = null
    this.stream = null
    this.peerConnection = null
    this.echoChannel = null
    this.pendingCandidates = []
    this.connected = false
    this.onStateChange = null
    this.subscribed = false
    this.offerInProgress = false
    this.recoveryTimer = null
    this.recoveryCount = 0
  }

  /**
   * Bắt đầu kết nối giám sát WebRTC
   * @param {number|string} phienId ID phiên giám sát đang diễn ra
   * @param {number|string} sinhVienId ID của Sinh viên
   * @param {MediaStream} stream Luồng webcam từ GiamSatCamera (không gọi getUserMedia lần 2)
   * @param {Function} onStateChange Callback khi trạng thái kết nối WebRTC thay đổi
   */
  async init(phienId, sinhVienId, stream, onStateChange = null) {
    if (!phienId || !sinhVienId) {
      console.warn('[WebRTC Sender] Thiếu phienId hoặc sinhVienId, chưa thể kết nối WebRTC.')
      return
    }

    const nextPhienId = Number(phienId)
    const nextSinhVienId = Number(sinhVienId)
    if (this.phienId === nextPhienId && this.sinhVienId === nextSinhVienId) {
      this.onStateChange = onStateChange
      if (this.stream !== stream) await this.updateStream(stream)
      return
    }

    this.closePeerConnection()
    this.unsubscribeChannel()
    this.recoveryCount = 0
    this.phienId = nextPhienId
    this.sinhVienId = nextSinhVienId
    this.stream = stream
    this.onStateChange = onStateChange

    debugLog(`Khởi tạo WebRTC Sender: phienId=${this.phienId}, sinhVienId=${this.sinhVienId}`)

    // 1. Đăng ký kênh Private Channel trao đổi tín hiệu
    this.subscribeChannel()
  }

  /**
   * Lắng nghe Private Channel qua Reverb
   */
  subscribeChannel() {
    if (this.echoChannel) {
      this.unsubscribeChannel()
    }

    const channelName = `exam-monitor.${this.phienId}.student.${this.sinhVienId}`

    try {
      this.echoChannel = echo.private(channelName)

      this.echoChannel.listen('.webrtc.signal', (event) => {
        // Chỉ xử lý tín hiệu từ Giảng viên gửi tới
        if (event.senderRole !== 'teacher') return
        debugLog(`Nhận signal '${event.type}' từ Giảng viên`)
        this.handleTeacherSignal(event)
      })

      // Khi kênh đã xác thực thành công trên Reverb
      const onSubscribed = () => {
        debugLog(`Kênh signaling đã subscribed thành công: ${channelName}`)
        this.subscribed = true
        if (this.stream) {
          this.sendSignal('camera_ready', null)
        }
      }

      if (
        Boolean(this.echoChannel?.subscribed) ||
        Boolean(this.echoChannel?.subscription?.subscribed)
      ) {
        onSubscribed()
      } else {
        this.echoChannel.subscribed(onSubscribed)
      }

      this.echoChannel.error?.((error) => {
        this.subscribed = false
        console.error('[WebRTC Sender] Không xác thực được signaling channel:', error)
      })
    } catch (err) {
      console.error('[WebRTC Sender] Lỗi tham gia kênh signaling:', err)
    }
  }

  unsubscribeChannel() {
    if (this.echoChannel) {
      const channelName = `exam-monitor.${this.phienId}.student.${this.sinhVienId}`
      echo.leave(channelName)
      this.echoChannel = null
      this.subscribed = false
    }
  }

  /**
   * Xử lý tín hiệu nhận được từ Giảng viên
   */
  async handleTeacherSignal(event) {
    const { type, data } = event

    switch (type) {
      case 'request_offer':
        debugLog('Giảng viên yêu cầu Offer. Bắt đầu tạo Offer...')
        await this.createAndSendOffer(Boolean(data?.restart))
        break

      case 'answer':
        debugLog('Nhận Answer từ Giảng viên. Đang thiết lập Remote Description...')
        await this.handleAnswer(data)
        break

      case 'candidate':
        await this.handleRemoteCandidate(data)
        break

      default:
        break
    }
  }

  /**
   * Khởi tạo hoặc làm mới RTCPeerConnection
   */
  async setupPeerConnection() {
    this.closePeerConnection()
    const stream = this.stream
    const rtcConfig = await getRtcConfiguration()
    if (this.stream !== stream || !stream) return null

    this.peerConnection = new RTCPeerConnection({ iceServers: rtcConfig.iceServers })
    this.pendingCandidates = []

    // Đưa video tracks vào PeerConnection (chỉ video, không gửi audio)
    if (this.stream) {
      const videoTracks = this.stream.getVideoTracks()
      videoTracks.forEach((track) => {
        if (track.readyState === 'live') {
          debugLog(`Thêm video track: ${track.label || track.id}`)
          this.peerConnection.addTrack(track, this.stream)
        }
      })
    }

    // Lắng nghe ICE Candidate từ trình duyệt
    this.peerConnection.onicecandidate = (event) => {
      if (event.candidate && event.candidate.candidate) {
        this.sendSignal('candidate', event.candidate.toJSON())
      }
    }

    // Lắng nghe thay đổi trạng thái kết nối P2P
    this.peerConnection.onconnectionstatechange = () => {
      const state = this.peerConnection?.connectionState || 'closed'
      debugLog(`P2P ConnectionState: ${state}`)
      this.connected = state === 'connected'
      if (state === 'connected') {
        clearTimeout(this.recoveryTimer)
        this.recoveryTimer = null
        this.recoveryCount = 0
      } else if (state === 'failed' || state === 'disconnected') {
        this.scheduleRecovery()
      }

      if (typeof this.onStateChange === 'function') {
        this.onStateChange(state)
      }
    }

    this.peerConnection.oniceconnectionstatechange = () => {
      debugLog(`ICE ConnectionState: ${this.peerConnection?.iceConnectionState}`)
    }
    return this.peerConnection
  }

  scheduleRecovery() {
    if (this.recoveryTimer || this.recoveryCount >= 3 || !this.stream) return
    this.recoveryTimer = setTimeout(async () => {
      this.recoveryTimer = null
      if (!this.stream || this.connected || this.recoveryCount >= 3) return
      this.recoveryCount++
      await this.createAndSendOffer(true)
    }, 6000)
  }

  /**
   * Tạo Offer và gửi sang Giảng viên
   */
  async createAndSendOffer(forceRestart = false) {
    if (this.offerInProgress) return
    if (!this.stream || !this.stream.getVideoTracks().some((t) => t.readyState === 'live')) {
      console.warn('[WebRTC Sender] Không có webcam live để tạo offer.')
      this.sendSignal('camera_stopped', null)
      return
    }

    this.offerInProgress = true
    try {
      const currentPc = this.peerConnection
      if (!forceRestart && currentPc && ['connected', 'connecting'].includes(currentPc.connectionState)) {
        if (currentPc.localDescription?.type === 'offer') {
          await this.sendSignal('offer', currentPc.localDescription.toJSON())
        }
        return
      }

      if (!forceRestart && currentPc && currentPc.signalingState === 'have-local-offer' && currentPc.localDescription) {
        await this.sendSignal('offer', currentPc.localDescription.toJSON())
        return
      }

      if (forceRestart || !currentPc || ['failed', 'closed', 'disconnected'].includes(currentPc.connectionState)) {
        await this.setupPeerConnection()
      }

      if (!this.peerConnection) return

      const offer = await this.peerConnection.createOffer({
        offerToReceiveAudio: false,
        offerToReceiveVideo: false,
      })

      await this.peerConnection.setLocalDescription(offer)
      debugLog('Đã tạo Offer và setLocalDescription')

      const localDescription = this.peerConnection.localDescription
      const diagnostics = await inspectSdp(localDescription, 'Offer phía Sinh viên')
      logSdpDiagnostics('Sender', 'outgoing offer', diagnostics)
      await this.sendSignal('offer', localDescription.toJSON())
    } catch (err) {
      console.error('[WebRTC Sender] Lỗi tạo và gửi Offer:', err)
    } finally {
      this.offerInProgress = false
    }
  }

  /**
   * Nhận Answer từ Giảng viên
   */
  async handleAnswer(answerData) {
    const pc = this.peerConnection
    if (!pc || !answerData) return

    try {
      if (answerData.type !== 'answer') throw new Error('payload phải có type="answer".')
      const prepared = await prepareRemoteDescription(answerData, 'Answer nhận từ Giảng viên')
      const diagnostics = prepared.diagnostics
      logSdpDiagnostics('Sender', 'incoming answer', diagnostics)
      if (this.peerConnection !== pc || pc.signalingState !== 'have-local-offer') {
        debugLog('Bỏ qua Answer đến trễ hoặc thuộc PeerConnection cũ')
        return
      }
      await pc.setRemoteDescription(new RTCSessionDescription(prepared.description))
      debugLog('Đã gán RemoteDescription Answer thành công')

      // Xử lý các ICE candidate đến trước Remote Description
      while (this.pendingCandidates.length > 0) {
        const candidate = this.pendingCandidates.shift()
        if (candidate && candidate.candidate && this.peerConnection === pc) {
          try {
            await pc.addIceCandidate(new RTCIceCandidate(candidate))
          } catch (candErr) {
            console.warn('[WebRTC Sender] Bỏ qua ICE candidate lỗi:', candErr.message)
          }
        }
      }
    } catch (err) {
      console.error('[WebRTC Sender] Lỗi gán RemoteDescription Answer:', err)
    }
  }

  /**
   * Nhận ICE Candidate từ Giảng viên
   */
  async handleRemoteCandidate(candidateData) {
    if (!candidateData || !candidateData.candidate) return

    try {
      if (this.peerConnection && this.peerConnection.remoteDescription) {
        await this.peerConnection.addIceCandidate(new RTCIceCandidate(candidateData))
      } else {
        this.pendingCandidates.push(candidateData)
      }
    } catch (err) {
      console.warn('[WebRTC Sender] Lỗi thêm ICE candidate từ Giảng viên:', err.message)
    }
  }

  /**
   * Cập nhật luồng webcam khi camera bật lại
   */
  async updateStream(newStream) {
    this.stream = newStream
    this.recoveryCount = 0

    if (this.stream) {
      debugLog('Camera đã bật lại, gửi camera_ready.')
      this.closePeerConnection()
      if (this.subscribed) await this.sendSignal('camera_ready', null)
    }
  }

  /**
   * Báo hiệu khi webcam bị dừng/tắt
   */
  async notifyCameraStopped() {
    this.stream = null
    await this.sendSignal('camera_stopped', null)
    this.closePeerConnection()

    if (typeof this.onStateChange === 'function') {
      this.onStateChange('camera_stopped')
    }
  }

  /**
   * Gửi tín hiệu WebRTC signaling qua API Sanctum
   */
  async sendSignal(type, data) {
    if (!this.phienId || !this.sinhVienId) return

    try {
      await api.post('/webrtc/signal', {
        phienId: this.phienId,
        sinhVienId: this.sinhVienId,
        type,
        data,
      })
    } catch (err) {
      console.warn(`[WebRTC Sender] Không thể gửi signaling '${type}':`, err.message)
    }
  }

  /**
   * Đóng PeerConnection (không dừng track vì stream thuộc GiamSatCamera)
   */
  closePeerConnection() {
    clearTimeout(this.recoveryTimer)
    this.recoveryTimer = null
    if (this.peerConnection) {
      this.peerConnection.onicecandidate = null
      this.peerConnection.onconnectionstatechange = null
      this.peerConnection.oniceconnectionstatechange = null
      this.peerConnection.close()
      this.peerConnection = null
    }
    this.connected = false
    this.pendingCandidates = []
  }

  /**
   * Dọn dẹp toàn bộ khi Sinh viên unmount hoặc nộp bài
   */
  disconnect() {
    if (this.connected) {
      this.sendSignal('camera_stopped', null).catch(() => {})
    }
    this.closePeerConnection()
    this.unsubscribeChannel()
    this.stream = null
    this.phienId = null
    this.sinhVienId = null
  }
}

export default new StudentSender()
