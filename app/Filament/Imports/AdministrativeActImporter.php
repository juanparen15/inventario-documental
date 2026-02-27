<?php

namespace App\Filament\Imports;

use App\Models\AdministrativeAct;
use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AdministrativeActImporter
{
    protected array $errors       = [];
    protected int   $successCount = 0;
    protected int   $errorCount   = 0;

    /**
     * Estructura del archivo Excel generado por generateTemplate():
     *
     *   Fila 1 — Banner de instrucciones (ignorar al importar)
     *   Fila 2 — Encabezados de columnas  (ignorar al importar)
     *   Filas 3-102 — Datos
     *
     *   A = Dependencia            (pre-llenada)
     *   B = Serie Documental *     (obligatoria, desplegable filtrado por unidad)
     *   C = Subserie Documental *  (obligatoria, desplegable dependiente de B)
     *   D = Año                    (pre-llenado)
     *   E = Asunto / Descripción * (obligatorio, texto libre)
     *   F = Observaciones          (opcional, texto libre)
     *   G = [columna oculta – fórmula helper para el desplegable dependiente]
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet   = $spreadsheet->getActiveSheet();
        $rows        = $worksheet->toArray();

        // Saltar fila 1 (instrucciones) y fila 2 (encabezados)
        array_shift($rows);
        array_shift($rows);

        // ── Cachés de búsqueda ────────────────────────────────────────────
        $organizationalUnits = OrganizationalUnit::where('is_active', true)
            ->pluck('id', 'name')
            ->toArray();

        // Construir lookups desde ccd_entries para validar que la serie/subserie
        // pertenece a la unidad indicada en cada fila.
        //
        // seriesAllowedByUnit[unitId][displayName] = seriesId
        // subseriesAllowedBySeries[seriesId][displayName] = subseriesId
        $ccdAll = CcdEntry::with([
            'documentarySeries:id,code,name,is_active',
            'documentarySubseries:id,code,name,is_active',
        ])->get();

        $seriesAllowedByUnit      = [];
        $subseriesAllowedBySeries = [];

        foreach ($ccdAll as $entry) {
            $s = $entry->documentarySeries;
            if (! $s || ! $s->is_active) {
                continue;
            }

            $unitId   = $entry->organizational_unit_id;
            $sDisplay = "{$s->code} - {$s->name}";

            $seriesAllowedByUnit[$unitId][$sDisplay] = $s->id;
            $seriesAllowedByUnit[$unitId][$s->name]  = $s->id;

            $sub = $entry->documentarySubseries;
            if ($sub && $sub->is_active) {
                $subDisplay = "{$sub->code} - {$sub->name}";
                $subseriesAllowedBySeries[$s->id][$subDisplay] = $sub->id;
                $subseriesAllowedBySeries[$s->id][$sub->name]  = $sub->id;
            }
        }

        foreach ($rows as $index => $row) {
            // +3 porque se saltaron 2 filas y las filas en Excel empiezan en 1
            $rowNumber = $index + 3;

            if (empty(array_filter($row))) {
                continue;
            }

            $rowErrors = [];
            $data      = [];
            $unitId    = null;
            $seriesId  = null;

            // Columna A — Dependencia (obligatoria)
            $orgUnitName = trim($row[0] ?? '');
            if (empty($orgUnitName)) {
                $rowErrors[] = 'Columna A (Dependencia): es requerida';
            } elseif (! isset($organizationalUnits[$orgUnitName])) {
                $rowErrors[] = "Columna A (Dependencia): '{$orgUnitName}' no existe en el sistema";
            } else {
                $unitId                          = $organizationalUnits[$orgUnitName];
                $data['organizational_unit_id']  = $unitId;
            }

            // Columna B — Serie Documental (obligatoria, validar contra ccd_entries)
            $seriesName    = trim($row[1] ?? '');
            $unitSeriesMap = $unitId ? ($seriesAllowedByUnit[$unitId] ?? []) : [];

            if (empty($seriesName)) {
                $rowErrors[] = 'Columna B (Serie Documental): es requerida';
            } elseif ($unitId && ! isset($unitSeriesMap[$seriesName])) {
                $rowErrors[] = "Columna B (Serie Documental): '{$seriesName}' no está asignada a esta dependencia";
            } elseif ($unitId) {
                $seriesId                      = $unitSeriesMap[$seriesName];
                $data['documentary_series_id'] = $seriesId;
            }

            // Columna C — Subserie Documental (obligatoria, validar contra la serie)
            $subseriesName = trim($row[2] ?? '');
            $seriesSubMap  = $seriesId ? ($subseriesAllowedBySeries[$seriesId] ?? []) : [];

            if (empty($subseriesName)) {
                $rowErrors[] = 'Columna C (Subserie Documental): es requerida';
            } elseif ($seriesId && ! isset($seriesSubMap[$subseriesName])) {
                $rowErrors[] = "Columna C (Subserie Documental): '{$subseriesName}' no pertenece a la serie seleccionada";
            } elseif ($seriesId) {
                $data['documentary_subseries_id'] = $seriesSubMap[$subseriesName];
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
     * Características:
     * - Series filtradas por la unidad organizacional del usuario (ccd_entries)
     * - Desplegable de Subserie dependiente: al seleccionar una Serie en col B,
     *   la col C muestra únicamente las subseries de esa serie asignadas a la unidad.
     * - Ambos campos son obligatorios.
     * - La col G (oculta) contiene la fórmula helper que hace funcionar el INDIRECT.
     */
    public static function generateTemplate(User $user): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registro de Documentos');

        $unitName    = $user->organizationalUnit?->name ?? '';
        $unitId      = $user->organizational_unit_id;
        $currentYear = (int) date('Y');

        // ── Obtener series y subseries de la unidad via ccd_entries ──────
        if ($unitId) {
            $ccdEntries = CcdEntry::with([
                'documentarySeries:id,code,name,is_active',
                'documentarySubseries:id,code,name,is_active',
            ])->where('organizational_unit_id', $unitId)->get();

            $seriesList = $ccdEntries
                ->filter(fn ($e) => $e->documentarySeries?->is_active)
                ->pluck('documentarySeries')
                ->unique('id')
                ->sortBy('code')
                ->values();

            $subseriesBySeriesId = $ccdEntries
                ->filter(fn ($e) => $e->documentarySeries && $e->documentarySubseries?->is_active)
                ->groupBy('documentary_series_id')
                ->map(fn ($group) => $group
                    ->pluck('documentarySubseries')
                    ->unique('id')
                    ->sortBy('code')
                    ->values());
        } else {
            // Fallback para super_admin sin unidad asignada: mostrar todas las series activas
            $seriesList = DocumentarySeries::where('is_active', true)
                ->where('context', 'ccd')
                ->orderBy('code')
                ->get();

            $subseriesBySeriesId = DocumentarySubseries::where('is_active', true)
                ->where('context', 'ccd')
                ->get()
                ->groupBy('documentary_series_id');
        }

        $seriesValues = $seriesList->map(fn ($s) => "{$s->code} - {$s->name}")->toArray();
        $seriesCount  = count($seriesValues);

        // ── Fila 1: Banner de instrucciones ──────────────────────────────
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1',
            'INSTRUCCIONES: Diligencie desde la fila 3. Los campos con * son obligatorios. ' .
            '"Dependencia" y "Año" ya están diligenciados — no los modifique. ' .
            'Primero seleccione la Serie (▼ col B); la Subserie (▼ col C) ' .
            'mostrará automáticamente solo las opciones de esa serie.'
        );
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '92400E']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D97706']]],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(50);

        // ── Fila 2: Encabezados ───────────────────────────────────────────
        $headers = [
            'A2' => "Dependencia\n(no modificar)",
            'B2' => "Serie Documental *\n(seleccionar ▼)",
            'C2' => "Subserie Documental *\n(seleccionar ▼ según Serie)",
            'D2' => "Año\n(no modificar)",
            'E2' => "Asunto / Descripción del Documento *",
            'F2' => "Observaciones\n(opcional)",
        ];
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $sheet->getStyle('A2:F2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '93C5FD']]],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER,
                            'horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        // Columnas pre-llenadas con tono más oscuro
        $sheet->getStyle('A2')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E2A5E']]]);
        $sheet->getStyle('D2')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E2A5E']]]);
        $sheet->getRowDimension(2)->setRowHeight(46);

        // ── Anchos de columna ─────────────────────────────────────────────
        foreach (['A' => 34, 'B' => 36, 'C' => 40, 'D' => 10, 'E' => 58, 'F' => 40] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // ── Filas de datos 3–102 ──────────────────────────────────────────
        $lockedStyle = [
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFDBFE']]],
            'font'      => ['color' => ['rgb' => '1E3A8A'], 'italic' => true],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $editableStyle = [
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

        $sheet->getStyle('A3:A102')->applyFromArray($lockedStyle);
        $sheet->getStyle('D3:D102')->applyFromArray($lockedStyle);
        $sheet->getStyle('B3:C102')->applyFromArray($editableStyle);
        $sheet->getStyle('E3:F102')->applyFromArray($editableStyle);
        $sheet->freezePane('A3');

        // ── Hoja catálogo oculta ("Cat") ─────────────────────────────────
        // Col A  = lista de series de la unidad
        // Col B+ = subseries de cada serie (una columna por serie)
        // Cada bloque tiene un named range: SR_LIST para las series,
        // SR_{code} para las subseries de cada serie (ej: SR_S001, SR_S003).
        $catalogSheet = $spreadsheet->createSheet();
        $catalogSheet->setTitle('Cat');
        $catalogSheet->setSheetState('hidden');

        // Columna A: series
        foreach ($seriesValues as $i => $val) {
            $catalogSheet->setCellValue('A' . ($i + 2), $val);
        }
        if ($seriesCount > 0) {
            $spreadsheet->addNamedRange(new NamedRange(
                'SR_LIST',
                $catalogSheet,
                '$A$2:$A$' . ($seriesCount + 1)
            ));
        }

        // Columnas B+: subseries por serie
        $colIdx = 2; // B
        foreach ($seriesList as $series) {
            $subseries = $subseriesBySeriesId[$series->id] ?? collect();
            if ($subseries->isEmpty()) {
                continue;
            }

            $subValues = $subseries->map(fn ($sub) => "{$sub->code} - {$sub->name}")->toArray();
            $colLetter = Coordinate::stringFromColumnIndex($colIdx);

            foreach ($subValues as $j => $subVal) {
                $catalogSheet->setCellValue($colLetter . ($j + 2), $subVal);
            }

            // Named range: SR_S001, SR_S003, etc.
            // preg_replace elimina caracteres no válidos para nombres de rango
            $rangeName = 'SR_' . preg_replace('/[^A-Z0-9]/i', '_', $series->code);
            $spreadsheet->addNamedRange(new NamedRange(
                $rangeName,
                $catalogSheet,
                '$' . $colLetter . '$2:$' . $colLetter . '$' . (count($subValues) + 1)
            ));

            $colIdx++;
        }

        // ── Columna G (oculta): extrae el nombre del named range desde col B ─
        // Fórmula: =IFERROR("SR_"&LEFT(B3, FIND(" - ",B3)-1), "")
        // Ejemplo: "S001 - ACCIONES" → LEFT hasta posición 4 → "S001" → "SR_S001"
        // Al cambiar B3, G3 se recalcula y el INDIRECT de C3 carga el rango correcto.
        for ($row = 3; $row <= 102; $row++) {
            $sheet->setCellValue(
                "G{$row}",
                "=IFERROR(\"SR_\"&LEFT(B{$row},FIND(\" - \",B{$row})-1),\"\")"
            );
        }
        $sheet->getColumnDimension('G')->setVisible(false);

        // ── Validaciones (desplegables) ───────────────────────────────────
        for ($row = 3; $row <= 102; $row++) {

            // Columna B — Serie (named range fijo SR_LIST)
            if ($seriesCount > 0) {
                $v = $sheet->getCell("B{$row}")->getDataValidation();
                $v->setType(DataValidation::TYPE_LIST);
                $v->setErrorStyle(DataValidation::STYLE_STOP);
                $v->setAllowBlank(false);
                $v->setShowDropDown(true);
                $v->setShowInputMessage(true);
                $v->setShowErrorMessage(true);
                $v->setPromptTitle('Serie Documental *');
                $v->setPrompt('Haga clic en ▼ para seleccionar la serie. La Subserie se actualizará automáticamente.');
                $v->setErrorTitle('Valor no válido');
                $v->setError('Seleccione una serie de la lista. No escriba valores que no estén en el catálogo.');
                $v->setFormula1('SR_LIST');
            }

            // Columna C — Subserie (named range dinámico via INDIRECT(G{row}))
            // Cuando el usuario selecciona una serie en Bn, Gn se actualiza a "SR_Snnn"
            // y este INDIRECT muestra solo las subseries de esa serie.
            $v = $sheet->getCell("C{$row}")->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST);
            $v->setErrorStyle(DataValidation::STYLE_STOP);
            $v->setAllowBlank(false);
            $v->setShowDropDown(true);
            $v->setShowInputMessage(true);
            $v->setShowErrorMessage(true);
            $v->setPromptTitle('Subserie Documental *');
            $v->setPrompt('Primero seleccione la Serie en col B, luego haga clic en ▼ para ver las subseries disponibles.');
            $v->setErrorTitle('Subserie requerida');
            $v->setError('Seleccione una subserie válida. Primero debe seleccionar la Serie en la columna B.');
            $v->setFormula1("INDIRECT(G{$row})");
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}
