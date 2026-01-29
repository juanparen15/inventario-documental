<?php

namespace App\Filament\Resources\ProcessTypeResource\Pages;

use App\Filament\Resources\ProcessTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProcessTypes extends ListRecords
{
    protected static string $resource = ProcessTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
