<?php

declare(strict_types=1);

final class SettingsService
{
    private const DEFAULTS = [
        'github_token' => '',
        'github_owner' => '',
        'github_orgs' => '',
        'include_forks' => '1',
        'include_archived' => '1',
        'findings_skip_forks' => '1',
        'findings_skip_archived' => '1',
        'stale_days' => '180',
        'required_files' => ".gitignore",
        'theme' => 'dark',
    ];

    public static function seedDefaults(): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)'
        );
        foreach (self::DEFAULTS as $key => $value) {
            $env = self::envFallback($key);
            $stmt->execute([$key, $env ?? $value]);
        }
    }

    public static function all(): array
    {
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = self::DEFAULTS;
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        $token = rs_env('GITHUB_TOKEN');
        if (is_string($token) && $token !== '' && ($settings['github_token'] ?? '') === '') {
            $settings['github_token'] = $token;
        }
        $owner = rs_env('GITHUB_OWNER');
        if (is_string($owner) && $owner !== '' && ($settings['github_owner'] ?? '') === '') {
            $settings['github_owner'] = $owner;
        }
        return $settings;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        Database::pdo()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute([$key, $value]);
    }

    public static function save(array $values): void
    {
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $value = $values[$key];
            if ($key === 'github_token' && ($value === null || $value === '')) {
                continue;
            }
            if (in_array($key, ['include_forks', 'include_archived', 'findings_skip_forks', 'findings_skip_archived'], true)) {
                $value = !empty($value) ? '1' : '0';
            }
            self::put($key, is_string($value) ? $value : (string) $value);
        }
    }

    public static function requiredFiles(): array
    {
        $raw = (string) self::get('required_files', '');
        $files = [];
        foreach (preg_split('/[\r\n,]+/', $raw) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $files[] = $line;
        }
        return array_values(array_unique($files));
    }

    public static function staleDays(): int
    {
        $days = (int) self::get('stale_days', 180);
        return max(1, $days);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? '1' : '0');
        return $value === '1' || $value === 1 || $value === true;
    }

    public static function githubToken(): string
    {
        $fromDb = trim((string) self::get('github_token', ''));
        if ($fromDb !== '') {
            return $fromDb;
        }
        return trim((string) (rs_env('GITHUB_TOKEN', '') ?? ''));
    }

    private static function envFallback(string $key): ?string
    {
        return match ($key) {
            'github_token' => rs_env('GITHUB_TOKEN'),
            'github_owner' => rs_env('GITHUB_OWNER'),
            default => null,
        };
    }
}
