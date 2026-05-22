<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    
protected $fillable = [

        'company_id', 
        'route_id', 
        'bus_id', 

        'scheduled_time', 
        'days_of_week', 
        'is_active',

// رح يمرق على المصفوفة يوم يوم ويوخذ تاريخ اليوم ويعمل سجلات بال db
        'trip_date',

        'start_date',
        'end_date',
    ];




// بخبر الداتا بيز لما ابعثلك مصفوفة حوليها لنص (json) واحفظيها ولما اقرأها حوليها لمصفوفة مرة ثانية
    protected $casts = [
        'days_of_week' => 'array', // عشان نحفظ الأيام كمصفوفة

        // هون مشان يقرأ فقط الساعات والدقائق بدون ثواني 
        'scheduled_time' => 'datetime:H:i', // عشان يقرأ الوقت صح

        
        'trip_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];



    
    //العلاقات مع جداول الربط
    public function company()
    {
    return $this->belongsTo(Company::class);
    }



    public function bus() 
    { 
        return $this->belongsTo(Bus::class); 
    }



    public function route() 
    {
         return $this->belongsTo(Route::class); 
    }



    public function offers() 
    {
    return $this->hasMany(Offer::class);
    }



    // جلب كافة الحجوزات المسجلة على هذه الرحلة
    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class);
    }



    // جلب كافة المقاعد المحجوزة لهذه الرحلة (علاقة غير مباشرة احترافية)

//لما المستخدم يفتح خريطة الباص لرحلة معينة، إحنا محتاجين "قائمة بأرقام المقاعد المحجوزة" فوراَ عشان نلونها بالأحمر.
//بدل ما نكتب كود طويل، بنكتب بس: $trip->bookedSeats فبيرجع لنا كل المقاعد اللي انحجزت بهي الرحلة من كل الناس اللي دفعوا
    public function bookedSeats(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(BookingSeat::class, Booking::class);
    }

}
