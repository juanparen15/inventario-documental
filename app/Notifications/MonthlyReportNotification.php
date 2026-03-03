<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Correo de informe mensual de actos administrativos.
 * Contiene estadísticas por entidad/unidad/subserie y actos sin PDF.
 */
class MonthlyReportNotification extends Notification
{
    use Queueable;

    /**
     * @param array  $stats       ['entity' => ['unit' => ['subserie' => count]]]
     * @param int    $totalActs   Total de actos en el período
     * @param Collection $pendingPdf  Actos sin PDF (cualquier estado)
     * @param int    $month
     * @param int    $year
     */
    public function __construct(
        public readonly array $stats,
        public readonly int $totalActs,
        public readonly Collection $pendingPdf,
        public readonly int $month,
        public readonly int $year,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $monthName = \Carbon\Carbon::createFromDate($this->year, $this->month, 1)
            ->locale('es')
            ->isoFormat('MMMM YYYY');

        $mail = (new MailMessage)
            ->subject("Informe mensual del Sistema Unificado de Registro — {$monthName}")
            ->greeting('Hola, ' . $notifiable->name)
            ->line("A continuación el resumen de registros del Sistema Unificado en **{$monthName}**:")
            ->line("**Total de actos registrados:** {$this->totalActs}")
            ->line('---')
            ->line('**Detalle por entidad y unidad:**')
            ->line('');

        foreach ($this->stats as $entityName => $units) {
            $mail->line("**{$entityName}**");
            foreach ($units as $unitName => $subseries) {
                $unitTotal = array_sum($subseries);
                $mail->line("  • {$unitName} — {$unitTotal} acto(s)");
                foreach ($subseries as $subserieName => $count) {
                    $mail->line("    ↳ {$subserieName}: {$count}");
                }
            }
        }

        $pendingCount = $this->pendingPdf->count();
        if ($pendingCount > 0) {
            $mail->line('')
                ->line('---')
                ->line("**Actos sin PDF adjunto:** {$pendingCount}");
            foreach ($this->pendingPdf->take(20) as $act) {
                $days = $act->pdfDaysRemaining();
                $label = $days < 0 ? '⚠ Vencido hace ' . abs($days) . 'd' : $days . 'd restantes';
                $mail->line("  • {$act->filing_number} — {$act->organizationalUnit?->name} | {$label}");
            }
            if ($pendingCount > 20) {
                $mail->line('  … y ' . ($pendingCount - 20) . ' más.');
            }
        }

        return $mail
            ->line('')
            ->action('Ver en la plataforma', url('/admin/monthly-report'))
            ->salutation('Inventario Documental — Sistema de Gestión');
    }
}
