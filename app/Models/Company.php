<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    protected $fillable = [
        'name', 

        'location_url',

        'phone', 
        'address', 
        'logo_url',
        'status', 
        'admin_id',

        // عشان نربط الشركة بصاحبها
        'owner_id',

        // مشان الميزات
        'features',
        'slogan',

       
    ];

// عشان لارافيل يفهم إن هاد الحقل عبارة عن "قائمة" مش مجرد نص
    protected $casts = [
    'features' => 'array',
];







    // دوال العلاقات 

    // الشركة تتبع للأدمن الذي أنشأها
// علاقة بتجيب الأدمن اللي أنشأ هي الشركة من الأساس
public function admin(): BelongsTo
{
    return $this->belongsTo(User::class, 'admin_id');
}



// علاقة بتجيب كل الناس اللي بالشركة (مدراء وموظفين)
public function users(): HasMany
{
    return $this->hasMany(User::class);
}



// دالة احترافية: بتجيبلك بس "الموظفين" (staff) تبعين هي الشركة بدون المدير
public function staff(): HasMany
{
    return $this->hasMany(User::class)->where('role', 'staff');
}




// دالة احترافية: بتجيبلك بس "صاحب الشركة" (owner) لهي الشركة

// صاحب الشركة بصير "رسمياً" هو المربوط بالحقل الجديد owner_id.
public function owner(): BelongsTo
{
    // الربط الصحيح: الشركة "تنتمي" لمستخدم واحد عبر الحقل owner_id
    return $this->belongsTo(User::class, 'owner_id');
}





// مشان ما يطلعلي erore
// مشان نظام تعدد الشركات Tenancy
public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(Message::class);
}

// hasMany: تعني أن "الشركة" تمتلك عدة مسارات (علاقة الكل بالأجزاء)

public function routes()
{
    return $this->hasMany(Route::class, 'company_id');
}




// كل شركة عندها اكثر من باص 
public function buses()
{
    return $this->hasMany(Bus::class);
}



// الشركة ممكن ان تحتوي على اكثر من عرض 
// مشان نظام تعدد الشركات Tenancy

public function offers()
{
    return $this->hasMany(Offer::class);
}



public function trips() 
{
     return $this->hasMany(Trip::class); 
}



// كل شركة عندها اكثر من اشعار او بتقدر ترسل اكثر من رسالة للتطبيق
public function appNotifications(): HasMany
{
    return $this->hasMany(AppNotification::class, 'company_id');
}


// كل شركة عندها اكثر من مدينة
public function cities() 
{
    return $this->belongsToMany(City::class);
}

}