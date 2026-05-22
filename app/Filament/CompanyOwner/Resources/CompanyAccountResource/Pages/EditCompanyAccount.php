<?php

namespace App\Filament\CompanyOwner\Resources\CompanyAccountResource\Pages;

use App\Filament\CompanyOwner\Resources\CompanyAccountResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\Company;

// mount
// تروح للداتابيز، وتدور على الشركة اللي الـ owner_id تبعها بيساوي الـ ID تبع المستخدم اللي عامل دخول حالياً (auth()->id()).



// الدالة mount

class EditCompanyAccount extends EditRecord
{
    protected static string $resource = CompanyAccountResource::class;

    //     بيخلي الصفحة تفتح بيانات الشركة تلقائياً
    public function mount(int | string $record = null): void
    {
        
        $company = Company::where('owner_id', auth()->id())->first();

        if ($company) {
            // إذا لقينا الشركة، بنفتحها فوراً للتعديل
            parent::mount($company->id);
        } else {
            // إذا المستخدم ما عنده شركة، بنطلعه برا أو بنعطيه رسالة
            abort(403, 'ليس لديك شركة مسجلة حالياً');
        }
    }

    protected function getHeaderActions(): array
    {
        return []; // إخفاء زر الحذف من فوق
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}