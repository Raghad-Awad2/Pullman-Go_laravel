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

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            // مين بعث؟
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            // لمين وصلت؟
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            // تابعة لأي شركة؟
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            
            $table->text('content'); // نص الكلام
            $table->boolean('is_read')->default(false); // انقرأت ولا لأ؟
            
            $table->timestamps();

        });
    }




    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
