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
        Schema::table('messages', function (Blueprint $table) {
            // إضافة الحقل اللي بيربط الرد بالرسالة الأصلية
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id') // بيحطه بعد حقل الـ ID عشان الترتيب
                ->constrained('messages')
                ->onDelete('cascade');
        });
    }




    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
