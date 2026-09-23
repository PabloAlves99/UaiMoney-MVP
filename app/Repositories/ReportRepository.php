<?php

declare(strict_types=1);

namespace App\Repositories;

final class ReportRepository extends FinanceRepository
{
    public function categories(int $user): array
    {
        return $this->rows('SELECT s.id,s.nome,g.nome AS grupo_nome,g.id AS grupo_id,g.tipo FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=? AND g.ativo=1 AND s.ativo=1 ORDER BY g.tipo,g.nome,s.nome', [$user]);
    }

    public function totals(int $user, string $start, string $end, array $filters = []): array
    {
        [$where, $params] = $this->scope($user, $start, $end, $filters);
        return $this->rows("SELECT
            COALESCE(SUM(CASE WHEN tipo='receita' AND status='efetivada' THEN valor_centavos ELSE 0 END),0) AS receitas,
            COALESCE(SUM(CASE WHEN tipo='despesa' AND status='efetivada' THEN valor_centavos ELSE 0 END),0) AS despesas,
            COALESCE(SUM(CASE WHEN tipo='receita' AND status='pendente' THEN valor_centavos ELSE 0 END),0) AS receber,
            COALESCE(SUM(CASE WHEN tipo='despesa' AND status='pendente' THEN valor_centavos ELSE 0 END),0) AS pagar
            FROM consumo WHERE $where", $params)[0];
    }

    private function scope(int $user, string $start, string $end, array $filters): array
    {
        $where = 'usuario_id=? AND data BETWEEN ? AND ?';
        $params = [$user, $start, $end];
        foreach (['conta_id', 'cartao_id', 'grupo_id', 'subgrupo_id', 'meio_pagamento', 'tipo'] as $key) {
            if (!empty($filters[$key])) {
                $where .= " AND $key=?";
                $params[] = $filters[$key];
            }
        }
        return [$where, $params];
    }

    public function breakdown(int $user, string $start, string $end, string $dimension, array $filters = []): array
    {
        $dimensions = [
            'categoria' => ['grupo_id', '(SELECT nome FROM grupos WHERE id=c.grupo_id AND usuario_id=c.usuario_id)'],
            'subcategoria' => ['subgrupo_id', "(SELECT g.nome || ' · ' || s.nome FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE s.id=c.subgrupo_id AND g.usuario_id=c.usuario_id)"],
            'conta' => ['conta_id', "COALESCE((SELECT nome FROM contas WHERE id=c.conta_id AND usuario_id=c.usuario_id),'Sem conta / cartão')"],
            'cartao' => ['cartao_id', "COALESCE((SELECT nome FROM cartoes WHERE id=c.cartao_id AND usuario_id=c.usuario_id),'Fora do cartão')"],
            'meio' => ['meio_pagamento', "COALESCE(meio_pagamento,'Não informado')"],
            'origem' => ["CASE WHEN recorrencia_id IS NULL THEN 'Eventual' ELSE 'Recorrente' END", "CASE WHEN recorrencia_id IS NULL THEN 'Eventual' ELSE 'Recorrente' END"],
            'mes' => ["substr(data,1,7)", "substr(data,1,7)"],
        ];
        [$column, $label] = $dimensions[$dimension] ?? $dimensions['categoria'];
        $parent = $dimension === 'subcategoria' ? 'grupo_id,' : '';
        [$where, $params] = $this->scope($user, $start, $end, $filters);
        return $this->rows("SELECT $parent $column AS id, $label AS nome, SUM(CASE WHEN tipo='despesa' THEN valor_centavos ELSE 0 END) AS despesas,
            SUM(CASE WHEN tipo='receita' THEN valor_centavos ELSE 0 END) AS receitas
            FROM consumo c WHERE $where AND status='efetivada' GROUP BY $column ORDER BY " . ($dimension === 'mes' ? 'nome' : 'despesas DESC'), $params);
    }

    public function monthly(int $user, string $start, string $end, array $filters = []): array
    {
        $map = array_column($this->breakdown($user, $start, $end, 'mes', $filters), null, 'nome');
        $rows = [];
        $last = substr($end, 0, 7);
        for ($month = substr($start, 0, 7); $month <= $last; $month = \App\Core\FinancialDate::shift($month, 1)) {
            $rows[] = $map[$month] ?? ['nome' => $month, 'receitas' => 0, 'despesas' => 0];
        }
        return $rows;
    }

    public function entries(int $user, string $start, string $end, array $filters, int $page = 1): array
    {
        [$where, $params] = $this->scope($user, $start, $end, $filters);
        return $this->rows("SELECT c.*,
            (SELECT nome FROM grupos WHERE id=c.grupo_id AND usuario_id=c.usuario_id) AS categoria,
            (SELECT nome FROM subgrupos WHERE id=c.subgrupo_id) AS subcategoria,
            (SELECT nome FROM contas WHERE id=c.conta_id AND usuario_id=c.usuario_id) AS conta,
            (SELECT nome FROM cartoes WHERE id=c.cartao_id AND usuario_id=c.usuario_id) AS cartao
            FROM consumo c WHERE $where AND status='efetivada'
            ORDER BY data DESC,transacao_id DESC,valor_centavos DESC,descricao LIMIT 31 OFFSET " . ((max(1, $page) - 1) * 30), $params);
    }

    public function upcoming(int $user): array
    {
        return $this->rows("SELECT t.id,t.descricao,t.data_vencimento,t.valor_centavos,g.tipo FROM transacoes t
            JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=t.usuario_id
            WHERE t.usuario_id=? AND t.status='pendente' AND t.cartao_id IS NULL ORDER BY t.data_vencimento LIMIT 8", [$user]);
    }

    public function pendingCash(int $user, string $end): int
    {
        return (int) $this->rows("SELECT COALESCE(SUM(CASE g.tipo WHEN 'receita' THEN t.valor_centavos ELSE -t.valor_centavos END),0) AS valor
            FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=t.usuario_id
            WHERE t.usuario_id=? AND t.status='pendente' AND t.cartao_id IS NULL AND t.data_vencimento<=?", [$user, $end])[0]['valor'];
    }

    public function budgets(int $user, string $month): array
    {
        return $this->rows("SELECT b.*,g.nome,
            COALESCE((SELECT SUM(c.valor_centavos) FROM consumo c WHERE c.usuario_id=b.usuario_id AND c.grupo_id=b.grupo_id AND substr(c.data,1,7)=b.ano_mes AND c.status='efetivada'),0) AS realizado,
            COALESCE((SELECT SUM(c.valor_centavos) FROM consumo c WHERE c.usuario_id=b.usuario_id AND c.grupo_id=b.grupo_id AND substr(c.data,1,7)=b.ano_mes AND c.status='pendente'),0) AS pendente
            FROM orcamentos b JOIN grupos g ON g.id=b.grupo_id AND g.usuario_id=b.usuario_id WHERE b.usuario_id=? AND b.ano_mes=? ORDER BY g.nome", [$user, $month]);
    }

    public function saveBudget(int $user, string $month, int $group, int $amount): void
    {
        $this->run('INSERT INTO orcamentos(usuario_id,ano_mes,grupo_id,valor_limite_centavos) VALUES(?,?,?,?) ON CONFLICT(usuario_id,ano_mes,grupo_id) DO UPDATE SET valor_limite_centavos=excluded.valor_limite_centavos,atualizado_em=CURRENT_TIMESTAMP', [$user, $month, $group, $amount]);
    }

    public function deleteBudget(int $user, int $id): void
    {
        $this->run('DELETE FROM orcamentos WHERE usuario_id=? AND id=?', [$user, $id]);
    }

    public function copyBudgets(int $user, string $from, string $to): void
    {
        $this->run('INSERT OR IGNORE INTO orcamentos(usuario_id,ano_mes,grupo_id,valor_limite_centavos) SELECT usuario_id,?,grupo_id,valor_limite_centavos FROM orcamentos WHERE usuario_id=? AND ano_mes=?', [$to, $user, $from]);
    }
}
