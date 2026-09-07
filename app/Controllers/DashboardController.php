<?php

declare(strict_types=1);

final class DashboardController
{
    public static function index(): void
    {
        view('dashboard/index', [
            'title' => 'Panel',
            'stats' => StatsService::dashboard(),
            'hasToken' => SettingsService::githubToken() !== '',
        ]);
    }
}
