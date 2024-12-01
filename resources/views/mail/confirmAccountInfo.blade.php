<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $type == 'accept' ? 'Xác Thực Thành Công' : 'Xác Thực Thất Bại' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            padding: 10px 0;
            color: #ffffff;
            border-radius: 8px 8px 0 0;
        }
        .header.success {
            background-color: #28a745;
        }
        .header.failure {
            background-color: #dc3545;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin: 20px 0;
            font-size: 16px;
            line-height: 1.5;
            color: #333;
        }
        .content p {
            margin: 10px 0;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #888;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 0;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            color: #ffffff;
        }
        .btn.success {
            background-color: #28a745;
        }
        .btn.failure {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header {{ $type == 'accept' ? 'success' : 'failure' }}">
        <h1>{{ $type == 'accept' ? 'Đăng Kí Thành Công' : 'Đăng Kí Thất Bại' }}</h1>
    </div>
    <div class="content">
        @if ($type == 'accept')
            <p>Chúc mừng! Tài khoản {{$email}} của bạn đã được xác thực thành công.</p>
            <p>Bây giờ bạn có thể đăng nhập và sử dụng toàn bộ tính năng của chúng tôi.</p>
            <a href="{{ url('/login') }}" class="btn success">Đăng nhập ngay</a>
        @else
            <p>Rất tiếc, quá trình xác thực tài khoản của bạn đã thất bại.</p>
            <p>Vui lòng kiểm tra lại thông tin và thử lại. Nếu cần hỗ trợ, hãy liên hệ với chúng tôi.</p>
            <a href="{{ url('/support') }}" class="btn failure">Liên hệ hỗ trợ</a>
        @endif
    </div>
    <div class="footer">
        <p>Trân trọng,<br>Đội ngũ Hỗ trợ</p>
    </div>
</div>
</body>
</html>
