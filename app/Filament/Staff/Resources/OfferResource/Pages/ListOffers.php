<?php

namespace App\Filament\Staff\Resources\OfferResource\Pages;

use App\Filament\Staff\Resources\OfferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOffers extends ListRecords
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
{
    return [
        
        'active_offers' => \Filament\Resources\Components\Tab::make('العروض الحالية')
            ->icon('heroicon-m-check-circle')
            ->modifyQueryUsing(fn ($query) => $query->where('end_date', '>=', now()->startOfDay())),


        'old_offers' => \Filament\Resources\Components\Tab::make('العروض القديمة')
            ->icon('heroicon-m-clock')
            ->modifyQueryUsing(fn ($query) => $query->where('end_date', '<', now()->startOfDay())),
    ];
}
}
