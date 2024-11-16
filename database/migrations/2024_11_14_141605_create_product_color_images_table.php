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
        Schema::create('product_color_images', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Product::class)->index()->constrained();
            $table->foreignIdFor(\App\Models\Color::class)->index()->constrained();
            $table->string("image", 255)->nullable();
            $table->unique(['product_id', 'color_id'], 'product_color_image_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_color_images');
    }
};
