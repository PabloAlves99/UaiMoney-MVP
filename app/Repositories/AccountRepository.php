<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AccountRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }


    private function balances(): string
    {
        return "SELECT c.*, CASE WHEN c.saldo_inicial_em <= :hoje THEN c.saldo_inicial_centavos ELSE 0 END
            + COALESCE((SELECT SUM(m.valor_centavos) FROM movimentos_caixa m
                WHERE m.usuario_id=c.usuario_id AND m.conta_id=c.id
                AND m.data>=c.saldo_inicial_em AND m.data<=:hoje),0) AS saldo_atual_centavos FROM contas c";
    }

    public function listActive(int $usuarioId): array
    {
        $stmt = $this->pdo->prepare($this->balances().' WHERE c.usuario_id=:usuario AND c.ativo=1 ORDER BY c.nome');
        $stmt->execute([':usuario'=>$usuarioId, ':hoje'=>date('Y-m-d')]);
        return $stmt->fetchAll();
    }

    public function listInactive(int $usuarioId): array
    {
        $stmt=$this->pdo->prepare('SELECT id,nome FROM contas WHERE usuario_id=? AND ativo=0 ORDER BY nome');
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public function reactivate(int $usuarioId,int $contaId): void
    {
        $stmt=$this->pdo->prepare('UPDATE contas SET ativo=1,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?');
        $stmt->execute([$usuarioId,$contaId]);
    }

    public function findById(int $contaId, int $usuarioId): ?array
    {
        $stmt = $this->pdo->prepare($this->balances().' WHERE c.usuario_id=:usuario AND c.id=:id');
        $stmt->execute([':usuario'=>$usuarioId, ':id'=>$contaId, ':hoje'=>date('Y-m-d')]);
        return $stmt->fetch() ?: null;
    }

    public function existsByName(
        int $usuarioId,
        string $nome
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM contas
            WHERE usuario_id = :usuario_id
              AND nome = :nome
            LIMIT 1
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome
        ]);

        return $stmt->fetchColumn()
            !== false;
    }


    public function create(
        int $usuarioId,
        string $nome,
        string $tipo,
        ?string $instituicao,
        int $saldoInicialCentavos,
        string $saldoInicialEm
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO contas (
                usuario_id,
                nome,
                tipo,
                instituicao,
                saldo_inicial_centavos,
                saldo_inicial_em
            )
            VALUES (
                :usuario_id,
                :nome,
                :tipo,
                :instituicao,
                :saldo_inicial_centavos,
                :saldo_inicial_em
            )
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':instituicao' => $instituicao,
            ':saldo_inicial_centavos'
            => $saldoInicialCentavos,
            ':saldo_inicial_em'
            => $saldoInicialEm
        ]);

        return (int) 
            $this->pdo->lastInsertId();
    }


    public function deactivate(
        int $contaId,
        int $usuarioId
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE contas
            SET
                ativo = 0,
                atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id
              AND usuario_id = :usuario_id
        ");

        $stmt->execute([
            ':id' => $contaId,
            ':usuario_id' => $usuarioId
        ]);
    }

    public function existsByNameExceptId(
        int $usuarioId,
        string $nome,
        int $contaId
    ): bool {
        $stmt = $this->pdo->prepare("
        SELECT 1
        FROM contas
        WHERE usuario_id = :usuario_id
          AND nome = :nome
          AND id <> :id
        LIMIT 1
    ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome,
            ':id' => $contaId
        ]);

        return $stmt->fetchColumn()
            !== false;
    }


    public function updateDetails(
        int $contaId,
        int $usuarioId,
        string $nome,
        string $tipo,
        ?string $instituicao
    ): bool {
        $stmt = $this->pdo->prepare("
        UPDATE contas
        SET
            nome = :nome,
            tipo = :tipo,
            instituicao = :instituicao,
            atualizado_em = CURRENT_TIMESTAMP
        WHERE id = :id
          AND usuario_id = :usuario_id
          AND ativo = 1
    ");

        $stmt->execute([
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':instituicao' => $instituicao,
            ':id' => $contaId,
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->rowCount() === 1;
    }
}
