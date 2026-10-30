<?php
// Kopie als config.php ablegen, AUSSERHALB des Webordners (z. B. eine Ebene über httpdocs).
// Pfad in public/api/erinnerung.php anpassen, falls nötig.
return [
    'db' => [
        'dsn'  => 'mysql:host=localhost;dbname=groeppel;charset=utf8mb4',
        'user' => 'groeppel',
        'pass' => '***',
    ],
    'smtp' => [
        'host'     => 'smtp.example.de',
        'port'     => 587,
        'secure'   => 'tls',          // 'tls' (STARTTLS, Port 587) oder 'ssl' (Port 465)
        'user'     => 'post@groeppel-und-picky.de',
        'pass'     => '***',
        'from'     => 'post@groeppel-und-picky.de',
        'fromName' => 'Gröppel und Picky',
    ],
    'baseUrl' => 'https://www.groeppel-und-picky.de',
];
