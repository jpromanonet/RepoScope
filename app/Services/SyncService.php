<?php

declare(strict_types=1);

final class SyncService
{
    public static function lastRun(): ?array
    {
        $row = Database::pdo()->query(
            'SELECT * FROM sync_runs ORDER BY id DESC LIMIT 1'
        )->fetch();
        return $row ?: null;
    }

    public static function recent(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        return Database::pdo()->query(
            "SELECT * FROM sync_runs ORDER BY id DESC LIMIT {$limit}"
        )->fetchAll();
    }

    public static function activeRun(): ?array
    {
        self::expireStale();
        $row = Database::pdo()->query(
            "SELECT * FROM sync_runs WHERE status = 'running' ORDER BY id DESC LIMIT 1"
        )->fetch();
        return $row ?: null;
    }

    public static function progress(?array $run = null): array
    {
        $run = $run ?? self::activeRun() ?? self::lastRun();
        $pending = RepoService::pendingRootCount();
        $upserted = (int) ($run['repos_upserted'] ?? 0);
        $scanned = (int) ($run['repos_scanned'] ?? 0);
        $total = max($upserted, $scanned + $pending);

        return [
            'run' => $run,
            'running' => $run && ($run['status'] ?? '') === 'running',
            'pending' => $pending,
            'scanned' => $scanned,
            'total' => $total,
            'pct' => $total > 0 ? (int) round($scanned / $total * 100) : 0,
        ];
    }

    public static function begin(): array
    {
        @set_time_limit(60);
        self::expireStale();
        $existing = self::activeRun();
        if ($existing) {
            return self::progress($existing);
        }

        $settings = SettingsService::all();
        $client = new GitHubClient(SettingsService::githubToken());
        $user = $client->authenticatedUser();
        $login = (string) ($user['login'] ?? '');

        $pdo = Database::pdo();
        $pdo->prepare(
            'INSERT INTO sync_runs (started_at, status, source) VALUES (NOW(), ?, ?)'
        )->execute(['running', $login]);
        $runId = (int) $pdo->lastInsertId();

        try {
            $fetched = $client->listOwnerRepos();
            $orgs = preg_split('/[\s,]+/', (string) ($settings['github_orgs'] ?? '')) ?: [];
            $seen = [];
            foreach ($fetched as $repo) {
                $id = (int) ($repo['id'] ?? 0);
                if ($id > 0) {
                    $seen[$id] = true;
                }
            }
            foreach ($orgs as $org) {
                $org = trim($org);
                if ($org === '') {
                    continue;
                }
                foreach ($client->listOrgRepos($org) as $repo) {
                    $id = (int) ($repo['id'] ?? 0);
                    if ($id > 0 && isset($seen[$id])) {
                        continue;
                    }
                    $fetched[] = $repo;
                    if ($id > 0) {
                        $seen[$id] = true;
                    }
                }
            }

            $includeForks = ($settings['include_forks'] ?? '1') === '1';
            $includeArchived = ($settings['include_archived'] ?? '1') === '1';
            $upserted = 0;

            foreach ($fetched as $payload) {
                if (!$includeForks && !empty($payload['fork'])) {
                    continue;
                }
                if (!$includeArchived && !empty($payload['archived'])) {
                    continue;
                }
                RepoService::upsertFromGitHub($payload, [], false, false, false);
                $upserted++;
            }

            if (($settings['github_owner'] ?? '') === '' && $login !== '') {
                SettingsService::put('github_owner', $login);
            }

            $pdo->prepare(
                'UPDATE sync_runs SET repos_fetched = ?, repos_upserted = ? WHERE id = ?'
            )->execute([count($fetched), $upserted, $runId]);

            return self::progress(self::findRun($runId));
        } catch (Throwable $e) {
            $pdo->prepare(
                'UPDATE sync_runs SET finished_at = NOW(), status = ?, error_message = ? WHERE id = ?'
            )->execute(['error', $e->getMessage(), $runId]);
            throw $e;
        }
    }

    public static function inspectBatch(?int $runId = null): array
    {
        @set_time_limit(25);
        $run = $runId ? self::findRun($runId) : self::activeRun();
        if (!$run || ($run['status'] ?? '') !== 'running') {
            return self::progress($run);
        }

        $client = new GitHubClient(SettingsService::githubToken());
        $deadline = time() + 10;
        $scanned = 0;
        $pdo = Database::pdo();

        foreach (RepoService::pendingRootScan(20) as $repo) {
            if (time() >= $deadline) {
                break;
            }
            $owner = (string) ($repo['owner'] ?? '');
            $name = (string) ($repo['name'] ?? '');
            $branch = (string) ($repo['default_branch'] ?? '');
            try {
                $contents = $client->rootContents($owner, $name, $branch !== '' ? $branch : null);
            } catch (Throwable $e) {
                $contents = [];
            }
            $files = [];
            foreach ($contents as $item) {
                if (($item['type'] ?? '') === 'file' && !empty($item['name'])) {
                    $files[] = (string) $item['name'];
                }
            }
            [$hasReadme, $hasLicense] = FindingService::detectFromRootFiles($files);
            RepoService::applyRootScan((int) $repo['id'], $files, $hasReadme, $hasLicense);
            $scanned++;
        }

        $donePending = RepoService::pendingRootCount();
        $totalScanned = (int) ($run['repos_scanned'] ?? 0) + $scanned;
        if ($donePending === 0) {
            $open = (int) $pdo->query('SELECT COUNT(*) FROM findings WHERE is_open = 1')->fetchColumn();
            $pdo->prepare(
                'UPDATE sync_runs
                 SET finished_at = NOW(), status = ?, repos_scanned = ?, findings_open = ?
                 WHERE id = ?'
            )->execute(['ok', $totalScanned, $open, (int) $run['id']]);
        } else {
            $pdo->prepare(
                'UPDATE sync_runs SET repos_scanned = ? WHERE id = ?'
            )->execute([$totalScanned, (int) $run['id']]);
        }

        return self::progress(self::findRun((int) $run['id']));
    }

    /** @deprecated Use begin() + inspectBatch(). */
    public static function run(): array
    {
        $state = self::begin();
        $guard = 0;
        while (!empty($state['running']) && $guard < 80) {
            $state = self::inspectBatch((int) ($state['run']['id'] ?? 0));
            $guard++;
        }
        $run = $state['run'] ?? [];
        return [
            'id' => (int) ($run['id'] ?? 0),
            'fetched' => (int) ($run['repos_fetched'] ?? 0),
            'upserted' => (int) ($run['repos_upserted'] ?? 0),
            'findings' => (int) ($run['findings_open'] ?? 0),
            'login' => (string) ($run['source'] ?? ''),
        ];
    }

    private static function findRun(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM sync_runs WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function expireStale(): void
    {
        Database::pdo()->exec(
            "UPDATE sync_runs
             SET status = 'error', finished_at = NOW(),
                 error_message = 'Se interrumpió (la sync anterior quedó colgada). Volvé a sincronizar.'
             WHERE status = 'running' AND started_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );
    }
}
