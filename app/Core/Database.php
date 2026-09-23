<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private ?PDO $connection = null;

    public function __construct(
        private readonly array $config
    ) {
    }

    public function connect(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $driver = $this->config['driver'] ?? null;

        if ($driver !== 'sqlite') {
            throw new RuntimeException(
                'Driver de banco de dados não suportado.'
            );
        }

        $databasePath = $this->config['database'] ?? null;

        if (!$databasePath) {
            throw new RuntimeException(
                'Caminho do banco de dados não configurado.'
            );
        }

        $directory = dirname($databasePath);

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException(
                    'Não foi possível criar o diretório do banco de dados.'
                );
            }
        }

        try {
            $pdo = new PDO(
                'sqlite:' . $databasePath
            );

            $pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $pdo->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );

            if ($this->config['foreign_keys'] ?? true) {
                $pdo->exec(
                    'PRAGMA foreign_keys = ON'
                );
            }

            $pdo->exec(
                'PRAGMA journal_mode = WAL'
            );

            $pdo->exec(
                'PRAGMA busy_timeout = 5000'
            );

            $this->connection = $pdo;

            return $this->connection;

        } catch (PDOException $e) {

            throw new RuntimeException(
                'Erro ao conectar ao banco de dados.',
                0,
                $e
            );
        }
    }
}