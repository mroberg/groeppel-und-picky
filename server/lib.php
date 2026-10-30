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

function sendMail(string $to, string $subject, string $text): void {
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
    $m->Body = $text;
    $m->send();
}

function json(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
