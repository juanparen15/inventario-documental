<?php

namespace App\Filament\Resources\AdministrativeActResource\Pages;

use App\Filament\Imports\AdministrativeActImporter;
use App\Filament\Resources\AdministrativeActResource;
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
                ->extraAttributes([
                    'data-tour' => 'create-button-acts',
                ]),

            Action::make('downloadTemplate')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->extraAttributes([
                    'data-tour' => 'download-template-acts',
                ])
                ->action(function () {
                    $spreadsheet = AdministrativeActImporter::generateTemplate();
                    $writer = new Xlsx($spreadsheet);

                    $fileName = 'plantilla_actos_administrativos_' . date('Y-m-d') . '.xlsx';
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
                ->modalHeading('Importar Actos Administrativos')
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
                            ->body("Se importaron {$result['success']} actos administrativos correctamente.")
                            ->success()
                            ->send();
                    }
                }),

            ExportAction::make()
                ->label('Exportar')
                ->extraAttributes([
                    'data-tour' => 'export-button-acts',
                ])
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('actos_administrativos_' . date('Y-m-d'))
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                        ->withColumns([
                            Column::make('vigencia')->heading('Vigencia'),
                            Column::make('actClassification.name')->heading('Tipo de Acto'),
                            Column::make('filing_number')->heading('Consecutivo'),
                            Column::make('subject')->heading('Asunto'),
                            Column::make('organizationalUnit.name')->heading('Unidad Organizacional'),
                            Column::make('user.email')->heading('Usuario'),
                            Column::make('notes')->heading('Notas'),
                            Column::make('created_at')->heading('Fecha de Registro'),
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
