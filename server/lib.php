<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require __DIR__ . '/vendor/autoload.php';

function config(): array {
    static $c = null;
    return $c ??= require dirname(__DIR__, 2) . '/config.php';
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $c = config()['db'];
        $pdo = new PDO($c['dsn'], $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    return $pdo;
}

// Einfaches, kindgerechtes HTML-Layout für alle Mails. $html darf <p> und <a class="btn"> enthalten.
function mailHtml(string $title, string $html, string $bild = 'mail-picky.png'): string {
    $base = config()['baseUrl'];
    $html = str_replace('<a class="btn"', '<a style="display:inline-block;background:#1f7a4c;color:#ffffff;font-weight:bold;text-decoration:none;padding:12px 22px;border:3px solid #1d2420;border-radius:30px"', $html);
    $html = str_replace('<p>', '<p style="margin:0 0 14px;font-size:16px;line-height:1.55">', $html);
    $t = htmlspecialchars($title);
    return <<<HTML
<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>$t</title></head>
<body style="margin:0;background:#f6f0de;font-family:Trebuchet MS,Arial,sans-serif;color:#1d2420">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f0de;padding:24px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fffcf3;border:3px solid #1d2420;border-radius:18px">
<tr><td style="padding:22px 26px 0" align="left"><img src="$base/img/$bild" width="80" alt="" style="display:block;border:0"></td></tr>
<tr><td style="padding:10px 26px 0"><h1 style="margin:0 0 14px;font-size:24px;line-height:1.2;color:#1f7a4c">$t</h1>$html</td></tr>
<tr><td style="padding:10px 26px 22px;font-size:13px;color:#4d564f;border-top:2px dashed #d9d0b4">Gröppel und Picky · <a href="$base" style="color:#2f78c8">groeppel-und-picky.de</a></td></tr>
</table></td></tr></table></body></html>
HTML;
}

function sendMail(string $to, string $subject, string $text, ?string $html = null): void {
    $s = config()['smtp'];
    $m = new PHPMailer(true);
    $m->isSMTP();
    $m->CharSet = 'UTF-8';
    $m->Host = $s['host'];
    $m->Port = $s['port'];
    $m->SMTPAuth = true;
    $m->SMTPSecure = $s['secure'];
    $m->Username = $s['user'];
    $m->Password = $s['pass'];
    $m->setFrom($s['from'], $s['fromName']);
    $m->addAddress($to);
    $m->Subject = $subject;
    if ($html !== null) {
        $m->isHTML(true);
        $m->Body = $html;
        $m->AltBody = $text;
    } else {
        $m->Body = $text;
    }
    $m->send();
}

function json(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
