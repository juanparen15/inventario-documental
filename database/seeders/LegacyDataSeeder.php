<?php

namespace Database\Seeders;

use App\Models\AdministrativeAct;
use App\Models\InventoryRecord;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LegacyDataSeeder extends Seeder
{
    /**
     * Seeds inventory records and administrative acts from the legacy database (bdpaakgr).
     *
     * IMPORTANT: Run this AFTER ProductionDataSeeder which seeds the base data.
     *
     * Requirements:
     * - Configure OLD_DB_* variables in .env for the legacy database connection
     * - The legacy database should have tables: planadquisiciones, actos, users, areas
     *
     * Run: php artisan db:seed --class=LegacyDataSeeder
     */
    protected $oldConnection = 'old_mysql';

    // ID mappings for relationships
    protected array $areaMapping = [];
    protected array $userMapping = [];
    protected array $clasificacionMapping = [];
    protected array $requiproyectoToAreaMapping = [];

    public function run(): void
    {
        $this->command->info('Seeding legacy data from database: bdpaakgr...');

        // Check if old connection is configured
        if (!$this->checkOldConnection()) {
            $this->command->warn('Old database connection not configured. Skipping legacy data migration.');
            $this->command->info('To migrate legacy data, configure OLD_DB_* variables in .env');
            return;
        }

        Schema::disableForeignKeyConstraints();

        // Build mappings from legacy to new IDs
        $this->buildAreaMapping();
        $this->buildRequiproyectoMapping();
        $this->buildUserMapping();
        $this->buildClassificationMapping();

        // Migrate data
        $this->migrateAdministrativeActs();
        $this->migrateInventoryRecords();

        Schema::enableForeignKeyConstraints();

        $this->command->info('Legacy data seeded successfully!');
    }

    protected function checkOldConnection(): bool
    {
        try {
            DB::connection($this->oldConnection)->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function buildAreaMapping(): void
    {
        $this->command->info('Building area mappings...');

        try {
            // Get old areas
            $oldAreas = DB::connection($this->oldConnection)->table('areas')->get();

            // Get new organizational units
            $newUnits = OrganizationalUnit::all()->keyBy('name');

            foreach ($oldAreas as $oldArea) {
                $areaName = $oldArea->nomarea ?? $oldArea->nombre ?? null;
                if ($areaName && isset($newUnits[$areaName])) {
                    $this->areaMapping[$oldArea->id] = $newUnits[$areaName]->id;
                }
            }

            $this->command->info('  - Mapped ' . count($this->areaMapping) . ' areas');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not build area mapping: ' . $e->getMessage());
        }
    }

    protected function buildRequiproyectoMapping(): void
    {
        $this->command->info('Building requiproyecto to area mappings...');

        try {
            // requiproyectos has areas_id which relates to the area
            $requiproyectos = DB::connection($this->oldConnection)->table('requiproyectos')->get();

            foreach ($requiproyectos as $rp) {
                $areaId = $rp->areas_id ?? null;
                if ($areaId && isset($this->areaMapping[$areaId])) {
                    $this->requiproyectoToAreaMapping[$rp->id] = $this->areaMapping[$areaId];
                }
            }

            $this->command->info('  - Mapped ' . count($this->requiproyectoToAreaMapping) . ' requiproyectos');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not build requiproyecto mapping: ' . $e->getMessage());
        }
    }

    protected function buildUserMapping(): void
    {
        $this->command->info('Building user mappings...');

        try {
            // Get old users
            $oldUsers = DB::connection($this->oldConnection)->table('users')->get();

            // Get new users by email
            $newUsers = User::all()->keyBy('email');

            foreach ($oldUsers as $oldUser) {
                if (isset($newUsers[$oldUser->email])) {
                    $this->userMapping[$oldUser->id] = $newUsers[$oldUser->email]->id;
                }
            }

            $this->command->info('  - Mapped ' . count($this->userMapping) . ' users');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not build user mapping: ' . $e->getMessage());
        }
    }

    protected function buildClassificationMapping(): void
    {
        $this->command->info('Building classification mappings...');

        try {
            // Get old classifications
            $oldClassifications = DB::connection($this->oldConnection)->table('clasificaciones')->get();

            // Get new classifications by slug
            $newClassifications = DB::table('act_classifications')->get()->keyBy('slug');

            foreach ($oldClassifications as $old) {
                $slug = $old->slug ?? Str::slug($old->nom_clasificacion ?? 'classification');
                if (isset($newClassifications[$slug])) {
                    $this->clasificacionMapping[$old->id] = $newClassifications[$slug]->id;
                }
            }

            $this->command->info('  - Mapped ' . count($this->clasificacionMapping) . ' classifications');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not build classification mapping: ' . $e->getMessage());
        }
    }

    protected function migrateAdministrativeActs(): void
    {
        $this->command->info('Migrating administrative acts from legacy database...');

        try {
            // Clear existing records
            DB::table('administrative_acts')->truncate();

            $oldData = DB::connection($this->oldConnection)->table('actos')->get();

            $count = 0;
            $errors = 0;

            foreach ($oldData as $row) {
                try {
                    // Map relationships
                    $userId = $this->userMapping[$row->user_id] ?? null;
                    $organizationalUnitId = $this->areaMapping[$row->area_id] ?? null;
                    $classificationId = $this->clasificacionMapping[$row->clasificacion_id] ?? null;

                    // Parse date (format: dd-mm-yyyy)
                    $actDate = null;
                    if (!empty($row->fecha_acto)) {
                        $date = \DateTime::createFromFormat('d-m-Y', $row->fecha_acto);
                        if ($date) {
                            $actDate = $date->format('Y-m-d');
                        }
                    }

                    // Parse attachments
                    $attachments = null;
                    if (!empty($row->pdf_acto_administrativo)) {
                        $decoded = json_decode($row->pdf_acto_administrativo, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $attachments = $decoded;
                        } else {
                            $attachments = [$row->pdf_acto_administrativo];
                        }
                    }

                    // Generate unique slug using ID
                    $baseSlug = Str::slug($row->objeto ?? 'acto');
                    $slug = $baseSlug . '-' . $row->id . '-' . substr(md5($row->id . time()), 0, 8);

                    // The field is 'radicado' not 'consecutivo' in the old database
                    $filingNumber = $row->radicado ?? null;

                    // Extract year from fecha_acto for vigencia
                    $vigencia = null;
                    if ($actDate) {
                        $vigencia = substr($actDate, 0, 4);
                    }

                    AdministrativeAct::create([
                        'user_id' => $userId,
                        'organizational_unit_id' => $organizationalUnitId,
                        'act_classification_id' => $classificationId,
                        'filing_number' => $filingNumber,
                        'vigencia' => $vigencia,
                        'act_date' => $actDate,
                        'subject' => $row->objeto,
                        'attachments' => $attachments,
                        'slug' => $slug,
                        'notes' => null,
                        'created_by' => $userId,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);

                    $count++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::warning("Error migrating act {$row->id}: " . $e->getMessage());
                }
            }

            $this->command->info("  - Migrated {$count} administrative acts ({$errors} errors)");
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate administrative acts: ' . $e->getMessage());
        }
    }

    protected function migrateInventoryRecords(): void
    {
        $this->command->info('Migrating inventory records from legacy database...');

        try {
            // Clear existing records
            DB::table('inventory_records')->truncate();

            // Get total count for progress reporting
            $totalCount = DB::connection($this->oldConnection)->table('planadquisiciones')->count();
            $this->command->info("  - Found {$totalCount} inventory records to migrate");

            // Process in chunks to handle large datasets
            $chunkSize = 500;
            $processed = 0;
            $errors = 0;

            DB::connection($this->oldConnection)
                ->table('planadquisiciones')
                ->orderBy('id')
                ->chunk($chunkSize, function ($records) use (&$processed, &$errors, $totalCount) {
                    foreach ($records as $row) {
                        try {
                            $this->createInventoryRecord($row);
                            $processed++;
                        } catch (\Exception $e) {
                            $errors++;
                            Log::warning("Error migrating inventory record {$row->id}: " . $e->getMessage());
                        }
                    }

                    // Progress report every chunk
                    $this->command->info("  - Progress: {$processed}/{$totalCount} records ({$errors} errors)");
                });

            $this->command->info("  - Migrated {$processed} inventory records ({$errors} errors)");
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate inventory records: ' . $e->getMessage());
        }
    }

    protected function createInventoryRecord(object $row): void
    {
        // Map relationships - planadquisiciones uses requiproyecto_id, not area_id
        $requiproyectoId = $row->requiproyecto_id ?? null;
        $organizationalUnitId = $requiproyectoId ? ($this->requiproyectoToAreaMapping[$requiproyectoId] ?? null) : null;
        $userId = $this->userMapping[$row->user_id] ?? null;

        // If organizational unit not found, use a default
        if (!$organizationalUnitId) {
            $defaultUnit = OrganizationalUnit::where('slug', 'unidad-sin-asignar')->first();
            if (!$defaultUnit) {
                $defaultEntity = DB::table('entities')->first();
                $defaultUnit = OrganizationalUnit::firstOrCreate(
                    ['slug' => 'unidad-sin-asignar'],
                    ['name' => 'Unidad Sin Asignar', 'code' => 'USA', 'entity_id' => $defaultEntity->id ?? 1]
                );
            }
            $organizationalUnitId = $defaultUnit->id;
        }

        // Parse dates
        $startDate = $this->parseDate($row->fechaInicial ?? null);
        $endDate = $this->parseDate($row->fechaFinal ?? null);

        // Parse folios
        $folios = $this->parseFolios($row->folio ?? null);

        // Build title
        $title = !empty($row->nota) ? $row->nota : "Registro {$row->id}";
        if (strlen($title) > 255) {
            $title = substr($title, 0, 252) . '...';
        }

        // Build description
        $descriptionParts = [];
        if (!empty($row->nombre_unidad)) {
            $descriptionParts[] = "Unidad: {$row->nombre_unidad}";
        }
        if (!empty($row->ubicacion)) {
            $descriptionParts[] = "Ubicación: {$row->ubicacion}";
        }
        if (!empty($row->soporte_formato)) {
            $descriptionParts[] = "Formato: {$row->soporte_formato}";
        }
        if (!empty($row->cantidad_documentos)) {
            $descriptionParts[] = "Cantidad docs: {$row->cantidad_documentos}";
        }
        if (!empty($row->tamano_documentos)) {
            $descriptionParts[] = "Tamaño: {$row->tamano_documentos}";
        }
        if (!empty($row->otro)) {
            $descriptionParts[] = "Otro: {$row->otro}";
        }
        if (!empty($row->cantidad_otros)) {
            $descriptionParts[] = "Cantidad otros: {$row->cantidad_otros}";
        }
        $description = !empty($descriptionParts) ? implode("\n", $descriptionParts) : null;

        // Parse attachments
        $attachments = null;
        if (!empty($row->archivo_pdf)) {
            $decoded = json_decode($row->archivo_pdf, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $attachments = $decoded;
            } else {
                $attachments = [$row->archivo_pdf];
            }
        }

        // Generate reference code
        $referenceCode = sprintf(
            'INV-%06d-%s',
            $row->id,
            strtoupper(substr(md5($row->slug ?? $row->id), 0, 6))
        );

        InventoryRecord::create([
            'organizational_unit_id' => $organizationalUnitId,
            'inventory_purpose' => 'inventarios_individuales',
            'documentary_series_id' => $row->segmento_id ?? 1,
            'documentary_subseries_id' => $row->familias_id ?? null,
            'title' => $title,
            'description' => $description,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'has_start_date' => !empty($startDate),
            'has_end_date' => !empty($endDate),
            'box' => $row->caja ?? null,
            'folder' => $row->carpeta ?? null,
            'volume' => $row->tomo ?? null,
            'folios' => $folios,
            'storage_medium_id' => $row->fuente_id ?? null,
            'storage_unit_type' => null,
            'storage_unit_quantity' => null,
            'priority_level_id' => $row->tipoprioridade_id ?? null,
            'notes' => null,
            'reference_code' => $referenceCode,
            'attachments' => $attachments,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => $row->created_at ?? now(),
            'updated_at' => $row->updated_at ?? now(),
        ]);
    }

    protected function parseDate(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        // Try yyyy-mm-dd format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
            return $dateString;
        }

        // Try dd-mm-yyyy format
        $date = \DateTime::createFromFormat('d-m-Y', $dateString);
        if ($date) {
            return $date->format('Y-m-d');
        }

        // Try dd/mm/yyyy format
        $date = \DateTime::createFromFormat('d/m/Y', $dateString);
        if ($date) {
            return $date->format('Y-m-d');
        }

        return null;
    }

    protected function parseFolios(?string $folioString): ?int
    {
        if (empty($folioString)) {
            return null;
        }

        // If it's a range like "1-72", calculate the count
        if (preg_match('/^(\d+)-(\d+)$/', $folioString, $matches)) {
            $start = (int) $matches[1];
            $end = (int) $matches[2];
            return max(0, $end - $start + 1);
        }

        // If it's a single number
        if (is_numeric($folioString)) {
            return (int) $folioString;
        }

        return null;
    }
}
