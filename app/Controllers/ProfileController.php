<?php

declare(strict_types=1);

final class ProfileController
{
    public static function index(): void
    {
        $user = Auth::findCurrent();
        if (!$user) {
            redirect('/login');
        }
        view('profile/index', [
            'title' => 'Perfil',
            'user' => $user,
        ]);
    }

    public static function save(): void
    {
        require_csrf();
        try {
            $new = (string) ($_POST['new_password'] ?? '');
            $current = (string) ($_POST['current_password'] ?? '');
            Auth::updateProfile(
                Auth::id(),
                (string) ($_POST['name'] ?? ''),
                (string) ($_POST['email'] ?? ''),
                $new !== '' ? $new : null,
                $current !== '' ? $current : null
            );
            flash('success', 'Perfil actualizado.');
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/perfil');
    }
}
