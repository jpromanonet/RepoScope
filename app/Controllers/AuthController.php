<?php

declare(strict_types=1);

final class AuthController
{
    public static function showLogin(): void
    {
        if (Auth::check()) {
            redirect('/');
        }
        view('auth/login', [
            'title' => 'Iniciar sesión',
        ], 'layouts/auth');
    }

    public static function login(): void
    {
        require_csrf();
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        if ($email === '' || $password === '') {
            flash('error', 'Ingresá mail y contraseña.');
            redirect('/login');
        }
        if (!Auth::attempt($email, $password)) {
            flash(
                'error',
                Auth::isLoginLocked()
                    ? 'Demasiados intentos. Esperá unos minutos.'
                    : 'Mail o contraseña incorrectos.'
            );
            redirect('/login');
        }
        redirect('/');
    }

    public static function logout(): void
    {
        require_csrf();
        Auth::logout();
        redirect('/login');
    }
}
