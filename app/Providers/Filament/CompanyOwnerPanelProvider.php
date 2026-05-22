<?php

namespace App\Providers\Filament;

use App\Models\Company; // مشان يفتحلي لوحة التحكم تبعيت صاحب الشركة ومشان تزبط مع ->tenant(Company::class)
use Filament\Facades\Filament; // هاد السطر هو اللي رح يحل الخطأ 500


use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Concerns\HasExtraSidebarAttributes;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;


// --- هدول السطرين مشان الايقونة اللوغو والاسم تبع صاحب الشركة---
use Filament\View\PanelsRenderHook; 
use Illuminate\Support\Facades\Blade;
// ------------------------------------------

// استدعاء الملف مشان اقدر اغير صورة الملف الشحخصي
use App\Filament\Pages\EditProfile;



class CompanyOwnerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {


return $panel

->databaseNotifications() // تفعيل جرس الإشعارات



->default()
// عشان يلغي اسم الشركة من القائمة الجانبية 

->tenantMenu(false)

// الاسم البرمجي للوحة التحكم

// لما تحاول تدخل، فلامنت بيعرف إنه اللوحة اللي واقف عليها اسمها company-owner.

// بيروح ينادي دالة canAccessPanel في ملف المستخدم.

    ->id('company_owner') // توحيد الـ ID مع ملف الـ User

    
           ->passwordReset()
            ->emailVerification()



                        // بتبينلي الملف الشخصي الي بالوحة التحكم
            ->profile()

            // ربط الكلاس EditProfile بهي اللوحة التحكم تبعيت الادمن مشان الصورة بالملف الشخصي

            ->profile(EditProfile::class)
    
  

        ->sidebarCollapsibleOnDesktop()
        ->collapsedSidebarWidth('54px') 
        
        ->sidebarFullyCollapsibleOnDesktop()
        
   

    
    // 1.اظهار اسم الشركة في الرابط 

    ->brandName(fn () => Filament::getTenant()?->name ?? 'أهلا وسهلا بك يا ملك 😎 ')
    ->colors([
        'primary' => \Filament\Support\Colors\Color::Green, // هاد بيخلي اللون الأساسي أخضر
    ])

    
    





        
        ->path('company-owner') // هاد الرابط اللي رح تدخل منه
        ->login() // تفعيل صفحة تسجيل الدخول
        ->colors([
            'primary' => Color::Green, // غير اللون للأزرق عشان تميزه عن الأدمن
        ])


        // --- يا فلامنت، هاي اللوحة مو عامة، هاي اللوحة تابعة لشركة (Company)".---
        //وصاحب الشركة "أ" مستحيل يشوف بيانات الشركة "ب".
         ->tenant(Company::class) 
        
        // ------------------------

        // دالة discoverResources هي إحدى أهم الدوال في Laravel Filament، ووظيفتها "البحث التلقائي" عن ملفات الإدارة (Resources) وتفعيلها داخل اللوحة دون الحاجة لتسجيل كل ملف يدوياً
        ->discoverResources(in: app_path('Filament/CompanyOwner/Resources'), for: 'App\\Filament\\CompanyOwner\\Resources')
        ->discoverPages(in: app_path('Filament/CompanyOwner/Pages'), for: 'App\\Filament\\CompanyOwner\\Pages')




            ->pages([
                Pages\Dashboard::class,
            ])


            ->discoverWidgets(in: app_path('Filament/CompanyOwner/Widgets'), for: 'App\\Filament\\CompanyOwner\\Widgets')
            ->widgets([
  


                // ربط 
                // تسجيل وتفعيل الـ Widget ليظهر في الصفحة الرئيسية للوحة التحكم
                \App\Filament\CompanyOwner\Widgets\CompanyWelcomeWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])







            // 1. كود لتكبير الأيقونة وإظهار الاسم باستخدام CSS
// 1. حقن الستايل في رأس الصفحة (بديل لـ extraStyles)
// 1. حقن الستايل الذكي (بيكبر الأيقونة وبيغير لون الاسم حسب الوضع)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
                    <style>

                        /* تنسيق الاسم */
                        .user-name-label {
                            margin-inline-start: 12px;
                            font-weight: 700;
                            align-self: center;
                            display: flex;
                            color: #111827; /* لون غامق للوضع النهاري */
                        }

                        /* إذا كان الوضع ليلي.. حول اللون لأبيض */
                        .dark .user-name-label {
                            color: #ffffff !important;
                        }
                    </style>
                '),
            )
            // 2. إظهار الاسم بجانب الأيقونة
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('
                    <div class="user-name-label">
                        {{ auth()->user()->name }}
                    </div>
                '),
            );



            
    }
}
