<?php

namespace App\Console\Commands;

use App\Models\AdministrativeAct;
use App\Models\User;
use App\Notifications\PdfDeadlineNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Revisa diariamente los actos sin PDF y envía notificaciones
 * a los días 15, 25 y cuando vence el plazo de 30 días.
 *
 * Destinatarios:
 *   - super_admin y supervisor  → resumen global
 *   - creador del acto          → solo sus propios actos
 */
class CheckPdfDeadlines extends Command
{
    protected $signature   = 'acts:check-pdf-deadlines {--dry-run : Muestra qué enviaría sin enviar correos}';
    protected $description = 'Envía notificaciones de actos administrativos con PDF pendiente (días 15, 25 y vencido)';

    /** Umbrales en días desde la creación. */
    private array $thresholds = [15, 25, 30];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Todos los actos sin PDF (ni regular ni confidencial) y sin soft-delete
        $pendingActs = AdministrativeAct::with(['organizationalUnit.entity', 'creator'])
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('attachments')
                  ->orWhere('attachments', '[]')
                  ->orWhere('attachments', '');
            })
            ->where(function ($q) {
                $q->whereNull('confidential_attachments')
                  ->orWhere('confidential_attachments', '[]')
                  ->orWhere('confidential_attachments', '');
            })
            ->get();

        if ($pendingActs->isEmpty()) {
            $this->info('No hay actos pendientes de PDF.');
            return self::SUCCESS;
        }

        foreach ($this->thresholds as $threshold) {
            $matching = $pendingActs->filter(function (AdministrativeAct $act) use ($threshold) {
                $days = (int) $act->created_at->diffInDays(now());

                // Ya notificado para este umbral → omitir
                $notified = $act->pdf_notified_days ?? [];
                if (in_array($threshold, $notified)) {
                    return false;
                }

                return match ($threshold) {
                    15 => $days >= 15 && $days < 25,
                    25 => $days >= 25 && $days < 30,
                    30 => $days >= 30,
                    default => false,
                };
            });

            if ($matching->isEmpty()) {
                $this->line("  Umbral {$threshold}d: sin actos nuevos.");
                continue;
            }

            $this->info("  Umbral {$threshold}d: {$matching->count()} acto(s).");

            if (! $dryRun) {
                // Notificar admins/supervisores con el resumen completo
                $this->notifyAdmins($matching, $threshold);

                // Notificar a cada creador solo con sus actos
                $this->notifyCreators($matching, $threshold);

                // Marcar como notificado
                foreach ($matching as $act) {
                    $notified   = $act->pdf_notified_days ?? [];
                    $notified[] = $threshold;
                    $act->updateQuietly(['pdf_notified_days' => array_unique($notified)]);
                }
            }
        }

        $this->newLine();
        $this->info('Proceso completado.');

        return self::SUCCESS;
    }

    private function notifyAdmins(Collection $acts, int $threshold): void
    {
        $admins = User::role(['super_admin', 'supervisor'])->get();
        foreach ($admins as $admin) {
            $admin->notify(new PdfDeadlineNotification($acts, $threshold));
        }
    }

    private function notifyCreators(Collection $acts, int $threshold): void
    {
        // Agrupar por creador
        $byCreator = $acts->groupBy('created_by');

        foreach ($byCreator as $userId => $userActs) {
            $creator = User::find($userId);
            if (! $creator) {
                continue;
            }
            // Si ya es admin, ya recibió el resumen global — no duplicar
            if ($creator->hasAnyRole(['super_admin', 'supervisor'])) {
                continue;
            }
            $creator->notify(new PdfDeadlineNotification(collect($userActs), $threshold));
        }
    }
}
