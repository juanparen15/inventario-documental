<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportAction extends Action
{
    use CanCustomizeProcess;

    protected string $importerClass;
    protected string $modelLabel;

    public static function getDefaultName(): ?string
    {
        return 'import';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Importar')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->modalHeading(fn () => 'Importar ' . $this->modelLabel)
            ->modalDescription('Seleccione un archivo Excel (.xlsx) con los datos a importar. Puede descargar la plantilla para ver el formato correcto.')
            ->modalSubmitActionLabel('Importar')
            ->form([
                FileUpload::make('file')
                    ->label('Archivo Excel')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->directory('imports')
                    ->required()
                    ->helperText('Formatos aceptados: .xlsx, .xls'),
            ])
            ->action(function (array $data) {
                $filePath = Storage::disk('public')->path($data['file']);

                $importer = new $this->importerClass();
                $result = $importer->import($filePath);

                // Delete the uploaded file
                Storage::disk('public')->delete($data['file']);

                if ($result['errors'] > 0) {
                    $errorDetails = $this->formatErrors($result['details']);

                    Notification::make()
                        ->title('Importación completada con errores')
                        ->body("Se importaron {$result['success']} registros. {$result['errors']} registros con errores.")
                        ->warning()
                        ->persistent()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('ver_errores')
                                ->label('Ver Errores')
                                ->url('#')
                                ->extraAttributes([
                                    'onclick' => 'alert(' . json_encode($errorDetails) . '); return false;',
                                ]),
                        ])
                        ->send();

                    // Store errors in session for display
                    session()->flash('import_errors', $result['details']);
                } else {
                    Notification::make()
                        ->title('Importación exitosa')
                        ->body("Se importaron {$result['success']} registros correctamente.")
                        ->success()
                        ->send();
                }
            })
            ->modalFooterActions(fn () => [
                Action::make('downloadTemplate')
                    ->label('Descargar Plantilla')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $spreadsheet = $this->importerClass::generateTemplate();
                        $writer = new Xlsx($spreadsheet);

                        $fileName = 'plantilla_' . str_replace(' ', '_', strtolower($this->modelLabel)) . '_' . date('Y-m-d') . '.xlsx';
                        $tempFile = tempnam(sys_get_temp_dir(), 'template');
                        $writer->save($tempFile);

                        return response()->download($tempFile, $fileName, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    }),
                $this->getModalSubmitAction(),
                $this->getModalCancelAction(),
            ]);
    }

    public function importer(string $importerClass): static
    {
        $this->importerClass = $importerClass;

        return $this;
    }

    public function modelLabel(string $label): static
    {
        $this->modelLabel = $label;

        return $this;
    }

    protected function formatErrors(array $errors): string
    {
        $lines = ["Errores de importación:\n"];

        foreach ($errors as $row => $rowErrors) {
            $lines[] = "Fila {$row}:";
            foreach ($rowErrors as $error) {
                $lines[] = "  - {$error}";
            }
        }

        return implode("\n", $lines);
    }
}
