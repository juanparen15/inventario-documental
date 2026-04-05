---
title: Super Administrador
description: Guía completa para el rol super_admin del Sistema de Inventario Documental.
---

## Responsabilidades del Super Administrador

El `super_admin` tiene acceso total al sistema sin restricciones. Sus responsabilidades principales son:

- Gestionar usuarios, entidades y unidades organizacionales
- Configurar las series y subseries documentales (TRD)
- Supervisar el cumplimiento de toda la institución
- Acceder a reportes mensuales y enviarlos
- Restaurar registros eliminados
- Ver adjuntos confidenciales de cualquier acto

---

## Gestión de Usuarios

### Crear un Usuario

1. Panel → **Usuarios** → **Nuevo Usuario**
2. Completar los campos:

| Campo | Descripción |
|-------|-------------|
| **Nombre** | Primer nombre del usuario |
| **Apellido** | Apellido del usuario |
| **Correo electrónico** | Email de acceso (único en el sistema) |
| **Teléfono** | Contacto (opcional) |
| **Número de documento** | CC o NIT (opcional) |
| **Unidad Organizacional** | Dependencia a la que pertenece |
| **Contraseña** | Mínimo 8 caracteres |
| **Roles** | Asignar uno o más roles |

3. Guardar

### Roles Disponibles

| Rol | Descripción |
|-----|-------------|
| `super_admin` | Acceso total, sin restricciones |
| `supervisor` | Ve todas las unidades, accede a reportes e importación |
| `usuario` | Solo su propia unidad organizacional |

### Asignar Rol Super Admin via Artisan

Para usuarios que necesitan acceso inmediato como super_admin sin crear desde el panel:

```bash
php artisan shield:super-admin --user={ID_USUARIO}
```

---

## Gestión de Entidades

Las entidades son las organizaciones propietarias de las unidades organizacionales.

1. Panel → **Entidades** → **Nueva Entidad**

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre** | Nombre completo de la entidad | `Alcaldía Municipal de Bogotá` |
| **Código** | Siglas para el radicado automático | `AMB` |

:::tip
Si no se define un código, el sistema deriva las siglas automáticamente del nombre de la entidad eliminando artículos y preposiciones: "Alcaldía Municipal de Bogotá" → `AMB`.
:::

---

## Gestión de Unidades Organizacionales

Las dependencias o secretarías dentro de cada entidad.

1. Panel → **Unidades Organizacionales** → **Nueva Unidad**

| Campo | Descripción |
|-------|-------------|
| **Nombre** | Nombre de la dependencia |
| **Código** | Código corto para el código de referencia |
| **Entidad** | Entidad a la que pertenece |
| **Activa** | Si aparece en las listas del sistema |
| **Puede importar** | Habilita la importación masiva desde Excel |

---

## Gestión de Series Documentales

Las series se configuran con un **contexto** que determina en qué módulo se usan:

| Contexto | Módulo |
|----------|--------|
| `CCD` | Actos Administrativos (SUR) |
| `FUID` | Inventario Documental |

### Crear una Serie

1. Panel → **Series Documentales** → **Nueva Serie**

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Código** | Código numérico | `01` |
| **Nombre** | Nombre oficial de la serie | `Acuerdos Municipales` |
| **Contexto** | CCD o FUID | `CCD` |
| **Retención (años)** | Tiempo de conservación | `10` |
| **Disposición final** | CT, E, S, M | `CT` |

:::note
Cambiar el contexto de una serie actualiza automáticamente el contexto de todas sus subseries.
:::

### Crear una Subserie

1. Panel → **Subseries Documentales** → **Nueva Subserie**

| Campo | Descripción |
|-------|-------------|
| **Código** | Código de la subserie |
| **Nombre** | Nombre de la subserie |
| **Serie** | Serie a la que pertenece |

---

## Gestión de Clasificaciones de Actos

Tipos de actos administrativos disponibles al crear un acto CCD.

1. Panel → **Clasificaciones de Actos** → **Nueva Clasificación**

| Campo | Descripción |
|-------|-------------|
| **Nombre** | Tipo de acto (e.g., Decreto, Resolución, Circular) |

---

## Gestión de Medios de Almacenamiento

Soportes físicos disponibles al registrar un inventario FUID.

1. Panel → **Medios de Almacenamiento** → **Nuevo Medio**

---

## Gestión de Niveles de Prioridad

Niveles de prioridad disponibles en registros FUID.

1. Panel → **Niveles de Prioridad** → **Nuevo Nivel**

---

## Ver Registros Eliminados (Soft Delete)

Para ver y restaurar registros eliminados:

1. Ir al módulo (Actos o Inventario)
2. Abrir el panel de **Filtros**
3. Activar **"Registros eliminados"**
4. Seleccionar el registro
5. Usar la acción **Restaurar**

Para eliminar definitivamente (force delete), seleccionar y usar **"Eliminar permanentemente"**.

---

## Log de Auditoría

Ver el historial completo de acciones:

1. Panel → **Log de Actividad**
2. Filtrar por usuario, modelo, fecha o tipo de acción

El log registra automáticamente:
- Creación, edición y eliminación de registros
- Acceso a adjuntos confidenciales (con nota del usuario)
- Importaciones masivas

---

## Gestión de Roles y Permisos (Shield)

1. Panel → **Shield** → **Roles**
2. Seleccionar un rol para ver/editar sus permisos
3. Activar o desactivar permisos granulares por recurso

Los permisos por recurso incluyen: `ver`, `crear`, `editar`, `eliminar`, `restaurar`, `forzar eliminación`.
