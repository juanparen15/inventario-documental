<?php

namespace App\Traits;

use App\Support\ActivityFormatter;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait HasAuditLog
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('inventory')
            ->setDescriptionForEvent(fn(string $eventName) => $eventName); // se reemplaza en tapActivity
    }

    /**
     * Enriquece cada actividad con:
     *  - IP del request
     *  - Descripción legible en español con identificador del registro y campos cambiados
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        // IP del cliente
        if ($ip = request()?->ip()) {
            $activity->properties = $activity->properties->put('ip', $ip);
        }

        // Identificador legible del registro
        $identifier = $this->filing_number
            ?? $this->name
            ?? $this->title
            ?? '#' . $this->id;

        // Tipo de modelo en español
        $modelLabel = ActivityFormatter::modelLabel(get_class($this));

        // ¿Es una eliminación permanente (forceDelete) o una anulación (soft delete)?
        $isForceDelete = method_exists($this, 'isForceDeleting') && $this->isForceDeleting();
        $isAnnulled    = ! $isForceDelete && ! empty($this->annulment_reason);

        // Verbo de la acción
        $verb = match ($eventName) {
            'created'  => 'registrado',
            'updated'  => 'actualizado',
            'deleted'  => $isForceDelete ? 'eliminado definitivamente' : ($isAnnulled ? 'anulado' : 'eliminado'),
            'restored' => 'restaurado',
            default    => $eventName,
        };

        // Para 'deleted' por anulación: añadir el motivo
        if ($eventName === 'deleted' && $isAnnulled) {
            $verb .= ' — Motivo: ' . $this->annulment_reason;
        }

        // Para 'updated': añadir qué campos cambiaron
        if ($eventName === 'updated') {
            $changedFields = array_keys($activity->properties['old'] ?? []);
            $labels = [];
            foreach ($changedFields as $field) {
                if (! ActivityFormatter::shouldSkip($field)) {
                    $labels[] = ActivityFormatter::fieldLabel($field);
                }
            }
            if (! empty($labels)) {
                $verb .= ' — ' . implode(', ', $labels);
            }
        }

        $activity->description = "{$modelLabel} «{$identifier}» {$verb}";
    }
}
