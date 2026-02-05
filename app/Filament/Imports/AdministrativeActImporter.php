<?php

namespace App\Filament\Imports;

use App\Models\AdministrativeAct;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class AdministrativeActImporter
{
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $errorCount = 0;

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Remove header row
        array_shift($rows);

        // Cache lookups
        $organizationalUnits = OrganizationalUnit::where('is_active', true)->pluck('id', 'name')->toArray();
        $users = User::pluck('id', 'email')->toArray();

        // Build series lookup: "code - name" => id
        $seriesLookup = DocumentarySeries::where('is_active', true)
            ->where('context', 'ccd')
            ->get()
            ->mapWithKeys(fn($s) => ["{$s->code} - {$s->name}" => $s->id, $s->name => $s->id])
            ->toArray();

        // Build subseries lookup: "code - name" => id
        $subseriesLookup = DocumentarySubseries::where('is_active', true)
            ->where('context', 'ccd')
            ->get()
            ->mapWithKeys(fn($s) => ["{$s->code} - {$s->name}" => $s->id, $s->name => $s->id])
            ->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $rowErrors = [];
            $data = [];

            // Organizational unit (required)
            $orgUnitName = trim($row[0] ?? '');
            if (empty($orgUnitName)) {
                $rowErrors[] = 'Unidad Organizacional es requerida';
            } elseif (!isset($organizationalUnits[$orgUnitName])) {
                $rowErrors[] = "Unidad Organizacional '{$orgUnitName}' no existe";
            } else {
                $data['organizational_unit_id'] = $organizationalUnits[$orgUnitName];
            }

            // Serie Documental (required)
            $seriesName = trim($row[1] ?? '');
            if (empty($seriesName)) {
                $rowErrors[] = 'Serie Documental es requerida';
            } elseif (!isset($seriesLookup[$seriesName])) {
                $rowErrors[] = "Serie Documental '{$seriesName}' no existe";
            } else {
                $data['documentary_series_id'] = $seriesLookup[$seriesName];
            }

            // Subserie Documental (optional)
            $subseriesName = trim($row[2] ?? '');
            if (!empty($subseriesName)) {
                if (!isset($subseriesLookup[$subseriesName])) {
                    $rowErrors[] = "Subserie Documental '{$subseriesName}' no existe";
                } else {
                    $data['documentary_subseries_id'] = $subseriesLookup[$subseriesName];
                }
            }

            // Vigencia (required)
            $vigencia = trim($row[3] ?? '');
            if (empty($vigencia)) {
                $rowErrors[] = 'Vigencia es requerida';
            } elseif (!is_numeric($vigencia) || (int) $vigencia < 2020 || (int) $vigencia > (int) date('Y')) {
                $rowErrors[] = "Vigencia '{$vigencia}' no es valida (debe ser entre 2020 y " . date('Y') . ")";
            } else {
                $data['vigencia'] = (int) $vigencia;
            }

            // Subject (required)
            $subject = trim($row[4] ?? '');
            if (empty($subject)) {
                $rowErrors[] = 'Asunto es requerido';
            } else {
                $data['subject'] = $subject;
            }

            // User by email (optional)
            $userEmail = trim($row[5] ?? '');
            if (!empty($userEmail)) {
                if (!isset($users[$userEmail])) {
                    $rowErrors[] = "Usuario con correo '{$userEmail}' no existe";
                } else {
                    $data['user_id'] = $users[$userEmail];
                }
            }

            // Notes (optional)
            $data['notes'] = trim($row[6] ?? '') ?: null;

            // Set created_by
            $data['created_by'] = Auth::id();

            if (!empty($rowErrors)) {
                $this->errors[$rowNumber] = $rowErrors;
                $this->errorCount++;
            } else {
                try {
                    AdministrativeAct::create($data);
                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->errors[$rowNumber] = ['Error al guardar: ' . $e->getMessage()];
                    $this->errorCount++;
                }
            }
        }

        return [
            'success' => $this->successCount,
            'errors' => $this->errorCount,
            'details' => $this->errors,
        ];
    }

    public static function generateTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Actos Administrativos');

        // Headers
        $headers = [
            'A1' => 'Unidad Organizacional *',
            'B1' => 'Serie Documental *',
            'C1' => 'Subserie Documental',
            'D1' => 'Vigencia * (Año)',
            'E1' => 'Asunto *',
            'F1' => 'Correo del Usuario',
            'G1' => 'Notas',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        // Set column widths
        $widths = ['A' => 30, 'B' => 25, 'C' => 30, 'D' => 15, 'E' => 50, 'F' => 30, 'G' => 40];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Add catalog sheets
        self::addCatalogSheet($spreadsheet, 'Unidades', OrganizationalUnit::where('is_active', true)->pluck('name')->toArray());

        $seriesValues = DocumentarySeries::where('is_active', true)
            ->where('context', 'ccd')
            ->orderBy('code')
            ->get()
            ->map(fn($s) => "{$s->code} - {$s->name}")
            ->toArray();
        self::addCatalogSheet($spreadsheet, 'Series', $seriesValues);

        $subseriesValues = DocumentarySubseries::where('is_active', true)
            ->where('context', 'ccd')
            ->orderBy('code')
            ->get()
            ->map(fn($s) => "{$s->code} - {$s->name}")
            ->toArray();
        self::addCatalogSheet($spreadsheet, 'Subseries', $subseriesValues);

        self::addCatalogSheet($spreadsheet, 'Usuarios', User::pluck('email')->toArray());

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected static function addCatalogSheet(Spreadsheet $spreadsheet, string $name, array $values): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($name);
        $sheet->setCellValue('A1', 'Valores Disponibles');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $row = 2;
        foreach ($values as $value) {
            $sheet->setCellValue('A' . $row, $value);
            $row++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
    }
}
