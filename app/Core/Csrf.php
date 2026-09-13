<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public function __construct(
        private readonly Session $session
    ) {
    }

    public function token(): string
    {
        $token = $this->session->get(
            self::SESSION_KEY
        );

        if (
            !is_string($token) ||
            $token === ''
        ) {
            $token = $this->generate();

            $this->session->set(
                self::SESSION_KEY,
                $token
            );
        }

        return $token;
    }

    public function validate(
        ?string $token
    ): bool {
        if (
            $token === null ||
            $token === ''
        ) {
            return false;
        }

        $sessionToken = $this->session->get(
            self::SESSION_KEY
        );

        if (!is_string($sessionToken)) {
            return false;
        }

        return hash_equals(
            $sessionToken,
            $token
        );
    }

    public function regenerate(): string
    {
        $token = $this->generate();

        $this->session->set(
            self::SESSION_KEY,
            $token
        );

        return $token;
    }

    private function generate(): string
    {
        return bin2hex(
            random_bytes(32)
        );
    }
}