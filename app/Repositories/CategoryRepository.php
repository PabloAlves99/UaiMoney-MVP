<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CategoryRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {}


    public function listGroups(
        int $usuarioId
    ): array {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                tipo,
                ativo,
                criado_em,
                atualizado_em
            FROM grupos
            WHERE usuario_id = :usuario_id
              AND ativo = 1
            ORDER BY
                CASE tipo
                    WHEN 'receita' THEN 1
                    WHEN 'despesa' THEN 2
                END,
                nome
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }


    public function listSubgroups(
        int $usuarioId
    ): array {
        $stmt = $this->pdo->prepare("
            SELECT
                s.id,
                s.grupo_id,
                s.nome,
                s.descricao,
                s.ativo,
                s.criado_em,
                s.atualizado_em
            FROM subgrupos s

            INNER JOIN grupos g
                ON g.id = s.grupo_id

            WHERE g.usuario_id = :usuario_id
              AND g.ativo = 1
              AND s.ativo = 1

            ORDER BY
                s.nome
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        return $stmt->fetchAll();
    }


    public function findGroupById(
        int $grupoId,
        int $usuarioId
    ): ?array {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                usuario_id,
                nome,
                tipo,
                ativo
            FROM grupos
            WHERE id = :id
              AND usuario_id = :usuario_id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $grupoId,
            ':usuario_id' => $usuarioId
        ]);

        $grupo = $stmt->fetch();

        return $grupo ?: null;
    }


    public function groupExists(
        int $usuarioId,
        string $nome,
        string $tipo
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM grupos
            WHERE usuario_id = :usuario_id
              AND tipo = :tipo
              AND nome = :nome
            LIMIT 1
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':tipo' => $tipo,
            ':nome' => $nome
        ]);

        return $stmt->fetchColumn() !== false;
    }


    public function subgroupExists(
        int $grupoId,
        string $nome
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM subgrupos
            WHERE grupo_id = :grupo_id
              AND nome = :nome
            LIMIT 1
        ");

        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':nome' => $nome
        ]);

        return $stmt->fetchColumn() !== false;
    }


    public function createGroup(
        int $usuarioId,
        string $nome,
        string $tipo
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO grupos (
                usuario_id,
                nome,
                tipo
            )
            VALUES (
                :usuario_id,
                :nome,
                :tipo
            )
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome,
            ':tipo' => $tipo
        ]);

        return (int) $this->pdo->lastInsertId();
    }


    public function createSubgroup(
        int $grupoId,
        string $nome,
        ?string $descricao
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO subgrupos (
                grupo_id,
                nome,
                descricao
            )
            VALUES (
                :grupo_id,
                :nome,
                :descricao
            )
        ");

        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':nome' => $nome,
            ':descricao' => $descricao
        ]);

        return (int) $this->pdo->lastInsertId();
    }


    public function deactivateGroup(
        int $grupoId,
        int $usuarioId
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE grupos
            SET
                ativo = 0,
                atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id
              AND usuario_id = :usuario_id
        ");

        $stmt->execute([
            ':id' => $grupoId,
            ':usuario_id' => $usuarioId
        ]);
    }


    public function deactivateSubgroup(
        int $subgrupoId,
        int $usuarioId
    ): void {
        $stmt = $this->pdo->prepare("
            UPDATE subgrupos
            SET
                ativo = 0,
                atualizado_em = CURRENT_TIMESTAMP
            WHERE id = :id
              AND grupo_id IN (
                  SELECT id
                  FROM grupos
                  WHERE usuario_id = :usuario_id
              )
        ");

        $stmt->execute([
            ':id' => $subgrupoId,
            ':usuario_id' => $usuarioId
        ]);
    }

    public function findSubgroupById(
        int $subgrupoId,
        int $usuarioId
    ): ?array {
        $stmt = $this->pdo->prepare("
        SELECT
            s.id,
            s.grupo_id,
            s.nome,
            s.descricao,
            s.ativo,

            g.nome AS grupo_nome,
            g.tipo,
            g.ativo AS grupo_ativo

        FROM subgrupos s

        INNER JOIN grupos g
            ON g.id = s.grupo_id

        WHERE s.id = :id
          AND g.usuario_id = :usuario_id

        LIMIT 1
    ");

        $stmt->execute([
            ':id' => $subgrupoId,
            ':usuario_id' => $usuarioId
        ]);

        $subgrupo = $stmt->fetch();

        return $subgrupo ?: null;
    }
}
