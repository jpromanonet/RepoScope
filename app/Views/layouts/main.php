<?php
/** @var string $templateFile */
/** @var string $appName */
/** @var string $appVersion */
/** @var string $title */

$theme = 'dark';
try {
    $theme = (string) SettingsService::get('theme', 'dark');
} catch (Throwable $e) {
    $theme = 'dark';
}
if (!in_array($theme, ['dark', 'light'], true)) {
    $theme = 'dark';
}

$repoCount = 0;
$attentionCount = 0;
try {
    $repoCount = RepoService::countAll();
    $attentionCount = (int) Database::pdo()->query(
        'SELECT COUNT(*) FROM findings WHERE is_open = 1'
    )->fetchColumn();
} catch (Throwable $e) {
    $repoCount = 0;
}
$flashes = take_flashes();
$cssPath = dirname(__DIR__, 2) . '/assets/css/app.css';
$jsPath = dirname(__DIR__, 2) . '/assets/js/app.js';
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= e($theme) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') !== '' ? $title . ' · ' . $appName : $appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= (int) @filemtime($cssPath) ?>">
    <link rel="icon" href="<?= e(url('/assets/icons/radar.svg')) ?>" type="image/svg+xml">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?= e(url('/')) ?>">
            <span class="brand-mark"><?= icon('radar', 22) ?></span>
            <span>
                <span class="brand-name">RepoScope</span>
                <span class="brand-tag">Radar de repositorios</span>
            </span>
        </a>
        <nav class="sidebar-nav">
            <a class="<?= e(nav_active('/', true)) ?>" href="<?= e(url('/')) ?>"><?= icon('home') ?> Panel</a>
            <a class="<?= e(nav_active('/repositorios')) ?>" href="<?= e(url('/repositorios')) ?>"><?= icon('repos') ?> Repositorios</a>
            <a class="<?= e(nav_active('/atencion')) ?>" href="<?= e(url('/atencion')) ?>">
                <?= icon('alert') ?> Atención
                <?php if ($attentionCount > 0): ?><span class="nav-count"><?= format_number($attentionCount) ?></span><?php endif; ?>
            </a>
            <a class="<?= e(nav_active('/metricas')) ?>" href="<?= e(url('/metricas')) ?>"><?= icon('chart') ?> Métricas</a>
            <a class="<?= e(nav_active('/categorias')) ?>" href="<?= e(url('/categorias')) ?>"><?= icon('tag') ?> Categorías</a>
            <a class="<?= e(nav_active('/sincronizar')) ?>" href="<?= e(url('/sincronizar')) ?>"><?= icon('sync') ?> Sincronizar</a>
            <a class="<?= e(nav_active('/configuracion')) ?>" href="<?= e(url('/configuracion')) ?>"><?= icon('settings') ?> Configuración</a>
            <a class="<?= e(nav_active('/perfil')) ?>" href="<?= e(url('/perfil')) ?>"><?= icon('user') ?> Perfil</a>
        </nav>
        <div class="sidebar-footer">
            <form method="post" action="<?= e(url('/tema')) ?>" class="theme-form">
                <?= csrf_field() ?>
                <input type="hidden" name="theme" value="<?= e($theme === 'dark' ? 'light' : 'dark') ?>">
                <input type="hidden" name="back" value="<?= e(current_route()) ?>">
                <button type="submit" class="theme-btn">
                    <?= $theme === 'dark' ? icon('sun') : icon('moon') ?>
                    <?= $theme === 'dark' ? 'Modo claro' : 'Modo oscuro' ?>
                </button>
            </form>
            <?php $navUser = Auth::user(); ?>
            <?php if ($navUser): ?>
                <p class="sidebar-meta"><?= e((string) $navUser['email']) ?></p>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="theme-btn"><?= icon('logout', 16) ?> Salir</button>
                </form>
            <?php endif; ?>
            <p class="sidebar-meta">v<?= e($appVersion) ?> · <?= format_number($repoCount) ?> repos</p>
        </div>
    </aside>
    <div class="sidebar-backdrop" aria-hidden="true"></div>

    <div class="main-col">
        <header class="topbar">
            <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Menú"><?= icon('repos') ?></button>
            <form class="top-search" action="<?= e(url('/repositorios')) ?>" method="get">
                <?= icon('search', 16) ?>
                <input type="search" name="q" placeholder="Buscar repositorios…" value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            </form>
            <a class="btn btn-primary" href="<?= e(url('/sincronizar')) ?>"><?= icon('sync', 16) ?> Sincronizar</a>
        </header>

        <main class="content">
            <?php if ($flashes): ?>
                <div class="flash-stack">
                    <?php foreach ($flashes as $flash): ?>
                        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php require $templateFile; ?>
        </main>
    </div>
</div>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=<?= (int) @filemtime($jsPath) ?>"></script>
<?php if (!empty($charts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(url('/assets/js/charts.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 2) . '/assets/js/charts.js') ?>"></script>
<?php endif; ?>
</body>
</html>
