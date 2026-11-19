<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRtcSignalEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $phienId;
    public int $sinhVienId;
    public string $senderRole;
    public string $type;
    public mixed $data;

    /**
     * Khởi tạo sự kiện WebRTC signaling
     *
     * @param int $phienId
     * @param int $sinhVienId
     * @param string $senderRole 'teacher' | 'student'
     * @param string $type 'offer' | 'answer' | 'candidate' | 'camera_ready' | 'camera_stopped' | 'request_offer'
     * @param mixed $data
     */
    public function __construct(
        int $phienId,
        int $sinhVienId,
        string $senderRole,
        string $type,
        mixed $data = null
    ) {
        $this->phienId = $phienId;
        $this->sinhVienId = $sinhVienId;
        $this->senderRole = $senderRole;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Kênh phát sóng riêng tư cho từng sinh viên trong phiên thi.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exam-monitor.' . $this->phienId . '.student.' . $this->sinhVienId),
        ];
    }

    /**
     * Tên sự kiện broadcast
     */
    public function broadcastAs(): string
    {
        return 'webrtc.signal';
    }

    /**
     * Dữ liệu gửi qua WebSocket
     */
    public function broadcastWith(): array
    {
        return [
            'phienId' => $this->phienId,
            'sinhVienId' => $this->sinhVienId,
            'senderRole' => $this->senderRole,
            'type' => $this->type,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }
}
