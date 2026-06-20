<?php

namespace App\Http\Controllers;

use App\Mail\MasterMail;
use App\Models\Lop;
use App\Models\NguoiDung;
use App\Models\SinhVien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class NguoiDungController extends Controller
{
    public function dangNhap(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ]);

        $user = NguoiDung::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Thông tin đăng nhập không chính xác.',
            ], 401);
        }

        if ($user->trang_thai !== 'hoat_dong') {
            return response()->json([
                'status' => false,
                'message' => 'Tài khoản của bạn hiện không hoạt động.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Đăng nhập thành công.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'so_dien_thoai' => $user->so_dien_thoai,
                'ngay_sinh' => $user->ngay_sinh,
                'avatar' => $user->avatar,
                'vai_tro_id' => $user->vai_tro_id,
                'trang_thai' => $user->trang_thai,
            ],
        ]);
    }

    public function checkToken(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'user' => [
                'id' => $user->id,
                'ho_ten' => $user->ho_ten,
                'email' => $user->email,
                'so_dien_thoai' => $user->so_dien_thoai,
                'ngay_sinh' => $user->ngay_sinh,
                'avatar' => $user->avatar,
                'vai_tro_id' => $user->vai_tro_id,
                'trang_thai' => $user->trang_thai,
            ],
        ]);
    }

    public function thongTinNguoiDung(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ.',
            ], 401);
        }

        return response()->json([
            'status' => true,
            'data' => $user,
        ]);
    }

    public function dangXuat(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ.',
            ], 401);
        }

        $user->currentAccessToken()?->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    public function kichHoat($token)
    {
        $user = NguoiDung::where('verification_token', $token)->first();
        $frontend = env('FRONTEND_URL', config('app.url'));

        if (!$user) {
            return redirect($frontend . '/dang-nhap?verified=0');
        }

        if ($user->email_verified_at !== null && $user->trang_thai === 'hoat_dong') {
            return redirect($frontend . '/dang-nhap?verified=already');
        }

        $user->update([
            'email_verified_at' => now(),
            'trang_thai' => 'hoat_dong',
            'verification_token' => null,
        ]);

        return redirect($frontend . '/dang-nhap?verified=1');
    }

    public function guiOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        if (NguoiDung::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Email đã được đăng ký. Vui lòng đăng nhập hoặc dùng email khác.',
            ], 422);
        }

        $code = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('registration_verification_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $code,
                'created_at' => Carbon::now(),
            ]
        );

        try {
            Mail::to($request->email)->send(new MasterMail(
                'Mã xác nhận đăng ký',
                'otpDangKy',
                [
                    'ho_ten' => $request->email,
                    'code' => $code,
                    'link' => env('FRONTEND_URL', config('app.url')) . '/dang-ky',
                ]
            ));
        } catch (\Exception $exception) {
            return response()->json([
                'status' => false,
                'message' => 'Gửi email thất bại. Vui lòng kiểm tra cấu hình mail.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mã xác nhận đã được gửi đến email của bạn.',
        ]);
    }

    public function xacNhanOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'code.required' => 'Mã xác nhận không được để trống.',
            'code.digits' => 'Mã xác nhận phải có 6 chữ số.',
        ]);

        $record = DB::table('registration_verification_tokens')->where('email', $request->email)->first();

        if (!$record || $record->token !== $request->code) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác nhận không đúng hoặc đã hết hạn.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác nhận đã hết hạn. Vui lòng yêu cầu lại.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mã xác nhận hợp lệ.',
        ]);
    }

    public function guiOTPQuenMatKhau(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
        ]);

        $user = NguoiDung::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Email không tồn tại.',
            ], 404);
        }

        $code = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = Carbon::now()->addMinutes(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $code,
                'created_at' => Carbon::now(),
            ]
        );

        try {
            Mail::to($user->email)->send(new MasterMail(
                'Yêu cầu đặt lại mật khẩu',
                'quenMK',
                [
                    'ho_ten' => $user->ho_ten,
                    'code' => $code,
                    'link' => env('FRONTEND_URL', config('app.url')) . '/quen-mat-khau',
                    'expired_at' => $expiresAt->format('H:i d/m/Y'),
                ]
            ));
        } catch (\Exception $exception) {
            return response()->json([
                'status' => false,
                'message' => 'Gửi email thất bại. Vui lòng kiểm tra cấu hình mail.',
                'error' => $exception->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mã xác thực đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
        ]);
    }

    public function xacNhanOTPQuenMatKhau(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'code.required' => 'Mã xác thực không được để trống.',
            'code.digits' => 'Mã xác thực phải có 6 chữ số.',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (!$record || $record->token !== $request->code) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác thực không đúng hoặc đã hết hạn.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác thực đã hết hạn. Vui lòng yêu cầu lại.',
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Mã xác thực hợp lệ.',
        ]);
    }

    public function doiMatKhau(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Email không hợp lệ.',
            'code.required' => 'Mã xác thực không được để trống.',
            'code.digits' => 'Mã xác thực phải có 6 chữ số.',
            'password.required' => 'Mật khẩu không được để trống.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $user = NguoiDung::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Email không tồn tại.',
            ], 404);
        }

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        if (!$record || $record->token !== $request->code) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác thực không đúng hoặc đã hết hạn.',
            ], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json([
                'status' => false,
                'message' => 'Mã xác thực đã hết hạn. Vui lòng yêu cầu lại.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đổi mật khẩu thành công.',
        ]);
    }

    public function getNguoiDung(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn!'
            ], 401);
        }

        $data = NguoiDung::orderByDesc('id')->get();

        foreach ($data as $nguoiDung) {
            $maLop = null;

            // Cố vấn học tập
            if ($nguoiDung->vai_tro_id == 3) {
                $maLop = Lop::where('co_van_id', $nguoiDung->id)
                    ->pluck('ma_lop')
                    ->implode(', ');
            }

            // Lớp trưởng
            if ($nguoiDung->vai_tro_id == 4) {
                $maLop = Lop::where('lop_truong_id', $nguoiDung->id)
                    ->pluck('ma_lop')
                    ->implode(', ');
            }

            // Bí thư
            if ($nguoiDung->vai_tro_id == 5) {
                $maLop = Lop::where('bi_thu_id', $nguoiDung->id)
                    ->pluck('ma_lop')
                    ->implode(', ');
            }

            // Sinh viên
            if ($nguoiDung->vai_tro_id == 6) {
                $sinhVien = SinhVien::where('user_id', $nguoiDung->id)->first();

                if ($sinhVien) {
                    $lop = Lop::find($sinhVien->lop_id);

                    if ($lop) {
                        $maLop = $lop->ma_lop;
                    }
                }
            }

            $nguoiDung->lop = $maLop ?: null;
        }

        return response()->json([
            'status' => true,
            'message' => 'Lấy danh sách người dùng thành công',
            'data' => $data
        ]);
    }

    public function chiTietNguoiDung(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn!'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => ['required', 'exists:nguoi_dung,id'],
        ], [
            'id.required' => 'Vui lòng truyền ID người dùng',
            'id.exists' => 'Người dùng không tồn tại',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = NguoiDung::find($request->id);

        return response()->json([
            'status' => true,
            'message' => 'Lấy chi tiết người dùng thành công',
            'data' => $data
        ]);
    }
    public function themNguoiDung(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn!'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'ho_ten'        => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:nguoi_dung,email'],
            'password'      => ['required', 'string', 'min:6'],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'ngay_sinh'     => ['nullable', 'date'],
            'avatar'        => ['nullable', 'string'],
            'vai_tro_id'    => ['required', 'integer'],
            'trang_thai'    => ['required', Rule::in(['hoat_dong', 'khong_hoat_dong', 'bi_khoa'])],
        ], [
            'ho_ten.required'     => 'Vui lòng nhập họ tên',
            'email.required'      => 'Vui lòng nhập email',
            'email.email'         => 'Email không hợp lệ',
            'email.unique'        => 'Email đã tồn tại',
            'password.required'   => 'Vui lòng nhập mật khẩu',
            'password.min'        => 'Mật khẩu phải có ít nhất 6 ký tự',
            'vai_tro_id.required' => 'Vui lòng chọn vai trò',
            'trang_thai.required' => 'Vui lòng chọn trạng thái',
            'trang_thai.in'       => 'Trạng thái không hợp lệ',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $nguoiDung = NguoiDung::create([
            'ho_ten'             => $request->ho_ten,
            'email'              => $request->email,
            'password'           => $request->password,
            'so_dien_thoai'      => $request->so_dien_thoai,
            'ngay_sinh'          => $request->ngay_sinh,
            'avatar'             => $request->avatar,
            'vai_tro_id'         => $request->vai_tro_id,
            'trang_thai'         => $request->trang_thai,
            'email_verified_at'  => $request->trang_thai === 'hoat_dong' ? now() : null,
            'verification_token' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Thêm người dùng ' . $request->ho_ten . ' thành công!',
            'data' => $nguoiDung
        ], 201);
    }
    public function suaNguoiDung(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn!'
            ], 401);
        }

        $nguoiDung = NguoiDung::find($request->id);

        if (!$nguoiDung) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id'            => ['required', 'exists:nguoi_dung,id'],
            'ho_ten'        => ['required', 'string', 'max:255'],
            'email'         => [
                'required',
                'email',
                Rule::unique('nguoi_dung', 'email')->ignore($request->id)
            ],
            'so_dien_thoai' => ['nullable', 'string', 'max:20'],
            'ngay_sinh'     => ['nullable', 'date'],
            'avatar'        => ['nullable', 'string'],
            'vai_tro_id'    => ['required', 'integer'],
            'trang_thai'    => ['required', Rule::in(['hoat_dong', 'khong_hoat_dong', 'bi_khoa'])],
            'password'      => ['nullable', 'string', 'min:6'],
        ], [
            'id.required'          => 'Thiếu ID người dùng',
            'id.exists'            => 'Người dùng không tồn tại',
            'ho_ten.required'      => 'Vui lòng nhập họ tên',
            'email.required'       => 'Vui lòng nhập email',
            'email.email'          => 'Email không hợp lệ',
            'email.unique'         => 'Email đã tồn tại',
            'vai_tro_id.required'  => 'Vui lòng chọn vai trò',
            'trang_thai.required'  => 'Vui lòng chọn trạng thái',
            'trang_thai.in'        => 'Trạng thái không hợp lệ',
            'password.min'         => 'Mật khẩu phải có ít nhất 6 ký tự',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $dataUpdate = [
            'ho_ten'        => $request->ho_ten,
            'email'         => $request->email,
            'so_dien_thoai' => $request->so_dien_thoai,
            'ngay_sinh'     => $request->ngay_sinh,
            'avatar'        => $request->avatar,
            'vai_tro_id'    => $request->vai_tro_id,
            'trang_thai'    => $request->trang_thai,
        ];

        if ($request->filled('password')) {
            $dataUpdate['password'] = $request->password;
        }

        $nguoiDung->update($dataUpdate);

        return response()->json([
            'status'  => true,
            'message' => 'Cập nhật người dùng thành công!',
            'data'    => $nguoiDung
        ]);
    }
    public function xoaNguoiDung(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn!'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => ['required', 'exists:nguoi_dung,id'],
        ], [
            'id.required' => 'Vui lòng truyền ID người dùng',
            'id.exists'   => 'Người dùng không tồn tại',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        $nguoiDung = NguoiDung::find($request->id);

        if ($user->id == $nguoiDung->id) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể tự xóa tài khoản đang đăng nhập'
            ], 400);
        }

        $hoTen = $nguoiDung->ho_ten;

        $nguoiDung->tokens()->delete();
        $nguoiDung->delete();

        return response()->json([
            'status' => true,
            'message' => 'Xóa người dùng ' . $hoTen . ' thành công',
        ]);
    }
    public function timKiem(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Token không hợp lệ hoặc đã hết hạn!'
            ], 401);
        }

        $keyword = $request->input('tim_nguoi_dung', '');

        $query = NguoiDung::query();

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('ho_ten', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('vai_tro_id')) {
            $query->where('vai_tro_id', $request->vai_tro_id);
        }

        if ($request->filled('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $data = $query->orderByDesc('id')->get();

        return response()->json([
            'status' => true,
            'message' => 'Tìm kiếm người dùng thành công',
            'data' => $data
        ]);
    }
}
