---
title: Requisitos
description: Requisitos de hardware y software para instalar el Sistema de Inventario Documental.
---

## Requisitos de Software

### Servidor / Desarrollo Local

| Componente | Versión mínima | Recomendada |
|------------|----------------|-------------|
| **PHP** | 8.2 | 8.3 |
| **Composer** | 2.x | 2.7+ |
| **Node.js** | 18.x | 20.x LTS |
| **npm** | 9.x | 10.x |
| **MySQL** | 8.0 | 8.0+ |
| **MariaDB** | 10.6 | 10.11+ |

### Extensiones PHP Requeridas

```
php-mbstring
php-xml
php-bcmath
php-json
php-curl
php-zip
php-gd
php-pdo
php-mysql
php-fileinfo
```

### Servidor Web

- **Apache 2.4+** con `mod_rewrite` habilitado, o
- **Nginx 1.18+** con configuración para Laravel

---

## Instalación en Windows (Laragon)

Laragon es el entorno recomendado para desarrollo en Windows.

### Descargar Laragon

1. Ir a [laragon.org](https://laragon.org) y descargar **Laragon Full**
2. Instalar con las opciones por defecto
3. Laragon incluye automáticamente: Apache/Nginx, PHP 8.x, MySQL, Composer y Node.js

### Verificar Versiones en Laragon

Abrir la terminal de Laragon y ejecutar:

```bash
php --version
# PHP 8.2.x (cli)

composer --version
# Composer version 2.x

node --version
# v20.x.x

npm --version
# 10.x.x

mysql --version
# mysql  Ver 8.0.x
```

### Habilitar Extensiones PHP

En Laragon → click derecho → PHP → Extensiones → activar:
- `php_fileinfo`
- `php_gd`
- `php_zip`

---

## Instalación en macOS (Laravel Valet)

```bash
# Instalar Homebrew
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Instalar PHP 8.2
brew install php@8.2
brew link php@8.2

# Instalar Composer
brew install composer

# Instalar Node.js (via NVM recomendado)
brew install nvm
nvm install 20
nvm use 20

# Instalar MySQL
brew install mysql@8.0
brew services start mysql@8.0

# Instalar Valet
composer global require laravel/valet
valet install
```

---

## Instalación en Linux (Ubuntu/Debian)

```bash
# Actualizar repositorios
sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.2 y extensiones
sudo apt install -y php8.2 php8.2-cli php8.2-fpm \
  php8.2-mysql php8.2-mbstring php8.2-xml \
  php8.2-bcmath php8.2-curl php8.2-zip \
  php8.2-gd php8.2-fileinfo

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Node.js 20 (via NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Instalar MySQL 8.0
sudo apt install -y mysql-server
sudo systemctl start mysql
sudo mysql_secure_installation
```

---

## Requisitos de Hardware (Producción)

| Componente | Mínimo | Recomendado |
|------------|--------|-------------|
| **CPU** | 2 cores | 4 cores |
| **RAM** | 2 GB | 4 GB |
| **Disco** | 10 GB | 20 GB SSD |
| **Red** | 10 Mbps | 100 Mbps |

---

## Navegadores Soportados (Usuarios Finales)

| Navegador | Versión mínima |
|-----------|----------------|
| Chrome | 90+ |
| Firefox | 88+ |
| Microsoft Edge | 90+ |
| Safari | 14+ |

:::note
Internet Explorer no está soportado.
:::
