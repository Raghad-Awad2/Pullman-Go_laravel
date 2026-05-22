<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Illuminate\Database\Eloquent\Builder;


use Filament\Resources\Components\Tab;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }




    // بيعمل التبويبات فوق الجدول
    public function getTabs(): array
    {
        return [
            
            'staff' => Tab::make('طاقم العمل') // تبويب للموظفين والآدمن
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', ['admin', 'owner', 'staff']))

                ->icon('heroicon-m-briefcase'),

            
            'passengers' => Tab::make('مستخدمين التطبيق') // التبويب اللي طلبته للمسافرين بس
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', 'passenger'))
                
                ->badge(fn () => \App\Models\User::where('role', 'passenger')->count()) // بيظهر عدد المسافرين فوق التبويب
                ->badgeColor('danger') // لون العدد أحمر مثل ما طلبت

                ->icon('heroicon-m-device-phone-mobile'),


            'all' => Tab::make('الكل'), // تبويب لكل المستخدمين
        ];
    }


}
