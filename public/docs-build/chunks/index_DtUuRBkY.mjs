import { l as createVNode, h as Fragment, _ as __astro_tag_component__ } from './astro/server_DO_nUfqZ.mjs';
import { c as $$CardGrid, d as $$Card } from './Code_CudQHJw7.mjs';
import 'clsx';

const frontmatter = {
  "title": "Inventario Documental",
  "description": "Documentación completa del Sistema de Inventario Documental para gestión de Actos Administrativos e Inventario Documental en entidades gubernamentales colombianas.",
  "template": "splash",
  "hero": {
    "tagline": "Sistema integral para la gestión de Actos Administrativos (CCD/SUR) e Inventario Documental (FUID) con numeración automática, control de cumplimiento PDF y reportes mensuales.",
    "image": {
      "file": "../../assets/houston.webp"
    },
    "actions": [{
      "text": "Comenzar",
      "link": "/docs/inicio/introduccion/",
      "icon": "right-arrow"
    }, {
      "text": "Guía de Instalación",
      "link": "/docs/instalacion/requisitos/",
      "icon": "rocket",
      "variant": "minimal"
    }]
  }
};
function getHeadings() {
  return [{
    "depth": 2,
    "slug": "características-principales",
    "text": "Características Principales"
  }, {
    "depth": 2,
    "slug": "módulos-del-sistema",
    "text": "Módulos del Sistema"
  }, {
    "depth": 2,
    "slug": "stack-tecnológico",
    "text": "Stack Tecnológico"
  }, {
    "depth": 2,
    "slug": "soporte",
    "text": "Soporte"
  }];
}
function _createMdxContent(props) {
  const {Fragment: Fragment$1} = props.components || ({});
  if (!Fragment$1) _missingMdxReference("Fragment");
  return createVNode(Fragment, {
    children: [createVNode(Fragment$1, {
      "set:html": "<div class=\"sl-heading-wrapper level-h2\"><h2 id=\"características-principales\">Características Principales</h2><a class=\"sl-anchor-link\" href=\"#características-principales\"><span aria-hidden=\"true\" class=\"sl-anchor-icon\"><svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\"><path fill=\"currentcolor\" d=\"m12.11 15.39-3.88 3.88a2.52 2.52 0 0 1-3.5 0 2.47 2.47 0 0 1 0-3.5l3.88-3.88a1 1 0 0 0-1.42-1.42l-3.88 3.89a4.48 4.48 0 0 0 6.33 6.33l3.89-3.88a1 1 0 1 0-1.42-1.42Zm8.58-12.08a4.49 4.49 0 0 0-6.33 0l-3.89 3.88a1 1 0 0 0 1.42 1.42l3.88-3.88a2.52 2.52 0 0 1 3.5 0 2.47 2.47 0 0 1 0 3.5l-3.88 3.88a1 1 0 1 0 1.42 1.42l3.88-3.89a4.49 4.49 0 0 0 0-6.33ZM8.83 15.17a1 1 0 0 0 1.1.22 1 1 0 0 0 .32-.22l4.92-4.92a1 1 0 0 0-1.42-1.42l-4.92 4.92a1 1 0 0 0 0 1.42Z\"></path></svg></span><span class=\"sr-only\">Section titled “Características Principales”</span></a></div>\n"
    }), createVNode($$CardGrid, {
      stagger: true,
      children: [createVNode($$Card, {
        title: "Actos Administrativos (SUR/CCD)",
        icon: "document",
        "set:html": "<p>Radicación automática con formato YYYY.ENTIDAD.SERIE.SUBSERIE.###.SUR, control de adjuntos PDF con plazo de 30 días y sección confidencial.</p>"
      }), createVNode($$Card, {
        title: "Inventario Documental (FUID)",
        icon: "list-format",
        "set:html": "<p>Registro de documentos con clasificación TRD, ubicación física (caja, carpeta, tomo), fechas extremas y código de referencia automático.</p>"
      }), createVNode($$Card, {
        title: "Reportes Mensuales",
        icon: "approve-check",
        "set:html": "<p>Informes de cumplimiento PDF exportables en Excel y PDF con KPIs por unidad, entidad y serie documental.</p>"
      }), createVNode($$Card, {
        title: "Control de Acceso por Roles",
        icon: "warning",
        "set:html": "<p>Roles super_admin, supervisor y usuario con restricciones por unidad organizacional y archivos confidenciales.</p>"
      }), createVNode($$Card, {
        title: "Dashboard Interactivo",
        icon: "star",
        "set:html": "<p>11 widgets con estadísticas en tiempo real, gráficas de cumplimiento, evolución histórica y últimos registros.</p>"
      }), createVNode($$Card, {
        title: "Auditoría Completa",
        icon: "seti:notebook",
        "set:html": "<p>Registro detallado de todas las acciones con Spatie ActivityLog: quién creó, editó o accedió a cada documento.</p>"
      })]
    }), "\n", createVNode(Fragment$1, {
      "set:html": "<div class=\"sl-heading-wrapper level-h2\"><h2 id=\"módulos-del-sistema\">Módulos del Sistema</h2><a class=\"sl-anchor-link\" href=\"#módulos-del-sistema\"><span aria-hidden=\"true\" class=\"sl-anchor-icon\"><svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\"><path fill=\"currentcolor\" d=\"m12.11 15.39-3.88 3.88a2.52 2.52 0 0 1-3.5 0 2.47 2.47 0 0 1 0-3.5l3.88-3.88a1 1 0 0 0-1.42-1.42l-3.88 3.89a4.48 4.48 0 0 0 6.33 6.33l3.89-3.88a1 1 0 1 0-1.42-1.42Zm8.58-12.08a4.49 4.49 0 0 0-6.33 0l-3.89 3.88a1 1 0 0 0 1.42 1.42l3.88-3.88a2.52 2.52 0 0 1 3.5 0 2.47 2.47 0 0 1 0 3.5l-3.88 3.88a1 1 0 1 0 1.42 1.42l3.88-3.89a4.49 4.49 0 0 0 0-6.33ZM8.83 15.17a1 1 0 0 0 1.1.22 1 1 0 0 0 .32-.22l4.92-4.92a1 1 0 0 0-1.42-1.42l-4.92 4.92a1 1 0 0 0 0 1.42Z\"></path></svg></span><span class=\"sr-only\">Section titled “Módulos del Sistema”</span></a></div>\n"
    }), createVNode($$CardGrid, {
      children: [createVNode($$Card, {
        title: "Usuario",
        icon: "open-book",
        "set:html": "<p>Crea y gestiona actos administrativos e inventario de su unidad organizacional.\r\n<a href=\"/docs/usuario/primeros-pasos/\">Ver guía</a></p>"
      }), createVNode($$Card, {
        title: "Supervisor",
        icon: "approve-check",
        "set:html": "<p>Supervisa todas las unidades, accede a reportes mensuales e importa datos masivamente.\r\n<a href=\"/docs/roles/supervisor/\">Ver guía</a></p>"
      }), createVNode($$Card, {
        title: "Super Admin",
        icon: "setting",
        "set:html": "<p>Administración total: usuarios, entidades, unidades, series documentales y configuración.\r\n<a href=\"/docs/roles/super-admin/\">Ver guía</a></p>"
      })]
    }), "\n", createVNode(Fragment$1, {
      "set:html": "<div class=\"sl-heading-wrapper level-h2\"><h2 id=\"stack-tecnológico\">Stack Tecnológico</h2><a class=\"sl-anchor-link\" href=\"#stack-tecnológico\"><span aria-hidden=\"true\" class=\"sl-anchor-icon\"><svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\"><path fill=\"currentcolor\" d=\"m12.11 15.39-3.88 3.88a2.52 2.52 0 0 1-3.5 0 2.47 2.47 0 0 1 0-3.5l3.88-3.88a1 1 0 0 0-1.42-1.42l-3.88 3.89a4.48 4.48 0 0 0 6.33 6.33l3.89-3.88a1 1 0 1 0-1.42-1.42Zm8.58-12.08a4.49 4.49 0 0 0-6.33 0l-3.89 3.88a1 1 0 0 0 1.42 1.42l3.88-3.88a2.52 2.52 0 0 1 3.5 0 2.47 2.47 0 0 1 0 3.5l-3.88 3.88a1 1 0 1 0 1.42 1.42l3.88-3.89a4.49 4.49 0 0 0 0-6.33ZM8.83 15.17a1 1 0 0 0 1.1.22 1 1 0 0 0 .32-.22l4.92-4.92a1 1 0 0 0-1.42-1.42l-4.92 4.92a1 1 0 0 0 0 1.42Z\"></path></svg></span><span class=\"sr-only\">Section titled “Stack Tecnológico”</span></a></div>\n<ul>\n<li><strong>Backend</strong>: Laravel 12 · PHP 8.2+</li>\n<li><strong>Panel Admin</strong>: FilamentPHP 3.2</li>\n<li><strong>Base de Datos</strong>: MySQL / MariaDB</li>\n<li><strong>Permisos</strong>: Spatie Permission + Filament Shield</li>\n<li><strong>Exportación</strong>: Maatwebsite Excel · DomPDF</li>\n<li><strong>Auditoría</strong>: Spatie ActivityLog</li>\n</ul>\n<div class=\"sl-heading-wrapper level-h2\"><h2 id=\"soporte\">Soporte</h2><a class=\"sl-anchor-link\" href=\"#soporte\"><span aria-hidden=\"true\" class=\"sl-anchor-icon\"><svg width=\"16\" height=\"16\" viewBox=\"0 0 24 24\"><path fill=\"currentcolor\" d=\"m12.11 15.39-3.88 3.88a2.52 2.52 0 0 1-3.5 0 2.47 2.47 0 0 1 0-3.5l3.88-3.88a1 1 0 0 0-1.42-1.42l-3.88 3.89a4.48 4.48 0 0 0 6.33 6.33l3.89-3.88a1 1 0 1 0-1.42-1.42Zm8.58-12.08a4.49 4.49 0 0 0-6.33 0l-3.89 3.88a1 1 0 0 0 1.42 1.42l3.88-3.88a2.52 2.52 0 0 1 3.5 0 2.47 2.47 0 0 1 0 3.5l-3.88 3.88a1 1 0 1 0 1.42 1.42l3.88-3.89a4.49 4.49 0 0 0 0-6.33ZM8.83 15.17a1 1 0 0 0 1.1.22 1 1 0 0 0 .32-.22l4.92-4.92a1 1 0 0 0-1.42-1.42l-4.92 4.92a1 1 0 0 0 0 1.42Z\"></path></svg></span><span class=\"sr-only\">Section titled “Soporte”</span></a></div>\n<p>Si necesitas ayuda, consulta la sección de <a href=\"/docs/referencia/troubleshooting/\">Solución de Problemas</a> o revisa los <a href=\"/docs/referencia/comandos/\">Comandos de referencia</a>.</p>"
    })]
  });
}
function MDXContent(props = {}) {
  const {wrapper: MDXLayout} = props.components || ({});
  return MDXLayout ? createVNode(MDXLayout, {
    ...props,
    children: createVNode(_createMdxContent, {
      ...props
    })
  }) : _createMdxContent(props);
}
function _missingMdxReference(id, component) {
  throw new Error("Expected " + ("component" ) + " `" + id + "` to be defined: you likely forgot to import, pass, or provide it.");
}

const url = "src/content/docs/index.mdx";
const file = "C:/laragon/www/inventario-documental/public/docs/src/content/docs/index.mdx";
const Content = (props = {}) => MDXContent({
  ...props,
  components: { Fragment: Fragment, ...props.components, },
});
Content[Symbol.for('mdx-component')] = true;
Content[Symbol.for('astro.needsHeadRendering')] = !Boolean(frontmatter.layout);
Content.moduleId = "C:/laragon/www/inventario-documental/public/docs/src/content/docs/index.mdx";
__astro_tag_component__(Content, 'astro:jsx');

export { Content, Content as default, file, frontmatter, getHeadings, url };
