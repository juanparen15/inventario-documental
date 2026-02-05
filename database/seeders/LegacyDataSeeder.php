<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyDataSeeder extends Seeder
{
    /**
     * Seeds inventory records and administrative acts from JSON files.
     *
     * Run this AFTER ProductionDataSeeder:
     * php artisan db:seed --class=LegacyDataSeeder
     */
    public function run(): void
    {
        $this->command->info('Seeding legacy data (inventory records & administrative acts)...');

        Schema::disableForeignKeyConstraints();

        $this->seedFromJson('inventory_records', 'inventory_records.json');
        $this->seedFromJson('administrative_acts', 'administrative_acts.json');

        Schema::enableForeignKeyConstraints();

        $this->command->info('Legacy data seeded successfully!');
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

        // Clean data
        $cleanData = array_map(function ($row) {
            unset($row['deleted_at']);
            return $row;
        }, $data);

        DB::table($table)->truncate();

        // Insert in chunks (important for large datasets)
        $chunks = array_chunk($cleanData, 500);
        $bar = $this->command->getOutput()->createProgressBar(count($chunks));
        $bar->start();

        foreach ($chunks as $chunk) {
            try {
                DB::table($table)->insert($chunk);
            } catch (\Exception $e) {
                // Try inserting one by one to find problematic records
                foreach ($chunk as $row) {
                    try {
                        DB::table($table)->insert($row);
                    } catch (\Exception $e2) {
                        $this->command->warn("  - Skipped row ID {$row['id']}: " . $e2->getMessage());
                    }
                }
            }
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("  - {$table}: " . count($data) . " records");
    }
}
