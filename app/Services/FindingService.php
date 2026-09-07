<?php

declare(strict_types=1);

final class FindingService
{
    private const README_NAMES = [
        'readme', 'readme.md', 'readme.txt', 'readme.rst', 'readme.adoc', 'readme.org',
    ];

    private const LICENSE_NAMES = [
        'license', 'license.md', 'license.txt', 'licence', 'licence.md', 'copying', 'copying.md',
    ];

    public static function scanRepository(array $repo, array $settings): array
    {
        if (!empty($repo['is_ignored'])) {
            return [];
        }
        if (!empty($repo['is_fork']) && ($settings['findings_skip_forks'] ?? '1') === '1') {
            return [];
        }
        if (!empty($repo['is_archived']) && ($settings['findings_skip_archived'] ?? '1') === '1') {
            return [];
        }

        $ignored = parse_json_list($repo['ignored_findings'] ?? '[]');
        $root = array_map('strtolower', parse_json_list($repo['root_files'] ?? '[]'));
        $findings = [];

        if (!in_array('readme', $ignored, true) && !empty($repo['root_scanned_at']) && empty($repo['has_readme'])) {
            $findings[] = [
                'type' => 'readme',
                'severity' => 'critical',
                'message' => 'No hay README en la raíz',
                'detail' => '',
            ];
        }

        $hasLicenseMeta = null_if_blank($repo['license_key'] ?? null) !== null
            || null_if_blank($repo['license_name'] ?? null) !== null;
        $hasLicenseFile = !empty($repo['has_license_file']);
        if (!in_array('license', $ignored, true) && !$hasLicenseMeta && !$hasLicenseFile) {
            $findings[] = [
                'type' => 'license',
                'severity' => 'warn',
                'message' => 'No hay licencia declarada ni archivo LICENSE',
                'detail' => '',
            ];
        }

        if (!in_array('description', $ignored, true) && null_if_blank($repo['description'] ?? null) === null) {
            $findings[] = [
                'type' => 'description',
                'severity' => 'warn',
                'message' => 'El repositorio no tiene descripción',
                'detail' => '',
            ];
        }

        if (!in_array('file', $ignored, true) && !empty($repo['root_scanned_at'])) {
            foreach (SettingsService::requiredFiles() as $file) {
                if (!self::rootHas($root, $file)) {
                    $findings[] = [
                        'type' => 'file',
                        'severity' => 'info',
                        'message' => 'Falta el archivo ' . $file,
                        'detail' => $file,
                    ];
                }
            }
        }

        if (!in_array('maintenance', $ignored, true) && empty($repo['is_archived'])) {
            $staleDays = SettingsService::staleDays();
            $pushed = $repo['pushed_at'] ?? null;
            if (is_string($pushed) && $pushed !== '') {
                $age = time() - strtotime($pushed);
                if ($age > $staleDays * 86400) {
                    $findings[] = [
                        'type' => 'maintenance',
                        'severity' => 'warn',
                        'message' => 'Sin actividad hace más de ' . $staleDays . ' días',
                        'detail' => '',
                    ];
                }
            }
        }

        return $findings;
    }

    public static function replaceForRepository(int $repoId, array $findings): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('DELETE FROM findings WHERE repository_id = ?')->execute([$repoId]);
        if ($findings === []) {
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO findings (repository_id, type, severity, message, detail, is_open, detected_at)
             VALUES (?, ?, ?, ?, ?, 1, NOW())'
        );
        foreach ($findings as $finding) {
            $stmt->execute([
                $repoId,
                $finding['type'],
                $finding['severity'],
                $finding['message'],
                $finding['detail'] ?? '',
            ]);
        }
    }

    public static function healthScore(array $findings): int
    {
        $score = 100;
        $filePenalty = 0;
        foreach ($findings as $finding) {
            $score -= match ($finding['type']) {
                'readme' => 25,
                'license' => 20,
                'description' => 15,
                'maintenance' => 20,
                'file' => 0,
                default => 5,
            };
            if ($finding['type'] === 'file') {
                $filePenalty += 10;
            }
        }
        $score -= min(20, $filePenalty);
        return max(0, min(100, $score));
    }

    public static function detectFromRootFiles(array $names): array
    {
        $lower = array_map('strtolower', $names);
        $hasReadme = false;
        $hasLicense = false;
        foreach ($lower as $name) {
            if (in_array($name, self::README_NAMES, true)) {
                $hasReadme = true;
            }
            if (in_array($name, self::LICENSE_NAMES, true)) {
                $hasLicense = true;
            }
        }
        return [$hasReadme, $hasLicense];
    }

    public static function openByType(): array
    {
        $rows = Database::pdo()->query(
            "SELECT type, COUNT(*) AS total FROM findings WHERE is_open = 1 GROUP BY type"
        )->fetchAll();
        $out = [];
        foreach (array_keys(finding_types()) as $type) {
            $out[$type] = 0;
        }
        foreach ($rows as $row) {
            $out[$row['type']] = (int) $row['total'];
        }
        return $out;
    }

    public static function openForRepo(int $repoId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM findings WHERE repository_id = ? AND is_open = 1 ORDER BY type, detail'
        );
        $stmt->execute([$repoId]);
        return $stmt->fetchAll();
    }

    public static function attention(array $filters, int $page = 1, int $perPage = 40): array
    {
        $where = ['f.is_open = 1'];
        $params = [];
        if (!empty($filters['type'])) {
            $where[] = 'f.type = ?';
            $params[] = $filters['type'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(r.full_name LIKE ? OR r.description LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sqlWhere = implode(' AND ', $where);
        $pdo = Database::pdo();
        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM findings f JOIN repositories r ON r.id = f.repository_id WHERE {$sqlWhere}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare(
            "SELECT f.*, r.full_name, r.html_url, r.language, r.health_score, r.visibility,
                    r.is_fork, r.is_archived, c.name AS category_name, c.color AS category_color
             FROM findings f
             JOIN repositories r ON r.id = f.repository_id
             LEFT JOIN categories c ON c.id = r.category_id
             WHERE {$sqlWhere}
             ORDER BY FIELD(f.severity, 'critical', 'warn', 'info'), r.full_name, f.type
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

    private static function rootHas(array $rootLower, string $file): bool
    {
        $target = strtolower(basename(str_replace('\\', '/', $file)));
        return in_array($target, $rootLower, true);
    }
}
