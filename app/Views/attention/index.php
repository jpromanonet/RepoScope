<div class="page-head">
    <div>
        <p class="eyebrow">Inbox</p>
        <h1>Atención</h1>
        <p class="muted"><?= format_number((int) $result['total']) ?> hallazgos abiertos</p>
    </div>
</div>

<div class="type-tabs">
    <a class="<?= $filters['type'] === '' ? 'is-active' : '' ?>" href="<?= e(url('/atencion')) ?>">Todos</a>
    <?php foreach (finding_types() as $type => $label): ?>
        <a class="<?= $filters['type'] === $type ? 'is-active' : '' ?>" href="<?= e(url('/atencion?type=' . $type)) ?>">
            <?= e($label) ?>
            <span><?= format_number((int) ($counts[$type] ?? 0)) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<form class="filters" method="get" action="<?= e(url('/atencion')) ?>">
    <?php if ($filters['type'] !== ''): ?>
        <input type="hidden" name="type" value="<?= e($filters['type']) ?>">
    <?php endif; ?>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Filtrar por repo">
    <button class="btn btn-ghost" type="submit">Buscar</button>
</form>

<div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>Repositorio</th>
                <th>Tipo</th>
                <th>Detalle</th>
                <th>Salud</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$result['rows']): ?>
            <tr><td colspan="5" class="muted">Nada pendiente. El radar está limpio.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['rows'] as $row): ?>
            <tr>
                <td>
                    <a class="repo-name" href="<?= e(url('/repositorios/' . (int) $row['repository_id'])) ?>"><?= e((string) $row['full_name']) ?></a>
                    <div class="repo-meta">
                        <?php if (!empty($row['category_name'])): ?>
                            <span class="chip chip-static">
                                <i class="dot" style="background:<?= e((string) $row['category_color']) ?>"></i>
                                <?= e((string) $row['category_name']) ?>
                            </span>
                        <?php endif; ?>
                        <?= e((string) ($row['language'] ?: '')) ?>
                    </div>
                </td>
                <td><span class="badge <?= e(finding_badge_class((string) $row['type'])) ?>"><?= e(finding_label((string) $row['type'])) ?></span></td>
                <td><?= e((string) $row['message']) ?></td>
                <td><span class="health <?= e(health_class((int) $row['health_score'])) ?>"><?= (int) $row['health_score'] ?></span></td>
                <td>
                    <form method="post" action="<?= e(url('/repositorios/' . (int) $row['repository_id'] . '/omitir')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="<?= e((string) $row['type']) ?>">
                        <input type="hidden" name="back" value="atencion">
                        <button class="btn btn-sm" type="submit">Omitir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= pager('/atencion', $filters, (int) $result['page'], (int) $result['pages']) ?>
