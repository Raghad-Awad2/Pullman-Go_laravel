<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// رح نستخدم هاي لاحقاً لما نعمل جدول المقاعد
use Illuminate\Database\Eloquent\Relations\HasMany; 
use Illuminate\Support\Str; // عشان نولد الرقم المرجعي

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [

        'reference_number',

        'user_id',
        'trip_id',
        // التاريخ الي بدو يسافر فيه المسافر
        'travel_date',
        'seats_count',

// السعر الاجمالي لهاد الحجز (اذا المستخدم حجز اكثر من مقعد بيضرب السعر الاساسي بعدد المقاعد الي حجزها بالكونترولر)
        'total_price',

        'payment_status',
        'payment_method',

        // اذا المسافر وهو عم يحجز حب يبعث ملاحظات للموظف بقدر يبعثلو من هون
        'notes',

    ];



    protected $casts = [
        // مشان يعطيني التاريخ ك نص يعني اسم اليوم
        'travel_date' => 'date',

        // مشان السعر يضل رقم صحيح
        'total_price' => 'decimal:2', 
    ];




    // --- لتوليد الرقم المرجعي تلقائياً قبل إنشاء الحجز ---
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            // رح يولد رقم مثل: PULL-8A9B2C
            $booking->reference_number = 'PULL-' . strtoupper(Str::random(6));
        });
    }



    // --- العلاقات ---

    // 1. الحجز يتبع لمستخدم واحد
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }



    // 2. الحجز يتبع لرحلة واحدة
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
    


    // الحجز الواحد ممكن يكون لاكثر من مقعد 
    public function seats(): HasMany
{
    return $this->hasMany(BookingSeat::class);
}

// اسم الدالة بيمثل "شو بدك تجيب"، واسم الموديل بيمثل "من وين بدك تجيب"
}