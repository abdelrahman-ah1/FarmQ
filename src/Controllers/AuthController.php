<?php

declare(strict_types=1);

namespace FarmQ\Controllers;

use FarmQ\Localization\Translator;
use FarmQ\Services\AuthService;

final class AuthController
{
    public function __construct(
        private Translator $t,
        private AuthService $auth = new AuthService()
    ) {
    }

    public function showLogin(): string
    {
        if ($this->auth->check()) {
            redirect('/dashboard?lang=' . $this->t->locale());
        }

        return view('auth/login', [
            't' => $this->t,
            'pageTitle' => $this->t->get('auth.login_title'),
            'errors' => flash('errors') ?? [],
            'old' => flash('old') ?? [],
        ]);
    }

    public function login(): never
    {
        verify_csrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$this->auth->login($email, $password)) {
            flash('errors', ['credentials' => 'invalid']);
            flash('old', ['email' => $email]);
            redirect('/login?lang=' . $this->t->locale());
        }

        redirect('/dashboard?lang=' . $this->t->locale());
    }

    public function showSignup(): string
    {
        if ($this->auth->check()) {
            redirect('/dashboard?lang=' . $this->t->locale());
        }

        return view('auth/signup', [
            't' => $this->t,
            'pageTitle' => $this->t->get('auth.signup_title'),
            'errors' => flash('errors') ?? [],
            'old' => flash('old') ?? [],
        ]);
    }

    public function signup(): never
    {
        verify_csrf();

        $input = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'role' => $_POST['role'] ?? 'operator',
            'locale' => $this->t->locale(),
        ];

        $result = $this->auth->register($input);
        if (!$result['ok']) {
            flash('errors', $result['errors'] ?? []);
            flash('old', $input);
            redirect('/signup?lang=' . $this->t->locale());
        }

        redirect('/farms/create?lang=' . $this->t->locale());
    }

    public function logout(): never
    {
        $this->auth->logout();
        redirect('/?lang=' . $this->t->locale());
    }
}
