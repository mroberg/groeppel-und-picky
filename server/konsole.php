<?php
// Kleine Verwaltungskonsole für die Erinnerung an Band 2.
// Zugang: Benutzer und Passwort-Hash in config.php unter 'admin' (siehe config.sample.php).
declare(strict_types=1);
require __DIR__ . '/lib.php';

header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:");

session_name('gp_konsole');
session_set_cookie_params(['lifetime' => 0, 'path' => '/api/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
session_start();

const IDLE_MINUTEN = 30;
const MAX_FEHLVERSUCHE = 5;
const BATCH = 20;

db()->exec('CREATE TABLE IF NOT EXISTS konsole_login (
  ip_hash CHAR(64) NOT NULL, zeit DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (ip_hash, zeit)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$admin = config()['admin'] ?? null;
if (!$admin || empty($admin['user']) || empty($admin['passHash'])) {
    http_response_code(503);
    exit('Konsole ist nicht eingerichtet: Bitte admin.user und admin.passHash in config.php eintragen.');
}

$ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . 'konsole');
$_SESSION['csrf'] ??= bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
$angemeldet = !empty($_SESSION['admin']) && ($_SESSION['zuletzt'] ?? 0) > time() - IDLE_MINUTEN * 60;
if ($angemeldet) $_SESSION['zuletzt'] = time();

$aktion = $_POST['aktion'] ?? $_GET['aktion'] ?? '';
$meldung = '';
$fehler = '';

function pruefeCsrf(string $csrf): void {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Ungültige Anfrage. Bitte die Seite neu laden.');
    }
}

// ---------- Anmelden / Abmelden ----------
if ($aktion === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    pruefeCsrf($csrf);
    $st = db()->prepare('SELECT COUNT(*) FROM konsole_login WHERE ip_hash = ? AND zeit > NOW() - INTERVAL 15 MINUTE');
    $st->execute([$ipHash]);
    if ((int)$st->fetchColumn() >= MAX_FEHLVERSUCHE) {
        $fehler = 'Zu viele Fehlversuche. Bitte in 15 Minuten erneut versuchen.';
    } elseif (hash_equals($admin['user'], (string)($_POST['user'] ?? '')) && password_verify((string)($_POST['pass'] ?? ''), $admin['passHash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['zuletzt'] = time();
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
        db()->prepare('DELETE FROM konsole_login WHERE ip_hash = ?')->execute([$ipHash]);
        header('Location: konsole.php');
        exit;
    } else {
        db()->prepare('INSERT INTO konsole_login (ip_hash) VALUES (?)')->execute([$ipHash]);
        usleep(800000);
        $fehler = 'Benutzer oder Passwort stimmt nicht.';
    }
}
if ($aktion === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    pruefeCsrf($csrf);
    $_SESSION = [];
    session_destroy();
    header('Location: konsole.php');
    exit;
}

// ---------- Aktionen (nur angemeldet) ----------
if ($angemeldet && $_SERVER['REQUEST_METHOD'] === 'POST' && $aktion !== '') {
    pruefeCsrf($csrf);
    try {
        switch ($aktion) {
            case 'loeschen':
                db()->prepare('DELETE FROM erinnerung WHERE id = ?')->execute([(int)$_POST['id']]);
                $meldung = 'Eintrag gelöscht.';
                break;
            case 'aufraeumen':
                $n = db()->exec('DELETE FROM erinnerung WHERE bestaetigt IS NULL AND erstellt < NOW() - INTERVAL 7 DAY');
                $meldung = "$n abgelaufene, unbestätigte Einträge gelöscht.";
                break;
            case 'test':
                $an = trim((string)($_POST['an'] ?? ''));
                if (!filter_var($an, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Bitte eine gültige Test-Adresse eingeben.');
                [$betreff, $text, $html] = ankuendigungsMail();
                sendMail($an, '[TEST] ' . $betreff, $text, $html);
                $meldung = "Testmail an $an verschickt.";
                break;
            case 'senden':
                // Wird vom Browser in Paketen aufgerufen, bis nichts mehr übrig ist.
                if (($_POST['bestaetigung'] ?? '') !== 'SENDEN') json(['ok' => false, 'error' => 'Bitte SENDEN eintippen.'], 422);
                [$betreff, $text, $html] = ankuendigungsMail();
                $rows = db()->query('SELECT id, email FROM erinnerung WHERE bestaetigt IS NOT NULL ORDER BY id LIMIT ' . BATCH)->fetchAll(PDO::FETCH_ASSOC);
                $del = db()->prepare('DELETE FROM erinnerung WHERE id = ?');
                $ok = 0; $fehlgeschlagen = [];
                foreach ($rows as $r) {
                    try { sendMail($r['email'], $betreff, $text, $html); $del->execute([$r['id']]); $ok++; }
                    catch (Throwable $e) { $fehlgeschlagen[] = $r['email']; error_log('Konsole Versand: ' . $e->getMessage()); }
                    usleep(300000);
                }
                $rest = (int)db()->query('SELECT COUNT(*) FROM erinnerung WHERE bestaetigt IS NOT NULL')->fetchColumn();
                // Bei Fehlern nicht endlos wiederholen: fehlgeschlagene Adressen bleiben stehen, Schleife endet.
                json(['ok' => true, 'gesendet' => $ok, 'fehler' => $fehlgeschlagen, 'rest' => $fehlgeschlagen ? 0 : $rest, 'offen' => $rest]);
        }
    } catch (Throwable $e) {
        $fehler = $e->getMessage();
    }
}

// ---------- Daten für die Ansicht ----------
$eintraege = [];
$stat = ['bestaetigt' => 0, 'offen' => 0];
if ($angemeldet) {
    $eintraege = db()->query('SELECT id, email, erstellt, bestaetigt FROM erinnerung ORDER BY bestaetigt IS NULL, erstellt DESC')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($eintraege as $e) $stat[$e['bestaetigt'] ? 'bestaetigt' : 'offen']++;
}
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
$datum = fn($s) => $s ? date('d.m.Y H:i', strtotime($s)) : '–';
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Konsole · Gröppel und Picky</title>
<style>
  :root { --paper:#fffcf3; --paper-2:#f6f0de; --ink:#1d2420; --soft:#4d564f; --croc:#1f7a4c; --picky:#2f78c8; --red:#c0392b; --sun:#f7d33f; color-scheme: light }
  * { box-sizing: border-box }
  body { margin: 0; background: var(--paper-2); color: var(--ink); font: 16px/1.5 "Trebuchet MS", system-ui, sans-serif }
  main { max-width: 980px; margin: 0 auto; padding: 24px 16px 48px; display: grid; gap: 20px }
  header { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap }
  h1 { margin: 0; font-size: 1.6rem } h2 { margin: 0 0 12px; font-size: 1.2rem }
  .box { background: var(--paper); border: 3px solid var(--ink); border-radius: 14px; padding: 18px }
  .stats { display: flex; gap: 14px; flex-wrap: wrap }
  .stat { background: var(--paper); border: 3px solid var(--ink); border-radius: 14px; padding: 10px 18px; min-width: 150px }
  .stat b { display: block; font-size: 2rem; line-height: 1.1; font-variant-numeric: tabular-nums }
  button, .btn { font: inherit; font-weight: 700; border: 3px solid var(--ink); border-radius: 999px; padding: 8px 16px; background: var(--croc); color: #fff; cursor: pointer }
  button.alt { background: #fff; color: var(--ink) } button.red { background: var(--red) } button.small { padding: 3px 10px; font-size: .85rem }
  button:disabled { opacity: .5; cursor: default }
  input[type=text], input[type=email], input[type=password] { font: inherit; padding: 8px 12px; border: 3px solid var(--ink); border-radius: 10px; background: #fff; min-width: 0 }
  form.row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center }
  .msg { padding: 10px 14px; border-radius: 10px; font-weight: 700 } .ok { background: #d9f2e3 } .err { background: #fadbd8 }
  .table { overflow-x: auto } table { width: 100%; border-collapse: collapse; font-size: .95rem }
  th, td { text-align: left; padding: 8px; border-bottom: 1px solid #d9d0b4; white-space: nowrap } td.mail { white-space: normal; word-break: break-all }
  .pill { display: inline-block; padding: 1px 10px; border-radius: 999px; font-size: .8rem; font-weight: 700 }
  .pill.y { background: #d9f2e3; color: #135c37 } .pill.n { background: #fff1c2; color: #6b5200 }
  .login { max-width: 360px; margin: 10vh auto 0 } .login form { display: grid; gap: 10px }
  .danger { border-color: var(--red) } small { color: var(--soft) }
  progress { width: 100%; height: 14px }
</style>
</head>
<body>
<main>
<?php if (!$angemeldet): ?>
  <div class="box login">
    <h1>Konsole</h1>
    <?php if ($fehler): ?><p class="msg err"><?= $h($fehler) ?></p><?php endif ?>
    <form method="post" action="konsole.php">
      <input type="hidden" name="aktion" value="login"><input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
      <label for="user">Benutzer</label><input type="text" id="user" name="user" autocomplete="username" required autofocus>
      <label for="pass">Passwort</label><input type="password" id="pass" name="pass" autocomplete="current-password" required>
      <button type="submit">Anmelden</button>
    </form>
  </div>
<?php else: ?>
  <header>
    <h1>Erinnerung an Band 2</h1>
    <form method="post" action="konsole.php"><input type="hidden" name="aktion" value="logout"><input type="hidden" name="csrf" value="<?= $h($csrf) ?>"><button class="alt" type="submit">Abmelden</button></form>
  </header>
  <?php if ($meldung): ?><p class="msg ok"><?= $h($meldung) ?></p><?php endif ?>
  <?php if ($fehler): ?><p class="msg err"><?= $h($fehler) ?></p><?php endif ?>

  <div class="stats">
    <div class="stat"><b><?= $stat['bestaetigt'] ?></b>bestätigt</div>
    <div class="stat"><b><?= $stat['offen'] ?></b>noch nicht bestätigt</div>
  </div>

  <section class="box">
    <h2>Testmail</h2>
    <p><small>Schickt die Ankündigungsmail mit „[TEST]“ im Betreff nur an diese Adresse. Niemand wird gelöscht.</small></p>
    <form class="row" method="post" action="konsole.php">
      <input type="hidden" name="aktion" value="test"><input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
      <input type="email" name="an" placeholder="deine@adresse.de" required aria-label="Test-Adresse">
      <button type="submit">Testmail senden</button>
    </form>
  </section>

  <section class="box danger">
    <h2>Ankündigung an alle senden</h2>
    <p><small>Geht an alle <?= $stat['bestaetigt'] ?> bestätigten Adressen. Jede Adresse wird nach dem Versand gelöscht. Das lässt sich nicht rückgängig machen. Zum Bestätigen <b>SENDEN</b> eintippen.</small></p>
    <form class="row" id="senden-form">
      <input type="text" id="bestaetigung" placeholder="SENDEN" autocomplete="off" aria-label="Bestätigung">
      <button class="red" type="submit" id="senden-btn" disabled>An alle senden</button>
    </form>
    <div id="fortschritt" hidden><progress id="bar" max="<?= max(1, $stat['bestaetigt']) ?>" value="0"></progress><p id="status"></p></div>
  </section>

  <section class="box">
    <header><h2>Empfänger (<?= count($eintraege) ?>)</h2>
      <form method="post" action="konsole.php"><input type="hidden" name="aktion" value="aufraeumen"><input type="hidden" name="csrf" value="<?= $h($csrf) ?>"><button class="alt small" type="submit">Abgelaufene Unbestätigte löschen</button></form>
    </header>
    <div class="table"><table>
      <thead><tr><th>E-Mail</th><th>Status</th><th>Eingetragen</th><th>Bestätigt</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($eintraege as $e): ?>
        <tr>
          <td class="mail"><?= $h($e['email']) ?></td>
          <td><?= $e['bestaetigt'] ? '<span class="pill y">bestätigt</span>' : '<span class="pill n">offen</span>' ?></td>
          <td><?= $datum($e['erstellt']) ?></td><td><?= $datum($e['bestaetigt']) ?></td>
          <td><form method="post" action="konsole.php" data-confirm="<?= $h($e['email']) ?>"><input type="hidden" name="aktion" value="loeschen"><input type="hidden" name="csrf" value="<?= $h($csrf) ?>"><input type="hidden" name="id" value="<?= (int)$e['id'] ?>"><button class="alt small" type="submit">Löschen</button></form></td>
        </tr>
      <?php endforeach ?>
      <?php if (!$eintraege): ?><tr><td colspan="5"><small>Noch niemand eingetragen.</small></td></tr><?php endif ?>
      </tbody>
    </table></div>
  </section>
  <script>
    const csrf = <?= json_encode($csrf) ?>;
    const inp = document.getElementById('bestaetigung'), btn = document.getElementById('senden-btn');
    inp.addEventListener('input', () => btn.disabled = inp.value !== 'SENDEN' || <?= $stat['bestaetigt'] ?> === 0);
    document.querySelectorAll('form[data-confirm]').forEach(f => f.addEventListener('submit', e => {
      if (!confirm(f.dataset.confirm + ' wirklich löschen?')) e.preventDefault();
    }));
    document.getElementById('senden-form').addEventListener('submit', async e => {
      e.preventDefault(); btn.disabled = true; inp.disabled = true;
      const box = document.getElementById('fortschritt'), bar = document.getElementById('bar'), st = document.getElementById('status');
      box.hidden = false; let gesendet = 0, fehler = [];
      try {
        while (true) {
          const fd = new FormData(); fd.append('aktion', 'senden'); fd.append('csrf', csrf); fd.append('bestaetigung', 'SENDEN');
          const r = await fetch('konsole.php', { method: 'POST', body: fd, credentials: 'same-origin' });
          const d = await r.json(); if (!d.ok) throw new Error(d.error || 'Fehler');
          gesendet += d.gesendet; fehler = fehler.concat(d.fehler); bar.value = gesendet;
          st.textContent = `${gesendet} verschickt …`;
          if (d.rest === 0) { st.textContent = `Fertig: ${gesendet} verschickt.` + (fehler.length ? ` Fehlgeschlagen (bleiben in der Liste): ${fehler.join(', ')}` : ''); break; }
        }
      } catch (x) { st.textContent = 'Abgebrochen: ' + x.message + ` (${gesendet} bis dahin verschickt)`; }
    });
  </script>
<?php endif ?>
</main>
</body>
</html>
