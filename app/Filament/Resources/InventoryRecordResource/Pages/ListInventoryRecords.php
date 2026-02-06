<?php

namespace App\Filament\Resources\InventoryRecordResource\Pages;

use App\Filament\Imports\InventoryRecordImporter;
use App\Filament\Resources\InventoryRecordResource;
use App\Models\InventoryRecord;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListInventoryRecords extends ListRecords
{
    protected static string $resource = InventoryRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tutorial')
                ->label('¿Cómo funciona?')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->extraAttributes([
                    'data-tour' => 'help-button-inventory',
                    'onclick' => 'window.iniciarTour(); return false;',
                ]),

            Actions\CreateAction::make()
                ->extraAttributes([
                    'data-tour' => 'create-button-inventory',
                ]),

            Action::make('downloadTemplate')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn() => auth()->user()?->hasRole('super_admin'))
                ->extraAttributes([
                    'data-tour' => 'download-template-inventory',
                ])
                ->action(function () {
                    $spreadsheet = InventoryRecordImporter::generateTemplate();
                    $writer = new Xlsx($spreadsheet);

                    $fileName = 'plantilla_registros_inventario_' . date('Y-m-d') . '.xlsx';
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
                ->visible(fn() => auth()->user()?->hasRole('super_admin'))
                ->extraAttributes([
                    'data-tour' => 'import-button-inventory',
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
                ->modalHeading('Importar Registros de Inventario')
                ->modalDescription('Seleccione un archivo Excel con los datos a importar. Los campos marcados con * son obligatorios.')
                ->modalSubmitActionLabel('Importar')
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['file']);

                    $importer = new InventoryRecordImporter();
                    $result = $importer->import($filePath);

                    // Delete the uploaded file
                    Storage::disk('local')->delete($data['file']);

                    if ($result['errors'] > 0) {
                        $errorMessage = $this->formatImportErrors($result['details']);

                        Notification::make()
                            ->title('Importación completada con errores')
                            ->body("Importados: {$result['success']} | Con errores: {$result['errors']}")
                            ->warning()
                            ->persistent()
                            ->send();

                        // Store errors in session for modal display
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
                ->visible(fn() => auth()->user()?->hasRole('super_admin'))
                ->extraAttributes([
                    'data-tour' => 'export-button-inventory',
                ])
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('registros_inventario_' . date('Y-m-d'))
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                        ->withColumns([
                            Column::make('reference_code')->heading('Código de Referencia'),
                            Column::make('organizationalUnit.name')->heading('Unidad Organizacional'),
                            Column::make('documentarySeries.name')->heading('Serie Documental'),
                            Column::make('documentarySubseries.name')->heading('Subserie Documental'),
                            Column::make('documentaryClass.name')->heading('Clase Documental'),
                            Column::make('documentType.name')->heading('Tipo de Documento'),
                            Column::make('title')->heading('Título'),
                            Column::make('description')->heading('Descripción'),
                            Column::make('start_date')->heading('Fecha Inicial'),
                            Column::make('end_date')->heading('Fecha Final'),
                            Column::make('box')->heading('Caja'),
                            Column::make('folder')->heading('Carpeta'),
                            Column::make('volume')->heading('Tomo'),
                            Column::make('folios')->heading('Folios'),
                            Column::make('storageMedium.name')->heading('Soporte'),
                            Column::make('documentPurpose.name')->heading('Objeto'),
                            Column::make('processType.name')->heading('Tipo de Proceso'),
                            Column::make('validityStatus.name')->heading('Estado de Vigencia'),
                            Column::make('priorityLevel.name')->heading('Nivel de Prioridad'),
                            Column::make('project.name')->heading('Proyecto'),
                            Column::make('notes')->heading('Notas'),
                            Column::make('created_at')->heading('Fecha de Creación'),
                        ]),
                ]),
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
