<?php

namespace App\Filament\Resources\AdministrativeActResource\Pages;

use App\Filament\Resources\AdministrativeActResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateAdministrativeAct extends CreateRecord
{
    protected static string $resource = AdministrativeActResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['folios'] = $this->countPdfPages($data['attachments'] ?? []);

        return $data;
    }

    protected function countPdfPages(array $files): ?int
    {
        $files = array_filter($files);
        if (empty($files)) {
            return null;
        }

        $totalPages = 0;
        foreach ($files as $file) {
            try {
                $path = storage_path('app/public/' . $file);
                if ($path && file_exists($path)) {
                    $totalPages += AdministrativeActResource::countPagesFromPdf($path);
                }
            } catch (\Throwable $e) {
                // Never block the save
            }
        }

        return $totalPages > 0 ? $totalPages : null;
    }

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
                    'data-tour' => 'help-button-acts-create',
                    'onclick' => 'window.iniciarTour(); return false;',
                ]),
        ];
    }
}
