# Diseño: Documentación Starlight — Inventario Documental

**Fecha:** 2026-04-04
**Proyecto:** inventario-documental
**Referencia:** gestion-afiliaciones-arl/public/docs

---

## 1. Objetivo

Crear un sitio de documentación completo usando Starlight (Astro) para el sistema **Inventario Documental**, incluyendo manual técnico, manual de usuario y guías por rol. El contenido será generado automáticamente basándose en el código fuente del proyecto.

---

## 2. Ubicación y Stack

- **Ruta:** `inventario-documental/public/docs/`
- **Framework:** Starlight 0.36.2 + Astro 5.6.1 (mismo que ARL)
- **Idioma:** Español
- **Base path:** `/docs`
- **Salida build:** `../docs-build`
- **Dev URL:** `http://localhost:8000/docs`

---

## 3. Configuración Starlight (`astro.config.mjs`)

```js
export default defineConfig({
  site: 'http://localhost:8000',
  base: '/docs',
  outDir: '../docs-build',
  publicDir: './public',
  build: { format: 'directory' },
  integrations: [
    starlight({
      title: 'Inventario Documental',
      logo: { src: '/favicon.svg', alt: 'Logo' },
      sidebar: [
        { label: 'Inicio', autogenerate: { directory: 'inicio' } },
        { label: 'Instalación', autogenerate: { directory: 'instalacion' } },
        { label: 'Manual de Usuario', autogenerate: { directory: 'usuario' } },
        { label: 'Roles', autogenerate: { directory: 'roles' } },
        { label: 'Referencia', autogenerate: { directory: 'referencia' } },
        { label: 'Documentación Técnica', autogenerate: { directory: 'tecnica' } },
      ],
    }),
  ],
});
```

---

## 4. Estructura de Archivos

```
public/docs/
├── src/
│   ├── assets/
│   │   ├── favicon.svg
│   │   ├── logo-light.svg
│   │   └── logo-dark.svg
│   └── content/
│       └── docs/
│           ├── index.mdx                        ← Página de bienvenida
│           ├── inicio/
│           │   ├── introduccion.md
│           │   └── caracteristicas.md
│           ├── instalacion/
│           │   ├── requisitos.md
│           │   ├── instalacion.md
│           │   ├── configuracion.md
│           │   └── despliegue.md
│           ├── usuario/
│           │   ├── primeros-pasos.md
│           │   ├── dashboard.md
│           │   ├── actos-administrativos.md
│           │   ├── inventario.md
│           │   └── reportes.md
│           ├── roles/
│           │   ├── super-admin.md
│           │   ├── supervisor.md
│           │   └── usuario.md
│           ├── referencia/
│           │   ├── comandos.md
│           │   ├── flujos.md
│           │   ├── base-datos.md
│           │   └── troubleshooting.md
│           └── tecnica/
│               ├── arquitectura.md
│               ├── modelos.md
│               ├── permisos.md
│               ├── recursos-filament.md
│               └── exportaciones.md
├── public/
│   └── favicon.svg
├── astro.config.mjs
├── package.json
└── tsconfig.json
```

**Total: 23 páginas + index.mdx**

---

## 5. Contenido por Sección

### 5.1 Inicio (2 páginas)

| Archivo | Contenido |
|---------|-----------|
| `introduccion.md` | Qué es el sistema, propósito, instituciones objetivo (gubernamentales colombianas), problema que resuelve |
| `caracteristicas.md` | Lista completa: Actos Administrativos con radicado automático (YYYY.ENTIDAD.SERIE.SUBSERIE.###.SUR), Inventario con TRD, adjuntos confidenciales, reportes de cumplimiento PDF, exportación Excel/PDF, auditoría |

### 5.2 Instalación (4 páginas)

| Archivo | Contenido |
|---------|-----------|
| `requisitos.md` | PHP 8.2+, Composer, Node.js/npm, MySQL/MariaDB, Laragon (Windows), configuración mínima del servidor |
| `instalacion.md` | Clonar repo, `composer install`, `npm install`, `.env` desde `.env.example`, `php artisan key:generate`, `php artisan migrate --seed` |
| `configuracion.md` | Variables de entorno clave, SMTP para notificaciones, Filament Shield (`php artisan shield:generate --all`), timezone America/Bogota, locale es_CO |
| `despliegue.md` | Producción en Linux, configuración Nginx, optimizaciones Laravel (`config:cache`, `route:cache`, `view:cache`), queue worker |

### 5.3 Manual de Usuario (5 páginas)

| Archivo | Contenido |
|---------|-----------|
| `primeros-pasos.md` | Login en `/admin`, navegación del panel Filament, cambio de contraseña, tour de onboarding con Driver.js |
| `dashboard.md` | Widgets disponibles: estadísticas generales, gráfica de cumplimiento PDF (por mes), actos por clasificación, registros por serie, listados de últimos registros |
| `actos-administrativos.md` | Crear/editar/ver actos: campos (vigencia, asunto, folios, clasificación), numeración automática de radicado, adjuntar PDFs (plazo 30 días), sección confidencial (solo creador + super_admin) |
| `inventario.md` | Registros de inventario: clasificación por TRD (serie/subserie), rango de fechas, ubicación física (caja, carpeta, volumen), medios de almacenamiento, código de referencia automático |
| `reportes.md` | Reportes mensuales (página dedicada), exportación Excel y PDF, KPIs: total de actos, % cumplimiento PDF, vencidos, envío por correo a administradores |

### 5.4 Roles (3 páginas)

| Archivo | Roles | Contenido |
|---------|-------|-----------|
| `super-admin.md` | super_admin | Gestión de usuarios, entidades, unidades organizacionales, series/subseries documentales, clasificaciones, niveles de prioridad, medios de almacenamiento |
| `supervisor.md` | supervisor | Supervisión de actos de todas las unidades, acceso a reportes mensuales, importación masiva de datos |
| `usuario.md` | user | Creación y consulta de actos propios, registro de inventario, adjuntar PDFs |

### 5.5 Referencia (4 páginas)

| Archivo | Contenido |
|---------|-----------|
| `comandos.md` | Artisan commands: `migrate`, `db:seed`, `shield:generate`, `shield:super-admin`, queue worker, importación de errores |
| `flujos.md` | Flujo de vida de un acto administrativo: creación → numeración automática de radicado → revisión → adjunto PDF (30 días) → cumplimiento. Diagrama de flujo en ASCII/Mermaid |
| `base-datos.md` | Tablas principales y relaciones: administrative_acts, inventory_records, organizational_units, documentary_series, documentary_subseries, entities, act_classifications, users, roles, permissions |
| `troubleshooting.md` | Problemas comunes: error de permisos, radicado duplicado, adjunto que no carga, reporte que no genera, correos que no se envían |

### 5.6 Documentación Técnica (5 páginas)

| Archivo | Contenido |
|---------|-----------|
| `arquitectura.md` | Laravel 12 + Filament 3.2, Spatie Permission + Shield, Spatie MediaLibrary, Spatie ActivityLog, DomPDF, Filament Excel, Driver.js. Estructura de carpetas del proyecto |
| `modelos.md` | Modelos Eloquent: AdministrativeAct, InventoryRecord, OrganizationalUnit, DocumentarySeries, DocumentarySubseries, Entity, ActClassification, User. Relaciones y campos principales |
| `permisos.md` | Sistema Spatie Laravel Permission: roles (super_admin, supervisor, user), Filament Shield para el panel, restricciones por unidad organizacional, acceso a archivos confidenciales |
| `recursos-filament.md` | 9 Resources (CRUD), 5 Pages (Dashboard, MonthlyReport, PasswordChange, ImportErrors, ImportPermissions), 11 Widgets (estadísticas, gráficas Apex Charts), Actions personalizadas |
| `exportaciones.md` | Clases Export de Maatwebsite Excel, generación PDF con DomPDF, reportes mensuales de cumplimiento, estructura de las hojas Excel, entrega por email |

---

## 6. Método de Implementación

**Enfoque A — Copia y adaptación del proyecto ARL:**

1. Copiar `package.json`, `tsconfig.json`, `astro.config.mjs` del ARL
2. Crear `src/assets/` con logos e ícono del proyecto
3. Crear `src/content/docs/index.mdx` (página principal)
4. Crear los 23 archivos Markdown con contenido real generado del código fuente
5. Ejecutar `npm install` y verificar `npm run dev`
6. Confirmar build con `npm run build`

**Orden de creación:** Configuración → Assets → index.mdx → inicio/ → instalacion/ → usuario/ → roles/ → referencia/ → tecnica/

---

## 7. Criterios de Éxito

- [ ] `npm run dev` funciona sin errores en `localhost:4321`
- [ ] `npm run build` genera `docs-build/` correctamente
- [ ] Los 6 grupos del sidebar se muestran con todas sus páginas
- [ ] El contenido de cada página refleja el sistema real (no placeholders)
- [ ] La búsqueda integrada (Pagefind) funciona
- [ ] Compatible con dark/light mode de Starlight

---

## 8. Fuera de Alcance

- Integración del build dentro de Laravel (servir desde `/docs` en producción requiere configuración adicional de Nginx/Apache — documentar pero no implementar)
- Internacionalización (solo español)
- Screenshots o capturas de pantalla (texto y diagramas ASCII/Mermaid únicamente)
- Integración CI/CD para auto-build de docs
