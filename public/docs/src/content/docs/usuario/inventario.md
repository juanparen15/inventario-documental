---
title: Inventario Documental (FUID)
description: Guía para registrar y gestionar el inventario documental según el Formato Único de Inventario Documental (FUID).
---

## ¿Qué es el FUID?

El **Formato Único de Inventario Documental (FUID)** es el estándar colombiano para el registro de documentos en los archivos de gestión, central e histórico. El sistema implementa este formato digitalmente, permitiendo registrar expedientes y documentos con su clasificación TRD, ubicación física y metadatos.

---

## Crear un Registro de Inventario

1. En el menú lateral ir a **Inventario Documental**
2. Hacer clic en **Nuevo Registro** (esquina superior derecha)
3. Completar el formulario

### Sección 1: Identificación

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Unidad Organizacional** | Dependencia responsable del documento | Sí |
| **Objeto del Inventario** | Propósito conforme al FUID (ver tabla abajo) | Sí |

**Objetos del inventario disponibles:**

| Opción | Descripción |
|--------|-------------|
| Transferencias Primarias | Documentos de archivo de gestión a archivo central |
| Transferencias Secundarias | De archivo central a archivo histórico |
| Valoración de Fondos Acumulados | Fondos sin TRD aplicada |
| Fusión y Supresión de Entidades | Reorganización institucional |
| Inventarios Individuales | Inventario de producción normal |

### Sección 2: Clasificación Documental (TRD)

| Campo | Descripción |
|-------|-------------|
| **Serie Documental** | Serie del FUID (e.g., `01 - Acuerdos`) |
| **Subserie Documental** | Subserie correspondiente (opcional) |

:::note
Las series y subseries mostradas son las configuradas con **contexto FUID**. Si no aparece la que necesitas, contacta al administrador.
:::

### Sección 3: Descripción de la Unidad Documental

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Título** | Nombre del expediente o documento | Sí |
| **Descripción** | Detalle adicional sobre el contenido | No |

### Sección 4: Fechas Extremas

| Campo | Descripción |
|-------|-------------|
| **Fecha inicial** | Primera fecha del documento/expediente |
| **Fecha final** | Última fecha del documento/expediente |

Para expedientes sin fecha definida, activar el toggle **"Sin Fecha"** (S.F.) en el campo correspondiente.

### Sección 5: Ubicación Física

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Caja** | Número de caja donde se encuentra | `Caja 15` |
| **Carpeta** | Número de carpeta dentro de la caja | `Carpeta 3` |
| **Tomo/Volumen** | Número de tomo si aplica | `Tomo 1` |
| **Folios** | Rango de folios del documento | `1-50`, `51-100` |

### Sección 6: Soporte

| Campo | Descripción |
|-------|-------------|
| **Soporte / Medio** | Tipo de soporte: papel, electrónico, microfilm, etc. |
| **Tipo unidad almacenamiento** | Para soportes digitales: CD, DVD, USB, disco duro |
| **Cantidad unidades** | Número de unidades del soporte digital |

### Sección 7: Adjuntos

Subir archivos digitales asociados al registro (máximo **20 MB por archivo**):

- Escaneos de documentos físicos
- Archivos digitales del expediente
- Documentos de soporte

### Sección 8: Información Adicional

| Campo | Descripción |
|-------|-------------|
| **Nivel de prioridad** | Prioridad de conservación del documento |
| **Notas** | Condición física, faltantes, observaciones especiales |

### Código de Referencia (Automático)

El sistema genera automáticamente el código de referencia con el formato:

```
YYYY-COD_UNIDAD-000001
```

Ejemplo: `2026-ALCAL-000042`

Este código aparece en la ficha del registro una vez guardado.

4. Hacer clic en **Guardar**

---

## Ver Adjuntos de un Registro

En la tabla de inventario:

1. Localizar el registro
2. Hacer clic en el ícono de **adjuntos** en la columna correspondiente
3. Se abre un modal con la lista de archivos
4. Hacer clic en cada archivo para descargarlo o visualizarlo

---

## Filtros y Búsqueda

La tabla de inventario permite filtrar por:

- **Unidad Organizacional**
- **Objeto del inventario**
- **Serie documental**
- **Subserie documental**
- **Rango de fechas**
- **Soporte/Medio**

La barra de búsqueda busca en: código de referencia, título y descripción.

---

## Indicadores en la Tabla

| Columna | Descripción |
|---------|-------------|
| **Cód. Referencia** | Código único (copiable con clic) |
| **Unidad** | Dependencia responsable |
| **Objeto** | Propósito del inventario |
| **Serie** | Clasificación TRD |
| **Caja / Carpeta** | Ubicación física |
| **Fechas** | Rango de fechas extremas (S.F. si no aplica) |
| **Adjuntos** | Número de archivos adjuntos (botón para ver) |
| **Creado** | Fecha de creación del registro |

---

## Importación Masiva desde Excel

Si tu unidad organizacional tiene habilitada la importación (`can_import`):

1. En el menú lateral, acceder a **Importar Inventario**
2. Descargar la plantilla Excel
3. Completar los registros siguiendo el formato
4. Subir el archivo Excel
5. Revisar los errores en **Errores de Importación** si los hubiera

:::caution
La importación masiva solo está disponible si el administrador habilitó esta función para tu unidad.
:::
