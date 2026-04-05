---
title: Dashboard
description: Guía del tablero de control principal con todos los widgets e indicadores del sistema.
---

## Vista General del Dashboard

El Dashboard es la pantalla de inicio al ingresar al panel. Muestra estadísticas en tiempo real del sistema, filtradas según el rol del usuario.

```
┌─────────────────────────────────────────────────────────┐
│  📊 Dashboard                                           │
├────────────┬────────────┬────────────┬──────────────────┤
│ FUID Total │ CCD Total  │ Unidades   │ Usuarios activos │
│   1,234    │    856     │    12      │      24          │
│ ↑ 8.5%     │  ↑ 12%    │ (admin)    │   (admin)        │
├────────────┴────────────┴────────────┴──────────────────┤
│                                                         │
│  🟢 Cumplimiento PDF   │  📈 Evolución Histórica        │
│  87% (con PDF)         │  [Gráfica de líneas FUID/CCD]  │
│  ⚠️  8 vencidos        │                                │
│  ⏰ 3 por vencer       │                                │
├─────────────────────────┴────────────────────────────── │
│  🍩 Top Series FUID    │  🍩 Top Series CCD             │
│  [Gráfica doughnut]    │  [Gráfica doughnut]            │
├─────────────────────────┴────────────────────────────── │
│  📊 Producción por Dependencia (solo admin)             │
│  [Barras FUID vs CCD por unidad]                        │
├─────────────────────────────────────────────────────────┤
│  📋 Últimos FUID       │  📋 Últimos CCD                │
│  [Tabla 5 registros]   │  [Tabla 3 actos]               │
└─────────────────────────────────────────────────────────┘
```

---

## Widget: Estadísticas del Sistema

Muestra los totales globales con tendencia mensual:

| Tarjeta | Descripción |
|---------|-------------|
| **Total FUID** | Número total de registros de inventario creados |
| **Total CCD** | Número total de actos administrativos registrados |
| **Unidades Activas** | Cantidad de unidades organizacionales activas *(solo super_admin)* |
| **Series FUID** | Series documentales activas del FUID *(solo super_admin)* |
| **Series CCD** | Series documentales activas del CCD *(solo super_admin)* |
| **Usuarios** | Total de usuarios activos *(solo super_admin)* |

Las flechas (↑↓) indican el cambio porcentual respecto al mes anterior.

---

## Widget: Cumplimiento PDF

Muestra el estado del cumplimiento del plazo de 30 días para adjuntar PDFs a los actos administrativos:

| Tarjeta | Descripción |
|---------|-------------|
| **% Cumplimiento** | Porcentaje de actos que tienen PDF adjunto |
| **Vencidos sin PDF** | Actos que superaron los 30 días sin PDF *(requieren razón de tardanza)* |
| **Por vencer (7 días)** | Actos en zona de advertencia: quedan 7 días o menos |

:::caution
Un acto en estado **"Vencido"** (>30 días sin PDF) debe registrar obligatoriamente la razón de la subida tardía al adjuntar el PDF.
:::

---

## Widget: Top Series Documentales — FUID

Gráfica de tipo **doughnut** con las 8 series documentales más usadas en el módulo de inventario. Al pasar el cursor sobre cada segmento se muestra:

- Nombre completo de la serie
- Cantidad de registros
- Porcentaje del total

---

## Widget: Top Series Documentales — CCD

Igual que el anterior pero para el módulo de Actos Administrativos (SUR/CCD).

---

## Widget: Evolución Histórica

Gráfica de **líneas** con dos series:
- 🔵 **FUID** — Registros de inventario por año
- 🟢 **CCD** — Actos administrativos por año

Muestra datos desde el año 2010 hasta la fecha actual, permitiendo ver la evolución de la producción documental.

---

## Widget: Producción por Dependencia *(solo super_admin)*

Gráfica de **barras agrupadas** con las 10 unidades con mayor producción documental:
- 🔵 Barras azules: Registros FUID
- 🟢 Barras verdes: Actos CCD

Al pasar el cursor se muestra el total de cada unidad.

---

## Widget: Últimos Registros FUID

Tabla con los **5 registros de inventario más recientes**:

| Columna | Descripción |
|---------|-------------|
| Código referencia | Identificador único del registro |
| Unidad | Unidad organizacional |
| Serie | Serie documental |
| Fecha | Fecha de creación |
| Ver | Botón para ir al detalle |

---

## Widget: Últimos Actos CCD

Tabla con los **3 actos administrativos más recientes**:

| Columna | Descripción |
|---------|-------------|
| Radicado | Número de radicado (ej: 2026.AMB.01.001.SUR) |
| Asunto | Descripción del acto (primeros 30 caracteres) |
| Tipo | Clasificación del acto (badge de color) |
| Fecha | Fecha de creación |

---

## Actualización Automática

Los widgets se actualizan automáticamente al recargar la página. Las notificaciones del panel se actualizan cada **60 segundos** via polling.
