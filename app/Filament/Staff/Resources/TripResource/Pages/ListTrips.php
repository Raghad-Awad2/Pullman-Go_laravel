<?php

namespace App\Filament\Staff\Resources\TripResource\Pages;

use App\Filament\Staff\Resources\TripResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTrips extends ListRecords
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }





    public function getTabs(): array
{
    return [
        'all' => Tab::make('كل الرحلات')
            ->icon('heroicon-m-list-bullet'),
            

        'active' => Tab::make('الغير نشطة')
            ->icon('heroicon-o-pause-circle')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
    ];
}
}
