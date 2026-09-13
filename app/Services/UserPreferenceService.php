<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use DomainException;

final class UserPreferenceService
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    public function updateTheme(
        int $usuarioId,
        string $tema
    ): void {

        if (
            !in_array(
                $tema,
                ['light', 'dark'],
                true
            )
        ) {
            throw new DomainException(
                'Tema inválido.'
            );
        }

        $this->userRepository->updateTheme(
            $usuarioId,
            $tema
        );
    }
}