<?php
/** @var array $user */
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Cuenta</p>
        <h1>Perfil</h1>
        <p class="muted">Mail y contraseña de esta instalación. No es la cuenta de GitHub.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/perfil')) ?>" class="panel stack-form">
    <?= csrf_field() ?>
    <label>
        <span>Nombre</span>
        <input type="text" name="name" required maxlength="120" value="<?= e((string) $user['name']) ?>">
    </label>
    <label>
        <span>Mail</span>
        <input type="text" name="email" required maxlength="190" value="<?= e((string) $user['email']) ?>" inputmode="email" autocomplete="username">
    </label>
    <p class="muted">Último acceso: <?= e(format_datetime($user['last_login_at'] ?? null)) ?></p>

    <h2 class="mt">Cambiar contraseña</h2>
    <p class="field-hint">Dejá vacío si solo querés actualizar nombre o mail.</p>
    <label>
        <span>Contraseña actual</span>
        <input type="password" name="current_password" autocomplete="current-password">
    </label>
    <label>
        <span>Contraseña nueva (mín. 8)</span>
        <input type="password" name="new_password" autocomplete="new-password" minlength="8">
    </label>
    <button class="btn btn-primary" type="submit">Guardar</button>
</form>
