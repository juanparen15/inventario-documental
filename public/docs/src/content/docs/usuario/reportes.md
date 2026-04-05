---
title: Reportes Mensuales
description: Guía para generar, exportar y enviar reportes mensuales de cumplimiento de actos administrativos.
---

## Acceso a Reportes

Los reportes mensuales están disponibles para usuarios con rol **supervisor** o **super_admin**.

1. En el menú lateral ir a **Reporte Mensual**
2. Por defecto se muestra el mes actual

:::note
Los usuarios con rol `user` no tienen acceso a esta sección.
:::

---

## Navegación por Meses

### Botones de Navegación

- **← Mes anterior:** Retrocede un mes
- **→ Mes siguiente:** Avanza un mes (deshabilitado si ya es el mes actual)

### Seleccionar Mes y Año Específico

1. Hacer clic en **"Cambiar mes"**
2. Seleccionar el **mes** y el **año** en los desplegables
3. El año mínimo disponible es **2024**
4. Hacer clic en **Aplicar**

---

## Contenido del Reporte

La página de reporte muestra:

### KPIs Principales

| Indicador | Descripción |
|-----------|-------------|
| **Total de actos del mes** | Cantidad de actos registrados en el período seleccionado |
| **Tendencia vs mes anterior** | Variación porcentual respecto al mes previo |
| **% Cumplimiento PDF** | Porcentaje de actos con PDF adjunto |
| **Pendientes de PDF** | Actos sin PDF en el mes seleccionado |
| **Vencidos sin PDF** | Actos que superaron los 30 días sin PDF |

### Gráfica de Tendencia

Gráfica de barras con los **últimos 6 meses** mostrando la cantidad de actos registrados. Permite ver tendencias de producción documental.

### Gráfica de Cumplimiento por Unidad

Barras horizontales con el **% de cumplimiento PDF por unidad organizacional** (top 10):

- 🟢 Verde: ≥ 90% cumplimiento
- 🟡 Amarillo: 70% - 89%
- 🔴 Rojo: < 70%

### Distribución por Entidad / Unidad / Serie

Tabla jerárquica que muestra:
- Entidad
  - Unidad organizacional
    - Serie / Subserie
    - Cantidad de actos

---

## Exportar el Reporte

### Exportar a Excel

1. Hacer clic en el botón **Exportar Excel** (ícono de tabla)
2. Se descarga automáticamente el archivo `.xlsx`

El archivo Excel contiene **3 hojas**:

**Hoja 1: Resumen**
- KPIs del mes: total actos, con PDF, sin PDF, vencidos
- % de cumplimiento con calificación: `Óptimo (≥90%)`, `Regular (70-89%)`, `Crítico (<70%)`

**Hoja 2: Por Unidades**
- Distribución jerárquica: Entidad → Unidad → Serie → Cantidad

**Hoja 3: Sin PDF (Histórico)**
Listado completo de actos sin PDF adjunto con:

| Columna | Descripción |
|---------|-------------|
| Consecutivo | Número de radicado |
| Entidad | Entidad del acto |
| Unidad | Unidad organizacional |
| Objeto/Asunto | Descripción del acto |
| Fecha Registro | Cuándo se creó el acto |
| Días para PDF | `Vencido hace Xd` o `X días restantes` |
| Estado | `VENCIDO`, `CRÍTICO`, `ADVERTENCIA`, `En plazo` |

---

## Enviar Reporte por Correo

1. Hacer clic en el botón **Enviar Reporte**
2. Confirmar la acción en el diálogo
3. El sistema ejecuta el comando `acts:monthly-report` y envía el informe por correo a todos los usuarios administradores

:::caution
El envío del reporte llega a los correos configurados como administradores. Asegúrate de que los correos estén correctamente configurados en el sistema de usuarios.
:::

---

## Interpretar los Indicadores de Cumplimiento

### ¿Qué significa cada estado?

| Estado | Descripción | Plazo |
|--------|-------------|-------|
| **En plazo** | El acto tiene PDF o aún está dentro de los 30 días | Menos de 30 días |
| **ADVERTENCIA** | Quedan 7 días o menos para adjuntar el PDF | 23-30 días transcurridos |
| **CRÍTICO** | Quedan 1-3 días | 27-30 días transcurridos |
| **VENCIDO** | Superó los 30 días sin PDF adjunto | Más de 30 días |

### Actos Vencidos

Un acto en estado **VENCIDO** requiere:
1. El usuario sube el PDF con una **razón de tardanza** documentada
2. El supervisor o super_admin revisa que la razón sea válida
3. El acto deja de aparecer en la lista de vencidos una vez adjuntado el PDF
