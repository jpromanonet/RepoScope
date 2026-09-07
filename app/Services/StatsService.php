<?php

declare(strict_types=1);

final class StatsService
{
    public static function dashboard(): array
    {
        $pdo = Database::pdo();
        $total = (int) $pdo->query('SELECT COUNT(*) FROM repositories')->fetchColumn();
        $attention = (int) $pdo->query(
            'SELECT COUNT(*) FROM repositories WHERE health_score < 100 AND is_ignored = 0'
        )->fetchColumn();
        $healthy = (int) $pdo->query(
            'SELECT COUNT(*) FROM repositories WHERE health_score = 100 OR is_ignored = 1'
        )->fetchColumn();
        $uncategorized = (int) $pdo->query(
            'SELECT COUNT(*) FROM repositories WHERE category_id IS NULL'
        )->fetchColumn();
        $private = (int) $pdo->query('SELECT COUNT(*) FROM repositories WHERE is_private = 1')->fetchColumn();
        $forks = (int) $pdo->query('SELECT COUNT(*) FROM repositories WHERE is_fork = 1')->fetchColumn();
        $archived = (int) $pdo->query('SELECT COUNT(*) FROM repositories WHERE is_archived = 1')->fetchColumn();
        $avg = $total > 0
            ? (int) round((float) $pdo->query('SELECT AVG(health_score) FROM repositories')->fetchColumn())
            : 100;

        return [
            'total' => $total,
            'attention' => $attention,
            'healthy' => $healthy,
            'uncategorized' => $uncategorized,
            'private' => $private,
            'forks' => $forks,
            'archived' => $archived,
            'avg_health' => $avg,
            'findings' => FindingService::openByType(),
            'by_language' => array_slice(RepoService::languages(), 0, 8),
            'by_category' => CategoryService::counts(),
            'last_sync' => SyncService::lastRun(),
            'stale_days' => SettingsService::staleDays(),
        ];
    }

    public static function metrics(): array
    {
        $pdo = Database::pdo();
        $dash = self::dashboard();
        $total = (int) $dash['total'];
        $safe = max(1, $total);

        $bands = [
            'ok' => 0,
            'high' => 0,
            'warn' => 0,
            'bad' => 0,
        ];
        if ($total > 0) {
            $row = $pdo->query(
                "SELECT
                    SUM(health_score = 100) AS ok_n,
                    SUM(health_score BETWEEN 80 AND 99) AS high_n,
                    SUM(health_score BETWEEN 50 AND 79) AS warn_n,
                    SUM(health_score < 50) AS bad_n
                 FROM repositories"
            )->fetch() ?: [];
            $bands = [
                'ok' => (int) ($row['ok_n'] ?? 0),
                'high' => (int) ($row['high_n'] ?? 0),
                'warn' => (int) ($row['warn_n'] ?? 0),
                'bad' => (int) ($row['bad_n'] ?? 0),
            ];
        }

        $activity = [
            'd30' => 0,
            'd90' => 0,
            'd180' => 0,
            'd365' => 0,
            'older' => 0,
            'never' => 0,
        ];
        if ($total > 0) {
            $row = $pdo->query(
                "SELECT
                    SUM(pushed_at IS NULL) AS never_n,
                    SUM(pushed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS d30,
                    SUM(pushed_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND pushed_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)) AS d90,
                    SUM(pushed_at < DATE_SUB(NOW(), INTERVAL 90 DAY) AND pushed_at >= DATE_SUB(NOW(), INTERVAL 180 DAY)) AS d180,
                    SUM(pushed_at < DATE_SUB(NOW(), INTERVAL 180 DAY) AND pushed_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)) AS d365,
                    SUM(pushed_at IS NOT NULL AND pushed_at < DATE_SUB(NOW(), INTERVAL 365 DAY)) AS older_n
                 FROM repositories"
            )->fetch() ?: [];
            $activity = [
                'd30' => (int) ($row['d30'] ?? 0),
                'd90' => (int) ($row['d90'] ?? 0),
                'd180' => (int) ($row['d180'] ?? 0),
                'd365' => (int) ($row['d365'] ?? 0),
                'older' => (int) ($row['older_n'] ?? 0),
                'never' => (int) ($row['never_n'] ?? 0),
            ];
        }

        $findingRepos = [];
        foreach (array_keys(finding_types()) as $type) {
            $findingRepos[$type] = 0;
        }
        $findingRows = $pdo->query(
            "SELECT type, COUNT(DISTINCT repository_id) AS repos, COUNT(*) AS total
             FROM findings WHERE is_open = 1 GROUP BY type"
        )->fetchAll();
        foreach ($findingRows as $row) {
            $findingRepos[(string) $row['type']] = (int) $row['repos'];
        }
        $openFindings = (int) $pdo->query('SELECT COUNT(*) FROM findings WHERE is_open = 1')->fetchColumn();
        $reposWithFindings = (int) $pdo->query(
            'SELECT COUNT(DISTINCT repository_id) FROM findings WHERE is_open = 1'
        )->fetchColumn();

        $byOwner = $pdo->query(
            "SELECT owner, COUNT(*) AS total, ROUND(AVG(health_score)) AS avg_health,
                    SUM(is_private) AS private_n, SUM(is_fork) AS fork_n
             FROM repositories
             GROUP BY owner
             ORDER BY total DESC, owner ASC"
        )->fetchAll();

        $byLanguage = RepoService::languages();
        $byCategory = $pdo->query(
            "SELECT c.id, c.name, c.color, COUNT(r.id) AS total,
                    ROUND(AVG(r.health_score)) AS avg_health
             FROM categories c
             LEFT JOIN repositories r ON r.category_id = c.id
             GROUP BY c.id, c.name, c.color
             ORDER BY total DESC, c.name ASC"
        )->fetchAll();

        $worst = $total > 0
            ? $pdo->query(
                "SELECT id, full_name, health_score, language, visibility, is_fork, is_archived, pushed_at
                 FROM repositories
                 WHERE is_ignored = 0 AND health_score < 100
                 ORDER BY health_score ASC, full_name ASC
                 LIMIT 12"
            )->fetchAll()
            : [];

        $totals = $pdo->query(
            "SELECT COALESCE(SUM(stars), 0) AS stars,
                    COALESCE(SUM(open_issues), 0) AS issues,
                    COALESCE(SUM(is_template), 0) AS templates
             FROM repositories"
        )->fetch() ?: [];

        $mix = ['originals' => 0, 'forks' => 0, 'archived' => 0];
        if ($total > 0) {
            $row = $pdo->query(
                "SELECT
                    SUM(is_archived = 0 AND is_fork = 0) AS originals,
                    SUM(is_archived = 0 AND is_fork = 1) AS forks,
                    SUM(is_archived = 1) AS archived
                 FROM repositories"
            )->fetch() ?: [];
            $mix = [
                'originals' => (int) ($row['originals'] ?? 0),
                'forks' => (int) ($row['forks'] ?? 0),
                'archived' => (int) ($row['archived'] ?? 0),
            ];
        }

        $pct = static function (int $n) use ($total, $safe): int {
            return $total > 0 ? (int) round($n / $safe * 100) : 0;
        };

        return [
            'total' => $total,
            'avg_health' => (int) $dash['avg_health'],
            'healthy' => (int) $dash['healthy'],
            'attention' => (int) $dash['attention'],
            'uncategorized' => (int) $dash['uncategorized'],
            'private' => (int) $dash['private'],
            'public' => max(0, $total - (int) $dash['private']),
            'forks' => (int) $dash['forks'],
            'archived' => (int) $dash['archived'],
            'originals' => $mix['originals'],
            'mix' => $mix,
            'stars' => (int) ($totals['stars'] ?? 0),
            'issues' => (int) ($totals['issues'] ?? 0),
            'templates' => (int) ($totals['templates'] ?? 0),
            'open_findings' => $openFindings,
            'repos_with_findings' => $reposWithFindings,
            'pct_healthy' => $pct((int) $dash['healthy']),
            'pct_attention' => $pct((int) $dash['attention']),
            'pct_categorized' => $pct($total - (int) $dash['uncategorized']),
            'pct_private' => $pct((int) $dash['private']),
            'bands' => $bands,
            'activity' => $activity,
            'findings' => $dash['findings'],
            'finding_repos' => $findingRepos,
            'by_language' => $byLanguage,
            'by_category' => $byCategory,
            'by_owner' => $byOwner,
            'worst' => $worst,
            'stale_days' => (int) $dash['stale_days'],
            'last_sync' => $dash['last_sync'],
            'syncs' => SyncService::recent(10),
            'created_months' => self::monthSeries('github_created_at', 18),
            'pushed_months' => self::monthSeries('pushed_at', 12),
            'sync_series' => self::syncSeries(),
        ];
    }

    /** @return array{labels:list<string>,values:list<int>} */
    private static function monthSeries(string $column, int $months): array
    {
        $allowed = ['github_created_at' => true, 'pushed_at' => true];
        if (!isset($allowed[$column])) {
            return ['labels' => [], 'values' => []];
        }
        $rows = Database::pdo()->query(
            "SELECT DATE_FORMAT({$column}, '%Y-%m') AS ym, COUNT(*) AS total
             FROM repositories
             WHERE {$column} IS NOT NULL
               AND {$column} >= DATE_SUB(NOW(), INTERVAL {$months} MONTH)
             GROUP BY ym"
        )->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['ym']] = (int) $row['total'];
        }
        $labels = [];
        $values = [];
        $cursor = new DateTimeImmutable('first day of this month');
        for ($i = $months - 1; $i >= 0; $i--) {
            $ym = $cursor->modify("-{$i} months")->format('Y-m');
            $labels[] = $cursor->modify("-{$i} months")->format('m/y');
            $values[] = $map[$ym] ?? 0;
        }
        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array{labels:list<string>,upserted:list<int>,findings:list<int>} */
    private static function syncSeries(): array
    {
        $runs = array_reverse(SyncService::recent(12));
        $labels = [];
        $upserted = [];
        $findings = [];
        foreach ($runs as $run) {
            if (($run['status'] ?? '') !== 'ok') {
                continue;
            }
            $labels[] = format_datetime((string) ($run['finished_at'] ?? $run['started_at'] ?? ''), 'd/m');
            $upserted[] = (int) ($run['repos_upserted'] ?? 0);
            $findings[] = (int) ($run['findings_open'] ?? 0);
        }
        return ['labels' => $labels, 'upserted' => $upserted, 'findings' => $findings];
    }
}
