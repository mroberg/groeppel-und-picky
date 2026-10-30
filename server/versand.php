<?php
// Einmaliger Versand, wenn Band 2 erscheint. Nur auf der Kommandozeile:
//   php versand.php --test=deine@adresse.de   (schickt nur an diese Adresse)
//   php versand.php --los                    (schickt an alle Bestätigten und löscht danach)
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/lib.php';

$opts = getopt('', ['test:', 'los']);
$base = config()['baseUrl'];
$text = "Hallo!\n\nEs ist so weit: \"Gröppel und Picky und die Piraten\" ist erschienen!\n\n" .
        "Alle Infos und Links zum Buch: $base/abenteuer/die-piraten/\n\n" .
        "Wie versprochen ist das unsere einzige E-Mail an dich. Deine Adresse haben wir jetzt gelöscht.\n\n" .
        "Viele Grüße\nGröppel und Picky";
$subject = 'Die Piraten sind da! Gröppel und Picky Band 2 ist erschienen';

if (isset($opts['test'])) { sendMail($opts['test'], $subject, $text); echo "Testmail verschickt.\n"; exit; }
if (!isset($opts['los'])) { echo "Bitte --test=adresse oder --los angeben.\n"; exit(1); }

$rows = db()->query('SELECT id, email FROM erinnerung WHERE bestaetigt IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
$del = db()->prepare('DELETE FROM erinnerung WHERE id = ?');
foreach ($rows as $r) {
    try { sendMail($r['email'], $subject, $text); $del->execute([$r['id']]); echo "ok  {$r['email']}\n"; }
    catch (Throwable $e) { echo "FEHLER {$r['email']}: {$e->getMessage()}\n"; }
    usleep(300000);
}
// Unbestätigte Reste ebenfalls löschen
db()->exec('DELETE FROM erinnerung WHERE bestaetigt IS NULL');
echo count($rows) . " Adressen bearbeitet.\n";
