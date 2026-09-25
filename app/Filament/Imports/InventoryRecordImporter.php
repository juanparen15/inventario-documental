<?php

namespace App\Filament\Imports;

use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\InventoryRecord;
use App\Models\OrganizationalUnit;
use App\Models\PriorityLevel;
use App\Models\StorageMedium;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class InventoryRecordImporter
{
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $errorCount = 0;

    /**
     * Columnas del Excel (fila 1 = encabezados, datos desde la fila 2):
     *
     *   A = Unidad Organizacional * (Oficina Productora)
     *   B = Objeto *                (uno de InventoryRecord::INVENTORY_PURPOSES)
     *   C = Serie Documental *      (FUID, según ccd_entries de la unidad)
     *   D = Subserie Documental     (obligatoria solo si la serie tiene subseries asignadas)
     *   E = Título de la Unidad Documental
     *   F = Descripción
     *   G = Fecha Inicial (DD/MM/YYYY)
     *   H = Fecha Final (DD/MM/YYYY)
     *   I = No. Caja *
     *   J = No. Carpeta *
     *   K = No. Tomo/Legajo/Libro *
     *   L = No. Folios *            (texto libre, ej: 1-200)
     *   M = Soporte Físico/Electrónico
     *   N = Tipo de Unidad de Almacenamiento (uno de InventoryRecord::STORAGE_UNIT_TYPES)
     *   O = Cantidad de Unidades de Almacenamiento
     *   P = Nivel de Prioridad
     *   Q = Notas
     */
    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Quitar fila de encabezados
        array_shift($rows);

        // ── Cachés de búsqueda ────────────────────────────────────────────
        $organizationalUnits = OrganizationalUnit::where('is_active', true)->pluck('id', 'name')->toArray();
        $storageMediums = StorageMedium::where('is_active', true)->pluck('id', 'name')->toArray();
        $priorityLevels = PriorityLevel::where('is_active', true)->pluck('id', 'name')->toArray();

        // Series/subseries FUID activas, admite 'código - nombre' o solo 'nombre'
        $allSeries = DocumentarySeries::where('is_active', true)->where('context', 'fuid')->get(['id', 'code', 'name']);
        $allSeriesMap = [];
        foreach ($allSeries as $s) {
            $allSeriesMap["{$s->code} - {$s->name}"] = $s->id;
            $allSeriesMap[$s->name] = $s->id;
        }

        $allSubseries = DocumentarySubseries::where('is_active', true)->get(['id', 'code', 'name', 'documentary_series_id']);
        $subseriesById = $allSubseries->keyBy('id');
        $subseriesMapBySeries = [];
        foreach ($allSubseries as $sub) {
            $subseriesMapBySeries[$sub->documentary_series_id]["{$sub->code} - {$sub->name}"] = $sub->id;
            $subseriesMapBySeries[$sub->documentary_series_id][$sub->name] = $sub->id;
        }

        // Series/subseries permitidas por unidad, según ccd_entries (fallback: todas las FUID activas)
        $ccdAll = CcdEntry::with(['documentarySeries:id,code,name,is_active,context'])->get();
        $seriesAllowedByUnit = [];
        $unitHasSubseriesForSeries = [];
        foreach ($ccdAll as $entry) {
            $s = $entry->documentarySeries;
            if (! $s || ! $s->is_active || $s->context !== 'fuid') {
                continue;
            }
            $unitId = $entry->organizational_unit_id;
            $seriesAllowedByUnit[$unitId]["{$s->code} - {$s->name}"] = $s->id;
            $seriesAllowedByUnit[$unitId][$s->name] = $s->id;

            if ($entry->documentary_subseries_id) {
                $unitHasSubseriesForSeries[$unitId][$s->id] = true;
            }
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Fila de Excel (1-indexada, más el encabezado)

            if (empty(array_filter($row))) {
                continue;
            }

            $rowErrors = [];
            $data = [];
            $unitId = null;
            $seriesId = null;

            // Columna A — Unidad Organizacional (obligatoria)
            $orgUnitName = trim($row[0] ?? '');
            if (empty($orgUnitName)) {
                $rowErrors[] = 'Unidad Organizacional es requerida';
            } elseif (! isset($organizationalUnits[$orgUnitName])) {
                $rowErrors[] = "Unidad Organizacional '{$orgUnitName}' no existe";
            } else {
                $unitId = $organizationalUnits[$orgUnitName];
                $data['organizational_unit_id'] = $unitId;
            }

            // Columna B — Objeto (obligatorio)
            $purposeInput = trim($row[1] ?? '');
            $purposeKey = $this->resolvePurposeKey($purposeInput);
            if (empty($purposeInput)) {
                $rowErrors[] = 'Objeto es requerido';
            } elseif (! $purposeKey) {
                $rowErrors[] = "Objeto '{$purposeInput}' no es válido";
            } else {
                $data['inventory_purpose'] = $purposeKey;
            }

            // Columna C — Serie Documental (obligatoria)
            $seriesName = trim($row[2] ?? '');
            $unitSeriesMap = $unitId
                ? (! empty($seriesAllowedByUnit[$unitId]) ? $seriesAllowedByUnit[$unitId] : $allSeriesMap)
                : [];

            if (empty($seriesName)) {
                $rowErrors[] = 'Serie Documental es requerida';
            } elseif ($unitId && ! isset($unitSeriesMap[$seriesName])) {
                $rowErrors[] = "Serie Documental '{$seriesName}' no es una serie FUID válida para la unidad";
            } elseif ($unitId) {
                $seriesId = $unitSeriesMap[$seriesName];
                $data['documentary_series_id'] = $seriesId;
            }

            // Columna D — Subserie Documental (obligatoria solo si la serie tiene subseries asignadas)
            $subseriesName = trim($row[3] ?? '');
            $seriesSubMap = $seriesId ? ($subseriesMapBySeries[$seriesId] ?? []) : [];
            $subseriesRequired = $unitId && $seriesId && ! empty($unitHasSubseriesForSeries[$unitId][$seriesId]);

            if ($subseriesRequired && empty($subseriesName)) {
                $rowErrors[] = 'Subserie Documental es requerida para la serie seleccionada';
            } elseif (! empty($subseriesName)) {
                if (! isset($seriesSubMap[$subseriesName])) {
                    $rowErrors[] = "Subserie Documental '{$subseriesName}' no pertenece a la serie seleccionada";
                } else {
                    $data['documentary_subseries_id'] = $seriesSubMap[$subseriesName];
                }
            }

            // Columna E — Título (opcional)
            $data['title'] = trim($row[4] ?? '') ?: '';

            // Columna F — Descripción (opcional)
            $data['description'] = trim($row[5] ?? '') ?: null;

            // Columnas G/H — Fechas extremas (opcionales, "S.F." si se omiten)
            $startDate = $this->parseDate($row[6] ?? '');
            $endDate = $this->parseDate($row[7] ?? '');

            if ($startDate && $endDate && $startDate > $endDate) {
                $rowErrors[] = 'La fecha inicial no puede ser mayor que la fecha final';
            }

            $data['has_start_date'] = (bool) $startDate;
            $data['start_date'] = $startDate;
            $data['has_end_date'] = (bool) $endDate;
            $data['end_date'] = $endDate;

            // Columnas I/J/K/L — Ubicación física (obligatorias)
            $box = trim($row[8] ?? '');
            if (empty($box)) {
                $rowErrors[] = 'No. Caja es requerido';
            } else {
                $data['box'] = $box;
            }

            $folder = trim($row[9] ?? '');
            if (empty($folder)) {
                $rowErrors[] = 'No. Carpeta es requerido';
            } else {
                $data['folder'] = $folder;
            }

            $volume = trim($row[10] ?? '');
            if (empty($volume)) {
                $rowErrors[] = 'No. Tomo/Legajo/Libro es requerido';
            } else {
                $data['volume'] = $volume;
            }

            $folios = trim($row[11] ?? '');
            if (empty($folios)) {
                $rowErrors[] = 'No. Folios es requerido';
            } else {
                $data['folios'] = $folios;
            }

            // Columna M — Soporte (opcional)
            $storageMediumName = trim($row[12] ?? '');
            if (! empty($storageMediumName)) {
                if (! isset($storageMediums[$storageMediumName])) {
                    $rowErrors[] = "Soporte '{$storageMediumName}' no existe";
                } else {
                    $data['storage_medium_id'] = $storageMediums[$storageMediumName];
                }
            }

            // Columna N — Tipo de Unidad de Almacenamiento (opcional)
            $storageUnitTypeInput = trim($row[13] ?? '');
            if (! empty($storageUnitTypeInput)) {
                $storageUnitTypeKey = $this->resolveStorageUnitTypeKey($storageUnitTypeInput);
                if (! $storageUnitTypeKey) {
                    $rowErrors[] = "Tipo de Unidad de Almacenamiento '{$storageUnitTypeInput}' no es válido";
                } else {
                    $data['storage_unit_type'] = $storageUnitTypeKey;
                }
            }

            // Columna O — Cantidad de Unidades (opcional)
            $storageUnitQuantity = trim($row[14] ?? '');
            if (! empty($storageUnitQuantity)) {
                if (! is_numeric($storageUnitQuantity) || $storageUnitQuantity < 1) {
                    $rowErrors[] = 'Cantidad de Unidades debe ser un número positivo';
                } else {
                    $data['storage_unit_quantity'] = (int) $storageUnitQuantity;
                }
            }

            // Columna P — Nivel de Prioridad (opcional)
            $priorityLevelName = trim($row[15] ?? '');
            if (! empty($priorityLevelName)) {
                if (! isset($priorityLevels[$priorityLevelName])) {
                    $rowErrors[] = "Nivel de Prioridad '{$priorityLevelName}' no existe";
                } else {
                    $data['priority_level_id'] = $priorityLevels[$priorityLevelName];
                }
            }

            // Columna Q — Notas (opcional)
            $data['notes'] = trim($row[16] ?? '') ?: null;

            $data['created_by'] = Auth::id();

            if (! empty($rowErrors)) {
                $this->errors[$rowNumber] = $rowErrors;
                $this->errorCount++;
            } else {
                try {
                    InventoryRecord::create($data);
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

    protected function resolvePurposeKey(string $value): ?string
    {
        if (isset(InventoryRecord::INVENTORY_PURPOSES[$value])) {
            return $value;
        }

        $key = array_search($value, InventoryRecord::INVENTORY_PURPOSES, true);

        return $key !== false ? $key : null;
    }

    protected function resolveStorageUnitTypeKey(string $value): ?string
    {
        if (isset(InventoryRecord::STORAGE_UNIT_TYPES[$value])) {
            return $value;
        }

        $key = array_search($value, InventoryRecord::STORAGE_UNIT_TYPES, true);

        return $key !== false ? $key : null;
    }

    protected function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Ignorar: no es una fecha serial de Excel válida
            }
        }

        return null;
    }

    public static function generateTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registros de Inventario');

        $headers = [
            'A1' => 'Unidad Organizacional *',
            'B1' => 'Objeto *',
            'C1' => 'Serie Documental *',
            'D1' => 'Subserie Documental',
            'E1' => 'Título',
            'F1' => 'Descripción',
            'G1' => 'Fecha Inicial (DD/MM/YYYY)',
            'H1' => 'Fecha Final (DD/MM/YYYY)',
            'I1' => 'No. Caja *',
            'J1' => 'No. Carpeta *',
            'K1' => 'No. Tomo/Legajo/Libro *',
            'L1' => 'No. Folios *',
            'M1' => 'Soporte',
            'N1' => 'Tipo Unidad de Almacenamiento',
            'O1' => 'Cantidad Unidades',
            'P1' => 'Nivel de Prioridad',
            'Q1' => 'Notas',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

        $widths = ['A' => 30, 'B' => 25, 'C' => 25, 'D' => 25, 'E' => 35, 'F' => 35, 'G' => 18, 'H' => 18, 'I' => 10, 'J' => 10, 'K' => 10, 'L' => 15, 'M' => 20, 'N' => 22, 'O' => 15, 'P' => 18, 'Q' => 30];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        self::addCatalogSheet($spreadsheet, 'Unidades', OrganizationalUnit::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Objetos', array_values(InventoryRecord::INVENTORY_PURPOSES));
        self::addCatalogSheet($spreadsheet, 'Series FUID', DocumentarySeries::where('is_active', true)->where('context', 'fuid')->orderBy('code')->get()->map(fn ($s) => "{$s->code} - {$s->name}")->toArray());
        self::addCatalogSheet($spreadsheet, 'Subseries', DocumentarySubseries::where('is_active', true)->orderBy('code')->get()->map(fn ($s) => "{$s->code} - {$s->name}")->toArray());
        self::addCatalogSheet($spreadsheet, 'Soportes', StorageMedium::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Tipos Unidad Almacenamiento', array_values(InventoryRecord::STORAGE_UNIT_TYPES));
        self::addCatalogSheet($spreadsheet, 'Niveles Prioridad', PriorityLevel::where('is_active', true)->pluck('name')->toArray());

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
