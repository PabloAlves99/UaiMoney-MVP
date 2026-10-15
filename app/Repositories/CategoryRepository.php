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
                classificacao,
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
                classificacao,
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
        string $tipo,
        int $exceptId = 0
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM grupos
            WHERE usuario_id = :usuario_id
              AND tipo = :tipo
              AND nome = :nome
              AND id <> :except_id
            LIMIT 1
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':tipo' => $tipo,
            ':nome' => $nome,
            ':except_id' => $exceptId
        ]);

        return $stmt->fetchColumn() !== false;
    }


    public function subgroupExists(
        int $grupoId,
        string $nome,
        int $exceptId = 0
    ): bool {
        $stmt = $this->pdo->prepare("
            SELECT 1
            FROM subgrupos
            WHERE grupo_id = :grupo_id
              AND nome = :nome
              AND id <> :except_id
            LIMIT 1
        ");

        $stmt->execute([
            ':grupo_id' => $grupoId,
            ':nome' => $nome,
            ':except_id' => $exceptId
        ]);

        return $stmt->fetchColumn() !== false;
    }


    public function createGroup(
        int $usuarioId,
        string $nome,
        string $tipo,
        string $classificacao = 'variavel'
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO grupos (
                usuario_id,
                nome,
                tipo,
                classificacao
            )
            VALUES (
                :usuario_id,
                :nome,
                :tipo,
                :classificacao
            )
        ");

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nome' => $nome,
            ':tipo' => $tipo,
            ':classificacao' => $classificacao
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateClassification(int $grupoId, int $usuarioId, string $classificacao): void
    {
        $stmt = $this->pdo->prepare('UPDATE grupos SET classificacao=:classificacao, atualizado_em=CURRENT_TIMESTAMP WHERE id=:id AND usuario_id=:usuario_id AND tipo=\'despesa\'');
        $stmt->execute([':classificacao' => $classificacao, ':id' => $grupoId, ':usuario_id' => $usuarioId]);
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

    public function managementGroups(int $user): array
    {
        $stmt = $this->pdo->prepare("SELECT g.*,
            (SELECT COUNT(*) FROM subgrupos s WHERE s.grupo_id=g.id AND s.ativo=1) AS subcategorias_ativas,
            (SELECT COUNT(*) FROM subgrupos s WHERE s.grupo_id=g.id) AS subcategorias_total
            FROM grupos g WHERE g.usuario_id=? ORDER BY g.nome COLLATE NOCASE");
        $stmt->execute([$user]);
        return $stmt->fetchAll();
    }

    public function managementSubgroups(int $user): array
    {
        $stmt = $this->pdo->prepare('SELECT s.*,g.nome AS grupo_nome,g.tipo,g.ativo AS grupo_ativo FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=? ORDER BY g.nome COLLATE NOCASE,s.nome COLLATE NOCASE');
        $stmt->execute([$user]);
        return $stmt->fetchAll();
    }

    public function groupHasHistory(int $user, int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT EXISTS(SELECT 1 FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id WHERE s.grupo_id=? AND t.usuario_id=?) OR EXISTS(SELECT 1 FROM recorrencias r JOIN subgrupos s ON s.id=r.subgrupo_id WHERE s.grupo_id=? AND r.usuario_id=?) OR EXISTS(SELECT 1 FROM orcamentos WHERE grupo_id=? AND usuario_id=?)');
        $stmt->execute([$id,$user,$id,$user,$id,$user]);
        return (bool) $stmt->fetchColumn();
    }

    public function updateGroup(int $user, int $id, string $name, string $type, string $classification): void
    {
        $stmt = $this->pdo->prepare('UPDATE grupos SET nome=?,tipo=?,classificacao=?,atualizado_em=CURRENT_TIMESTAMP WHERE id=? AND usuario_id=?');
        $stmt->execute([$name,$type,$classification,$id,$user]);
    }

    public function updateSubgroup(int $user, int $id, string $name, ?string $description): void
    {
        $stmt = $this->pdo->prepare('UPDATE subgrupos SET nome=?,descricao=?,atualizado_em=CURRENT_TIMESTAMP WHERE id=? AND grupo_id IN (SELECT id FROM grupos WHERE usuario_id=?)');
        $stmt->execute([$name,$description,$id,$user]);
    }

    public function reactivateGroup(int $user, int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE grupos SET ativo=1,atualizado_em=CURRENT_TIMESTAMP WHERE id=? AND usuario_id=?');
        $stmt->execute([$id,$user]);
    }

    public function reactivateSubgroup(int $user, int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE subgrupos SET ativo=1,atualizado_em=CURRENT_TIMESTAMP WHERE id=? AND grupo_id IN (SELECT id FROM grupos WHERE usuario_id=? AND ativo=1)');
        $stmt->execute([$id,$user]);
    }
}
