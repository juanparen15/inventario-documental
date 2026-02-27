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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class AdministrativeActImporter
{
    protected array $errors      = [];
    protected int   $successCount = 0;
    protected int   $errorCount   = 0;

    /**
     * Estructura del archivo Excel generado por generateTemplate():
     *
     *   Fila 1 — Banner de instrucciones (ignorar al importar)
     *   Fila 2 — Encabezados de columnas  (ignorar al importar)
     *   Filas 3-102 — Datos
     *
     *   A = Dependencia            (pre-llenada, bloqueada)
     *   B = Serie Documental *     (obligatoria, desplegable)
     *   C = Subserie Documental    (opcional, desplegable)
     *   D = Año                    (pre-llenado, bloqueado)
     *   E = Asunto / Descripción * (obligatorio, texto libre)
     *   F = Observaciones          (opcional, texto libre)
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet   = $spreadsheet->getActiveSheet();
        $rows        = $worksheet->toArray();

        // Saltar fila 1 (instrucciones) y fila 2 (encabezados)
        array_shift($rows);
        array_shift($rows);

        // Cachés de búsqueda
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
            // +3 porque se saltaron 2 filas antes y las filas en Excel empiezan en 1
            $rowNumber = $index + 3;

            // Saltar filas vacías
            if (empty(array_filter($row))) {
                continue;
            }

            $rowErrors = [];
            $data      = [];

            // Columna A — Dependencia (obligatoria)
            $orgUnitName = trim($row[0] ?? '');
            if (empty($orgUnitName)) {
                $rowErrors[] = 'Columna A (Dependencia): es requerida';
            } elseif (! isset($organizationalUnits[$orgUnitName])) {
                $rowErrors[] = "Columna A (Dependencia): '{$orgUnitName}' no existe en el sistema";
            } else {
                $data['organizational_unit_id'] = $organizationalUnits[$orgUnitName];
            }

            // Columna B — Serie Documental (obligatoria)
            $seriesName = trim($row[1] ?? '');
            if (empty($seriesName)) {
                $rowErrors[] = 'Columna B (Serie Documental): es requerida';
            } elseif (! isset($seriesLookup[$seriesName])) {
                $rowErrors[] = "Columna B (Serie Documental): '{$seriesName}' no encontrada — use el desplegable";
            } else {
                $data['documentary_series_id'] = $seriesLookup[$seriesName];
            }

            // Columna C — Subserie Documental (opcional)
            $subseriesName = trim($row[2] ?? '');
            if (! empty($subseriesName)) {
                if (! isset($subseriesLookup[$subseriesName])) {
                    $rowErrors[] = "Columna C (Subserie Documental): '{$subseriesName}' no encontrada — use el desplegable";
                } else {
                    $data['documentary_subseries_id'] = $subseriesLookup[$subseriesName];
                }
            }

            // Columna D — Año (obligatorio)
            $vigencia = trim($row[3] ?? '');
            if (empty($vigencia)) {
                $rowErrors[] = 'Columna D (Año): es requerido';
            } elseif (! is_numeric($vigencia) || (int) $vigencia < 2020 || (int) $vigencia > (int) date('Y')) {
                $rowErrors[] = "Columna D (Año): '{$vigencia}' no es válido (debe estar entre 2020 y " . date('Y') . ')';
            } else {
                $data['vigencia'] = (int) $vigencia;
            }

            // Columna E — Asunto (obligatorio)
            $subject = trim($row[4] ?? '');
            if (empty($subject)) {
                $rowErrors[] = 'Columna E (Asunto): es requerido';
            } else {
                $data['subject'] = $subject;
            }

            // Columna F — Observaciones (opcional)
            $data['notes'] = trim($row[5] ?? '') ?: null;

            // Asignar al usuario autenticado
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
     * Genera la plantilla Excel pre-diligenciada para el usuario.
     *
     * Estructura:
     *   Fila 1 — Banner de instrucciones (fondo ámbar)
     *   Fila 2 — Encabezados
     *   Filas 3-102 — Datos (100 filas)
     *   Hojas adicionales: "Lista de Series" y "Lista de Subseries" (catálogos visibles)
     *
     * Columnas:
     *   A = Dependencia        (pre-llenada + bloqueada)
     *   B = Serie Documental * (obligatoria, desplegable)
     *   C = Subserie           (opcional, desplegable)
     *   D = Año                (pre-llenado + bloqueado)
     *   E = Asunto *           (obligatorio, texto libre)
     *   F = Observaciones      (opcional, texto libre)
     */
    public static function generateTemplate(User $user): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registro de Documentos');

        $unitName    = $user->organizationalUnit?->name ?? '';
        $currentYear = (int) date('Y');

        // ── Fila 1: Banner de instrucciones ─────────────────────────────────
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1',
            'INSTRUCCIONES: Diligencie los campos desde la fila 3. ' .
            'Los campos marcados con * son obligatorios. ' .
            'Las columnas "Dependencia" y "Año" ya están diligenciadas y NO se deben modificar. ' .
            'Use el desplegable (▼) en "Serie" y "Subserie" para seleccionar el valor correcto.'
        );
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '92400E']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D97706']]],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(50);

        // ── Fila 2: Encabezados ──────────────────────────────────────────────
        $headers = [
            'A2' => "Dependencia\n(no modificar)",
            'B2' => "Serie Documental *\n(seleccionar de la lista ▼)",
            'C2' => "Subserie Documental\n(opcional – seleccionar ▼)",
            'D2' => "Año\n(no modificar)",
            'E2' => "Asunto / Descripción del Documento *",
            'F2' => "Observaciones\n(opcional)",
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $baseHeaderStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '93C5FD']]],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER,
                            'horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A2:F2')->applyFromArray($baseHeaderStyle);

        // Las columnas pre-llenadas/bloqueadas con tono más oscuro
        $lockedHeaderStyle = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E2A5E']]];
        $sheet->getStyle('A2')->applyFromArray($lockedHeaderStyle);
        $sheet->getStyle('D2')->applyFromArray($lockedHeaderStyle);

        $sheet->getRowDimension(2)->setRowHeight(46);

        // ── Anchos de columna ────────────────────────────────────────────────
        $widths = ['A' => 34, 'B' => 32, 'C' => 36, 'D' => 10, 'E' => 58, 'F' => 40];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // ── Filas de datos 3–102 ─────────────────────────────────────────────
        // Estilos
        $lockedCellStyle = [
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFDBFE']]],
            'font'      => ['color' => ['rgb' => '1E3A8A'], 'italic' => true],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $editableCellStyle = [
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        for ($row = 3; $row <= 102; $row++) {
            if ($unitName !== '') {
                $sheet->setCellValue("A{$row}", $unitName);
            }
            $sheet->setCellValue("D{$row}", $currentYear);
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        $sheet->getStyle('A3:A102')->applyFromArray($lockedCellStyle);
        $sheet->getStyle('D3:D102')->applyFromArray($lockedCellStyle);
        $sheet->getStyle('B3:C102')->applyFromArray($editableCellStyle);
        $sheet->getStyle('E3:F102')->applyFromArray($editableCellStyle);

        // Congelar filas 1 y 2 para que siempre sean visibles al hacer scroll
        $sheet->freezePane('A3');

        // ── Catálogos (hojas visibles al final) ──────────────────────────────
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

        self::addCatalogSheet($spreadsheet, 'Lista de Series', $seriesValues);
        self::addCatalogSheet($spreadsheet, 'Lista de Subseries', $subseriesValues);

        // ── Desplegables celda por celda (único método fiable en PhpSpreadsheet 1.x) ──
        $seriesMax = count($seriesValues) + 1;   // fila final del catálogo (fila 1 = encabezado)
        $subMax    = count($subseriesValues) + 1;

        for ($row = 3; $row <= 102; $row++) {
            // Columna B — Serie (obligatoria)
            if (! empty($seriesValues)) {
                $v = $sheet->getCell("B{$row}")->getDataValidation();
                $v->setType(DataValidation::TYPE_LIST);
                $v->setErrorStyle(DataValidation::STYLE_STOP);
                $v->setAllowBlank(false);
                $v->setShowDropDown(false);   // false = mostrar flecha ▼
                $v->setShowErrorMessage(true);
                $v->setShowInputMessage(true);
                $v->setPromptTitle('Serie Documental');
                $v->setPrompt('Haga clic en la flecha ▼ para ver y seleccionar la serie.');
                $v->setErrorTitle('Valor no válido');
                $v->setError('Seleccione una serie de la lista. No escriba valores que no estén en el catálogo.');
                $v->setFormula1("'Lista de Series'!\$A\$2:\$A\${$seriesMax}");
            }

            // Columna C — Subserie (opcional)
            if (! empty($subseriesValues)) {
                $v = $sheet->getCell("C{$row}")->getDataValidation();
                $v->setType(DataValidation::TYPE_LIST);
                $v->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $v->setAllowBlank(true);
                $v->setShowDropDown(false);
                $v->setShowErrorMessage(true);
                $v->setShowInputMessage(true);
                $v->setPromptTitle('Subserie Documental');
                $v->setPrompt('Opcional. Si aplica, haga clic en ▼ para seleccionar. Puede dejarlo en blanco.');
                $v->setErrorTitle('Valor no encontrado');
                $v->setError('El valor ingresado no está en el catálogo. Puede dejarlo en blanco si no aplica.');
                $v->setFormula1("'Lista de Subseries'!\$A\$2:\$A\${$subMax}");
            }
        }

        // ── Protección: bloquear A y D, dejar libres B, C, E, F ─────────────
        $unlocked = ['protection' => ['locked' => Protection::PROTECTION_UNPROTECTED]];
        $sheet->getStyle('B3:B102')->applyFromArray($unlocked);
        $sheet->getStyle('C3:C102')->applyFromArray($unlocked);
        $sheet->getStyle('E3:E102')->applyFromArray($unlocked);
        $sheet->getStyle('F3:F102')->applyFromArray($unlocked);

        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setSelectLockedCells(false);

        // ── Volver a la hoja principal ───────────────────────────────────────
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Crea una hoja de catálogo visible con los valores disponibles.
     */
    protected static function addCatalogSheet(Spreadsheet $spreadsheet, string $title, array $values): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);

        $sheet->setCellValue('A1', 'Valores disponibles');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
        ]);

        $row = 2;
        foreach ($values as $value) {
            $sheet->setCellValue("A{$row}", $value);
            $row++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
    }
}
