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
        Schema::create('cars', function (Blueprint $table) {
    $table->id();

    $table->string('name'); // اسم السيارة (اختياري)
    $table->string('plate_number'); // لوحة السيارة
    $table->string('color');
    $table->integer('daily_km'); // الكيلومترات المسموحة يومياً
    $table->decimal('price', 8, 2); // سعر الإيجار
    $table->integer('model_year')->nullable();

    // الصور
    $table->string('image1');
    $table->string('image2');
    $table->string('image3');

    $table->boolean('available')->default(true);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
