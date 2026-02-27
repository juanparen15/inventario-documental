<?php

namespace App\Filament\Imports;

use App\Models\AdministrativeAct;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AdministrativeActImporter
{
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $errorCount = 0;

    /**
     * Columns in the new template:
     *   A = Unidad Organizacional  (pre-filled, locked)
     *   B = Serie Documental       (required, dropdown)
     *   C = Subserie Documental    (optional, dropdown)
     *   D = Vigencia               (pre-filled, locked)
     *   E = Asunto                 (required, free text)
     *   F = Notas                  (optional, free text)
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet   = $spreadsheet->getActiveSheet();
        $rows        = $worksheet->toArray();

        // Remove header row
        array_shift($rows);

        // Cache lookups
        $organizationalUnits = OrganizationalUnit::where('is_active', true)->pluck('id', 'name')->toArray();

        $seriesLookup = DocumentarySeries::where('is_active', true)
            ->where('context', 'ccd')
            ->get()
            ->mapWithKeys(fn ($s) => ["{$s->code} - {$s->name}" => $s->id, $s->name => $s->id])
            ->toArray();

        $subseriesLookup = DocumentarySubseries::where('is_active', true)
            ->where('context', 'ccd')
            ->get()
            ->mapWithKeys(fn ($s) => ["{$s->code} - {$s->name}" => $s->id, $s->name => $s->id])
            ->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $rowErrors = [];
            $data      = [];

            // Column A — Unidad Organizacional (required)
            $orgUnitName = trim($row[0] ?? '');
            if (empty($orgUnitName)) {
                $rowErrors[] = 'Columna A (Unidad Organizacional): es requerida';
            } elseif (! isset($organizationalUnits[$orgUnitName])) {
                $rowErrors[] = "Columna A (Unidad Organizacional): '{$orgUnitName}' no existe";
            } else {
                $data['organizational_unit_id'] = $organizationalUnits[$orgUnitName];
            }

            // Column B — Serie Documental (required)
            $seriesName = trim($row[1] ?? '');
            if (empty($seriesName)) {
                $rowErrors[] = 'Columna B (Serie Documental): es requerida';
            } elseif (! isset($seriesLookup[$seriesName])) {
                $rowErrors[] = "Columna B (Serie Documental): '{$seriesName}' no encontrada";
            } else {
                $data['documentary_series_id'] = $seriesLookup[$seriesName];
            }

            // Column C — Subserie Documental (optional)
            $subseriesName = trim($row[2] ?? '');
            if (! empty($subseriesName)) {
                if (! isset($subseriesLookup[$subseriesName])) {
                    $rowErrors[] = "Columna C (Subserie Documental): '{$subseriesName}' no encontrada";
                } else {
                    $data['documentary_subseries_id'] = $subseriesLookup[$subseriesName];
                }
            }

            // Column D — Vigencia (required)
            $vigencia = trim($row[3] ?? '');
            if (empty($vigencia)) {
                $rowErrors[] = 'Columna D (Vigencia): es requerida';
            } elseif (! is_numeric($vigencia) || (int) $vigencia < 2020 || (int) $vigencia > (int) date('Y')) {
                $rowErrors[] = "Columna D (Vigencia): '{$vigencia}' no es válida (debe ser entre 2020 y " . date('Y') . ')';
            } else {
                $data['vigencia'] = (int) $vigencia;
            }

            // Column E — Asunto (required)
            $subject = trim($row[4] ?? '');
            if (empty($subject)) {
                $rowErrors[] = 'Columna E (Asunto): es requerido';
            } else {
                $data['subject'] = $subject;
            }

            // Column F — Notas (optional)
            $data['notes'] = trim($row[5] ?? '') ?: null;

            // Assign to the authenticated user
            $data['user_id']    = Auth::id();
            $data['created_by'] = Auth::id();

            if (! empty($rowErrors)) {
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
            'errors'  => $this->errorCount,
            'details' => $this->errors,
        ];
    }

    /**
     * Generate a smart Excel template pre-filled with the user's unit and the
     * current year.  Columns A and D are locked; B and C have dropdowns.
     *
     * Layout (6 columns):
     *   A = Unidad Organizacional  (pre-filled + locked)
     *   B = Serie Documental *     (required, dropdown)
     *   C = Subserie Documental    (optional, dropdown)
     *   D = Vigencia *             (pre-filled + locked)
     *   E = Asunto *               (required, free text)
     *   F = Notas                  (optional, free text)
     */
    public static function generateTemplate(User $user): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Actos Administrativos');

        $unitName    = $user->organizationalUnit?->name ?? '';
        $currentYear = (int) date('Y');

        // ── Headers ─────────────────────────────────────────────────────────
        $headers = [
            'A1' => 'Unidad Organizacional',
            'B1' => 'Serie Documental *',
            'C1' => 'Subserie Documental (Opcional)',
            'D1' => 'Vigencia',
            'E1' => 'Asunto *',
            'F1' => 'Notas (Opcional)',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Header styles
        $headerStyle = [
            'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['wrapText' => true],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Locked-column headers get a slightly darker tint
        $lockedHeaderStyle = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '312E81']],
        ];
        $sheet->getStyle('A1')->applyFromArray($lockedHeaderStyle);
        $sheet->getStyle('D1')->applyFromArray($lockedHeaderStyle);

        // ── Column widths ────────────────────────────────────────────────────
        $widths = ['A' => 32, 'B' => 30, 'C' => 35, 'D' => 12, 'E' => 55, 'F' => 40];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getRowDimension(1)->setRowHeight(36);

        // ── Pre-fill rows 2–101 ──────────────────────────────────────────────
        $lockedFill = [
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDE9FE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C4B5FD']]],
            'font'    => ['color' => ['rgb' => '4C1D95'], 'italic' => true],
        ];
        $editableFill = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ];

        for ($row = 2; $row <= 101; $row++) {
            if ($unitName !== '') {
                $sheet->setCellValue("A{$row}", $unitName);
            }
            $sheet->setCellValue("D{$row}", $currentYear);
        }

        // Style locked columns (A and D)
        $sheet->getStyle('A2:A101')->applyFromArray($lockedFill);
        $sheet->getStyle('D2:D101')->applyFromArray($lockedFill);

        // Style editable columns (B, C, E, F)
        $sheet->getStyle('B2:C101')->applyFromArray($editableFill);
        $sheet->getStyle('E2:F101')->applyFromArray($editableFill);

        // ── Catalog sheets ───────────────────────────────────────────────────
        $seriesValues = DocumentarySeries::where('is_active', true)
            ->where('context', 'ccd')
            ->orderBy('code')
            ->get()
            ->map(fn ($s) => "{$s->code} - {$s->name}")
            ->toArray();

        $subseriesValues = DocumentarySubseries::where('is_active', true)
            ->where('context', 'ccd')
            ->orderBy('code')
            ->get()
            ->map(fn ($s) => "{$s->code} - {$s->name}")
            ->toArray();

        $seriesSheet    = self::addHiddenCatalogSheet($spreadsheet, '_Series', $seriesValues);
        $subseriesSheet = self::addHiddenCatalogSheet($spreadsheet, '_Subseries', $subseriesValues);

        // ── Data validation — Column B (Serie) ──────────────────────────────
        if (! empty($seriesValues)) {
            $seriesMax        = count($seriesValues) + 1;
            $seriesValidation = $sheet->getCell('B2')->getDataValidation();
            $seriesValidation->setType(DataValidation::TYPE_LIST);
            $seriesValidation->setErrorStyle(DataValidation::STYLE_STOP);
            $seriesValidation->setAllowBlank(false);
            $seriesValidation->setShowDropDown(false);
            $seriesValidation->setShowErrorMessage(true);
            $seriesValidation->setErrorTitle('Valor inválido');
            $seriesValidation->setError('Seleccione una serie de la lista desplegable.');
            $seriesValidation->setFormula1("'_Series'!\$A\$2:\$A\${$seriesMax}");
            $seriesValidation->setSqref('B2:B101');
        }

        // ── Data validation — Column C (Subserie) ────────────────────────────
        if (! empty($subseriesValues)) {
            $subMax              = count($subseriesValues) + 1;
            $subValidation       = $sheet->getCell('C2')->getDataValidation();
            $subValidation->setType(DataValidation::TYPE_LIST);
            $subValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $subValidation->setAllowBlank(true);
            $subValidation->setShowDropDown(false);
            $subValidation->setShowErrorMessage(true);
            $subValidation->setErrorTitle('Valor no encontrado');
            $subValidation->setError('El valor ingresado no está en la lista. Puede dejarlo en blanco si no aplica.');
            $subValidation->setFormula1("'_Subseries'!\$A\$2:\$A\${$subMax}");
            $subValidation->setSqref('C2:C101');
        }

        // ── Sheet protection (lock A and D, unlock B, C, E, F) ───────────────
        // By default all cells are locked — unlock only the editable ones
        $unlocked = ['protection' => ['locked' => Protection::PROTECTION_UNPROTECTED]];
        $sheet->getStyle('B2:B101')->applyFromArray($unlocked);
        $sheet->getStyle('C2:C101')->applyFromArray($unlocked);
        $sheet->getStyle('E2:E101')->applyFromArray($unlocked);
        $sheet->getStyle('F2:F101')->applyFromArray($unlocked);

        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setSelectLockedCells(false);

        // ── Restore active sheet to main ────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected static function addHiddenCatalogSheet(Spreadsheet $spreadsheet, string $name, array $values): Worksheet
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($name);
        $sheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        $sheet->setCellValue('A1', 'Valores');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $row = 2;
        foreach ($values as $value) {
            $sheet->setCellValue("A{$row}", $value);
            $row++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);

        return $sheet;
    }
}
