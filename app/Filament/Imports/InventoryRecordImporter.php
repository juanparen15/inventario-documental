<?php

namespace App\Filament\Imports;

use App\Models\DocumentaryClass;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\DocumentPurpose;
use App\Models\DocumentType;
use App\Models\InventoryRecord;
use App\Models\OrganizationalUnit;
use App\Models\PriorityLevel;
use App\Models\ProcessType;
use App\Models\Project;
use App\Models\StorageMedium;
use App\Models\ValidityStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InventoryRecordImporter
{
    protected array $errors = [];
    protected int $successCount = 0;
    protected int $errorCount = 0;

    protected array $columnMapping = [
        'A' => 'organizational_unit',
        'B' => 'documentary_series',
        'C' => 'documentary_subseries',
        'D' => 'documentary_class',
        'E' => 'document_type',
        'F' => 'title',
        'G' => 'description',
        'H' => 'start_date',
        'I' => 'end_date',
        'J' => 'box',
        'K' => 'folder',
        'L' => 'volume',
        'M' => 'folios',
        'N' => 'storage_medium',
        'O' => 'document_purpose',
        'P' => 'process_type',
        'Q' => 'validity_status',
        'R' => 'priority_level',
        'S' => 'project',
        'T' => 'notes',
    ];

    public function import(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        // Remove header row
        $header = array_shift($rows);

        // Cache lookups
        $organizationalUnits = OrganizationalUnit::where('is_active', true)->pluck('id', 'name')->toArray();
        $documentarySeries = DocumentarySeries::where('is_active', true)->pluck('id', 'name')->toArray();
        $documentarySubseries = DocumentarySubseries::where('is_active', true)->get()->keyBy('name');
        $documentaryClasses = DocumentaryClass::where('is_active', true)->get()->keyBy('name');
        $documentTypes = DocumentType::where('is_active', true)->get()->keyBy('name');
        $storageMediums = StorageMedium::where('is_active', true)->pluck('id', 'name')->toArray();
        $documentPurposes = DocumentPurpose::where('is_active', true)->pluck('id', 'name')->toArray();
        $processTypes = ProcessType::where('is_active', true)->pluck('id', 'name')->toArray();
        $validityStatuses = ValidityStatus::where('is_active', true)->pluck('id', 'name')->toArray();
        $priorityLevels = PriorityLevel::where('is_active', true)->pluck('id', 'name')->toArray();
        $projects = Project::where('is_active', true)->pluck('id', 'name')->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Excel row number (1-indexed, plus header)

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $rowErrors = [];
            $data = [];

            // Process organizational unit (required)
            $orgUnitName = trim($row[0] ?? '');
            if (empty($orgUnitName)) {
                $rowErrors[] = 'Unidad Organizacional es requerida';
            } elseif (!isset($organizationalUnits[$orgUnitName])) {
                $rowErrors[] = "Unidad Organizacional '{$orgUnitName}' no existe";
            } else {
                $data['organizational_unit_id'] = $organizationalUnits[$orgUnitName];
            }

            // Process documentary series (required)
            $seriesName = trim($row[1] ?? '');
            if (empty($seriesName)) {
                $rowErrors[] = 'Serie Documental es requerida';
            } elseif (!isset($documentarySeries[$seriesName])) {
                $rowErrors[] = "Serie Documental '{$seriesName}' no existe";
            } else {
                $data['documentary_series_id'] = $documentarySeries[$seriesName];
            }

            // Process documentary subseries (optional)
            $subseriesName = trim($row[2] ?? '');
            if (!empty($subseriesName)) {
                $subseries = $documentarySubseries->get($subseriesName);
                if (!$subseries) {
                    $rowErrors[] = "Subserie Documental '{$subseriesName}' no existe";
                } elseif (isset($data['documentary_series_id']) && $subseries->documentary_series_id !== $data['documentary_series_id']) {
                    $rowErrors[] = "Subserie '{$subseriesName}' no pertenece a la serie seleccionada";
                } else {
                    $data['documentary_subseries_id'] = $subseries->id;
                }
            }

            // Process documentary class (optional)
            $className = trim($row[3] ?? '');
            if (!empty($className)) {
                $class = $documentaryClasses->get($className);
                if (!$class) {
                    $rowErrors[] = "Clase Documental '{$className}' no existe";
                } elseif (isset($data['documentary_subseries_id']) && $class->documentary_subseries_id !== $data['documentary_subseries_id']) {
                    $rowErrors[] = "Clase '{$className}' no pertenece a la subserie seleccionada";
                } else {
                    $data['documentary_class_id'] = $class->id;
                }
            }

            // Process document type (optional)
            $typeName = trim($row[4] ?? '');
            if (!empty($typeName)) {
                $type = $documentTypes->get($typeName);
                if (!$type) {
                    $rowErrors[] = "Tipo de Documento '{$typeName}' no existe";
                } elseif (isset($data['documentary_class_id']) && $type->documentary_class_id !== $data['documentary_class_id']) {
                    $rowErrors[] = "Tipo '{$typeName}' no pertenece a la clase seleccionada";
                } else {
                    $data['document_type_id'] = $type->id;
                }
            }

            // Process title (required)
            $title = trim($row[5] ?? '');
            if (empty($title)) {
                $rowErrors[] = 'Título es requerido';
            } else {
                $data['title'] = $title;
            }

            // Process description (optional)
            $data['description'] = trim($row[6] ?? '') ?: null;

            // Process dates
            $startDate = $this->parseDate($row[7] ?? '');
            $endDate = $this->parseDate($row[8] ?? '');

            if ($startDate && $endDate && $startDate > $endDate) {
                $rowErrors[] = 'La fecha inicial no puede ser mayor que la fecha final';
            }

            $data['start_date'] = $startDate;
            $data['end_date'] = $endDate;

            // Process physical location
            $data['box'] = trim($row[9] ?? '') ?: null;
            $data['folder'] = trim($row[10] ?? '') ?: null;
            $data['volume'] = trim($row[11] ?? '') ?: null;

            $folios = trim($row[12] ?? '');
            if (!empty($folios)) {
                if (!is_numeric($folios) || $folios < 0) {
                    $rowErrors[] = 'Folios debe ser un número positivo';
                } else {
                    $data['folios'] = (int) $folios;
                }
            }

            // Process catalog references
            $this->processCatalogField($row[13] ?? '', 'Soporte', $storageMediums, 'storage_medium_id', $data, $rowErrors);
            $this->processCatalogField($row[14] ?? '', 'Objeto', $documentPurposes, 'document_purpose_id', $data, $rowErrors);
            $this->processCatalogField($row[15] ?? '', 'Tipo de Proceso', $processTypes, 'process_type_id', $data, $rowErrors);
            $this->processCatalogField($row[16] ?? '', 'Estado de Vigencia', $validityStatuses, 'validity_status_id', $data, $rowErrors);
            $this->processCatalogField($row[17] ?? '', 'Nivel de Prioridad', $priorityLevels, 'priority_level_id', $data, $rowErrors);
            $this->processCatalogField($row[18] ?? '', 'Proyecto', $projects, 'project_id', $data, $rowErrors);

            // Process notes
            $data['notes'] = trim($row[19] ?? '') ?: null;

            // Add created_by
            $data['created_by'] = Auth::id();

            if (!empty($rowErrors)) {
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

    protected function processCatalogField(?string $value, string $label, array $lookup, string $field, array &$data, array &$errors): void
    {
        $value = trim($value ?? '');
        if (!empty($value)) {
            if (!isset($lookup[$value])) {
                $errors[] = "{$label} '{$value}' no existe";
            } else {
                $data[$field] = $lookup[$value];
            }
        }
    }

    protected function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value);

        // Try different date formats
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        // Try Excel serial date
        if (is_numeric($value)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Ignore
            }
        }

        return null;
    }

    public static function generateTemplate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Registros de Inventario');

        // Headers
        $headers = [
            'A1' => 'Unidad Organizacional *',
            'B1' => 'Serie Documental *',
            'C1' => 'Subserie Documental',
            'D1' => 'Clase Documental',
            'E1' => 'Tipo de Documento',
            'F1' => 'Título *',
            'G1' => 'Descripción',
            'H1' => 'Fecha Inicial (DD/MM/YYYY)',
            'I1' => 'Fecha Final (DD/MM/YYYY)',
            'J1' => 'Caja',
            'K1' => 'Carpeta',
            'L1' => 'Tomo',
            'M1' => 'Folios',
            'N1' => 'Soporte',
            'O1' => 'Objeto',
            'P1' => 'Tipo de Proceso',
            'Q1' => 'Estado de Vigencia',
            'R1' => 'Nivel de Prioridad',
            'S1' => 'Proyecto',
            'T1' => 'Notas',
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
        $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);

        // Set column widths
        $widths = ['A' => 25, 'B' => 20, 'C' => 20, 'D' => 20, 'E' => 20, 'F' => 40, 'G' => 40, 'H' => 18, 'I' => 18, 'J' => 10, 'K' => 10, 'L' => 10, 'M' => 10, 'N' => 15, 'O' => 15, 'P' => 15, 'Q' => 15, 'R' => 15, 'S' => 20, 'T' => 30];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Add catalog data sheets
        self::addCatalogSheet($spreadsheet, 'Unidades', OrganizationalUnit::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Series', DocumentarySeries::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Subseries', DocumentarySubseries::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Clases', DocumentaryClass::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Tipos Doc', DocumentType::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Soportes', StorageMedium::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Objetos', DocumentPurpose::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Tipos Proceso', ProcessType::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Estados Vigencia', ValidityStatus::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Niveles Prioridad', PriorityLevel::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Proyectos', Project::where('is_active', true)->pluck('name')->toArray());

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
