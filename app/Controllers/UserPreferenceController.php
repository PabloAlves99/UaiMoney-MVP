<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\AuthService;
use App\Services\UserPreferenceService;
use DomainException;

final class UserPreferenceController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly UserPreferenceService $preferenceService,
        private readonly Csrf $csrf,
        private readonly string $basePath
    ) {
    }

    public function updateTheme(): void
    {
        $this->validateCsrf();

        $usuario = $this->authService
            ->currentUser();

        if ($usuario === null) {

            http_response_code(401);

            echo 'Não autenticado';

            return;
        }

        $tema = $_POST['tema']
            ?? '';

        try {

            $this->preferenceService
                ->updateTheme(
                    (int) $usuario['id'],
                    $tema
                );

        } catch (DomainException $e) {

            http_response_code(422);

            echo $e->getMessage();

            return;
        }

        header(
            'Content-Type: application/json; charset=UTF-8'
        );

        echo json_encode([
            'success' => true,
            'tema' => $tema
        ]);
    }

    private function validateCsrf(): void
    {
        $token = $_POST['_token']
            ?? null;

        if ($this->csrf->validate($token)) {
            return;
        }

        http_response_code(419);

        echo 'Token CSRF inválido.';

        exit;
    }
}