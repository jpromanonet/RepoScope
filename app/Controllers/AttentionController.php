<?php

declare(strict_types=1);

final class AttentionController
{
    public static function index(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'type' => (string) ($_GET['type'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));

        view('attention/index', [
            'title' => 'Atención',
            'result' => FindingService::attention($filters, $page),
            'filters' => $filters,
            'counts' => FindingService::openByType(),
        ]);
    }
}
