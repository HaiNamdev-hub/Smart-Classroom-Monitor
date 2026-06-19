<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Lại Mật Khẩu - HiTravel</title>
</head>

<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; line-height: 1.6;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f4f4f4;">
        <tr>
            <td style="padding: 20px 0;">
                <table role="presentation" style="width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); overflow: hidden;">

                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); padding: 40px 30px; text-align: center;">
                            <div style="background-color: #ffffff; display: inline-block; padding: 12px 25px; border-radius: 8px; margin-bottom: 15px;">
                                <h1 style="margin: 0; color: #0072ff; font-size: 26px; font-weight: bold; letter-spacing: 1px;">
                                    🌍 HiTravel
                                </h1>
                            </div>
                            <h2 style="margin: 0; color: #ffffff; font-size: 20px; font-weight: 300; opacity: 0.9;">
                                Yêu Cầu Đặt Lại Mật Khẩu
                            </h2>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <div style="text-align: center; margin-bottom: 30px;">
                                <h3 style="color: #333333; font-size: 22px; margin-bottom: 15px; font-weight: 600;">
                                    Khôi Phục Mật Khẩu Của Bạn
                                </h3>
                                <p style="color: #666666; font-size: 16px; margin: 0; line-height: 1.8; text-align: left;">
                                    Xin chào <strong>{{ $data['ho_ten'] }}</strong>,
                                </p>
                                <p style="color: #666666; font-size: 16px; margin-top: 10px; margin-bottom: 25px; line-height: 1.8; text-align: left;">
                                    Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại <strong>HiTravel</strong>. Vui lòng nhấp vào nút bên dưới để tiến hành tạo mật khẩu mới.
                                </p>
                            </div>

                            <!-- Reset Button -->
                            <div style="text-align: center; margin: 35px 0;">
                                <p style="color: #333333; font-size: 18px; margin-top: 20px; font-weight: 600; text-align: center;">
                                    Mã xác thực của bạn là:
                                </p>
                                <p style="text-align: center; background-color: #f4f8ff; display: inline-block; padding: 18px 28px; border-radius: 12px; color: #0b3d91; font-size: 24px; font-weight: 700; letter-spacing: 2px; margin: 0 0 30px 0;">
                                    {{ $data['code'] }}
                                </p>

                                <a href="{{ $data['link'] }}"
                                    style="display: inline-block; background: linear-gradient(135deg, #00c6ff 0%, #0072ff 100%); color: #ffffff; text-decoration: none; padding: 15px 40px; font-size: 16px; font-weight: 600; border-radius: 50px; box-shadow: 0 4px 15px rgba(0, 114, 255, 0.4); transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px;">
                                    🔒 Đặt Lại Mật Khẩu
                                </a>
                            </div>

                            <!-- Alternative Link -->
                            <div style="background-color: #f8f9fa; padding: 25px; border-radius: 8px; margin: 30px 0; border-left: 4px solid #0072ff;">
                                <p style="color: #666666; font-size: 14px; margin-bottom: 15px; font-weight: 600;">
                                    Nếu nút trên không hoạt động, bạn có thể sao chép và dán đường dẫn sau vào trình duyệt:
                                </p>
                                <p style="word-break: break-all; background-color: #ffffff; padding: 12px; border-radius: 4px; border: 1px solid #e9ecef; font-family: monospace; font-size: 13px; color: #495057; margin: 0;">
                                    {{ $data['link'] }}
                                </p>
                            </div>

                            <!-- Security Notice -->
                            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="vertical-align: top; width: 30px; font-size: 18px;">⚠️</td>
                                        <td style="vertical-align: top;">
                                            <p style="color: #856404; font-size: 14px; margin: 0; font-weight: 600;">
                                                Lưu ý bảo mật quan trọng:
                                            </p>
                                            <p style="color: #856404; font-size: 13px; margin: 5px 0 0 0; line-height: 1.5;">
                                                • Đường dẫn này chỉ có hiệu lực trong vòng <strong>60 phút</strong>.<br>
                                                • Nếu bạn không yêu cầu thay đổi này, hãy yên tâm bỏ qua email này, mật khẩu cũ của bạn vẫn sẽ được giữ an toàn.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2c3e50; padding: 30px; text-align: center;">
                            <div style="margin-bottom: 20px;">
                                <h3 style="color: #ffffff; font-size: 18px; margin-bottom: 10px; font-weight: 600;">
                                    🌍 Hệ Thống HiTravel
                                </h3>
                                <p style="color: #bdc3c7; font-size: 13px; margin: 0; line-height: 1.6;">
                                    Đặt tour dễ dàng – Trải nghiệm hoàn hảo – Giá tốt mỗi ngày
                                </p>
                            </div>

                            <div style="border-top: 1px solid #34495e; padding-top: 20px; margin-top: 20px;">
                                <p style="color: #95a5a6; font-size: 12px; margin: 0 0 10px 0;">
                                    📧 Đây là email tự động, vui lòng không phản hồi trực tiếp vào email này.
                                </p>
                                <p style="color: #95a5a6; font-size: 12px; margin: 0;">
                                    © HiTravel All rights reserved.
                                </p>
                                <div style="margin-top: 15px;">
                                    <a href="#" style="color: #3498db; text-decoration: none; font-size: 12px; margin: 0 10px;">Chính sách bảo mật</a>
                                    <span style="color: #7f8c8d;">|</span>
                                    <a href="#" style="color: #3498db; text-decoration: none; font-size: 12px; margin: 0 10px;">Điều khoản sử dụng</a>
                                    <span style="color: #7f8c8d;">|</span>
                                    <a href="#" style="color: #3498db; text-decoration: none; font-size: 12px; margin: 0 10px;">Liên hệ hỗ trợ</a>
                                </div>
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>