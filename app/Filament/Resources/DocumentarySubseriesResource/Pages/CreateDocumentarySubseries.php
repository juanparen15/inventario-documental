<?php

namespace App\Filament\Resources\DocumentarySubseriesResource\Pages;

use App\Filament\Resources\DocumentarySubseriesResource;
use App\Models\CcdEntry;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentarySubseries extends CreateRecord
{
    protected static string $resource = DocumentarySubseriesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $unitIds = $this->form->getRawState()['organizational_units'] ?? [];

        foreach ($unitIds as $unitId) {
            CcdEntry::firstOrCreate([
                'organizational_unit_id' => $unitId,
                'documentary_series_id' => $this->record->documentary_series_id,
                'documentary_subseries_id' => $this->record->id,
            ]);
        }
    }
}
