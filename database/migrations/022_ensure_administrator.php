<?php

declare(strict_types=1);

return function (PDO $pdo): void {
    $hasAdmin = (int) $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo='admin' AND ativo=1")->fetchColumn();
    if ($hasAdmin === 0) $pdo->exec("UPDATE usuarios SET tipo='admin' WHERE id=(SELECT id FROM usuarios ORDER BY id LIMIT 1)");
};
