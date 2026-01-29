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

    protected $fillable = [
        'code',
        'name',
        'description',
        'retention_years',
        'final_disposition',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'retention_years' => 'integer',
    ];

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
