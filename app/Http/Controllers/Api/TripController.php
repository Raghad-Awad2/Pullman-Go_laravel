<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route; 
use App\Models\City; 
use Illuminate\Http\Request;

class TripController extends Controller
{
    /**
     * جلب قائمة المدن/المحافظات المفعلة من قاعدة البيانات
     */
    public function getCities()
    {
        try {
            // جلب المحافظات التي حالتها "مفعلة" (is_active = 1)
            $cities = City::where('is_active', 1)->get(['id', 'name']);
            
            return response()->json([
                'status' => true,
                'data' => $cities
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء جلب المدن',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * البحث عن الرحلات المتاحة بناءً على المدن واليوم المختار
     */
    public function search(Request $request)
    {
        // التحقق من صحة المدخلات (معرف مدينة الانطلاق والوصول)
        $request->validate([
            'from_id' => 'required|exists:cities,id', 
            'to_id' => 'required|exists:cities,id',   
        ]);

        try {
            // 1. بناء الاستعلام الأساسي للمسارات وجلب العلاقات (الشركة والمدن)
            $query = Route::with(['company', 'departureCity', 'arrivalCity'])
                ->where('departure_city_id', $request->from_id)
                ->where('arrival_city_id', $request->to_id);

            // 2. الفلترة حسب اليوم: نتأكد إن المسار فيه رحلات (trips) بتشتغل بهذا اليوم
            if ($request->has('day_index') && $request->day_index !== null) {
                $query->whereHas('trips', function($q) use ($request) {
                    // البحث داخل حقل JSON الخاص بأيام الأسبوع
                    $q->whereJsonContains('days_of_week', (string)$request->day_index);
                });
            }

            $trips = $query->get();

            // قاموس لترجمة الميزات من الإنجليزية للعربية لعرضها في التطبيق
            $featuresTranslation = [
                'wifi' => 'واي فاي مجاني',
                'ac' => 'تكييف هواء',
                'comfortable_seats' => 'مقاعد مريحة',
                'usb' => 'شواحن USB',
                'wc' => 'دورة مياه داخلية',
                'screen' => 'شاشات عرض',
                'water' => 'توزيع مياه ضيافة',
                'snacks' => 'وجبات خفيفة',
                'coffee' => 'مشروبات ساخنة',
                'gps' => 'تتبع مباشر (GPS)',
                'camera' => 'كاميرات مراقبة',
                'insurance' => 'تأمين سفر شامل',
                'luggage' => 'خدمة الأمتعة',
                'extra_bag' => 'وزن إضافي',
            ];

            // تحويل ميزات كل شركة إلى نصوص عربية
            foreach ($trips as $trip) {
                if ($trip->company && is_array($trip->company->features)) {
                    $translatedFeatures = [];
                    foreach ($trip->company->features as $feature) {
                        $translatedFeatures[] = $featuresTranslation[$feature] ?? $feature;
                    }
                    $trip->company->features = $translatedFeatures;
                }
            }

            return response()->json([
                'status' => true,
                'count' => $trips->count(),
                'data' => $trips
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'حدث خطأ أثناء البحث',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب تفاصيل شركة معينة
     */
    public function getCompanyDetails($id)
    {
        try {
            $company = \App\Models\Company::find($id);
            if (!$company) {
                return response()->json(['status' => false, 'message' => 'الشركة غير موجودة'], 404);
            }
            return response()->json(['status' => true, 'data' => $company], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * جلب المواعيد التفصيلية المتاحة فقط في اليوم المختار
     */
   /**
     * جلب المواعيد التفصيلية المتاحة فقط في اليوم المختار مع بيانات المسار والكراجات
     */
    public function getCompanyTrips(Request $request)
{
    try {
        // 💡 جلب بيانات المسار والباص المرتبط بالرحلة ديناميكياً بناءً على حقول قاعدة البيانات الحالية
        $query = \App\Models\Trip::with([
            'route:id,departure_address,arrival_address',
            'bus:id,total_seats,bus_numbernnn' // 👈 تم التأكيد على مطابقة حقل قاعدة البيانات بدقة
        ])
        ->where('company_id', $request->company_id)
        ->where('route_id', $request->route_id);

        // --- الفلترة حسب اليوم المختار من التطبيق ---
        if ($request->has('day_index') && $request->day_index !== null) {
            $query->whereJsonContains('days_of_week', (string)$request->day_index);
        }

        // إضافة bus_id للمصفوفة لكي تعمل علاقة الـ BelongsTo بشكل صحيح
        $trips = $query->get(['id', 'route_id', 'bus_id', 'scheduled_time', 'days_of_week']);

        return response()->json([
            'status' => true,
            'data' => $trips
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
    }
}



    // داخل TripController.php

    public function getReservedSeats(Request $request)
{
    // نحتاج للـ trip_id والتاريخ للتأكد من حجز المقاعد لهذا الموعد بالضبط
    $reservedSeats = \App\Models\BookingSeat::where('trip_id', $request->trip_id)
        ->where('travel_date', $request->travel_date)
        ->pluck('seat_number')
        ->toArray();

    return response()->json([
        'status' => true,
        'reserved_seats' => $reservedSeats
    ]);
}
}