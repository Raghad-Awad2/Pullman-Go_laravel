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
        // إضافة الرتبة (النوع)
        $table->string('role')->default('admin'); // admin, owner, staff, customer
        
        // ربط المستخدم بالشركة (اختياري للأدمن والمسافر، وإجباري للبقية الي هما (صاحب الشركة والموظف))
        $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('set null');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
