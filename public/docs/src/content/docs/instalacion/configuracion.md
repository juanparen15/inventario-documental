---
title: Configuración
description: Configuración de variables de entorno, correo, permisos y ajustes del sistema.
---

## Variables de Entorno Principales

El archivo `.env` controla toda la configuración del sistema. A continuación las variables más importantes:

### Aplicación

```env
APP_NAME="Inventario Documental"
APP_ENV=production          # local | production
APP_KEY=base64:...          # Generado con php artisan key:generate
APP_DEBUG=false             # false en producción
APP_TIMEZONE=America/Bogota # Zona horaria oficial
APP_URL=https://tu-dominio.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CO
```

### Base de Datos

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventario_documental
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### Correo Electrónico (SMTP)

El sistema envía notificaciones y reportes mensuales por correo. Configurar un servidor SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.ejemplo.com
MAIL_PORT=587
MAIL_USERNAME=correo@ejemplo.com
MAIL_PASSWORD=tu_contraseña
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@tu-dominio.com"
MAIL_FROM_NAME="Inventario Documental"
```

:::tip
Para pruebas locales puedes usar [Mailtrap](https://mailtrap.io/) o configurar `MAIL_MAILER=log` para ver los correos en los logs.
:::

### Sesiones y Cache

```env
SESSION_DRIVER=database    # Sesiones en base de datos
SESSION_LIFETIME=120       # Minutos de inactividad hasta cerrar sesión
CACHE_STORE=database       # Cache en base de datos
QUEUE_CONNECTION=database  # Cola de trabajos en base de datos
```

### Almacenamiento de Archivos

```env
FILESYSTEM_DISK=public     # Almacenamiento local público
```

Los archivos subidos se almacenan en `storage/app/public/` y se acceden via `storage/` (requiere `php artisan storage:link`).

---

## Configuración de Filament Shield

Después de ejecutar `php artisan migrate --seed`, generar todos los permisos:

```bash
# Generar permisos para todos los Resources, Pages y Widgets
php artisan shield:generate --all

# Asignar super_admin a un usuario específico (por ID)
php artisan shield:super-admin --user=1
```

Los permisos generados incluyen acciones por recurso:
- `view`, `view_any`, `create`, `update`, `delete`, `delete_any`
- `restore`, `restore_any`, `force_delete`, `force_delete_any`
- `page_*` para páginas del panel
- `widget_*` para widgets del dashboard

---

## Configuración de la Cola de Trabajos

El envío de reportes por correo usa el sistema de colas de Laravel. Para procesarlas:

```bash
# Modo desarrollo (procesa inmediatamente)
php artisan queue:listen --tries=1 --timeout=0

# Modo producción (como servicio del sistema)
php artisan queue:work --daemon --tries=3 --timeout=60
```

---

## Configuración de Zona Horaria

La zona horaria está configurada en `APP_TIMEZONE=America/Bogota` y en `config/app.php`:

```php
'timezone' => env('APP_TIMEZONE', 'America/Bogota'),
```

Todos los timestamps se almacenan en UTC en la base de datos y se muestran en hora colombiana en la interfaz.

---

## Configuración de Permisos de Importación

Para habilitar la importación masiva de Excel en una unidad organizacional, un `super_admin` debe activar el campo `can_import` en la unidad:

1. Panel → **Unidades Organizacionales** → Editar
2. Activar la opción **"Puede importar"**
3. Guardar

Solo las unidades con `can_import = true` verán la opción de importar en su menú.

---

## Configuración de Entidades

Cada unidad organizacional pertenece a una entidad. El código de la entidad se usa en el radicado automático:

1. Panel → **Entidades** → Crear/Editar
2. Completar el campo **Código** (e.g., `AMB` para Alcaldía Municipal de Bogotá)
3. Si no se define código, el sistema lo deriva automáticamente de las iniciales del nombre

---

## Configuración de Series Documentales

Las series y subseries se configuran con un contexto:

- **FUID:** Para el módulo de Inventario Documental
- **CCD:** Para el módulo de Actos Administrativos

```
Panel → Series Documentales → Crear
  - Código: 01
  - Nombre: Acuerdos Municipales
  - Contexto: CCD
  - Retención: 10 años
  - Disposición final: CT (Conservación Total)
```

---

## Configuración de Notificaciones del Panel

Las notificaciones del panel se actualizan automáticamente cada 60 segundos via polling. No requiere configuración adicional.
