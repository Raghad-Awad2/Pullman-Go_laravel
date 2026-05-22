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
        Schema::create('buses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            $table->string('bus_number')->unique(); // رقم اللوحة
            $table->string('driver_name');          // اسم السائق
            $table->integer('total_seats');        // عدد المقاعد
            $table->string('bus_model')->nullable(); // الموديل 
            $table->string('status')->default('active'); // الحالة


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};
