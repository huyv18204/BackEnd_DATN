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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string("sku", 10)->unique();
            $table->string("name", 55);
            $table->string("thumbnail", 255);
            $table->text("short_description")->nullable();
            $table->text("long_description")->nullable();
            $table->unsignedBigInteger("view")->default(0);
            $table->decimal('regular_price', 8, 0);
            $table->decimal('reduced_price', 8, 0)->nullable();
            $table->foreignIdFor(\App\Models\ProductCategory::class);
            $table->integer("stock")->default(0);
            $table->boolean("is_active")->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
