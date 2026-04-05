---
title: Primeros Pasos
description: Guía de inicio rápido para usuarios del Sistema de Inventario Documental.
---

## Acceder al Sistema

1. Abrir el navegador y ir a la URL del sistema (e.g., `http://tu-dominio.com/admin`)
2. Ingresar el **correo electrónico** y la **contraseña** asignados por el administrador
3. Hacer clic en **Iniciar sesión**

:::tip
Si olvidaste tu contraseña, contacta al administrador del sistema para que te la restablezca.
:::

---

## Tour de Bienvenida

Al iniciar sesión por primera vez, el sistema muestra un **tour interactivo** (Driver.js) que guía por las secciones principales del panel. Puedes:

- Seguir el tour completo haciendo clic en **Siguiente**
- Saltarlo haciendo clic en **Omitir**
- Reiniciarlo desde el menú de usuario (esquina superior derecha)

---

## Navegación del Panel

El panel de Filament está organizado en tres áreas:

```
┌──────────────────────────────────────────────────────────┐
│  [Logo] Inventario Documental          [🔔] [🌙] [👤]   │
├────────────────┬─────────────────────────────────────────┤
│                │                                         │
│  📊 Dashboard  │         Contenido Principal             │
│                │                                         │
│  📁 CCD / SUR  │   (Tablas, Formularios, Gráficas)       │
│    Actos Admin │                                         │
│                │                                         │
│  📂 FUID       │                                         │
│    Inventario  │                                         │
│                │                                         │
│  ⚙️  Admin     │                                         │
│    (solo admin)│                                         │
│                │                                         │
└────────────────┴─────────────────────────────────────────┘
```

### Iconos del Encabezado

| Icono | Función |
|-------|---------|
| 🔔 | Notificaciones del sistema |
| 🌙 / ☀️ | Cambiar entre modo oscuro y claro |
| 👤 | Menú de usuario (perfil, cambiar contraseña, cerrar sesión) |

---

## Cambiar Contraseña

1. Hacer clic en el **avatar/nombre de usuario** (esquina superior derecha)
2. Seleccionar **Cambiar Contraseña**
3. Ingresar la contraseña actual y la nueva (mínimo 8 caracteres)
4. Confirmar la nueva contraseña
5. Hacer clic en **Guardar**

:::caution
Se recomienda cambiar la contraseña asignada por el administrador al iniciar sesión por primera vez.
:::

---

## Cambiar Tema (Claro / Oscuro)

Hacer clic en el ícono 🌙/☀️ en el encabezado para alternar entre modo oscuro y claro. La preferencia se guarda automáticamente.

---

## Buscar en el Panel

El panel incluye búsqueda global. Presionar `Ctrl + K` (o `⌘ + K` en Mac) para abrir el buscador. Permite encontrar rápidamente:

- Actos administrativos por número de radicado o asunto
- Registros de inventario por código de referencia o título
- Páginas del panel (Reportes, Dashboard, etc.)

---

## Permisos y Lo Que Puedes Ver

Lo que ves en el panel depende de tu rol asignado:

| Sección | Usuario | Supervisor | Super Admin |
|---------|---------|------------|-------------|
| Dashboard | ✓ (su unidad) | ✓ (todo) | ✓ (todo) |
| Actos CCD | ✓ (su unidad) | ✓ (todo) | ✓ (todo) |
| Inventario FUID | ✓ (su unidad) | ✓ (todo) | ✓ (todo) |
| Reportes mensuales | ✗ | ✓ | ✓ |
| Gestión de usuarios | ✗ | ✗ | ✓ |
| Configuración | ✗ | ✗ | ✓ |

---

## Próximos Pasos

- [Explorar el Dashboard](/docs/usuario/dashboard/)
- [Crear un Acto Administrativo](/docs/usuario/actos-administrativos/)
- [Registrar un Inventario FUID](/docs/usuario/inventario/)
