<?php

namespace App\Filament\Resources\ValidityStatusResource\Pages;

use App\Filament\Resources\ValidityStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidityStatus extends EditRecord
{
    protected static string $resource = ValidityStatusResource::class;

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
