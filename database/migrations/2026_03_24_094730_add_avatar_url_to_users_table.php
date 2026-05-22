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
        Schema::table('users', function (Blueprint $table) {

// مشان الصورة لوحة الادمن بقدر اغيرها بالايقونة الي على اليسار من فوق 

            $table->string('avatar_url')->nullable(); // هاد الحقل اللي رح يخزن مسار الصورة


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
