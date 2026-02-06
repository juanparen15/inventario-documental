<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Available seeders:
     * - ProductionDataSeeder: Seeds all base data (roles, entities, series, users)
     * - LegacyDataSeeder: Seeds inventory records and administrative acts
     *
     * Usage:
     * - Fresh install: php artisan migrate:fresh --seed
     * - Only base data: php artisan db:seed --class=ProductionDataSeeder
     * - Only legacy records: php artisan db:seed --class=LegacyDataSeeder
     * - Full data: php artisan db:seed (runs both)
     */
    public function run(): void
    {
        $this->call([
            ProductionDataSeeder::class,
            DataMigrationSeeder::class,
            // LegacyDataSeeder::class, // Uncomment if you want to seed legacy inventory records and acts
        ]);
    }
}
