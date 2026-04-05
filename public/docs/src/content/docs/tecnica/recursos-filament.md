---
title: Recursos Filament
description: Documentación técnica de los Resources, Pages, Widgets y Actions del panel Filament.
---

## Resources (CRUD)

Los Resources de Filament proveen las interfaces CRUD del panel. El sistema tiene **11 resources**:

| Resource | Tabla | Descripción |
|----------|-------|-------------|
| `AdministrativeActResource` | `administrative_acts` | Actos Administrativos (CCD/SUR) |
| `InventoryRecordResource` | `inventory_records` | Inventario Documental (FUID) |
| `DocumentarySeriesResource` | `documentary_series` | Series documentales |
| `DocumentarySubseriesResource` | `documentary_subseries` | Subseries documentales |
| `OrganizationalUnitResource` | `organizational_units` | Unidades organizacionales |
| `EntityResource` | `entities` | Entidades |
| `ActClassificationResource` | `act_classifications` | Tipos de actos |
| `StorageMediumResource` | `storage_mediums` | Medios de almacenamiento |
| `PriorityLevelResource` | `priority_levels` | Niveles de prioridad |
| `UserResource` | `users` | Gestión de usuarios |
| `ActivityLogResource` | `activity_log` | Log de auditoría (solo lectura) |

---

## AdministrativeActResource — Detalle

### Formulario

**Sección Principal:**
- `Select` → Unidad Organizacional (filtrado por rol del usuario)
- `Select` → Vigencia (años 2020–2030)
- `Select` → Serie Documental (CCD context, live, reactivo)
- `Select` → Subserie Documental (CCD context, live, filtrado por serie)
- `Select` → Clasificación del Acto
- `Placeholder` → Vista previa del radicado (live, calculado en tiempo real)
- `Textarea` → Asunto/Objeto (max 1000 chars)
- `TextInput` → Folios (calculado automáticamente del PDF)
- `Textarea` → Notas

**Sección Adjuntos:**
- `FileUpload` → PDFs oficiales (max 200KB, múltiples)
- `TextInput` → Razón de subida tardía (visible solo si >30 días)

**Sección Confidencial** *(oculta para no-creadores y no-admins):*
- `FileUpload` → PDFs confidenciales (max 200KB, múltiples)

### Tabla

Columnas: `vigencia`, `filing_number` (copiable), `documentarySeries`, `documentarySubseries`, `subject` (truncado 50 chars), `folios`, `confidential` (ícono 🔒), `pdf_days_remaining`, `creator.full_name`, `created_at`

Filtros: `vigencia`, `organizational_unit_id`, `documentary_series_id`, rango de fechas, `trashed`

Acciones de tabla: `ViewAction`, `EditAction`, `DeleteAction`, `RestoreAction`, `ForceDeleteAction`, acciones de adjuntos (modal)

---

## InventoryRecordResource — Detalle

### Formulario

**Sección Identificación:**
- `Select` → Unidad Organizacional
- `Select` → Objeto del Inventario (constantes de `InventoryRecord::INVENTORY_PURPOSES`)

**Sección Clasificación TRD:**
- `Select` → Serie Documental (FUID context)
- `Select` → Subserie (filtrada por serie)

**Sección Descripción:**
- `TextInput` → Título
- `Textarea` → Descripción

**Sección Fechas Extremas:**
- `Toggle` + `DatePicker` → Fecha inicial (con opción S.F.)
- `Toggle` + `DatePicker` → Fecha final (con opción S.F.)

**Sección Ubicación Física:**
- `TextInput` → Caja, Carpeta, Tomo/Volumen
- `TextInput` → Folios (rango: "1-50")

**Sección Soporte:**
- `Select` → Medio de almacenamiento
- `Select` → Tipo de unidad de almacenamiento digital
- `TextInput` → Cantidad de unidades

**Adjuntos:**
- `FileUpload` → PDFs (max 20MB, múltiples)

**Adicional:**
- `Select` → Nivel de Prioridad
- `Textarea` → Notas / condición física

### Tabla

Columnas: `reference_code` (copiable), `organizationalUnit.name`, `inventory_purpose`, `documentarySeries.code`, `box`, `folder`, `date_range`, adjuntos (modal), `creator.full_name`, `created_at`

---

## Pages (Páginas del Panel)

### Dashboard (`/admin`)

Página principal del panel. Muestra los 11 widgets del sistema (ver documentación de Widgets).

**Clase:** `App\Filament\Pages\Dashboard`

### MonthlyReportPage (`/admin/monthly-report`)

**Acceso:** solo `super_admin` y `supervisor`

**Funcionalidades:**
- Navegación mes/año con botones ← →
- Modal de selección de mes y año (desde 2024)
- Header Actions: exportar Excel, exportar PDF, enviar por correo
- Widgets incrustados: `MonthlyStatsOverview`, `MonthlyTrendChart`, `ComplianceByUnitChart`
- Datos de estadísticas jerárquicas: Entity → Unit → Series → Count

**Métodos:**
- `getStats()` — estadísticas jerárquicas del mes
- `getPendingPdf()` — actos sin PDF del mes
- `getExcelUrl()` — URL de descarga del Excel

### PasswordChangePage (`/admin/password`)

Formulario para que el usuario autenticado cambie su propia contraseña.

### ImportErrorsPage (`/admin/import-errors`)

Tabla de errores de importaciones masivas fallidas.

### ImportPermissionsPage (`/admin/import-permissions`)

Gestión de permisos de importación por unidad organizacional.

---

## Widgets

El dashboard incluye **11 widgets**:

| Widget | Tipo | Orden | Acceso |
|--------|------|-------|--------|
| `StatsOverviewWidget` | StatsOverview | 1 | Todos |
| `PdfComplianceWidget` | StatsOverview | 2 | Todos |
| `RecordsBySeriesChart` | ChartWidget (doughnut) | 3 | Todos |
| `ActsByClassificationChart` | ChartWidget (doughnut) | 4 | Todos |
| `RecordsTimelineChart` | ChartWidget (line) | 5 | Todos |
| `DocumentsByUnitComparisonChart` | ChartWidget (bar) | 6 | super_admin |
| `LatestRecordsWidget` | TableWidget | 7 | Todos |
| `LatestActsWidget` | TableWidget | 8 | Todos |
| `MonthlyStatsOverview` | StatsOverview | — | Solo MonthlyReportPage |
| `MonthlyTrendChart` | ChartWidget (bar) | — | Solo MonthlyReportPage |
| `ComplianceByUnitChart` | ChartWidget (bar horiz.) | — | Solo MonthlyReportPage |

:::note
Los widgets `MonthlyStatsOverview`, `MonthlyTrendChart` y `ComplianceByUnitChart` tienen `canView() → false` desde el Dashboard. Solo se muestran cuando `MonthlyReportPage` los incluye explícitamente.
:::

---

## Actions Personalizadas

### ViewAttachmentsAction

Modal que lista los archivos PDF adjuntos a un acto o registro. Los adjuntos confidenciales solo se muestran al creador y al `super_admin`.

### LateUploadAction

Aparece en actos con más de 30 días sin PDF. Permite al usuario registrar la razón de la subida tardía y adjuntar el PDF en una sola acción.

---

## Imports (Importación desde Excel)

Clases en `app/Filament/Imports/`:

- **`InventoryRecordImport`** — Importación de registros FUID desde Excel
- **`PermissionsImport`** — Importación de permisos/roles de usuarios

Los errores de importación se registran en la tabla de errores y son visibles en `ImportErrorsPage`.
