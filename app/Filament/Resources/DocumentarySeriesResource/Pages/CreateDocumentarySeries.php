<?php

namespace App\Filament\Resources\DocumentarySeriesResource\Pages;

use App\Filament\Resources\DocumentarySeriesResource;
use App\Models\CcdEntry;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentarySeries extends CreateRecord
{
    protected static string $resource = DocumentarySeriesResource::class;

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
                'documentary_series_id' => $this->record->id,
                'documentary_subseries_id' => null,
            ]);
        }
    }
}
