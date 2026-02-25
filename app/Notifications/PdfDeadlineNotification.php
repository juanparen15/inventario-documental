<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Notificación de actos administrativos con PDF pendiente.
 * Se envía en los días 15, 25 y cuando vence el plazo de 30 días.
 */
class PdfDeadlineNotification extends Notification
{
    use Queueable;

    /**
     * @param Collection $acts     Actos sin PDF que aplican a este umbral
     * @param int        $threshold 15, 25 o 30 (vencido)
     */
    public function __construct(
        public readonly Collection $acts,
        public readonly int $threshold,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isExpired = $this->threshold >= 30;

        $subject = $isExpired
            ? '[VENCIDO] Actos administrativos sin PDF adjunto - Inventario Documental'
            : "Aviso: actos administrativos pendientes de PDF ({$this->threshold} días) - Inventario Documental";

        $intro = $isExpired
            ? 'Los siguientes actos administrativos han superado el plazo de **30 días** sin tener un archivo PDF adjunto:'
            : "Los siguientes actos administrativos llevan **{$this->threshold} días** registrados sin tener un archivo PDF adjunto. Quedan " . (30 - $this->threshold) . ' días para el vencimiento del plazo.';

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hola, ' . $notifiable->name)
            ->line($intro)
            ->line('');

        foreach ($this->acts as $act) {
            $days     = $act->pdfDaysRemaining();
            $label    = $days < 0 ? 'Vencido hace ' . abs($days) . ' día(s)' : $days . ' día(s) restantes';
            $mail->line(
                "• **{$act->filing_number}** — {$act->subject} " .
                "| Unidad: {$act->organizationalUnit?->name} " .
                "| {$label}"
            );
        }

        $mail->line('')
            ->action('Ir a Actos Administrativos', url('/admin/administrative-acts'))
            ->line('Por favor, adjunta el PDF correspondiente a cada acto antes de que venza el plazo.');

        return $mail;
    }
}
