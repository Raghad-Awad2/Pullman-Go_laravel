<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bus extends Model
{
    
    protected $fillable = [
        'company_id',

        'route_id',

        // رقم الباص
        "bus_numbernnn",

        // رقم اللوحة
        'bus_number',


        'driver_name',
        'driver_phone',
        'total_seats',
        'bus_model',
        'status',
    ];




    // 2. علاقة الباص بالشركة (كل باص يتبع لشركة واحدة)
    public function company(): BelongsTo
    {
// علاقة الـ belongsTo: هي اللي بتخلي فلامنت يعرف إن هذا الباص ملك للشركة الفلانية، وهيك لما ندخل بلوحة صاحب الشركة، السيستم بيعرف يجيب له باصاته هو بس
        return $this->belongsTo(Company::class);
    }


    
    // مشان الباص يكون الو مسار محدد للشركة
        public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }


    
    public function trips() 
    {
         return $this->hasMany(Trip::class); 
    }


}
