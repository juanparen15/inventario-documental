<?php

namespace App\Console\Commands;

use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\OrganizationalUnit;
use Illuminate\Console\Command;

class AssignAllUnitsToSeriesCommand extends Command
{
    protected $signature = 'ccd:assign-all-units
                            {--dry-run : Muestra lo que se haría sin guardar cambios}';

    protected $description = 'Asigna todas las unidades organizacionales a todas las series y subseries (FUID y CCD)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $units = OrganizationalUnit::where('is_active', true)->get();
        $series = DocumentarySeries::withoutGlobalScopes()->get();
        $subseries = DocumentarySubseries::withoutGlobalScopes()->get();

        $this->info("Unidades organizacionales activas: {$units->count()}");
        $this->info("Series (FUID + CCD): {$series->count()}");
        $this->info("Subseries (FUID + CCD): {$subseries->count()}");

        if ($dryRun) {
            $this->warn('Modo dry-run: no se guardarán cambios.');
        }

        // --- Series ---
        $this->newLine();
        $this->info('Procesando series...');
        $seriesCreated = 0;
        $seriesExisting = 0;

        $bar = $this->output->createProgressBar($series->count() * $units->count());
        $bar->start();

        foreach ($series as $s) {
            foreach ($units as $unit) {
                $attributes = [
                    'organizational_unit_id'   => $unit->id,
                    'documentary_series_id'    => $s->id,
                    'documentary_subseries_id' => null,
                ];

                if (! $dryRun) {
                    [$entry, $wasCreated] = [
                        CcdEntry::firstOrCreate($attributes),
                        false,
                    ];
                    // firstOrCreate devuelve el modelo; comprobamos si fue creado comparando timestamps
                    $wasCreated = $entry->wasRecentlyCreated;
                } else {
                    $wasCreated = ! CcdEntry::where($attributes)->exists();
                }

                $wasCreated ? $seriesCreated++ : $seriesExisting++;
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Series: {$seriesCreated} entradas creadas, {$seriesExisting} ya existían.");

        // --- Subseries ---
        $this->newLine();
        $this->info('Procesando subseries...');
        $subseriesCreated = 0;
        $subseriesExisting = 0;

        $bar2 = $this->output->createProgressBar($subseries->count() * $units->count());
        $bar2->start();

        foreach ($subseries as $sub) {
            foreach ($units as $unit) {
                $attributes = [
                    'organizational_unit_id'   => $unit->id,
                    'documentary_series_id'    => $sub->documentary_series_id,
                    'documentary_subseries_id' => $sub->id,
                ];

                if (! $dryRun) {
                    $entry = CcdEntry::firstOrCreate($attributes);
                    $wasCreated = $entry->wasRecentlyCreated;
                } else {
                    $wasCreated = ! CcdEntry::where($attributes)->exists();
                }

                $wasCreated ? $subseriesCreated++ : $subseriesExisting++;
                $bar2->advance();
            }
        }

        $bar2->finish();
        $this->newLine();
        $this->line("  Subseries: {$subseriesCreated} entradas creadas, {$subseriesExisting} ya existían.");

        $this->newLine();
        $this->info('Proceso completado.');

        return self::SUCCESS;
    }
}
