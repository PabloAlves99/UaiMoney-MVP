<?php
declare(strict_types=1);
namespace App\Repositories;

final class GoalRepository extends FinanceRepository
{
    public function list(int $user): array { return $this->rows('SELECT * FROM metas_financeiras WHERE usuario_id=? AND ativo=1 ORDER BY data_alvo IS NULL,data_alvo,nome', [$user]); }
    public function create(int $user, array $data): void { $this->run('INSERT INTO metas_financeiras(usuario_id,nome,tipo,valor_meta_centavos,valor_atual_centavos,data_alvo) VALUES(?,?,?,?,?,?)', [$user, ...$data]); }
    public function updateCurrent(int $user, int $id, int $amount): bool { return $this->run('UPDATE metas_financeiras SET valor_atual_centavos=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=? AND ativo=1', [$amount,$user,$id]) === 1; }
    public function deactivate(int $user, int $id): void { $this->run('UPDATE metas_financeiras SET ativo=0,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?',[$user,$id]); }
}
