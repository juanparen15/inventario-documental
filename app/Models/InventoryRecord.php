<?php

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryRecord extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'organizational_unit_id',
        'inventory_purpose',
        'documentary_series_id',
        'documentary_subseries_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'has_start_date',
        'has_end_date',
        'box',
        'folder',
        'volume',
        'folios',
        'storage_medium_id',
        'storage_unit_type',
        'storage_unit_quantity',
        'priority_level_id',
        'notes',
        'reference_code',
        'attachments',
        'created_by',
        'updated_by',
    ];

    // Opciones de Objeto del Inventario (FUID)
    public const INVENTORY_PURPOSES = [
        'transferencias_primarias' => 'Transferencias Primarias',
        'transferencias_secundarias' => 'Transferencias Secundarias',
        'valoracion_fondos' => 'Valoracion de Fondos Acumulados',
        'fusion_supresion' => 'Fusion y Supresion de Entidades y/o Dependencias',
        'inventarios_individuales' => 'Inventarios Individuales',
    ];

    // Tipos de unidad de almacenamiento
    public const STORAGE_UNIT_TYPES = [
        'microfilm' => 'Rollo de Microfilm',
        'casette' => 'Casette',
        'cinta_video' => 'Cinta de Video',
        'cd' => 'CD',
        'dvd' => 'DVD',
        'disco_duro' => 'Disco Duro',
        'usb' => 'USB',
        'otro' => 'Otro',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'has_start_date' => 'boolean',
        'has_end_date' => 'boolean',
        'folios' => 'integer',
        'storage_unit_quantity' => 'integer',
        'attachments' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (empty($model->reference_code)) {
                $model->reference_code = static::generateReferenceCode($model);
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    protected static function generateReferenceCode($model): string
    {
        $year = now()->format('Y');
        $unitCode = $model->organizationalUnit?->code ?? 'XX';
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%s-%06d', $year, $unitCode, $count);
    }

    // Relationships - TRD Structure
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function documentarySeries(): BelongsTo
    {
        return $this->belongsTo(DocumentarySeries::class);
    }

    public function documentarySubseries(): BelongsTo
    {
        return $this->belongsTo(DocumentarySubseries::class);
    }

    // Relationships - Auxiliary catalogs
    public function storageMedium(): BelongsTo
    {
        return $this->belongsTo(StorageMedium::class);
    }

    public function priorityLevel(): BelongsTo
    {
        return $this->belongsTo(PriorityLevel::class);
    }

    // Audit relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // Accessors
    public function getDateRangeAttribute(): string
    {
        $start = $this->has_start_date ? ($this->start_date?->format('d/m/Y') ?? '-') : 'S.F.';
        $end = $this->has_end_date ? ($this->end_date?->format('d/m/Y') ?? '-') : 'S.F.';
        return "{$start} - {$end}";
    }

    public function getLocationAttribute(): string
    {
        $parts = array_filter([
            $this->box ? "Caja: {$this->box}" : null,
            $this->folder ? "Carpeta: {$this->folder}" : null,
            $this->volume ? "Tomo: {$this->volume}" : null,
        ]);
        return implode(' | ', $parts) ?: '-';
    }
}
