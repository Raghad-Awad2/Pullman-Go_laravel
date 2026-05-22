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
           
        // حقل رمز التحقق
        $table->string('otp_code')->nullable()->after('password');
        
        // وقت انتهاء الرمز
        $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
        
        // حالة الحساب (مفعل أو لا)
        $table->boolean('is_active')->default(false)->after('otp_expires_at');

        
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
