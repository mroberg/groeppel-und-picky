<?php
// POST: Eintragen (schickt Bestätigungsmail). GET ?t=…&a=ok|ab: bestätigen oder abmelden.
declare(strict_types=1);
require __DIR__ . '/lib.php';

$base = config()['baseUrl'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = (string)($_GET['t'] ?? '');
    $action = $_GET['a'] ?? 'ok';
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        header("Location: $base/erinnerung/?status=fehler");
        exit;
    }
    if ($action === 'ab') {
        db()->prepare('DELETE FROM erinnerung WHERE token = ?')->execute([$token]);
        header("Location: $base/erinnerung/?status=abgemeldet");
        exit;
    }
    // Unbestätigte Einträge verfallen nach 7 Tagen
    $st = db()->prepare('UPDATE erinnerung SET bestaetigt = NOW() WHERE token = ? AND erstellt > NOW() - INTERVAL 7 DAY');
    $st->execute([$token]);
    header("Location: $base/erinnerung/?status=" . ($st->rowCount() || isConfirmed($token) ? 'ok' : 'fehler'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json(['ok' => false, 'error' => 'Nicht erlaubt.'], 405);

// Honeypot: Bots füllen das versteckte Feld aus
if (!empty($_POST['website'])) json(['ok' => true]);

$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($_POST['ok'])) {
    json(['ok' => false, 'error' => 'Bitte gib eine gültige E-Mail-Adresse ein und setz den Haken.'], 422);
}

// Einfaches Limit: höchstens 5 Anmeldungen pro IP und Stunde
$ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d'));
$st = db()->prepare('SELECT COUNT(*) FROM erinnerung WHERE ip_hash = ? AND erstellt > NOW() - INTERVAL 1 HOUR');
$st->execute([$ipHash]);
if ((int)$st->fetchColumn() >= 5) json(['ok' => false, 'error' => 'Zu viele Versuche.'], 429);

$token = bin2hex(random_bytes(32));
db()->prepare('INSERT INTO erinnerung (email, token, ip_hash) VALUES (?, ?, ?)
               ON DUPLICATE KEY UPDATE token = IF(bestaetigt IS NULL, VALUES(token), token), erstellt = IF(bestaetigt IS NULL, NOW(), erstellt)')
    ->execute([$email, $token, $ipHash]);

$st = db()->prepare('SELECT token, bestaetigt FROM erinnerung WHERE email = ?');
$st->execute([$email]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if ($row['bestaetigt'] !== null) json(['ok' => true]); // schon eingetragen, keine zweite Mail

try {
    sendMail($email, 'Bitte bestätigen: Erinnerung an Gröppel und Picky Band 2',
        "Hallo!\n\nDu möchtest erfahren, wann \"Gröppel und Picky und die Piraten\" erscheint.\n" .
        "Bitte bestätige deine Adresse mit diesem Link:\n\n$base/api/erinnerung.php?t={$row['token']}&a=ok\n\n" .
        "Wir schicken dir genau eine E-Mail, wenn das Buch da ist, und löschen deine Adresse danach.\n" .
        "Wenn du dich nicht eingetragen hast, ignoriere diese E-Mail einfach. Ohne Bestätigung wird nichts gespeichert, " .
        "der Eintrag verfällt nach 7 Tagen.\n\nViele Grüße\nGröppel und Picky");
} catch (Throwable $e) {
    error_log('Erinnerung: ' . $e->getMessage());
    json(['ok' => false, 'error' => 'Die Bestätigungsmail konnte nicht verschickt werden.'], 500);
}
json(['ok' => true]);

function isConfirmed(string $token): bool {
    $st = db()->prepare('SELECT 1 FROM erinnerung WHERE token = ? AND bestaetigt IS NOT NULL');
    $st->execute([$token]);
    return (bool)$st->fetchColumn();
}
