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
    public function getCompanyTrips(Request $request)
    {
        try {
            // البحث عن المواعيد لشركة ومسار معينين
            $query = \App\Models\Trip::where('company_id', $request->company_id)
                ->where('route_id', $request->route_id);

            // --- التعديل الأهم: فلترة المواعيد حسب اليوم المختار من التطبيق ---
            if ($request->has('day_index') && $request->day_index !== null) {
                // التأكد أن موعد الرحلة هذا متاح في هذا اليوم (0 = الأحد، 1 = الاثنين، إلخ)
                $query->whereJsonContains('days_of_week', (string)$request->day_index);
            }

            $trips = $query->get(['id', 'scheduled_time', 'days_of_week']);

            return response()->json([
                'status' => true,
                'data' => $trips
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'error' => $e->getMessage()], 500);
        }
    }
}