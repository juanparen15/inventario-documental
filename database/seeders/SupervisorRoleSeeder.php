<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea el rol 'supervisor' con los mismos permisos de visualización que super_admin,
 * pero sin acceso a configuración del sistema (usuarios, series, subseries, unidades).
 *
 * Ejecutar: php artisan db:seed --class=SupervisorRoleSeeder
 */
class SupervisorRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);

        // Permisos de lectura y gestión de inventario y actos
        $viewPermissions = [
            'view_administrative::act',
            'view_any_administrative::act',
            'create_administrative::act',
            'update_administrative::act',
            'delete_administrative::act',
            'restore_administrative::act',
            'view_inventory::record',
            'view_any_inventory::record',
        ];

        foreach ($viewPermissions as $permName) {
            $perm = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            if (! $role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }

        $this->command->info('Rol supervisor creado/actualizado correctamente.');
    }
}
