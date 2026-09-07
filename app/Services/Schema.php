<?php

declare(strict_types=1);

final class Schema
{
    public static function ensure(): void
    {
        $pdo = Database::pdo();
        $exists = $pdo->query("SHOW TABLES LIKE 'settings'")->fetch();
        if (!$exists) {
            self::applySqlFile();
        }
        SettingsService::seedDefaults();
        CategoryService::seedDefaults();
        self::migrate();
    }

    public static function migrate(): void
    {
        $pdo = Database::pdo();
        $hasRoot = $pdo->query("SHOW COLUMNS FROM repositories LIKE 'root_scanned_at'")->fetch();
        if (!$hasRoot) {
            $pdo->exec('ALTER TABLE repositories ADD COLUMN root_scanned_at DATETIME NULL AFTER last_synced_at');
        }
        $hasScanned = $pdo->query("SHOW COLUMNS FROM sync_runs LIKE 'repos_scanned'")->fetch();
        if (!$hasScanned) {
            $pdo->exec('ALTER TABLE sync_runs ADD COLUMN repos_scanned INT UNSIGNED NOT NULL DEFAULT 0 AFTER repos_upserted');
        }
    }

    public static function applySqlFile(): void
    {
        $sqlFile = dirname(__DIR__, 2) . '/sql/schema.sql';
        if (!is_file($sqlFile)) {
            throw new RuntimeException('Falta sql/schema.sql');
        }

        $sql = (string) file_get_contents($sqlFile);
        $pdo = Database::pdo();
        foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            if (preg_match('/^(SET)\b/i', $stmt)) {
                continue;
            }
            $pdo->exec($stmt);
        }
    }
}
