<?php

declare(strict_types=1);

final class SettingsController
{
    public static function index(): void
    {
        view('settings/index', [
            'title' => 'Configuración',
            'settings' => SettingsService::all(),
            'hasEnvToken' => trim((string) (rs_env('GITHUB_TOKEN', '') ?? '')) !== '',
        ]);
    }

    public static function save(): void
    {
        require_csrf();
        try {
            SettingsService::save([
                'github_token' => (string) ($_POST['github_token'] ?? ''),
                'github_owner' => trim((string) ($_POST['github_owner'] ?? '')),
                'github_orgs' => trim((string) ($_POST['github_orgs'] ?? '')),
                'include_forks' => $_POST['include_forks'] ?? '0',
                'include_archived' => $_POST['include_archived'] ?? '0',
                'findings_skip_forks' => $_POST['findings_skip_forks'] ?? '0',
                'findings_skip_archived' => $_POST['findings_skip_archived'] ?? '0',
                'stale_days' => (string) max(1, (int) ($_POST['stale_days'] ?? 180)),
                'required_files' => (string) ($_POST['required_files'] ?? ''),
                'theme' => in_array($_POST['theme'] ?? 'dark', ['dark', 'light'], true)
                    ? (string) $_POST['theme']
                    : 'dark',
            ]);
            if (!empty($_POST['rescan'])) {
                RepoService::refreshAllFindings();
                flash('success', 'Configuración guardada y hallazgos recalculados.');
            } else {
                flash('success', 'Configuración guardada.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/configuracion');
    }

    public static function theme(): void
    {
        require_csrf();
        $theme = in_array($_POST['theme'] ?? '', ['dark', 'light'], true) ? (string) $_POST['theme'] : 'dark';
        SettingsService::put('theme', $theme);
        $back = (string) ($_POST['back'] ?? '/');
        if (!str_starts_with($back, '/')) {
            $back = '/';
        }
        redirect($back);
    }
}
