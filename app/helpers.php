<?php

declare(strict_types=1);

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_path(): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $appUrl = (string) app_config('url', '');
    if ($appUrl !== '') {
        $urlPath = parse_url($appUrl, PHP_URL_PATH);
        if (is_string($urlPath) && $urlPath !== '' && $urlPath !== '/') {
            $cached = rtrim($urlPath, '/');
            return $cached;
        }
        $cached = '';
        return $cached;
    }

    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '/' || $script === '\\' || $script === '.') {
        $cached = '';
        return $cached;
    }
    $cached = rtrim($script, '/');
    return $cached;
}

function url(string $path = '/'): string
{
    $extraQuery = [];
    $hash = '';
    if (str_contains($path, '#')) {
        [$path, $hash] = explode('#', $path, 2);
        $hash = '#' . $hash;
    }
    if (str_contains($path, '?')) {
        [$path, $qs] = explode('?', $path, 2);
        parse_str($qs, $extraQuery);
    }

    $path = '/' . ltrim($path, '/');
    if ($path === '//') {
        $path = '/';
    }

    $base = base_path();

    if (str_starts_with($path, '/assets/') || preg_match('#^/[^/]+\.php$#', $path) === 1) {
        $suffix = $extraQuery ? ('?' . http_build_query($extraQuery)) : '';
        return $base . $path . $suffix . $hash;
    }

    $script = $base . '/index.php';
    $params = $extraQuery;
    if ($path !== '/') {
        $params = array_merge(['r' => $path], $params);
    }
    $suffix = $params ? ('?' . http_build_query($params)) : '';
    return $script . $suffix . $hash;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function view(string $template, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    $appName = (string) app_config('name', 'RepoScope');
    $appVersion = (string) app_config('version', '0.1.0');
    $templateFile = dirname(__DIR__) . '/app/Views/' . $template . '.php';
    if (!is_file($templateFile)) {
        throw new RuntimeException('View not found: ' . $template);
    }
    require dirname(__DIR__) . '/app/Views/layouts/main.php';
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

function require_csrf(): void
{
    if (!verify_csrf($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        echo 'Token CSRF inválido';
        exit;
    }
}

function session_release(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

function session_resume(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $flashes;
}

function current_route(): string
{
    $current = $_GET['r'] ?? '/';
    if ($current === '' || $current === false) {
        $current = '/';
    }
    $current = '/' . trim((string) $current, '/');
    return $current === '//' ? '/' : $current;
}

function nav_active(string $prefix, bool $exact = false): string
{
    $current = current_route();
    if ($exact) {
        return $current === $prefix ? 'is-active' : '';
    }
    if ($prefix === '/') {
        return $current === '/' ? 'is-active' : '';
    }
    return str_starts_with($current, $prefix) ? 'is-active' : '';
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function null_if_blank(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    $value = trim($value);
    return $value === '' ? null : $value;
}

function int_or_null(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    return (int) $value;
}

function format_number(int|float|null $n): string
{
    if ($n === null) {
        return '—';
    }
    return number_format((float) $n, 0, ',', '.');
}

function format_datetime(?string $value, string $format = 'd/m/Y H:i'): string
{
    if ($value === null || $value === '' || $value === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return '—';
    }
    return date($format, $ts);
}

function format_relative(?string $value): string
{
    if ($value === null || $value === '') {
        return 'nunca';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return '—';
    }
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'hace un momento';
    }
    if ($diff < 3600) {
        $m = (int) floor($diff / 60);
        return 'hace ' . $m . ' min';
    }
    if ($diff < 86400) {
        $h = (int) floor($diff / 3600);
        return 'hace ' . $h . ' h';
    }
    $d = (int) floor($diff / 86400);
    if ($d < 30) {
        return 'hace ' . $d . ' d';
    }
    if ($d < 365) {
        $mo = (int) floor($d / 30);
        return 'hace ' . $mo . ' mes' . ($mo === 1 ? '' : 'es');
    }
    $y = (int) floor($d / 365);
    return 'hace ' . $y . ' año' . ($y === 1 ? '' : 's');
}

function finding_types(): array
{
    return [
        'readme' => 'README',
        'license' => 'Licencia',
        'description' => 'Descripción',
        'file' => 'Archivo',
        'maintenance' => 'Mantenimiento',
    ];
}

function finding_label(string $type): string
{
    return finding_types()[$type] ?? $type;
}

function finding_badge_class(string $type): string
{
    return match ($type) {
        'readme' => 'badge-cyan',
        'license' => 'badge-violet',
        'description' => 'badge-blue',
        'file' => 'badge-amber',
        'maintenance' => 'badge-rose',
        default => 'badge-muted',
    };
}

function health_class(int $score): string
{
    if ($score >= 80) {
        return 'health-ok';
    }
    if ($score >= 50) {
        return 'health-warn';
    }
    return 'health-bad';
}

function parse_json_list(?string $value): array
{
    if ($value === null || $value === '') {
        return [];
    }
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function encode_json_list(array $value): string
{
    return json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
}

function shorten(string $text, int $len): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $len, '…');
    }
    if (strlen($text) <= $len) {
        return $text;
    }
    return substr($text, 0, max(0, $len - 1)) . '…';
}

function github_dt(?string $iso): ?string
{
    if ($iso === null || $iso === '') {
        return null;
    }
    $ts = strtotime($iso);
    if ($ts === false) {
        return null;
    }
    return date('Y-m-d H:i:s', $ts);
}

function icon(string $name, int $size = 18): string
{
    $icons = [
        'radar' => '<circle cx="12" cy="12" r="9"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/><path d="M12 12l5-2"/><circle cx="12" cy="12" r="2"/>',
        'home' => '<path d="M4 11 12 4l8 7v8a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/>',
        'repos' => '<path d="M7 4h10v16H7zM10 8h4M10 12h4M10 16h2"/><path d="M7 7H5v10h2"/>',
        'alert' => '<path d="M12 4 20 19H4z"/><path d="M12 10v4M12 16h.01"/>',
        'tag' => '<path d="M4 12 12 4h7v7l-8 8z"/><circle cx="16" cy="8" r="1.2"/>',
        'sync' => '<path d="M20 12a8 8 0 0 0-14-5.3V4M4 12a8 8 0 0 0 14 5.3V20"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3v2M12 19v2M5 5l1.5 1.5M17.5 17.5 19 19M3 12h2M19 12h2M5 19l1.5-1.5M17.5 6.5 19 5"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="m20 20-4-4"/>',
        'external' => '<path d="M14 5h5v5M19 5l-9 9M11 5H6v14h14v-5"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M5 5l1.4 1.4M17.6 17.6 19 19M3 12h2M19 12h2M5 19l1.4-1.4M17.6 6.4 19 5"/>',
        'moon' => '<path d="M18 13a7 7 0 1 1-7-9 7 7 0 0 0 7 9z"/>',
        'check' => '<path d="m5 12 5 5 9-9"/>',
        'chart' => '<path d="M4 19h16M7 16v-5M12 16V8M17 16v-8"/>',
    ];
    $path = $icons[$name] ?? $icons['radar'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}

function pager(string $path, array $query, int $page, int $pages): string
{
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav class="pager">';
    for ($i = max(1, $page - 3); $i <= min($pages, $page + 3); $i++) {
        $q = http_build_query(array_filter(array_merge($query, ['page' => $i]), static fn ($v) => $v !== '' && $v !== null));
        $href = url($path . ($q !== '' ? '?' . $q : ''));
        $cls = $i === $page ? ' is-active' : '';
        $html .= '<a class="pager-link' . $cls . '" href="' . e($href) . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}
