<?php

namespace App\Filament\Resources\DocumentarySeriesResource\Pages;

use App\Filament\Resources\DocumentarySeriesResource;
use App\Models\CcdEntry;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentarySeries extends EditRecord
{
    protected static string $resource = DocumentarySeriesResource::class;

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

        // Remove entries for units no longer selected (only entries without subseries)
        CcdEntry::where('documentary_series_id', $this->record->id)
            ->whereNull('documentary_subseries_id')
            ->whereNotIn('organizational_unit_id', $unitIds)
            ->delete();

        // Add new entries
        foreach ($unitIds as $unitId) {
            CcdEntry::firstOrCreate([
                'organizational_unit_id' => $unitId,
                'documentary_series_id' => $this->record->id,
                'documentary_subseries_id' => null,
            ]);
        }
    }
}
