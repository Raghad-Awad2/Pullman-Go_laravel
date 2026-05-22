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
        Schema::create('companies', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // اسم الشركة
        $table->string('phone')->nullable(); // رقم الشركة الي رح يظهر للناس بتطبيقي
        $table->string('address')->nullable(); // العنوان
        $table->string('logo_url')->nullable(); // رابط اللوجو
        $table->enum('status', ['active', 'inactive', 'suspended'])->default('active'); // حالة الشركة
        
        // ربط الشركة بالأدمن اللي أنشأها (User ID)
        $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
        
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
