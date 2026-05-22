<?php

namespace App\Filament\Staff\Resources\BookingResource\Pages;

use App\Filament\Staff\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;
}
