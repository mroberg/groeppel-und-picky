import { defineConfig } from 'astro/config';

export default defineConfig({
  site: 'https://groeppel-und-picky.de',
  trailingSlash: 'always',
  build: { format: 'directory' },
});
