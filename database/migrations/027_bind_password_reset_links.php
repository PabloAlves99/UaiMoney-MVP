<?php
declare(strict_types=1);

return static function (PDO $pdo): void {
    // Existing requests have no token and cannot be used by the new flow.
    $pdo->exec('ALTER TABLE password_resets ADD COLUMN token_hash TEXT');
    $pdo->exec('CREATE UNIQUE INDEX password_resets_token_hash ON password_resets(token_hash)');
};
