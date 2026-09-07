<?php

declare(strict_types=1);

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        if (!self::allowLoginAttempt()) {
            return false;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT id, name, email, password_hash, is_active FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !(int) $user['is_active'] || !password_verify($password, (string) $user['password_hash'])) {
            self::recordLoginFailure();
            return false;
        }

        self::clearLoginFailures();
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
        ];
        Database::pdo()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
            ->execute([(int) $user['id']]);
        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'secure' => (bool) $params['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function findCurrent(): ?array
    {
        $id = self::id();
        if ($id < 1) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, email, last_login_at FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updateProfile(int $id, string $name, string $email, ?string $newPassword, ?string $currentPassword): void
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $name = trim($name);
        $email = strtolower(trim($email));
        if ($name === '' || $email === '' || !preg_match('/^[^@\s]+@[^@\s]+$/', $email)) {
            throw new RuntimeException('Nombre y mail válidos son obligatorios.');
        }

        $dup = Database::pdo()->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $dup->execute([$email, $id]);
        if ($dup->fetch()) {
            throw new RuntimeException('Ese mail ya está en uso.');
        }

        if ($newPassword !== null && $newPassword !== '') {
            if ($currentPassword === null || $currentPassword === ''
                || !password_verify($currentPassword, (string) $user['password_hash'])) {
                throw new RuntimeException('La contraseña actual no es correcta.');
            }
            if (strlen($newPassword) < 8) {
                throw new RuntimeException('La nueva contraseña tiene que tener al menos 8 caracteres.');
            }
            Database::pdo()->prepare(
                'UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?'
            )->execute([$name, $email, password_hash($newPassword, PASSWORD_DEFAULT), $id]);
        } else {
            Database::pdo()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')
                ->execute([$name, $email, $id]);
        }

        if (isset($_SESSION['user'])) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
        }
    }

    public static function isLoginLocked(): bool
    {
        $fails = $_SESSION['_login_fails'] ?? ['count' => 0, 'until' => 0];
        return ((int) ($fails['until'] ?? 0)) > time();
    }

    public static function seedDefaultUser(): void
    {
        $count = (int) Database::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count > 0) {
            return;
        }
        Database::pdo()->prepare(
            'INSERT INTO users (name, email, password_hash, is_active) VALUES (?, ?, ?, 1)'
        )->execute(['Admin', 'admin@local', password_hash('reposcope', PASSWORD_DEFAULT)]);
    }

    private static function allowLoginAttempt(): bool
    {
        $fails = $_SESSION['_login_fails'] ?? ['count' => 0, 'until' => 0];
        return ((int) ($fails['until'] ?? 0)) <= time();
    }

    private static function recordLoginFailure(): void
    {
        $fails = $_SESSION['_login_fails'] ?? ['count' => 0, 'until' => 0];
        $fails['count'] = (int) $fails['count'] + 1;
        if ($fails['count'] >= 8) {
            $fails['until'] = time() + 300;
            $fails['count'] = 0;
        }
        $_SESSION['_login_fails'] = $fails;
    }

    private static function clearLoginFailures(): void
    {
        unset($_SESSION['_login_fails']);
    }
}
