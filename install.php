<?php

declare(strict_types=1);

/**
 * Instalador RepoScope: crea la base y aplica sql/schema.sql.
 * Abrir una sola vez: install.php
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/app/Database.php';

$appConfig = require __DIR__ . '/config/app.php';
$dbConfig = require __DIR__ . '/config/database.php';
date_default_timezone_set($appConfig['timezone']);

$messages = [];
$ok = false;
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $server = Database::connectServer($dbConfig);
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $dbConfig['name']) ?: 'reposcope';
        $server->exec(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
        $messages[] = "Base `{$dbName}` lista.";

        Database::connect($dbConfig);
        $pdo = Database::pdo();

        $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('No se pudo leer sql/schema.sql');
        }

        $parts = explode(';', $schema);
        $applied = 0;
        foreach ($parts as $part) {
            $lines = preg_split("/\r\n|\n|\r/", $part) ?: [];
            $clean = [];
            foreach ($lines as $line) {
                $trim = trim($line);
                if ($trim === '' || str_starts_with($trim, '--')) {
                    continue;
                }
                $clean[] = $line;
            }
            $statement = trim(implode("\n", $clean));
            if ($statement === '') {
                continue;
            }
            $pdo->exec($statement);
            $applied++;
        }
        $messages[] = "Esquema aplicado ({$applied} sentencias).";
        $ok = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$already = false;
try {
    Database::connect($dbConfig);
    $already = (bool) Database::pdo()->query("SHOW TABLES LIKE 'settings'")->fetch();
} catch (Throwable $e) {
    $already = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalar RepoScope</title>
    <style>
        :root { color-scheme: dark; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: "IBM Plex Sans", system-ui, sans-serif;
            background: #0E141B; color: #E6EDF3; padding: 1.5rem;
        }
        .card {
            width: min(32rem, 100%); background: #151D27; border: 1px solid #243040;
            border-radius: 16px; padding: 1.75rem; box-shadow: 0 18px 50px rgb(0 0 0 / 35%);
        }
        h1 { margin: 0 0 .35rem; font-size: 1.4rem; letter-spacing: .04em; }
        .muted { color: #8B9BB4; margin: 0 0 1.2rem; }
        .ok { color: #3DDC97; } .err { color: #F07178; }
        ul { padding-left: 1.1rem; }
        button, .btn {
            display: inline-block; border: 0; border-radius: 10px; padding: .65rem 1rem;
            background: #3DDCFF; color: #0E141B; font-weight: 700; cursor: pointer;
            text-decoration: none; font-family: inherit;
        }
        code { background: #0E141B; padding: .1rem .35rem; border-radius: 4px; }
    </style>
</head>
<body>
<div class="card">
    <h1>RepoScope</h1>
    <p class="muted">Crea la base y aplica <code>sql/schema.sql</code> (único archivo SQL).</p>
    <?php if ($ok): ?>
        <p class="ok">Listo.</p>
        <ul><?php foreach ($messages as $m): ?><li><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
        <p>Login inicial: <code>admin@local</code> / <code>reposcope</code>. Cambialo en Perfil.</p>
        <p><a class="btn" href="index.php">Entrar a RepoScope</a></p>
    <?php elseif ($error): ?>
        <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <p>Revisá <code>.env</code> (host, usuario y clave de MySQL).</p>
    <?php elseif ($already): ?>
        <p class="ok">La base ya está instalada.</p>
        <p>Si no cambiaste la cuenta: <code>admin@local</code> / <code>reposcope</code>.</p>
        <p><a class="btn" href="index.php">Entrar a RepoScope</a></p>
    <?php else: ?>
        <p>Se va a crear la base <code><?= htmlspecialchars((string) $dbConfig['name'], ENT_QUOTES, 'UTF-8') ?></code> y las tablas.</p>
        <form method="post"><button type="submit">Instalar</button></form>
    <?php endif; ?>
</div>
</body>
</html>
