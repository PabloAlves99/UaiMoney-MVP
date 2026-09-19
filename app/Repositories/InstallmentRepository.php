<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class InstallmentRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }


    public function create(
        int $usuarioId,
        string $descricao,
        int $valorTotalCentavos,
        int $totalParcelas
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO parcelamentos (
                usuario_id,
                descricao,
                valor_total_centavos,
                total_parcelas
            )
            VALUES (
                :usuario_id,
                :descricao,
                :valor_total_centavos,
                :total_parcelas
            )
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':descricao' => $descricao,
            ':valor_total_centavos' => $valorTotalCentavos,
            ':total_parcelas' => $totalParcelas
        ]);

        return (int) 
            $this->pdo->lastInsertId();
    }
}