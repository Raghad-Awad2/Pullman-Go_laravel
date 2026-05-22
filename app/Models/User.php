<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;


// هون مشان الايقونة السودة الي بلوحة التحكم تبعيت صاحب الشركة
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;

class User extends Authenticatable implements FilamentUser, HasTenants, HasAvatar, HasName
{
    
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        
        'otp_code',        // حقل الرمز (جديد)
        'otp_expires_at',  // وقت انتهاء الرمز (جديد)
        'is_active',       // حالة الحساب (جديد)
        
        'phone',

        'password',
        'role',        
        'company_id', 
        
        "avatar_url",
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // --- العلاقات ---
    
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
        
    }



    // جلب كافة حجوزات هذا المستخدم
    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class);
    }


    //  (يعني بتبويب رحلاتي بالفلتر )دالة إضافية للتأكد إذا كان المسافر لديه قائمة حجوزات 

//إذا فتح المستخدم صفحة "حجوزاتي" وكانت الدالة بتعطي false (يعني ما عنده ولا حجز)، بنظهرله صورة "لا يوجد لديك حجوزات حالياً" بدل ما تظهر الصفحة فاضية وشكلها غلط.
// الحماية: ممكن نستخدمها عشان نمنع المستخدم من تقييم التطبيق أو كتابة شكوى إلا إذا كان "عنده حجوزات فعلاً" (hasBookings
    public function hasBookings(): bool
    {
        return $this->bookings()->exists();
    }





// **********************************************
// ********************************************

// مهمممممممممممم جداااااااا لتحديد صلاحيات الدخول تبعات لوحات التحكم

// ********************************************


    // 1. صلاحية دخول اللوحات (هون دمجنا كل اللوحات بدالة وحدة)
    public function canAccessPanel(Panel $panel): bool
    {

         // إذا كان المستخدم مسافر، امنعه فوراً من كل اللوحات
        if ($this->role === 'passenger') {
            return false;
        }



        if ($panel->getId() === 'admin') {
            return $this->role === 'admin';
        }

        if ($panel->getId() === 'company_owner') {
            // الأدمن وصاحب الشركة بيقدروا يدخلوا لوحة الـ Owner
            return in_array($this->role, ['admin', 'owner']);
        }


        // لوحة الموظف (للأدمن، صاحب الشركة، والموظف) <--
        if ($panel->getId() === 'staff') {
            return in_array($this->role, ['admin', 'owner', 'staff']);
        }



        return false;
    }







    // 2. جلب الشركات التابع لها (للوحة صاحب الشركة)
// 2. جلب الشركات التابع لها (للوحة صاحب الشركة)
public function getTenants(Panel $panel): array|\Illuminate\Support\Collection
{
    // سطر واحد فقط يكفي
    return $this->company ? collect([$this->company]) : collect();
}

    // 3. فحص هل مسموح له يدخل هاي الشركة بالتحديد
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->company_id === $tenant->id || $this->role === 'admin';

    }







// ****************************
// ****************************
// هدول مشان الايقونة الصغيرة الي بلوحة التحكم تبعيت صاحب الشركة بتبين اللوغو والاسم

// ****************************

public function getFilamentName(): string
    {
        return $this->name;
    }






    // الدالة السحرية اللي بتجيب اللوغو للدائرة للوحات التحكم الي عندي
public function getFilamentAvatarUrl(): ?string
{
    // 1. إذا كان المستخدم الحالي داخل لوحة الأدمن (أو لديه صلاحية دخولها)
    // بنعرض له الصورة الشخصية اللي رفعها من الـ Profile


// 1. الأولوية القصوى: إذا المستخدم (أدمن، صاحب شركة، أو موظف) رفع صورة شخصية
    if ($this->avatar_url) {
        return asset('storage/' . $this->avatar_url);
    }

    // 2. إذا كان المستخدم هو "صاحب شركة" (دخل لوحة الشركة)
    // بنعرض له لوغو الشركة الخاص فيه حصراً
    if ($this->company && $this->company->logo_url) {
        return asset('storage/' . $this->company->logo_url);
    }

    // 3. الحالة الافتراضية (أول حرف من الاسم)
    return null;
}



// *******************************
// *****************************






    // --- دوال التحقق المساعدة (بتحتاجها بالكود) ---

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }


    // المسافر فقط هو الي بيحجز 
    public function isPassenger(): bool
{
    return $this->role === 'passenger';
}





}