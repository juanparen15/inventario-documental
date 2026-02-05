<?php

namespace App\Filament\Resources\ActClassificationResource\Pages;

use App\Filament\Resources\ActClassificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActClassification extends EditRecord
{
    protected static string $resource = ActClassificationResource::class;

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
