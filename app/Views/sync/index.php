<?php
$progress = $progress ?? ['running' => false, 'pending' => 0, 'scanned' => 0, 'total' => 0, 'pct' => 0];
$running = !empty($progress['running']);
?>
<div class="page-head">
    <div>
        <p class="eyebrow">GitHub</p>
        <h1>Sincronizar</h1>
        <p class="muted">Trae repos, mira la raíz y recalcula README, licencia, descripción, archivos y mantenimiento.</p>
    </div>
</div>

<section class="panel">
    <?php if (!$hasToken): ?>
        <p>
            Falta el token de GitHub. En
            <a href="<?= e(url('/configuracion')) ?>">Configuración</a>
            está el paso a paso: se crea un Personal Access Token (classic) con
            <code>repo</code> y <code>read:org</code>. Ese token es de <strong>tu usuario</strong>
            y trae los repos que posee, los de orgs autorizadas y donde seas colaborador.
        </p>
    <?php elseif ($running): ?>
        <p>
            Sincronizando<?= $owner !== '' ? ' <strong>@' . e($owner) . '</strong>' : '' ?>:
            <?= format_number((int) $progress['scanned']) ?> /
            <?= format_number((int) $progress['total']) ?> repos con archivos revisados.
        </p>
        <div class="sync-progress" aria-label="<?= (int) $progress['pct'] ?>%">
            <span style="width:<?= (int) $progress['pct'] ?>%"></span>
        </div>
        <p class="muted">La app sigue usable. Esta pantalla avanza sola de a tandas cortas.</p>
        <form method="post" action="<?= e(url('/sincronizar/paso')) ?>" id="sync-continue">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit"><?= icon('sync', 16) ?> Continuar ahora</button>
        </form>
        <script>
          setTimeout(function () {
            var form = document.getElementById('sync-continue');
            if (form) form.submit();
          }, 600);
        </script>
    <?php else: ?>
        <p>
            Token listo<?= $owner !== '' ? ' · cuenta <strong>@' . e($owner) . '</strong>' : '' ?>.
            Hay <?= format_number($total) ?> repositorios locales.
        </p>
        <div class="btn-row">
            <form method="post" action="<?= e(url('/sincronizar')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-primary" type="submit"><?= icon('sync', 16) ?> Sincronizar ahora</button>
            </form>
            <form method="post" action="<?= e(url('/sincronizar/reanalizar')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost" type="submit">Reanalizar sin llamar a GitHub</button>
            </form>
        </div>
        <p class="muted">
            Primero baja la lista (rápido). Después mira la raíz de cada repo en tandas, para no colgar Apache ni el resto de la app.
        </p>
    <?php endif; ?>
</section>

<section class="panel mt">
    <h2>Historial</h2>
    <?php if (!$runs): ?>
        <p class="muted">Todavía no hubo una corrida.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Inicio</th>
                        <th>Estado</th>
                        <th>Origen</th>
                        <th>Repos</th>
                        <th>Hallazgos</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($runs as $run): ?>
                    <tr>
                        <td><?= e(format_datetime($run['started_at'])) ?></td>
                        <td><span class="pill pill-<?= e((string) $run['status']) ?>"><?= e((string) $run['status']) ?></span></td>
                        <td><?= e((string) ($run['source'] ?: '—')) ?></td>
                        <td><?= format_number((int) ($run['repos_scanned'] ?? 0)) ?> / <?= format_number((int) $run['repos_upserted']) ?></td>
                        <td><?= format_number((int) $run['findings_open']) ?></td>
                        <td class="muted"><?= e((string) ($run['error_message'] ?: '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
