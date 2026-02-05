<?php

namespace App\Filament\Resources\InventoryRecordResource\Pages;

use App\Filament\Resources\InventoryRecordResource;
// use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Filament\Actions;
use Filament\Actions\Action;

class CreateInventoryRecord extends CreateRecord
{
    protected static string $resource = InventoryRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tutorial')
                ->label('¿Cómo funciona?')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->extraAttributes([
                    'data-tour' => 'help-button-inventory-create',
                    'onclick' => 'window.iniciarTour(); return false;',
                ]),
        ];
    }

    // protected function getCreateAnotherFormAction(): Actions\Action
    // {
    //     return parent::getCreateAnotherFormAction()
    //         ->hidden();
    // }

}
