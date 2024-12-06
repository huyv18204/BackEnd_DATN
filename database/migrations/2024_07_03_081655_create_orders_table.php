<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 10)->unique();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total_amount', 10, 0);
            $table->decimal('total_product_amount', 10, 0);

            $table->enum('payment_method', [
                'Thanh toán khi nhận hàng',
                'MOMO',
                'VNPAY'
            ]);
            $table->enum('order_status', [
                'Chờ xác nhận',
                'Đã xác nhận',
                'Chờ lấy hàng',
                'Đang giao',
                'Đã giao',
                'Trả hàng',
                "Đã huỷ",
                'Đã nhận hàng',
                'Chưa nhận hàng'
            ])->default("Chờ xác nhận");
            $table->foreignIdFor(\App\Models\DeliveryPerson::class)->nullable()->constrained()->onDelete('set null');
            $table->enum('payment_status', [
                'Chưa thanh toán',
                'Đã thanh toán',
                'Thanh toán thất bại'
            ]);
            $table->string('order_address', 255);
            $table->decimal('delivery_fee', 10, 0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
