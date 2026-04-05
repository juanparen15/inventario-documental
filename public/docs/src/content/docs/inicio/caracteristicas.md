---
title: Características
description: Lista completa de funcionalidades del Sistema de Inventario Documental.
---

## Módulo SUR/CCD — Actos Administrativos

### Numeración Automática de Radicado

El sistema genera automáticamente el número de radicado al crear cada acto, siguiendo el formato oficial:

```
YYYY.ENTIDAD.SERIE.SUBSERIE.###.SUR
```

Ejemplo: `2026.AMB.01.02.001.SUR`

- **YYYY:** Año de vigencia del acto
- **ENTIDAD:** Siglas de la entidad (derivadas automáticamente del nombre o del código configurado)
- **SERIE:** Código de la serie documental CCD
- **SUBSERIE:** Código de la subserie documental (opcional)
- **###:** Consecutivo reiniciable por año
- **SUR:** Sufijo del Sistema Unificado de Registro

### Gestión de Adjuntos PDF

- Adjuntar PDFs del acto administrativo (hasta 200 KB por archivo)
- **Indicador de plazo:** Muestra los días restantes para adjuntar el PDF (límite: 30 días desde la creación)
- Conteo automático de folios desde los archivos PDF adjuntos
- Registro de razón de subida tardía (>30 días) para control normativo

### Sección Confidencial

- Adjuntos confidenciales separados del expediente público
- Visibles **únicamente** para el creador del acto y usuarios con rol `super_admin`
- Registro de acceso en el log de auditoría cuando un super_admin consulta archivos confidenciales

### Clasificación Documental

- Vinculación con Series y Subseries del CCD (Cuadro de Clasificación Documental)
- Clasificación por tipo de acto (ActClassification)
- Filtros por vigencia, unidad organizacional, serie y rango de fechas

---

## Módulo FUID — Inventario Documental

### Registro de Inventario

- Título y descripción del documento o expediente
- **Fechas extremas:** Fecha inicial y final con soporte para "Sin Fecha" (S.F.)
- Folio en formato libre (e.g., `1-50`, `51-100`)

### Clasificación TRD

- Unidad organizacional responsable
- Serie y subserie documental del FUID
- **Objeto del inventario** (propósito conforme al FUID):
  - Transferencias Primarias
  - Transferencias Secundarias
  - Valoración de Fondos Acumulados
  - Fusión y Supresión de Entidades
  - Inventarios Individuales

### Ubicación Física

- Número de **caja**, **carpeta** y **tomo/volumen**
- **Soporte:** Papel, electrónico u otro medio de almacenamiento
- Tipo y cantidad de unidades de almacenamiento digital (CD, DVD, USB, disco duro, etc.)

### Código de Referencia Automático

Generado automáticamente con el formato: `YYYY-COD_UNIDAD-000001`

---

## Dashboard e Indicadores

### Widgets Disponibles

| Widget | Descripción | Roles |
|--------|-------------|-------|
| **Estadísticas del Sistema** | Totales de registros FUID y actos CCD con tendencia mensual | Todos |
| **Cumplimiento PDF** | % de actos con PDF, vencidos y próximos a vencer | Todos |
| **Top Series FUID** | Doughnut chart de series más usadas en inventario | Todos |
| **Top Series CCD** | Doughnut chart de series más usadas en actos | Todos |
| **Evolución Histórica** | Línea comparativa FUID vs CCD por año | Todos |
| **Producción por Dependencia** | Barras agrupadas FUID vs CCD por unidad | super_admin |
| **Últimos Registros FUID** | Tabla con los 5 registros más recientes | Todos |
| **Últimos Actos CCD** | Tabla con los 3 actos más recientes | Todos |

---

## Reportes Mensuales

- **Página dedicada** de reportes en el panel (`/admin/monthly-report`)
- Navegación por mes y año con botones anterior/siguiente
- **Exportación Excel** con 3 hojas:
  1. Resumen KPIs (total actos, % cumplimiento, vencidos)
  2. Distribución por entidad/unidad/serie
  3. Listado histórico de actos sin PDF
- **Exportación PDF** del informe mensual
- **Envío por correo** automático a usuarios administradores
- Acceso restringido a `super_admin` y `supervisor`

---

## Seguridad y Control de Acceso

### Roles del Sistema

| Rol | Descripción |
|-----|-------------|
| `super_admin` | Acceso total al sistema, sin restricciones |
| `supervisor` | Acceso a todas las unidades, reportes e importación |
| `user` (usuario) | Solo su propia unidad organizacional |

### Restricciones por Unidad

- Los usuarios con rol `user` solo ven los registros de su unidad organizacional
- Los supervisores y super_admins ven todas las unidades

### Auditoría

- Registro completo de creación, edición y eliminación con Spatie ActivityLog
- Log especial cuando un super_admin accede a adjuntos confidenciales
- Usuarios `created_by` y `updated_by` en todos los modelos

---

## Importación Masiva

- Importación de registros FUID desde Excel (unidades habilitadas con `can_import`)
- Importación de permisos y roles desde Excel
- Registro de errores de importación con página dedicada

---

## Notificaciones

- Notificaciones en el panel sobre eventos del sistema
- Polling automático cada 60 segundos
- Envío de reportes mensuales por correo electrónico SMTP

---

## Modo Oscuro / Claro

- Alternador de tema integrado (`awcodes/light-switch`)
- Preferencia guardada automáticamente en el navegador
