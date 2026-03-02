<?php

namespace App\Support;

use App\Models\ActClassification;
use App\Models\DocumentarySeries;
use App\Models\DocumentarySubseries;
use App\Models\Entity;
use App\Models\OrganizationalUnit;
use App\Models\PriorityLevel;
use App\Models\StorageMedium;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Centraliza la transformación del log de actividad a lenguaje humano.
 * Usado en la vista de detalle, en la tabla del recurso y en el trait HasAuditLog.
 */
class ActivityFormatter
{
    // -------------------------------------------------------------------------
    // Campos que no aportan valor al log de auditoría
    // -------------------------------------------------------------------------

    private const SKIP_FIELDS = [
        'slug',
        'remember_token',
        'pdf_notified_days',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'updated_at',
        'created_at',
        'deleted_at',
    ];

    // -------------------------------------------------------------------------
    // Etiquetas legibles por campo
    // -------------------------------------------------------------------------

    private const FIELD_LABELS = [
        // Comunes
        'id'                          => 'ID',
        'name'                        => 'Nombre',
        'last_name'                   => 'Apellido',
        'email'                       => 'Correo electrónico',
        'phone'                       => 'Teléfono',
        'document_number'             => 'Número de documento',
        'password'                    => 'Contraseña',
        'avatar'                      => 'Foto de perfil',
        'is_active'                   => 'Estado activo',
        'can_import'                  => 'Permiso de importación',
        'code'                        => 'Código / Siglas',
        'notes'                       => 'Notas',
        'description'                 => 'Descripción',
        'context'                     => 'Contexto',
        'reference_code'              => 'Código de referencia',

        // AdministrativeAct
        'filing_number'               => 'Consecutivo',
        'subject'                     => 'Objeto / Asunto',
        'vigencia'                    => 'Vigencia',
        'folios'                      => 'Folios (páginas)',
        'attachments'                 => 'Archivos PDF',
        'confidential_attachments'    => 'Archivos confidenciales',
        'late_upload_reason'          => 'Razón del retraso en la subida del PDF',

        // InventoryRecord
        'title'                       => 'Título',
        'inventory_purpose'           => 'Propósito del inventario',
        'start_date'                  => 'Fecha de inicio',
        'end_date'                    => 'Fecha de fin',
        'has_start_date'              => 'Tiene fecha de inicio',
        'has_end_date'                => 'Tiene fecha de fin',
        'box'                         => 'Caja',
        'folder'                      => 'Carpeta',
        'volume'                      => 'Tomo / Volumen',
        'storage_unit_type'           => 'Tipo de unidad de almacenamiento',
        'storage_unit_quantity'       => 'Cantidad de unidades',

        // Series / Subseries
        'retention_years'             => 'Años de retención',
        'final_disposition'           => 'Disposición final',

        // Claves foráneas
        'user_id'                     => 'Usuario asignado',
        'organizational_unit_id'      => 'Unidad organizacional',
        'act_classification_id'       => 'Clasificación del acto',
        'documentary_series_id'       => 'Serie documental',
        'documentary_subseries_id'    => 'Subserie documental',
        'entity_id'                   => 'Entidad',
        'storage_medium_id'           => 'Medio de almacenamiento',
        'priority_level_id'           => 'Nivel de prioridad',
        'created_by'                  => 'Registrado por',
        'updated_by'                  => 'Última modificación por',
    ];

    // -------------------------------------------------------------------------
    // API pública
    // -------------------------------------------------------------------------

    /** Etiqueta legible para un campo de base de datos. */
    public static function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field]
            ?? ucwords(str_replace('_', ' ', $field));
    }

    /** Indica si el campo debe omitirse del log de auditoría. */
    public static function shouldSkip(string $field): bool
    {
        return in_array($field, self::SKIP_FIELDS, true);
    }

    /**
     * Convierte el valor de un campo a texto legible.
     * Resuelve IDs foráneos a nombres, booleanos a Sí/No, arrays a conteo, etc.
     */
    public static function fieldValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '(vacío)';
        }

        // Contraseña — nunca mostrar
        if ($field === 'password') {
            return '(oculto por seguridad)';
        }

        // Booleanos / flags
        if (is_bool($value) || in_array($field, ['is_active', 'can_import', 'has_start_date', 'has_end_date', 'is_reserved'])) {
            if ($field === 'is_active') {
                return $value ? 'Activo' : 'Inactivo';
            }
            if ($field === 'can_import') {
                return $value ? 'Puede importar' : 'Sin permiso de importación';
            }
            return $value ? 'Sí' : 'No';
        }

        // Archivos adjuntos — mostrar cantidad
        if (in_array($field, ['attachments', 'confidential_attachments'], true)) {
            $arr   = is_array($value) ? $value : (json_decode($value, true) ?? []);
            $count = count(array_filter($arr));
            if ($count === 0) return 'Sin archivos';
            return $count === 1 ? '1 archivo adjunto' : "{$count} archivos adjuntos";
        }

        // Arrays / JSON genéricos
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Claves foráneas — resolver a nombre legible
        return match ($field) {
            'organizational_unit_id'   => OrganizationalUnit::find($value)?->name ?? "(unidad #{$value})",
            'act_classification_id'    => ActClassification::find($value)?->name  ?? "(clasificación #{$value})",
            'documentary_series_id'    => DocumentarySeries::find($value)?->name  ?? "(serie #{$value})",
            'documentary_subseries_id' => DocumentarySubseries::find($value)?->name ?? "(subserie #{$value})",
            'entity_id'                => Entity::find($value)?->name              ?? "(entidad #{$value})",
            'storage_medium_id'        => StorageMedium::find($value)?->name       ?? "(medio #{$value})",
            'priority_level_id'        => PriorityLevel::find($value)?->name       ?? "(prioridad #{$value})",
            'user_id',
            'created_by',
            'updated_by'               => User::find($value)?->name                ?? "(usuario #{$value})",
            default                    => (string) $value,
        };
    }

    /** Nombre legible del tipo de modelo. */
    public static function modelLabel(string $class): string
    {
        return match ($class) {
            'App\Models\AdministrativeAct'    => 'Acto',
            'App\Models\InventoryRecord'      => 'Inventario',
            'App\Models\OrganizationalUnit'   => 'Unidad',
            'App\Models\DocumentarySeries'    => 'Serie',
            'App\Models\DocumentarySubseries' => 'Subserie',
            'App\Models\Entity'               => 'Entidad',
            'App\Models\ActClassification'    => 'Clasificación',
            'App\Models\StorageMedium'        => 'Medio de almacenamiento',
            'App\Models\PriorityLevel'        => 'Nivel de prioridad',
            'App\Models\User'                 => 'Usuario',
            default                           => class_basename($class),
        };
    }

    /**
     * Identificador principal del sujeto de una actividad
     * (consecutivo, nombre, título o ID como fallback).
     */
    public static function subjectIdentifier(Activity $activity): string
    {
        try {
            $subject = $activity->subject;
        } catch (\Throwable) {
            $subject = null;
        }

        if (! $subject) {
            return $activity->subject_id ? "#{$activity->subject_id}" : '—';
        }

        return $subject->filing_number
            ?? $subject->name
            ?? $subject->title
            ?? "#{$activity->subject_id}";
    }

    /**
     * Lista de campos visibles de un bloque de propiedades,
     * ya filtrados (sin skip) y con sus etiquetas.
     *
     * @return array<string, string>  ['label' => 'value']
     */
    public static function formatProperties(array $fields): array
    {
        $result = [];
        foreach ($fields as $field => $value) {
            if (self::shouldSkip($field)) {
                continue;
            }
            $result[self::fieldLabel($field)] = self::fieldValue($field, $value);
        }
        return $result;
    }

    /**
     * Resumen de campos cambiados para la columna de la tabla
     * (solo etiquetas, sin valores).
     */
    public static function changedFieldsSummary(Activity $activity): string
    {
        $props = $activity->properties?->toArray() ?? [];
        $fields = array_keys($props['old'] ?? $props['attributes'] ?? []);
        $labels = [];

        foreach ($fields as $field) {
            if (self::shouldSkip($field)) {
                continue;
            }
            $labels[] = self::fieldLabel($field);
        }

        return empty($labels) ? '—' : implode(', ', $labels);
    }
}
