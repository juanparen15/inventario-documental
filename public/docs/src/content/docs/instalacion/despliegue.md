---
title: Despliegue en Producción
description: Guía de despliegue del Sistema de Inventario Documental en servidor de producción Linux.
---

## Preparación del Servidor

### Requisitos del Servidor

- Ubuntu 22.04 LTS o Debian 12
- Nginx 1.18+ o Apache 2.4+
- PHP 8.2+ con extensiones requeridas
- MySQL 8.0+ o MariaDB 10.11+
- Composer 2.x
- Certificado SSL (recomendado: Let's Encrypt)

### Instalar Dependencias del Servidor

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Nginx
sudo apt install -y nginx

# Instalar PHP 8.2 y extensiones
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql \
  php8.2-mbstring php8.2-xml php8.2-bcmath \
  php8.2-curl php8.2-zip php8.2-gd php8.2-fileinfo

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## Despliegue de la Aplicación

### 1. Clonar el Repositorio

```bash
cd /var/www
sudo git clone <url-del-repositorio> inventario-documental
sudo chown -R www-data:www-data inventario-documental
cd inventario-documental
```

### 2. Instalar Dependencias (sin dev)

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### 3. Configurar el Entorno

```bash
cp .env.example .env
php artisan key:generate
```

Editar `.env` con los valores de producción:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_DATABASE=inventario_documental_prod
DB_USERNAME=inventario_user
DB_PASSWORD=contraseña_segura

MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-proveedor.com
MAIL_PORT=587
# ... resto de configuración SMTP
```

### 4. Base de Datos y Migraciones

```bash
php artisan migrate --force --seed
php artisan shield:generate --all
php artisan shield:super-admin --user=1
```

### 5. Permisos y Storage

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
php artisan storage:link
```

### 6. Optimizaciones de Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache
php artisan filament:cache-components
```

---

## Configuración de Nginx

Crear el archivo de configuración del sitio:

```bash
sudo nano /etc/nginx/sites-available/inventario-documental
```

```nginx
server {
    listen 80;
    server_name tu-dominio.com www.tu-dominio.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name tu-dominio.com www.tu-dominio.com;

    root /var/www/inventario-documental/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/tu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tu-dominio.com/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/inventario-documental /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Configurar el Queue Worker como Servicio

Crear un servicio systemd para el queue worker:

```bash
sudo nano /etc/systemd/system/inventario-queue.service
```

```ini
[Unit]
Description=Inventario Documental Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/inventario-documental
ExecStart=/usr/bin/php artisan queue:work --daemon --tries=3 --timeout=60
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable inventario-queue
sudo systemctl start inventario-queue
sudo systemctl status inventario-queue
```

---

## SSL con Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d tu-dominio.com -d www.tu-dominio.com
```

---

## Mantenimiento

### Actualizar la Aplicación

```bash
cd /var/www/inventario-documental
git pull origin master
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart inventario-queue
```

### Limpiar Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Monitoreo de Logs

```bash
# Ver errores de Laravel en tiempo real
tail -f storage/logs/laravel.log

# Ver logs de la cola
php artisan pail

# Ver errores de Nginx
sudo tail -f /var/log/nginx/error.log
```
