<h2>Iniciar sesión</h2>
<p class="muted">Una cuenta local. El usuario inicial es <code>admin@local</code> / <code>reposcope</code> hasta que lo cambies en Perfil.</p>
<form method="post" action="<?= e(url('/login')) ?>" class="stack-form">
    <?= csrf_field() ?>
    <label>
        <span>Mail</span>
        <input type="text" name="email" required autocomplete="username" placeholder="admin@local" inputmode="email">
    </label>
    <label>
        <span>Contraseña</span>
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button class="btn btn-primary" type="submit">Entrar</button>
</form>
