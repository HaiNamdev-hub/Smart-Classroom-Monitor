<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TieuChiLuaChonSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $managedCodes = [
            'TC01', 'TC02', 'TC03',
            'TC14', 'TC15', 'TC16', 'TC17',
            'TC19', 'TC20', 'TC21',
            'TC22', 'TC23', 'TC24', 'TC26',
        ];

        $tieuChiIds = DB::table('tieu_chi')
            ->whereIn('ma_tieu_chi', $managedCodes)
            ->pluck('id', 'ma_tieu_chi');

        DB::table('tieu_chi_lua_chon')
            ->whereIn('tieu_chi_id', $tieuChiIds->values()->toArray())
            ->delete();

        $options = [
            [
                'tieu_chi_id' => $tieuChiIds['TC01'],
                'noi_dung' => 'Điểm TB đánh giá thường xuyên học kỳ ≥ 9',
                'diem_tuong_ung' => 6,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC01'],
                'noi_dung' => 'Điểm TB đánh giá thường xuyên học kỳ từ 7 đến cận 9',
                'diem_tuong_ung' => 5,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC01'],
                'noi_dung' => 'Điểm TB đánh giá thường xuyên học kỳ từ 5 đến cận 7',
                'diem_tuong_ung' => 4,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC01'],
                'noi_dung' => 'Điểm TB đánh giá thường xuyên học kỳ từ 4 đến cận 5',
                'diem_tuong_ung' => 2,
                'thu_tu' => 4,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC01'],
                'noi_dung' => 'Điểm TB đánh giá thường xuyên học kỳ từ 01 đến cận 04',
                'diem_tuong_ung' => 1,
                'thu_tu' => 5,
            ],

            [
                'tieu_chi_id' => $tieuChiIds['TC02'],
                'noi_dung' => 'Tham gia đầy đủ hoạt động, sự kiện liên quan đến NCKH, học thuật, chuyên môn',
                'diem_tuong_ung' => 2,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC02'],
                'noi_dung' => 'Có công bố khoa học tại Hội nghị hoặc tạp chí khoa học chuyên ngành hoặc tham gia các cuộc thi SV NCKH, Olympic',
                'diem_tuong_ung' => 2,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC02'],
                'noi_dung' => 'Đạt giải trong các cuộc thi SV NCKH, Olympic',
                'diem_tuong_ung' => 2,
                'thu_tu' => 3,
            ],

            [
                'tieu_chi_id' => $tieuChiIds['TC03'],
                'noi_dung' => 'Loại xuất sắc',
                'diem_tuong_ung' => 8,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC03'],
                'noi_dung' => 'Loại Giỏi',
                'diem_tuong_ung' => 7,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC03'],
                'noi_dung' => 'Loại Khá',
                'diem_tuong_ung' => 6,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC03'],
                'noi_dung' => 'Loại Trung bình',
                'diem_tuong_ung' => 4,
                'thu_tu' => 4,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC03'],
                'noi_dung' => 'Loại Yếu nhưng chưa bị cảnh báo kết quả học tập',
                'diem_tuong_ung' => 2,
                'thu_tu' => 5,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC03'],
                'noi_dung' => 'Loại Yếu nhưng bị cảnh báo kết quả học tập lần 1',
                'diem_tuong_ung' => 1,
                'thu_tu' => 6,
            ],

            [
                'tieu_chi_id' => $tieuChiIds['TC14'],
                'noi_dung' => 'Tham gia và chấp hành tốt các hoạt động',
                'diem_tuong_ung' => 5,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC14'],
                'noi_dung' => 'Vắng 01 buổi không có lý do',
                'diem_tuong_ung' => 3,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC14'],
                'noi_dung' => 'Vắng 02 buổi không có lý do',
                'diem_tuong_ung' => 2,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC14'],
                'noi_dung' => 'Vắng từ 02 buổi trở lên không có lý do hoặc không tham gia',
                'diem_tuong_ung' => 0,
                'thu_tu' => 4,
            ],

            [
                'tieu_chi_id' => $tieuChiIds['TC15'],
                'noi_dung' => 'Tham gia đầy đủ, có hiệu quả các hoạt động lớp hoặc các đơn vị tổ chức ghi nhận',
                'diem_tuong_ung' => 5,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC15'],
                'noi_dung' => 'Tham gia có hiệu quả từ 50% các hoạt động trở lên được lớp hoặc đơn vị tổ chức ghi nhận',
                'diem_tuong_ung' => 3,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC15'],
                'noi_dung' => 'Tích cực vận động mọi người tham gia hoặc hưởng ứng tích cực các phong trào',
                'diem_tuong_ung' => 2,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC15'],
                'noi_dung' => 'Vắng trên 50% số buổi của các hoạt động',
                'diem_tuong_ung' => 1,
                'thu_tu' => 4,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC15'],
                'noi_dung' => 'Không tham gia',
                'diem_tuong_ung' => 0,
                'thu_tu' => 5,
            ],

            [
                'tieu_chi_id' => $tieuChiIds['TC16'],
                'noi_dung' => 'Tham gia đầy đủ, có hiệu quả các hoạt động',
                'diem_tuong_ung' => 5,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC16'],
                'noi_dung' => 'Tham gia tích cực, có hiệu quả từ 01 hoạt động trở lên',
                'diem_tuong_ung' => 3,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC16'],
                'noi_dung' => 'Là thành viên tích cực hưởng ứng các hoạt động',
                'diem_tuong_ung' => 2,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC16'],
                'noi_dung' => 'Vắng trên 50% số buổi của các hoạt động',
                'diem_tuong_ung' => 1,
                'thu_tu' => 4,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC16'],
                'noi_dung' => 'Không tham gia',
                'diem_tuong_ung' => 0,
                'thu_tu' => 5,
            ],

            [
                'tieu_chi_id' => $tieuChiIds['TC17'],
                'noi_dung' => 'Tham gia tích cực nhiều hoạt động hoặc có ý thức tố giác các tệ nạn xã hội',
                'diem_tuong_ung' => 3,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC17'],
                'noi_dung' => 'Tham gia một hoạt động đạt hiệu quả',
                'diem_tuong_ung' => 2,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC17'],
                'noi_dung' => 'Có ý thức tham gia hoặc hưởng ứng các hoạt động tuyên truyền, phòng chống tệ nạn xã hội',
                'diem_tuong_ung' => 1,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC17'],
                'noi_dung' => 'Bị nhắc nhở 1 lần do vi phạm các tệ nạn xã hội nhưng chưa đến mức xử lý kỷ luật',
                'diem_tuong_ung' => 0,
                'thu_tu' => 4,
            ],            // ===================== TC19 - IV.1 =====================
            [
                'tieu_chi_id' => $tieuChiIds['TC19'],
                'noi_dung' => 'Chấp hành đúng và tham gia tuyên truyền tốt, được khen thưởng',
                'diem_tuong_ung' => 10,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC19'],
                'noi_dung' => 'Chấp hành đúng và tham gia tuyên truyền tốt',
                'diem_tuong_ung' => 8,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC19'],
                'noi_dung' => 'Chấp hành đúng các quy định',
                'diem_tuong_ung' => 5,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC19'],
                'noi_dung' => 'Bị nhắc nhở, lập biên bản do vi phạm các quy định',
                'diem_tuong_ung' => 0,
                'thu_tu' => 4,
            ],

            // ===================== TC20 - IV.2 =====================
            [
                'tieu_chi_id' => $tieuChiIds['TC20'],
                'noi_dung' => 'Tích cực, nhiệt tình tham gia các hoạt động đạt hiệu quả được Học viện hoặc các tổ chức khen thưởng',
                'diem_tuong_ung' => 10,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC20'],
                'noi_dung' => 'Tham gia tích cực các hoạt động được lớp hoặc tập thể ghi nhận',
                'diem_tuong_ung' => 8,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC20'],
                'noi_dung' => 'Có ý thức tham gia hoặc hưởng ứng các hoạt động',
                'diem_tuong_ung' => 5,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC20'],
                'noi_dung' => 'Tham gia các hoạt động nhưng gây mất đoàn kết',
                'diem_tuong_ung' => 0,
                'thu_tu' => 4,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC20'],
                'noi_dung' => 'Không tham gia',
                'diem_tuong_ung' => 0,
                'thu_tu' => 5,
            ],

            // ===================== TC21 - IV.3 =====================
            [
                'tieu_chi_id' => $tieuChiIds['TC21'],
                'noi_dung' => 'Có ý thức xây dựng tập thể lớp đoàn kết, giữ gìn giảng đường, nơi cư trú văn minh, sạch đẹp, thực hiện tốt văn hóa học đường',
                'diem_tuong_ung' => 5,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC21'],
                'noi_dung' => 'Bị nhắc nhở hoặc kiểm điểm 1 lần',
                'diem_tuong_ung' => 1,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC21'],
                'noi_dung' => 'Bị nhắc nhở hoặc kiểm điểm 2 lần',
                'diem_tuong_ung' => 0,
                'thu_tu' => 3,
            ],

            // ===================== TC22 - V.1a =====================
            [
                'tieu_chi_id' => $tieuChiIds['TC22'],
                'noi_dung' => 'Hoàn thành xuất sắc nhiệm vụ, được khen thưởng hoặc được lãnh đạo các đơn vị, CVHT, tập thể ghi nhận',
                'diem_tuong_ung' => 7,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC22'],
                'noi_dung' => 'Hoàn thành tốt nhiệm vụ',
                'diem_tuong_ung' => 6,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC22'],
                'noi_dung' => 'Hoàn thành nhiệm vụ',
                'diem_tuong_ung' => 4,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC22'],
                'noi_dung' => 'Không hoàn thành nhiệm vụ',
                'diem_tuong_ung' => 0,
                'thu_tu' => 4,
            ],

            // ===================== TC23 - V.1b =====================
            [
                'tieu_chi_id' => $tieuChiIds['TC23'],
                'noi_dung' => 'Hoàn thành xuất sắc nhiệm vụ, được khen thưởng hoặc được lãnh đạo các đơn vị, CVHT, tập thể ghi nhận',
                'diem_tuong_ung' => 7,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC23'],
                'noi_dung' => 'Hoàn thành tốt nhiệm vụ',
                'diem_tuong_ung' => 6,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC23'],
                'noi_dung' => 'Hoàn thành nhiệm vụ',
                'diem_tuong_ung' => 4,
                'thu_tu' => 3,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC23'],
                'noi_dung' => 'Không hoàn thành nhiệm vụ',
                'diem_tuong_ung' => 0,
                'thu_tu' => 4,
            ],

            // ===================== TC24 - V.1c =====================
            [
                'tieu_chi_id' => $tieuChiIds['TC24'],
                'noi_dung' => 'Cấp trưởng: Liên chi đoàn, Lớp sinh viên, Lớp học phần, chi đoàn, chi bộ, chi hội, Chủ nhiệm các CLB, Đội',
                'diem_tuong_ung' => 3,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC24'],
                'noi_dung' => 'Cấp phó: Liên chi đoàn, Lớp sinh viên, chi đoàn, chi bộ, chi hội, các CLB, Đội',
                'diem_tuong_ung' => 2,
                'thu_tu' => 2,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC24'],
                'noi_dung' => 'Ủy viên: BCH Đoàn, Hội, CLB, Đội',
                'diem_tuong_ung' => 1,
                'thu_tu' => 3,
            ],

            // ===================== TC26 - V.2 =====================
            [
                'tieu_chi_id' => $tieuChiIds['TC26'],
                'noi_dung' => 'Được khen thưởng từ cấp Học viện trở lên',
                'diem_tuong_ung' => 7,
                'thu_tu' => 1,
            ],
            [
                'tieu_chi_id' => $tieuChiIds['TC26'],
                'noi_dung' => 'Đạt khen thưởng từ cấp Khoa trở lên',
                'diem_tuong_ung' => 5,
                'thu_tu' => 2,
            ],
        ];

        foreach ($options as $option) {
            DB::table('tieu_chi_lua_chon')->insert($option);
        }
    }
}