<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kích hoạt tài khoản</title>
</head>

<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8;">
    <table role="presentation" style="width:100%; border-collapse:collapse; background-color:#f4f6f8;">
        <tr>
            <td style="padding:20px 0;">
                <table role="presentation" style="max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">

                    <!-- HEADER -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#0d6efd 0%,#0b5ed7 100%); padding:35px 30px; text-align:center; color:#ffffff;">
                            <h1 style="margin:0; font-size:28px; font-weight:700;">
                                Kích hoạt tài khoản
                            </h1>
                            <p style="margin:10px 0 0; font-size:16px; opacity:.95;">
                                Hệ thống Đánh Giá Rèn Luyện
                            </p>
                        </td>
                    </tr>

                    <!-- CONTENT -->
                    <tr>
                        <td style="padding:40px 30px;">
                            <p style="margin:0 0 20px; color:#333333; font-size:16px;">
                                Xin chào <strong>{{ $data['ho_ten'] ?? 'người dùng' }}</strong>,
                            </p>

                            <p style="margin:0 0 20px; color:#555555; font-size:15px; line-height:1.7;">
                                Tài khoản của bạn đã được tạo trên hệ thống
                                <strong>Đánh Giá Rèn Luyện</strong>.
                            </p>

                            <p style="margin:0 0 28px; color:#555555; font-size:15px; line-height:1.7;">
                                Vui lòng nhấn vào nút bên dưới để kích hoạt tài khoản trước khi đăng nhập vào hệ thống.
                            </p>

                            <!-- THÔNG TIN TÀI KHOẢN -->
                            <div style="background:#f8f9fa; padding:20px 22px; border-radius:12px; border-left:4px solid #0d6efd; margin-bottom:30px;">
                                <p style="margin:0 0 12px; color:#222222; font-size:15px; font-weight:700;">
                                    Thông tin tài khoản
                                </p>

                                @isset($data['email'])
                                    <p style="margin:6px 0; color:#555555; font-size:14px;">
                                        <strong>Email đăng nhập:</strong> {{ $data['email'] }}
                                    </p>
                                @endisset

                                @isset($data['vai_tro'])
                                    <p style="margin:6px 0; color:#555555; font-size:14px;">
                                        <strong>Vai trò:</strong> {{ $data['vai_tro'] }}
                                    </p>
                                @endisset

                                @isset($data['mat_khau'])
                                    <p style="margin:6px 0; color:#555555; font-size:14px;">
                                        <strong>Mật khẩu tạm thời:</strong> {{ $data['mat_khau'] }}
                                    </p>

                                    <p style="margin:10px 0 0; color:#dc3545; font-size:13px;">
                                        Bạn nên đổi mật khẩu sau khi đăng nhập lần đầu.
                                    </p>
                                @endisset
                            </div>

                            <!-- BUTTON -->
                            @isset($data['link'])
                                <div style="text-align:center; margin:35px 0;">
                                    <a href="{{ $data['link'] }}"
                                        style="display:inline-block; background:linear-gradient(135deg,#0d6efd 0%,#0b5ed7 100%); color:#ffffff; text-decoration:none; padding:15px 36px; font-size:16px; font-weight:700; border-radius:50px; box-shadow:0 6px 18px rgba(13,110,253,0.35);">
                                        Kích hoạt tài khoản
                                    </a>
                                </div>

                                <p style="margin:0 0 12px; color:#555555; font-size:15px; line-height:1.7;">
                                    Nếu nút trên không hoạt động, bạn có thể sao chép đường dẫn sau và dán vào trình duyệt:
                                </p>

                                <p style="word-break:break-all; background:#f8f9fa; padding:14px 16px; border-radius:10px; margin:0 0 25px; font-family:monospace; font-size:14px; color:#333333; border:1px solid #e6edf7;">
                                    {{ $data['link'] }}
                                </p>
                            @endisset

                            <!-- LƯU Ý -->
                            <div style="background:#fff3cd; border:1px solid #ffe69c; border-radius:10px; padding:18px; margin-top:25px;">
                                <p style="margin:0 0 8px; color:#856404; font-size:14px; font-weight:700;">
                                    Lưu ý bảo mật
                                </p>

                                <p style="margin:0; color:#856404; font-size:13px; line-height:1.6;">
                                    Đường dẫn kích hoạt có thời hạn sử dụng. Nếu bạn không yêu cầu hoặc không được cấp tài khoản này,
                                    vui lòng bỏ qua email hoặc liên hệ quản trị viên hệ thống.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- FOOTER -->
                    <tr>
                        <td style="background:#f8f9fa; padding:25px 30px; text-align:center; color:#667785; font-size:13px;">
                            <p style="margin:0;">Email này được gửi tự động, vui lòng không trả lời.</p>
                            <p style="margin:6px 0 0;">© Hệ thống Đánh Giá Rèn Luyện</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>