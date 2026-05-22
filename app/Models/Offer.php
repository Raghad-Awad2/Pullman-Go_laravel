<?php

namespace App\Models;

// المسار ممكن نعمل عليه اكثر من عرض بفترات مختلفة
// اما العرض هوا تابع لمسار محدد واحد

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    
    protected $fillable = [

    'route_id',
    'company_id',
    // الرحلة فيها اكثر من عرض 
    'trip_id',

    'offer_price',
    'days_of_week',

    'start_date',
    'end_date',

    'is_active',


    ];




    protected $casts = 
    [
    // هي مشان يقدر يحدد اكثر من رحلة بجدول العروض 
    // نخزن "مصفوفة أرقام" في حقل واحد
    'trip_id' => 'array', // ضروري جداً عشان يحول الـ JSON لمصفوفة تلقائياً
    'days_of_week' => 'array', // عشان نحفظ الأيام كمصفوفة

    ];



    public function trips()
{
    // بنقله جيب كل الرحلات اللي الـ ID تبعها موجود جوات مصفوفة الـ trip_id
    return \App\Models\Trip::whereIn('id', $this->trip_id ?? [])->get();
}



// العرض تباع لمسار واحد محدد
    public function route()
    {
        return $this->belongsTo(Route::class);
    }


    public function departureCity() {
    return $this->belongsTo(City::class, 'departure_city_id');
}

public function arrivalCity() {
    return $this->belongsTo(City::class, 'arrival_city_id');
}



    // ربط لوحة التحكم تبعيت الموظف بالشركة التابع الها 
    public function company()
{
    return $this->belongsTo(Company::class);
}



public function trip() 
{
    return $this->belongsTo(Trip::class);
}


}
