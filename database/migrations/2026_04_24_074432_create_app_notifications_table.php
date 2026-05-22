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
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();

            // 1. ربط الإشعار بالشركة (عشان نجيب اللوغو والاسم)
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            
            // 2. ربط الإشعار بالموظف اللي أرسله (المرسل)
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            
            $table->string('title');      // عنوان الرسالة العريض
            $table->text('content');      // موضوع الرسالة بالتفصيل
            
            // 4. نوع الجمهور المستهدف 
            $table->string('target_role')->default('passenger');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
