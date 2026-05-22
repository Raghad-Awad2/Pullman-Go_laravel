<?php

namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    // الدالة السحرية لتوزيع البيانات على الجداول
    protected function handleRecordCreation(array $data): Model
    {
        // 1. حفظ الشركة في جدول companies
        $company = static::getModel()::create([
            'name'     => $data['name'],
            'phone'    => $data['phone'],
            'address'  => $data['address'],
            'logo_url' => $data['logo_url'] ?? null,
            'status'   => $data['status'],
            'admin_id' => auth()->id(), // ربطها بالأدمن اللي أنشأها

            'owner_id' => $data['owner_id'], // هاد هو الحقل البديل عن owner_name

        ]);




        // // 2. إنشاء حساب "صاحب الشركة" في جدول users
        // User::create([
        //     'name'       => $data['owner_name'],
        //     'email'      => $data['owner_email'],
        //     'password'   => Hash::make($data['owner_password']),
        //     'role'       => 'owner', 
        //     'company_id' => $company->id, // ربطه بالشركة الجديدة
        // ]);

        // 2. تحديث المالك المختار لربطه بهذه الشركة (بدل إنشاء مستخدم جديد)
        $owner = User::find($data['owner_id']);
        if ($owner) {
            $owner->update([
                'company_id' => $company->id,
            ]);
        }





        // 3. ربط الموظف المختار بالشركة
        if (!empty($data['staff_id'])) {
            $staff = User::find($data['staff_id']);
            if ($staff) {
                $staff->update([
                    'company_id' => $company->id,
                ]);
            }
        }

        return $company;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}