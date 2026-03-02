<?php

namespace App\Filament\Resources\AdministrativeActResource\Pages;

use App\Filament\Resources\AdministrativeActResource;
use App\Models\AdministrativeAct;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAdministrativeAct extends ViewRecord
{
    protected static string $resource = AdministrativeActResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        activity('inventory')
            ->performedOn($this->record)
            ->causedBy(auth()->user())
            ->event('viewed')
            ->withProperties(['ip' => request()->ip()])
            ->log('Registro consultado');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn() => $this->record->created_at->diffInDays(now()) <= 30),
        ];
    }
}
