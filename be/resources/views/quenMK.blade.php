<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Đánh Giá Rèn Luyện</title>
</head>

<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f8; line-height: 1.6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f4f6f8;">
        <tr>
            <td style="padding: 20px 0;">
                <table role="presentation" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); overflow: hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); padding: 40px 30px; text-align: center;">
                            <div style="background-color: #ffffff; display: inline-block; padding: 12px 25px; border-radius: 8px; margin-bottom: 15px;">
                                <h1 style="margin: 0; color: #0d6efd; font-size: 26px; font-weight: bold;">
                                    Đánh Giá Rèn Luyện
                                </h1>
                            </div>

                            <h2 style="margin: 0; color: #ffffff; font-size: 21px; font-weight: 400;">
                                Yêu cầu đặt lại mật khẩu
                            </h2>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #212529; font-size: 22px; margin-bottom: 15px; font-weight: 600; text-align: center;">
                                    Khôi phục mật khẩu tài khoản
                                </h3>

                                <p style="color: #555555; font-size: 16px; margin: 0 0 12px 0; line-height: 1.8;">
                                    Xin chào <strong>{{ $data['ho_ten'] ?? 'người dùng' }}</strong>,
                                </p>

                                <p style="color: #555555; font-size: 16px; margin: 0 0 20px 0; line-height: 1.8;">
                                    Hệ thống nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại
                                    <strong>Đánh Giá Rèn Luyện</strong>.
                                </p>

                                <p style="color: #555555; font-size: 16px; margin: 0; line-height: 1.8;">
                                    Vui lòng sử dụng mã xác thực bên dưới hoặc nhấn vào nút đặt lại mật khẩu để tiếp tục.
                                </p>
                            </div>

                            <!-- OTP Code -->
                            @isset($data['code'])
                                <div style="text-align: center; margin: 35px 0;">
                                    <p style="color: #212529; font-size: 17px; margin: 0 0 16px 0; font-weight: 600;">
                                        Mã xác thực của bạn là:
                                    </p>

                                    <p style="text-align: center; background-color: #f4f8ff; display: inline-block; padding: 18px 32px; border-radius: 12px; color: #0b3d91; font-size: 26px; font-weight: 700; letter-spacing: 4px; margin: 0;">
                                        {{ $data['code'] }}
                                    </p>
                                </div>
                            @endisset

                            <!-- Reset Button -->
                            @isset($data['link'])
                                <div style="text-align: center; margin: 35px 0;">
                                    <a href="{{ $data['link'] }}"
                                        style="display: inline-block; background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; font-size: 16px; font-weight: 600; border-radius: 50px; box-shadow: 0 4px 15px rgba(13, 110, 253, 0.35); text-transform: uppercase; letter-spacing: 0.5px;">
                                        Đặt lại mật khẩu
                                    </a>
                                </div>

                                <!-- Alternative Link -->
                                <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #0d6efd;">
                                    <p style="color: #555555; font-size: 14px; margin-bottom: 15px; font-weight: 600;">
                                        Nếu nút trên không hoạt động, bạn có thể sao chép và dán đường dẫn sau vào trình duyệt:
                                    </p>

                                    <p style="word-break: break-all; background-color: #ffffff; padding: 12px; border-radius: 4px; border: 1px solid #e9ecef; font-family: monospace; font-size: 13px; color: #495057; margin: 0;">
                                        {{ $data['link'] }}
                                    </p>
                                </div>
                            @endisset

                            <!-- Security Notice -->
                            <div style="background-color: #fff3cd; border: 1px solid #ffe69c; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="vertical-align: top; width: 30px; font-size: 18px;">⚠️</td>

                                        <td style="vertical-align: top;">
                                            <p style="color: #856404; font-size: 14px; margin: 0; font-weight: 600;">
                                                Lưu ý bảo mật quan trọng:
                                            </p>

                                            <p style="color: #856404; font-size: 13px; margin: 5px 0 0 0; line-height: 1.6;">
                                                Đường dẫn và mã xác thực chỉ có hiệu lực trong vòng <strong>60 phút</strong>.
                                                Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
                                                Mật khẩu hiện tại của bạn vẫn được giữ nguyên.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #212529; padding: 30px; text-align: center;">
                            <div style="margin-bottom: 20px;">
                                <h3 style="color: #ffffff; font-size: 18px; margin-bottom: 10px; font-weight: 600;">
                                    Hệ thống Đánh Giá Rèn Luyện
                                </h3>

                                <p style="color: #adb5bd; font-size: 13px; margin: 0; line-height: 1.6;">
                                    Hỗ trợ quản lý, đánh giá và theo dõi kết quả rèn luyện của sinh viên.
                                </p>
                            </div>

                            <div style="border-top: 1px solid #343a40; padding-top: 20px; margin-top: 20px;">
                                <p style="color: #adb5bd; font-size: 12px; margin: 0 0 10px 0;">
                                    Đây là email tự động, vui lòng không phản hồi trực tiếp vào email này.
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