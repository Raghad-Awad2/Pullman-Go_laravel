<?php

namespace App\Filament\CompanyOwner\Resources\BusResource\Pages;

use App\Filament\CompanyOwner\Resources\BusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBus extends EditRecord
{
    protected static string $resource = BusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
