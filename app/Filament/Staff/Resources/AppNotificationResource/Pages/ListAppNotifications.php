<?php

namespace App\Filament\Staff\Resources\AppNotificationResource\Pages;

use App\Filament\Staff\Resources\AppNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppNotifications extends ListRecords
{
    protected static string $resource = AppNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
