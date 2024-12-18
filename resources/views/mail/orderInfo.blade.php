<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
        line-height: 1.6;
        color: #333;
    }

    .email-container {
        max-width: 600px;
        margin: 20px auto;
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .header {
        background-color: #4CAF50;
        color: #fff;
        padding: 20px;
        text-align: center;
    }

    .header h1 {
        margin: 0;
        font-size: 24px;
    }

    .content {
        padding: 20px;
    }

    .content h1 {
        color: #4CAF50;
        font-size: 20px;
        margin-top: 0;
    }

    .content p {
        font-size: 16px;
        margin: 10px 0;
    }

    .footer {
        background: #f8f9fa;
        color: #555;
        text-align: center;
        padding: 10px;
        font-size: 12px;
    }

    .footer a {
        color: #4CAF50;
        text-decoration: none;
    }

    ul {
        list-style-type: none;
        padding: 0;
    }

    ul li {
        padding: 5px 0;
    }

    .highlight {
        font-weight: bold;
        color: #4CAF50;
    }
</style>
<body>
<div class="email-container">
    <div class="header">
        <h1>Thông tin đơn hàng #{{$order->order_code}}</h1>
    </div>

    <div class="content">
        @if($status == "Chờ xác nhận")
            <h1>Bạn đã đặt thành công đơn hàng <span class="highlight">{{$order->order_code}}</span></h1>
            <p><strong>Mã đơn hàng:</strong> {{$order->order_code}}</p>
            <p><strong>Tổng giá tiền:</strong> {{ number_format($order->total_amount, 0, ',', '.') }} đ</p>
            <p><strong>Phí vận chuyển:</strong> {{ number_format($order->delivery_fee, 0, ',', '.') }} đ</p>
            <p><strong>Phương thức thanh toán:</strong> {{$order->payment_method}}</p>
            <p><strong>Địa chỉ giao hàng:</strong> {{$order->order_address}}</p>
        @endif

        @if($status == "Đã xác nhận")
            <h1>Đơn hàng <span class="highlight">{{$order->order_code}}</span> của bạn đã được xác nhận</h1>
        @endif

        @if($status == "Đang giao")
            <h1>Đơn hàng <span class="highlight">{{$order->order_code}}</span> đang được giao tới bạn</h1>
        @endif

        @if($status == "Đã huỷ")
            <h1>Đơn hàng <span class="highlight">{{$order->order_code}}</span> đã được huỷ</h1>
        @endif

        @if($status == "Đã giao")
            <h1>Đơn hàng <span class="highlight">{{$order->order_code}}</span> đã được giao thành công</h1>
        @endif
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} Your Company. All rights reserved.</p>
        <p><a href="#">Liên hệ chúng tôi</a></p>
    </div>
</div>
</body>
