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
        $where = 'usuario_id=? AND conta_id=?';
        $params = [(int) $share['usuario_id'], (int) $share['conta_id']];
        if (($filters['inicio'] ?? '') !== '') {
            $where .= ' AND data>=?';
            $params[] = $filters['inicio'];
        }
        if (($filters['fim'] ?? '') !== '') {
            $where .= ' AND data<=?';
            $params[] = $filters['fim'];
        }
        if (($filters['tipo'] ?? '') === 'entrada') {
            $where .= ' AND valor_centavos>0';
        } elseif (($filters['tipo'] ?? '') === 'saida') {
            $where .= ' AND valor_centavos<0';
        }
        if (($filters['q'] ?? '') !== '') {
            $where .= ' AND descricao LIKE ? ESCAPE \'\\\'';
            $params[] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']) . '%';
        }
        return [$where, $params];
    }

    public function entries(array $share, array $filters, int $page): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        return $this->rows(
            "SELECT data,descricao,valor_centavos,origem FROM movimentos_caixa WHERE $where
             ORDER BY data DESC, origem_id DESC LIMIT 51 OFFSET " . ((max(1, $page) - 1) * 50),
            $params
        );
    }

    public function exportEntries(array $share, array $filters): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        return $this->rows(
            "SELECT data,descricao,valor_centavos,origem FROM movimentos_caixa WHERE $where
             ORDER BY data DESC, origem_id DESC LIMIT 10001",
            $params
        );
    }

    public function totals(array $share, array $filters): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        return $this->rows(
            "SELECT COALESCE(SUM(CASE WHEN valor_centavos>0 THEN valor_centavos ELSE 0 END),0) AS entradas,
                    COALESCE(SUM(CASE WHEN valor_centavos<0 THEN -valor_centavos ELSE 0 END),0) AS saidas
             FROM movimentos_caixa WHERE $where",
            $params
        )[0];
    }

    public function monthly(array $share, array $filters): array
    {
        [$where, $params] = $this->filteredWhere($share, $filters);
        return $this->rows(
            "SELECT substr(data,1,7) AS mes,
                    SUM(CASE WHEN valor_centavos>0 THEN valor_centavos ELSE 0 END) AS entradas,
                    SUM(CASE WHEN valor_centavos<0 THEN -valor_centavos ELSE 0 END) AS saidas
             FROM movimentos_caixa WHERE $where GROUP BY substr(data,1,7) ORDER BY mes",
            $params
        );
    }
}
