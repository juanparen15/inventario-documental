<?php

namespace Database\Seeders;

use App\Models\ActClassification;
use App\Models\AdministrativeAct;
use App\Models\InventoryRecord;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DataMigrationSeeder extends Seeder
{
    protected $oldConnection = 'old_mysql';

    // ID mappings for relationships
    protected array $areaMapping = [];
    protected array $clasificacionMapping = [];
    protected array $userMapping = [];

    public function run(): void
    {
        $this->command->info('Starting data migration from old database...');

        // Migrate entities (dependencias)
        $this->migrateEntities();

        // Migrate organizational units (areas)
        $this->migrateOrganizationalUnits();

        // Assign codes from requiproyectos to organizational units
        $this->assignOrganizationalUnitCodes();

        // Migrate documentary series (segmentos)
        $this->migrateDocumentarySeries();

        // Migrate documentary subseries (familias)
        $this->migrateDocumentarySubseries();

        // Migrate storage mediums (fuentes)
        $this->migrateStorageMediums();

        // Migrate priority levels (tipoprioridades)
        $this->migratePriorityLevels();

        // Migrate act classifications (clasificaciones)
        $this->migrateActClassifications();

        // Migrate users
        $this->migrateUsers();

        // Migrate administrative acts (actos)
        $this->migrateAdministrativeActs();

        // Migrate inventory records (planadquisiciones)
        $this->migrateInventoryRecords();

        $this->command->info('Data migration completed successfully!');
    }

    protected function migrateEntities(): void
    {
        $this->command->info('Migrating entities...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('dependencias')->get();

            foreach ($oldData as $row) {
                DB::table('entities')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->nomdependencia ?? $row->nomDependencia ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'slug' => Str::slug($row->nomdependencia ?? $row->nomDependencia ?? $row->nombre ?? 'entity-' . $row->id),
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' entities');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate entities: ' . $e->getMessage());
        }
    }

    protected function migrateOrganizationalUnits(): void
    {
        $this->command->info('Migrating organizational units...');

        try {
            // First, create a default entity for areas without dependencia
            DB::table('entities')->updateOrInsert(
                ['code' => '000'],
                [
                    'name' => 'SIN DEPENDENCIA',
                    'slug' => 'sin-dependencia',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $defaultEntity = DB::table('entities')->where('code', '000')->first();

            $oldData = DB::connection($this->oldConnection)->table('areas')->get();

            foreach ($oldData as $row) {
                // Get entity_id from dependencia_id (entities should already be migrated)
                $entityId = $row->dependencia_id ?? null;
                if (!$entityId || !DB::table('entities')->where('id', $entityId)->exists()) {
                    $entityId = $defaultEntity->id;
                }

                $result = DB::table('organizational_units')->updateOrInsert(
                    ['name' => $row->nomarea ?? $row->nombre ?? 'Sin nombre'],
                    [
                        'code' => $row->codigo ?? (string) $row->id,
                        'slug' => Str::slug($row->nomarea ?? $row->nombre ?? 'unit-' . $row->id),
                        'entity_id' => $entityId,
                        'is_active' => true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );

                // Get the inserted/updated unit ID for mapping
                $unit = DB::table('organizational_units')
                    ->where('name', $row->nomarea ?? $row->nombre ?? 'Sin nombre')
                    ->first();

                if ($unit) {
                    $this->areaMapping[$row->id] = $unit->id;
                }
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' organizational units');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate organizational units: ' . $e->getMessage());
        }
    }

    protected function assignOrganizationalUnitCodes(): void
    {
        $this->command->info('Assigning codes to organizational units from requiproyectos...');

        try {
            // Los requiproyectos contienen los codigos de las oficinas (DA, AJ, CI, etc.)
            // con su areas_id que indica a que area pertenece
            $oldData = DB::connection($this->oldConnection)->table('requiproyectos')->get();

            $count = 0;
            foreach ($oldData as $row) {
                $areaId = $row->areas_id;
                $code = $row->detproyecto ?? $row->slug ?? null;

                if ($areaId && $code) {
                    // Buscar la unidad organizacional por el mapping
                    $unitId = $this->areaMapping[$areaId] ?? null;

                    if ($unitId) {
                        DB::table('organizational_units')
                            ->where('id', $unitId)
                            ->update(['code' => strtoupper($code)]);
                        $count++;
                    }
                }
            }

            $this->command->info("  - Assigned codes to {$count} organizational units");
        } catch (\Exception $e) {
            $this->command->warn('  - Could not assign codes: ' . $e->getMessage());
        }
    }

    protected function migrateDocumentarySeries(): void
    {
        $this->command->info('Migrating documentary series...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('segmentos')->get();

            foreach ($oldData as $row) {
                DB::table('documentary_series')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'code' => $row->codSegmento ?? $row->codigo ?? 'S' . str_pad($row->id, 3, '0', STR_PAD_LEFT),
                        'name' => $row->detsegmento ?? $row->nomSegmento ?? $row->nombre ?? 'Sin nombre',
                        'description' => $row->descripcion ?? null,
                        'retention_years' => $row->anios_retencion ?? null,
                        'final_disposition' => $row->disposicion_final ?? null,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' documentary series');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate documentary series: ' . $e->getMessage());
        }
    }

    protected function migrateDocumentarySubseries(): void
    {
        $this->command->info('Migrating documentary subseries...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('familias')->get();

            foreach ($oldData as $row) {
                DB::table('documentary_subseries')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'code' => $row->codFamilia ?? $row->codigo ?? 'SS' . str_pad($row->id, 3, '0', STR_PAD_LEFT),
                        'name' => $row->detfamilia ?? $row->nomFamilia ?? $row->nombre ?? 'Sin nombre',
                        'description' => $row->descripcion ?? null,
                        'documentary_series_id' => $row->segmento_id ?? $row->idSegmento ?? 1,
                        'retention_years' => $row->anios_retencion ?? null,
                        'final_disposition' => $row->disposicion_final ?? null,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' documentary subseries');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate documentary subseries: ' . $e->getMessage());
        }
    }

    protected function migrateDocumentaryClasses(): void
    {
        $this->command->info('Migrating documentary classes...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('clases')->get();

            foreach ($oldData as $row) {
                DB::table('documentary_classes')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'code' => $row->codClase ?? $row->codigo ?? null,
                        'name' => $row->detclase ?? $row->nomClase ?? $row->nombre ?? 'Sin nombre',
                        'documentary_subseries_id' => $row->familia_id ?? $row->idFamilia ?? 1,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' documentary classes');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate documentary classes: ' . $e->getMessage());
        }
    }

    protected function migrateDocumentTypes(): void
    {
        $this->command->info('Migrating document types from tipoprocesos...');

        try {
            // Los tipos documentales están en la tabla tipoprocesos
            $oldData = DB::connection($this->oldConnection)->table('tipoprocesos')->get();

            foreach ($oldData as $row) {
                // El campo 'subserie' en tipoprocesos contiene el ID de la familia/subserie
                $subseriesId = $row->subserie ?? null;

                // Verificar que la subserie existe
                if ($subseriesId && !DB::table('documentary_subseries')->where('id', $subseriesId)->exists()) {
                    $subseriesId = null;
                }

                DB::table('document_types')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'code' => null, // tipoprocesos no tiene código separado
                        'name' => $row->dettipoproceso ?? $row->nomTipoProceso ?? 'Sin nombre',
                        'documentary_subseries_id' => $subseriesId,
                        'is_active' => true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' document types');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate document types: ' . $e->getMessage());
        }
    }

    protected function migrateStorageMediums(): void
    {
        $this->command->info('Migrating storage mediums...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('fuentes')->get();

            foreach ($oldData as $row) {
                DB::table('storage_mediums')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->detfuente ?? $row->nomFuente ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'description' => $row->descripcion ?? null,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' storage mediums');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate storage mediums: ' . $e->getMessage());
        }
    }

    protected function migrateDocumentPurposes(): void
    {
        $this->command->info('Migrating document purposes...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('modalidades')->get();

            foreach ($oldData as $row) {
                DB::table('document_purposes')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->detmodalidad ?? $row->nomModalidad ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'description' => $row->descripcion ?? null,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' document purposes');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate document purposes: ' . $e->getMessage());
        }
    }

    protected function migrateProcessTypes(): void
    {
        $this->command->info('Migrating process types...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('tipoprocesos')->get();

            foreach ($oldData as $row) {
                DB::table('process_types')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->dettipoproceso ?? $row->nomTipoProceso ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'description' => $row->descripcion ?? null,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' process types');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate process types: ' . $e->getMessage());
        }
    }

    protected function migrateValidityStatuses(): void
    {
        $this->command->info('Migrating validity statuses...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('estadovigencias')->get();

            foreach ($oldData as $row) {
                DB::table('validity_statuses')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->detestadovigencia ?? $row->nomEstadoVigencia ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'description' => $row->descripcion ?? null,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' validity statuses');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate validity statuses: ' . $e->getMessage());
        }
    }

    protected function migratePriorityLevels(): void
    {
        $this->command->info('Migrating priority levels...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('tipoprioridades')->get();

            foreach ($oldData as $row) {
                DB::table('priority_levels')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->detprioridad ?? $row->nomTipoPrioridad ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'description' => $row->descripcion ?? null,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' priority levels');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate priority levels: ' . $e->getMessage());
        }
    }

    protected function migrateProjects(): void
    {
        $this->command->info('Migrating projects...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('requiproyectos')->get();

            foreach ($oldData as $row) {
                DB::table('projects')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->detproyecto ?? $row->nomRequiProyecto ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'description' => $row->descripcion ?? null,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' projects');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate projects: ' . $e->getMessage());
        }
    }

    protected function migrateActClassifications(): void
    {
        $this->command->info('Migrating act classifications...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('clasificaciones')->get();

            foreach ($oldData as $row) {
                $classification = ActClassification::updateOrCreate(
                    ['slug' => $row->slug ?? Str::slug($row->nom_clasificacion ?? 'classification-' . $row->id)],
                    [
                        'name' => $row->nom_clasificacion ?? 'Sin nombre',
                        'is_active' => true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );

                $this->clasificacionMapping[$row->id] = $classification->id;
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' act classifications');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate act classifications: ' . $e->getMessage());

            // Seed default classifications if migration fails
            $defaults = [
                ['name' => 'Resolución', 'slug' => 'resolucion'],
                ['name' => 'Decreto', 'slug' => 'decreto'],
                ['name' => 'Acuerdo', 'slug' => 'acuerdo'],
                ['name' => 'Circular', 'slug' => 'circular'],
                ['name' => 'Otro', 'slug' => 'otro'],
            ];

            foreach ($defaults as $index => $data) {
                $classification = ActClassification::firstOrCreate(
                    ['slug' => $data['slug']],
                    ['name' => $data['name'], 'is_active' => true]
                );
                $this->clasificacionMapping[$index + 1] = $classification->id;
            }

            $this->command->info('  - Created ' . count($defaults) . ' default act classifications');
        }
    }

    protected function migrateUsers(): void
    {
        $this->command->info('Migrating users...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('users')->get();

            // Build area mapping from organizational units
            $orgUnits = OrganizationalUnit::pluck('id', 'name')->toArray();

            foreach ($oldData as $row) {
                // Skip if user already exists
                $existingUser = User::where('email', $row->email)->first();
                if ($existingUser) {
                    $this->userMapping[$row->id] = $existingUser->id;
                    continue;
                }

                // Map old area to new organizational unit
                $organizationalUnitId = $this->areaMapping[$row->areas_id] ?? null;

                $user = User::create([
                    'name' => $row->name,
                    'last_name' => $row->apellido,
                    'email' => $row->email,
                    'phone' => $row->telefono,
                    'document_number' => $row->documento,
                    'organizational_unit_id' => $organizationalUnitId,
                    'password' => $row->password, // Keep existing hashed password
                    'email_verified_at' => $row->email_verified_at,
                    'remember_token' => $row->remember_token,
                    'avatar' => $row->avatar,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $this->userMapping[$row->id] = $user->id;
            }

            $this->command->info('  - Migrated ' . count($this->userMapping) . ' users');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate users: ' . $e->getMessage());
        }
    }

    protected function migrateAdministrativeActs(): void
    {
        $this->command->info('Migrating administrative acts...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('actos')->get();

            $count = 0;
            foreach ($oldData as $row) {
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
                    $attachments = json_decode($row->pdf_acto_administrativo, true);
                }

                // Generate unique slug using ID
                $baseSlug = Str::slug($row->objeto ?? 'acto');
                $slug = $baseSlug . '-' . $row->id . '-' . uniqid();

                AdministrativeAct::create([
                    'user_id' => $userId,
                    'organizational_unit_id' => $organizationalUnitId,
                    'act_classification_id' => $classificationId,
                    'filing_number' => $row->consecutivo ?? null,
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
            }

            $this->command->info('  - Migrated ' . $count . ' administrative acts');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate administrative acts: ' . $e->getMessage());
        }
    }

    protected function migrateInventoryRecords(): void
    {
        $this->command->info('Migrating inventory records...');

        try {
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
        // Map relationships
        $organizationalUnitId = $this->areaMapping[$row->area_id] ?? null;
        $userId = $this->userMapping[$row->user_id] ?? null;

        // If organizational unit not found, try to find or create default
        if (!$organizationalUnitId) {
            // Get default entity first
            $defaultEntity = DB::table('entities')->where('code', '000')->first();
            if (!$defaultEntity) {
                DB::table('entities')->insert([
                    'name' => 'SIN DEPENDENCIA',
                    'code' => '000',
                    'slug' => 'sin-dependencia',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $defaultEntity = DB::table('entities')->where('code', '000')->first();
            }

            $defaultUnit = OrganizationalUnit::firstOrCreate(
                ['slug' => 'unidad-sin-asignar'],
                ['name' => 'Unidad Sin Asignar', 'code' => 'USA', 'entity_id' => $defaultEntity->id]
            );
            $organizationalUnitId = $defaultUnit->id;
        }

        // Parse dates (format: yyyy-mm-dd)
        $startDate = $this->parseDate($row->fechaInicial);
        $endDate = $this->parseDate($row->fechaFinal);

        // Parse folios (can be ranges like "1-72" or "401-600")
        $folios = $this->parseFolios($row->folio);

        // Build title from nota or generate one
        $title = !empty($row->nota) ? $row->nota : "Registro {$row->id}";

        // Truncate title if too long
        if (strlen($title) > 255) {
            $title = substr($title, 0, 252) . '...';
        }

        // Build description
        $description = null;
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
        if (!empty($descriptionParts)) {
            $description = implode("\n", $descriptionParts);
        }

        // Parse attachments (archivo_pdf might be JSON or a single path)
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

        // Check if record already exists
        $existing = InventoryRecord::where('reference_code', $referenceCode)->first();
        if ($existing) {
            return;
        }

        InventoryRecord::create([
            'organizational_unit_id' => $organizationalUnitId,
            'inventory_purpose' => 'inventarios_individuales', // Default para registros migrados
            'documentary_series_id' => $row->segmento_id ?? 1,
            'documentary_subseries_id' => $row->familias_id,
            'title' => $title,
            'description' => $description,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'has_start_date' => !empty($startDate),
            'has_end_date' => !empty($endDate),
            'box' => $row->caja,
            'folder' => $row->carpeta,
            'volume' => $row->tomo,
            'folios' => $folios,
            'storage_medium_id' => $row->fuente_id,
            'storage_unit_type' => null,
            'storage_unit_quantity' => null,
            'priority_level_id' => $row->tipoprioridade_id,
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
