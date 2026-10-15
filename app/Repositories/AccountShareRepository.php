<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AccountShareRepository extends FinanceRepository
{
    public function findForOwner(int $user, int $account): ?array
    {
        $rows = $this->rows(
            'SELECT * FROM compartilhamentos_conta WHERE usuario_id=? AND conta_id=?',
            [$user, $account]
        );
        return $rows[0] ?? null;
    }

    public function findByToken(string $token): ?array
    {
        $rows = $this->rows(
            'SELECT cc.*, c.nome AS conta_nome, c.ativo AS conta_ativa
             FROM compartilhamentos_conta cc
             JOIN contas c ON c.id=cc.conta_id AND c.usuario_id=cc.usuario_id
             WHERE cc.token_hash=? AND cc.ativo=1',
            [hash('sha256', $token)]
        );
        return $rows[0] ?? null;
    }

    public function save(int $user, int $account, string $tokenHash, string $hint, ?string $passwordHash): void
    {
        $this->run(
            'INSERT INTO compartilhamentos_conta(usuario_id,conta_id,token_hash,token_hint,senha_hash,ativo)
             VALUES(?,?,?,?,?,1)
             ON CONFLICT(usuario_id,conta_id) DO UPDATE SET
                token_hash=excluded.token_hash, token_hint=excluded.token_hint,
                senha_hash=excluded.senha_hash, ativo=1, atualizado_em=CURRENT_TIMESTAMP',
            [$user, $account, $tokenHash, $hint, $passwordHash]
        );
    }

    public function disable(int $user, int $account): void
    {
        $this->run(
            'UPDATE compartilhamentos_conta SET ativo=0, atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND conta_id=?',
            [$user, $account]
        );
    }

    public function touch(int $id): void
    {
        $this->run('UPDATE compartilhamentos_conta SET ultimo_acesso_em=CURRENT_TIMESTAMP WHERE id=?', [$id]);
    }

    private function filteredWhere(array $share, array $filters): array
    {
        $where = 'm.usuario_id=? AND m.conta_id=?';
        $params = [(int) $share['usuario_id'], (int) $share['conta_id']];
        if (($filters['inicio'] ?? '') !== '') {
            $where .= ' AND m.data>=?';
            $params[] = $filters['inicio'];
        }
        if (($filters['fim'] ?? '') !== '') {
            $where .= ' AND m.data<=?';
            $params[] = $filters['fim'];
        }
        if (($filters['tipo'] ?? '') === 'entrada') {
            $where .= ' AND m.valor_centavos>0';
        } elseif (($filters['tipo'] ?? '') === 'saida') {
            $where .= ' AND m.valor_centavos<0';
        }
        if (($filters['q'] ?? '') !== '') {
            $where .= ' AND m.descricao LIKE ? ESCAPE \'\\\'';
            $params[] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']) . '%';
        }
        foreach (['grupo_id' => 'g.id', 'subgrupo_id' => 's.id'] as $key => $column) {
            if (!empty($filters[$key])) {
                $where .= " AND $column=?";
                $params[] = $filters[$key];
            }
        }
        return [$where, $params];
    }

    private function ledgerFrom(): string
    {
        return "FROM movimentos_caixa m
            LEFT JOIN estornos e ON m.origem='estorno' AND e.id=m.origem_id AND e.usuario_id=m.usuario_id
            LEFT JOIN transacoes t ON t.usuario_id=m.usuario_id
                AND t.id=CASE WHEN m.origem='lancamento' THEN m.origem_id WHEN m.origem='estorno' THEN e.transacao_id END
            LEFT JOIN subgrupos s ON s.id=t.subgrupo_id
            LEFT JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=m.usuario_id";
    }

    public function categories(array $share): array
    {
        $from = $this->ledgerFrom();
        return $this->rows("SELECT DISTINCT g.id AS grupo_id,g.nome AS grupo_nome,g.tipo,s.id,s.nome
            $from WHERE m.usuario_id=? AND m.conta_id=? AND g.id IS NOT NULL ORDER BY g.nome,s.nome",
            [(int) $share['usuario_id'], (int) $share['conta_id']]);
    }

    private function order(array $filters): string
    {
        $columns = ['data'=>'m.data','descricao'=>'m.descricao COLLATE NOCASE','tipo'=>"CASE WHEN m.valor_centavos>0 THEN 'Entrada' ELSE 'Saída' END",'valor'=>'ABS(m.valor_centavos)'];
        $column = $columns[$filters['ordenacao'] ?? ''] ?? null;
        if ($column===null) return 'm.data DESC,m.origem_id DESC';
        return $column . (($filters['direcao'] ?? '')==='desc' ? ' DESC' : ' ASC') . ',m.data DESC,m.origem_id DESC';
    }

    public function entries(array $share, array $filters, int $page): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        $from = $this->ledgerFrom();
        $order = $this->order($filters);
        return $this->rows(
            "SELECT m.data,m.descricao,m.valor_centavos,m.origem,g.nome AS grupo_nome,s.nome AS subgrupo_nome $from WHERE $where
             ORDER BY $order LIMIT 51 OFFSET " . ((max(1, $page) - 1) * 50),
            $params
        );
    }

    public function exportEntries(array $share, array $filters): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        $from = $this->ledgerFrom();
        $order = $this->order($filters);
        return $this->rows(
            "SELECT m.data,m.descricao,m.valor_centavos,m.origem,g.nome AS grupo_nome,s.nome AS subgrupo_nome $from WHERE $where
             ORDER BY $order LIMIT 10001",
            $params
        );
    }

    public function totals(array $share, array $filters): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        $from = $this->ledgerFrom();
        return $this->rows(
            "SELECT COUNT(*) AS quantidade,
                    COALESCE(SUM(CASE WHEN m.valor_centavos>0 THEN m.valor_centavos ELSE 0 END),0) AS entradas,
                    COALESCE(SUM(CASE WHEN m.valor_centavos<0 THEN -m.valor_centavos ELSE 0 END),0) AS saidas
             $from WHERE $where",
            $params
        )[0];
    }

    public function monthly(array $share, array $filters): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        $from = $this->ledgerFrom();
        return $this->rows(
            "SELECT substr(m.data,1,7) AS mes,
                    SUM(CASE WHEN m.valor_centavos>0 THEN m.valor_centavos ELSE 0 END) AS entradas,
                    SUM(CASE WHEN m.valor_centavos<0 THEN -m.valor_centavos ELSE 0 END) AS saidas
             $from WHERE $where GROUP BY substr(m.data,1,7) ORDER BY mes",
            $params
        );
    }

    public function incomeBreakdown(array $share, array $filters, string $dimension): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        $from = $this->ledgerFrom();
        $column = $dimension === 'subgrupo' ? 's.id' : 'g.id';
        $name = $dimension === 'subgrupo'
            ? "CASE WHEN s.id IS NULL THEN 'Sem categoria · Sem subcategoria' ELSE g.nome || ' · ' || s.nome END"
            : "COALESCE(g.nome,'Sem categoria')";
        return $this->rows("SELECT $column AS id,g.id AS grupo_id,$name AS nome,SUM(m.valor_centavos) AS valor_centavos
            $from WHERE $where AND m.valor_centavos>0
            GROUP BY $column,g.id ORDER BY valor_centavos DESC,nome", $params);
    }
}
