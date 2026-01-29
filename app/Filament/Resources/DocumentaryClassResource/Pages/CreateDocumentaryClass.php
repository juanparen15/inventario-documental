<?php

namespace App\Filament\Resources\DocumentaryClassResource\Pages;

use App\Filament\Resources\DocumentaryClassResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentaryClass extends CreateRecord
{
    protected static string $resource = DocumentaryClassResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
