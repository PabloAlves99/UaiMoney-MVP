<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public function __construct(
        private readonly array $config
    ) {
    }


    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $directory = $this->config['save_path'] ?? null;
        if ($directory !== null) {
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new \RuntimeException('Não foi possível criar o armazenamento de sessões.');
            }
            session_save_path($directory);
        }

        ini_set(
            'session.use_strict_mode',
            '1'
        );

        session_name(
            $this->config['name']
        );

        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['path'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['http_only'],
            'samesite' => $this->config['same_site']
        ]);

        session_start();
    }


    public function set(
        string $key,
        mixed $value
    ): void {
        $_SESSION[$key] = $value;
    }


    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $_SESSION[$key] ?? $default;
    }


    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }


    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }


    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {

            session_regenerate_id(true);

        }
    }


    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }

        session_destroy();
    }
}
