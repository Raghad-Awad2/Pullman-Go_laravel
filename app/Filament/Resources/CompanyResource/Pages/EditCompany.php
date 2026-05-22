<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }

    // الدالة المسؤولة عن تحديث البيانات في الجداول المختلفة
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. تحديث بيانات الشركة الأساسية
        $record->update([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'address'  => $data['address'],
            'logo_url' => $data['logo_url'],
            'status'   => $data['status'],

            'owner_id' => $data['owner_id'], // تحديث المالك الجديد إذا تغير
        ]);



        // 2. تحديث المالك المختار لربطه بهذه الشركة
            $owner = \App\Models\User::find($data['owner_id']);
            if ($owner) {
                $owner->update([
                    'company_id' => $record->id,
                ]);
            }



        

        // 3. تحديث بيانات الموظف (Staff)
            // أولاً: فك ارتباط الموظف القديم (إذا تغير الموظف)
            User::where('company_id', $record->id)->where('role', 'staff')->update(['company_id' => null]);

            // ثانياً: ربط الموظف الجديد اللي اخترته
            if (!empty($data['staff_id'])) {
                $staff = User::find($data['staff_id']);
                if ($staff) {
                    $staff->update([
                        'company_id' => $record->id,
                    ]);
                }
            }

        return $record;
    }



    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}