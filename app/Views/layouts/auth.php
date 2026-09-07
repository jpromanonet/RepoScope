<?php
/** @var string $templateFile */
/** @var string $appName */
/** @var string $title */
$flashes = take_flashes();
$cssPath = dirname(__DIR__, 2) . '/assets/css/app.css';
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Login') . ' · ' . $appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= (int) @filemtime($cssPath) ?>">
    <link rel="icon" href="<?= e(url('/assets/icons/radar.svg')) ?>" type="image/svg+xml">
</head>
<body class="auth-body">
<main class="auth-shell">
    <section class="auth-brand">
        <span class="brand-mark"><?= icon('radar', 28) ?></span>
        <h1>RepoScope</h1>
        <p>Radar de repositorios: sync, clasificación e higiene (README, licencia, descripción, archivos y mantenimiento).</p>
    </section>
    <section class="auth-card">
        <?php if ($flashes): ?>
            <div class="flash-stack">
                <?php foreach ($flashes as $flash): ?>
                    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php require $templateFile; ?>
    </section>
</main>
</body>
</html>
