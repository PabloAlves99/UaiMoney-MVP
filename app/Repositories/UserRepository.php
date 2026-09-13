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
        string $tipo = 'usuario'
    ): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (
                nome,
                login,
                email,
                senha_hash,
                tipo
            )
            VALUES (
                :nome,
                :login,
                :email,
                :senha_hash,
                :tipo
            )
        ");

        $stmt->execute([
            ':nome' => $nome,
            ':login' => $login,
            ':email' => $email,
            ':senha_hash' => $senhaHash,
            ':tipo' => $tipo
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}