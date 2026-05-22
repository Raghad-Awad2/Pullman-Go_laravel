<?php

// *******************
// // ملف الكود الاول مشان تصميم لوحة التحكم تبعيت  (الموظف) 
// *******************

namespace App\Filament\Staff\Widgets;

use Filament\Widgets\Widget;
use Filament\Facades\Filament;

class StaffWelcomeWidget extends Widget
{
    // تغيير المسار ليكون خاصاً بمجلد الموظف (Staff)
    protected static string $view = 'filament.staff.widgets.staff-welcome-widget';

    // الترتيب ليظهر في أعلى الصفحة
    protected static ?int $sort = -1; 
    
    // ليأخذ عرض الصفحة كاملاً
    protected int | string | array $columnSpan = 'full';
}