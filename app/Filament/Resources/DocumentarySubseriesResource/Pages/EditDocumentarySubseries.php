<?php

namespace App\Filament\Resources\DocumentarySubseriesResource\Pages;

use App\Filament\Resources\DocumentarySubseriesResource;
use App\Models\CcdEntry;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentarySubseries extends EditRecord
{
    protected static string $resource = DocumentarySubseriesResource::class;

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

    protected function afterSave(): void
    {
        $unitIds = $this->form->getRawState()['organizational_units'] ?? [];

        // Remove entries for units no longer selected
        CcdEntry::where('documentary_series_id', $this->record->documentary_series_id)
            ->where('documentary_subseries_id', $this->record->id)
            ->whereNotIn('organizational_unit_id', $unitIds)
            ->delete();

        // Add new entries
        foreach ($unitIds as $unitId) {
            CcdEntry::firstOrCreate([
                'organizational_unit_id' => $unitId,
                'documentary_series_id' => $this->record->documentary_series_id,
                'documentary_subseries_id' => $this->record->id,
            ]);
        }
    }
}
