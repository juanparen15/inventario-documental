<?php

namespace App\Filament\Resources\ValidityStatusResource\Pages;

use App\Filament\Resources\ValidityStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidityStatuses extends ListRecords
{
    protected static string $resource = ValidityStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
