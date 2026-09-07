<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

return [
    'host' => rs_env('DB_HOST', '127.0.0.1') ?? '127.0.0.1',
    'port' => (int) (rs_env('DB_PORT', '3306') ?? '3306'),
    'name' => rs_env('DB_NAME', 'reposcope') ?? 'reposcope',
    'user' => rs_env('DB_USER', 'root') ?? 'root',
    'pass' => rs_env('DB_PASS', '') ?? '',
    'charset' => 'utf8mb4',
];
