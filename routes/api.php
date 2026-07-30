<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// استخدمت هاد الكلاس مشان رابط انشاء حساب جديد register
use App\Http\Controllers\Api\AuthController;
//للحجز
use App\Http\Controllers\Api\TripController;
//كونترولر الحجز
use App\Http\Controllers\Api\BookingController;
//كونترولر العروض
use App\Http\Controllers\Api\OfferController;



// الروابط المحمية التي تتطلب تسجيل الدخول وتمرير التوكن (Sanctum Middleware)
Route::middleware('auth:sanctum')->group(function () {
    
    // الرابط الافتراضي لجلب بيانات المستخدم الحالي
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // الرابط الجديد: المسؤول عن تحديث بيانات الملف الشخصي (الاسم والهاتف) في الداتابيز
    Route::put('/update-profile', [AuthController::class, 'updateProfile']);
    //لرحلاتي لقادمة والسابقة
    Route::get('/user-trips', [BookingController::class, 'getUserTrips']);
    // مسار إلغاء الحجز الجديد
    Route::post('/cancel-booking', [TripController::class, 'cancelBooking']);

});


// رابط إنشاء حساب للمسافرين
Route::post('/register', [AuthController::class, 'register']);


// رابط التحقق من الرمز
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);


//رابط تسجيل الدخول الي رح يطلبو التطبيق عن طريق دالة loginUser الموجودة بملف ال APIs
Route::post('/login', [AuthController::class, 'login']);


// رابط الي رح يطلبه التطبيق مشان يبعث للسيرفر الايميل او رقم الهاتف (نسيت كلمة المرور)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);


// رابط لارسال الايميل ورمز التحقق الي دخلو المستخدم بنسيت كلمة المرور 
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);


//رابط لارسال كلمة المرور الجديدة من المستخدم 
Route::post('/reset-password', [AuthController::class, 'resetPassword']);








// رابط جلب المحافظات لعرضها في القائمة المنسدلة (Dropdown)
// وظيفته: يذهب لقاعدة البيانات ويحضر قائمة بكل المدن المتاحة (دمشق، حلب، حمص...).

Route::get('/cities', [TripController::class, 'getCities']);

// رابط لجلب الشركات اعتمادا عالمحافظات يلي اخترناها بالبحث 
// يأخذ "المدن" التي اختارها المستخدم (مثلاً: من حمص إلى اللاذقية) ويرسلها للباك آند. الباك آند يبحث في جدول الرحلات والمسارات ويعيد فقط الشركات التي لديها رحلات مطابقة لهذا المسار.
Route::get('/search-trips', [TripController::class, 'search']);


//للمواعيد
Route::get('/get-company-trips', [TripController::class, 'getCompanyTrips']);









// رابط الـ API  المسؤول عن حفظ وتأكيد الحجوزات والمقاعد بالكامل
Route::post('/store-booking', [BookingController::class, 'store']);

// رابط تاكيد الحجز بس بحماية لحتى ما بيقدر اي حدا مو مسجل دخولو يحجز 
//Route::middleware('auth:sanctum')->group(function () {
    // ضعي سطر الحجز هنا ليكون محمياً تماماً باسم المستخدم الحقيقي
//     Route::post('/store-booking', [BookingController::class, 'store']);
// });






//لجلب العروض
Route::get('/offers', [OfferController::class, 'index']);

//لتتواصل العروض مع الحجز وتجيب تواريخ واوقات العرض
Route::get('/offers/{id}/details', [OfferController::class, 'getOfferDetails']);


// رابط جلب المقاعد المحجوزة لرحلة معينة وتاريخ معين لتعرض باللون الرمادي في فلاتر
Route::get('/get-reserved-seats', [TripController::class, 'getReservedSeats']);


// رابط جلب رحلات المستخدم القادمة والسابقة (مفتوح ومباشر للتجريب بدون توكن)
// Route::get('/user-trips/{user_id}', [BookingController::class, 'getUserTrips']);