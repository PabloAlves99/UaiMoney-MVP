<?php
declare(strict_types=1);
namespace App\Repositories;

final class ActivityRepository extends FinanceRepository
{
    private function query(int $user, array $filters): array
    {
        $where = 't.usuario_id=? AND g.usuario_id=t.usuario_id AND t.excluida_em IS ' . (($filters['lixeira'] ?? '') === '1' ? 'NOT NULL' : 'NULL');
        $params = [$user];
        foreach (['status' => 't.status', 'tipo' => 'g.tipo', 'conta_id' => 't.conta_id', 'cartao_id' => 't.cartao_id', 'grupo_id' => 'g.id', 'subgrupo_id' => 's.id', 'meio_pagamento' => 't.meio_pagamento'] as $key => $column) {
            if (!empty($filters[$key])) {
                $where .= " AND $column=?";
                $params[] = $filters[$key];
            }
        }
        if (!empty($filters['q'])) {
            $where .= ' AND t.descricao LIKE ?';
            $params[] = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['data_inicio'])) {
            $where .= ' AND t.data_competencia>=?';
            $params[] = $filters['data_inicio'];
        }
        if (!empty($filters['data_fim'])) {
            $where .= ' AND t.data_competencia<=?';
            $params[] = $filters['data_fim'];
        }
        $from = "FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id
            LEFT JOIN contas c ON c.id=t.conta_id LEFT JOIN cartoes k ON k.id=t.cartao_id
            LEFT JOIN parcelamentos p ON p.id=t.parcelamento_id WHERE $where";
        return [$from, $params];
    }

    public function list(int $user, array $filters, int $page = 1): array
    {
        [$from, $params] = $this->query($user, $filters);
        return $this->rows("SELECT t.*,g.tipo,g.nome AS grupo_nome,s.nome AS subgrupo_nome,c.nome AS conta_nome,k.nome AS cartao_nome,p.total_parcelas,
            COALESCE((SELECT SUM(e.valor_centavos) FROM estornos e WHERE e.usuario_id=t.usuario_id AND e.transacao_id=t.id),0) AS estornado $from
            ORDER BY t.data_competencia DESC,t.id DESC LIMIT 51 OFFSET " . ((max(1, $page) - 1) * 50), $params);
    }

    public function count(int $user, array $filters): int
    {
        [$from, $params] = $this->query($user, $filters);
        return (int) $this->rows("SELECT COUNT(*) AS total $from", $params)[0]['total'];
    }

    public function export(int $user, array $filters): \Generator
    {
        [$from, $params] = $this->query($user, $filters);
        $stmt = $this->pdo->prepare("SELECT t.id,t.descricao,g.tipo,g.nome AS categoria,s.nome AS subcategoria,t.valor_centavos,
            COALESCE((SELECT SUM(e.valor_centavos) FROM estornos e WHERE e.usuario_id=t.usuario_id AND e.transacao_id=t.id),0) AS estornado_centavos,
            t.data_competencia,t.data_vencimento,t.data_efetivacao,t.status,c.nome AS conta,k.nome AS cartao,t.meio_pagamento,t.parcelamento_id,t.recorrencia_id,t.fatura_id $from ORDER BY t.id");
        $stmt->execute($params);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC))
            yield $row;
    }
}
