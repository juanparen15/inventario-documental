<?php

namespace App\Filament\Resources\DocumentaryClassResource\Pages;

use App\Filament\Resources\DocumentaryClassResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentaryClass extends EditRecord
{
    protected static string $resource = DocumentaryClassResource::class;

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
