<?php

namespace App\Filament\Staff\Resources\AppNotificationResource\Pages;

use App\Filament\Staff\Resources\AppNotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppNotification extends EditRecord
{
    protected static string $resource = AppNotificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
