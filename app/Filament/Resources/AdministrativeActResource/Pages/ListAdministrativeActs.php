<?php

namespace App\Filament\Resources\AdministrativeActResource\Pages;

use App\Filament\Imports\AdministrativeActImporter;
use App\Filament\Resources\AdministrativeActResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListAdministrativeActs extends ListRecords
{
    protected static string $resource = AdministrativeActResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tutorial')
                ->label('¿Cómo funciona?')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->extraAttributes([
                    'data-tour' => 'help-button-acts',
                    'onclick' => 'window.iniciarTour(); return false;',
                ]),

            Actions\CreateAction::make()
                ->label('Crear documento')
                ->extraAttributes([
                    'data-tour' => 'create-button-acts',
                ]),

            Action::make('downloadTemplate')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn () => auth()->user()?->hasRole('super_admin') ||
                                   auth()->user()?->organizationalUnit?->can_import)
                ->extraAttributes([
                    'data-tour' => 'download-template-acts',
                ])
                ->action(function () {
                    $spreadsheet = AdministrativeActImporter::generateTemplate(auth()->user());
                    $writer = new Xlsx($spreadsheet);

                    $fileName = 'plantilla_sistema_unificado_' . date('Y-m-d') . '.xlsx';
                    $tempFile = tempnam(sys_get_temp_dir(), 'template');
                    $writer->save($tempFile);

                    return response()->download($tempFile, $fileName, [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])->deleteFileAfterSend(true);
                }),

            Action::make('import')
                ->label('Importar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => auth()->user()?->hasRole('super_admin') ||
                                   auth()->user()?->organizationalUnit?->can_import)
                ->extraAttributes([
                    'data-tour' => 'import-button-acts',
                ])
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->disk('local')
                        ->directory('imports')
                        ->visibility('private')
                        ->required()
                        ->helperText('Formatos aceptados: .xlsx, .xls. Descargue la plantilla para ver el formato correcto.'),
                ])
                ->modalHeading('Importar al Sistema Unificado de Registro')
                ->modalDescription('Seleccione un archivo Excel con los datos a importar. Los campos marcados con * son obligatorios.')
                ->modalSubmitActionLabel('Importar')
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['file']);

                    $importer = new AdministrativeActImporter();
                    $result = $importer->import($filePath);

                    Storage::disk('local')->delete($data['file']);

                    if ($result['errors'] > 0) {
                        $errorMessage = $this->formatImportErrors($result['details']);

                        Notification::make()
                            ->title('Importación completada con errores')
                            ->body("Importados: {$result['success']} | Con errores: {$result['errors']}")
                            ->warning()
                            ->persistent()
                            ->send();

                        session()->flash('import_errors', $result['details']);
                        session()->flash('import_error_message', $errorMessage);
                    } else {
                        Notification::make()
                            ->title('Importación exitosa')
                            ->body("Se importaron {$result['success']} registros correctamente.")
                            ->success()
                            ->send();
                    }
                }),

            ExportAction::make()
                ->label('Exportar')
                ->authorize(fn() => true)
                ->extraAttributes([
                    'data-tour' => 'export-button-acts',
                ])
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename(function ($livewire) {
                            $tabLabels = [
                                'sin_pdf'    => 'sin-pdf',
                                'vencidos'   => 'vencidos',
                                'por_vencer' => 'por-vencer',
                                'esta_semana'=> 'esta-semana',
                            ];
                            $tab = $livewire->activeTab ?? null;
                            $suffix = ($tab && isset($tabLabels[$tab])) ? '_' . $tabLabels[$tab] : '';
                            return 'sistema_unificado_registro' . $suffix . '_' . date('Y-m-d');
                        })
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                        ->withColumns([
                            Column::make('vigencia')->heading('Vigencia'),
                            Column::make('actClassification.name')->heading('Tipo de Acto'),
                            Column::make('filing_number')->heading('Consecutivo'),
                            Column::make('subject')->heading('Asunto'),
                            Column::make('organizationalUnit.name')->heading('Unidad Organizacional'),
                            Column::make('user.name')->heading('Registrado por'),
                            Column::make('attachments')
                                ->heading('Estado PDF')
                                ->formatStateUsing(function ($state, $record) {
                                    $hasPdf = !empty($record->attachments) || !empty($record->confidential_attachments);
                                    if ($hasPdf) return 'Con PDF';
                                    $days = $record->created_at->diffInDays(now());
                                    return $days > 30 ? 'Vencido (sin PDF)' : "Sin PDF ({$days} días)";
                                }),
                            Column::make('late_upload_reason')->heading('Razón de retraso PDF'),
                            Column::make('folios')->heading('Folios'),
                            Column::make('notes')->heading('Notas'),
                            Column::make('created_at')->heading('Fecha de Registro'),
                        ]),
                ]),
        ];
    }

    public function getTabs(): array
    {
        // Cierre reutilizable: registros sin ningún PDF adjunto (regular ni confidencial).
        // IMPORTANTE: el parámetro debe llamarse $query para que Filament lo inyecte correctamente
        // (Tab::modifyQuery pasa ['query' => $builder] y evalúa por nombre de parámetro).
        $noPdf = fn(Builder $query): Builder => $query
            ->where(fn(Builder $inner) => $inner
                ->whereNull('attachments')
                ->orWhereRaw('JSON_LENGTH(attachments) = 0'))
            ->where(fn(Builder $inner) => $inner
                ->whereNull('confidential_attachments')
                ->orWhereRaw('JSON_LENGTH(confidential_attachments) = 0'));

        return [
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-document-text'),

            'sin_pdf' => Tab::make('Sin PDF')
                ->icon('heroicon-o-document-minus')
                ->badge(fn() => $noPdf(AdministrativeActResource::getEloquentQuery())->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing($noPdf),

            'vencidos' => Tab::make('Vencidos')
                ->icon('heroicon-o-exclamation-circle')
                ->badge(fn() => $noPdf(
                    AdministrativeActResource::getEloquentQuery()
                        ->where('created_at', '<=', now()->subDays(30))
                )->count() ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn(Builder $query) => $noPdf(
                    $query->where('created_at', '<=', now()->subDays(30))
                )),

            'por_vencer' => Tab::make('Por vencer')
                ->icon('heroicon-o-clock')
                ->badge(fn() => $noPdf(
                    AdministrativeActResource::getEloquentQuery()
                        ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                        ->where('created_at', '<=', now()->subDays(23)->endOfDay())
                )->count() ?: null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn(Builder $query) => $noPdf(
                    $query->where('created_at', '>=', now()->subDays(29)->startOfDay())
                          ->where('created_at', '<=', now()->subDays(23)->endOfDay())
                )),

            'esta_semana' => Tab::make('Esta semana')
                ->icon('heroicon-o-calendar-days')
                ->badge(fn() => AdministrativeActResource::getEloquentQuery()
                    ->where('created_at', '>=', now()->startOfWeek())
                    ->count() ?: null)
                ->badgeColor('info')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->where('created_at', '>=', now()->startOfWeek())),
        ];
    }

    protected function formatImportErrors(array $errors): string
    {
        $lines = [];

        foreach ($errors as $row => $rowErrors) {
            $errorList = implode(', ', $rowErrors);
            $lines[] = "Fila {$row}: {$errorList}";
        }

        return implode("\n", $lines);
    }
}
