<?php
/** @var array $metrics */
$m = $metrics;
$total = (int) $m['total'];
$j = static function (array $payload): string {
    return e(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
};
$findingLabels = [];
$findingValues = [];
foreach (finding_types() as $type => $label) {
    $findingLabels[] = $label;
    $findingValues[] = (int) ($m['finding_repos'][$type] ?? 0);
}
$langLabels = [];
$langValues = [];
foreach (array_slice($m['by_language'] ?? [], 0, 12) as $lang) {
    $langLabels[] = (string) $lang['name'];
    $langValues[] = (int) $lang['total'];
}
$catLabels = [];
$catValues = [];
$catColors = [];
foreach ($m['by_category'] ?? [] as $cat) {
    if ((int) $cat['total'] < 1) {
        continue;
    }
    $catLabels[] = (string) $cat['name'];
    $catValues[] = (int) $cat['total'];
    $catColors[] = (string) $cat['color'];
}
if ((int) $m['uncategorized'] > 0) {
    $catLabels[] = 'Sin categoría';
    $catValues[] = (int) $m['uncategorized'];
    $catColors[] = '#8B9BB4';
}
$ownerLabels = [];
$ownerValues = [];
$ownerHealth = [];
foreach (array_slice($m['by_owner'] ?? [], 0, 10) as $owner) {
    $ownerLabels[] = (string) $owner['owner'];
    $ownerValues[] = (int) $owner['total'];
    $ownerHealth[] = (int) $owner['avg_health'];
}
$actLabels = ['30 d', '31–90', '91–180', '181–365', '> 1 año', 'Sin push'];
$actValues = [
    (int) $m['activity']['d30'],
    (int) $m['activity']['d90'],
    (int) $m['activity']['d180'],
    (int) $m['activity']['d365'],
    (int) $m['activity']['older'],
    (int) $m['activity']['never'],
];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Análisis</p>
        <h1>Métricas</h1>
        <p class="muted">Composición del inventario, actividad en el tiempo y salud por recorte.</p>
    </div>
    <?php if ($total > 0): ?>
        <a class="btn btn-ghost" href="<?= e(url('/atencion')) ?>">Ver atención</a>
    <?php endif; ?>
</div>

<?php if ($total === 0): ?>
    <section class="empty-hero">
        <div class="radar-ring" aria-hidden="true"></div>
        <h2>Todavía no hay números</h2>
        <p class="muted">Cuando sincronices, acá van barras, líneas y tortas del inventario.</p>
        <a class="btn btn-primary" href="<?= e(url('/sincronizar')) ?>">Sincronizar</a>
    </section>
<?php else: ?>
    <section class="stat-grid">
        <article class="stat-card">
            <span class="stat-label">Repositorios</span>
            <strong><?= format_number($total) ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">Salud promedio</span>
            <strong class="<?= e(health_class((int) $m['avg_health'])) ?>"><?= (int) $m['avg_health'] ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">En regla</span>
            <strong><?= (int) $m['pct_healthy'] ?>%</strong>
            <span class="stat-sub"><?= format_number((int) $m['healthy']) ?> repos</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Con hallazgos</span>
            <strong><?= format_number((int) $m['repos_with_findings']) ?></strong>
            <span class="stat-sub"><?= format_number((int) $m['open_findings']) ?> abiertos</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Clasificados</span>
            <strong><?= (int) $m['pct_categorized'] ?>%</strong>
            <span class="stat-sub"><?= format_number((int) $m['uncategorized']) ?> sin categoría</span>
        </article>
        <article class="stat-card">
            <span class="stat-label">Stars / issues</span>
            <strong><?= format_number((int) $m['stars']) ?></strong>
            <span class="stat-sub"><?= format_number((int) $m['issues']) ?> issues abiertos</span>
        </article>
    </section>

    <h2 class="section-title">Composición</h2>
    <div class="chart-grid">
        <section class="panel">
            <h2>Salud</h2>
            <div class="chart-box">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'doughnut',
                    'labels' => ['En regla (100)', 'Casi (80–99)', 'Regular (50–79)', 'Crítico (0–49)'],
                    'values' => [
                        (int) $m['bands']['ok'],
                        (int) $m['bands']['high'],
                        (int) $m['bands']['warn'],
                        (int) $m['bands']['bad'],
                    ],
                    'colors' => ['green', 'cyan', 'amber', 'rose'],
                ]) ?>"></canvas>
            </div>
        </section>
        <section class="panel">
            <h2>Visibilidad</h2>
            <div class="chart-box">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'doughnut',
                    'labels' => ['Públicos', 'Privados'],
                    'values' => [(int) $m['public'], (int) $m['private']],
                    'colors' => ['cyan', 'violet'],
                ]) ?>"></canvas>
            </div>
        </section>
        <section class="panel">
            <h2>Tipo de repo</h2>
            <div class="chart-box">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'doughnut',
                    'labels' => ['Originales', 'Forks', 'Archivados'],
                    'values' => [
                        (int) ($m['mix']['originals'] ?? $m['originals']),
                        (int) ($m['mix']['forks'] ?? $m['forks']),
                        (int) ($m['mix']['archived'] ?? $m['archived']),
                    ],
                    'colors' => ['green', 'blue', 'muted'],
                ]) ?>"></canvas>
            </div>
        </section>
        <section class="panel">
            <h2>Hallazgos (repos afectados)</h2>
            <div class="chart-box">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'doughnut',
                    'labels' => $findingLabels,
                    'values' => $findingValues,
                    'colors' => ['cyan', 'violet', 'blue', 'amber', 'rose'],
                ]) ?>"></canvas>
            </div>
        </section>
        <section class="panel">
            <h2>Categorías</h2>
            <div class="chart-box">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'pie',
                    'labels' => $catLabels ?: ['—'],
                    'values' => $catValues ?: [1],
                    'hex' => $catColors ?: ['#8B9BB4'],
                ]) ?>"></canvas>
            </div>
        </section>
    </div>

    <h2 class="section-title">Barras</h2>
    <div class="chart-grid chart-grid-wide">
        <section class="panel">
            <h2>Lenguajes</h2>
            <div class="chart-box chart-box-bar">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'bar',
                    'labels' => $langLabels ?: ['—'],
                    'values' => $langValues ?: [0],
                    'color' => 'cyan',
                ]) ?>"></canvas>
            </div>
        </section>
        <section class="panel">
            <h2>Último push</h2>
            <div class="chart-box chart-box-bar">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'bar',
                    'labels' => $actLabels,
                    'values' => $actValues,
                    'color' => 'amber',
                ]) ?>"></canvas>
            </div>
            <p class="muted">Mantenimiento se marca a los <?= (int) $m['stale_days'] ?> días.</p>
        </section>
        <?php if ($ownerLabels): ?>
        <section class="panel">
            <h2>Repos por owner</h2>
            <div class="chart-box chart-box-bar">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'bar',
                    'labels' => $ownerLabels,
                    'values' => $ownerValues,
                    'color' => 'blue',
                ]) ?>"></canvas>
            </div>
        </section>
        <section class="panel">
            <h2>Salud promedio por owner</h2>
            <div class="chart-box chart-box-bar">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'bar',
                    'labels' => $ownerLabels,
                    'values' => $ownerHealth,
                    'color' => 'green',
                    'max' => 100,
                ]) ?>"></canvas>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <h2 class="section-title">Líneas de tiempo</h2>
    <div class="chart-grid chart-grid-wide">
        <section class="panel">
            <h2>Repos creados (18 meses)</h2>
            <div class="chart-box chart-box-line">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'line',
                    'labels' => $m['created_months']['labels'] ?? [],
                    'values' => $m['created_months']['values'] ?? [],
                    'color' => 'cyan',
                    'label' => 'Creados',
                ]) ?>"></canvas>
            </div>
        </section>
        <section class="panel">
            <h2>Pushes (12 meses)</h2>
            <div class="chart-box chart-box-line">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'line',
                    'labels' => $m['pushed_months']['labels'] ?? [],
                    'values' => $m['pushed_months']['values'] ?? [],
                    'color' => 'green',
                    'label' => 'Con push en el mes',
                ]) ?>"></canvas>
            </div>
        </section>
        <?php if (!empty($m['sync_series']['labels'])): ?>
        <section class="panel chart-span">
            <h2>Historial de sync</h2>
            <div class="chart-box chart-box-line">
                <canvas data-rs-chart="<?= $j([
                    'type' => 'line',
                    'labels' => $m['sync_series']['labels'],
                    'datasets' => [
                        ['label' => 'Repos', 'data' => $m['sync_series']['upserted'], 'color' => 'cyan'],
                        ['label' => 'Hallazgos abiertos', 'data' => $m['sync_series']['findings'], 'color' => 'rose'],
                    ],
                ]) ?>"></canvas>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <section class="panel mt">
        <h2>Los que más atención piden</h2>
        <?php if (!$m['worst']): ?>
            <p class="ok-line"><?= icon('check', 16) ?> Ningún repo ignorado está por debajo de 100.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Repositorio</th>
                            <th>Salud</th>
                            <th>Lenguaje</th>
                            <th>Último push</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($m['worst'] as $repo): ?>
                        <tr>
                            <td>
                                <a class="repo-name" href="<?= e(url('/repositorios/' . (int) $repo['id'])) ?>"><?= e((string) $repo['full_name']) ?></a>
                            </td>
                            <td><span class="health <?= e(health_class((int) $repo['health_score'])) ?>"><?= (int) $repo['health_score'] ?></span></td>
                            <td><?= e((string) ($repo['language'] ?: '—')) ?></td>
                            <td><?= e(format_relative($repo['pushed_at'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
