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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            // ربط الرحلة بالشركة، المسار، والباص
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('bus_id')->constrained()->onDelete('cascade');
            
            // وقت الرحلة الثابت (مثلاً 09:00:00) - لمنطق الـ 5 ساعات
            
            $table->time('scheduled_time'); 
            
            // الأيام التي تعمل فيها هذه الرحلة (سبت، أحد...)
            $table->json('days_of_week')->nullable(); 
            
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
