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
    public ?string $eventId;
    public ?string $hoTen;
    public ?string $maSinhVien;
    public ?string $tenLop;
    public ?string $tenBaiKiemTra;
    public ?int $baiTapId;

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
        ?string $lyDoKhoa = null,
        ?string $eventId = null,
        ?string $hoTen = null,
        ?string $maSinhVien = null,
        ?string $tenLop = null,
        ?string $tenBaiKiemTra = null,
        ?int $baiTapId = null
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
        $this->eventId = $eventId;
        $this->hoTen = $hoTen;
        $this->maSinhVien = $maSinhVien;
        $this->tenLop = $tenLop;
        $this->tenBaiKiemTra = $tenBaiKiemTra;
        $this->baiTapId = $baiTapId;
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exam-monitor.' . $this->phienId . '.student.' . $this->sinhVienId),
            new PrivateChannel('exam-session.' . $this->phienId),
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
            'eventId' => $this->eventId,
            'baiTapId' => $this->baiTapId,
            'sinhVienId' => $this->sinhVienId,
            'hoTen' => $this->hoTen,
            'maSinhVien' => $this->maSinhVien,
            'tenLop' => $this->tenLop,
            'tenBaiKiemTra' => $this->tenBaiKiemTra,
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
