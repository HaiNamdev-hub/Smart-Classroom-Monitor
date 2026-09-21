<?php

namespace Database\Seeders;

use App\Models\NguoiDung;
use App\Models\VaiTro;
use Illuminate\Database\Seeder;

class NguoiDungSeeder extends Seeder
{
    public function run(): void
    {
        $roles = VaiTro::query()->pluck('id', 'ten_vai_tro');
        foreach ([['ho_ten' => 'Quan tri vien', 'email' => 'admin@example.com', 'vai_tro_id' => $roles['quan_tri_vien']], ['ho_ten' => 'Nguyen Van Giang', 'email' => 'giangvien@example.com', 'vai_tro_id' => $roles['giang_vien']], ['ho_ten' => 'Tran Minh An', 'email' => 'sv.an@example.com', 'vai_tro_id' => $roles['sinh_vien']], ['ho_ten' => 'Le Thu Binh', 'email' => 'sv.binh@example.com', 'vai_tro_id' => $roles['sinh_vien']]] as $nguoiDung) {
            NguoiDung::query()->updateOrCreate(['email' => $nguoiDung['email']], $nguoiDung + ['password' => 'password', 'trang_thai' => 'hoat_dong']);
        }
    }
}
