---
title: Supervisor
description: Guía para el rol supervisor en el Sistema de Inventario Documental.
---

## Responsabilidades del Supervisor

El `supervisor` tiene visibilidad sobre **todas las unidades organizacionales** de la institución, con acceso especial a reportes y herramientas de supervisión, pero sin capacidad de modificar la configuración del sistema.

---

## Lo Que Puede Hacer el Supervisor

| Función | Acceso |
|---------|--------|
| Ver actos de todas las unidades | ✓ |
| Ver inventario de todas las unidades | ✓ |
| Crear y editar actos e inventario | ✓ |
| Acceder a reportes mensuales | ✓ |
| Exportar Excel y PDF de reportes | ✓ |
| Enviar reportes por correo | ✓ |
| Importar datos desde Excel | ✓ |
| Gestionar usuarios | ✗ |
| Configurar entidades/series | ✗ |
| Ver adjuntos confidenciales ajenos | ✗ |

---

## Dashboard del Supervisor

El supervisor ve el dashboard completo sin restricción por unidad:

- **Estadísticas del Sistema:** Totales de toda la institución
- **Cumplimiento PDF:** Vencidos y por vencer de todas las unidades
- **Producción por Dependencia:** Comparativo FUID vs CCD de todas las unidades
- **Evolución histórica:** Datos institucionales completos

---

## Supervisar Actos Administrativos

### Ver Todos los Actos

El supervisor puede ver actos de **todas** las unidades organizacionales sin excepción. En la tabla de Actos Administrativos aparecen columnas adicionales:

- **Entidad:** Entidad dueña de la unidad
- **Unidad:** Dependencia que creó el acto

### Filtrar por Unidad

Para analizar una dependencia específica:

1. Abrir **Filtros** en la tabla de Actos
2. Seleccionar **Unidad Organizacional**
3. Elegir la dependencia a revisar

### Identificar Actos Vencidos

Para ver actos sin PDF vencidos (>30 días):

1. Ir a **Reporte Mensual**
2. En la hoja "Sin PDF" del Excel exportado aparecen todos los actos vencidos
3. O usar los filtros de la tabla y ordenar por **"Días PDF"** ascendente

---

## Supervisar Inventario Documental

Al igual que con los actos, el supervisor ve el inventario de todas las unidades. Puede filtrar por unidad para revisar la producción de cada dependencia.

---

## Reportes Mensuales

### Generar y Revisar el Reporte

1. Panel → **Reporte Mensual**
2. Navegar al mes de interés con los botones ← →
3. Revisar los KPIs:
   - Total de actos del mes y tendencia
   - % de cumplimiento PDF institucional
   - Actos vencidos sin PDF

### Exportar a Excel

El reporte Excel contiene 3 hojas con información detallada:

1. **Resumen:** KPIs del mes con calificación de cumplimiento
2. **Por Unidades:** Desglose por entidad → unidad → serie
3. **Sin PDF:** Listado completo de actos sin PDF con estado

### Exportar a PDF

Para una versión lista para imprimir o archivar.

### Enviar por Correo

El botón **"Enviar Reporte"** dispara el comando de notificación que envía el informe a los correos configurados como administradores.

---

## Importación Masiva de Datos

El supervisor puede importar registros de inventario FUID desde Excel:

1. Panel → **Importar Inventario** (si la unidad tiene `can_import` habilitado)
2. Descargar la plantilla Excel
3. Completar los datos
4. Subir el archivo
5. Revisar errores en **Errores de Importación**

También puede importar permisos de usuarios desde la sección de importación de permisos.

---

## Buenas Prácticas para Supervisores

1. **Revisar el reporte mensual** los primeros 5 días del mes siguiente
2. **Alertar a las unidades** con bajo cumplimiento PDF antes de que venzan los plazos
3. **Verificar tendencias** en el gráfico de evolución histórica para detectar unidades con baja producción
4. **Exportar y archivar** el Excel mensual para conservar el histórico de cumplimiento
