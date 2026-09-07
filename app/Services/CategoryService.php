<?php

declare(strict_types=1);

final class CategoryService
{
    /** @var list<array{slug:string,name:string,color:string,sort:int}> */
    private const DEFAULTS = [
        ['slug' => 'personal', 'name' => 'Personal', 'color' => '#3DDCFF', 'sort' => 1],
        ['slug' => 'trabajo', 'name' => 'Trabajo', 'color' => '#7C9CFF', 'sort' => 2],
        ['slug' => 'cliente', 'name' => 'Cliente', 'color' => '#F0B429', 'sort' => 3],
        ['slug' => 'experimento', 'name' => 'Experimento', 'color' => '#C084FC', 'sort' => 4],
        ['slug' => 'aprendizaje', 'name' => 'Aprendizaje', 'color' => '#3DDC97', 'sort' => 5],
        ['slug' => 'fork', 'name' => 'Fork', 'color' => '#8B9BB4', 'sort' => 6],
        ['slug' => 'archivo', 'name' => 'Archivo', 'color' => '#64748B', 'sort' => 7],
    ];

    public static function seedDefaults(): void
    {
        $pdo = Database::pdo();
        $count = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        if ($count > 0) {
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO categories (slug, name, color, sort_order, is_system) VALUES (?, ?, ?, ?, 1)'
        );
        foreach (self::DEFAULTS as $row) {
            $stmt->execute([$row['slug'], $row['name'], $row['color'], $row['sort']]);
        }
    }

    public static function all(): array
    {
        return Database::pdo()
            ->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')
            ->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM categories WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $color): int
    {
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('El nombre de la categoría es obligatorio.');
        }
        $slug = self::slugify($name);
        $color = self::normalizeColor($color);
        $pdo = Database::pdo();
        $next = (int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories')->fetchColumn();
        $pdo->prepare(
            'INSERT INTO categories (slug, name, color, sort_order, is_system) VALUES (?, ?, ?, ?, 0)'
        )->execute([$slug, $name, $color, $next]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $name, string $color): void
    {
        $cat = self::find($id);
        if (!$cat) {
            throw new RuntimeException('Categoría no encontrada.');
        }
        $name = trim($name);
        if ($name === '') {
            throw new RuntimeException('El nombre de la categoría es obligatorio.');
        }
        Database::pdo()->prepare(
            'UPDATE categories SET name = ?, color = ? WHERE id = ?'
        )->execute([$name, self::normalizeColor($color), $id]);
    }

    public static function destroy(int $id): void
    {
        $cat = self::find($id);
        if (!$cat) {
            return;
        }
        if ((int) $cat['is_system'] === 1) {
            throw new RuntimeException('No se puede eliminar una categoría de sistema.');
        }
        Database::pdo()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }

    public static function counts(): array
    {
        $sql = 'SELECT c.id, c.slug, c.name, c.color, c.is_system, COUNT(r.id) AS total
                FROM categories c
                LEFT JOIN repositories r ON r.category_id = c.id
                GROUP BY c.id, c.slug, c.name, c.color, c.is_system
                ORDER BY c.sort_order ASC';
        return Database::pdo()->query($sql)->fetchAll();
    }

    public static function suggestFor(array $repo): ?int
    {
        if (!empty($repo['is_archived'])) {
            $cat = self::findBySlug('archivo');
            return $cat ? (int) $cat['id'] : null;
        }
        if (!empty($repo['is_fork'])) {
            $cat = self::findBySlug('fork');
            return $cat ? (int) $cat['id'] : null;
        }
        return null;
    }

    private static function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
            if (is_string($converted) && $converted !== '') {
                $slug = $converted;
            }
        }
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'categoria';
        }
        $base = $slug;
        $i = 2;
        while (self::findBySlug($slug)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private static function normalizeColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return strtoupper($color);
        }
        return '#3DDCFF';
    }
}
