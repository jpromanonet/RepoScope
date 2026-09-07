<?php

declare(strict_types=1);

final class CategoryController
{
    public static function index(): void
    {
        view('categories/index', [
            'title' => 'Categorías',
            'categories' => CategoryService::counts(),
        ]);
    }

    public static function store(): void
    {
        require_csrf();
        try {
            CategoryService::create(
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['color'] ?? '#3DDCFF')
            );
            flash('success', 'Categoría creada.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/categorias');
    }

    public static function update(string $id): void
    {
        require_csrf();
        try {
            CategoryService::update(
                (int) $id,
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['color'] ?? '#3DDCFF')
            );
            flash('success', 'Categoría actualizada.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/categorias');
    }

    public static function destroy(string $id): void
    {
        require_csrf();
        try {
            CategoryService::destroy((int) $id);
            flash('success', 'Categoría eliminada.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/categorias');
    }
}
