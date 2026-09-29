<?php
declare(strict_types=1);

return static function (PDO $pdo): void {
    $pdo->exec('CREATE TABLE password_resets (
        usuario_id INTEGER PRIMARY KEY REFERENCES usuarios(id) ON DELETE CASCADE,
        code_hash TEXT NOT NULL,
        expires_at INTEGER NOT NULL,
        attempts INTEGER NOT NULL DEFAULT 0
    )');
};
