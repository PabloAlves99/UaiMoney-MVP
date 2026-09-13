<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function __construct(
        private readonly string $basePath = ''
    ) {
    }

    public function get(
        string $path,
        callable $handler
    ): void {
        $this->addRoute(
            'GET',
            $path,
            $handler
        );
    }

    public function post(
        string $path,
        callable $handler
    ): void {
        $this->addRoute(
            'POST',
            $path,
            $handler
        );
    }

    private function addRoute(
        string $method,
        string $path,
        callable $handler
    ): void {
        $path = $this->normalizePath($path);

        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(
        string $method,
        string $uri
    ): void {
        $path = parse_url(
            $uri,
            PHP_URL_PATH
        ) ?: '/';

        if (
            $this->basePath !== '' &&
            str_starts_with($path, $this->basePath)
        ) {
            $path = substr(
                $path,
                strlen($this->basePath)
            );
        }

        $path = $this->normalizePath($path);

        $handler = $this->routes[$method][$path]
            ?? null;

        if ($handler === null) {
            http_response_code(404);

            echo '404 - Página não encontrada';

            return;
        }

        call_user_func($handler);
    }

    private function normalizePath(
        string $path
    ): string {
        $path = '/' . trim($path, '/');

        return $path === '//'
            ? '/'
            : $path;
    }
}