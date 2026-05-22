<?php

namespace App\Filament\Staff\Resources\TripResource\Pages;


// عند انشاء رحلة هون بيعمل حلقة Loop يعني تخزين اسطر بعدد ايام عمل الرحلة الي اختارها


use App\Filament\Staff\Resources\TripResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonPeriod;


class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;



    //هي الدالة بتوقف عملية التخزين العادية وبتعمل تخزين مخصص

    // عشان الكود يعرف يلف على الأيامhandleRecordCreation
    protected function handleRecordCreation(array $data): Model
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $daysOfWeek = $data['days_of_week']; // مصفوفة الأيام المختارة [0, 2, 4]

        $firstRecord = null;

        // إنشاء فترة زمنية بين التاريخين
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            // نتحقق إذا كان يوم التاريخ الحالي موجود ضمن الأيام اللي اختارها الموظف
            if (in_array($date->dayOfWeek, $daysOfWeek)) {
                $instanceData = $data;
                $instanceData['trip_date'] = $date->format('Y-m-d');
                
                // تخزين سطر جديد لكل يوم
                $record = static::getModel()::create($instanceData);
                
                if (!$firstRecord) {
                    $firstRecord = $record; // بنحتفظ بأول سطر بس عشان فلامنت يكمل توجيه
                }
            }
        }

        return $firstRecord;
    }
}
