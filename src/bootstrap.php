<?php

declare(strict_types=1);

use FarmQ\Localization\Translator;

spl_autoload_register(static function (string $class): void {
    $prefix = 'FarmQ\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $path = __DIR__ . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

function env(string $key, ?string $default = null): ?string
{
    static $loaded = false;
    if (!$loaded) {
        $envFile = dirname(__DIR__) . '/.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $_ENV[trim($k)] = trim($v);
            }
        }
        $loaded = true;
    }

    return $_ENV[$key] ?? getenv($key) ?: $default;
}

function base_path(string $path = ''): string
{
    return dirname(__DIR__) . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
}

function view(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require base_path('views/' . $name . '.php');
    return (string) ob_get_clean();
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function asset(string $path): string
{
    $base = rtrim(env('APP_URL', ''), '/');
    return $base . '/assets/' . ltrim($path, '/');
}

function route(string $path, ?string $locale = null): string
{
    $base = rtrim(env('APP_URL', ''), '/');
    $locale = $locale ?? ($_SESSION['locale'] ?? env('DEFAULT_LOCALE', 'ar'));
    return $base . $path . (str_contains($path, '?') ? '&' : '?') . 'lang=' . $locale;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Invalid CSRF token');
    }
}

/** @param array<string, mixed>|string|null $value */
function flash(string $key, mixed $value = null): mixed
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $stored = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);

    return $stored;
}

function old(string $key, string $default = '', array $old = []): string
{
    return htmlspecialchars((string) ($old[$key] ?? $default));
}

/** @param array<string, mixed> $extra */
function app_view_data(Translator $t, array $user, array $extra = []): array
{
    $farmContext = new \FarmQ\Services\FarmContext();
    $farms = $farmContext->listForUser($user);

    return array_merge([
        't' => $t,
        'user' => $user,
        'farms' => $farms,
        'activeFarm' => $farmContext->active($user),
        'currentPath' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
    ], $extra);
}
