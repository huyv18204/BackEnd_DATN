<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 10)->unique();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total_amount',10,0);
            $table->enum('payment_method', [
                'Thanh toán khi nhận hàng',
                'MOMO'
            ]);
            $table->enum('order_status', [
                'Chờ xác nhận',
                'Chờ lấy hàng',
                'Đang giao',
                'Đã giao',
                'Trả hàng',
                "Đã huỷ"
            ])->default("Chờ xác nhận");
            $table->enum('payment_status', [
                'Chưa thanh toán',
                'Đã thanh toán',
            ]);
            $table->string('order_address',255);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
