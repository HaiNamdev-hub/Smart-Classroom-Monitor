<?php

namespace App\Http\Controllers;

use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class NguoiDungController extends CrudController
{
    protected string $modelClass = NguoiDung::class;

    public function dangNhap(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $nguoiDung = NguoiDung::query()->where('email', $credentials['email'])->first();

        if (! $nguoiDung || ! Hash::check($credentials['password'], $nguoiDung->password)) {
            return response()->json(['message' => 'Email hoặc mật khẩu không chính xác.'], 401);
        }

        if ($nguoiDung->trang_thai !== 'hoat_dong') {
            return response()->json(['message' => 'Tài khoản bị khóa hoặc chưa được kích hoạt.'], 403);
        }

        $expiresAt = now()->addDay();
        $token = $nguoiDung->createToken('dang-nhap', ['*'], $expiresAt);

        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'nguoi_dung' => $nguoiDung->load('vaiTro'),
        ]);
    }

    public function checkToken(Request $request): JsonResponse
    {
        if ($request->user()->trang_thai !== 'hoat_dong') {
            return response()->json(['message' => 'Tài khoản bị khóa hoặc chưa được kích hoạt.'], 403);
        }

        return response()->json(['message' => 'Token hợp lệ.', 'status' => true]);
    }

    public function thongTinNguoiDung(Request $request): JsonResponse
    {
        if ($request->user()->trang_thai !== 'hoat_dong') {
            return response()->json(['message' => 'Tài khoản bị khóa hoặc chưa được kích hoạt.'], 403);
        }

        return response()->json(['nguoi_dung' => $request->user()->load('vaiTro')]);
    }

    public function dangXuat(Request $request): JsonResponse
    {
        // Chỉ thu hồi token đang dùng, giữ nguyên đăng nhập trên thiết bị khác.
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Đăng xuất thành công.']);
    }

    protected function rules(?Model $record = null): array
    {
        return [
            'ho_ten' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('nguoi_dung')->ignore($record)],
            'password' => [$record ? 'nullable' : 'required', 'string', 'min:8'],
            'vai_tro_id' => ['required', 'integer', 'exists:vai_tro,id'],
            'trang_thai' => ['required', Rule::in(['hoat_dong', 'khong_hoat_dong', 'bi_khoa'])],
        ];
    }

    public function getNguoiDung(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'vai_tro_id' => ['nullable', 'integer', 'exists:vai_tro,id'],
            'trang_thai' => ['nullable', Rule::in(['hoat_dong', 'khong_hoat_dong', 'bi_khoa'])],
        ]);
        $query = NguoiDung::with('vaiTro');
        if (! empty($filters['search'])) {
            $query->where(fn($q) => $q->where('ho_ten', 'like', '%' . $filters['search'] . '%')
                ->orWhere('email', 'like', '%' . $filters['search'] . '%'));
        }
        foreach (['vai_tro_id', 'trang_thai'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return response()->json($query->latest('id')->paginate(10));
    }

    public function themNguoiDung(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());
        $user = NguoiDung::create($data);

        return response()->json(['message' => 'Đã thêm người dùng.', 'data' => $user->load('vaiTro')], 201);
    }

    public function suaNguoiDung(Request $request, int $id): JsonResponse
    {
        $user = NguoiDung::findOrFail($id);
        $data = $request->validate($this->rules($user));
        if (
            $user->id === $request->user()->id &&
            ($data['trang_thai'] !== 'hoat_dong' || (int) $data['vai_tro_id'] !== $user->vai_tro_id)
        ) {
            return response()->json(['message' => 'Không thể tự khóa tài khoản hoặc thay đổi vai trò của chính mình.'], 422);
        }
        // Giữ mật khẩu cũ khi ô mật khẩu được để trống.
        if (empty($data['password'])) {
            unset($data['password']);
        }
        if (
            (int) $data['vai_tro_id'] !== $user->vai_tro_id &&
            ($user->lopGiangDays()->exists() || $user->thanhVienLops()->exists())
        ) {
            return response()->json(['message' => 'Không thể đổi vai trò của người dùng đang có liên kết lớp học.'], 422);
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $data) {
            $user->update($data);
            if (isset($data['password']) || $user->trang_thai !== 'hoat_dong' || $user->wasChanged('vai_tro_id')) {
                $user->tokens()->delete();
            }
        });

        return response()->json(['message' => 'Đã cập nhật người dùng.', 'data' => $user->load('vaiTro')]);
    }
}
