<?php

namespace App\Filament\Resources\DocumentPurposeResource\Pages;

use App\Filament\Resources\DocumentPurposeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentPurpose extends EditRecord
{
    protected static string $resource = DocumentPurposeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
