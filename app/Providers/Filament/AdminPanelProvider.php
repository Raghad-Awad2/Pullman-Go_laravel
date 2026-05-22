<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()

            
            

            // للوغو في صفحة تسجيل الدخول وفي لوحة التحكم في الزاوية 

        ->brandLogo(fn () => new \Illuminate\Support\HtmlString('<img src="'.asset('images/logo.png').'" class="rounded-full object-cover shadow-lg border-2 border-amber-500" style="height: 3.6rem; width: 3.6rem; margin: auto;">'))
        ->favicon(asset('images/favicon.png')) 
        ->brandLogoHeight('4.2rem')
        

            // مشان القائمة الجانبية المربع 
            ->sidebarFullyCollapsibleOnDesktop()
            
            // مشان الايقونات تظهر بالقائمة الجانبية 
           ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('54px') 


            ->id('admin')
            ->path('admin')
            ->login()

            
            ->passwordReset()
            ->emailVerification()

            // بتبينلي الملف الشخصي الي بالوحة التحكم
            ->profile()

            // ربط الكلاس EditProfile بهي اللوحة التحكم تبعيت الادمن مشان الصورة بالملف الشخصي

            ->profile(EditProfile::class)




            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([

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
