<?php

declare(strict_types=1);

final class SyncController
{
    public static function index(): void
    {
        view('sync/index', [
            'title' => 'Sincronizar',
            'last' => SyncService::lastRun(),
            'runs' => SyncService::recent(),
            'progress' => SyncService::progress(),
            'hasToken' => SettingsService::githubToken() !== '',
            'owner' => (string) SettingsService::get('github_owner', ''),
            'total' => RepoService::countAll(),
        ]);
    }

    public static function run(): void
    {
        require_csrf();
        session_release();
        try {
            $state = SyncService::begin();
            if (!empty($state['running']) && (int) ($state['pending'] ?? 0) > 0) {
                $state = SyncService::inspectBatch((int) ($state['run']['id'] ?? 0));
            }
            session_resume();
            self::finishOrContinue($state, true);
        } catch (Throwable $e) {
            session_resume();
            flash('error', $e->getMessage());
            redirect('/sincronizar');
        }
    }

    public static function step(): void
    {
        require_csrf();
        session_release();
        try {
            $state = SyncService::inspectBatch();
            session_resume();
            self::finishOrContinue($state, false);
        } catch (Throwable $e) {
            session_resume();
            flash('error', $e->getMessage());
            redirect('/sincronizar');
        }
    }

    public static function rescan(): void
    {
        require_csrf();
        session_release();
        try {
            $n = RepoService::refreshAllFindings();
            session_resume();
            flash('success', 'Se reanalizaron ' . $n . ' repositorios con las reglas actuales.');
        } catch (Throwable $e) {
            session_resume();
            flash('error', $e->getMessage());
        }
        redirect('/sincronizar');
    }

    private static function finishOrContinue(array $state, bool $started): void
    {
        $run = $state['run'] ?? [];
        if (empty($state['running'])) {
            flash(
                'success',
                'Sincronización lista: ' . (int) ($run['repos_upserted'] ?? 0) . ' repos · '
                . (int) ($run['findings_open'] ?? 0) . ' hallazgos abiertos'
                . (!empty($run['source']) ? ' · @' . $run['source'] : '')
            );
            redirect('/sincronizar');
        }
        if ($started) {
            flash(
                'success',
                'Lista traída: ' . (int) ($run['repos_upserted'] ?? 0)
                . ' repos. Ahora se revisan los archivos de a poco, sin congelar la app.'
            );
        }
        redirect('/sincronizar');
    }
}
