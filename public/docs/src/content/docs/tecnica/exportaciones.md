---
title: Exportaciones y Reportes
description: Documentación técnica del sistema de exportación Excel, generación PDF y reportes mensuales.
---

## Arquitectura de Exportaciones

El sistema usa **Maatwebsite Laravel Excel** para exportaciones Excel y **DomPDF** para generación de PDFs.

```
MonthlyReportPage
       │
       ├── Exportar Excel ──▶ MonthlyReportExport ──▶ [3 Sheets]
       │
       ├── Exportar PDF ────▶ DomPDF → Blade Template
       │
       └── Enviar Correo ───▶ Queue → acts:monthly-report → Mail
```

---

## MonthlyReportExport

**Archivo:** `app/Exports/MonthlyReportExport.php`

**Interfaz implementada:** `WithMultipleSheets`

### Constructor

```php
public function __construct(int $month, int $year)
```

### Método `sheets()`

Retorna las 3 hojas del Excel:

```php
public function sheets(): array
{
    return [
        new MonthlyReportResumenSheet($this->month, $this->year),
        new MonthlyReportUnidadesSheet($this->month, $this->year),
        new MonthlyReportSinPdfSheet($this->month, $this->year),
    ];
}
```

---

## Hoja 1: MonthlyReportResumenSheet — Resumen KPIs

**Título:** `INFORME MENSUAL DE ACTOS ADMINISTRATIVOS — [Mes Año]`

### KPIs Calculados

| KPI | Descripción | Query |
|-----|-------------|-------|
| Total actos | Actos del mes | `whereMonth/whereYear('created_at')` |
| Con PDF | Actos con al menos 1 adjunto | `JSON_LENGTH(attachments) > 0 OR JSON_LENGTH(confidential_attachments) > 0` |
| Sin PDF | Actos sin adjunto | Diferencia |
| Vencidos | Más de 30 días sin PDF | `created_at < now()-30 AND lacksPdf()` |
| Por vencer | 7 días o menos | `created_at BETWEEN now()-30 AND now()-23` |
| % Cumplimiento | Con PDF / Total × 100 | Calculado |
| Calificación | Óptimo/Regular/Crítico | ≥90% / 70-89% / <70% |

### Estructura de la Hoja

```
Fila 1: Título (fusionado, fondo azul, texto blanco, altura 28)
Fila 2: [vacía]
Fila 3: Encabezados (fondo azul, texto blanco)
Fila 4+: Datos con bordes, filas alternas
```

---

## Hoja 2: MonthlyReportUnidadesSheet — Distribución por Unidades

**Datos:** Desglose jerárquico Entity → OrganizationalUnit → Series/Subseries → Cantidad

### Columnas

| Columna | Descripción |
|---------|-------------|
| Entidad | Nombre de la entidad |
| Unidad Organizacional | Nombre de la dependencia |
| Serie/Subserie | Clasificación del acto |
| Cantidad de Actos | Total de actos en el mes |

### Query Principal

```php
AdministrativeAct::with(['organizationalUnit.entity', 'documentarySeries', 'documentarySubseries'])
    ->whereMonth('created_at', $this->month)
    ->whereYear('created_at', $this->year)
    ->get()
    ->groupBy(fn($act) => $act->organizationalUnit?->entity?->name)
    // → luego por unidad → luego por serie/subserie
```

---

## Hoja 3: MonthlyReportSinPdfSheet — Actos Sin PDF

**Título:** `ACTOS SIN PDF ADJUNTO (HISTÓRICO)`

### Columnas

| Columna | Descripción |
|---------|-------------|
| Consecutivo | Número de radicado |
| Entidad | Entidad del acto |
| Unidad | Unidad organizacional |
| Objeto/Asunto | Descripción del acto (truncado) |
| Fecha Registro | Fecha de creación |
| Días para PDF | `"Vencido hace Xd"` o `"X días"` |
| Estado | VENCIDO / CRÍTICO / ADVERTENCIA / En plazo |

### Estados del Plazo

```php
$daysRemaining = $act->pdfDaysRemaining();

$estado = match(true) {
    $daysRemaining <= 0  => 'VENCIDO',
    $daysRemaining <= 3  => 'CRÍTICO',
    $daysRemaining <= 7  => 'ADVERTENCIA',
    default              => 'En plazo',
};
```

### Query — Detectar Actos Sin PDF

```php
AdministrativeAct::where(function ($q) {
    $q->whereNull('attachments')
      ->orWhereRaw('JSON_LENGTH(attachments) = 0');
})->where(function ($q) {
    $q->whereNull('confidential_attachments')
      ->orWhereRaw('JSON_LENGTH(confidential_attachments) = 0');
})->get();
```

---

## Comando de Reporte Mensual

**Archivo:** `app/Console/Commands/MonthlyActsReport.php`

```bash
php artisan acts:monthly-report
```

**Funcionalidad:**
1. Obtiene el mes y año actual
2. Genera los datos del reporte
3. Crea el Excel (`MonthlyReportExport`)
4. Envía el email a todos los usuarios con rol `super_admin`

---

## Conteo Automático de Folios

Al adjuntar un PDF a un acto, el sistema intenta contar las páginas con 3 métodos en cascada:

### Método 1: pdfinfo (Poppler Utils)

```php
$output = shell_exec("pdfinfo {$path}");
// Extrae "Pages: X" del output
```

Más eficiente. Requiere `poppler-utils` instalado en el servidor.

### Método 2: smalot/pdfparser

```php
$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile($path);
$pages = count($pdf->getPages());
```

Funciona para PDFs ≤ 10 MB. PHP puro, sin dependencias externas.

### Método 3: Regex en contenido raw

```php
preg_match_all('/\/Page\b/', $content, $matches);
return count($matches[0]);
```

Fallback de último recurso. Menor precisión.

---

## Ruta de Exportación Web

La descarga del Excel del reporte mensual se sirve via una ruta convencional de Laravel (no via Filament):

```php
// routes/web.php
Route::get('/admin/monthly-report/excel', [MonthlyReportController::class, 'exportExcel'])
    ->middleware(['auth', 'role:super_admin|supervisor'])
    ->name('monthly-report.excel');
```

---

## Almacenamiento de Adjuntos

Los archivos PDF se almacenan en `storage/app/public/` y se referencian en los campos JSON del modelo:

```json
// administrative_acts.attachments
["acto_2026_001.pdf", "acto_2026_001_v2.pdf"]

// administrative_acts.confidential_attachments
["confidencial_2026_001.pdf"]
```

Para Registros de Inventario, los adjuntos usan la relación polimórfica `Attachment` (`morphMany`).
