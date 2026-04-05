---
title: Modelos de Datos
description: Documentación técnica de los modelos Eloquent del Sistema de Inventario Documental.
---

## Mapa de Modelos

```
Entity (1) ──────────────────── (N) OrganizationalUnit
OrganizationalUnit (1) ──────── (N) User
OrganizationalUnit (1) ──────── (N) AdministrativeAct
OrganizationalUnit (1) ──────── (N) InventoryRecord

DocumentarySeries (1) ──────── (N) DocumentarySubseries
DocumentarySeries (1) ──────── (N) AdministrativeAct
DocumentarySeries (1) ──────── (N) InventoryRecord
DocumentarySubseries (1) ────── (N) AdministrativeAct
DocumentarySubseries (1) ────── (N) InventoryRecord

User (1) ────────────────────── (N) AdministrativeAct (user_id)
User (1) ────────────────────── (N) AdministrativeAct (created_by)
User (1) ────────────────────── (N) InventoryRecord (created_by)

ActClassification (1) ──────── (N) AdministrativeAct
StorageMedium (1) ────────────── (N) InventoryRecord
PriorityLevel (1) ────────────── (N) InventoryRecord
```

---

## AdministrativeAct

**Tabla:** `administrative_acts`
**Traits:** `HasFactory`, `SoftDeletes`, `HasAuditLog`

### Campos Fillable

```php
$fillable = [
    'user_id', 'organizational_unit_id', 'act_classification_id',
    'vigencia', 'documentary_series_id', 'documentary_subseries_id',
    'filing_number', 'subject', 'attachments', 'confidential_attachments',
    'late_upload_reason', 'folios', 'pdf_notified_days', 'slug', 'notes',
    'created_by', 'updated_by',
];
```

### Casts

| Campo | Tipo PHP |
|-------|---------|
| `vigencia` | `integer` |
| `attachments` | `array` |
| `confidential_attachments` | `array` |
| `pdf_notified_days` | `array` |

### Métodos de Negocio

**`pdfDaysRemaining(): int`**
Calcula los días restantes para el plazo de 30 días de adjuntar el PDF. Negativo = vencido.

```php
public function pdfDaysRemaining(): int
{
    return 30 - (int) $this->created_at->diffInDays(now());
}
```

**`lacksPdf(): bool`**
Retorna `true` si el acto no tiene PDF (ni regular ni confidencial).

**`generateFilingNumber($model): string`** *(static)*
Genera el radicado automático con el formato `YYYY.ENTIDAD.SERIE.SUBSERIE.###.SUR`. El consecutivo se reinicia con cada nueva vigencia por prefijo.

**`previewFilingNumber(?int $vigencia, ?int $unitId, ?int $seriesId, ?int $subseriesId): ?string`** *(static)*
Calcula el próximo radicado sin guardarlo, usado en el formulario para mostrar la vista previa en tiempo real.

### Boot: Hooks de Creación

Al crear (`static::creating`):
- Asigna `created_by` = usuario autenticado
- Asigna `vigencia` = año actual si está vacío
- Genera `slug` único
- Llama a `generateFilingNumber()` para asignar el radicado

Al actualizar (`static::updating`):
- Asigna `updated_by` = usuario autenticado

### Relaciones

```php
user()               → BelongsTo(User::class)
organizationalUnit() → BelongsTo(OrganizationalUnit::class)
actClassification()  → BelongsTo(ActClassification::class)
documentarySeries()  → BelongsTo(DocumentarySeries::class)
documentarySubseries() → BelongsTo(DocumentarySubseries::class)
creator()            → BelongsTo(User::class, 'created_by')
updater()            → BelongsTo(User::class, 'updated_by')
```

---

## InventoryRecord

**Tabla:** `inventory_records`
**Traits:** `HasFactory`, `SoftDeletes`, `HasAuditLog`

### Constantes

**`INVENTORY_PURPOSES`** — Objetos del inventario FUID:
- `transferencias_primarias` → Transferencias Primarias
- `transferencias_secundarias` → Transferencias Secundarias
- `valoracion_fondos` → Valoración de Fondos Acumulados
- `fusion_supresion` → Fusión y Supresión de Entidades
- `inventarios_individuales` → Inventarios Individuales

**`STORAGE_UNIT_TYPES`** — Tipos de unidades de almacenamiento digital:
`microfilm`, `casette`, `cinta_video`, `cd`, `dvd`, `disco_duro`, `usb`, `otro`

### Métodos de Negocio

**`generateReferenceCode($model): string`** *(static)*
Genera el código de referencia: `YYYY-COD_UNIDAD-000001`.

**`getDateRangeAttribute(): string`**
Retorna el rango de fechas formateado: `"01/01/2026 - 31/12/2026"`. Usa `"S.F."` si no tiene fecha.

**`getLocationAttribute(): string`**
Retorna la ubicación física formateada: `"Caja: 15 | Carpeta: 3 | Tomo: 1"`.

### Relaciones

```php
organizationalUnit()    → BelongsTo(OrganizationalUnit::class)
documentarySeries()     → BelongsTo(DocumentarySeries::class)
documentarySubseries()  → BelongsTo(DocumentarySubseries::class)
storageMedium()         → BelongsTo(StorageMedium::class)
priorityLevel()         → BelongsTo(PriorityLevel::class)
creator()               → BelongsTo(User::class, 'created_by')
updater()               → BelongsTo(User::class, 'updated_by')
attachments()           → MorphMany(Attachment::class, 'attachable')
```

---

## DocumentarySeries

**Tabla:** `documentary_series`
**Traits:** `HasFactory`, `SoftDeletes`, `HasAuditLog`

### Constantes

**`CONTEXTS`**: `['fuid' => 'FUID (Inventario Documental)', 'ccd' => 'CCD (Cuadro de Clasificacion)']`

### Scopes

```php
scopeFuid($query)  → WHERE context = 'fuid'
scopeCcd($query)   → WHERE context = 'ccd'
```

### Boot

Al actualizar: si cambia el `context`, actualiza automáticamente el contexto de todas sus subseries.

### Accessor

**`getFullNameAttribute(): string`** → `"01 - Acuerdos Municipales"`

---

## User

**Tabla:** `users`
**Traits:** `HasFactory`, `Notifiable`, `HasRoles`, `HasPanelShield`
**Interfaces:** `FilamentUser`, `HasAvatar`

### Métodos Clave

**`canAccessPanel(Panel $panel): bool`**
Siempre retorna `true` (el acceso granular lo controla Shield por recurso).

**`getFilamentAvatarUrl(): ?string`**
Retorna la URL del avatar. Si no tiene avatar personalizado, genera uno con UI Avatars API usando el nombre del usuario.

**`getFullNameAttribute(): string`**
Combina `name` + `last_name`.

---

## HasAuditLog Trait

Trait compartido por `AdministrativeAct`, `InventoryRecord`, `OrganizationalUnit` y `DocumentarySeries`:

```php
// Registra automáticamente en la tabla activity_log
// Usa spatie/laravel-activitylog
// Registra: created, updated, deleted, restored
```

---

## OrganizationalUnit

**Tabla:** `organizational_units`

### Boot

Al crear/actualizar: genera el `slug` automáticamente del nombre si está vacío.

### Relaciones

```php
entity()           → BelongsTo(Entity::class)
inventoryRecords() → HasMany(InventoryRecord::class)
administrativeActs()→ HasMany(AdministrativeAct::class)
```
