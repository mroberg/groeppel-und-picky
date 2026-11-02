# Einrichtung in Plesk

Beispiel für die Test-Subdomain `groeppel-und-picky.de`. Für den Livegang später dieselben Schritte mit der Hauptdomain.

## 1. Voraussetzungen im Abonnement

- **Node.js 22.12 oder neuer** (Plesk-Erweiterung „Node.js“, dort die Version installieren). Das Skript nimmt automatisch die neueste Version unter `/opt/plesk/node/`. Liegt Node woanders, `NODE_BIN=/pfad/zu/bin` vor den Befehl setzen.
- **Composer** auf der Kommandozeile erreichbar. Sonst `COMPOSER=/pfad/zu/composer` setzen.
- **SSH-Zugang** für den Systembenutzer des Abonnements auf `/bin/bash` (nicht „chrooted“), damit die Bereitstellungsaktion Node und Composer findet.
- **PHP 8.2 oder neuer** für die Subdomain (8.5 passt).
- **Apache mit .htaccess** (Standard „nginx als Proxy für Apache“). Bei „nur nginx“ greifen die Weiterleitungen nicht.
- **SSL-Zertifikat** für die Subdomain (Let's Encrypt).

## 2. Subdomain

- Subdomain `groeppel-und-picky.de` anlegen.
- Dokumentenstamm: `groeppel-und-picky.de/public`

## 3. Git verknüpfen

1. In der Subdomain auf **Git** gehen und **Remote-Repository** wählen.
2. Repository-URL: `git@github.com:mroberg/groeppel-und-picky.git`
3. Plesk zeigt einen **öffentlichen SSH-Schlüssel**. Diesen in GitHub unter *Settings → Deploy keys* eintragen (nur Lesezugriff).
4. Bereitstellungsmodus: **Automatisch**, Bereitstellungspfad: `groeppel-und-picky.de/src`
5. **Zusätzliche Bereitstellungsaktionen** aktivieren:

   ```
   bash scripts/plesk-deploy.sh ../public
   ```

   Den Zielordner relativ angeben: Die Aktion läuft im Bereitstellungspfad (`…/src`), und in einer chrooted Shell gibt es den Pfad `/var/www/vhosts/…` nicht.

6. Für automatische Updates: In Plesk die **Webhook-URL** kopieren und in GitHub unter *Settings → Webhooks* eintragen (Content type `application/json`, nur Push-Events).

## 4. Erinnerung an Band 2

1. In Plesk eine **MariaDB-Datenbank** mit eigenem Benutzer anlegen.
2. `server/schema.sql` über phpMyAdmin einspielen.
3. `server/config.sample.php` als `config.php` nach `groeppel-und-picky.de/config.php` kopieren, also eine Ebene **über** `public`. Dort Datenbank- und SMTP-Zugangsdaten eintragen.
4. Test: Auf der Seite eintragen und prüfen, ob die Bestätigungsmail ankommt.

### Konsole

Unter https://groeppel-und-picky.de/api/konsole.php gibt es eine kleine, nicht indexierte Konsole: Empfänger ansehen und löschen, Testmail schicken und die Ankündigung an alle senden (mit Eingabe von `SENDEN` als Sicherung).

Zugang einrichten: in `config.php` eintragen

```
'admin' => ['user' => 'matthias', 'pass' => 'DeinPasswort'],
```

(Alternativ statt `pass` einen Hash als `passHash`.)

Nach 5 Fehlversuchen ist die Anmeldung 15 Minuten gesperrt, nach 30 Minuten ohne Aktivität wird man abgemeldet.

Alternativ per SSH in `groeppel-und-picky.de/public/api`:

```
php versand.php --test=deine@adresse.de
php versand.php --los
```
