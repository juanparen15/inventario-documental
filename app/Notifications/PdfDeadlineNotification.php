<?php

namespace App\Notifications;

use App\Models\AdministrativeAct;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Notificación de actos administrativos con PDF pendiente.
 * Canales: mail + database (campana in-app de Filament).
 * Se envía en los días 15, 25 y cuando vence el plazo de 30 días.
 */
class PdfDeadlineNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Collection $acts,
        public readonly int $threshold,
    ) {}

    // -------------------------------------------------------------------------
    // Canales
    // -------------------------------------------------------------------------

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    // -------------------------------------------------------------------------
    // Correo electrónico
    // -------------------------------------------------------------------------

    public function toMail(object $notifiable): MailMessage
    {
        $isExpired = $this->threshold >= 30;
        $remaining = 30 - $this->threshold;

        $subject = $isExpired
            ? '[VENCIDO] Registros sin PDF adjunto — Sistema Unificado de Registro'
            : "[Aviso {$this->threshold} días] Registros pendientes de PDF — Sistema Unificado de Registro";

        $intro = $isExpired
            ? 'Los siguientes registros han superado el plazo de **30 días** sin archivo PDF adjunto:'
            : "Los siguientes registros llevan **{$this->threshold} días** sin PDF. Quedan **{$remaining} día(s)** antes del vencimiento:";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hola, ' . $notifiable->name)
            ->line($intro)
            ->line('');

        // Agrupar por unidad organizacional para legibilidad
        $byUnit = $this->acts->groupBy(
            fn(AdministrativeAct $act) => $act->organizationalUnit?->name ?? 'Sin unidad'
        );

        foreach ($byUnit as $unitName => $unitActs) {
            $mail->line("**{$unitName}**");

            foreach ($unitActs as $act) {
                $days   = $act->pdfDaysRemaining();
                $status = $days < 0
                    ? 'Vencido hace ' . abs($days) . ' día(s)'
                    : $days . ' día(s) restantes';
                $actUrl = url('/admin/administrative-acts/' . $act->id);

                $mail->line(
                    "• [{$act->filing_number}]({$actUrl}) — {$act->subject} | {$status}"
                );
            }

            $mail->line('');
        }

        $mail->action('Ir al Sistema Unificado de Registro', url('/admin/administrative-acts'))
             ->line('Adjunta el PDF correspondiente a cada registro antes de que venza el plazo.');

        return $mail;
    }

    // -------------------------------------------------------------------------
    // Notificación in-app (campana de Filament)
    // -------------------------------------------------------------------------

    public function toDatabase(object $notifiable): array
    {
        $isExpired = $this->threshold >= 30;
        $count     = $this->acts->count();
        $remaining = 30 - $this->threshold;

        $title = $isExpired
            ? "Plazo vencido: {$count} acto(s) sin PDF"
            : "{$count} acto(s) sin PDF — {$this->threshold} días transcurridos";

        $body = $isExpired
            ? "{$count} acto(s) han superado los 30 días sin adjuntar el documento PDF."
            : "Quedan {$remaining} día(s) para el vencimiento. Sube el PDF cuanto antes.";

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon($isExpired ? 'heroicon-o-exclamation-circle' : 'heroicon-o-clock')
            ->iconColor($isExpired ? 'danger' : 'warning')
            ->actions([
                NotificationAction::make('ver')
                    ->label('Ver actos pendientes')
                    ->url(url('/admin/administrative-acts'))
                    ->color($isExpired ? 'danger' : 'warning')
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
