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
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_default')->default(false);
            $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
            $table->string('recipient_name',55);
            $table->string('recipient_phone',11);
            $table->string('recipient_address',255);
            $table->string('province_code');
            $table->foreign('province_code')->references('code')->on('provinces')->cascadeOnDelete();
            $table->string('district_code');
            $table->foreign('district_code')->references('code')->on('districts')->cascadeOnDelete();
            $table->string('ward_code');
            $table->foreign('ward_code')->references('code')->on('wards')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_addresses');
    }
};
