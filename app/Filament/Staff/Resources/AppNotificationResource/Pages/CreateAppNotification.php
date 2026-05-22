<?php

namespace App\Filament\Staff\Resources\AppNotificationResource\Pages;

use App\Filament\Staff\Resources\AppNotificationResource;
use Filament\Resources\Pages\CreateRecord;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class CreateAppNotification extends CreateRecord
{
// مررت البيانات مرتين للمستخدم 
// withNotification مررت البيانات لشريط الاشعارات ع الموبايل
// withData مررت البيانات للمستخدم على صفحةالاشعارات بالتطبيق


    protected static string $resource = AppNotificationResource::class;

    // رقم 2: حقن البيانات تلقائياً 🌟
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // سحب الآيدي تبع الموظف اللي مسجل دخولو حالياً
        $data['sender_id'] = auth()->id();
        
        // سحب الآيدي تبع شركة هاد الموظف
        $data['company_id'] = auth()->user()->company_id;
        
        return $data;
    }




    protected function afterCreate(): void
{
    $record = $this->record;
    $user = auth()->user();
    

    $company = $user->company;
    // هاد$company->name  وهاد $company->logo_url مفاتيح مشان الفايربيز
    $name = $company->name ?? 'PULLMAN GO';
    $logo_url = $company->logo_url ?? ''; 

    // تحويل المسار لرابط كامل (عشان الفلاتر يشوف الصورة)
    // الكود بفوت على ملف شركة الموظف الي بعث الاشعار وبوخذ حقل الlogo_url عن طريق المسار storage
    $fullLogoUrl = $logo_url ? asset('storage/' . $logo_url) : asset('images/default-logo.png');

    // شريط الاشعارات try
    try {
        $messaging = app('firebase.messaging');
        // topic العنوان 
        // passenger القناة الموجودة بالفايربيز

// withTarget('topic', 'passenger') هي القناة او العنوان الي رح يبعثه اللارافيل 
// رح يبعثه على القناة الي موجودة بالفايربيز
        $message = CloudMessage::withTarget('topic', 'passenger')
        
        // Notification::create هون حددت العنوان ومحتوى الرسالة مشان يظهرو بشريط الاشعارات بالموبايل من فوق
        // withImageUrl تقوم باظهار لوغو الشركة في شريط الاشعارات بشكل مصغر او مكبر حب نوع الموبايل
            ->withNotification(Notification::create($record->title, $record->content)
                ->withImageUrl($fullLogoUrl))
            

// 🌟هي الداتا اللي رح يستقبلها الفلتر عشان يرسم التصميم اللي بدك ياه!
// هون مررت البيانات كمتغيرات للفلتر (مصفوفة)

            ->withData([
                'notification_id' => (string) $record->id,
                'name'            => $name,         // اسم الشركة من حقل name
                'logo_url'        => $fullLogoUrl,  // رابط اللوغو من حقل logo_url
                'title'           => $record->title,
                'content'         => $record->content,
                'created_at'      => $record->created_at->format('Y-m-d H:i A'),
// لما المستخدم يضغط على الإشعار وهو بشريط الإشعارات، لا تفتح المتصفح ولا تفتح تطبيق ثاني، افتح تطبيق الفلتر تبعي
                'click_action'    => 'FLUTTER_NOTIFICATION_CLICK',
            ]);

        $messaging->send($message);
        
    } catch (\Exception $e) {
        \Log::error("Firebase Error: " . $e->getMessage());
    }

}



    // رسالة تظهر للموظف بعد نجاح الإرسال
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إرسال الإشعار لجميع المستخدمين بنجاح!';
    }
    

    // توجيه الموظف للجدول بعد الإرسال بدل ما يضل بصفحة الإضافة
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}