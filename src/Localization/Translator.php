<?php

declare(strict_types=1);

namespace FarmQ\Localization;

final class Translator
{
    private string $locale;
    /** @var array<string, array<string, mixed>> */
    private array $strings = [];

    public function __construct()
    {
        $supported = explode(',', env('SUPPORTED_LOCALES', 'en,ar'));
        $requested = $_GET['lang'] ?? $_SESSION['locale'] ?? $this->detectBrowserLocale($supported) ?? env('DEFAULT_LOCALE', 'ar');
        $this->locale = in_array($requested, $supported, true) ? $requested : 'ar';
        $_SESSION['locale'] = $this->locale;
        $this->load($this->locale);
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function isRtl(): bool
    {
        return $this->locale === 'ar';
    }

    public function direction(): string
    {
        return $this->isRtl() ? 'rtl' : 'ltr';
    }

    /** @param array<string, string|int|float> $replace */
    public function get(string $key, array $replace = []): string
    {
        $value = $this->resolve($key);
        if (!is_string($value)) {
            return $key;
        }

        foreach ($replace as $search => $replacement) {
            $value = str_replace(':' . $search, (string) $replacement, $value);
        }

        return $value;
    }

    /** @return array<int, array<string, string>>|array<int, mixed> */
    public function array(string $key): array
    {
        $value = $this->resolve($key);
        return is_array($value) ? $value : [];
    }

    private function resolve(string $key): mixed
    {
        if (isset($this->strings[$key])) {
            return $this->strings[$key];
        }

        $current = $this->strings;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    private function load(string $locale): void
    {
        $file = base_path('resources/locales/' . $locale . '.php');
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $this->strings = $loaded;
            }
        }
    }

    /** @param array<int, string> $supported */
    private function detectBrowserLocale(array $supported): ?string
    {
        if (isset($_SESSION['locale']) || isset($_GET['lang'])) {
            return null;
        }

        $header = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($header === '') {
            return null;
        }

        foreach (explode(',', $header) as $part) {
            $code = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
            if (in_array($code, $supported, true)) {
                return $code;
            }
        }

        return null;
    }
}
