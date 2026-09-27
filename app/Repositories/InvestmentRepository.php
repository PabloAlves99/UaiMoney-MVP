<?php

declare(strict_types=1);

namespace App\Repositories;

final class InvestmentRepository extends FinanceRepository
{
    public function list(int $user): array
    {
        return $this->rows('SELECT * FROM investimentos WHERE usuario_id=? ORDER BY ativo DESC, valor_atual_centavos DESC, nome', [$user]);
    }

    public function summary(int $user): array
    {
        return $this->rows("SELECT COALESCE(SUM(valor_aplicado_centavos), 0) AS aplicado,
            COALESCE(SUM(valor_atual_centavos), 0) AS atual
            FROM investimentos WHERE usuario_id=? AND ativo=1", [$user])[0];
    }

    public function create(int $user, array $data): int
    {
        $this->run('INSERT INTO investimentos(usuario_id,nome,tipo,instituicao,objetivo,valor_aplicado_centavos,valor_atual_centavos,data_inicio)
            VALUES(?,?,?,?,?,?,?,?)', [$user, ...$data]);
        return (int) $this->pdo->lastInsertId();
    }

    public function deactivate(int $user, int $id): void
    {
        $this->run('UPDATE investimentos SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?', [$user, $id]);
    }

    public function updateCurrentValue(int $user, int $id, int $amount): bool
    {
        return $this->run('UPDATE investimentos SET valor_atual_centavos=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=? AND ativo=1', [$amount, $user, $id]) === 1;
    }
}
