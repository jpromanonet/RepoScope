<?php
/** @var array $metrics */
$m = $metrics;
$total = (int) $m['total'];
$safe = max(1, $total);
$bar = static function (int $n, int $max): int {
    if ($max <= 0) {
        return 0;
    }
    return (int) round($n / $max * 100);
};
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Análisis</p>
        <h1>Métricas</h1>
        <p class="muted">Distribución de salud, hallazgos, actividad y dónde está el inventario.</p>
    </div>
    <?php if ($total > 0): ?>
        <a class="btn btn-ghost" href="<?= e(url('/atencion')) ?>">Ver atención</a>
    <?php endif; ?>
</div>

<?php if ($total === 0): ?>
    <section class="empty-hero">
        <div class="radar-ring" aria-hidden="true"></div>
        <h2>Todavía no hay números</h2>
        <p class="muted">Cuando sincronices, acá vas a ver salud, lenguajes, actividad y los repos que más atención piden.</p>
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

    <div class="split">
        <section class="panel">
            <h2>Salud</h2>
            <?php
            $bandMeta = [
                'ok' => ['100', 'En regla', 'var(--green)'],
                'high' => ['80–99', 'Casi', 'var(--cyan)'],
                'warn' => ['50–79', 'Regular', 'var(--amber)'],
                'bad' => ['0–49', 'Crítico', 'var(--rose)'],
            ];
            $bandMax = max(1, (int) max($m['bands']));
            ?>
            <ul class="metric-bars">
                <?php foreach ($bandMeta as $key => [$range, $label, $color]):
                    $n = (int) ($m['bands'][$key] ?? 0);
                    ?>
                    <li>
                        <span class="metric-k"><?= e($label) ?> <small><?= e($range) ?></small></span>
                        <span class="bar"><i style="width:<?= $bar($n, $bandMax) ?>%;background:<?= $color ?>"></i></span>
                        <strong><?= format_number($n) ?></strong>
                        <span class="muted"><?= (int) round($n / $safe * 100) ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="mix-pills">
                <span class="pill"><?= format_number((int) $m['public']) ?> públicos</span>
                <span class="pill"><?= format_number((int) $m['private']) ?> privados</span>
                <span class="pill"><?= format_number((int) $m['forks']) ?> forks</span>
                <span class="pill"><?= format_number((int) $m['archived']) ?> archivados</span>
                <?php if ((int) $m['templates'] > 0): ?>
                    <span class="pill"><?= format_number((int) $m['templates']) ?> templates</span>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <h2>Actividad (último push)</h2>
            <?php
            $actMeta = [
                'd30' => 'Últimos 30 días',
                'd90' => '31–90 días',
                'd180' => '91–180 días',
                'd365' => '181–365 días',
                'older' => 'Más de un año',
                'never' => 'Sin push',
            ];
            $actMax = max(1, (int) max($m['activity']));
            ?>
            <ul class="metric-bars">
                <?php foreach ($actMeta as $key => $label):
                    $n = (int) ($m['activity'][$key] ?? 0);
                    ?>
                    <li>
                        <span class="metric-k"><?= e($label) ?></span>
                        <span class="bar"><i style="width:<?= $bar($n, $actMax) ?>%"></i></span>
                        <strong><?= format_number($n) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="muted">Mantenimiento se marca a los <?= (int) $m['stale_days'] ?> días sin push.</p>
        </section>
    </div>

    <section class="panel mt">
        <h2>Hallazgos: repos afectados</h2>
        <p class="muted">Cuántos repositorios tienen cada tipo abierto (un repo puede contar en varios).</p>
        <ul class="metric-bars">
            <?php
            $findMax = max(1, (int) max($m['finding_repos'] ?: [0]));
            foreach (finding_types() as $type => $label):
                $n = (int) ($m['finding_repos'][$type] ?? 0);
                $open = (int) ($m['findings'][$type] ?? 0);
                ?>
                <li>
                    <span class="metric-k">
                        <a href="<?= e(url('/atencion?type=' . $type)) ?>"><?= e($label) ?></a>
                    </span>
                    <span class="bar"><i style="width:<?= $bar($n, $findMax) ?>%"></i></span>
                    <strong><?= format_number($n) ?></strong>
                    <span class="muted"><?= (int) round($n / $safe * 100) ?>% · <?= format_number($open) ?> hallazgos</span>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <div class="split mt">
        <section class="panel">
            <h2>Categorías</h2>
            <?php if (!$m['by_category']): ?>
                <p class="muted">Sin categorías.</p>
            <?php else: ?>
                <ul class="plain-list metric-rows">
                    <?php foreach ($m['by_category'] as $cat): ?>
                        <li>
                            <a href="<?= e(url('/repositorios?category_id=' . (int) $cat['id'])) ?>">
                                <span>
                                    <i class="dot" style="background:<?= e((string) $cat['color']) ?>"></i>
                                    <?= e((string) $cat['name']) ?>
                                </span>
                                <span>
                                    <?= format_number((int) $cat['total']) ?>
                                    <?php if ((int) $cat['total'] > 0): ?>
                                        <small class="<?= e(health_class((int) $cat['avg_health'])) ?>"> · <?= (int) $cat['avg_health'] ?></small>
                                    <?php endif; ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <?php if ((int) $m['uncategorized'] > 0): ?>
                        <li>
                            <a href="<?= e(url('/repositorios?category_id=none')) ?>">
                                <span>Sin categoría</span>
                                <span><?= format_number((int) $m['uncategorized']) ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Lenguajes</h2>
            <?php if (!$m['by_language']): ?>
                <p class="muted">Sin lenguaje declarado.</p>
            <?php else:
                $langMax = max(1, (int) ($m['by_language'][0]['total'] ?? 1));
                ?>
                <ul class="metric-bars compact">
                    <?php foreach ($m['by_language'] as $lang):
                        $n = (int) $lang['total'];
                        ?>
                        <li>
                            <span class="metric-k">
                                <a href="<?= e(url('/repositorios?language=' . rawurlencode((string) $lang['name']))) ?>"><?= e((string) $lang['name']) ?></a>
                            </span>
                            <span class="bar"><i style="width:<?= $bar($n, $langMax) ?>%"></i></span>
                            <strong><?= format_number($n) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <?php if (count($m['by_owner']) > 1): ?>
        <section class="panel mt">
            <h2>Por dueño / org</h2>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Owner</th>
                            <th>Repos</th>
                            <th>Salud</th>
                            <th>Privados</th>
                            <th>Forks</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($m['by_owner'] as $owner): ?>
                        <tr>
                            <td><?= e((string) $owner['owner']) ?></td>
                            <td><?= format_number((int) $owner['total']) ?></td>
                            <td><span class="health <?= e(health_class((int) $owner['avg_health'])) ?>"><?= (int) $owner['avg_health'] ?></span></td>
                            <td><?= format_number((int) $owner['private_n']) ?></td>
                            <td><?= format_number((int) $owner['fork_n']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

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
                                <div class="repo-meta">
                                    <?php if (!empty($repo['is_fork'])): ?><span class="pill">fork</span><?php endif; ?>
                                    <?php if (!empty($repo['is_archived'])): ?><span class="pill">archivado</span><?php endif; ?>
                                    <?= e((string) $repo['visibility']) ?>
                                </div>
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

    <?php if ($m['syncs']): ?>
        <section class="panel mt">
            <h2>Historial de sync</h2>
            <ul class="plain-list">
                <?php foreach ($m['syncs'] as $run): ?>
                    <li>
                        <span>
                            <?= e(format_datetime($run['started_at'])) ?>
                            <span class="pill pill-<?= e((string) $run['status']) ?>"><?= e((string) $run['status']) ?></span>
                        </span>
                        <span><?= format_number((int) $run['repos_upserted']) ?> repos</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
<?php endif; ?>
