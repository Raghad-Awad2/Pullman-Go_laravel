<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    // الحقول اللي مسموح نعبّيها
    protected $fillable = [

        'sender_id',
        'receiver_id',
        'company_id',
        'content',
        'is_read',

        'parent_id'
    ];



    // علاقة: الرسالة تنتمي لمرسل (مستخدم)
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }



    // علاقة: الرسالة تنتمي لمستقبل (مستخدم)
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }



    // علاقة: الرسالة تابعة لشركة معينة
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    



    // علاقة عشان تعرف الرسالة الأصلية
    // مشان يجيبلي الرسالة الاصلية الي اني بعثتها واجاني رد عليهامشان تبين لما اضغط على اكشن القراءة
    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }


}