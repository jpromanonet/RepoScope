<?php
$s = $settings;
$tokenSet = trim((string) ($s['github_token'] ?? '')) !== '' || $hasEnvToken;
$tokenNewUrl = 'https://github.com/settings/tokens/new?scopes=repo,read:org&description=RepoScope';
$tokensUrl = 'https://github.com/settings/tokens';
?>
<div class="page-head">
    <div>
        <p class="eyebrow">Ajustes</p>
        <h1>Configuración</h1>
        <p class="muted">Cómo conectar GitHub y qué mira RepoScope en cada repositorio.</p>
    </div>
</div>

<form method="post" action="<?= e(url('/configuracion')) ?>" class="stack-form">
    <?= csrf_field() ?>

    <section class="panel">
        <h2>Qué cubre el token</h2>
        <p>
            Un Personal Access Token habla <strong>en nombre de un usuario de GitHub</strong>.
            No es un token “de toda la cuenta de GitHub Inc.”: es de <em>tu</em> usuario.
            Con el alcance <code>repo</code>, la sincronización trae:
        </p>
        <ul class="help-list">
            <li>Todos los repos que <strong>posee ese usuario</strong> (públicos y privados, incluidos forks).</li>
            <li>Repos de <strong>organizaciones</strong> donde seas miembro y el token esté autorizado.</li>
            <li>Repos ajenos donde te hayan agregado como <strong>colaborador</strong>.</li>
        </ul>
        <p class="muted">
            No trae repos que solo estrellaste, ni repos públicos de otra gente, ni GitLab.
            Si una organización usa SSO (SAML), después de crear el token hay que pulsar
            <strong>Authorize</strong> junto a esa org en la lista de tokens.
        </p>
    </section>

    <section class="panel mt">
        <h2>Cómo crear el token</h2>
        <ol class="steps">
            <li>
                Entrá a
                <a href="<?= e($tokensUrl) ?>" target="_blank" rel="noopener">GitHub → Settings → Developer settings → Personal access tokens → Tokens (classic)</a>
                e iniciá sesión si hace falta.
            </li>
            <li>
                Pulsá <strong>Generate new token → Generate new token (classic)</strong>
                (o abrí
                <a href="<?= e($tokenNewUrl) ?>" target="_blank" rel="noopener">este enlace con los alcances ya tildados</a>).
            </li>
            <li>
                <strong>Note:</strong> <code>RepoScope</code>.
                <strong>Expiration:</strong> la que te sirva (90 días, 1 año, o sin vencimiento si es solo Home Lab).
            </li>
            <li>
                En <strong>Select scopes</strong> tildá:
                <ul>
                    <li><code>repo</code> — lectura de repos públicos y privados de tu usuario (y orgs, si el token está autorizado).</li>
                    <li><code>read:org</code> — listar organizaciones de las que sos miembro.</li>
                </ul>
                Si solo querés públicos, alcanza <code>public_repo</code> en lugar de <code>repo</code>.
            </li>
            <li>
                Generate token, copiá el valor (<code>ghp_…</code>). GitHub no lo vuelve a mostrar.
            </li>
            <li>
                Pegalo abajo, guardá, y andá a
                <a href="<?= e(url('/sincronizar')) ?>">Sincronizar</a>.
            </li>
        </ol>
        <p class="muted">
            RepoScope solo lista repos y lee archivos de la raíz. No crea, no borra y no pushea.
            El token se guarda en la base local (o en <code>.env</code> como <code>GITHUB_TOKEN</code>).
        </p>
    </section>

    <section class="panel mt">
        <h2>GitHub</h2>
        <label>
            <span>Personal Access Token</span>
            <input type="password" name="github_token" placeholder="<?= $tokenSet ? '••••••••  (dejar vacío para no cambiar)' : 'ghp_…' ?>" autocomplete="off">
        </label>
        <?php if ($tokenSet): ?>
            <p class="ok-line"><?= icon('check', 16) ?> Hay un token cargado. Pegá uno nuevo solo si querés reemplazarlo.</p>
        <?php else: ?>
            <p class="muted">Todavía no hay token. Sin esto no se puede sincronizar.</p>
        <?php endif; ?>

        <div class="form-grid">
            <label>
                <span>Usuario de GitHub</span>
                <input type="text" name="github_owner" value="<?= e((string) ($s['github_owner'] ?? '')) ?>" placeholder="se completa al sincronizar">
                <span class="field-hint">Informativo. Lo rellena la sync con el login del token; no filtra la lista.</span>
            </label>
            <label>
                <span>Organizaciones extra</span>
                <input type="text" name="github_orgs" value="<?= e((string) ($s['github_orgs'] ?? '')) ?>" placeholder="mi-org, otra-org">
                <span class="field-hint">Opcional. Nombres separados por coma, si alguna org no aparece sola. El token tiene que tener acceso a esa org.</span>
            </label>
        </div>
        <label class="check">
            <input type="checkbox" name="include_forks" value="1" <?= ($s['include_forks'] ?? '1') === '1' ? 'checked' : '' ?>>
            Incluir forks al sincronizar
        </label>
        <p class="field-hint indent">Los forks suelen copiar README y licencia ajenos. Por defecto igual se sincronizan, pero no se reportan hallazgos.</p>
        <label class="check">
            <input type="checkbox" name="include_archived" value="1" <?= ($s['include_archived'] ?? '1') === '1' ? 'checked' : '' ?>>
            Incluir archivados al sincronizar
        </label>
    </section>

    <section class="panel mt">
        <h2>Reglas de higiene</h2>
        <p class="muted">Se aplican a cada repo sincronizado, salvo forks/archivados si abajo está tildado que se salteen.</p>
        <label>
            <span>Días sin push para marcar mantenimiento</span>
            <input type="number" name="stale_days" min="1" max="3650" value="<?= e((string) ($s['stale_days'] ?? '180')) ?>">
            <span class="field-hint">Si el último push es más viejo que esto, el repo entra en Atención → Mantenimiento. 180 días ≈ medio año.</span>
        </label>
        <label>
            <span>Archivos requeridos en la raíz</span>
            <textarea name="required_files" rows="5"><?= e((string) ($s['required_files'] ?? '')) ?></textarea>
            <span class="field-hint">Uno por línea, además de README y LICENSE (esos se chequean siempre). Ejemplo: <code>.gitignore</code>, <code>CODEOWNERS</code>.</span>
        </label>
        <label class="check">
            <input type="checkbox" name="findings_skip_forks" value="1" <?= ($s['findings_skip_forks'] ?? '1') === '1' ? 'checked' : '' ?>>
            No reportar hallazgos en forks
        </label>
        <label class="check">
            <input type="checkbox" name="findings_skip_archived" value="1" <?= ($s['findings_skip_archived'] ?? '1') === '1' ? 'checked' : '' ?>>
            No reportar hallazgos en archivados
        </label>
        <label class="check">
            <input type="checkbox" name="rescan" value="1">
            Recalcular hallazgos al guardar (sin volver a llamar a GitHub)
        </label>
    </section>

    <section class="panel mt">
        <h2>Apariencia</h2>
        <label>
            <span>Tema</span>
            <select name="theme">
                <option value="dark" <?= ($s['theme'] ?? 'dark') === 'dark' ? 'selected' : '' ?>>Oscuro</option>
                <option value="light" <?= ($s['theme'] ?? '') === 'light' ? 'selected' : '' ?>>Claro</option>
            </select>
        </label>
    </section>

    <div class="btn-row mt">
        <button class="btn btn-primary" type="submit">Guardar</button>
        <a class="btn btn-ghost" href="<?= e(url('/sincronizar')) ?>">Ir a sincronizar</a>
    </div>
</form>
