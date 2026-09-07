<?php
$ignored = $ignoredFindings ?? [];
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Detalle</p>
        <h1><?= e((string) $repo['full_name']) ?></h1>
        <p class="muted">
            <?php if (!empty($repo['description'])): ?>
                <?= e((string) $repo['description']) ?>
            <?php else: ?>
                Sin descripción en GitHub.
            <?php endif; ?>
        </p>
    </div>
    <div class="btn-row">
        <a class="btn btn-ghost" href="<?= e((string) $repo['html_url']) ?>" target="_blank" rel="noopener">
            <?= icon('external', 16) ?> GitHub
        </a>
        <span class="health <?= e(health_class((int) $repo['health_score'])) ?> health-lg"><?= (int) $repo['health_score'] ?></span>
    </div>
</div>

<div class="split">
    <section class="panel">
        <h2>Clasificación</h2>
        <form method="post" action="<?= e(url('/repositorios/' . (int) $repo['id'])) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label>
                <span>Categoría</span>
                <select name="category_id">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>" <?= (string) ($repo['category_id'] ?? '') === (string) $cat['id'] ? 'selected' : '' ?>>
                            <?= e((string) $cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Notas</span>
                <textarea name="notes" rows="4" placeholder="Para qué es, si conviene archivarlo, dueño, etc."><?= e((string) ($repo['notes'] ?? '')) ?></textarea>
            </label>
            <label class="check">
                <input type="checkbox" name="is_ignored" value="1" <?= !empty($repo['is_ignored']) ? 'checked' : '' ?>>
                Ignorar hallazgos (útil para forks o sandboxes)
            </label>
            <fieldset class="checks">
                <legend>Omitir tipos</legend>
                <?php foreach (finding_types() as $type => $label): ?>
                    <label class="check">
                        <input type="checkbox" name="ignored_findings[]" value="<?= e($type) ?>" <?= in_array($type, $ignored, true) ? 'checked' : '' ?>>
                        <?= e($label) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>
            <button class="btn btn-primary" type="submit">Guardar</button>
        </form>
    </section>

    <section class="panel">
        <h2>Hallazgos</h2>
        <?php if (!$findings): ?>
            <p class="ok-line"><?= icon('check', 16) ?> Nada pendiente.</p>
        <?php else: ?>
            <ul class="finding-list">
                <?php foreach ($findings as $finding): ?>
                    <li>
                        <span class="badge <?= e(finding_badge_class((string) $finding['type'])) ?>"><?= e(finding_label((string) $finding['type'])) ?></span>
                        <span><?= e((string) $finding['message']) ?></span>
                        <form method="post" action="<?= e(url('/repositorios/' . (int) $repo['id'] . '/omitir')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="<?= e((string) $finding['type']) ?>">
                            <button class="btn btn-sm" type="submit">Omitir</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2 class="mt">Ficha GitHub</h2>
        <dl class="meta-grid">
            <div><dt>Visibilidad</dt><dd><?= e((string) $repo['visibility']) ?></dd></div>
            <div><dt>Lenguaje</dt><dd><?= e((string) ($repo['language'] ?: '—')) ?></dd></div>
            <div><dt>Licencia</dt><dd><?= e((string) ($repo['license_name'] ?: $repo['license_key'] ?: '—')) ?></dd></div>
            <div><dt>Stars</dt><dd><?= format_number((int) $repo['stars']) ?></dd></div>
            <div><dt>Issues</dt><dd><?= format_number((int) $repo['open_issues']) ?></dd></div>
            <div><dt>Último push</dt><dd><?= e(format_datetime($repo['pushed_at'] ?? null)) ?></dd></div>
            <div><dt>Creado</dt><dd><?= e(format_datetime($repo['github_created_at'] ?? null, 'd/m/Y')) ?></dd></div>
            <div><dt>Sync</dt><dd><?= e(format_relative($repo['last_synced_at'] ?? null)) ?></dd></div>
        </dl>
        <?php if ($topics): ?>
            <p class="chip-row">
                <?php foreach ($topics as $topic): ?>
                    <span class="pill"><?= e((string) $topic) ?></span>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
        <?php if ($rootFiles): ?>
            <h2 class="mt">Archivos en la raíz</h2>
            <p class="file-cloud">
                <?php foreach ($rootFiles as $file): ?>
                    <code><?= e((string) $file) ?></code>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </section>
</div>
