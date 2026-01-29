<?php

namespace App\Filament\Resources\DocumentPurposeResource\Pages;

use App\Filament\Resources\DocumentPurposeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentPurposes extends ListRecords
{
    protected static string $resource = DocumentPurposeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
