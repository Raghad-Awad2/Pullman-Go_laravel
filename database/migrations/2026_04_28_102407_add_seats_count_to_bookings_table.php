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
        Schema::table('bookings', function (Blueprint $table) {
            
        // عدد المقاعد المحجوزة للمستخدم الواحد
        $table->integer('seats_count')->default(1)->after('trip_id');
        // حطي هذا العمود في الجدول بعد عمود trip_id مباشرة after

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            //
        });
    }
};
