# groeppel-und-picky.de

Website für die Kinderbuchreihe „Gröppel und Picky“, gebaut mit [Astro](https://astro.build) als statische Seite.

## Lokal starten

```bash
npm install
npm run dev      # http://localhost:4321
npm run build    # fertige Seite in dist/
```

## Inhalte ändern

- Links, Termine, Buchdaten: `src/data/site.ts`
- Seiten: `src/pages/`
- Bilder: `public/img/`, Hörprobe und Video: `public/media/`
- Ausmalbilder (PDF): `public/ausmalbilder/`
- Alte WordPress-Adressen werden in `public/.htaccess` weitergeleitet.

## Erinnerung an Band 2 (PHP)

Liegt in `server/` und wird beim Deploy nach `/api/` kopiert.

1. Datenbank in Plesk anlegen und `server/schema.sql` einspielen.
2. `server/config.sample.php` als `config.php` **eine Ebene über dem Webordner** ablegen (neben `httpdocs`) und Datenbank- sowie SMTP-Zugangsdaten eintragen.
3. Wenn Band 2 erscheint, per SSH im Ordner `httpdocs/api`:
   `php versand.php --test=deine@adresse.de`, danach `php versand.php --los`.

## Deploy

Jeder Push auf `main` baut die Seite und lädt sie per rsync über SSH hoch (`.github/workflows/deploy.yml`).
Nötige Secrets im GitHub-Repo: `SSH_HOST`, `SSH_USER`, `SSH_KEY` (privater Schlüssel), `SSH_PATH` (z. B. der `httpdocs`-Ordner der Test-Subdomain).
