<?php
$qs = static function (array $extra = []) use ($filters, $sort, $dir): string {
    $params = array_filter(
        array_merge($filters, ['sort' => $sort, 'dir' => $dir], $extra),
        static fn ($v) => $v !== '' && $v !== null
    );
    return http_build_query($params);
};
$sortLink = static function (string $col) use ($sort, $dir, $qs): string {
    $nextDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return url('/repositorios?' . $qs(['sort' => $col, 'dir' => $nextDir, 'page' => 1]));
};
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Inventario</p>
        <h1>Repositorios</h1>
        <p class="muted"><?= format_number((int) $result['total']) ?> en el radar</p>
    </div>
</div>

<form class="filters" method="get" action="<?= e(url('/repositorios')) ?>">
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Nombre o descripción">
    <select name="language">
        <option value="">Lenguaje</option>
        <?php foreach ($languages as $lang): ?>
            <option value="<?= e((string) $lang['name']) ?>" <?= $filters['language'] === $lang['name'] ? 'selected' : '' ?>>
                <?= e((string) $lang['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="category_id">
        <option value="">Categoría</option>
        <option value="none" <?= $filters['category_id'] === 'none' ? 'selected' : '' ?>>Sin categoría</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= $filters['category_id'] === (string) $cat['id'] ? 'selected' : '' ?>>
                <?= e((string) $cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <select name="finding">
        <option value="">Hallazgo</option>
        <?php foreach (finding_types() as $type => $label): ?>
            <option value="<?= e($type) ?>" <?= $filters['finding'] === $type ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="attention">
        <option value="">Todos</option>
        <option value="1" <?= $filters['attention'] === '1' ? 'selected' : '' ?>>Necesitan atención</option>
    </select>
    <select name="visibility">
        <option value="">Visibilidad</option>
        <option value="public" <?= $filters['visibility'] === 'public' ? 'selected' : '' ?>>Público</option>
        <option value="private" <?= $filters['visibility'] === 'private' ? 'selected' : '' ?>>Privado</option>
    </select>
    <button class="btn btn-ghost" type="submit">Filtrar</button>
</form>

<div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th><a href="<?= e($sortLink('full_name')) ?>">Repositorio</a></th>
                <th>Categoría</th>
                <th><a href="<?= e($sortLink('language')) ?>">Lenguaje</a></th>
                <th><a href="<?= e($sortLink('health_score')) ?>">Salud</a></th>
                <th><a href="<?= e($sortLink('pushed_at')) ?>">Último push</a></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$result['rows']): ?>
            <tr><td colspan="6" class="muted">No hay repositorios con ese filtro. Sincronizá o cambiá la búsqueda.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['rows'] as $repo): ?>
            <tr>
                <td>
                    <a class="repo-name" href="<?= e(url('/repositorios/' . (int) $repo['id'])) ?>"><?= e((string) $repo['full_name']) ?></a>
                    <div class="repo-meta">
                        <?php if (!empty($repo['is_private'])): ?><span class="pill">privado</span><?php endif; ?>
                        <?php if (!empty($repo['is_fork'])): ?><span class="pill">fork</span><?php endif; ?>
                        <?php if (!empty($repo['is_archived'])): ?><span class="pill">archivado</span><?php endif; ?>
                        <?php if (!empty($repo['description'])): ?>
                            <span class="desc"><?= e(shorten((string) $repo['description'], 90)) ?></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <?php if (!empty($repo['category_name'])): ?>
                        <span class="chip chip-static">
                            <i class="dot" style="background:<?= e((string) $repo['category_color']) ?>"></i>
                            <?= e((string) $repo['category_name']) ?>
                        </span>
                    <?php else: ?>
                        <span class="muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= e((string) ($repo['language'] ?: '—')) ?></td>
                <td>
                    <span class="health <?= e(health_class((int) $repo['health_score'])) ?>"><?= (int) $repo['health_score'] ?></span>
                    <?php if ((int) $repo['open_findings'] > 0): ?>
                        <span class="muted"><?= (int) $repo['open_findings'] ?></span>
                    <?php endif; ?>
                </td>
                <td><?= e(format_relative($repo['pushed_at'] ?? null)) ?></td>
                <td><a class="btn btn-sm" href="<?= e(url('/repositorios/' . (int) $repo['id'])) ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= pager('/repositorios', array_merge($filters, ['sort' => $sort, 'dir' => $dir]), (int) $result['page'], (int) $result['pages']) ?>
