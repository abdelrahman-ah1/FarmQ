<?php

declare(strict_types=1);

namespace FarmQ\Services;

use FarmQ\Repositories\UserRepository;

final class AuthService
{
    private const SESSION_USER_KEY = 'user_id';

    public function __construct(private UserRepository $users = new UserRepository())
    {
    }

    public function check(): bool
    {
        return isset($_SESSION[self::SESSION_USER_KEY]);
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return $this->users->findById((int) $_SESSION[self::SESSION_USER_KEY]);
    }

    public function login(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $_SESSION[self::SESSION_USER_KEY] = (int) $user['id'];
        $_SESSION['locale'] = $user['locale'];

        return true;
    }

    /** @return array{ok: bool, errors?: array<string, string>} */
    public function register(array $input): array
    {
        $errors = $this->validateRegistration($input);
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $userId = $this->users->create([
            'email' => $input['email'],
            'password_hash' => password_hash($input['password'], PASSWORD_DEFAULT),
            'full_name' => $input['full_name'],
            'role' => $input['role'],
            'locale' => $input['locale'] ?? env('DEFAULT_LOCALE', 'ar'),
        ]);

        $_SESSION[self::SESSION_USER_KEY] = $userId;
        $_SESSION['locale'] = $input['locale'] ?? env('DEFAULT_LOCALE', 'ar');

        return ['ok' => true];
    }

    public function logout(): void
    {
        unset($_SESSION[self::SESSION_USER_KEY], $_SESSION['active_farm_id']);
    }

    public function requireAuth(): array
    {
        $user = $this->user();
        if ($user === null) {
            $lang = $_SESSION['locale'] ?? env('DEFAULT_LOCALE', 'ar');
            redirect('/login?lang=' . $lang);
        }

        return $user;
    }

    public function requireAdmin(): array
    {
        $user = $this->requireAuth();
        if (($user['role'] ?? '') !== 'admin') {
            redirect('/dashboard?lang=' . ($user['locale'] ?? 'ar'));
        }

        return $user;
    }

    public function isAgronomist(array $user): bool
    {
        return in_array($user['role'] ?? '', ['agronomist', 'admin'], true);
    }

    /** @return array<string, string> */
    private function validateRegistration(array $input): array
    {
        $errors = [];

        $fullName = trim($input['full_name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $confirm = $input['password_confirm'] ?? '';
        $role = $input['role'] ?? 'operator';

        if ($fullName === '') {
            $errors['full_name'] = 'required';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'invalid';
        } elseif ($this->users->emailExists($email)) {
            $errors['email'] = 'taken';
        }

        if (strlen($password) < 8) {
            $errors['password'] = 'short';
        } elseif ($password !== $confirm) {
            $errors['password_confirm'] = 'mismatch';
        }

        if (!in_array($role, ['operator', 'agronomist', 'owner'], true)) {
            $errors['role'] = 'invalid';
        }

        return $errors;
    }
}
