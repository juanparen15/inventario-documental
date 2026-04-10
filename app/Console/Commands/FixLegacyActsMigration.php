<?php

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixLegacyActsMigration extends Command
{
    protected $signature = 'migration:fix-legacy-acts
                            {--dry-run : Muestra los cambios sin aplicarlos}
                            {--connection=old_mysql : Nombre de la conexión a la BD antigua}';

    protected $description = 'Corrige filing_number y vigencia de los actos administrativos migrados desde el sistema anterior (bdpaakgr)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $connection = $this->option('connection');

        if ($dryRun) {
            $this->warn('MODO DRY-RUN: No se aplicarán cambios.');
        }

        // Verificar conexión a BD antigua
        try {
            DB::connection($connection)->getPdo();
            $this->info("Conectado a la BD antigua: {$connection}");
        } catch (\Exception $e) {
            $this->error("No se puede conectar a la BD antigua ({$connection}): " . $e->getMessage());
            $this->line('Verifique las variables OLD_DB_* en su .env');
            return Command::FAILURE;
        }

        $this->info('Cargando registros del sistema anterior (actos)...');

        $oldActs = DB::connection($connection)->table('actos')->orderBy('id')->get();

        $this->info("  → {$oldActs->count()} actos encontrados en la BD antigua.");

        $updated   = 0;
        $notFound  = 0;
        $skipped   = 0;

        foreach ($oldActs as $old) {
            // Extraer vigencia del campo fecha_acto (formato dd-mm-yyyy)
            $vigencia       = null;
            $filingNumber   = $old->radicado ?? null;

            if (!empty($old->fecha_acto)) {
                $date = DateTime::createFromFormat('d-m-Y', $old->fecha_acto);
                if ($date) {
                    $vigencia = (int) $date->format('Y');
                }
            }

            // Fallback: extraer vigencia del año del created_at
            if (!$vigencia && !empty($old->created_at)) {
                $vigencia = (int) substr($old->created_at, 0, 4);
            }

            if (!$filingNumber || !$vigencia) {
                $this->warn("  ⚠ Acto ID={$old->id} sin radicado o fecha válida — omitido.");
                $skipped++;
                continue;
            }

            // Buscar el registro correspondiente en la nueva BD
            // Criterio primario: created_at exacto + subject
            $match = DB::table('administrative_acts')
                ->where('created_at', $old->created_at)
                ->where('subject', $old->objeto)
                ->whereNull('deleted_at')
                ->first();

            // Criterio secundario: solo por created_at si el subject fue truncado/modificado
            if (!$match && !empty($old->created_at)) {
                $match = DB::table('administrative_acts')
                    ->where('created_at', $old->created_at)
                    ->whereNull('deleted_at')
                    ->first();
            }

            if (!$match) {
                $this->warn("  ✗ No encontrado: acto ID={$old->id} radicado={$filingNumber} created_at={$old->created_at}");
                $notFound++;
                continue;
            }

            // Verificar si ya está correcto
            if ($match->filing_number === $filingNumber && (int)$match->vigencia === $vigencia) {
                $skipped++;
                continue;
            }

            $this->line(sprintf(
                '  → ID=%d | filing_number: %s → %s | vigencia: %s → %s',
                $match->id,
                $match->filing_number,
                $filingNumber,
                $match->vigencia,
                $vigencia
            ));

            if (!$dryRun) {
                DB::table('administrative_acts')
                    ->where('id', $match->id)
                    ->update([
                        'filing_number' => $filingNumber,
                        'vigencia'      => $vigencia,
                        'updated_at'    => now(),
                    ]);
            }

            $updated++;
        }

        $this->newLine();
        $this->info('=== Resumen ===');
        $this->line("  Actualizados : {$updated}");
        $this->line("  No encontrados: {$notFound}");
        $this->line("  Sin cambio   : {$skipped}");

        if ($notFound > 0) {
            $this->warn("Hay {$notFound} actos del sistema anterior que no pudieron ser emparejados.");
            $this->line('Revise el log o ejecute con --dry-run para ver los detalles.');
        }

        if ($dryRun) {
            $this->warn('DRY-RUN completado. Use sin --dry-run para aplicar los cambios.');
        } else {
            $this->info('Corrección aplicada exitosamente.');
        }

        return Command::SUCCESS;
    }
}
