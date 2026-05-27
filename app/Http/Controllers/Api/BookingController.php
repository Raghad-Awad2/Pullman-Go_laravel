<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingSeat;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        // 1. التحقق من البيانات القادمة من تطبيق الفلاتر (تم تعديل travel_date إلى string ليتوافق مع الـ varchar بالداتابيز)
        $request->validate([
            'user_id'         => 'required|integer',
            'trip_id'         => 'required|integer',
            'seats_count'     => 'required|integer',
            'travel_date'     => 'required|string', // 👈 تم التعديل هنا ليتوافق مع نوع العمود varchar(191)
            'total_price'     => 'required|numeric',
            'payment_method'  => 'required|string',
            'notes'           => 'nullable|string', 
            'passengers'      => 'required|array',
            'passengers.*.seat_number'    => 'required|string',
            'passengers.*.passenger_name' => 'required|string',
        ]);

        // 2. استخدام Transaction لحماية البيانات
        DB::beginTransaction();

        try {
            // 3. تخزين بيانات الحجز الرئيسي في جدول bookings (تمت إزالة حقل notes لأنه غير موجود بأعمدة الجدول)
           // 3. تخزين بيانات الحجز الرئيسي في جدول bookings
$booking = Booking::create([
    // 'user_id'        => $request->user_id == 0 ? 1 : $request->user_id, // هون ثبتا ال id تبع المستخدم  الاول يلي هو ادمن النظام لحتى اربط واجهات تسجيل الدخول بعدلها
    'user_id'        => auth('sanctum')->id() ?? ($request->user_id == 0 ? 1 : $request->user_id),
    'trip_id'        => $request->trip_id,
    'seats_count'    => $request->seats_count,
    'travel_date'    => $request->travel_date,
    'total_price'    => $request->total_price,
    'payment_status' => 'paid', 
    'payment_method' => $request->payment_method,
]);

            // 4. تخزين تفاصيل مقاعد الركاب في جدول booking_seats المرتبط
            foreach ($request->passengers as $passenger) {
                BookingSeat::create([
                    'booking_id'     => $booking->id, 
                    'seat_number'    => $passenger['seat_number'],
                    'passenger_name' => $passenger['passenger_name'],
                ]);
            }

            // اعتماد الحفظ النهائي والتثبيت في قاعدة البيانات
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'تم حفظ الحجز وتأكيد المقاعد بنجاح',
                'data' => [
                    'booking_id'       => $booking->id,
                    'reference_number' => $booking->reference_number 
                ]
            ], 201);

        } catch (\Exception $e) {
            // التراجع فوراً وإلغاء أي تعديل جزئي حدث بالجداول في حال حدوث خطأ
            DB::rollback();
            return response()->json([
                'status' => false,
                // 👈 قمنا بدمج رسالة الخطأ التقنية هنا لكي يطبعها الفلاتر وتعرفي سبب الرفض بوضوح تام
                'message' => 'فشلت عملية الحجز: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}