<?php

namespace App\Filament\Resources\ActClassificationResource\Pages;

use App\Filament\Resources\ActClassificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListActClassifications extends ListRecords
{
    protected static string $resource = ActClassificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
