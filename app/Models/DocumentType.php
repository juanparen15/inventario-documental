<?php

namespace App\Models;

use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'code',
        'name',
        'documentary_class_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function documentaryClass(): BelongsTo
    {
        return $this->belongsTo(DocumentaryClass::class);
    }

    public function inventoryRecords(): HasMany
    {
        return $this->hasMany(InventoryRecord::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->code ? "{$this->code} - {$this->name}" : $this->name;
    }
}
