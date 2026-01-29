<?php

namespace App\Filament\Resources\ProcessTypeResource\Pages;

use App\Filament\Resources\ProcessTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProcessType extends EditRecord
{
    protected static string $resource = ProcessTypeResource::class;

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
