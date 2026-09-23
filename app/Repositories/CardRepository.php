<?php
declare(strict_types=1);
namespace App\Repositories;

final class CardRepository extends FinanceRepository
{
    public function list(int $user): array
    {
        return $this->rows("SELECT c.*,
            COALESCE((SELECT SUM(t.valor_centavos) FROM transacoes t WHERE t.usuario_id=c.usuario_id AND t.cartao_id=c.id AND t.status<>'cancelada'),0)
            -COALESCE((SELECT SUM(e.valor_centavos) FROM estornos e JOIN transacoes t ON t.id=e.transacao_id WHERE e.usuario_id=c.usuario_id AND t.cartao_id=c.id),0)
            -COALESCE((SELECT SUM(p.valor_centavos) FROM pagamentos_fatura p JOIN faturas f ON f.id=p.fatura_id WHERE p.usuario_id=c.usuario_id AND f.cartao_id=c.id),0) AS utilizado
            FROM cartoes c WHERE c.usuario_id=? ORDER BY c.ativo DESC,c.nome", [$user]);
    }

    public function find(int $user, int $id): ?array
    {
        return $this->rows('SELECT * FROM cartoes WHERE usuario_id=? AND id=?', [$user, $id])[0] ?? null;
    }

    public function save(int $user, ?int $id, array $data): int
    {
        if ($id) {
            $this->run('UPDATE cartoes SET nome=?,instituicao=?,limite_centavos=?,dia_fechamento=?,dia_vencimento=?,conta_pagamento_id=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?', [...$data, $user, $id]);
            return $id;
        }
        $this->run('INSERT INTO cartoes(nome,instituicao,limite_centavos,dia_fechamento,dia_vencimento,conta_pagamento_id,usuario_id) VALUES(?,?,?,?,?,?,?)', [...$data, $user]);
        return (int) $this->pdo->lastInsertId();
    }

    public function deactivate(int $user, int $id): void
    {
        $this->run('UPDATE cartoes SET ativo=0 WHERE usuario_id=? AND id=?', [$user, $id]);
    }

    public function invoices(int $user, ?int $card = null): array
    {
        return $this->rows("SELECT f.*,c.nome AS cartao_nome,c.conta_pagamento_id,
            COALESCE((SELECT SUM(t.valor_centavos) FROM transacoes t WHERE t.fatura_id=f.id AND t.usuario_id=f.usuario_id AND t.status<>'cancelada'),0)
            -COALESCE((SELECT SUM(e.valor_centavos) FROM estornos e WHERE e.fatura_id=f.id AND e.usuario_id=f.usuario_id),0)
            +COALESCE((SELECT SUM(x.valor_centavos) FROM creditos_fatura x WHERE x.origem_id=f.id AND x.usuario_id=f.usuario_id),0)
            -COALESCE((SELECT SUM(x.valor_centavos) FROM creditos_fatura x WHERE x.destino_id=f.id AND x.usuario_id=f.usuario_id),0) AS total,
            COALESCE((SELECT SUM(p.valor_centavos) FROM pagamentos_fatura p WHERE p.fatura_id=f.id AND p.usuario_id=f.usuario_id),0) AS pago
            FROM faturas f JOIN cartoes c ON c.id=f.cartao_id AND c.usuario_id=f.usuario_id
            WHERE f.usuario_id=?" . ($card ? ' AND f.cartao_id=?' : '') . ' ORDER BY f.data_vencimento DESC', $card ? [$user, $card] : [$user]);
    }

    public function invoice(int $user, int $id): ?array
    {
        foreach ($this->invoices($user) as $invoice) {
            if ((int) $invoice['id'] === $id)
                return $invoice;
        }
        return null;
    }

    public function ensureInvoice(int $user, int $card, string $month, string $closing, string $due): array
    {
        $this->run("INSERT OR IGNORE INTO faturas(usuario_id,cartao_id,competencia,data_fechamento,data_vencimento) VALUES(?,?,?,?,?)", [$user, $card, $month, $closing, $due]);
        return $this->rows('SELECT * FROM faturas WHERE usuario_id=? AND cartao_id=? AND competencia=?', [$user, $card, $month])[0];
    }

    public function installment(int $user, string $description, int $value, int $count): int
    {
        $this->run('INSERT INTO parcelamentos(usuario_id,descricao,valor_total_centavos,total_parcelas) VALUES(?,?,?,?)', [$user, $description, $value, $count]);
        return (int) $this->pdo->lastInsertId();
    }

    public function purchase(int $user, int $category, int $card, array $invoice, string $description, int $amount, string $date, ?int $installment, int $number, string $purchaseDate): int
    {
        $this->run("INSERT INTO transacoes(usuario_id,subgrupo_id,cartao_id,fatura_id,descricao,valor_centavos,data_competencia,data_vencimento,data_efetivacao,status,meio_pagamento,parcelamento_id,numero_parcela,data_compra)
            VALUES(?,?,?,?,?,?,?,?,?,'efetivada','credito',?,?,?)", [$user, $category, $card, $invoice['id'], $description, $amount, $date, $invoice['data_vencimento'], $purchaseDate, $installment, $installment ? $number : null, $purchaseDate]);
        return (int) $this->pdo->lastInsertId();
    }

    public function payment(int $user, int $invoice, int $account, int $amount, string $date): void
    {
        $this->run('INSERT INTO pagamentos_fatura(usuario_id,fatura_id,conta_id,valor_centavos,data_pagamento) VALUES(?,?,?,?,?)', [$user, $invoice, $account, $amount, $date]);
    }

    public function close(int $user, int $invoice): void
    {
        $this->run("UPDATE faturas SET status='fechada',atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=? AND status='aberta'", [$user, $invoice]);
    }

    public function items(int $user, int $invoice): array
    {
        return $this->rows('SELECT t.*,s.nome AS categoria,COALESCE((SELECT SUM(e.valor_centavos) FROM estornos e WHERE e.usuario_id=t.usuario_id AND e.transacao_id=t.id),0) AS estornado FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id WHERE t.usuario_id=? AND t.fatura_id=? ORDER BY t.data_competencia,t.id', [$user, $invoice]);
    }

    public function payments(int $user, int $invoice): array
    {
        return $this->rows('SELECT p.*,c.nome AS conta_nome FROM pagamentos_fatura p JOIN contas c ON c.id=p.conta_id AND c.usuario_id=p.usuario_id WHERE p.usuario_id=? AND p.fatura_id=? ORDER BY p.id DESC', [$user, $invoice]);
    }

    public function move(int $user, int $transaction, array $invoice): void
    {
        $this->run('UPDATE transacoes SET fatura_id=?,data_vencimento=?,atualizado_em=CURRENT_TIMESTAMP WHERE usuario_id=? AND id=?', [$invoice['id'], $invoice['data_vencimento'], $user, $transaction]);
    }

    public function credit(int $user, int $source, int $target, int $amount): void
    {
        $this->run('INSERT INTO creditos_fatura(usuario_id,origem_id,destino_id,valor_centavos) VALUES(?,?,?,?)', [$user, $source, $target, $amount]);
    }

    public function credits(int $user, int $invoice): array
    {
        return $this->rows('SELECT * FROM creditos_fatura WHERE usuario_id=? AND (origem_id=? OR destino_id=?) ORDER BY id', [$user, $invoice, $invoice]);
    }
}
