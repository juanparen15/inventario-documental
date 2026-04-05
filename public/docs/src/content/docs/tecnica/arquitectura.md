---
title: Arquitectura del Sistema
description: Documentación técnica de la arquitectura del Sistema de Inventario Documental.
---

## Stack Tecnológico

### Backend

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| PHP | 8.2+ | Lenguaje de programación |
| Laravel | 12.x | Framework web MVC |
| FilamentPHP | 3.2 | Panel de administración |
| MySQL / MariaDB | 8.0+ / 10.11+ | Base de datos relacional |
| Composer | 2.x | Gestión de dependencias PHP |

### Frontend

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| Vite | 7.x | Bundler y hot-reload |
| Tailwind CSS | 4.0 | Framework CSS |
| Alpine.js | incluido con Filament | Reactividad en la UI |
| Blade | Laravel | Motor de plantillas |
| Driver.js | incluido | Tour interactivo de onboarding |

### Paquetes PHP Clave

| Paquete | Versión | Propósito |
|---------|---------|-----------|
| `bezhansalleh/filament-shield` | 3.2 | Control de acceso basado en roles para el panel |
| `spatie/laravel-permission` | — | Roles y permisos a nivel de modelo |
| `spatie/laravel-activitylog` | 4.8 | Auditoría de acciones |
| `spatie/laravel-medialibrary` | 11.x | Gestión de archivos multimedia |
| `barryvdh/laravel-dompdf` | 3.1 | Generación de PDFs |
| `pxlrbt/filament-excel` | 2.3 | Exportación Excel desde Filament |
| `smalot/pdfparser` | 2.12 | Parsing de PDFs (conteo de folios) |
| `awcodes/light-switch` | 1.0 | Toggle dark/light mode |

---

## Estructura de Carpetas del Proyecto

```
inventario-documental/
├── app/
│   ├── Console/
│   │   └── Commands/          ← Comandos Artisan personalizados
│   │       └── MonthlyActsReport.php
│   ├── Exports/               ← Clases de exportación Excel
│   │   └── MonthlyReportExport.php (+ Sheets)
│   ├── Filament/
│   │   ├── Actions/           ← Acciones personalizadas del panel
│   │   ├── Imports/           ← Clases de importación Excel
│   │   ├── Pages/             ← Páginas del panel (5)
│   │   │   ├── Dashboard.php
│   │   │   ├── MonthlyReportPage.php
│   │   │   ├── PasswordChangePage.php
│   │   │   ├── ImportErrorsPage.php
│   │   │   └── ImportPermissionsPage.php
│   │   ├── Resources/         ← CRUD Resources (11)
│   │   └── Widgets/           ← Widgets del dashboard (11)
│   ├── Http/
│   │   └── Controllers/       ← Solo para exportaciones de rutas web
│   ├── Models/                ← Modelos Eloquent (13+)
│   ├── Notifications/         ← Notificaciones del sistema
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   ├── Support/               ← Clases de soporte
│   └── Traits/
│       └── HasAuditLog.php    ← Trait de auditoría compartido
├── database/
│   ├── migrations/            ← 40+ migraciones
│   ├── factories/             ← Factories para pruebas
│   └── seeders/               ← Seeders de datos iniciales
├── public/
│   ├── docs/                  ← Proyecto Starlight (documentación)
│   └── storage/               ← Enlace simbólico a storage/app/public
├── resources/
│   ├── css/                   ← Tailwind CSS
│   ├── js/                    ← JavaScript (Vite entry points)
│   └── views/
│       └── filament/          ← Vistas Blade personalizadas
├── routes/
│   └── web.php                ← Rutas (exportaciones de reportes)
├── storage/
│   ├── app/public/            ← Archivos subidos por usuarios
│   │   ├── avatars/           ← Avatares de usuarios
│   │   └── imports/           ← Archivos de importación
│   └── logs/                  ← Logs de aplicación
└── tests/                     ← Suite de pruebas PHPUnit
```

---

## Patrón de Arquitectura

El sistema sigue el patrón **MVC de Laravel** con una capa de administración de **Filament**:

```
┌─────────────────────────────────────────────────────────┐
│                    CAPA DE PRESENTACIÓN                 │
│  FilamentPHP Panel → Blade Views → Alpine.js → Vite    │
├─────────────────────────────────────────────────────────┤
│                    CAPA DE NEGOCIO                      │
│  Resources (CRUD) → Actions → Notifications → Exports   │
├─────────────────────────────────────────────────────────┤
│                    CAPA DE DATOS                        │
│  Eloquent Models → Migrations → Seeders                 │
├─────────────────────────────────────────────────────────┤
│                    SERVICIOS TRANSVERSALES              │
│  Spatie Permission │ ActivityLog │ MediaLibrary │ Queue  │
└─────────────────────────────────────────────────────────┘
```

---

## Autenticación y Autorización

### Autenticación

Laravel Sanctum gestiona las sesiones. La sesión se almacena en base de datos (`SESSION_DRIVER=database`).

### Autorización con Filament Shield

Filament Shield integra Spatie Laravel Permission con el panel de Filament, generando automáticamente permisos para cada recurso, página y widget.

**Flujo de autorización:**

1. Usuario intenta acceder a un recurso en el panel
2. Filament consulta `canAccess()` → Shield verifica el permiso
3. Si tiene el rol `super_admin`, acceso garantizado via `gate:before`
4. Si no, verifica el permiso específico del recurso

### Restricción por Unidad Organizacional

Los recursos `AdministrativeActResource` e `InventoryRecordResource` aplican un scope adicional para usuarios con rol `user`:

```php
// Solo registros de la unidad del usuario autenticado
if (!auth()->user()->hasRole(['super_admin', 'supervisor'])) {
    $query->where('organizational_unit_id', auth()->user()->organizational_unit_id);
}
```

---

## Sistema de Colas (Queue)

El sistema usa colas de base de datos para:

- Envío de reportes mensuales por correo
- Procesamiento de importaciones masivas
- Notificaciones del sistema

**Configuración:** `QUEUE_CONNECTION=database`

Los trabajos se almacenan en la tabla `jobs`. Los fallidos en `failed_jobs`.

---

## Almacenamiento de Archivos

Los archivos PDF de actos e inventario se almacenan en `storage/app/public/` mediante el disco `public` de Laravel.

**Acceso:** `storage/app/public/` ←→ `public/storage/` (vía `storage:link`)

Los adjuntos se guardan como arrays JSON en los campos `attachments` y `confidential_attachments` de `administrative_acts`.

---

## Configuración del Entorno

| Variable | Valor en desarrollo | Valor en producción |
|----------|---------------------|---------------------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `SESSION_DRIVER` | `database` | `database` |
| `QUEUE_CONNECTION` | `database` | `database` |
| `CACHE_STORE` | `database` | `redis` (recomendado) |
| `APP_TIMEZONE` | `America/Bogota` | `America/Bogota` |
