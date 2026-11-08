<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CanhBaoExamEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $phienId;
    public int $sinhVienId;
    public string $loaiCanhBao;
    public string $noiDung;
    public string $thoiGian;
    public int $soLanCamera;
    public int $soLanDienThoai;
    public int $soLanViPham;
    public bool $biKhoa;
    public ?string $lyDoKhoa;

    public function __construct(
        int $phienId,
        int $sinhVienId,
        string $loaiCanhBao,
        string $noiDung,
        string $thoiGian,
        int $soLanCamera,
        int $soLanDienThoai,
        int $soLanViPham,
        bool $biKhoa = false,
        ?string $lyDoKhoa = null
    ) {
        $this->phienId = $phienId;
        $this->sinhVienId = $sinhVienId;
        $this->loaiCanhBao = $loaiCanhBao;
        $this->noiDung = $noiDung;
        $this->thoiGian = $thoiGian;
        $this->soLanCamera = $soLanCamera;
        $this->soLanDienThoai = $soLanDienThoai;
        $this->soLanViPham = $soLanViPham;
        $this->biKhoa = $biKhoa;
        $this->lyDoKhoa = $lyDoKhoa;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exam-monitor.' . $this->phienId . '.student.' . $this->sinhVienId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'exam.violation';
    }

    public function broadcastWith(): array
    {
        return [
            'phienId' => $this->phienId,
            'sinhVienId' => $this->sinhVienId,
            'loaiCanhBao' => $this->loaiCanhBao,
            'noiDung' => $this->noiDung,
            'thoiGian' => $this->thoiGian,
            'soLanCamera' => $this->soLanCamera,
            'soLanDienThoai' => $this->soLanDienThoai,
            'soLanViPham' => $this->soLanViPham,
            'biKhoa' => $this->biKhoa,
            'lyDoKhoa' => $this->lyDoKhoa,
        ];
    }
}
