<?php

namespace App\Filament\Resources\InventoryRecordResource\Pages;

use App\Filament\Resources\InventoryRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryRecords extends ListRecords
{
    protected static string $resource = InventoryRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
