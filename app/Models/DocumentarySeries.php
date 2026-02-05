<?php

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentarySeries extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $table = 'documentary_series';

    public const CONTEXTS = [
        'fuid' => 'FUID (Inventario Documental)',
        'ccd' => 'CCD (Cuadro de Clasificacion)',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
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

        static::updating(function (self $series) {
            if ($series->isDirty('context')) {
                $series->documentarySubseries()->update(['context' => $series->context]);
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

    public function documentarySubseries(): HasMany
    {
        return $this->hasMany(DocumentarySubseries::class);
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
