<?php

namespace App\Filament\Resources\ProcessTypeResource\Pages;

use App\Filament\Resources\ProcessTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProcessType extends CreateRecord
{
    protected static string $resource = ProcessTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
