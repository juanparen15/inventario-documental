<?php

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentarySubseries extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $table = 'documentary_subseries';

    public const CONTEXTS = [
        'fuid' => 'FUID (Inventario Documental)',
        'ccd' => 'CCD (Cuadro de Clasificacion)',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'documentary_series_id',
        'retention_years',
        'final_disposition',
        'is_active',
        'context',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'retention_years' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $subseries) {
            if (empty($subseries->context) && $subseries->documentary_series_id) {
                $parent = DocumentarySeries::find($subseries->documentary_series_id);
                if ($parent) {
                    $subseries->context = $parent->context;
                }
            }
        });
    }

    public function scopeFuid($query)
    {
        return $query->where('context', 'fuid');
    }

    public function scopeCcd($query)
    {
        return $query->where('context', 'ccd');
    }

    public function documentarySeries(): BelongsTo
    {
        return $this->belongsTo(DocumentarySeries::class);
    }

    public function inventoryRecords(): HasMany
    {
        return $this->hasMany(InventoryRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }
}
