<?php

namespace Database\Seeders;

use App\Models\DocumentPurpose;
use App\Models\Entity;
use App\Models\OrganizationalUnit;
use App\Models\PriorityLevel;
use App\Models\ProcessType;
use App\Models\StorageMedium;
use App\Models\User;
use App\Models\ValidityStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create default roles
        $this->createRoles();

        // Create super admin user
        $this->createSuperAdmin();

        // Create default catalogs
        $this->createDefaultCatalogs();
    }

    protected function createRoles(): void
    {
        $roles = [
            'super_admin' => 'Super Administrador',
            'admin' => 'Administrador',
            'supervisor' => 'Supervisor',
            'user' => 'Usuario',
        ];

        foreach ($roles as $name => $description) {
            Role::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        $this->command->info('Roles created successfully.');
    }

    protected function createSuperAdmin(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@inventario.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $admin->assignRole('super_admin');

        $this->command->info('Super admin created: admin@inventario.local / password');
    }

    protected function createDefaultCatalogs(): void
    {
        // Storage Mediums (Soportes)
        $storageMediums = [
            ['name' => 'Papel', 'code' => 'PAP'],
            ['name' => 'Digital', 'code' => 'DIG'],
            ['name' => 'Mixto', 'code' => 'MIX'],
            ['name' => 'Microfilm', 'code' => 'MIC'],
        ];

        foreach ($storageMediums as $item) {
            StorageMedium::firstOrCreate(['code' => $item['code']], $item);
        }

        // Document Purposes (Objetos)
        $purposes = [
            ['name' => 'Administrativo', 'code' => 'ADM'],
            ['name' => 'Legal', 'code' => 'LEG'],
            ['name' => 'Contable', 'code' => 'CON'],
            ['name' => 'Técnico', 'code' => 'TEC'],
            ['name' => 'Histórico', 'code' => 'HIS'],
        ];

        foreach ($purposes as $item) {
            DocumentPurpose::firstOrCreate(['code' => $item['code']], $item);
        }

        // Process Types (Tipos de Proceso)
        $processTypes = [
            ['name' => 'Estratégico', 'code' => 'EST'],
            ['name' => 'Misional', 'code' => 'MIS'],
            ['name' => 'Apoyo', 'code' => 'APO'],
            ['name' => 'Evaluación', 'code' => 'EVA'],
        ];

        foreach ($processTypes as $item) {
            ProcessType::firstOrCreate(['code' => $item['code']], $item);
        }

        // Validity Statuses (Estados de Vigencia)
        $validityStatuses = [
            ['name' => 'Vigente', 'code' => 'VIG'],
            ['name' => 'No Vigente', 'code' => 'NVI'],
            ['name' => 'En Proceso', 'code' => 'PRO'],
        ];

        foreach ($validityStatuses as $item) {
            ValidityStatus::firstOrCreate(['code' => $item['code']], $item);
        }

        // Priority Levels (Niveles de Prioridad)
        $priorityLevels = [
            ['name' => 'Alta', 'code' => 'ALT'],
            ['name' => 'Media', 'code' => 'MED'],
            ['name' => 'Baja', 'code' => 'BAJ'],
        ];

        foreach ($priorityLevels as $item) {
            PriorityLevel::firstOrCreate(['code' => $item['code']], $item);
        }

        // Default Entity
        $entity = Entity::firstOrCreate(
            ['slug' => 'entidad-principal'],
            [
                'name' => 'Entidad Principal',
                'code' => 'EP001',
            ]
        );

        // Default Organizational Unit
        OrganizationalUnit::firstOrCreate(
            ['slug' => 'unidad-principal'],
            [
                'name' => 'Unidad Principal',
                'code' => 'UP001',
                'entity_id' => $entity->id,
            ]
        );

        $this->command->info('Default catalogs created successfully.');
    }
}
