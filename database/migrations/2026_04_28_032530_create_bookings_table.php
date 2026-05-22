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

            // الرقم المرجعي للحجز (نولده تلقائياً)
            $table->string('reference_number')->unique();
            
            // ربط الحجز بالمستخدم (المسافر)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // ربط الحجز بالرحلة
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            
            // تاريخ السفر اللي اختاره المستخدم من التطبيق
            $table->date('travel_date');
            
            // السعر الإجمالي للحجز
            $table->decimal('total_price', 10, 2); 
            
            // حالة الدفع: غير مدفوع بالبداية
            // الحالات: unpaid (غير مدفوع), paid (مدفوع), cancelled (ملغي)
            $table->string('payment_status')->default('unpaid');
            
            // طريقة الدفع 
            $table->string('payment_method')->nullable();

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
