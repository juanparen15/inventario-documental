---
title: Permisos y Autorización
description: Documentación técnica del sistema de roles y permisos con Spatie Permission y Filament Shield.
---

## Arquitectura de Permisos

El sistema usa dos capas complementarias:

1. **Spatie Laravel Permission:** Define roles y permisos a nivel de base de datos
2. **Filament Shield:** Integra esos permisos con el panel de Filament y genera automáticamente permisos para cada recurso

```
Usuario ──── tiene ────▶ Roles ──── tienen ────▶ Permisos
                                                      │
                                                      ▼
                                            Acceso a Resources,
                                            Pages y Widgets
                                            del panel Filament
```

---

## Roles del Sistema

### `super_admin`

- **Acceso:** Total, sin restricciones
- **Gate:** Intercepta el gate `before` → siempre retorna `true`
- **Restricciones:** Ninguna

### `supervisor`

- **Acceso:** Todos los recursos de todos los usuarios y unidades
- **Sin acceso:** Gestión de usuarios, entidades, series, configuración de Shield
- **Permisos específicos:** `view_any`, `view`, `create`, `update` en actos e inventario + acceso a `MonthlyReportPage`

### `usuario` (`user`)

- **Acceso:** Solo registros de su propia unidad organizacional
- **Sin acceso:** Reportes, importación (a menos que tenga `can_import`), gestión de configuración
- **Restricción extra:** Scope de query filtrado por `organizational_unit_id`

---

## Permisos Generados por Shield

Para cada **Resource**, Shield genera estos permisos:

| Permiso | Descripción |
|---------|-------------|
| `view_{resource}` | Ver un registro específico |
| `view_any_{resource}` | Ver la lista de registros |
| `create_{resource}` | Crear un nuevo registro |
| `update_{resource}` | Editar un registro existente |
| `delete_{resource}` | Eliminar (soft delete) |
| `delete_any_{resource}` | Eliminar múltiples registros |
| `restore_{resource}` | Restaurar registro eliminado |
| `restore_any_{resource}` | Restaurar múltiples |
| `force_delete_{resource}` | Eliminar permanentemente |
| `force_delete_any_{resource}` | Eliminar múltiples permanentemente |
| `replicate_{resource}` | Duplicar un registro |

Para **Pages**: `page_{PageName}`

Para **Widgets**: `widget_{WidgetName}`

### Permisos de Actos Administrativos

```
view_administrative_act
view_any_administrative_act
create_administrative_act
update_administrative_act
delete_administrative_act
delete_any_administrative_act
restore_administrative_act
force_delete_administrative_act
```

### Permisos de Inventario

```
view_inventory_record
view_any_inventory_record
create_inventory_record
update_inventory_record
delete_inventory_record
restore_inventory_record
```

---

## Exclusiones de Shield

Las siguientes entidades están **excluidas** de la generación de permisos (siempre accesibles):

**Pages excluidas:** `Dashboard`

**Widgets excluidos:** `AccountWidget`, `FilamentInfoWidget`

---

## Comandos de Gestión de Permisos

```bash
# Generar/actualizar permisos para todos los recursos
php artisan shield:generate --all

# Solo para recursos específicos
php artisan shield:generate --resource=AdministrativeActResource

# Asignar rol super_admin a un usuario
php artisan shield:super-admin --user=1

# Publicar configuración
php artisan vendor:publish --tag="filament-shield-config"
```

---

## Configuración de Shield (`config/filament-shield.php`)

```php
'super_admin' => [
    'enabled' => true,
    'name' => 'super_admin',
    'intercept_gate' => 'before',  // super_admin bypasses all gate checks
],

'panel_user' => [
    'enabled' => true,
    'name' => 'usuario',  // Nombre del rol básico
],

'permission_prefixes' => [
    'resource' => [
        'view', 'view_any', 'create', 'update',
        'restore', 'restore_any', 'replicate', 'reorder',
        'delete', 'delete_any', 'force_delete', 'force_delete_any',
    ],
    'page' => 'page',
    'widget' => 'widget',
],
```

---

## Restricción por Unidad Organizacional

Además de los permisos de Shield, los recursos `AdministrativeActResource` e `InventoryRecordResource` aplican un **scope adicional** para el rol `user`:

```php
// En el método table() o getEloquentQuery() del Resource:
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();

    if (!auth()->user()->hasRole(['super_admin', 'supervisor'])) {
        $query->where(
            'organizational_unit_id',
            auth()->user()->organizational_unit_id
        );
    }

    return $query;
}
```

---

## Acceso a Adjuntos Confidenciales

La sección confidencial en los formularios se oculta dinámicamente:

```php
// En el formulario de AdministrativeActResource:
Section::make('Adjuntos Confidenciales')
    ->hidden(function (?AdministrativeAct $record) {
        // Ocultar si no es el creador ni super_admin
        if (!$record) return false; // En creación: el usuario es el creador
        $user = auth()->user();
        return !($user->hasRole('super_admin') || $record->created_by === $user->id);
    })
```

Cuando un `super_admin` accede a los adjuntos confidenciales, se registra en el log de auditoría:

```php
activity()
    ->performedOn($record)
    ->withProperties(['accessed_confidential' => true])
    ->log('super_admin accessed confidential attachments');
```

---

## Gestión desde el Panel (Shield UI)

1. Panel → **Administración** → **Roles** (Shield)
2. Seleccionar un rol para ver y editar sus permisos
3. Los permisos se agrupan por: Resources, Pages, Widgets
4. Marcar/desmarcar permisos individuales
5. Guardar cambios

Los cambios surten efecto inmediatamente (sin necesidad de limpiar caché en la mayoría de entornos).
