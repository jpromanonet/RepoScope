<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'name' => rs_env('APP_NAME', 'RepoScope') ?? 'RepoScope',
    'env' => rs_env('APP_ENV', 'local') ?? 'local',
    'debug' => filter_var(rs_env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'url' => rs_env('APP_URL', '') ?? '',
    'timezone' => 'America/Argentina/Buenos_Aires',
    'session_name' => 'reposcope_session',
    'version' => '0.1.0',
];
