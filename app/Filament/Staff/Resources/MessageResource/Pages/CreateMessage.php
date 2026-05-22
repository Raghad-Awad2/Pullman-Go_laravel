<?php

namespace App\Filament\Staff\Resources\MessageResource\Pages;

use App\Filament\Staff\Resources\MessageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;


// مشان التنبيهات (الاشعارات)afterCreate
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;

    public function getHeading(): string
    {
        return 'إرسال رسالة جديدة';
    }



    
protected function getCreateFormAction(): \Filament\Actions\Action
{
    return parent::getCreateFormAction()
        ->label('إرسال الآن');
}



    
protected function mutateFormDataBeforeCreate(array $data): array
{
    // نضمن إن المرسل هو الشخص اللي مسجل دخول حالياً 100%
    $data['sender_id'] = auth()->id();
    
    // ونضمن إن الشركة هي شركة الشخص نفسه
    $data['company_id'] = auth()->user()->company_id;

    return $data;
}




protected function afterCreate(): void
{
    $message = $this->record;

    \Filament\Notifications\Notification::make()
        ->title('وصلتك رسالة جديدة')
        ->body("أرسل لك {$message->sender->name} رسالة جديدة")
        ->icon('heroicon-o-chat-bubble-left-right')
        ->color('success')
        ->actions([
            \Filament\Notifications\Actions\Action::make('view')
                ->label('عرض الرسالة')
                ->url(function () use ($message) {
                    // فحص ذكي وسريع بدون استخدام hasRole
                    $url = request()->url();

                    // إذا كانت الرسالة رايحة لصاحب الشركة (المدير)
                    // بنعرفه من رقم الشركة الموجود عند المستلم
                    if ($message->receiver->company_id && str_contains($url, 'company-owner')) {
                        return "/company-owner/{$message->receiver->company_id}/messages?activeTab=received";
                    }

                    // إذا كانت رايحة للموظف (Staff)
                    return "/staff/messages?activeTab=received";
                })
        ])
        ->sendToDatabase($message->receiver);
}


}
