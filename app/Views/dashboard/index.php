<div class="page-head">
    <div>
        <p class="eyebrow">Radar</p>
        <h1>Panel</h1>
        <p class="muted">Salud del inventario GitHub: README, licencia, descripción, archivos y mantenimiento.</p>
    </div>
    <?php if (empty($hasToken)): ?>
        <a class="btn btn-primary" href="<?= e(url('/configuracion')) ?>">Conectar GitHub</a>
    <?php else: ?>
        <form method="post" action="<?= e(url('/sincronizar')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit"><?= icon('sync', 16) ?> Escanear ahora</button>
        </form>
    <?php endif; ?>
</div>

<?php if ($stats['total'] === 0): ?>
    <section class="empty-hero">
        <div class="radar-ring" aria-hidden="true"></div>
        <h2>Todavía no hay repos en el radar</h2>
        <p class="muted">
            En Configuración está cómo crear el token de <strong>tu usuario</strong> de GitHub
            (alcance <code>repo</code>). Después sincronizá: clasifica cada repo y marca
            README, licencia, descripción, archivos requeridos o mantenimiento.
        </p>
        <div class="btn-row">
            <a class="btn btn-primary" href="<?= e(url('/configuracion')) ?>">Configurar token</a>
            <a class="btn btn-ghost" href="<?= e(url('/sincronizar')) ?>">Ir a sincronizar</a>
        </div>
    </section>
<?php else: ?>
    <section class="stat-grid">
        <article class="stat-card">
            <span class="stat-label">Repositorios</span>
            <strong><?= format_number($stats['total']) ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">Salud promedio</span>
            <strong class="<?= e(health_class((int) $stats['avg_health'])) ?>"><?= (int) $stats['avg_health'] ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">Necesitan atención</span>
            <strong><?= format_number($stats['attention']) ?></strong>
            <a class="stat-link" href="<?= e(url('/repositorios?attention=1')) ?>">Ver lista</a>
        </article>
        <article class="stat-card">
            <span class="stat-label">Sin categoría</span>
            <strong><?= format_number($stats['uncategorized']) ?></strong>
            <a class="stat-link" href="<?= e(url('/repositorios?category_id=none')) ?>">Clasificar</a>
        </article>
        <article class="stat-card">
            <span class="stat-label">Privados</span>
            <strong><?= format_number($stats['private']) ?></strong>
        </article>
        <article class="stat-card">
            <span class="stat-label">Archivados</span>
            <strong><?= format_number($stats['archived']) ?></strong>
        </article>
    </section>

    <section class="panel">
        <h2>Hallazgos abiertos</h2>
        <ul class="finding-bars">
            <?php foreach (finding_types() as $type => $label):
                $n = (int) ($stats['findings'][$type] ?? 0);
                $max = max(1, (int) max($stats['findings']));
                $pct = (int) round($n / $max * 100);
                ?>
                <li>
                    <a href="<?= e(url('/atencion?type=' . $type)) ?>">
                        <span><?= e($label) ?></span>
                        <span class="bar"><i style="width:<?= $pct ?>%"></i></span>
                        <strong><?= format_number($n) ?></strong>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="panel mt">
        <h2>Por categoría</h2>
        <?php if (!$stats['by_category']): ?>
            <p class="muted">Sin categorías todavía.</p>
        <?php else: ?>
            <ul class="chip-list">
                <?php foreach ($stats['by_category'] as $cat): ?>
                    <li>
                        <a class="chip" href="<?= e(url('/repositorios?category_id=' . (int) $cat['id'])) ?>">
                            <i class="dot" style="background:<?= e((string) $cat['color']) ?>"></i>
                            <?= e((string) $cat['name']) ?>
                            <span><?= format_number((int) $cat['total']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="panel mt">
        <h2>Lenguajes</h2>
        <?php if (!$stats['by_language']): ?>
            <p class="muted">Sin datos de lenguaje.</p>
        <?php else: ?>
            <ul class="plain-list">
                <?php foreach ($stats['by_language'] as $lang): ?>
                    <li>
                        <a href="<?= e(url('/repositorios?language=' . rawurlencode((string) $lang['name']))) ?>">
                            <?= e((string) $lang['name']) ?>
                            <span><?= format_number((int) $lang['total']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if (!empty($stats['last_sync'])): ?>
        <p class="muted footer-note">
            Última sync:
            <?php if ($stats['last_sync']['status'] === 'ok'): ?>
                <?= e(format_relative($stats['last_sync']['finished_at'] ?? $stats['last_sync']['started_at'])) ?>
                · <?= format_number((int) $stats['last_sync']['repos_upserted']) ?> repos
            <?php else: ?>
                <?= e((string) $stats['last_sync']['status']) ?>
                <?= !empty($stats['last_sync']['error_message']) ? '· ' . e((string) $stats['last_sync']['error_message']) : '' ?>
            <?php endif; ?>
            · Un repo se considera sin mantenimiento a los <?= (int) $stats['stale_days'] ?> días sin push.
            · <a href="<?= e(url('/metricas')) ?>">Ver métricas</a>
        </p>
    <?php endif; ?>
<?php endif; ?>
