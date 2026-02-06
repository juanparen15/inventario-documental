<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProductionDataSeeder extends Seeder
{
    /**
     * Seeds production data from JSON files exported from the current database.
     *
     * This seeder loads:
     * - Roles (super_admin, usuario)
     * - Entities, Organizational units
     * - Documentary series, subseries, CCD entries
     * - Storage mediums, priority levels, act classifications
     * - Users with role assignments
     *
     * Run: php artisan db:seed --class=ProductionDataSeeder
     */
    public function run(): void
    {
        $this->command->info('Seeding production data from JSON files...');

        // Disable foreign key checks for truncation
        Schema::disableForeignKeyConstraints();

        // 1. Create roles
        $this->seedRoles();

        // 2. Seed catalog tables from JSON
        $this->seedFromJson('entities', 'entities.json');
        $this->seedFromJson('organizational_units', 'organizational_units.json');
        $this->seedFromJson('documentary_series', 'documentary_series.json');
        $this->seedFromJson('documentary_subseries', 'documentary_subseries.json');
        $this->seedFromJson('ccd_entries', 'ccd_entries.json');
        $this->seedFromJson('storage_mediums', 'storage_mediums.json');
        $this->seedFromJson('priority_levels', 'priority_levels.json');
        $this->seedFromJson('act_classifications', 'act_classifications.json');

        // 3. Seed users with roles
        $this->seedUsers();

        // 4. Generate Shield permissions
        $this->generatePermissions();

        Schema::enableForeignKeyConstraints();

        $this->command->info('Production data seeded successfully!');
    }

    protected function seedRoles(): void
    {
        $this->command->info('Creating roles...');

        // Clear role-related tables
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('roles')->truncate();

        // Create roles
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'usuario', 'guard_name' => 'web']);

        $this->command->info('  - Roles: super_admin, usuario');
    }

    protected function seedFromJson(string $table, string $filename): void
    {
        $path = database_path("seeders/data/{$filename}");

        if (!file_exists($path)) {
            $this->command->warn("  - Skipping {$table}: {$filename} not found");
            return;
        }

        $data = json_decode(file_get_contents($path), true);

        if (empty($data)) {
            $this->command->warn("  - Skipping {$table}: no data");
            return;
        }

        // Clean data for insert
        $cleanData = array_map(function ($row) {
            unset($row['deleted_at']);
            return $row;
        }, $data);

        DB::table($table)->truncate();

        // Insert in chunks
        foreach (array_chunk($cleanData, 500) as $chunk) {
            DB::table($table)->insert($chunk);
        }

        $this->command->info("  - {$table}: " . count($data) . " records");
    }

    protected function seedUsers(): void
    {
        $this->command->info('Creating users...');

        $path = database_path('seeders/data/users.json');

        if (!file_exists($path)) {
            $this->createDefaultAdmin();
            return;
        }

        $users = json_decode(file_get_contents($path), true);

        if (empty($users)) {
            $this->createDefaultAdmin();
            return;
        }

        DB::table('users')->truncate();

        foreach ($users as $userData) {
            DB::table('users')->insert([
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'organizational_unit_id' => $userData['organizational_unit_id'],
                'email_verified_at' => now(),
                'created_at' => $userData['created_at'] ?? now(),
                'updated_at' => now(),
            ]);

            // Assign role
            $user = \App\Models\User::find($userData['id']);
            if ($userData['email'] === 'sistemas@puertoboyaca-boyaca.gov.co') {
                $user->assignRole('super_admin');
            } else {
                $user->assignRole('usuario');
            }
        }

        $this->command->info("  - users: " . count($users) . " records");
    }

    protected function createDefaultAdmin(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super_admin');
        $this->command->info('  - Default admin created: admin@example.com / password');
    }

    protected function generatePermissions(): void
    {
        $this->command->info('Generating permissions...');

        try {
            \Artisan::call('shield:generate', [
                '--all' => true,
                '--option' => 'permissions',
                '--panel' => 'admin',
            ]);

            // Assign basic permissions to usuario role
            $usuarioRole = Role::where('name', 'usuario')->first();
            if ($usuarioRole) {
                $permissions = Permission::whereIn('name', [
                    'view_inventory::record',
                    'view_any_inventory::record',
                    'create_inventory::record',
                    'update_inventory::record',
                    'delete_inventory::record',
                    'view_administrative::act',
                    'view_any_administrative::act',
                    'create_administrative::act',
                    'update_administrative::act',
                    'delete_administrative::act',
                ])->get();
                $usuarioRole->syncPermissions($permissions);
            }
            $this->command->info('  - Permissions generated and assigned');
        } catch (\Exception $e) {
            $this->command->warn('  - Could not generate permissions: ' . $e->getMessage());
        }
    }
}
