<?php

declare(strict_types=1);

final class MetricsController
{
    public static function index(): void
    {
        view('metrics/index', [
            'title' => 'Métricas',
            'metrics' => StatsService::metrics(),
            'charts' => true,
        ]);
    }
}
