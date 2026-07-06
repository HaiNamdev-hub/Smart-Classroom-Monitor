<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kích Hoạt Tài Khoản - Đánh Giá Rèn Luyện</title>
</head>

<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8; line-height: 1.6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f4f6f8;">
        <tr>
            <td style="padding: 20px 0;">
                <table role="presentation"
                    style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); overflow: hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); padding: 35px 30px; text-align: center;">
                            <div style="background-color: #ffffff; display: inline-block; padding: 14px 24px; border-radius: 8px; margin-bottom: 18px;">
                                <h1 style="margin: 0; color: #0d6efd; font-size: 26px; font-weight: bold;">
                                    Đánh Giá Rèn Luyện
                                </h1>
                            </div>

                            <h2 style="margin: 0; color: #ffffff; font-size: 23px; font-weight: 400;">
                                Kích hoạt tài khoản
                            </h2>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <div style="text-align: center; margin-bottom: 28px;">
                                <h3 style="color: #212529; font-size: 22px; margin-bottom: 18px; font-weight: 600;">
                                    Xin chào {{ $data['ho_ten'] ?? 'người dùng' }},
                                </h3>

                                <p style="color: #555555; font-size: 16px; margin-bottom: 20px; line-height: 1.8;">
                                    Tài khoản của bạn đã được tạo trên hệ thống
                                    <strong>Đánh Giá Rèn Luyện</strong>.
                                </p>

                                <p style="color: #555555; font-size: 16px; margin-bottom: 0; line-height: 1.8;">
                                    Vui lòng nhấn vào nút bên dưới để kích hoạt tài khoản trước khi đăng nhập và sử dụng hệ thống.
                                </p>
                            </div>

                            <!-- Account Info -->
                            <div style="background-color: #f8f9fa; padding: 22px; border-radius: 8px; margin: 28px 0; border-left: 4px solid #0d6efd;">
                                <p style="color: #212529; font-size: 15px; margin: 0 0 12px 0; font-weight: 600;">
                                    Thông tin tài khoản
                                </p>

                                @isset($data['email'])
                                    <p style="color: #555555; font-size: 14px; margin: 6px 0;">
                                        <strong>Email đăng nhập:</strong> {{ $data['email'] }}
                                    </p>
                                @endisset

                                @isset($data['vai_tro'])
                                    <p style="color: #555555; font-size: 14px; margin: 6px 0;">
                                        <strong>Vai trò:</strong> {{ $data['vai_tro'] }}
                                    </p>
                                @endisset

                                @isset($data['mat_khau'])
                                    <p style="color: #555555; font-size: 14px; margin: 6px 0;">
                                        <strong>Mật khẩu tạm thời:</strong> {{ $data['mat_khau'] }}
                                    </p>
                                    <p style="color: #dc3545; font-size: 13px; margin: 10px 0 0 0;">
                                        Bạn nên đổi mật khẩu sau lần đăng nhập đầu tiên.
                                    </p>
                                @endisset
                            </div>

                            <!-- Activation Button -->
                            <div style="text-align: center; margin: 35px 0;">
                                <a href="{{ $data['link'] }}"
                                    style="display: inline-block; background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: #ffffff; text-decoration: none; padding: 15px 38px; font-size: 17px; font-weight: 600; border-radius: 50px; box-shadow: 0 4px 14px rgba(13, 110, 253, 0.35); text-transform: uppercase; letter-spacing: 0.5px;">
                                    Kích hoạt tài khoản
                                </a>
                            </div>

                            <!-- Alternative Link -->
                            <div style="background-color: #f8f9fa; padding: 22px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #6c757d;">
                                <p style="color: #555555; font-size: 14px; margin-bottom: 12px; font-weight: 600;">
                                    Nếu nút trên không hoạt động, bạn có thể sao chép đường link sau và dán vào trình duyệt:
                                </p>

                                <p style="word-break: break-all; background-color: #ffffff; padding: 12px; border-radius: 4px; border: 1px solid #dee2e6; font-family: monospace; font-size: 13px; color: #495057; margin: 0;">
                                    {{ $data['link'] }}
                                </p>
                            </div>

                            <!-- Security Notice -->
                            <div style="background-color: #fff3cd; border: 1px solid #ffe69c; border-radius: 8px; padding: 18px; margin: 25px 0;">
                                <p style="color: #856404; font-size: 14px; margin: 0 0 6px 0; font-weight: 600;">
                                    Lưu ý bảo mật
                                </p>

                                <p style="color: #856404; font-size: 13px; margin: 0; line-height: 1.6;">
                                    Link kích hoạt có thời hạn sử dụng. Nếu bạn không yêu cầu hoặc không được cấp tài khoản này,
                                    vui lòng bỏ qua email hoặc liên hệ quản trị viên hệ thống.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #212529; padding: 30px; text-align: center;">
                            <h3 style="color: #ffffff; font-size: 19px; margin: 0 0 12px 0; font-weight: 600;">
                                Hệ thống Đánh Giá Rèn Luyện
                            </h3>

                            <p style="color: #adb5bd; font-size: 14px; margin: 0; line-height: 1.6;">
                                Hỗ trợ quản lý, đánh giá và theo dõi kết quả rèn luyện của sinh viên.
                            </p>

                            <div style="border-top: 1px solid #343a40; padding-top: 18px; margin-top: 22px;">
                                <p style="color: #adb5bd; font-size: 12px; margin: 0 0 8px 0;">
                                    Email này được gửi tự động, vui lòng không trả lời email này.
                                </p>

                                <p style="color: #adb5bd; font-size: 12px; margin: 0;">
                                    © Hệ thống Đánh Giá Rèn Luyện. All rights reserved.
                                </p>
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>