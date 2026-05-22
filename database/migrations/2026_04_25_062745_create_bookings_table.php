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
        Schema::create('bookings', function (Blueprint $table) {
    $table->id();

    $table->foreignId('car_id')->constrained()->onDelete('cascade'); // السيارة
    $table->foreignId('user_id')->constrained()->onDelete('cascade'); // المستخدم



    $table->string('full_name');
    $table->string('phone');

    $table->string('id_front');   // صورة الهوية الأمامي
    $table->string('id_back');    // صورة الهوية الخلفي

    $table->date('pickup_date');  // الاستلام
    $table->date('return_date');  // التسليم
     $table->time('pickup_time')->nullable();
    $table->time('return_time')->nullable();

    $table->boolean('delivery')->default(false); // خدمة التوصيل
    $table->string('delivery_location')->nullable(); // المكان

    $table->string('payment_image'); // إشعار الدفع
    $table->string('status')->default('pending');
$table->text('rejection_reason')->nullable();


    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
