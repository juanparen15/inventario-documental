---
title: Base de Datos
description: Esquema de tablas, relaciones y estructura de la base de datos del Sistema de Inventario Documental.
---

## Diagrama de Relaciones Principal

```
┌─────────────┐         ┌────────────────────┐
│   entities  │────────▶│ organizational_units│
│─────────────│  1:N    │────────────────────│
│ id          │         │ id                 │
│ name        │         │ name               │
│ code        │         │ code               │
└─────────────┘         │ entity_id (FK)     │
                        │ is_active          │
                        │ can_import         │
                        └────────┬───────────┘
                                 │
             ┌───────────────────┼───────────────────┐
             │                   │                   │
             ▼                   ▼                   ▼
┌────────────────────┐  ┌──────────────────┐  ┌──────────┐
│ administrative_acts │  │ inventory_records│  │  users   │
│────────────────────│  │──────────────────│  │──────────│
│ id                 │  │ id               │  │ id       │
│ user_id (FK)       │  │ organizational_  │  │ name     │
│ organizational_    │  │ unit_id (FK)     │  │ email    │
│ unit_id (FK)       │  │ documentary_     │  │ org_unit │
│ documentary_       │  │ series_id (FK)   │  │ _id (FK) │
│ series_id (FK)     │  │ documentary_     │  └──────────┘
│ documentary_       │  │ subseries_id (FK)│
│ subseries_id (FK)  │  │ title            │
│ act_classification_│  │ start_date       │
│ id (FK)            │  │ end_date         │
│ filing_number      │  │ box, folder, vol │
│ subject            │  │ reference_code   │
│ vigencia           │  └──────────────────┘
│ attachments (JSON) │
│ confidential_      │
│ attachments (JSON) │
└────────────────────┘
```

---

## Tablas Principales

### `entities` — Entidades

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint PK | Identificador |
| `name` | string | Nombre de la entidad |
| `code` | string nullable | Código para el radicado (ej: `AMB`) |
| `created_at` | timestamp | Fecha de creación |
| `updated_at` | timestamp | Fecha de modificación |
| `deleted_at` | timestamp | Soft delete |

### `organizational_units` — Unidades Organizacionales

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint PK | Identificador |
| `name` | string | Nombre de la dependencia |
| `code` | string unique nullable | Código corto |
| `slug` | string unique | Slug URL |
| `entity_id` | bigint FK | Entidad propietaria |
| `is_active` | boolean | Si está activa (default: true) |
| `can_import` | boolean | Si puede importar Excel (default: false) |
| `deleted_at` | timestamp | Soft delete |

### `documentary_series` — Series Documentales

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint PK | Identificador |
| `code` | string unique | Código (ej: `01`) |
| `name` | string | Nombre de la serie |
| `description` | text nullable | Descripción |
| `retention_years` | integer nullable | Años de retención |
| `final_disposition` | string nullable | CT, E, S, M |
| `is_active` | boolean | Estado |
| `context` | string | `fuid` o `ccd` |
| `deleted_at` | timestamp | Soft delete |

### `documentary_subseries` — Subseries Documentales

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint PK | Identificador |
| `code` | string | Código de la subserie |
| `name` | string | Nombre de la subserie |
| `documentary_series_id` | bigint FK | Serie padre |
| `context` | string | `fuid` o `ccd` (hereda de la serie) |
| `deleted_at` | timestamp | Soft delete |

### `act_classifications` — Clasificaciones de Actos

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint PK | Identificador |
| `name` | string | Tipo de acto (Decreto, Resolución, etc.) |

### `administrative_acts` — Actos Administrativos

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint PK | Identificador |
| `user_id` | bigint FK nullable | Usuario propietario |
| `organizational_unit_id` | bigint FK nullable | Unidad organizacional |
| `act_classification_id` | bigint FK nullable | Tipo de acto |
| `documentary_series_id` | bigint FK nullable | Serie CCD |
| `documentary_subseries_id` | bigint FK nullable | Subserie CCD |
| `filing_number` | string nullable | Radicado: `2026.AMB.01.02.001.SUR` |
| `vigencia` | integer | Año de vigencia |
| `subject` | string | Asunto/objeto del acto |
| `attachments` | JSON nullable | Array de nombres de archivos adjuntos |
| `confidential_attachments` | JSON nullable | Adjuntos confidenciales |
| `late_upload_reason` | text nullable | Razón de subida tardía (>30 días) |
| `folios` | integer nullable | Número de folios del PDF |
| `pdf_notified_days` | JSON nullable | Días en que se enviaron notificaciones |
| `slug` | string unique | Slug URL |
| `notes` | text nullable | Notas adicionales |
| `created_by` | bigint FK nullable | Usuario creador |
| `updated_by` | bigint FK nullable | Último editor |
| `deleted_at` | timestamp | Soft delete |

**Índices:** `filing_number`, `act_date`

### `inventory_records` — Registros de Inventario FUID

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint PK | Identificador |
| `organizational_unit_id` | bigint FK | Unidad organizacional |
| `inventory_purpose` | string | Objeto del inventario |
| `documentary_series_id` | bigint FK | Serie FUID |
| `documentary_subseries_id` | bigint FK nullable | Subserie FUID |
| `title` | string | Título del expediente |
| `description` | text nullable | Descripción |
| `start_date` | date nullable | Fecha inicial |
| `end_date` | date nullable | Fecha final |
| `has_start_date` | boolean | Si tiene fecha inicial |
| `has_end_date` | boolean | Si tiene fecha final |
| `box` | string nullable | Número de caja |
| `folder` | string nullable | Número de carpeta |
| `volume` | string nullable | Número de tomo |
| `folios` | string nullable | Rango de folios |
| `storage_medium_id` | bigint FK nullable | Tipo de soporte |
| `storage_unit_type` | string nullable | Tipo unidad digital |
| `storage_unit_quantity` | integer nullable | Cantidad unidades digitales |
| `priority_level_id` | bigint FK nullable | Nivel de prioridad |
| `attachments` | JSON nullable | Archivos adjuntos |
| `notes` | text nullable | Observaciones |
| `reference_code` | string unique nullable | Código `YYYY-COD-000001` |
| `created_by` | bigint FK nullable | Usuario creador |
| `updated_by` | bigint FK nullable | Último editor |
| `deleted_at` | timestamp | Soft delete |

**Índices:** `(start_date, end_date)`, `box`, `reference_code`

### `users` — Usuarios

| Columna | Tipo | Descripción |
|---------|------|-------------|
| `id` | bigint PK | Identificador |
| `name` | string | Nombre |
| `last_name` | string nullable | Apellido |
| `email` | string unique | Email de acceso |
| `phone` | string nullable | Teléfono |
| `document_number` | string nullable | Número de documento |
| `organizational_unit_id` | bigint FK nullable | Unidad organizacional |
| `password` | string | Contraseña hasheada |
| `avatar` | string nullable | Nombre del archivo de avatar |

---

## Tablas de Soporte

| Tabla | Descripción |
|-------|-------------|
| `storage_mediums` | Tipos de soporte documental (papel, digital, microfilm) |
| `priority_levels` | Niveles de prioridad para inventario |
| `permissions` | Permisos de Spatie Permission |
| `roles` | Roles del sistema (super_admin, supervisor, usuario) |
| `model_has_roles` | Relación usuario ↔ rol |
| `model_has_permissions` | Permisos directos por usuario |
| `role_has_permissions` | Permisos por rol |
| `activity_log` | Log de auditoría (Spatie ActivityLog) |
| `notifications` | Notificaciones del panel |
| `sessions` | Sesiones de usuarios |
| `cache` | Cache de aplicación |
| `jobs` | Cola de trabajos |
| `failed_jobs` | Trabajos fallidos |

---

## Relaciones Clave

```
Entity (1) ──────── (N) OrganizationalUnit
OrganizationalUnit (1) ─ (N) AdministrativeAct
OrganizationalUnit (1) ─ (N) InventoryRecord
OrganizationalUnit (1) ─ (N) User

DocumentarySeries (1) ── (N) DocumentarySubseries
DocumentarySeries (1) ── (N) AdministrativeAct
DocumentarySeries (1) ── (N) InventoryRecord
DocumentarySubseries (1) (N) AdministrativeAct
DocumentarySubseries (1) (N) InventoryRecord

User (1) ─────────────── (N) AdministrativeAct
User (created_by) ─────── AdministrativeAct / InventoryRecord
```
