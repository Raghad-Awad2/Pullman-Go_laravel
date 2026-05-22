<?php

namespace App\Providers\Filament;

use App\Models\Company;
use Filament\Facades\Filament;
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
use Filament\View\PanelsRenderHook; 
use Illuminate\Support\Facades\Blade;
use App\Filament\Pages\EditProfile;

class StaffPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel

        ->databaseNotifications() // تفعيل جرس الإشعارات



            // الموظف ليس اللوحة الافتراضية
            ->tenantMenu(false)

            // الاسم البرمجي والمسار للموظف
            ->id('staff') 
            ->path('staff') 



            ->passwordReset()
            ->emailVerification()

             ->profile()

             // ربط الكلاس EditProfile بهي اللوحة التحكم تبعيت الادمن مشان الصورة بالملف الشخصي

            ->profile(EditProfile::class)

            ->sidebarCollapsibleOnDesktop()
            ->collapsedSidebarWidth('54px') 
            ->sidebarFullyCollapsibleOnDesktop()

            // إظهار اسم الشركة في الرابط أو رسالة ترحيب
            ->brandName(fn () => Filament::getTenant()?->name ?? 'أهلاً بك يا بطل الموظفين 😎')

            ->login() 
            ->colors([
                'primary' => Color::Orange, // لون برتقالي للموظف لتمييزه
            ])


            // ربط الموظف بالشركة (Tenant)
            ->tenant(Company::class, slugAttribute: 'id')
            ->tenantRegistration(null) // عشان ما يطلب تسجيل شركة جديدة


            // البحث عن الملفات في مجلد Staff حصراً
            ->discoverResources(in: app_path('Filament/Staff/Resources'), for: 'App\\Filament\\Staff\\Resources')
            ->discoverPages(in: app_path('Filament/Staff/Pages'), for: 'App\\Filament\\Staff\\Pages')

            ->pages([
                Pages\Dashboard::class,
            ])

            ->discoverWidgets(in: app_path('Filament/Staff/Widgets'), for: 'App\\Filament\\Staff\\Widgets')


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

            // حقن الستايل والاسم بجانب الأيقونة (نفس طلبك تماماً)
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('
                    <style>
                        .user-name-label {
                            margin-inline-start: 12px;
                            font-weight: 700;
                            align-self: center;
                            display: flex;
                            color: #111827;
                        }
                        .dark .user-name-label {
                            color: #ffffff !important;
                        }
                    </style>
                '),
            )
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