---
title: Comandos de Referencia
description: Referencia completa de comandos Artisan, npm y utilidades del Sistema de Inventario Documental.
---

## Comandos de Instalación y Configuración

### Configuración Inicial

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias JavaScript
npm install && npm run build

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones con datos iniciales
php artisan migrate --seed

# Crear enlace de almacenamiento
php artisan storage:link
```

### Filament Shield

```bash
# Generar permisos para TODOS los recursos, páginas y widgets
php artisan shield:generate --all

# Generar permisos solo para recursos
php artisan shield:generate --resource

# Asignar super_admin a un usuario (por ID)
php artisan shield:super-admin --user=1

# Publicar configuración de Shield
php artisan vendor:publish --tag="filament-shield-config"
```

---

## Comandos de Base de Datos

```bash
# Ejecutar migraciones pendientes
php artisan migrate

# Revertir última migración
php artisan migrate:rollback

# Revertir y volver a migrar todo (¡CUIDADO: borra datos!)
php artisan migrate:fresh --seed

# Ver estado de migraciones
php artisan migrate:status

# Ejecutar seeders manualmente
php artisan db:seed

# Ejecutar un seeder específico
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

## Comandos de Desarrollo

### Servidor de Desarrollo Completo

```bash
# Inicia servidor, queue, logs y vite en paralelo
composer run dev
```

Equivale a ejecutar simultáneamente:

```bash
php artisan serve          # http://localhost:8000
php artisan queue:listen --tries=1 --timeout=0
php artisan pail --timeout=0
npm run dev                # Vite hot-reload
```

### Solo Servidor PHP

```bash
php artisan serve
php artisan serve --host=0.0.0.0 --port=8000
```

---

## Comandos de Producción

### Optimización (ejecutar en cada despliegue)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components
```

### Limpiar Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Build de Assets

```bash
# Build para producción
npm run build

# Desarrollo con hot-reload
npm run dev
```

---

## Comandos de Cola (Queue)

```bash
# Modo desarrollo (procesa inmediatamente, reinicia tras errores)
php artisan queue:listen --tries=1 --timeout=0

# Modo producción (daemon)
php artisan queue:work --daemon --tries=3 --timeout=60

# Ver trabajos fallidos
php artisan queue:failed

# Reintentar trabajos fallidos
php artisan queue:retry all

# Eliminar trabajos fallidos
php artisan queue:flush
```

---

## Comandos de Reportes

```bash
# Generar y enviar reporte mensual de actos administrativos
php artisan acts:monthly-report

# Enviar reporte de un mes específico (si implementado)
php artisan acts:monthly-report --month=3 --year=2026
```

---

## Comandos de Mantenimiento

```bash
# Poner en mantenimiento
php artisan down --message="En mantenimiento. Volvemos pronto." --retry=60

# Restaurar de mantenimiento
php artisan up

# Ver logs en tiempo real
php artisan pail

# Limpiar logs
> storage/logs/laravel.log

# Generar usuario Filament interactivamente
php artisan make:filament-user

# Tinker (REPL interactivo de Laravel)
php artisan tinker
```

---

## Comandos de Importación

```bash
# Ver errores de importación desde Tinker
php artisan tinker
>>> App\Models\ImportError::all()

# Limpiar errores de importación (si el modelo existe)
php artisan tinker
>>> App\Models\ImportError::truncate()
```

---

## Comandos npm para Documentación

Desde `public/docs/`:

```bash
# Servidor de desarrollo de la documentación
npm run dev        # http://localhost:4321

# Build de producción de la documentación
npm run build      # Genera en ../docs-build/

# Preview del build
npm run preview
```

---

## Variables de Entorno de Referencia Rápida

```env
# Críticas en producción
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://tu-dominio.com
APP_TIMEZONE=America/Bogota

# Base de datos
DB_CONNECTION=mysql
DB_DATABASE=inventario_documental

# Correo
MAIL_MAILER=smtp
MAIL_HOST=smtp.ejemplo.com
MAIL_PORT=587

# Cola y sesión
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```
