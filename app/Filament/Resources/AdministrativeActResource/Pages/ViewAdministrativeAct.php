<?php

namespace App\Filament\Resources\AdministrativeActResource\Pages;

use App\Filament\Resources\AdministrativeActResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAdministrativeAct extends ViewRecord
{
    protected static string $resource = AdministrativeActResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
