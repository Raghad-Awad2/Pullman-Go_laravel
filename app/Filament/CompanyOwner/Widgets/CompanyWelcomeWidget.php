<?php

// *******************
// // ملف الكود الاول مشان تصميم لوحة التحكم تبعيت صاحب الشركة 
// *******************

namespace App\Filament\CompanyOwner\Widgets;

use Filament\Widgets\Widget;
use Filament\Facades\Filament;

class CompanyWelcomeWidget extends Widget
{
    protected static string $view = 'filament.company-owner.widgets.company-welcome-widget';

    // عشان تطلع أول وحدة فوق بالنص وتأخذ عرض الصفحة كامل
protected static ?int $sort = -1; 
protected int | string | array $columnSpan = 'full';
}