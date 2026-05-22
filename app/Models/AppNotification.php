<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    // عشان أقدر أنشئ بيانات وهمية (Dummy Data) للموديل بسهولة
    use HasFactory;

    protected $fillable = [
        'company_id',
        'sender_id',
        'title',
        'content',
        'target_role',
    ];




    // علاقة لجلب بيانات الشركة (الاسم واللوغو)
    // الاشعار تابع لشركة واحدة فقط
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }



    // علاقة لجلب بيانات الموظف اللي أرسل
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}