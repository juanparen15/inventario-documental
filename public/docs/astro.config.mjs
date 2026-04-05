import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
  site: 'http://localhost:8000',
  base: '/docs',
  outDir: './dist',
  publicDir: './public',
  build: {
    format: 'directory',
  },
  integrations: [
    starlight({
      title: 'Inventario Documental',
      logo: {
        src: './src/assets/favicon.svg',
        alt: 'Logo',
      },
      sidebar: [
        {
          label: 'Inicio',
          autogenerate: { directory: 'inicio' },
        },
        {
          label: 'Instalación',
          autogenerate: { directory: 'instalacion' },
        },
        {
          label: 'Manual de Usuario',
          autogenerate: { directory: 'usuario' },
        },
        {
          label: 'Roles',
          autogenerate: { directory: 'roles' },
        },
        {
          label: 'Referencia',
          autogenerate: { directory: 'referencia' },
        },
        {
          label: 'Documentación Técnica',
          autogenerate: { directory: 'tecnica' },
        },
      ],
    }),
  ],
});
