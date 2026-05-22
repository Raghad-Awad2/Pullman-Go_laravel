<?php

namespace App\Filament\CompanyOwner\Resources\RoutePriceResource\Pages;

use App\Filament\CompanyOwner\Resources\RoutePriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

use Filament\Resources\Components\Tab;

use Illuminate\Database\Eloquent\Builder;

class ManageRoutePrices extends ManageRecords
{
    protected static string $resource = RoutePriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }



    public function getTabs(): array
{
    return [
        
        'base_prices' => Tab::make('صفحة الأسعار')
            ->icon('heroicon-m-currency-dollar'),
    ];
}
}
