<?php
declare(strict_types=1);
namespace App\Core;

use PDO;
use DomainException;

final class RateLimiter
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function hit(string $scope, string $identity, int $limit = 10, int $window = 900): void
    {
        $key = hash('sha256', $scope . '|' . $identity);
        $now = time();
        $stmt = $this->pdo->prepare('INSERT INTO tentativas_acesso(chave,tentativas,inicio) VALUES(?,1,?)
            ON CONFLICT(chave) DO UPDATE SET tentativas=CASE WHEN inicio<? THEN 1 ELSE tentativas+1 END,
            inicio=CASE WHEN inicio<? THEN excluded.inicio ELSE inicio END');
        $stmt->execute([$key, $now, $now - $window, $now - $window]);
        $stmt = $this->pdo->prepare('SELECT tentativas FROM tentativas_acesso WHERE chave=?');
        $stmt->execute([$key]);
        if ((int) $stmt->fetchColumn() > $limit)
            throw new DomainException('Muitas tentativas. Aguarde 15 minutos antes de tentar novamente.');
        $stmt = $this->pdo->prepare('DELETE FROM tentativas_acesso WHERE inicio<?');
        $stmt->execute([$now - 86400]);
    }

    public function clear(string $scope, string $identity): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tentativas_acesso WHERE chave=?');
        $stmt->execute([hash('sha256', $scope . '|' . $identity)]);
    }
}
