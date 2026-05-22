<?php

namespace App\Filament\CompanyOwner\Resources\TripResource\Pages;

use App\Filament\CompanyOwner\Resources\TripResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            
        ];
    }
}
