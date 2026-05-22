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
        Schema::table('trips', function (Blueprint $table) {

// تاريخ (اليوم الي رح يوخذو من مصفوفة ايام عمل الرحلة بالاسبوع )
// ورح يمرق على كل الايام الي بالمصفوفة ويوخذ تاريخها ويعمللها سجلات بال db
        $table->date('trip_date')->nullable()->after('bus_id'); // تاريخ اليوم المحدد للرحلة

        $table->date('start_date')->nullable(); // بداية الفترة (للتوثيق)
        $table->date('end_date')->nullable();   // نهاية الفترة (للتوثيق)

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            //
        });
    }
};
