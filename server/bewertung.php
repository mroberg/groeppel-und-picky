<?php
// GET:  freigegebene Bewertungen als JSON (für die Website).
// POST: neue Bewertung speichern (noch nicht sichtbar) und Matthias per Mail benachrichtigen.
//       Freigeben, verbergen und löschen geht in der Konsole (/api/konsole.php).
declare(strict_types=1);
require __DIR__ . '/lib.php';

const FORMATE = ['buch' => 'Buch', 'ebook' => 'E-Book', 'hoerbuch' => 'Hörbuch'];
const BUECHER = ['band1' => 'Gröppel und Picky bei den Indianern'];

bewertungTabelle();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Cache-Control: public, max-age=300');
    $buch = array_key_exists($_GET['buch'] ?? '', BUECHER) ? $_GET['buch'] : 'band1';
    $st = db()->prepare('SELECT name, titel, text, sterne, format, DATE(erstellt) AS datum FROM bewertung
                         WHERE freigegeben IS NOT NULL AND buch = ? ORDER BY erstellt DESC LIMIT 200');
    $st->execute([$buch]);
    $rows = array_map(fn($r) => ['sterne' => (int)$r['sterne']] + $r, $st->fetchAll(PDO::FETCH_ASSOC));
    json(['ok' => true, 'bewertungen' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json(['ok' => false, 'error' => 'Nicht erlaubt.'], 405);

// Honeypot und Zeitfalle: Bots füllen das versteckte Feld aus oder schicken sofort ab
if (!empty($_POST['website'])) json(['ok' => true]);
if ((int)($_POST['dauer'] ?? 0) < 4000) json(['ok' => true]);

$einzeilig = fn(string $k, int $max) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string)($_POST[$k] ?? ''))), 0, $max);
$name   = $einzeilig('name', 40);
$titel  = $einzeilig('titel', 80);
$text   = mb_substr(trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\r", '', (string)($_POST['text'] ?? '')))), 0, 1500);
$sterne = (int)($_POST['sterne'] ?? 0);
$email  = trim((string)($_POST['email'] ?? ''));
$format = array_key_exists($_POST['format'] ?? '', FORMATE) ? $_POST['format'] : null;
$buch   = array_key_exists($_POST['buch'] ?? '', BUECHER) ? $_POST['buch'] : 'band1';

if ($sterne < 1 || $sterne > 5) json(['ok' => false, 'error' => 'Bitte wähle aus, wie viele Sterne du gibst.'], 422);
if ($name === '') json(['ok' => false, 'error' => 'Bitte gib deinen Vornamen an.'], 422);
if (mb_strlen($text) < 20) json(['ok' => false, 'error' => 'Schreib bitte ein paar Worte mehr (mindestens 20 Zeichen).'], 422);
if (empty($_POST['ok'])) json(['ok' => false, 'error' => 'Bitte setz den Haken, damit wir die Bewertung zeigen dürfen.'], 422);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json(['ok' => false, 'error' => 'Die E-Mail-Adresse stimmt nicht. Du kannst das Feld auch leer lassen.'], 422);
// Links sind fast immer Spam
if (preg_match('~https?://|www\.~i', $text . ' ' . $titel . ' ' . $name)) json(['ok' => false, 'error' => 'Bitte lass Links weg.'], 422);

// Höchstens 3 Bewertungen pro IP und Stunde
$ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d'));
$st = db()->prepare('SELECT COUNT(*) FROM bewertung WHERE ip_hash = ? AND erstellt > NOW() - INTERVAL 1 HOUR');
$st->execute([$ipHash]);
if ((int)$st->fetchColumn() >= 3) json(['ok' => false, 'error' => 'Zu viele Versuche.'], 429);

db()->prepare('INSERT INTO bewertung (buch, format, name, sterne, titel, text, email, ip_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$buch, $format, $name, $sterne, $titel !== '' ? $titel : null, $text, $email !== '' ? $email : null, $ipHash]);

// Benachrichtigung an Matthias. Scheitert die Mail, ist die Bewertung trotzdem gespeichert.
try {
    $c = config();
    $an = $c['benachrichtigung'] ?? $c['smtp']['from'];
    $link = $c['baseUrl'] . '/api/konsole.php#bewertungen';
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    $sternText = str_repeat('★', $sterne) . str_repeat('☆', 5 - $sterne);
    $text_ = "Neue Bewertung für \"" . BUECHER[$buch] . "\" (noch nicht sichtbar):\n\n" .
        "$sternText  $name" . ($format ? ' · ' . FORMATE[$format] : '') . "\n" . ($titel !== '' ? "$titel\n" : '') . "\n$text\n\n" .
        ($email !== '' ? "E-Mail: $email\n\n" : '') .
        "Freigeben oder löschen in der Konsole:\n$link\n";
    $html = mailHtml('Neue Bewertung!',
        '<p><b style="color:#ee8a23;font-size:20px">' . $sternText . '</b><br><b>' . $h($name) . '</b>' . ($format ? ' · ' . FORMATE[$format] : '') . '</p>' .
        ($titel !== '' ? '<p><b>' . $h($titel) . '</b></p>' : '') .
        '<p>' . nl2br($h($text)) . '</p>' .
        ($email !== '' ? '<p style="font-size:13px;color:#4d564f">E-Mail: ' . $h($email) . '</p>' : '') .
        '<p>Die Bewertung ist noch nicht auf der Seite. Du entscheidest in der Konsole:</p>' .
        '<p><a class="btn" href="' . $h($link) . '">Zur Konsole</a></p>');
    sendMail($an, "Neue Bewertung: $sternText von $name", $text_, $html);
} catch (Throwable $e) {
    error_log('Bewertung Mail: ' . $e->getMessage());
}
json(['ok' => true]);
