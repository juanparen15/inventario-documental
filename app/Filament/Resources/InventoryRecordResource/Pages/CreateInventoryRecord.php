<?php

namespace App\Filament\Resources\InventoryRecordResource\Pages;

use App\Filament\Resources\InventoryRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryRecord extends CreateRecord
{
    protected static string $resource = InventoryRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
