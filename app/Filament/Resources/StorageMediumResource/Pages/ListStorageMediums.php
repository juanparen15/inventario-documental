<?php

namespace App\Filament\Resources\StorageMediumResource\Pages;

use App\Filament\Resources\StorageMediumResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStorageMediums extends ListRecords
{
    protected static string $resource = StorageMediumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
