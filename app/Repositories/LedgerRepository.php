<?php
declare(strict_types=1);
namespace App\Repositories;

final class LedgerRepository extends FinanceRepository
{
    public function transfer(int $user, int $from, int $to, int $amount, string $date, string $description): void
    {
        $this->run('INSERT INTO transferencias(usuario_id,origem_id,destino_id,valor_centavos,data,descricao) VALUES(?,?,?,?,?,?)', [$user,$from,$to,$amount,$date,$description]);
    }

    public function reconcile(int $user, int $account, int $before, int $actual, string $reason): void
    {
        $this->run('INSERT INTO conferencias(usuario_id,conta_id,data,saldo_anterior_centavos,saldo_informado_centavos,ajuste_centavos,motivo) VALUES(?,?,?,?,?,?,?)', [$user,$account,date('Y-m-d'),$before,$actual,$actual-$before,$reason]);
    }

    public function statement(int $user, int $account, int $page = 1): array
    {
        return $this->rows('SELECT * FROM movimentos_caixa WHERE usuario_id=? AND conta_id=? ORDER BY data DESC,origem_id DESC LIMIT 51 OFFSET ' . ((max(1,$page)-1)*50), [$user,$account]);
    }

    public function transaction(int $user, int $id): ?array
    {
        return $this->rows('SELECT t.*,g.tipo,g.nome AS grupo_nome,s.nome AS subgrupo_nome,c.nome AS conta_nome,k.nome AS cartao_nome,
            COALESCE((SELECT SUM(e.valor_centavos) FROM estornos e WHERE e.usuario_id=t.usuario_id AND e.transacao_id=t.id),0) AS estornado
            FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id AND g.usuario_id=t.usuario_id
            LEFT JOIN contas c ON c.id=t.conta_id LEFT JOIN cartoes k ON k.id=t.cartao_id WHERE t.usuario_id=? AND t.id=? AND t.excluida_em IS NULL', [$user,$id])[0] ?? null;
    }

    public function refund(int $user, int $transaction, ?int $invoice, int $amount, string $date, string $reason): void
    {
        $this->run('UPDATE transacoes SET versao=versao+1 WHERE usuario_id=? AND id=?', [$user, $transaction]);
        $this->run('INSERT INTO estornos(usuario_id,transacao_id,fatura_id,valor_centavos,data,motivo) VALUES(?,?,?,?,?,?)', [$user,$transaction,$invoice,$amount,$date,$reason]);
    }

    public function history(int $user, int $id): array
    {
        return $this->rows(
            'SELECT acao,criado_em FROM historico_movimentacoes
             WHERE usuario_id=? AND transacao_id=? ORDER BY id DESC LIMIT 20',
            [$user, $id]
        );
    }

    public function refunds(int $user, int $transaction): array
    {
        return $this->rows('SELECT * FROM estornos WHERE usuario_id=? AND transacao_id=? ORDER BY id DESC', [$user,$transaction]);
    }

    public function startCategories(int $user): void
    {
        foreach (['receita'=>['Renda'=>['Salário','Outras receitas']], 'despesa'=>['Moradia'=>['Aluguel','Contas da casa'],'Alimentação'=>['Mercado','Restaurantes'],'Transporte'=>['Combustível','Transporte público'],'Saúde'=>['Cuidados pessoais'],'Lazer'=>['Passeios','Assinaturas']]] as $type=>$groups) {
            foreach ($groups as $name=>$children) {
                $this->run('INSERT OR IGNORE INTO grupos(usuario_id,nome,tipo) VALUES(?,?,?)', [$user,$name,$type]);
                $group = $this->rows('SELECT id FROM grupos WHERE usuario_id=? AND nome=? AND tipo=?', [$user,$name,$type])[0]['id'];
                foreach ($children as $child) $this->run('INSERT OR IGNORE INTO subgrupos(grupo_id,nome) VALUES(?,?)', [$group,$child]);
            }
        }
    }
}
