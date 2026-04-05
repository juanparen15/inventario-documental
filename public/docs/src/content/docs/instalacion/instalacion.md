---
title: Instalación
description: Guía paso a paso para instalar el Sistema de Inventario Documental.
---

## Instalación Rápida (Script Automático)

El proyecto incluye un script de configuración automática:

```bash
git clone <url-del-repositorio> inventario-documental
cd inventario-documental
composer run setup
```

El script `composer run setup` ejecuta automáticamente:
1. `composer install`
2. Copia `.env.example` → `.env`
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install`
6. `npm run build`

---

## Instalación Manual Paso a Paso

### 1. Clonar el Repositorio

```bash
git clone <url-del-repositorio> inventario-documental
cd inventario-documental
```

### 2. Instalar Dependencias PHP

```bash
composer install
```

:::tip
En producción usa `composer install --optimize-autoloader --no-dev` para mejor rendimiento.
:::

### 3. Configurar el Archivo de Entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los datos de la base de datos:

```env
APP_NAME="Inventario Documental"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://inventario-documental.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventario_documental
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Crear la Base de Datos

En MySQL/MariaDB:

```sql
CREATE DATABASE inventario_documental CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Ejecutar Migraciones y Seeders

```bash
# Solo migraciones
php artisan migrate

# Migraciones + datos iniciales (recomendado para primera instalación)
php artisan migrate --seed
```

Los seeders crean:
- Roles: `super_admin`, `supervisor`, `user`
- Permisos de Filament Shield para todos los recursos
- Usuario administrador inicial

### 6. Instalar Dependencias JavaScript

```bash
npm install
npm run build
```

Para desarrollo con hot-reload:

```bash
npm run dev
```

### 7. Configurar Permisos de Almacenamiento

```bash
php artisan storage:link
```

En Linux/macOS también:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 8. Generar Permisos de Filament Shield

```bash
php artisan shield:generate --all
```

### 9. Crear el Super Administrador

```bash
php artisan shield:super-admin --user=1
```

O crear un nuevo usuario super_admin:

```bash
php artisan make:filament-user
```

### 10. Verificar la Instalación

Iniciar el servidor de desarrollo:

```bash
composer run dev
```

Esto inicia en paralelo:
- Servidor PHP (`php artisan serve`) en `http://localhost:8000`
- Queue listener (`php artisan queue:listen`)
- Log viewer (`php artisan pail`)
- Vite hot-reload (`npm run dev`)

Abrir `http://localhost:8000/admin` en el navegador e iniciar sesión.

---

## Migración desde Base de Datos Legada

Si el sistema reemplaza una base de datos existente (`bdpaakgr`), configurar en `.env`:

```env
OLD_DB_HOST=127.0.0.1
OLD_DB_PORT=3306
OLD_DB_DATABASE=bdpaakgr
OLD_DB_USERNAME=root
OLD_DB_PASSWORD=
```

:::caution
Remover estas variables de entorno después de completar la migración inicial.
:::
