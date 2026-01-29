<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataMigrationSeeder extends Seeder
{
    protected $oldConnection = 'old_mysql';

    public function run(): void
    {
        $this->command->info('Starting data migration from old database...');

        // Migrate entities (dependencias)
        $this->migrateEntities();

        // Migrate organizational units (areas)
        $this->migrateOrganizationalUnits();

        // Migrate documentary series (segmentos)
        $this->migrateDocumentarySeries();

        // Migrate documentary subseries (familias)
        $this->migrateDocumentarySubseries();

        // Migrate documentary classes (clases)
        $this->migrateDocumentaryClasses();

        // Migrate document types (productos)
        $this->migrateDocumentTypes();

        // Migrate storage mediums (fuentes)
        $this->migrateStorageMediums();

        // Migrate document purposes (modalidades)
        $this->migrateDocumentPurposes();

        // Migrate process types (tipoprocesos)
        $this->migrateProcessTypes();

        // Migrate validity statuses (estadovigencias)
        $this->migrateValidityStatuses();

        // Migrate priority levels (tipoprioridades)
        $this->migratePriorityLevels();

        // Migrate projects (requiproyectos)
        $this->migrateProjects();

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
                        'name' => $row->nomDependencia ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'slug' => Str::slug($row->nomDependencia ?? $row->nombre ?? 'entity-' . $row->id),
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
            $oldData = DB::connection($this->oldConnection)->table('areas')->get();

            foreach ($oldData as $row) {
                DB::table('organizational_units')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'name' => $row->nomArea ?? $row->nombre ?? 'Sin nombre',
                        'code' => $row->codigo ?? null,
                        'slug' => Str::slug($row->nomArea ?? $row->nombre ?? 'unit-' . $row->id),
                        'entity_id' => $row->dependencia_id ?? $row->idDependencia ?? 1,
                        'is_active' => $row->estado ?? true,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            }

            $this->command->info('  - Migrated ' . $oldData->count() . ' organizational units');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not migrate organizational units: ' . $e->getMessage());
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
                        'name' => $row->nomSegmento ?? $row->nombre ?? 'Sin nombre',
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
                        'name' => $row->nomFamilia ?? $row->nombre ?? 'Sin nombre',
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
                        'name' => $row->nomClase ?? $row->nombre ?? 'Sin nombre',
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
        $this->command->info('Migrating document types...');

        try {
            $oldData = DB::connection($this->oldConnection)->table('productos')->get();

            foreach ($oldData as $row) {
                DB::table('document_types')->updateOrInsert(
                    ['id' => $row->id],
                    [
                        'code' => $row->codProducto ?? $row->codigo ?? null,
                        'name' => $row->nomProducto ?? $row->nombre ?? 'Sin nombre',
                        'documentary_class_id' => $row->clase_id ?? $row->idClase ?? 1,
                        'is_active' => $row->estado ?? true,
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
                        'name' => $row->nomFuente ?? $row->nombre ?? 'Sin nombre',
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
                        'name' => $row->nomModalidad ?? $row->nombre ?? 'Sin nombre',
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
                        'name' => $row->nomTipoProceso ?? $row->nombre ?? 'Sin nombre',
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
                        'name' => $row->nomEstadoVigencia ?? $row->nombre ?? 'Sin nombre',
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
                        'name' => $row->nomTipoPrioridad ?? $row->nombre ?? 'Sin nombre',
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
                        'name' => $row->nomRequiProyecto ?? $row->nombre ?? 'Sin nombre',
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
}
