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

    protected $fillable = [
        'code',
        'name',
        'description',
        'documentary_series_id',
        'retention_years',
        'final_disposition',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'retention_years' => 'integer',
    ];

    public function documentarySeries(): BelongsTo
    {
        return $this->belongsTo(DocumentarySeries::class);
    }

    public function documentaryClasses(): HasMany
    {
        return $this->hasMany(DocumentaryClass::class);
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
