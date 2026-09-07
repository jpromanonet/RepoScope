<div class="page-head">
    <div>
        <p class="eyebrow">Taxonomía</p>
        <h1>Categorías</h1>
        <p class="muted">Clasificá el inventario. Forks y archivados se sugieren solos al sincronizar.</p>
    </div>
</div>

<div class="split">
    <section class="panel">
        <h2>Nueva categoría</h2>
        <form method="post" action="<?= e(url('/categorias')) ?>" class="stack-form">
            <?= csrf_field() ?>
            <label>
                <span>Nombre</span>
                <input type="text" name="name" required maxlength="120" placeholder="Ej. Cliente interno">
            </label>
            <label>
                <span>Color</span>
                <input type="color" name="color" value="#3DDCFF">
            </label>
            <button class="btn btn-primary" type="submit">Crear</button>
        </form>
    </section>
    <section class="panel">
        <h2>Listado</h2>
        <ul class="cat-list">
            <?php foreach ($categories as $cat): ?>
                <li>
                    <form method="post" action="<?= e(url('/categorias/' . (int) $cat['id'])) ?>" class="cat-row">
                        <?= csrf_field() ?>
                        <input type="color" name="color" value="<?= e((string) $cat['color']) ?>" aria-label="Color">
                        <input type="text" name="name" value="<?= e((string) $cat['name']) ?>" required>
                        <span class="muted"><?= format_number((int) $cat['total']) ?></span>
                        <button class="btn btn-sm" type="submit">Guardar</button>
                    </form>
                    <a class="btn btn-sm" href="<?= e(url('/repositorios?category_id=' . (int) $cat['id'])) ?>">Ver</a>
                    <?php if (empty($cat['is_system'])): ?>
                        <form method="post" action="<?= e(url('/categorias/' . (int) $cat['id'] . '/eliminar')) ?>" onsubmit="return confirm('¿Eliminar categoría?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-danger" type="submit">Borrar</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
