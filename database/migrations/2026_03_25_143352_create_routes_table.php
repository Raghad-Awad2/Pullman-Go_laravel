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

    Schema::create('routes', function (Blueprint $table) {
        
        $table->id();

        // ربط المسار بشركة معينة
        $table->foreignId('company_id')->constrained()->onDelete('cascade');
        
        // بيانات الرحلة (إضافة الأدمن)
        $table->string('departure_city_id');    // مدينة الانطلاق
        $table->string('arrival_city_id');      // مدينة الوصول
        $table->text('departure_address');   // عنوان الانطلاق التفصيلي
        $table->text('arrival_address');     // عنوان الوصول التفصيلي
        $table->decimal('distance', 8, 2);   // المسافة بالكيلومتر
        $table->string('estimated_time');    // الوقت التقريبي (مثلاً: 3 ساعات)
        

        // بيانات التسعير ( بضيفه صاحب الشركة فقط)
        $table->decimal('base_price', 12, 2)->nullable(); // السعر الأساسي
        
        
        $table->timestamps();

    });

}




    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
