<?php

namespace App\Http\Controllers;

use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class NguoiDungController extends CrudController
{
    protected string $modelClass = NguoiDung::class;

    /*
    |--------------------------------------------------------------------------
    | ĐĂNG NHẬP
    |--------------------------------------------------------------------------
    */

    public function dangNhap(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
            ],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',

            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.max' => 'Mật khẩu không được vượt quá 255 ký tự.',
        ]);

        $canonicalEmail = mb_strtolower(trim($credentials['email']));
        $nguoiDung = NguoiDung::query()
            ->whereRaw('LOWER(email) = ?', [$canonicalEmail])
            ->first();

        if (
            ! $nguoiDung ||
            ! Hash::check(
                $credentials['password'],
                $nguoiDung->password
            )
        ) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không chính xác.',
            ], 401);
        }

        if ($nguoiDung->trang_thai !== 'hoat_dong') {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không chính xác.',
            ], 401);
        }

        $expiresAt = now()->addDay();

        $token = $nguoiDung->createToken(
            'dang-nhap',
            ['*'],
            $expiresAt
        );

        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'nguoi_dung' => $nguoiDung->load('vaiTro'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | KIỂM TRA TOKEN
    |--------------------------------------------------------------------------
    */

    public function checkToken(Request $request): JsonResponse
    {
        if ($request->user()->trang_thai !== 'hoat_dong') {
            return response()->json([
                'message' => 'Tài khoản bị khóa hoặc chưa được kích hoạt.',
            ], 403);
        }

        return response()->json([
            'message' => 'Token hợp lệ.',
            'status' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | THÔNG TIN NGƯỜI DÙNG
    |--------------------------------------------------------------------------
    */

    public function thongTinNguoiDung(Request $request): JsonResponse
    {
        if ($request->user()->trang_thai !== 'hoat_dong') {
            return response()->json([
                'message' => 'Tài khoản bị khóa hoặc chưa được kích hoạt.',
            ], 403);
        }

        return response()->json([
            'nguoi_dung' => $request->user()->load('vaiTro'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ĐĂNG XUẤT
    |--------------------------------------------------------------------------
    */

    public function dangXuat(Request $request): JsonResponse
    {
        // Chỉ thu hồi token đang sử dụng.
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION RULES
    |--------------------------------------------------------------------------
    */

    protected function rules(?Model $record = null): array
    {
        return [
            'ho_ten' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',

                Rule::unique('nguoi_dung', 'email')
                    ->ignore($record?->id),
            ],

            'password' => [
                $record ? 'nullable' : 'required',
                'string',
                'min:8',
                'max:255',
            ],

            'vai_tro_id' => [
                'required',
                'integer',
                'exists:vai_tro,id',
            ],

            'trang_thai' => [
                'required',

                Rule::in([
                    'hoat_dong',
                    'khong_hoat_dong',
                    'bi_khoa',
                ]),
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
            // Họ tên
            'ho_ten.required' =>
                'Vui lòng nhập họ và tên.',

            'ho_ten.string' =>
                'Họ và tên không hợp lệ.',

            'ho_ten.max' =>
                'Họ và tên không được vượt quá 255 ký tự.',

            // Email
            'email.required' =>
                'Vui lòng nhập email.',

            'email.email' =>
                'Email không đúng định dạng.',

            'email.max' =>
                'Email không được vượt quá 255 ký tự.',

            'email.unique' =>
                'Email này đã được sử dụng.',

            // Password
            'password.required' =>
                'Vui lòng nhập mật khẩu.',

            'password.string' =>
                'Mật khẩu không hợp lệ.',

            'password.min' =>
                'Mật khẩu phải có ít nhất 8 ký tự.',

            'password.max' =>
                'Mật khẩu không được vượt quá 255 ký tự.',

            // Vai trò
            'vai_tro_id.required' =>
                'Vui lòng chọn vai trò.',

            'vai_tro_id.integer' =>
                'Vai trò không hợp lệ.',

            'vai_tro_id.exists' =>
                'Vai trò được chọn không tồn tại.',

            // Trạng thái
            'trang_thai.required' =>
                'Vui lòng chọn trạng thái.',

            'trang_thai.in' =>
                'Trạng thái được chọn không hợp lệ.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH NGƯỜI DÙNG
    |--------------------------------------------------------------------------
    */

    public function getNguoiDung(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'vai_tro_id' => [
                'nullable',
                'integer',
                'exists:vai_tro,id',
            ],

            'trang_thai' => [
                'nullable',

                Rule::in([
                    'hoat_dong',
                    'khong_hoat_dong',
                    'bi_khoa',
                ]),
            ],
        ], [
            'search.string' =>
                'Từ khóa tìm kiếm không hợp lệ.',

            'search.max' =>
                'Từ khóa tìm kiếm không được vượt quá 255 ký tự.',

            'vai_tro_id.integer' =>
                'Vai trò không hợp lệ.',

            'vai_tro_id.exists' =>
                'Vai trò được chọn không tồn tại.',

            'trang_thai.in' =>
                'Trạng thái được chọn không hợp lệ.',
        ]);

        $query = NguoiDung::with('vaiTro');

        /*
         * Tìm kiếm theo họ tên hoặc email.
         */
        if (! empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where(
                    'ho_ten',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'email',
                    'like',
                    '%' . $search . '%'
                );
            });
        }

        /*
         * Lọc vai trò.
         */
        if (! empty($filters['vai_tro_id'])) {
            $query->where(
                'vai_tro_id',
                $filters['vai_tro_id']
            );
        }

        /*
         * Lọc trạng thái.
         */
        if (! empty($filters['trang_thai'])) {
            $query->where(
                'trang_thai',
                $filters['trang_thai']
            );
        }

        /*
         * Sắp xếp mới nhất và phân trang 10 người.
         */
        return response()->json(
            $query
                ->latest('id')
                ->paginate(10)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | THÊM NGƯỜI DÙNG
    |--------------------------------------------------------------------------
    */

    public function themNguoiDung(Request $request): JsonResponse
    {
        $data = $request->validate(
            $this->rules(),
            $this->messages()
        );

        $user = NguoiDung::create($data);

        return response()->json([
            'message' => 'Đã thêm người dùng.',
            'data' => $user->load('vaiTro'),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | SỬA NGƯỜI DÙNG
    |--------------------------------------------------------------------------
    */

    public function suaNguoiDung(
        Request $request,
        int $id
    ): JsonResponse {
        $user = NguoiDung::findOrFail($id);

        $data = $request->validate(
            $this->rules($user),
            $this->messages()
        );

        /*
         * Không cho quản trị viên tự khóa tài khoản
         * hoặc tự thay đổi vai trò của chính mình.
         */
        if (
            $user->id === $request->user()->id &&
            (
                $data['trang_thai'] !== 'hoat_dong' ||
                (int) $data['vai_tro_id'] !==
                    (int) $user->vai_tro_id
            )
        ) {
            return response()->json([
                'message' =>
                    'Không thể tự khóa tài khoản hoặc thay đổi vai trò của chính mình.',
            ], 422);
        }

        /*
         * Nếu không nhập mật khẩu mới
         * thì giữ mật khẩu hiện tại.
         */
        if (empty($data['password'])) {
            unset($data['password']);
        }

        /*
         * Không cho đổi vai trò nếu người dùng
         * đang có liên kết với lớp học.
         */
        if (
            (int) $data['vai_tro_id'] !==
                (int) $user->vai_tro_id &&
            (
                $user->lopGiangDays()->exists() ||
                $user->thanhVienLops()->exists()
            )
        ) {
            return response()->json([
                'message' =>
                    'Không thể đổi vai trò của người dùng đang có liên kết lớp học.',
            ], 422);
        }

        DB::transaction(
            function () use ($user, $data) {
                $user->update($data);

                /*
                 * Thu hồi token khi:
                 * - Đổi mật khẩu
                 * - Khóa/vô hiệu hóa tài khoản
                 * - Đổi vai trò
                 */
                if (
                    isset($data['password']) ||
                    $user->trang_thai !== 'hoat_dong' ||
                    $user->wasChanged('vai_tro_id')
                ) {
                    $user->tokens()->delete();
                }
            }
        );

        return response()->json([
            'message' => 'Đã cập nhật người dùng.',
            'data' => $user->load('vaiTro'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | XÓA NGƯỜI DÙNG
    |--------------------------------------------------------------------------
    */

    public function xoaNguoiDung(
        Request $request,
        int $id
    ): JsonResponse {
        $user = NguoiDung::findOrFail($id);

        /*
         * Không được tự xóa tài khoản.
         */
        if (
            $user->id ===
            $request->user()->id
        ) {
            return response()->json([
                'message' =>
                    'Không thể xóa tài khoản của chính mình.',
            ], 422);
        }

        /*
         * Không xóa người dùng nếu đang
         * liên kết với lớp học.
         */
        if (
            $user->lopGiangDays()->exists() ||
            $user->thanhVienLops()->exists()
        ) {
            return response()->json([
                'message' =>
                    'Không thể xóa người dùng đang có liên kết lớp học.',
            ], 422);
        }

        DB::transaction(
            function () use ($user) {
                // Xóa toàn bộ token trước.
                $user->tokens()->delete();

                // Xóa người dùng.
                $user->delete();
            }
        );

        return response()->json([
            'message' =>
                'Đã xóa người dùng.',
        ]);
    }
}
