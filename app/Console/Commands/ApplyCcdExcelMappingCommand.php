<?php

namespace App\Console\Commands;

use App\Models\CcdEntry;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\OrganizationalUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Resetea todas las ccd_entries de series CCD y recrea SOLO las combinaciones
 * definidas en el Excel "Series_V1 - ERIKA.xlsx", hoja "Implementar".
 *
 * Las entradas de series FUID no se tocan (InventoryRecord las usa con context='fuid').
 *
 * Mapeo exacto:
 *   DA   → 03 (Circulares, Decretos, Resoluciones) + 24 (Com.Ext, Com.Int)
 *   Demás 9 dependencias → 03 (Circulares, Resoluciones) + 24 (Com.Ext, Com.Int)
 */
class ApplyCcdExcelMappingCommand extends Command
{
    protected $signature = 'ccd:apply-excel-mapping
                            {--dry-run : Muestra los cambios sin guardarlos}';

    protected $description = 'Resetea ccd_entries CCD y aplica el mapeo exacto del Excel (series 03 y 24, 10 dependencias)';

    /** Mapeo exacto del Excel: codUnidad => [codSerie => [codSubserie, ...]] */
    private array $mapping = [
        'DA'    => ['03' => ['02', '03', '04'], '24' => ['01', '02']],
        'SGA'   => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SGCC'  => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SH'    => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SP'    => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SOP'   => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'SDSC'  => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'CI'    => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'UMATA' => ['03' => ['02', '04'],       '24' => ['01', '02']],
        'ITT'   => ['03' => ['02', '04'],       '24' => ['01', '02']],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo dry-run: no se guardarán cambios.');
        }

        // ── Cargar series CCD (todas, para el reset global) ───────────────
        $allCcdSeriesIds = DocumentarySeries::ccd()->pluck('id');

        if ($allCcdSeriesIds->isEmpty()) {
            $this->error('No se encontraron series CCD en la base de datos.');
            return self::FAILURE;
        }

        // ── Cargar las 2 series del Excel y sus subseries ─────────────────
        $excelSeries = DocumentarySeries::ccd()
            ->whereIn('code', ['03', '24'])
            ->get()
            ->keyBy('code');

        if ($excelSeries->count() < 2) {
            $this->error('No se encontraron las series 03 y/o 24 en contexto CCD.');
            return self::FAILURE;
        }

        $subseries = DocumentarySubseries::ccd()
            ->whereIn('documentary_series_id', $excelSeries->pluck('id'))
            ->get()
            ->groupBy('documentary_series_id');

        $units = OrganizationalUnit::whereIn('code', array_keys($this->mapping))
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('code');

        DB::beginTransaction();

        try {
            // ── PASO 1: eliminar TODAS las ccd_entries de series CCD ──────
            $totalToDelete = CcdEntry::whereIn('documentary_series_id', $allCcdSeriesIds)->count();
            $this->info("Eliminando {$totalToDelete} entradas CCD existentes...");

            if (! $dryRun) {
                CcdEntry::whereIn('documentary_series_id', $allCcdSeriesIds)->delete();
            }

            // ── PASO 2: crear SOLO las entradas del Excel ─────────────────
            $created = 0;

            foreach ($this->mapping as $unitCode => $seriesMap) {
                $unit = $units->get($unitCode);
                if (! $unit) {
                    $this->warn("  Unidad '{$unitCode}' no encontrada, se omite.");
                    continue;
                }

                foreach ($seriesMap as $serieCode => $allowedSubCodes) {
                    $serie = $excelSeries->get($serieCode);
                    if (! $serie) {
                        $this->warn("  Serie '{$serieCode}' no encontrada, se omite.");
                        continue;
                    }

                    $allSubsOfSerie = $subseries->get($serie->id, collect());

                    // Entrada nivel-serie (sin subserie) — hace que la serie
                    // aparezca en el select del formulario de actos administrativos
                    $this->line("  <fg=green>CREAR</> {$unitCode} | serie {$serieCode} | (nivel serie)");
                    if (! $dryRun) {
                        CcdEntry::create([
                            'organizational_unit_id'   => $unit->id,
                            'documentary_series_id'    => $serie->id,
                            'documentary_subseries_id' => null,
                        ]);
                    }
                    $created++;

                    // Entradas de subseries permitidas
                    $allowedSubs = $allSubsOfSerie->whereIn('code', $allowedSubCodes);

                    foreach ($allowedSubs as $sub) {
                        $this->line("  <fg=green>CREAR</> {$unitCode} | serie {$serieCode} | sub {$sub->code} - {$sub->name}");
                        if (! $dryRun) {
                            CcdEntry::create([
                                'organizational_unit_id'   => $unit->id,
                                'documentary_series_id'    => $serie->id,
                                'documentary_subseries_id' => $sub->id,
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
        $this->info("Completado: {$totalToDelete} entradas CCD eliminadas | {$created} entradas del Excel creadas.");

        return self::SUCCESS;
    }
}
