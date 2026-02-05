<?php

namespace App\Filament\Resources\ActClassificationResource\Pages;

use App\Filament\Resources\ActClassificationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActClassification extends CreateRecord
{
    protected static string $resource = ActClassificationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
