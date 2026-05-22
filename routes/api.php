<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// استخدمت هاد الكلاس مشان رابط انشاء حساب جديد register
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


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