<?php

declare(strict_types=1);

namespace App\Repositories;

final class MovementRepository extends FinanceRepository
{
    public function find(int $user, int $id): ?array
    {
        return $this->rows(
            'SELECT t.*, g.tipo, g.nome AS grupo_nome, s.nome AS subgrupo_nome,
                c.nome AS conta_nome, k.nome AS cartao_nome,
                COALESCE((SELECT SUM(e.valor_centavos) FROM estornos e
                    WHERE e.usuario_id=t.usuario_id AND e.transacao_id=t.id),0) AS estornado
             FROM transacoes t
             JOIN subgrupos s ON s.id=t.subgrupo_id
             JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=t.usuario_id
             LEFT JOIN contas c ON c.id=t.conta_id AND c.usuario_id=t.usuario_id
             LEFT JOIN cartoes k ON k.id=t.cartao_id AND k.usuario_id=t.usuario_id
             WHERE t.usuario_id=? AND t.id=?',
            [$user, $id]
        )[0] ?? null;
    }

    public function record(int $user, array $previous, string $action): void
    {
        $this->run(
            'INSERT INTO historico_movimentacoes(usuario_id,transacao_id,acao,dados_anteriores) VALUES(?,?,?,?)',
            [$user, $previous['id'], $action, json_encode($previous, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]
        );
    }

    public function update(int $user, int $id, array $data): void
    {
        $this->run(
            'UPDATE transacoes SET subgrupo_id=?,conta_id=?,descricao=?,valor_centavos=?,
                data_competencia=?,data_vencimento=?,data_efetivacao=?,status=?,meio_pagamento=?,observacao=?,
                versao=versao+1,atualizado_em=CURRENT_TIMESTAMP
             WHERE usuario_id=? AND id=? AND excluida_em IS NULL',
            [...$data, $user, $id]
        );
    }

    public function delete(int $user, int $id): void
    {
        $this->run(
            "UPDATE transacoes SET status_antes_exclusao=status,status='cancelada',
                excluida_em=CURRENT_TIMESTAMP,versao=versao+1,atualizado_em=CURRENT_TIMESTAMP
             WHERE usuario_id=? AND id=? AND excluida_em IS NULL",
            [$user, $id]
        );
    }

    public function restore(int $user, int $id): void
    {
        $this->run(
            'UPDATE transacoes SET status=status_antes_exclusao,status_antes_exclusao=NULL,
                excluida_em=NULL,versao=versao+1,atualizado_em=CURRENT_TIMESTAMP
             WHERE usuario_id=? AND id=? AND excluida_em IS NOT NULL',
            [$user, $id]
        );
    }
}
