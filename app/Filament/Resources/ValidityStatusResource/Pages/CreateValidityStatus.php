<?php

namespace App\Filament\Resources\ValidityStatusResource\Pages;

use App\Filament\Resources\ValidityStatusResource;
use Filament\Resources\Pages\CreateRecord;

class CreateValidityStatus extends CreateRecord
{
    protected static string $resource = ValidityStatusResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
