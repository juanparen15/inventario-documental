<?php

namespace App\Filament\Resources\DocumentaryClassResource\Pages;

use App\Filament\Resources\DocumentaryClassResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentaryClasses extends ListRecords
{
    protected static string $resource = DocumentaryClassResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
