<?php

namespace App\Console\Commands;

use App\Models\AdministrativeAct;
use App\Models\User;
use App\Notifications\MonthlyReportNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Genera y envía el informe mensual de actos administrativos.
 * Por defecto usa el mes anterior; se puede especificar mes/año.
 *
 * Uso:
 *   php artisan acts:monthly-report              → mes anterior
 *   php artisan acts:monthly-report --month=1 --year=2026
 *   php artisan acts:monthly-report --dry-run
 */
class SendMonthlyReport extends Command
{
    protected $signature = 'acts:monthly-report
                            {--month= : Mes (1-12). Por defecto el mes anterior.}
                            {--year=  : Año. Por defecto el año actual.}
                            {--dry-run : Muestra las estadísticas sin enviar correos.}';

    protected $description = 'Genera y envía el informe mensual de actos administrativos';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $month = (int) ($this->option('month') ?: Carbon::now()->subMonth()->month);
        $year  = (int) ($this->option('year')  ?: Carbon::now()->subMonth()->year);

        $this->info("Generando informe para {$month}/{$year}…");

        // ── Estadísticas del período ──────────────────────────────────────
        $acts = AdministrativeAct::with([
                'organizationalUnit.entity',
                'documentarySeries',
                'documentarySubseries',
            ])
            ->whereNull('deleted_at')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get();

        $totalActs = $acts->count();

        // Estructura: entity → unit → subserie → count
        $stats = [];
        foreach ($acts as $act) {
            $entity   = $act->organizationalUnit?->entity?->name  ?? 'Sin entidad';
            $unit     = $act->organizationalUnit?->name           ?? 'Sin unidad';
            $subserie = $act->documentarySubseries?->name
                ?? $act->documentarySeries?->name
                ?? 'Sin clasificación';

            $stats[$entity][$unit][$subserie] = ($stats[$entity][$unit][$subserie] ?? 0) + 1;
        }

        // ── Actos pendientes de PDF (sin límite de fecha) ─────────────────
        $pendingPdf = AdministrativeAct::with(['organizationalUnit'])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('attachments')->orWhere('attachments', '[]')->orWhere('attachments', '');
            })
            ->orderBy('created_at')
            ->get();

        // ── Salida en consola ─────────────────────────────────────────────
        $this->newLine();
        $this->table(['Entidad', 'Unidad', 'Subserie', 'Actos'], $this->flatStats($stats));
        $this->newLine();
        $this->info("Total del período: {$totalActs} actos.");
        $this->info("Pendientes de PDF: {$pendingPdf->count()} actos.");

        if ($dryRun) {
            $this->warn('Modo dry-run: no se envían correos.');
            return self::SUCCESS;
        }

        // ── Enviar a super_admin y supervisor ─────────────────────────────
        $recipients = User::role(['super_admin', 'supervisor'])->get();

        if ($recipients->isEmpty()) {
            $this->warn('No hay usuarios con rol super_admin o supervisor para notificar.');
            return self::SUCCESS;
        }

        foreach ($recipients as $user) {
            $user->notify(new MonthlyReportNotification(
                stats: $stats,
                totalActs: $totalActs,
                pendingPdf: $pendingPdf,
                month: $month,
                year: $year,
            ));
        }

        $this->info("Correo enviado a {$recipients->count()} destinatario(s).");

        return self::SUCCESS;
    }

    private function flatStats(array $stats): array
    {
        $rows = [];
        foreach ($stats as $entity => $units) {
            foreach ($units as $unit => $subseries) {
                foreach ($subseries as $subserie => $count) {
                    $rows[] = [$entity, $unit, $subserie, $count];
                }
            }
        }
        return $rows;
    }
}
