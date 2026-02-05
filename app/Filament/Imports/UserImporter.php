<?php

namespace App\Filament\Imports;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Role;

class UserImporter
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
        $roles = Role::pluck('id', 'name')->toArray();
        $existingEmails = User::pluck('email')->toArray();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $rowErrors = [];
            $data = [];

            // Name (required)
            $name = trim($row[0] ?? '');
            if (empty($name)) {
                $rowErrors[] = 'Nombre es requerido';
            } else {
                $data['name'] = $name;
            }

            // Last name (optional)
            $data['last_name'] = trim($row[1] ?? '') ?: null;

            // Email (required, unique)
            $email = trim($row[2] ?? '');
            if (empty($email)) {
                $rowErrors[] = 'Correo electrónico es requerido';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = "'{$email}' no es un correo electrónico válido";
            } elseif (in_array($email, $existingEmails)) {
                $rowErrors[] = "El correo '{$email}' ya existe en el sistema";
            } else {
                $data['email'] = $email;
                $existingEmails[] = $email; // Prevent duplicates in same import
            }

            // Phone (optional)
            $data['phone'] = trim($row[3] ?? '') ?: null;

            // Document number (optional)
            $data['document_number'] = trim($row[4] ?? '') ?: null;

            // Organizational unit (optional)
            $orgUnitName = trim($row[5] ?? '');
            if (!empty($orgUnitName)) {
                if (!isset($organizationalUnits[$orgUnitName])) {
                    $rowErrors[] = "Unidad Organizacional '{$orgUnitName}' no existe";
                } else {
                    $data['organizational_unit_id'] = $organizationalUnits[$orgUnitName];
                }
            }

            // Password (optional - will generate if empty)
            $password = trim($row[6] ?? '');
            if (empty($password)) {
                $password = 'password123'; // Default password
            } elseif (strlen($password) < 8) {
                $rowErrors[] = 'La contraseña debe tener al menos 8 caracteres';
            }
            $data['password'] = Hash::make($password);

            // Role (optional)
            $roleName = trim($row[7] ?? '');
            $roleId = null;
            if (!empty($roleName)) {
                if (!isset($roles[$roleName])) {
                    $rowErrors[] = "Rol '{$roleName}' no existe";
                } else {
                    $roleId = $roles[$roleName];
                }
            }

            if (!empty($rowErrors)) {
                $this->errors[$rowNumber] = $rowErrors;
                $this->errorCount++;
            } else {
                try {
                    $user = User::create($data);
                    if ($roleId) {
                        $user->roles()->attach($roleId);
                    }
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
        $sheet->setTitle('Usuarios');

        // Headers
        $headers = [
            'A1' => 'Nombre *',
            'B1' => 'Apellido',
            'C1' => 'Correo Electrónico *',
            'D1' => 'Teléfono',
            'E1' => 'Documento de Identidad',
            'F1' => 'Unidad Organizacional',
            'G1' => 'Contraseña (mín. 8 caracteres)',
            'H1' => 'Rol',
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
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Set column widths
        $widths = ['A' => 20, 'B' => 20, 'C' => 30, 'D' => 15, 'E' => 20, 'F' => 25, 'G' => 30, 'H' => 20];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Add notes
        $sheet->setCellValue('A3', 'Notas:');
        $sheet->setCellValue('A4', '- Los campos marcados con * son obligatorios');
        $sheet->setCellValue('A5', '- Si no se proporciona contraseña, se asignará "password123" por defecto');
        $sheet->setCellValue('A6', '- El correo electrónico debe ser único en el sistema');
        $sheet->getStyle('A3:A6')->getFont()->setItalic(true);

        // Add catalog sheets
        self::addCatalogSheet($spreadsheet, 'Unidades', OrganizationalUnit::where('is_active', true)->pluck('name')->toArray());
        self::addCatalogSheet($spreadsheet, 'Roles', Role::pluck('name')->toArray());

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
