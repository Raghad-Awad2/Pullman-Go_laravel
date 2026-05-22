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
        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();

            // ربط المقعد بالحجز الأساسي (الأب)
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            
            // رقم المقعد اللي اختاره من الخريطة (مثلاً 15)
            $table->integer('seat_number');
            
            // اسم المسافر وليس اسم المستخدم
            $table->string('passenger_name');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
    }
};
