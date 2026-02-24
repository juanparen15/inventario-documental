<?php

namespace App\Console\Commands;

use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\OrganizationalUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Aplica el mapeo exacto del Cuadro de Clasificación Documental (Excel "Implementar"):
 *
 * Solo DA tiene Decretos (serie 03, subserie 03).
 * Todas las 10 dependencias tienen Circulares y Resoluciones (serie 03).
 * Todas las 10 dependencias tienen Comunicaciones Externas e Internas (serie 24).
 */
class ApplyCcdExcelMappingCommand extends Command
{
    protected $signature = 'ccd:apply-excel-mapping
                            {--dry-run : Muestra los cambios sin guardarlos}';

    protected $description = 'Aplica el mapeo exacto del Excel CCD para series 03 y 24 en las 10 dependencias principales';

    /**
     * Mapeo exacto del Excel (hoja "Implementar"):
     * codUnidad => [codSerie => [codSubserie, ...]]
     */
    private array $mapping = [
        'DA'   => ['03' => ['02', '03', '04'], '24' => ['01', '02']], // DA tiene Circulares, Decretos, Resoluciones
        'SGA'  => ['03' => ['02', '04'],       '24' => ['01', '02']], // Sin Decretos
        'SGCC' => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SH'   => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SP'   => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SOP'  => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SDSC' => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'CI'   => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'UMATA'=> ['03' => ['02', '04'],       '24' => ['01', '02']],
        'ITT'  => ['03' => ['02', '04'],       '24' => ['01', '02']],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo dry-run: no se guardarán cambios.');
        }

        // Cargar modelos necesarios
        $series = DocumentarySeries::ccd()
            ->whereIn('code', ['03', '24'])
            ->get()
            ->keyBy('code');

        if ($series->count() < 2) {
            $this->error('No se encontraron las series 03 y/o 24 en contexto CCD.');
            return self::FAILURE;
        }

        $subseries = DocumentarySubseries::ccd()
            ->whereIn('documentary_series_id', $series->pluck('id'))
            ->get()
            ->groupBy('documentary_series_id'); // [serie_id => Collection<Subserie>]

        $units = OrganizationalUnit::whereIn('code', array_keys($this->mapping))
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('code');

        $created = 0;
        $deleted = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($this->mapping as $unitCode => $seriesMap) {
                $unit = $units->get($unitCode);
                if (! $unit) {
                    $this->warn("  Unidad '{$unitCode}' no encontrada, se omite.");
                    continue;
                }

                foreach ($seriesMap as $serieCode => $allowedSubCodes) {
                    $serie = $series->get($serieCode);
                    if (! $serie) {
                        $this->warn("  Serie '{$serieCode}' no encontrada, se omite.");
                        continue;
                    }

                    $allSubsOfSerie = $subseries->get($serie->id, collect());

                    // IDs de subseries permitidas para esta unidad+serie
                    $allowedSubIds = $allSubsOfSerie
                        ->whereIn('code', $allowedSubCodes)
                        ->pluck('id');

                    // IDs de todas las subseries de esta serie
                    $allSubIds = $allSubsOfSerie->pluck('id');

                    // ── Eliminar entradas de subseries NO permitidas ──────────
                    $toDelete = CcdEntry::where('organizational_unit_id', $unit->id)
                        ->where('documentary_series_id', $serie->id)
                        ->whereNotNull('documentary_subseries_id')
                        ->whereNotIn('documentary_subseries_id', $allowedSubIds)
                        ->get();

                    foreach ($toDelete as $entry) {
                        $subCode = $allSubsOfSerie->firstWhere('id', $entry->documentary_subseries_id)?->code ?? '?';
                        $this->line("  <fg=red>ELIMINAR</> {$unitCode} | serie {$serieCode} | sub {$subCode}");
                        if (! $dryRun) {
                            $entry->delete();
                        }
                        $deleted++;
                    }

                    // ── Crear entradas faltantes ──────────────────────────────
                    foreach ($allowedSubIds as $subId) {
                        $exists = CcdEntry::where('organizational_unit_id', $unit->id)
                            ->where('documentary_series_id', $serie->id)
                            ->where('documentary_subseries_id', $subId)
                            ->exists();

                        if (! $exists) {
                            $subCode = $allSubsOfSerie->firstWhere('id', $subId)?->code ?? '?';
                            $this->line("  <fg=green>CREAR</> {$unitCode} | serie {$serieCode} | sub {$subCode}");
                            if (! $dryRun) {
                                CcdEntry::create([
                                    'organizational_unit_id'   => $unit->id,
                                    'documentary_series_id'    => $serie->id,
                                    'documentary_subseries_id' => $subId,
                                ]);
                            }
                            $created++;
                        } else {
                            $skipped++;
                        }
                    }

                    // ── Asegurar la entrada de la serie sin subserie ──────────
                    $serieEntry = CcdEntry::where('organizational_unit_id', $unit->id)
                        ->where('documentary_series_id', $serie->id)
                        ->whereNull('documentary_subseries_id')
                        ->exists();

                    if (! $serieEntry) {
                        $this->line("  <fg=green>CREAR</> {$unitCode} | serie {$serieCode} | (sin subserie)");
                        if (! $dryRun) {
                            CcdEntry::create([
                                'organizational_unit_id'   => $unit->id,
                                'documentary_series_id'    => $serie->id,
                                'documentary_subseries_id' => null,
                            ]);
                        }
                        $created++;
                    }
                }
            }

            if (! $dryRun) {
                DB::commit();
            } else {
                DB::rollBack();
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Completado: {$created} creadas | {$deleted} eliminadas | {$skipped} ya correctas.");

        return self::SUCCESS;
    }
}
