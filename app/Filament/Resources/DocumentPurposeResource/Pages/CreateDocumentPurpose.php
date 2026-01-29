<?php

namespace App\Filament\Resources\DocumentPurposeResource\Pages;

use App\Filament\Resources\DocumentPurposeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentPurpose extends CreateRecord
{
    protected static string $resource = DocumentPurposeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
