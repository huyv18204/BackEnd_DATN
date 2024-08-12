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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Order::class)->constrained();
            $table->foreignIdFor(\App\Models\Product::class)->constrained();
            $table->foreignIdFor(\App\Models\ProductAtt::class)->constrained();
            $table->integer('quantity');
            $table->string("size", 55);
            $table->string("color", 55);
            $table->string("product_name", 255);
            $table->decimal('unit_price', 10, 0);
            $table->decimal('total_amount', 10, 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
