<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }


    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                login,
                email,
                senha_hash,
                tipo,
                ativo,
                tema,
                criado_em,
                atualizado_em
            FROM usuarios
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function list(): array
    {
        return $this->pdo->query("SELECT id,nome,login,email,tipo,ativo,tema,criado_em,atualizado_em
            FROM usuarios ORDER BY ativo DESC,nome COLLATE NOCASE,id")->fetchAll();
    }

    public function update(int $id, string $nome, string $login, string $email, string $tipo, int $ativo, string $tema, ?string $senhaHash): void
    {
        $sql = 'UPDATE usuarios SET nome=:nome,login=:login,email=:email,tipo=:tipo,ativo=:ativo,tema=:tema,atualizado_em=CURRENT_TIMESTAMP';
        $params = [':id' => $id, ':nome' => $nome, ':login' => $login, ':email' => $email, ':tipo' => $tipo, ':ativo' => $ativo, ':tema' => $tema];
        if ($senhaHash !== null) {
            $sql .= ',senha_hash=:senha_hash';
            $params[':senha_hash'] = $senhaHash;
        }
        $sql .= ' WHERE id=:id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }


    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                login,
                email,
                senha_hash,
                tipo,
                ativo,
                tema,
                criado_em,
                atualizado_em
            FROM usuarios
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ':email' => $email
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }


    public function findByLogin(string $login): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                id,
                nome,
                login,
                email,
                senha_hash,
                tipo,
                ativo,
                tema,
                criado_em,
                atualizado_em
            FROM usuarios
            WHERE login = :login
            LIMIT 1
        ");

        $stmt->execute([
            ':login' => $login
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function findByIdentifier(string $identifier): ?array
    {
        $stmt = $this->pdo->prepare("
        SELECT
            id,
            nome,
            login,
            email,
            senha_hash,
            tipo,
            ativo,
            tema,
            criado_em,
            atualizado_em
        FROM usuarios
        WHERE email = :identifier_email
           OR login = :identifier_login
        LIMIT 1
    ");

        $stmt->execute([
            ':identifier_email' => $identifier,
            ':identifier_login' => $identifier
        ]);

        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function create(
        string $nome,
        string $login,
        string $email,
        string $senhaHash,
        string $tipo = 'usuario',
        string $tema = 'light'
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (
                nome,
                login,
                email,
                senha_hash,
                tipo,
                tema
            )
            VALUES (
                :nome,
                :login,
                :email,
                :senha_hash,
                :tipo,
                :tema
            )
        ");

        $stmt->execute([
            ':nome' => $nome,
            ':login' => $login,
            ':email' => $email,
            ':senha_hash' => $senhaHash,
            ':tipo' => $tipo,
            ':tema' => $tema
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updateTheme(
        int $usuarioId,
        string $tema
    ): void {

        $stmt = $this->pdo->prepare("
        UPDATE usuarios
        SET
            tema = :tema,
            atualizado_em = CURRENT_TIMESTAMP
        WHERE id = :id
    ");

        $stmt->execute([
            ':tema' => $tema,
            ':id' => $usuarioId
        ]);
    }
}
