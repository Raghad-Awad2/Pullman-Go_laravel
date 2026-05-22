<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
   protected $fillable = [
    'name', 
    'is_active'
    
];


// الدينة ممكن تكون تابعة لاكثر من شركة
public function companies() 
{
    return $this->belongsToMany(Company::class);
}


}
