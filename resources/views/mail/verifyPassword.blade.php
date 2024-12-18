<!DOCTYPE html>
<html>
<head>
    <title>Đặt lại mật khẩu</title>
</head>
<body style="width: 100%; min-height: 600px; background-color: #dbdde4; font-size: 18px; font-family: Arial, sans-serif;">
    <h1 style="text-align: center; margin-top: 1.5rem; margin-bottom: 1.5rem;">{{ config('app.name') }}</h1>
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 20px; border: 1px solid #e1e1e1; border-radius: 8px; min-height: 300px; color: #718096;">
        <h2 style="color: #3d4852;">Đặt lại mật khẩu</h2>
        <p>Chào bạn,</p>
        <p>Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn. Mã OTP để đặt lại mật khẩu của bạn là <b>{{ $otp }}</b></p>
        <p>Liên kết này sẽ hết hạn sau {{ $minutes }} phút.</p>
        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        <p>Cảm ơn,<br>{{ config('app.name') }}</p>
    </div>
    <footer style="margin-top: 1.5rem; width: 100%; text-align: center; color: #718096;">© 2024 ShopHQ2621. All rights reserved.</footer>
</body>
</html>
