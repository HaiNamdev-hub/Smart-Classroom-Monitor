<?php

namespace App\Http\Controllers;

use App\Models\BaiTap;
use App\Models\ThanhVienLop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BaiTapController extends CrudController
{
    protected string $modelClass = BaiTap::class;

    protected function rules(?Model $record = null): array
    {
        return [
            'lop_hoc_id' => [
                'required',
                'integer',
                'exists:lop_hoc,id',
            ],

            'nguoi_tao_id' => [
                'required',
                'integer',
                'exists:nguoi_dung,id',
            ],

            'tieu_de' => [
                'required',
                'string',
                'max:255',
            ],

            'mo_ta' => [
                'nullable',
                'string',
            ],

            'thoi_gian_bat_dau' => [
                'required',
                'date',
            ],

            'thoi_gian_ket_thuc' => [
                'required',
                'date',
                'after_or_equal:thoi_gian_bat_dau',
            ],

            'han_nop' => [
                'required',
                'date',
            ],

            'cho_phep_nop_tre' => [
                'boolean',
            ],
        ];
    }

    public function getBaiTapSinhVien(
        Request $request
    ): JsonResponse {
        try {
            // =====================================
            // 1. KIỂM TRA TÀI KHOẢN
            // =====================================

            $nguoiDung = $request->user();

            if (!$nguoiDung) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bạn chưa đăng nhập.',
                ], 401);
            }

            // =====================================
            // 2. LẤY ĐÚNG ID SINH VIÊN
            // =====================================

            $sinhVienId = DB::table('sinh_vien')
                ->where(
                    'nguoi_dung_id',
                    $nguoiDung->id
                )
                ->value('id');

            if (!$sinhVienId) {
                return response()->json([
                    'status' => false,
                    'message' =>
                        'Không tìm thấy hồ sơ sinh viên.',
                ], 403);
            }

            // =====================================
            // 3. LẤY LỚP ĐANG HỌC
            // =====================================

            // thanh_vien_lop.sinh_vien_id references nguoi_dung.id.
            // Keep the profile ID for attempt-related records, but use the
            // authenticated account ID for class membership and submissions.
            $studentUserId = (int) $nguoiDung->id;

            $lopHocIds = ThanhVienLop::query()
                ->where(
                    'sinh_vien_id',
                    $studentUserId
                )
                ->where(
                    'trang_thai',
                    'dang_hoc'
                )
                ->pluck('lop_hoc_id');

            // Sinh viên chưa tham gia lớp nào
            if ($lopHocIds->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' =>
                        'Sinh viên chưa tham gia lớp học.',
                    'data' => [],
                ]);
            }

            // =====================================
            // 4. LẤY TẤT CẢ BÀI ĐƯỢC GIAO
            // =====================================

            // KHÔNG LỌC THEO GIỜ BẮT ĐẦU TẠI ĐÂY.
            // Sinh viên cần thấy lịch kiểm tra
            // trước khi bài thi được mở.

            $baiTapQuery = BaiTap::query()
                ->whereIn(
                    'lop_hoc_id',
                    $lopHocIds
                );

            // Keep legacy Phase 3 drafts hidden from students when the optional
            // metadata table exists, without making the exam API depend on it.
            if (Schema::hasTable('ngan_hang_de_tao')) {
                $baiTapQuery->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('ngan_hang_de_tao as ndt')
                        ->whereColumn('ndt.bai_tap_id', 'bai_tap.id')
                        ->where('ndt.trang_thai', 'nhap');
                });
            }

            $baiTaps = $baiTapQuery->with([
                    'lopHoc:id,ma_lop,ten_lop',

                    'nguoiTao:id,ho_ten,email',

                    'baiNops' => function ($query) use (
                        $studentUserId
                    ) {
                        $query->where(
                            'sinh_vien_id',
                            $studentUserId
                        );
                    },
                ])
                // Bài mới hiển thị trước
                ->orderByDesc('id')
                ->get();

            // =====================================
            // 5. XÁC ĐỊNH BÀI KIỂM TRA
            // =====================================

            $baiTapIds = $baiTaps->pluck('id');

            $baiKiemTraIds = DB::table('cau_hoi')
                ->whereIn(
                    'bai_tap_id',
                    $baiTapIds
                )
                ->distinct()
                ->pluck('bai_tap_id')
                ->map(
                    fn ($id) => (string) $id
                )
                ->all();

            $baiKiemTraLookup = array_fill_keys(
                $baiKiemTraIds,
                true
            );

            // =====================================
            // 6. TRẢ DỮ LIỆU CHO VUE
            // =====================================

            $result = $baiTaps->map(
                function ($baiTap) use (
                    $baiKiemTraLookup
                ) {
                    $baiNop = $baiTap
                        ->baiNops
                        ->first();

                    $laBaiKiemTra = isset(
                        $baiKiemTraLookup[
                            (string) $baiTap->id
                        ]
                    );

                    return [
                        'id' => $baiTap->id,

                        'lop_hoc_id' =>
                            $baiTap->lop_hoc_id,

                        'tieu_de' =>
                            $baiTap->tieu_de,

                        'mo_ta' =>
                            $baiTap->mo_ta,

                        // Giữ dạng giờ MySQL
                        // 2026-10-28 08:00:00
                        // để không vô tình lệch +7 giờ
                        // nếu DB đang lưu giờ Việt Nam.
                        'thoi_gian_bat_dau' =>
                            $baiTap
                                ->thoi_gian_bat_dau
                                ?->format('Y-m-d H:i:s'),

                        'thoi_gian_ket_thuc' =>
                            $baiTap
                                ->thoi_gian_ket_thuc
                                ?->format('Y-m-d H:i:s'),

                        'han_nop' =>
                            $baiTap
                                ->han_nop
                                ?->format('Y-m-d H:i:s'),

                        'cho_phep_nop_tre' =>
                            (bool) $baiTap
                                ->cho_phep_nop_tre,

                        'la_bai_kiem_tra' =>
                            $laBaiKiemTra,

                        'ten_lop' =>
                            $baiTap->lopHoc
                                ?->ten_lop,

                        'ma_lop' =>
                            $baiTap->lopHoc
                                ?->ma_lop,

                        'lop_hoc' =>
                            $baiTap->lopHoc
                                ? [
                                    'id' =>
                                        $baiTap
                                            ->lopHoc->id,

                                    'ma_lop' =>
                                        $baiTap
                                            ->lopHoc->ma_lop,

                                    'ten_lop' =>
                                        $baiTap
                                            ->lopHoc->ten_lop,
                                ]
                                : null,

                        'nguoi_tao' =>
                            $baiTap->nguoiTao
                                ? [
                                    'id' =>
                                        $baiTap
                                            ->nguoiTao->id,

                                    'ho_ten' =>
                                        $baiTap
                                            ->nguoiTao->ho_ten,

                                    'email' =>
                                        $baiTap
                                            ->nguoiTao->email,
                                ]
                                : null,

                        'da_nop' =>
                            $baiNop !== null,

                        'bai_nop' =>
                            $baiNop
                                ? [
                                    'id' =>
                                        $baiNop->id,

                                    'thoi_gian_nop' =>
                                        $baiNop
                                            ->thoi_gian_nop,

                                    'trang_thai' =>
                                        $baiNop
                                            ->trang_thai,
                                ]
                                : null,
                    ];
                }
            );

            // =====================================
            // 7. TRẢ KẾT QUẢ
            // =====================================

            return response()->json([
                'status' => true,

                'message' =>
                    'Lấy danh sách bài tập thành công.',

                'data' => $result->values(),
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' =>
                    'Không thể lấy danh sách bài tập.',
            ], 500);
        }
    }
}
