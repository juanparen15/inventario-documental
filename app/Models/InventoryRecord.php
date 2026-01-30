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
        'documentary_series_id',
        'documentary_subseries_id',
        'documentary_class_id',
        'document_type_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'box',
        'folder',
        'volume',
        'folios',
        'storage_medium_id',
        'document_purpose_id',
        'process_type_id',
        'validity_status_id',
        'priority_level_id',
        'project_id',
        'notes',
        'reference_code',
        'attachments',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'folios' => 'integer',
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
        $seriesCode = $model->documentarySeries?->code ?? 'XX';
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%s-%06d', $year, $seriesCode, $count);
    }

    // Relationships
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

    public function documentaryClass(): BelongsTo
    {
        return $this->belongsTo(DocumentaryClass::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function storageMedium(): BelongsTo
    {
        return $this->belongsTo(StorageMedium::class);
    }

    public function documentPurpose(): BelongsTo
    {
        return $this->belongsTo(DocumentPurpose::class);
    }

    public function processType(): BelongsTo
    {
        return $this->belongsTo(ProcessType::class);
    }

    public function validityStatus(): BelongsTo
    {
        return $this->belongsTo(ValidityStatus::class);
    }

    public function priorityLevel(): BelongsTo
    {
        return $this->belongsTo(PriorityLevel::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

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
        $start = $this->start_date?->format('d/m/Y') ?? '-';
        $end = $this->end_date?->format('d/m/Y') ?? '-';
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
