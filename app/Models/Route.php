<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $fillable = [

        // مشان نربط المسار بالشركة company_id

     'company_id', 
     
     'departure_city_id',
     'arrival_city_id', 
     'departure_address', 
     'arrival_address',
     'distance', 
     'estimated_time',
     'base_price',

    //  الاستراحات
     'rest_stops',

     'price_updated_at'
    
];


protected $casts = 
[
    //  مشان اقدر اعمل اكثر من استراحة ويتخزنن على شكل مصفوفة
    'rest_stops' => 'array', 

    // هون في عندي key  و  value  
    // ال key هوا "stop_name" و "stop_location" 
    // وال value هوا قيم الي فوق 
];


// مهممممم جداااا 


// علاقة لجلب كل مسارات الشركة من داخل المسار نفسه
public function companyRoutes()
{
    return $this->hasMany(Route::class, 'company_id', 'company_id');
}



public function company()
{
    // belongsTo: تعني أن "المسار" يتبع لشركة واحدة فقط (علاقة الجزء بالكل).


    return $this->belongsTo(Company::class, 'company_id');
}




public function buses()
{
    // المسار الواحد له "أكثر من باص" (HasMany)
    return $this->hasMany(Bus::class);
}



// هاد الكود بيعمل حقل "وهمي" بس فلامنت بيشوفه كأنه حقيقي
public function getRouteFullNameAttribute()
{
    return "من مدينة {$this->departureCity->name} إلى مدينة {$this->arrivalCity->name}";
}






// علاقة مدينة الانطلاق
public function departureCity()

// دالة بتربط المسار بجدول المدن عشان تجيب بيانات مدينة الانطلاق  (زي اسمها) باستخدام رقمها (departure_city_id).
{
    return $this->belongsTo(City::class, 'departure_city_id');
}


// علاقة مدينة الوصول
public function arrivalCity()
{
    return $this->belongsTo(City::class, 'arrival_city_id');
}







// المسار الو اكثر من عرض

    public function offers()
    {
        // استخدمت HasOne لأن كل مسار اله عرض واحد (أو حسب نظامك)
    return $this->hasOne(\App\Models\Offer::class, 'route_id');
    }



    // هاي الدالة بتفحص إذا في عرض شغال اليوم، وبترجع سعره، وإذا لأ بترجع السعر الأساسي.
    public function getActivePriceAttribute()
    {
        $currentOffer = $this->offers()
            ->where('is_active', true) // العرض مفعل
            ->whereDate('start_date', '<=', now()) // العرض بلش
            ->whereDate('end_date', '>=', now()) // العرض بعدو ما خلص
            ->first();

        // إذا لقينا عرض فعال رجع سعره، وإلا رجع السعر الأساسي (base_price)
        return $currentOffer ? $currentOffer->offer_price : $this->base_price;
    }






        public function trips() 
    {
         return $this->hasMany(Trip::class); 
    }
}
