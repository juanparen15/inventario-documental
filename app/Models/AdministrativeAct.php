<?php

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class AdministrativeAct extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'user_id',
        'organizational_unit_id',
        'act_classification_id',
        'vigencia',
        'documentary_series_id',
        'documentary_subseries_id',
        'filing_number',
        'subject',
        'attachments',
        'attachment_names',
        'confidential_attachments',
        'confidential_attachment_names',
        'late_upload_reason',
        'annulment_reason',
        'annulled_by',
        'folios',
        'pdf_notified_days',
        'slug',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vigencia'          => 'integer',
        'attachments'                => 'array',
        'attachment_names'           => 'array',
        'confidential_attachments'   => 'array',
        'confidential_attachment_names' => 'array',
        'pdf_notified_days'          => 'array',
    ];

    /** Días restantes hasta el límite de 30 días para subir PDF (negativo = vencido). */
    public function pdfDaysRemaining(): int
    {
        return 30 - (int) $this->created_at->diffInDays(now());
    }

    /** True si el acto no tiene PDF adjunto (ni regular ni confidencial). */
    public function lacksPdf(): bool
    {
        return empty($this->attachments) && empty($this->confidential_attachments);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (empty($model->vigencia)) {
                $model->vigencia = (int) date('Y');
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->subject) . '-' . uniqid();
            }
            if (empty($model->filing_number)) {
                $model->filing_number = static::generateFilingNumber($model);
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // Al restaurar (revertir la anulación) se limpian los datos de anulación.
        static::restoring(function ($model) {
            $model->annulment_reason = null;
            $model->annulled_by      = null;
        });
    }

    /**
     * Deriva las siglas de un nombre eliminando artículos y preposiciones comunes.
     * Ejemplo: "Alcaldía Municipal de Bogotá" → "AMB"
     * Si no queda ninguna inicial se toman los 3 primeros caracteres en mayúscula.
     */
    private static function siglaFromName(string $name): string
    {
        $stopWords = ['de', 'del', 'la', 'el', 'los', 'las', 'y', 'e', 'o', 'u', 'a', 'en', 'con', 'por', 'para', 'al'];
        $words = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach ($words as $word) {
            $lower = mb_strtolower($word);
            if ($word !== '' && !in_array($lower, $stopWords)) {
                $initials .= mb_strtoupper(mb_substr($word, 0, 1));
            }
        }
        return $initials ?: mb_strtoupper(mb_substr($name, 0, 3));
    }

    /**
     * Resuelve las siglas de la entidad asociada a una unidad organizacional.
     * Usa entity->code si existe; si no, las deriva del nombre de la entidad.
     */
    private static function resolveEntityCode(OrganizationalUnit $unit): string
    {
        $entity = $unit->entity;
        if (!$entity) {
            return 'XXX';
        }
        if (!empty($entity->code)) {
            return $entity->code;
        }
        return static::siglaFromName($entity->name);
    }

    /**
     * Genera el consecutivo: {vigencia}.{siglas_entidad}.{código_serie}.{código_subserie}.{consecutivo}
     * Ejemplo: 2026.AMB.01.01.001
     * Si no tiene subserie: 2026.AMB.01.001
     * El consecutivo se reinicia con cada nueva vigencia.
     */
    public static function generateFilingNumber($model): string
    {
        $vigencia    = $model->vigencia ?? (int) date('Y');
        $unit        = $model->organizationalUnit;
        $entityCode  = $unit ? static::resolveEntityCode($unit) : 'XXX';
        $seriesCode  = $model->documentarySeries?->code ?? '00';
        $subseriesCode = $model->documentarySubseries?->code;

        if ($subseriesCode) {
            $prefix = "{$vigencia}.{$entityCode}.{$seriesCode}.{$subseriesCode}";
        } else {
            $prefix = "{$vigencia}.{$entityCode}.{$seriesCode}";
        }

        $lastNumber = static::withTrashed()
            ->where('filing_number', 'like', "{$prefix}.%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(REPLACE(filing_number, \'.SUR\', \'\'), \'.\', -1) AS UNSIGNED) DESC')
            ->value('filing_number');

        if ($lastNumber) {
            $parts = explode('.', str_replace('.SUR', '', $lastNumber));
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return sprintf('%s.%03d.SUR', $prefix, $seq);
    }

    /**
     * Calcula el proximo consecutivo sin guardarlo (para preview en formulario).
     */
    public static function previewFilingNumber(?int $vigencia, ?int $unitId, ?int $seriesId, ?int $subseriesId): ?string
    {
        if (!$unitId || !$seriesId) {
            return null;
        }

        $vigencia  = $vigencia ?? (int) date('Y');
        $unit      = OrganizationalUnit::with('entity')->find($unitId);
        $series    = DocumentarySeries::find($seriesId);
        $subseries = $subseriesId ? DocumentarySubseries::find($subseriesId) : null;

        if (!$unit || !$series) {
            return null;
        }

        $entityCode    = static::resolveEntityCode($unit);
        $seriesCode    = $series->code;
        $subseriesCode = $subseries?->code;

        if ($subseriesCode) {
            $prefix = "{$vigencia}.{$entityCode}.{$seriesCode}.{$subseriesCode}";
        } else {
            $prefix = "{$vigencia}.{$entityCode}.{$seriesCode}";
        }

        $lastNumber = static::withTrashed()
            ->where('filing_number', 'like', "{$prefix}.%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(REPLACE(filing_number, \'.SUR\', \'\'), \'.\', -1) AS UNSIGNED) DESC')
            ->value('filing_number');

        if ($lastNumber) {
            $parts = explode('.', str_replace('.SUR', '', $lastNumber));
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return sprintf('%s.%03d.SUR', $prefix, $seq);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function actClassification(): BelongsTo
    {
        return $this->belongsTo(ActClassification::class);
    }

    public function documentarySeries(): BelongsTo
    {
        return $this->belongsTo(DocumentarySeries::class);
    }

    public function documentarySubseries(): BelongsTo
    {
        return $this->belongsTo(DocumentarySubseries::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Usuario que anuló el registro. */
    public function annuller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annulled_by');
    }
}
