<?php

namespace App\Filament\Resources\DocumentarySubseriesResource\Pages;

use App\Filament\Resources\DocumentarySubseriesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentarySubseries extends CreateRecord
{
    protected static string $resource = DocumentarySubseriesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
