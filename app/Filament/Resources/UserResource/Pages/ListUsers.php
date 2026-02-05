<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Imports\UserImporter;
use App\Filament\Resources\UserResource;
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

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Action::make('downloadTemplate')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $spreadsheet = UserImporter::generateTemplate();
                    $writer = new Xlsx($spreadsheet);

                    $fileName = 'plantilla_usuarios_' . date('Y-m-d') . '.xlsx';
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
                ->modalHeading('Importar Usuarios')
                ->modalDescription('Seleccione un archivo Excel con los datos a importar. Si no se proporciona contraseña, se asignará "password123" por defecto.')
                ->modalSubmitActionLabel('Importar')
                ->action(function (array $data) {
                    $filePath = Storage::disk('local')->path($data['file']);

                    $importer = new UserImporter();
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
                            ->body("Se importaron {$result['success']} usuarios correctamente.")
                            ->success()
                            ->send();
                    }
                }),

            ExportAction::make()
                ->label('Exportar')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('usuarios_' . date('Y-m-d'))
                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                        ->withColumns([
                            Column::make('name')->heading('Nombre'),
                            Column::make('last_name')->heading('Apellido'),
                            Column::make('email')->heading('Correo Electrónico'),
                            Column::make('phone')->heading('Teléfono'),
                            Column::make('document_number')->heading('Documento de Identidad'),
                            Column::make('organizationalUnit.name')->heading('Unidad Organizacional'),
                            Column::make('roles.name')->heading('Roles'),
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
