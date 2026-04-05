---
title: Introducción
description: Introducción al Sistema de Inventario Documental para entidades gubernamentales colombianas.
---

## ¿Qué es el Sistema de Inventario Documental?

El **Sistema de Inventario Documental** es una aplicación web desarrollada para automatizar y centralizar la gestión de documentos oficiales en entidades gubernamentales colombianas. Integra dos grandes módulos:

- **SUR/CCD — Actos Administrativos:** Registro de actos oficiales con numeración automática de radicado, control de adjuntos PDF y seguimiento de cumplimiento normativo.
- **FUID — Inventario Documental:** Inventario de fondos documentales con clasificación por Tabla de Retención Documental (TRD), ubicación física y código de referencia automático.

## Problema que Resuelve

Antes de este sistema, la gestión documental presentaba estos desafíos:

- **Numeración manual:** Los radicados se asignaban manualmente, generando errores y duplicados.
- **Sin control de cumplimiento:** No existía seguimiento automatizado del plazo de 30 días para adjuntar el PDF del acto.
- **Inventario disperso:** Los registros FUID se llevaban en hojas de cálculo sin trazabilidad.
- **Sin control de acceso:** Cualquier usuario podía ver o modificar documentos confidenciales.
- **Reportes manuales:** Los informes mensuales se elaboraban a mano, con alto riesgo de error.

## Solución Implementada

### Para Usuarios de Dependencias
- Creación de actos administrativos con radicado automático en formato `YYYY.ENTIDAD.SERIE.SUBSERIE.###.SUR`
- Adjuntar PDFs con indicador visual del plazo restante (30 días)
- Sección confidencial protegida: solo el creador y super_admin pueden verla
- Registro FUID con clasificación TRD, ubicación física y código de referencia automático

### Para Supervisores
- Vista centralizada de todos los actos de la institución
- Acceso a reportes mensuales con KPIs de cumplimiento
- Importación masiva de datos desde Excel

### Para Administradores
- Gestión completa de usuarios, entidades y unidades organizacionales
- Configuración de series y subseries documentales (TRD)
- Auditoría de todas las acciones del sistema
- Envío automático de reportes mensuales por correo

## Flujo General del Sistema

```
┌──────────────────┐     ┌─────────────────────┐     ┌─────────────────┐
│  Usuario crea    │────▶│  Radicado automático │────▶│  Adjunta PDF    │
│  Acto Admin.     │     │  YYYY.ENT.SER.###SUR │     │  (30 días)      │
└──────────────────┘     └─────────────────────┘     └────────┬────────┘
                                                               │
                         ┌─────────────────────────────────────┘
                         ▼
               ┌──────────────────┐     ┌──────────────────┐
               │  Dashboard muestra│    │  Reporte mensual │
               │  cumplimiento PDF │────▶│  Excel / PDF     │
               └──────────────────┘     └──────────────────┘
```

## Beneficios Clave

| Beneficio | Descripción |
|-----------|-------------|
| **Numeración automática** | Radicados sin errores ni duplicados |
| **Control de plazos** | Alertas de vencimiento del plazo PDF (30 días) |
| **Trazabilidad completa** | Quién creó, editó o accedió a cada documento |
| **Seguridad** | Archivos confidenciales solo visibles al creador y super_admin |
| **Reportes automáticos** | Informes mensuales generados y enviados automáticamente |
| **Estándares colombianos** | Cumple con TRD, FUID y normativa archivística colombiana |

## Requisitos para Usar el Sistema

1. **Credenciales de acceso:** Email y contraseña asignados por el administrador
2. **Navegador moderno:** Chrome, Firefox o Edge actualizados
3. **Conexión a internet:** Para acceder al panel web

## Próximos Pasos

- [Ver las características completas](/docs/inicio/caracteristicas/)
- [Guía de primeros pasos](/docs/usuario/primeros-pasos/)
- [Instalación del sistema](/docs/instalacion/requisitos/) (para desarrolladores)
