<?php

namespace App\Filament\CompanyOwner\Resources\CompanyAccountResource\Pages;

use App\Filament\CompanyOwner\Resources\CompanyAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanyAccounts extends ManageRecords
{
    protected static string $resource = CompanyAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
