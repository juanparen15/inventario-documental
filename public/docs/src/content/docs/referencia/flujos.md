---
title: Flujos del Sistema
description: Diagramas de flujo de los procesos principales del Sistema de Inventario Documental.
---

## Flujo 1: Ciclo de Vida de un Acto Administrativo

```
┌─────────────────────────────────────────────────────────────────┐
│               CICLO DE VIDA — ACTO ADMINISTRATIVO               │
└─────────────────────────────────────────────────────────────────┘

  Usuario selecciona:
  ┌────────────────────────────────────────────────────────────┐
  │  Unidad Org. + Serie CCD + Subserie (opcional) + Vigencia  │
  └──────────────────────────────┬─────────────────────────────┘
                                 │
                                 ▼
              ┌──────────────────────────────────┐
              │  Sistema genera VISTA PREVIA del  │
              │  radicado en tiempo real:         │
              │  2026.AMB.01.02.001.SUR           │
              └──────────────────┬───────────────┘
                                 │
                                 ▼
              ┌──────────────────────────────────┐
              │  Usuario completa: Asunto,        │
              │  Clasificación, Notas             │
              │  y presiona GUARDAR               │
              └──────────────────┬───────────────┘
                                 │
                                 ▼
              ┌──────────────────────────────────┐
              │  Radicado asignado definitivamente│
              │  Estado: SIN PDF                 │
              │  Plazo: 30 días restantes         │
              └──────────────────┬───────────────┘
                                 │
              ┌──────────────────┴───────────────┐
              │                                  │
              ▼                                  ▼
    ┌──────────────────┐              ┌──────────────────────┐
    │  PDF adjuntado   │              │  Pasan los 30 días   │
    │  dentro del plazo│              │  sin adjuntar PDF    │
    │  ✓ CUMPLIDO      │              │  ⚠️  VENCIDO          │
    └──────────────────┘              └──────────┬───────────┘
                                                 │
                                                 ▼
                                      ┌──────────────────────┐
                                      │  Usuario adjunta PDF  │
                                      │  + registra RAZÓN DE  │
                                      │  TARDANZA obligatoria │
                                      └──────────────────────┘
```

---

## Flujo 2: Generación del Número de Radicado

```
┌─────────────────────────────────────────────────────────────────┐
│                   GENERACIÓN DEL RADICADO                       │
└─────────────────────────────────────────────────────────────────┘

  Datos de entrada:
  ┌─────────────┬─────────────┬───────────────┬──────────────────┐
  │  Vigencia   │  Unidad Org │  Serie CCD    │  Subserie (opt)  │
  │   2026      │  → Entidad  │   código: 01  │   código: 02     │
  └──────┬──────┴──────┬──────┴───────┬───────┴──────────┬───────┘
         │             │              │                  │
         ▼             ▼              │                  │
  ┌─────────┐  ┌────────────────┐    │                  │
  │  2026   │  │  Entidad.code  │    │                  │
  │         │  │  Si existe: AMB│    │                  │
  │         │  │  Si no: deriva │    │                  │
  │         │  │  de iniciales  │    │                  │
  └────┬────┘  └──────┬─────────┘   │                  │
       │               │             │                  │
       └───────────────┴─────────────┴──────────────────┘
                               │
                               ▼
              ┌────────────────────────────┐
              │  Prefijo: 2026.AMB.01.02   │
              └──────────────┬─────────────┘
                             │
                             ▼
              ┌────────────────────────────────────┐
              │  Buscar último radicado con mismo   │
              │  prefijo en el año 2026:           │
              │  SELECT MAX(consecutivo)            │
              │  WHERE filing_number LIKE           │
              │  '2026.AMB.01.02.%'                │
              └──────────────┬─────────────────────┘
                             │
              ┌──────────────┴──────────────────────┐
              │                                     │
              ▼                                     ▼
    ┌─────────────────┐                 ┌─────────────────────┐
    │  Existe último  │                 │  No existe (primero │
    │  → consecutivo +1│                │  del prefijo)        │
    │  → 003          │                 │  → consecutivo = 001 │
    └────────┬────────┘                 └──────────┬──────────┘
             │                                     │
             └─────────────────┬───────────────────┘
                               │
                               ▼
              ┌────────────────────────────┐
              │  Radicado final:           │
              │  2026.AMB.01.02.003.SUR    │
              └────────────────────────────┘
```

---

## Flujo 3: Control de Cumplimiento PDF

```
┌─────────────────────────────────────────────────────────────────┐
│                  CONTROL DE CUMPLIMIENTO PDF                    │
└─────────────────────────────────────────────────────────────────┘

  Al consultar un acto:

  ┌──────────────────────────────┐
  │  pdfDaysRemaining()          │
  │  = 30 - días desde creación  │
  └───────────────┬──────────────┘
                  │
     ┌────────────┼─────────────────────┐
     │            │                     │
     ▼            ▼                     ▼
  ┌────────┐  ┌─────────┐          ┌──────────┐
  │> 7 días│  │ 1-7 días│          │ ≤ 0 días │
  │🟢 OK   │  │🟡 Alerta │          │🔴 Vencido│
  └────────┘  └─────────┘          └──────────┘

  Dashboard muestra en widget "Cumplimiento PDF":

  ┌────────────────────────────────────────────┐
  │  87%          │  8 Vencidos  │  3 x vencer │
  │  con PDF      │  >30 días    │  próx 7 días│
  │  (verde)      │  (rojo)      │  (amarillo) │
  └────────────────────────────────────────────┘
```

---

## Flujo 4: Registro de Inventario FUID

```
┌─────────────────────────────────────────────────────────────────┐
│                   REGISTRO FUID — FLUJO                         │
└─────────────────────────────────────────────────────────────────┘

  Datos de entrada:

  Unidad Org. + Objeto del inventario
         │
         ▼
  Clasificación TRD:
  Serie FUID + Subserie (opcional)
         │
         ▼
  Descripción:
  Título + Descripción
         │
         ▼
  Fechas extremas:
  Fecha inicio | S.F.    →    Fecha fin | S.F.
         │
         ▼
  Ubicación física:
  Caja + Carpeta + Tomo + Folios
         │
         ▼
  Soporte:
  Tipo medio + Tipo unidad almacenamiento + Cantidad
         │
         ▼
  GUARDAR
         │
         ▼
  ┌──────────────────────────────────────────┐
  │  Código de referencia generado:          │
  │  YYYY-COD_UNIDAD-000001                  │
  │  Ej: 2026-ALCAL-000042                   │
  └──────────────────────────────────────────┘
```

---

## Flujo 5: Exportación del Reporte Mensual

```
  Usuario (supervisor / super_admin)
         │
         ▼
  Panel → Reporte Mensual
  Seleccionar mes y año
         │
         ├──────────────────────────────┐
         │                              │
         ▼                              ▼
  Exportar Excel              Enviar por Correo
         │                              │
         ▼                              ▼
  MonthlyReportExport          php artisan
  (3 hojas):                   acts:monthly-report
  1. Resumen KPIs                       │
  2. Por Unidades                       ▼
  3. Sin PDF                   Mail enviado a
         │                     super_admins
         ▼
  Descarga .xlsx al
  equipo del usuario
```

---

## Flujo 6: Control de Acceso por Rol

```
  Usuario intenta acceder a un recurso
         │
         ▼
  ¿Tiene rol super_admin?
     │              │
    SÍ              NO
     │              │
     ▼              ▼
  Acceso total  ¿Tiene rol supervisor?
                    │              │
                   SÍ              NO (rol user)
                    │              │
                    ▼              ▼
               Acceso a       Solo su unidad
               todos los      organizacional
               registros
```
