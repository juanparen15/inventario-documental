<?php

namespace App\Filament\Resources\StorageMediumResource\Pages;

use App\Filament\Resources\StorageMediumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStorageMedium extends EditRecord
{
    protected static string $resource = StorageMediumResource::class;

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
