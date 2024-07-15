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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Product::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(\App\Models\ProductSize::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(\App\Models\ProductColor::class)->constrained()->onDelete('cascade');
            $table->decimal('regular_price', 8, 0)->nullable();
            $table->decimal('reduced_price', 8, 0)->nullable();
            $table->integer('stock')->default(0);
            $table->string("variants_image",255)->nullable();
            $table->unique(['product_id','product_size_id', "product_color_id"],'product_variant_unique');
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
