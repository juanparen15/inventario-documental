<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crea y asigna los permisos de Shield para páginas custom de Filament
 * que no se generan automáticamente con shield:generate.
 *
 * Ejecutar: php artisan db:seed --class=ShieldPagePermissionsSeeder
 *
 * Necesario cada vez que se agrega una nueva página Filament al panel.
 */
class ShieldPagePermissionsSeeder extends Seeder
{
    /**
     * Páginas custom y los roles que deben tener acceso a cada una.
     * Clave = nombre del permiso Shield (page_{NombreClase})
     * Valor = array de roles que reciben el permiso
     */
    private array $pagePermissions = [
        'page_ImportPermissionsPage' => ['super_admin'],
        'page_MonthlyReportPage'     => ['super_admin', 'supervisor'],
        'page_ImportErrors'          => ['super_admin', 'supervisor'],
        'page_CambiarPassword'       => ['super_admin', 'supervisor', 'panel_user'],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->pagePermissions as $permName => $roleNames) {
            $permission = Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'web',
            ]);

            foreach ($roleNames as $roleName) {
                $role = Role::where('name', $roleName)->first();

                if (! $role) {
                    $this->command->warn("Rol '{$roleName}' no encontrado, omitiendo.");
                    continue;
                }

                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                    $this->command->line("  ✓ {$roleName} → {$permName}");
                } else {
                    $this->command->line("  · {$roleName} → {$permName} (ya existía)");
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Permisos de páginas sincronizados correctamente.');
    }
}
