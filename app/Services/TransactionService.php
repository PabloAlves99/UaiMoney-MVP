<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TransactionRepository;

final class TransactionService
{
    public function __construct(
        private readonly TransactionRepository $transactionRepository
    ) {}


    public function list(
        int $usuarioId
    ): array {
        return $this
            ->transactionRepository
            ->listByUser(
                $usuarioId
            );
    }
}
