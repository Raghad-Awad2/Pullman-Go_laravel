<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingSeat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id'         => 'required|integer',
            'trip_id'         => 'required|integer',
            'seats_count'     => 'required|integer',
            'travel_date'     => 'required|string',
            'total_price'     => 'required|numeric',
            'payment_method'  => 'required|string',
            'notes'           => 'nullable|string', 
            'passengers'      => 'required|array',
            'passengers.*.seat_number'    => 'required|string',
            'passengers.*.passenger_name' => 'required|string',
        ]);

        DB::beginTransaction();

        try {
            // 🛡️ فحص الأمان باستخدام المسار الكامل للموديل لمنع خطأ التعريف
            $alreadyReserved = \App\Models\BookingSeat::join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
                ->where('bookings.trip_id', $request->trip_id)
                ->where('bookings.travel_date', $request->travel_date)
                ->where('bookings.payment_status', '!=', 'canceled')
                ->pluck('booking_seats.seat_number')
                ->toArray();

            // التحقق من المقاعد المطلوبة الآن
            foreach ($request->passengers as $passenger) {
                if (in_array($passenger['seat_number'], $alreadyReserved)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'عذراً، المقعد رقم ' . $passenger['seat_number'] . ' تم حجزه للتو من قبل مستخدم آخر!'
                    ], 422);
                }
            }

            // 1️⃣ الآن يتم إنشاء الحجز الأساسي بأمان
            $booking = Booking::create([
                'user_id'        => auth('sanctum')->id() ?? ($request->user_id == 0 ? 1 : $request->user_id),
                'trip_id'        => $request->trip_id,
                'seats_count'    => $request->seats_count,
                'travel_date'    => $request->travel_date,
                'total_price'    => $request->total_price,
                'payment_status' => 'paid', 
                'payment_method' => $request->payment_method,
            ]);

            foreach ($request->passengers as $passenger) {
                BookingSeat::create([
                    'booking_id'     => $booking->id, 
                    'seat_number'    => $passenger['seat_number'],
                    'passenger_name' => $passenger['passenger_name'],
                ]);
            }

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
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'فشلت عملية الحجز: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗺️ دالة جلب وتصنيف رحلات المستخدم  (تعتمد على التوكن)
     */
    public function getUserTrips(Request $request)
    {
        // 1️⃣ التعديل الجذري هنا: جلب الـ id الخاص بالمستخدم الحالي المسجل دخوله عبر التوكن تلقائياً
        $userId = $request->user()->id;

        // 2️⃣ جلب حجوزات المستخدم مع تحميل العلاقات بشكل كامل (باقي الكود كما هو تماماً دون حذف)
        $bookings = Booking::where('user_id', $userId)
            ->with(['trip.company', 'trip.route.departureCity', 'trip.route.arrivalCity', 'seats'])
            ->get();

        // جلب تاريخ اليوم الحالي بصيغة نصية مجردة (Y-m-d) باستخدام كربون لضمان دقة التوقيت على الـ IP
        $todayDate = Carbon::now()->format('Y-m-d');

        $upcomingBookings = [];
        $pastBookings = [];

        foreach ($bookings as $booking) {
            // التحقق من أن حجز الرحلة وبيانات المسار متوفرة لمنع أي خطأ أو انهيار (500)
            if ($booking->trip && $booking->trip->route) {
                
                // تحويل التاريخ المخزن في قاعدة البيانات إلى صيغة نصية مجردة ونظيفة
                $rawDate = $booking->travel_date;
                $dateString = ($rawDate instanceof \DateTimeInterface) ? $rawDate->format('Y-m-d') : trim($rawDate);
                
                // جلب الوقت المجدول من جدول الرحلات
                $timeString = $booking->trip->scheduled_time;

                // بناء الهيكل المنظم للبيانات الموجهة لواجهات تطبيق فلاتر
                $bookingData = [
                    'booking_id'       => $booking->id,
                    'reference_number' => $booking->reference_number ?? ('PGO-' . $booking->id),
                    'total_price'      => $booking->total_price,
                    'seats_count'      => $booking->seats_count,
                    'travel_date'      => $dateString,
                    'scheduled_time'   => Carbon::parse($timeString)->format('g:i أ'),
                    
                    'company_name'     => $booking->trip->company->name ?? 'شركة نقليات',
                    'from_city'        => $booking->trip->route->departureCity->name ?? 'غير محدد',
                    'to_city'          => $booking->trip->route->arrivalCity->name ?? 'غير محدد',
                    
                    'passengers'       => $booking->seats->map(function ($seat) {
                        return [
                            'passenger_name' => $seat->passenger_name,
                            'seat_number'    => $seat->seat_number,
                        ];
                    })->toArray(),
                ];

                // مقارنة نصية مباشرة وآمنة تماماً بناءً على تاريخ اليوم النظيف
                if ($dateString >= $todayDate) {
                    $upcomingBookings[] = $bookingData;
                } else {
                    $pastBookings[] = $bookingData;
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب وتصنيف رحلات المستخدم بنجاح',
            'data' => [
                'upcoming' => $upcomingBookings,
                'past'     => $pastBookings
            ]
        ], 200);
    }
}