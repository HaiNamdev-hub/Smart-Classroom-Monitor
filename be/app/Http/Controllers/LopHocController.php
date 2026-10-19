<?php

namespace App\Http\Controllers;

use App\Models\LopHoc;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LopHocController extends CrudController
{
    protected string $modelClass = LopHoc::class;

    /*
    |--------------------------------------------------------------------------
    | VALIDATION RULES
    |--------------------------------------------------------------------------
    */

    protected function rules(?Model $record = null): array
    {
        return [
            'ma_lop' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lop_hoc', 'ma_lop')
                    ->ignore($record?->id),
            ],

            'ten_lop' => [
                'required',
                'string',
                'max:255',
            ],

            'giang_vien_id' => [
                'required',
                'integer',
                'exists:nguoi_dung,id',
            ],

            'mo_ta' => [
                'nullable',
                'string',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION MESSAGES
    |--------------------------------------------------------------------------
    */

    protected function messages(): array
    {
        return [
            'ma_lop.required' =>
            'Vui lòng nhập mã lớp.',

            'ma_lop.string' =>
            'Mã lớp không hợp lệ.',

            'ma_lop.max' =>
            'Mã lớp không được vượt quá 255 ký tự.',

            'ma_lop.unique' =>
            'Mã lớp này đã tồn tại.',

            'ten_lop.required' =>
            'Vui lòng nhập tên lớp.',

            'ten_lop.string' =>
            'Tên lớp không hợp lệ.',

            'ten_lop.max' =>
            'Tên lớp không được vượt quá 255 ký tự.',

            'giang_vien_id.required' =>
            'Vui lòng chọn giảng viên.',

            'giang_vien_id.integer' =>
            'Giảng viên không hợp lệ.',

            'giang_vien_id.exists' =>
            'Giảng viên được chọn không tồn tại.',

            'mo_ta.string' =>
            'Mô tả không hợp lệ.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH LỚP HỌC
    |--------------------------------------------------------------------------
    */

    public function getLopHoc(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'giang_vien_id' => [
                'nullable',
                'integer',
                'exists:nguoi_dung,id',
            ],
        ], [
            'search.string' =>
            'Từ khóa tìm kiếm không hợp lệ.',

            'search.max' =>
            'Từ khóa tìm kiếm không được vượt quá 255 ký tự.',

            'giang_vien_id.integer' =>
            'Giảng viên không hợp lệ.',

            'giang_vien_id.exists' =>
            'Giảng viên được chọn không tồn tại.',
        ]);

        $query = LopHoc::with([
            'giangVien:id,ho_ten,email'
        ])
            ->withCount('thanhViens');

        /*
         * Tìm theo mã lớp hoặc tên lớp.
         */
        if (! empty($data['search'])) {
            $search = $data['search'];

            $query->where(function ($q) use ($search) {
                $q->where(
                    'ten_lop',
                    'like',
                    '%' . $search . '%'
                )
                    ->orWhere(
                        'ma_lop',
                        'like',
                        '%' . $search . '%'
                    );
            });
        }

        /*
         * Lọc theo giảng viên.
         */
        if (! empty($data['giang_vien_id'])) {
            $query->where(
                'giang_vien_id',
                $data['giang_vien_id']
            );
        }

        /*
         * Mới nhất trước, mỗi trang 9 lớp.
         */
        return response()->json(
            $query
                ->latest('id')
                ->paginate(9)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH GIẢNG VIÊN
    |--------------------------------------------------------------------------
    */

    public function getGiangVien(): JsonResponse
    {
        $giangVien = NguoiDung::where(
            'trang_thai',
            'hoat_dong'
        )
            ->whereHas(
                'vaiTro',
                function ($q) {
                    $q->where(
                        'ten_vai_tro',
                        'giang_vien'
                    );
                }
            )
            ->orderBy('ho_ten')
            ->get([
                'id',
                'ho_ten',
                'email',
            ]);

        return response()->json([
            'data' => $giangVien,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | THÊM LỚP
    |--------------------------------------------------------------------------
    */

    public function themLopHoc(
        Request $request
    ): JsonResponse {
        return $this->luuLopHoc(
            $request,
            new LopHoc()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SỬA LỚP
    |--------------------------------------------------------------------------
    */

    public function suaLopHoc(
        Request $request,
        int $id
    ): JsonResponse {
        $lopHoc = LopHoc::findOrFail($id);

        return $this->luuLopHoc(
            $request,
            $lopHoc
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LƯU LỚP HỌC
    |--------------------------------------------------------------------------
    */

    private function luuLopHoc(
        Request $request,
        LopHoc $class
    ): JsonResponse {
        $data = $request->validate(
            $this->rules(
                $class->exists
                    ? $class
                    : null
            ),
            $this->messages()
        );

        /*
         * Kiểm tra tài khoản được chọn
         * thực sự là giảng viên đang hoạt động.
         */
        $validTeacher = NguoiDung::whereKey(
            $data['giang_vien_id']
        )
            ->where(
                'trang_thai',
                'hoat_dong'
            )
            ->whereHas(
                'vaiTro',
                function ($q) {
                    $q->where(
                        'ten_vai_tro',
                        'giang_vien'
                    );
                }
            )
            ->exists();

        if (! $validTeacher) {
            return response()->json([
                'message' =>
                'Vui lòng chọn giảng viên đang hoạt động.',
            ], 422);
        }

        $status = $class->exists
            ? 200
            : 201;

        $class->fill($data);
        $class->save();

        return response()->json([
            'message' => $class->wasRecentlyCreated
                ? 'Đã thêm lớp học.'
                : 'Đã cập nhật lớp học.',

            'data' => $class->load(
                'giangVien'
            ),
        ], $status);
    }
    public function xoaLopHoc(int $id): JsonResponse
    {
        $lopHoc = LopHoc::findOrFail($id);

        /*
     * Không cho xóa lớp nếu đã có sinh viên.
     */
        if ($lopHoc->thanhViens()->exists()) {
            return response()->json([
                'message' => 'Không thể xóa lớp học đang có sinh viên.'
            ], 422);
        }

        /*
     * Có thể bổ sung kiểm tra bài tập,
     * phiên giám sát... nếu model đã có quan hệ.
     */

        $lopHoc->delete();

        return response()->json([
            'message' => 'Đã xóa lớp học.'
        ]);
    }
    public function getLopHocSinhVien(Request $request)
    {
        try {
            $sinhVien = $request->user();

            if (!$sinhVien) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bạn chưa đăng nhập.',
                ], 401);
            }

            $lopHocs = \App\Models\ThanhVienLop::query()

                ->where(
                    'sinh_vien_id',
                    $sinhVien->id
                )

                ->with([
                    'lopHoc.giangVien:id,ho_ten,email'
                ])

                ->orderByDesc('created_at')

                ->get()

                ->map(function ($thanhVien) {

                    $lop = $thanhVien->lopHoc;

                    if (!$lop) {
                        return null;
                    }

                    return [
                        'id' => $lop->id,

                        'ma_lop' => $lop->ma_lop,

                        'ten_lop' => $lop->ten_lop,

                        'mo_ta' => $lop->mo_ta,

                        'trang_thai' =>
                        $thanhVien->trang_thai,

                        'giang_vien' => $lop->giangVien
                            ? [
                                'id' =>
                                $lop->giangVien->id,

                                'ho_ten' =>
                                $lop->giangVien->ho_ten,

                                'email' =>
                                $lop->giangVien->email,
                            ]
                            : null,
                    ];
                })

                ->filter()

                ->values();

            return response()->json([
                'status' => true,

                'message' =>
                'Lấy danh sách lớp học thành công.',

                'data' =>
                $lopHocs,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,

                'message' =>
                'Không thể lấy danh sách lớp học.',

                'error' =>
                $e->getMessage(),
            ], 500);
        }
    }
}
