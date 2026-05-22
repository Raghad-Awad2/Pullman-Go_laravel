<x-filament-widgets::widget>



<!-- *********************الملف الثاني ملف التصيميم*** -->
<!-- هاد الكود للتصميم او ملف للتصميم رح يعرض اللوغو و اسم الشركة و اسم صاحب الشركة بالنص في لوحة التحكم-->
<!-- ****************** -->


<!-- مشان الساعة في لوحة التحكم-- -->
<div class="flex flex-col md:flex-row justify-between items-start md:items-center w-full mb-8  ">

    {{-- جهة اليسار: التاريخ والساعة بتصميم احترافي --}}
    <div class="flex items-center gap-4 bg-white dark:bg-gray-900 px-4 py-2 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800">
        {{-- أيقونة التاريخ --}}
        <div class="flex items-center gap-2 border-r border-gray-200 dark:border-gray-700 pr-4">
            <x-filament::icon icon="heroicon-m-calendar-days" class="h-5 w-5 text-gray-400" />
            <span class="text-sm font-bold text-gray-600 dark:text-gray-400">
                {{ now()->translatedFormat('l, d F Y') }}
            </span>
        </div>
        
        {{-- الساعة --}}
        <div class="flex items-center gap-2 pl-2">
            <x-filament::icon icon="heroicon-m-clock" class="h-5 w-5 text-primary-500" />
            <span id="header-live-clock" class="text-xl font-mono font-black text-primary-600 dark:text-primary-400">
                00:00:00
            </span>
        </div>
    </div>
    
</div>









{{-- إضافة ستايل النيون --}}
{{-- إضافة ستايل النيون الذكي للوضعين --}}
    <style>
@keyframes neon-glow {
    from {
        /* لمعة قوية بأكثر من طبقة */
        text-shadow: 0 0 10px #fff, 0 0 20px #fff, 0 0 40px #3b82f6, 0 0 70px #3b82f6, 0 0 100px #3b82f6;
    }
    to {
        /* تزداد القوة عند النبض */
        text-shadow: 0 0 20px #fff, 0 0 30px #fff, 0 0 60px #3b82f6, 0 0 100px #3b82f6, 0 0 150px #3b82f6;
    }
}
        
        /* التعديل هون: شلنا الـ !important الثابتة */
        .neon-text {
            animation: neon-glow 1.5s ease-in-out infinite alternate;
        }

        /* في الوضع الليلي: اجبر اللون يكون أبيض */
        .dark .neon-text {
            color: #ffffff !important;
        }

        /* في الوضع النهاري: اجبر اللون يكون أسود أو غامق عشان يبين */
        .neon-text {
            color: #111827; /* لون غامق جداً للوضع النهاري */
        }
    </style>




    {{-- إزالة الحدود (border-none) والظلال (shadow-none) لجعل التصميم انسيابي --}}
    <div class="flex flex-col items-center justify-center p-8  rounded-2xl">
        
        @php 
            $tenant = \Filament\Facades\Filament::getTenant();
            // جلب المستخدم الحالي لعرض اسمه الحقيقي من جدول الـ users
            $user = auth()->user(); 
        @endphp







        {{-- قسم اللوغو الدائري --}}
{{-- الـ div الأب لازم يكون relative --}}
@php 
    $tenant = \Filament\Facades\Filament::getTenant();
@endphp

<div class="flex justify-center mb-10">
    {{-- الحاوية النسبية (القفص) --}}
    <div class="relative" style="width: 200px; height: 200px;">
        
        {{-- حاوية اللوغو مع القص --}}
        <div class="overflow-hidden rounded-full bg-gradient-to-tr from-primary-500 to-blue-500 p-1 shadow-2xl" 
             style="width: 100%; height: 100%;">
            
            @if($tenant && $tenant->logo_url)
                <img src="{{ asset('storage/' . $tenant->logo_url) }}" 
                     class="rounded-full border-4 border-white dark:border-gray-900"
                     style="width: 100%; height: 100%; object-fit: cover; object-position: center; display: block;">
            @else
                {{-- لوغو افتراضي في حال ما في صورة --}}
                <div class="flex items-center justify-center w-full h-full bg-gray-200 rounded-full">
                    <x-filament::icon icon="heroicon-m-photo" class="w-12 h-12 text-gray-400" />
                </div>
            @endif
        </div>

        {{-- النقطة الخضراء - برا حاوية القص عشان ما تنوكل --}}
{{-- النقطة الخضراء المتوهجة --}}
        <div style="position: absolute; bottom: 20px; right: 20px; z-index: 999; display: flex; height: 24px; width: 24px;">

            {{-- تأثير النبض --}}

            <span style="position: absolute; display: inline-flex; height: 100%; width: 100%; border-radius: 9999px; background-color: #4ade80; opacity: 0.75;" class="animate-ping"></span>

            {{-- النقطة الثابتة - لون أخضر فاقع جداً --}}

            <span style="position: relative; display: inline-flex; border-radius: 9999px; height: 24px; width: 24px; background-color: #22c55e; border: 3px solid #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.3);"></span>

        </div>

    </div>

</div>








        {{-- قسم معلومات الشركة --}}
<div class="text-center space-y-4">
          
            {{-- هون التعديل الوحيد: أضفنا كلاس neon-text عشان يلمع --}}
            <h1 class="text-8xl font-black neon-text text-gray-900 dark:text-white tracking-tighter">
                {{ $tenant?->name }}
            </h1>
            
            {{-- اسم صاحب الشركة الحقيقي (تم تعديل المصدر ليكون من الـ User) --}}
            <div class="flex items-center justify-center">
                <span class="px-4 py-1.5 bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 rounded-full text-lg font-bold">
                   بإدارة الملك : {{ $user->name }}
                </span>
            </div>

            {{-- إضافات ذكية: العنوان والهاتف (إذا كانا موجودين في موديل الشركة) --}}
            <div class="flex flex-col md:flex-row items-center justify-center gap-4 text-gray-500 dark:text-gray-400 mt-4 text-sm font-medium">
                @if($tenant?->address)
                    <div class="flex items-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-map-pin" class="h-5 w-5 text-primary-500" />
                        <span>{{ $tenant->address }}</span>
                    </div>
                @endif

                @if($tenant?->phone)
                    <div class="flex items-center gap-1.5">
                        <x-filament::icon icon="heroicon-m-phone" class="h-5 w-5 text-primary-500" />
                        <span>{{ $tenant->phone }}</span>
                    </div>
                @endif
            </div>
        </div>


        
    </div>


    <!-- كود لتشغيل الساعة في لوحة التحكم -->
<script>
    function updateLiveClock() {
        const clockElement = document.getElementById('header-live-clock');
        if (!clockElement) return;

        setInterval(() => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            
            clockElement.textContent = `${hours}:${minutes}:${seconds}`;
        }, 1000);
    }

    // تشغيل الساعة أول ما تفتح الصفحة
    document.addEventListener('DOMContentLoaded', updateLiveClock);
    
    // للأمان مع Filament عشان لو الصفحة تحملت Livewire
    setTimeout(updateLiveClock, 500);
</script>



</x-filament-widgets::widget>
