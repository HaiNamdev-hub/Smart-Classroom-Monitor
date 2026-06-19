<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác nhận đăng ký</title>
</head>

<body style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" style="width:100%; border-collapse:collapse; background-color:#f4f4f4;">
        <tr>
            <td style="padding:20px 0;">
                <table role="presentation" style="max-width:600px; margin:0 auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#00c6ff 0%,#0072ff 100%); padding:35px 30px; text-align:center; color:#fff;">
                            <h1 style="margin:0; font-size:28px; font-weight:700;">Xác nhận email đăng ký</h1>
                            <p style="margin:10px 0 0; font-size:16px; opacity:.9;">Nhập mã bên dưới để hoàn tất đăng ký tài khoản</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 30px;">
                            <p style="margin:0 0 20px; color:#333; font-size:16px;">Xin chào <strong>{{ $data['ho_ten'] }}</strong>,</p>
                            <p style="margin:0 0 30px; color:#555; font-size:15px; line-height:1.7;">
                                Cảm ơn bạn đã đăng ký tài khoản tại <strong>Đánh Giá Rèn Luyện</strong>.
                                Mã xác nhận đăng ký của bạn là:
                            </p>
                            <div style="margin:0 auto 30px; width:fit-content; background:#f3f9ff; padding:18px 28px; border-radius:16px; text-align:center; border:1px solid #d3e8ff;">
                                <span style="font-size:28px; font-weight:700; letter-spacing:4px; color:#0f4db7;">{{ $data['code'] }}</span>
                            </div>
                            <p style="margin:0 0 20px; color:#555; font-size:15px; line-height:1.7;">
                                Mã xác nhận có hiệu lực trong 60 phút. Nếu bạn không yêu cầu mã này, hãy bỏ qua email.
                            </p>
                            <p style="margin:0; color:#555; font-size:15px; line-height:1.7;">
                                Nếu bạn cần mở lại trang đăng ký, hãy sử dụng đường dẫn sau:
                            </p>
                            <p style="word-break:break-all; background:#f8f9fa; padding:14px 16px; border-radius:10px; margin:12px 0 0; font-family:monospace; font-size:14px; color:#333; border:1px solid #e6edf7;">
                                {{ $data['link'] }}
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8f9fa; padding:25px 30px; text-align:center; color:#667785; font-size:13px;">
                            <p style="margin:0;">📧 Email tự động, vui lòng không trả lời.</p>
                            <p style="margin:6px 0 0;">© Đánh Giá Rèn Luyện</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
