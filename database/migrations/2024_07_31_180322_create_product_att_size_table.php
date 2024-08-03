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
        Schema::create('product_att_size', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\ProductAtt::class)->constrained();
            $table->foreignIdFor(\App\Models\Size::class)->constrained();
            $table->integer('stock_quantity')->default(0);
            $table->primary(['size_id','product_att_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_att_size');
    }
};
