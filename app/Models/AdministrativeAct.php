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
        'folios',
        'slug',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vigencia' => 'integer',
        'attachments' => 'array',
    ];

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
    }

    /**
     * Genera el consecutivo: {vigencia}.{código_unidad}.{código_serie}.{código_subserie}.{consecutivo}
     * Ejemplo: 2026.100.01.01.001
     * Si no tiene subserie: 2026.100.01.001
     * El consecutivo se reinicia con cada nueva vigencia.
     */
    public static function generateFilingNumber($model): string
    {
        $vigencia = $model->vigencia ?? (int) date('Y');
        $unitCode = $model->organizationalUnit?->code ?? 'XXX';
        $seriesCode = $model->documentarySeries?->code ?? '00';
        $subseriesCode = $model->documentarySubseries?->code;

        if ($subseriesCode) {
            $prefix = "{$vigencia}.{$unitCode}.{$seriesCode}.{$subseriesCode}";
        } else {
            $prefix = "{$vigencia}.{$unitCode}.{$seriesCode}";
        }

        $lastNumber = static::withTrashed()
            ->where('filing_number', 'like', "{$prefix}.%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(filing_number, \'.\', -1) AS UNSIGNED) DESC')
            ->value('filing_number');

        if ($lastNumber) {
            $parts = explode('.', $lastNumber);
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return sprintf('%s.%03d', $prefix, $seq);
    }

    /**
     * Calcula el proximo consecutivo sin guardarlo (para preview en formulario).
     */
    public static function previewFilingNumber(?int $vigencia, ?int $unitId, ?int $seriesId, ?int $subseriesId): ?string
    {
        if (!$unitId || !$seriesId) {
            return null;
        }

        $vigencia = $vigencia ?? (int) date('Y');
        $unit = OrganizationalUnit::find($unitId);
        $series = DocumentarySeries::find($seriesId);
        $subseries = $subseriesId ? DocumentarySubseries::find($subseriesId) : null;

        if (!$unit || !$series) {
            return null;
        }

        $unitCode = $unit->code;
        $seriesCode = $series->code;
        $subseriesCode = $subseries?->code;

        if ($subseriesCode) {
            $prefix = "{$vigencia}.{$unitCode}.{$seriesCode}.{$subseriesCode}";
        } else {
            $prefix = "{$vigencia}.{$unitCode}.{$seriesCode}";
        }

        $lastNumber = static::withTrashed()
            ->where('filing_number', 'like', "{$prefix}.%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(filing_number, \'.\', -1) AS UNSIGNED) DESC')
            ->value('filing_number');

        if ($lastNumber) {
            $parts = explode('.', $lastNumber);
            $seq = (int) end($parts) + 1;
        } else {
            $seq = 1;
        }

        return sprintf('%s.%03d', $prefix, $seq);
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
}
