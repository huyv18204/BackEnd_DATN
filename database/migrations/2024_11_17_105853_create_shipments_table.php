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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 11)->unique();
            $table->foreignIdFor(\App\Models\DeliveryPerson::class)->constrained();
            $table->enum('status', ["Chờ giao hàng", "Đang giao hàng","Hoàn thành giao hàng"])->default('Chờ giao hàng');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
