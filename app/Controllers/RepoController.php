<?php

declare(strict_types=1);

final class RepoController
{
    public static function index(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'language' => (string) ($_GET['language'] ?? ''),
            'category_id' => (string) ($_GET['category_id'] ?? ''),
            'visibility' => (string) ($_GET['visibility'] ?? ''),
            'attention' => (string) ($_GET['attention'] ?? ''),
            'fork' => (string) ($_GET['fork'] ?? ''),
            'archived' => (string) ($_GET['archived'] ?? ''),
            'finding' => (string) ($_GET['finding'] ?? ''),
        ];
        $sort = (string) ($_GET['sort'] ?? 'full_name');
        $dir = (string) ($_GET['dir'] ?? 'asc');
        $page = max(1, (int) ($_GET['page'] ?? 1));

        view('repos/index', [
            'title' => 'Repositorios',
            'result' => RepoService::search($filters, $sort, $dir, $page),
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
            'languages' => RepoService::languages(),
            'categories' => CategoryService::all(),
        ]);
    }

    public static function show(string $id): void
    {
        $repo = RepoService::find((int) $id);
        if (!$repo) {
            flash('error', 'Repositorio no encontrado.');
            redirect('/repositorios');
        }

        view('repos/show', [
            'title' => $repo['full_name'],
            'repo' => $repo,
            'findings' => FindingService::openForRepo((int) $id),
            'categories' => CategoryService::all(),
            'topics' => parse_json_list($repo['topics'] ?? '[]'),
            'rootFiles' => parse_json_list($repo['root_files'] ?? '[]'),
            'ignoredFindings' => parse_json_list($repo['ignored_findings'] ?? '[]'),
        ]);
    }

    public static function update(string $id): void
    {
        require_csrf();
        try {
            $ignored = $_POST['ignored_findings'] ?? [];
            if (!is_array($ignored)) {
                $ignored = [];
            }
            $allowed = array_keys(finding_types());
            $ignored = array_values(array_intersect($ignored, $allowed));
            RepoService::updateLocal((int) $id, [
                'category_id' => int_or_null($_POST['category_id'] ?? null),
                'notes' => null_if_blank($_POST['notes'] ?? null),
                'is_ignored' => !empty($_POST['is_ignored']),
                'ignored_findings' => $ignored,
            ]);
            flash('success', 'Clasificación guardada.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/repositorios/' . (int) $id);
    }

    public static function dismiss(string $id): void
    {
        require_csrf();
        $type = (string) ($_POST['type'] ?? '');
        if (!isset(finding_types()[$type])) {
            flash('error', 'Tipo de hallazgo inválido.');
            redirect('/repositorios/' . (int) $id);
        }
        try {
            RepoService::dismissFinding((int) $id, $type);
            flash('success', 'Hallazgo omitido para este repositorio.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        $back = (string) ($_POST['back'] ?? '');
        redirect($back === 'atencion' ? '/atencion' : '/repositorios/' . (int) $id);
    }
}
