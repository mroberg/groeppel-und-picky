// Sitemap für Suchmaschinen: listet alle Seiten außer 404 und der Erinnerungs-Bestätigung.
import type { APIRoute } from 'astro';

const ausgeschlossen = ['/404/', '/erinnerung/'];

export const GET: APIRoute = ({ site }) => {
  const seiten = Object.keys(import.meta.glob('./**/*.astro'))
    .map(f => f.replace(/^\./, '').replace(/\.astro$/, '').replace(/\/index$/, '') + '/')
    .map(p => p.replace(/^\/+/, '/'))
    .filter(p => !ausgeschlossen.includes(p))
    .sort();
  const heute = new Date().toISOString().slice(0, 10);
  const urls = seiten.map(p => `  <url><loc>${new URL(p, site)}</loc><lastmod>${heute}</lastmod></url>`).join('\n');
  return new Response(`<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`, {
    headers: { 'Content-Type': 'application/xml; charset=utf-8' },
  });
};
