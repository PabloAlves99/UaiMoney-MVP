<?php
declare(strict_types=1);
namespace App\Repositories;

use PDO;
use Throwable;

/** Shared SQL primitives. All public domain methods require an explicit user. */
abstract class FinanceRepository
{
    public function __construct(protected readonly PDO $pdo)
    {
    }

    protected function rows(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function run(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function atomic(callable $work): mixed
    {
        // Take the SQLite writer lock before reading balances or remaining amounts.
        $this->pdo->exec('BEGIN IMMEDIATE');
        try {
            $result = $work();
            $this->pdo->exec('COMMIT');
            return $result;
        } catch (Throwable $e) {
            $this->pdo->exec('ROLLBACK');
            throw $e;
        }
    }

    public function claim(int $user, string $key): bool
    {
        return $this->run('INSERT OR IGNORE INTO operacoes(usuario_id,chave) VALUES(?,?)', [$user, $key]) === 1;
    }

    public function once(int $user, array $input, string $scope): bool
    {
        if (!isset($input['_operation']))
            return true; // CLI/domain callers have no browser request key.
        $key = (string) $input['_operation'];
        if (!preg_match('/^[a-f0-9]{32}$/', $key))
            throw new \DomainException('Reabra o formulário e tente novamente.');
        return $this->claim($user, $scope . ':' . $key);
    }
}
