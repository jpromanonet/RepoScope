<?php

declare(strict_types=1);

final class RepoService
{
    public static function search(array $filters, string $sort, string $dir, int $page, int $perPage = 25): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(r.full_name LIKE ? OR r.description LIKE ? OR r.language LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['language'])) {
            $where[] = 'r.language = ?';
            $params[] = $filters['language'];
        }
        if (!empty($filters['category_id'])) {
            if ($filters['category_id'] === 'none') {
                $where[] = 'r.category_id IS NULL';
            } else {
                $where[] = 'r.category_id = ?';
                $params[] = (int) $filters['category_id'];
            }
        }
        if (!empty($filters['visibility'])) {
            $where[] = 'r.visibility = ?';
            $params[] = $filters['visibility'];
        }
        if (($filters['attention'] ?? '') === '1') {
            $where[] = 'r.health_score < 100 AND r.is_ignored = 0';
        }
        if (($filters['fork'] ?? '') === '1') {
            $where[] = 'r.is_fork = 1';
        } elseif (($filters['fork'] ?? '') === '0') {
            $where[] = 'r.is_fork = 0';
        }
        if (($filters['archived'] ?? '') === '1') {
            $where[] = 'r.is_archived = 1';
        } elseif (($filters['archived'] ?? '') === '0') {
            $where[] = 'r.is_archived = 0';
        }
        if (!empty($filters['finding'])) {
            $where[] = 'EXISTS (SELECT 1 FROM findings f WHERE f.repository_id = r.id AND f.is_open = 1 AND f.type = ?)';
            $params[] = $filters['finding'];
        }

        $allowedSort = [
            'full_name' => 'r.full_name',
            'pushed_at' => 'r.pushed_at',
            'health_score' => 'r.health_score',
            'stars' => 'r.stars',
            'language' => 'r.language',
        ];
        $sortSql = $allowedSort[$sort] ?? 'r.full_name';
        $dirSql = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';
        $sqlWhere = implode(' AND ', $where);

        $pdo = Database::pdo();
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM repositories r WHERE {$sqlWhere}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT r.*, c.name AS category_name, c.color AS category_color,
                    (SELECT COUNT(*) FROM findings f WHERE f.repository_id = r.id AND f.is_open = 1) AS open_findings
             FROM repositories r
             LEFT JOIN categories c ON c.id = r.category_id
             WHERE {$sqlWhere}
             ORDER BY {$sortSql} {$dirSql}, r.full_name ASC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.*, c.name AS category_name, c.color AS category_color, c.slug AS category_slug
             FROM repositories r
             LEFT JOIN categories c ON c.id = r.category_id
             WHERE r.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function languages(): array
    {
        return Database::pdo()->query(
            "SELECT language AS name, COUNT(*) AS total
             FROM repositories
             WHERE language IS NOT NULL AND language <> ''
             GROUP BY language
             ORDER BY total DESC, language ASC"
        )->fetchAll();
    }

    public static function countAll(): int
    {
        return (int) Database::pdo()->query('SELECT COUNT(*) FROM repositories')->fetchColumn();
    }

    public static function upsertFromGitHub(array $payload, array $rootFiles = [], bool $hasReadme = false, bool $hasLicenseFile = false, bool $withRoot = true): int
    {
        $githubId = (int) ($payload['id'] ?? 0);
        if ($githubId <= 0) {
            throw new RuntimeException('Repositorio de GitHub sin id.');
        }

        $fullName = (string) ($payload['full_name'] ?? '');
        $owner = (string) ($payload['owner']['login'] ?? explode('/', $fullName)[0] ?? '');
        $license = $payload['license'] ?? null;
        $topics = $payload['topics'] ?? [];
        if (!is_array($topics)) {
            $topics = [];
        }

        $existing = self::findByGithubId($githubId);
        $categoryId = $existing['category_id'] ?? null;
        if ($categoryId === null) {
            $categoryId = CategoryService::suggestFor([
                'is_archived' => !empty($payload['archived']),
                'is_fork' => !empty($payload['fork']),
            ]);
        }

        $row = [
            'github_id' => $githubId,
            'full_name' => $fullName,
            'name' => (string) ($payload['name'] ?? ''),
            'owner' => $owner,
            'description' => null_if_blank($payload['description'] ?? null),
            'html_url' => (string) ($payload['html_url'] ?? ''),
            'homepage' => null_if_blank($payload['homepage'] ?? null),
            'language' => null_if_blank($payload['language'] ?? null),
            'visibility' => (string) ($payload['visibility'] ?? (!empty($payload['private']) ? 'private' : 'public')),
            'is_private' => !empty($payload['private']) ? 1 : 0,
            'is_fork' => !empty($payload['fork']) ? 1 : 0,
            'is_archived' => !empty($payload['archived']) ? 1 : 0,
            'is_template' => !empty($payload['is_template']) ? 1 : 0,
            'default_branch' => null_if_blank($payload['default_branch'] ?? null),
            'stars' => (int) ($payload['stargazers_count'] ?? 0),
            'forks_count' => (int) ($payload['forks_count'] ?? 0),
            'open_issues' => (int) ($payload['open_issues_count'] ?? 0),
            'size_kb' => (int) ($payload['size'] ?? 0),
            'topics' => encode_json_list($topics),
            'root_files' => encode_json_list($rootFiles),
            'license_key' => is_array($license) ? null_if_blank($license['key'] ?? null) : null,
            'license_name' => is_array($license) ? null_if_blank($license['name'] ?? null) : null,
            'has_readme' => $hasReadme ? 1 : 0,
            'has_license_file' => $hasLicenseFile ? 1 : 0,
            'pushed_at' => github_dt($payload['pushed_at'] ?? null),
            'github_created_at' => github_dt($payload['created_at'] ?? null),
            'github_updated_at' => github_dt($payload['updated_at'] ?? null),
            'category_id' => $categoryId,
        ];

        $pdo = Database::pdo();
        if ($existing) {
            if ($withRoot) {
                $pdo->prepare(
                    'UPDATE repositories SET
                        full_name = ?, name = ?, owner = ?, description = ?, html_url = ?, homepage = ?,
                        language = ?, visibility = ?, is_private = ?, is_fork = ?, is_archived = ?, is_template = ?,
                        default_branch = ?, stars = ?, forks_count = ?, open_issues = ?, size_kb = ?,
                        topics = ?, root_files = ?, license_key = ?, license_name = ?, has_readme = ?,
                        has_license_file = ?, pushed_at = ?, github_created_at = ?, github_updated_at = ?,
                        last_synced_at = NOW(), category_id = ?
                     WHERE id = ?'
                )->execute([
                    $row['full_name'], $row['name'], $row['owner'], $row['description'], $row['html_url'], $row['homepage'],
                    $row['language'], $row['visibility'], $row['is_private'], $row['is_fork'], $row['is_archived'], $row['is_template'],
                    $row['default_branch'], $row['stars'], $row['forks_count'], $row['open_issues'], $row['size_kb'],
                    $row['topics'], $row['root_files'], $row['license_key'], $row['license_name'], $row['has_readme'],
                    $row['has_license_file'], $row['pushed_at'], $row['github_created_at'], $row['github_updated_at'],
                    $row['category_id'], $existing['id'],
                ]);
            } else {
                $pdo->prepare(
                    'UPDATE repositories SET
                        full_name = ?, name = ?, owner = ?, description = ?, html_url = ?, homepage = ?,
                        language = ?, visibility = ?, is_private = ?, is_fork = ?, is_archived = ?, is_template = ?,
                        default_branch = ?, stars = ?, forks_count = ?, open_issues = ?, size_kb = ?,
                        topics = ?, license_key = ?, license_name = ?,
                        pushed_at = ?, github_created_at = ?, github_updated_at = ?,
                        last_synced_at = NOW(), category_id = ?
                     WHERE id = ?'
                )->execute([
                    $row['full_name'], $row['name'], $row['owner'], $row['description'], $row['html_url'], $row['homepage'],
                    $row['language'], $row['visibility'], $row['is_private'], $row['is_fork'], $row['is_archived'], $row['is_template'],
                    $row['default_branch'], $row['stars'], $row['forks_count'], $row['open_issues'], $row['size_kb'],
                    $row['topics'], $row['license_key'], $row['license_name'],
                    $row['pushed_at'], $row['github_created_at'], $row['github_updated_at'],
                    $row['category_id'], $existing['id'],
                ]);
            }
            $id = (int) $existing['id'];
            $repo = self::find($id);
        } else {
            $pdo->prepare(
                'INSERT INTO repositories (
                    github_id, full_name, name, owner, description, html_url, homepage, language, visibility,
                    is_private, is_fork, is_archived, is_template, default_branch, stars, forks_count, open_issues,
                    size_kb, topics, root_files, license_key, license_name, has_readme, has_license_file,
                    pushed_at, github_created_at, github_updated_at, last_synced_at, category_id
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?)'
            )->execute([
                $row['github_id'], $row['full_name'], $row['name'], $row['owner'], $row['description'],
                $row['html_url'], $row['homepage'], $row['language'], $row['visibility'],
                $row['is_private'], $row['is_fork'], $row['is_archived'], $row['is_template'],
                $row['default_branch'], $row['stars'], $row['forks_count'], $row['open_issues'],
                $row['size_kb'], $row['topics'], $row['root_files'], $row['license_key'], $row['license_name'],
                $row['has_readme'], $row['has_license_file'], $row['pushed_at'], $row['github_created_at'],
                $row['github_updated_at'], $row['category_id'],
            ]);
            $id = (int) $pdo->lastInsertId();
            $repo = self::find($id);
        }

        self::refreshFindings($repo ?? ['id' => $id]);
        return $id;
    }

    public static function applyRootScan(int $id, array $rootFiles, bool $hasReadme, bool $hasLicenseFile): void
    {
        Database::pdo()->prepare(
            'UPDATE repositories
             SET root_files = ?, has_readme = ?, has_license_file = ?, root_scanned_at = NOW()
             WHERE id = ?'
        )->execute([
            encode_json_list($rootFiles),
            $hasReadme ? 1 : 0,
            $hasLicenseFile ? 1 : 0,
            $id,
        ]);
        self::refreshFindings(self::find($id));
    }

    public static function pendingRootScan(int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        return Database::pdo()->query(
            "SELECT * FROM repositories
             WHERE last_synced_at IS NOT NULL
               AND (root_scanned_at IS NULL OR root_scanned_at < last_synced_at)
             ORDER BY id ASC
             LIMIT {$limit}"
        )->fetchAll();
    }

    public static function pendingRootCount(): int
    {
        return (int) Database::pdo()->query(
            "SELECT COUNT(*) FROM repositories
             WHERE last_synced_at IS NOT NULL
               AND (root_scanned_at IS NULL OR root_scanned_at < last_synced_at)"
        )->fetchColumn();
    }

    public static function refreshFindings(?array $repo): void
    {
        if (!$repo || empty($repo['id'])) {
            return;
        }
        $findings = FindingService::scanRepository($repo, SettingsService::all());
        FindingService::replaceForRepository((int) $repo['id'], $findings);
        $score = FindingService::healthScore($findings);
        Database::pdo()->prepare('UPDATE repositories SET health_score = ? WHERE id = ?')
            ->execute([$score, $repo['id']]);
    }

    public static function refreshAllFindings(): int
    {
        $repos = Database::pdo()->query('SELECT * FROM repositories')->fetchAll();
        foreach ($repos as $repo) {
            self::refreshFindings($repo);
        }
        return count($repos);
    }

    public static function updateLocal(int $id, array $data): void
    {
        $repo = self::find($id);
        if (!$repo) {
            throw new RuntimeException('Repositorio no encontrado.');
        }
        Database::pdo()->prepare(
            'UPDATE repositories SET category_id = ?, notes = ?, is_ignored = ?, ignored_findings = ? WHERE id = ?'
        )->execute([
            $data['category_id'],
            $data['notes'],
            $data['is_ignored'] ? 1 : 0,
            encode_json_list($data['ignored_findings'] ?? []),
            $id,
        ]);
        self::refreshFindings(self::find($id));
    }

    public static function dismissFinding(int $id, string $type): void
    {
        $repo = self::find($id);
        if (!$repo) {
            throw new RuntimeException('Repositorio no encontrado.');
        }
        $ignored = parse_json_list($repo['ignored_findings'] ?? '[]');
        if (!in_array($type, $ignored, true)) {
            $ignored[] = $type;
        }
        Database::pdo()->prepare('UPDATE repositories SET ignored_findings = ? WHERE id = ?')
            ->execute([encode_json_list($ignored), $id]);
        self::refreshFindings(self::find($id));
    }

    public static function restoreFinding(int $id, string $type): void
    {
        $repo = self::find($id);
        if (!$repo) {
            throw new RuntimeException('Repositorio no encontrado.');
        }
        $ignored = array_values(array_filter(
            parse_json_list($repo['ignored_findings'] ?? '[]'),
            static fn ($item) => $item !== $type
        ));
        Database::pdo()->prepare('UPDATE repositories SET ignored_findings = ? WHERE id = ?')
            ->execute([encode_json_list($ignored), $id]);
        self::refreshFindings(self::find($id));
    }

    private static function findByGithubId(int $githubId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM repositories WHERE github_id = ? LIMIT 1');
        $stmt->execute([$githubId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
