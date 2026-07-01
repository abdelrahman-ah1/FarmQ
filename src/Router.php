<?php

declare(strict_types=1);

namespace FarmQ;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$this->normalize($path)] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$this->normalize($path)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = $this->normalize($uri);
        $handler = $this->match($method, $path);

        if ($handler === null) {
            http_response_code(404);
            echo view('errors/404', ['path' => $path]);
            return;
        }

        echo $handler();
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function match(string $method, string $path): ?callable
    {
        if (isset($this->routes[$method][$path])) {
            return $this->routes[$method][$path];
        }

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $route);
            if ($pattern !== null && preg_match('#^' . $pattern . '$#', $path, $matches)) {
                array_shift($matches);
                return static fn () => $handler(...$matches);
            }
        }

        return null;
    }
}
