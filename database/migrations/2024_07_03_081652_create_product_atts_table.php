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
        Schema::create('product_atts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Product::class)->constrained();
            $table->foreignIdFor(\App\Models\Size::class)->constrained();
            $table->foreignIdFor(\App\Models\Color::class)->constrained();
            $table->integer('stock_quantity')->default(0);
            $table->string("image",255)->nullable();
            $table->unique(['product_id','size_id', "color_id"],'product_variant_unique');
            $table->boolean("is_active")->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
