<?php

namespace App\Filament\Resources\StorageMediumResource\Pages;

use App\Filament\Resources\StorageMediumResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStorageMedium extends CreateRecord
{
    protected static string $resource = StorageMediumResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
