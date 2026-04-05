---
title: Actos Administrativos (CCD/SUR)
description: Guía completa para crear, editar y gestionar actos administrativos en el Sistema de Inventario Documental.
---

## ¿Qué es un Acto Administrativo?

Un acto administrativo es un documento oficial emitido por la entidad (decreto, resolución, circular, etc.) que se registra en el Sistema Unificado de Registro (SUR) bajo la clasificación del Cuadro de Clasificación Documental (CCD).

Al registrar un acto, el sistema asigna automáticamente un **número de radicado** único.

---

## Crear un Acto Administrativo

1. En el menú lateral ir a **Actos Administrativos**
2. Hacer clic en el botón **Nuevo Acto** (esquina superior derecha)
3. Completar el formulario

### Campos del Formulario

#### Información Básica

| Campo | Descripción | Requerido |
|-------|-------------|-----------|
| **Unidad Organizacional** | Tu unidad (prellenada automáticamente para usuarios) | Sí |
| **Vigencia** | Año del acto (por defecto: año actual) | Sí |
| **Serie Documental** | Serie del CCD a la que pertenece el acto | Sí |
| **Subserie Documental** | Subserie del CCD (opcional, depende de la serie) | No |
| **Clasificación** | Tipo de acto (Decreto, Resolución, Circular, etc.) | No |

#### Número de Radicado (Automático)

Al seleccionar la Unidad Organizacional y la Serie Documental, el campo **Número de Radicado** muestra una vista previa del consecutivo que se asignará:

```
Vista previa: 2026.AMB.01.02.001.SUR
                ↑    ↑   ↑  ↑   ↑
              Año  Ent Ser Sub Cons
```

:::note
El radicado definitivo se genera al guardar el acto. No se puede modificar manualmente.
:::

#### Descripción del Acto

| Campo | Descripción | Límite |
|-------|-------------|--------|
| **Asunto / Objeto** | Descripción del acto administrativo | 1000 caracteres |
| **Folios** | Número de folios (se calcula automáticamente del PDF) | Automático |
| **Notas** | Observaciones internas adicionales | Sin límite |

#### Adjuntos PDF

Sección para subir el documento oficial del acto en formato PDF:

- Formatos aceptados: `.pdf`
- Tamaño máximo: **200 KB por archivo**
- El sistema cuenta automáticamente los folios del PDF
- Se pueden adjuntar múltiples archivos

:::tip
Si el PDF pesa más de 200 KB, comprimirlo con herramientas como [ilovepdf.com](https://www.ilovepdf.com/) o Adobe Acrobat antes de subirlo.
:::

#### Sección Confidencial

Adjuntos que **solo pueden ver** el creador del acto y el `super_admin`:

- Mismas restricciones de formato y tamaño que los adjuntos regulares
- Cuando un `super_admin` accede a adjuntos confidenciales, queda registrado en el log de auditoría
- La sección completa es invisible para otros usuarios

4. Hacer clic en **Guardar**

---

## Plazo para Adjuntar el PDF (30 días)

Cada acto tiene un plazo de **30 días calendario** desde su creación para adjuntar el PDF del documento oficial.

### Estados del Plazo

| Estado | Descripción | Acción |
|--------|-------------|--------|
| 🟢 **En plazo** | Quedan más de 7 días | Subir el PDF normalmente |
| 🟡 **Por vencer** | Quedan 7 días o menos | Subir el PDF con urgencia |
| 🔴 **Vencido** | Superó los 30 días | Requiere registrar la razón de tardanza |

### Subir PDF con Tardanza (>30 días)

Si intentas adjuntar el PDF después de 30 días:

1. El sistema solicita registrar la **razón de la subida tardía**
2. Escribir la justificación en el campo de texto
3. Adjuntar el PDF
4. Guardar

---

## Ver Adjuntos de un Acto

En la tabla de Actos Administrativos:

1. Localizar el acto
2. Hacer clic en el botón **Ver adjuntos** (ícono de documento)
3. Se abre un modal con la lista de PDFs adjuntos
4. Hacer clic en cada archivo para descargarlo

:::note
Los adjuntos confidenciales solo aparecen en el modal si eres el creador del acto o tienes rol `super_admin`.
:::

---

## Editar un Acto Administrativo

1. En la tabla, hacer clic en el ícono de **lápiz** (editar) del acto
2. Modificar los campos necesarios
3. Guardar

:::caution
El **número de radicado** no se puede modificar una vez asignado.
:::

---

## Filtros y Búsqueda

La tabla de actos permite filtrar por:

- **Vigencia** (año)
- **Unidad Organizacional**
- **Serie Documental**
- **Rango de fechas** (fecha de creación)
- **Actos eliminados** (soft delete — solo super_admin)

Para buscar, usar la barra de búsqueda en la parte superior de la tabla. Busca en: número de radicado, asunto y notas.

---

## Eliminar un Acto

Los actos se eliminan de forma **suave (soft delete)**: el registro no se borra definitivamente, solo se oculta. Para eliminar:

1. Seleccionar el acto (checkbox)
2. Clic en **Acciones masivas** → **Eliminar**

Para restaurar actos eliminados, activar el filtro **"Registros eliminados"** y usar la acción **Restaurar**.

---

## Indicadores en la Tabla

| Columna | Descripción |
|---------|-------------|
| **Vigencia** | Año del acto |
| **Radicado** | Número de radicado (copiable) |
| **Serie / Subserie** | Clasificación CCD |
| **Asunto** | Descripción del acto |
| **Folios** | Número de páginas del PDF |
| **🔒** | Ícono si tiene adjuntos confidenciales |
| **Días PDF** | Días restantes/vencidos para adjuntar PDF |
| **Creado por** | Usuario que creó el acto |
| **Fecha** | Fecha de creación |
