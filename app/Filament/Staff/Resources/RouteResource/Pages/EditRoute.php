<?php

namespace App\Filament\Staff\Resources\RouteResource\Pages;

use App\Filament\Staff\Resources\RouteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoute extends EditRecord
{
    protected static string $resource = RouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
