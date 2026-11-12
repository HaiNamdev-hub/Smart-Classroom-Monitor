<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamProtectionAlertEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $eventId,
        public int $phienId,
        public int $baiTapId,
        public int $sinhVienId,
        public string $loaiSuKien,
        public string $noiDung,
        public string $thoiGian,
        public string $hoTen,
        public ?string $maSinhVien,
        public string $tenBaiKiemTra,
        public bool $canXemXet = true,
        public bool $daXacMinh = false,
        public ?array $signalInfo = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("exam-monitor.{$this->phienId}.student.{$this->sinhVienId}")];
    }

    public function broadcastAs(): string
    {
        return 'exam.protection-alert';
    }

    public function broadcastWith(): array
    {
        return [
            'eventId' => $this->eventId,
            'phienId' => $this->phienId,
            'baiTapId' => $this->baiTapId,
            'sinhVienId' => $this->sinhVienId,
            'loaiSuKien' => $this->loaiSuKien,
            'noiDung' => $this->noiDung,
            'thoiGian' => $this->thoiGian,
            'hoTen' => $this->hoTen,
            'maSinhVien' => $this->maSinhVien,
            'tenBaiKiemTra' => $this->tenBaiKiemTra,
            'signal' => $this->signalInfo['signal'] ?? null,
            'signalSource' => $this->signalInfo['source'] ?? null,
            'canXemXet' => $this->canXemXet,
            'daXacMinh' => $this->daXacMinh,
        ];
    }
}
