<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. حماية وتدقيق البيانات (Validation)
        // هون بنتأكد إن المسافر بعت كل شي صح ومافي إيميل أو رقم مكرر
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|unique:users', // رقم فريد
            'password' => 'required|string|min:6',
        ]);


        // إذا في خطأ بالبيانات (مثلاً رقم مستخدم من قبل)
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'يوجد خطأ في البيانات المدخلة',
                'errors' => $validator->errors() // رح ترجع للموبايل عشان تظهرها للمستخدم
            ], 422); 
        }




// 2. توليد الرمز (4 أرقام عشوائية من السيرفر)rand 
        $otp = rand(1000, 9999);

// // هون احنا متاكدين انو دخل الايميل اما بنسيت كلمة المرور يمكن يكون مدخل ايميل او رقم الهاتف
// بتفحص المدخل، إذا لقيته "إيميل" بتبعث الرسالة

        // 3. إنشاء المستخدم (ولكن كحساب غير مفعل)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password), // تشفير الباسورد
            'role' => 'passenger', // إجبار الرتبة تكون مسافر

            'otp_code' => $otp, // خزنّا الرمز هون
            'otp_expires_at' => now()->addMinutes(10), // الرمز بموت بعد 10 دقايق
            'is_active' => false, // الحساب لسا مو مفعل
        ]);

        

        
// new OtpMail($otp) هون بوخذ الرمز وبرميه على ملف ال (OtpMail.php )على __construct($otp)
// 4. إرسال الإيميل الحقيقي
Mail::to($user->email)->send(new OtpMail(

    $otp, 

    'مرحباً بك في PULLMAN_GO - تفعيل الحساب', 
    'لقد طلبت رمز التحقق لإنشاء وتفعيل حسابك الجديد معنا.'
));





        // 5. الرد على تطبيق الفلاتر
        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء الحساب مبدئياً، يرجى تفقد بريدك الإلكتروني',
            'email' => $user->email // رجعنا الإيميل عشان الفلاتر يستخدمه بشاشة الـ OTP
        ], 201); 
    }





    




    public function verifyOtp(Request $request)
    {

        // 1. التأكد إن الموبايل بعت الإيميل والرمز
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'otp_code' => 'required|string|size:4', // الرمز لازم يكون 4 أرقام
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى إدخال الرمز بشكل صحيح',
            ], 422);
        }


        // 2. البحث عن المستخدم اللي عنده هاد الإيميل وهاد الرمز تحديداً
        $user = User::where('email', $request->email)
                    ->where('otp_code', $request->otp_code)
                    ->first();


        // 3. إذا ما لقيناه، يعني يا الرمز غلط يا الإيميل غلط
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'رمز التحقق غير صحيح، تأكد من الرمز وأعد المحاولة',
            ], 401);
        }


        // 4. التأكد إذا الرمز انتهت مدته (الـ 10 دقائق اللي حددناها)
        if (now()->isAfter($user->otp_expires_at)) {
            return response()->json([
                'status'  => false,
                'message' => 'انتهت صلاحية الرمز، يرجى طلب رمز جديد',
            ], 401);
        }


        // 5.
        // أولاً: تفعيل الحساب وتصفير خانات الرمز (عشان ما يستخدمه مرة تانية)
        $user->update([
            'is_active'      => true,
            'otp_code'       => null, 
            'otp_expires_at' => null,
        ]);


        // ثانياً: إنشاء توكن الدخول (المفتاح)
        $token = $user->createToken('MobileAppToken')->plainTextToken;


        // 6. الرد النهائي على الفلاتر
        return response()->json([
            'status'  => true,
            'message' => 'تم تفعيل حسابك بنجاح، أهلاً بك يا بطل',
            'token'   => $token, // هاد اللي رح يحفظه الـ Dio بالموبايل
            'user'    => $user    // معلومات المستخدم عشان أظهرها بالبروفايل
        ], 200);
    }










    public function login(Request $request)
    {

        // 1. تدقيق البيانات (لازم يبعث الحقل وكلمة السر)
        $validator = Validator::make($request->all(), [
            'login_field' => 'required', // ممكن يكون إيميل أو هاتف
            'password'    => 'required|string|min:6',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'يرجى إدخال البيانات بشكل صحيح',
                'errors'  => $validator->errors()
            ], 422);
        }


        // 2. المحرك الذكي: البحث عن المستخدم بالإيميل أو بالهاتف
        $user = User::where(function($query) use ($request) {
            $query->where('email', $request->login_field)
                  ->orWhere('phone', $request->login_field);
        })->first();

        // 3. التحقق من وجود المستخدم وصحة كلمة السر
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'خطأ في البريد الإلكتروني/رقم الهاتف أو كلمة المرور',
            ], 401);
        }


        // 4. خطوة الأمان: هل الحساب مفعل بالـ OTP؟
        if (!$user->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'حسابك غير مفعل، يرجى تفعيل الحساب أولاً',
                'needs_otp' => true, // هاي إشارة للفلاتر عشان يحوله لصفحة الـ OTP إذا بدك
                'email' => $user->email
            ], 403);
        }


        // 5. إنشاء توكن جديد (المفتاح السحري)
        $token = $user->createToken('MobileAppToken')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'تم تسجيل الدخول بنجاح، نورت التطبيق!',
            'token'   => $token,
            'user'    => $user
        ], 200);
    }




    




    public function forgotPassword(Request $request)
    {
        // 1. التأكد من وصول الحقل
        $request->validate([
            'login_field' => 'required'
        ]);


        // 2. البحث عن المستخدم بالإيميل أو رقم الهاتف
        $user = User::where('email', $request->login_field)
                    ->orWhere('phone', $request->login_field)
                    ->first();

        // 3. إذا المستخدم مو موجود أصلاً
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'عذراً، هذا الحساب غير موجود لدينا'
            ], 404);
        }


        // 4. توليد رمز OTP جديد وتحديث الداتابيز
        $otp = rand(1000, 9999);
        $user->otp_code = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();




        // 5. إرسال الرمز للإيميل (إذا كان المدخل إيميل)
        // رح نستخدم نفس ملف البريد اللي عملناه قبل
        if (filter_var($request->login_field, FILTER_VALIDATE_EMAIL)) {

            // new OtpMail($otp) هون بوخذ الرمز وبرميه على ملف ال (OtpMail.php )على __construct($otp)

            // 4. إرسال الإيميل الحقيقي
Mail::to($user->email)->send(new OtpMail(

     $otp, 
    'إعادة تعيين كلمة المرور - PULLMAN_GO', 
    'لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك.'

        ));

            
        } else {
                // إذا كان رقم هاتف، هون بنجهز مكان للـ SMS
                // حالياً بنتركها فاضية أو بنكتب Log عشان ما يضرب الكود
                \Log::info("رمز التحقق لرقم الهاتف " . $user->phone . " هو: " . $otp);
                
                // ملاحظة: الـ Log بيخليك تشوف الرمز بملفات السيرفر بدون ما تدفع حق رسالة SMS
        }


        // 6. الرد بنجاح
        return response()->json([
            'status'  => true,
            'message' => 'تم إرسال رمز التحقق بنجاح',
            'email'   => $user->email // بنرجعه ايميل تأكيد
        ], 200);

    }








    // دالة لفحص الرمز otp الي بعثو المستخدم بنسيت كلمة المرور 

    public function verifyResetOtp(Request $request)
{
    // 1. لازم يبعث الإيميل/الهاتف والرمز
    $request->validate([
        'login_field' => 'required',
        'otp_code'    => 'required|numeric',
    ]);



    // 2. البحث عن المستخدم اللي معه هاد الرمز وهاد الإيميل/الهاتف
    $user = User::where(function($query) use ($request) {
                $query->where('email', $request->login_field)
                      ->orWhere('phone', $request->login_field);
            })
            ->where('otp_code', $request->otp_code)
            ->first();

    // 3. إذا ما لقيناه أو الرمز غلط
    if (!$user) {
        return response()->json([
            'status'  => false,
            'message' => 'رمز التحقق غير صحيح، يرجى المحاولة مرة أخرى'
        ], 400);
    }



    // 4. فحص وقت انتهاء الرمز)
    if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
        return response()->json([
            'status'  => false,
            'message' => 'عذراً، هذا الرمز انتهت صلاحيته'
        ], 400);

    }


    // 5. إذا كل شي تمام
    return response()->json([
        'status'  => true,
        'message' => 'الرمز صحيح، يمكنك الآن تعيين كلمة مرور جديدة'
    ], 200);
}









    // دالة لاستقبال كلمة المرور الجديدة من المستخدم وهو المعالجة بتتم

    public function resetPassword(Request $request)
{
    // 1. التحقق من المدخلات
    $request->validate([
        'login_field' => 'required',
        'password'    => 'required|min:6', // تأكد إنها نفس شروط الفلاتر
    ]);

    // 2. البحث عن المستخدم (إيميل أو هاتف)
    $user = User::where('email', $request->login_field)
                ->orWhere('phone', $request->login_field)
                ->first();

    if (!$user) {
        return response()->json(['status' => false, 'message' => 'المستخدم غير موجود'], 404);
    }



    // 3. تحديث الباسورد وتصفير الـ OTP (للأمان عشان ما يُستخدم مرة ثانية)
    $user->password = Hash::make($request->password);
    $user->otp_code = null; // مسح الرمز القديم
    $user->otp_expires_at = null;
    
    $user->save();



    return response()->json([
        'status'  => true,
        'message' => 'تم تغيير كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن'
    ], 200);
}


}