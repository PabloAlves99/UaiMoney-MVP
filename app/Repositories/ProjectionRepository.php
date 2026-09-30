<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProjectionRepository extends FinanceRepository
{
    private const SELECT = "SELECT p.*,
        COALESCE(i.nome, m.nome) AS origem_nome,
        CASE p.origem_tipo WHEN 'investimento' THEN i.valor_atual_centavos ELSE m.valor_atual_centavos END AS valor_inicial_centavos,
        CASE p.origem_tipo WHEN 'investimento' THEN p.valor_meta_centavos ELSE m.valor_meta_centavos END AS meta_calculada_centavos,
        CASE p.origem_tipo WHEN 'investimento' THEN i.ativo ELSE m.ativo END AS origem_ativa
        FROM projecoes p
        LEFT JOIN investimentos i ON i.id=p.investimento_id AND i.usuario_id=p.usuario_id
        LEFT JOIN metas_financeiras m ON m.id=p.objetivo_id AND m.usuario_id=p.usuario_id";

    public function list(int $user): array
    {
        return $this->rows(self::SELECT . ' WHERE p.usuario_id=? ORDER BY p.atualizado_em DESC, p.nome', [$user]);
    }

    public function find(int $user, int $id): ?array
    {
        $rows = $this->rows(self::SELECT . ' WHERE p.usuario_id=? AND p.id=? LIMIT 1', [$user, $id]);
        return $rows[0] ?? null;
    }

    public function sources(int $user): array
    {
        return $this->rows("SELECT 'investimento' AS tipo, id, nome, valor_atual_centavos AS atual_centavos, NULL AS meta_centavos, ativo
            FROM investimentos WHERE usuario_id=?
            UNION ALL
            SELECT 'objetivo', id, nome, valor_atual_centavos, valor_meta_centavos, ativo
            FROM metas_financeiras WHERE usuario_id=?
            ORDER BY ativo DESC, tipo, nome", [$user, $user]);
    }

    public function source(int $user, string $type, int $id): ?array
    {
        $table = $type === 'investimento' ? 'investimentos' : ($type === 'objetivo' ? 'metas_financeiras' : null);
        if ($table === null) return null;
        $target = $type === 'objetivo' ? ', valor_meta_centavos AS meta_centavos' : ', NULL AS meta_centavos';
        $rows = $this->rows("SELECT id,nome,valor_atual_centavos AS atual_centavos,ativo{$target} FROM {$table} WHERE usuario_id=? AND id=? LIMIT 1", [$user, $id]);
        return $rows[0] ?? null;
    }

    public function history(int $user, string $type, int $id): array
    {
        $field = $type === 'investimento' ? 'investimento_id' : ($type === 'objetivo' ? 'objetivo_id' : null);
        if ($field === null) return [];
        return $this->rows("SELECT valor_centavos,data_referencia,registrado_em FROM historico_patrimonio WHERE usuario_id=? AND origem_tipo=? AND {$field}=? ORDER BY data_referencia,id", [$user, $type, $id]);
    }

    public function create(int $user, array $data): int
    {
        $this->run('INSERT INTO projecoes(usuario_id,nome,origem_tipo,investimento_id,objetivo_id,aporte_mensal_centavos,taxa_quatro_casas,periodicidade_taxa,valor_meta_centavos) VALUES(?,?,?,?,?,?,?,?,?)', [$user, ...$data]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $user, int $id, array $data): bool
    {
        return $this->run('UPDATE projecoes SET nome=?,origem_tipo=?,investimento_id=?,objetivo_id=?,aporte_mensal_centavos=?,taxa_quatro_casas=?,periodicidade_taxa=?,valor_meta_centavos=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?', [...$data, $user, $id]) === 1;
    }

    public function delete(int $user, int $id): bool
    {
        return $this->run('DELETE FROM projecoes WHERE usuario_id=? AND id=?', [$user, $id]) === 1;
    }
}
