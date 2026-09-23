<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\FinancialDate as Dates;
use App\Core\Money;
use App\Repositories\CardRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\AccountRepository;
use DomainException;

final class CardService
{
    public function __construct(private readonly CardRepository $cards, private readonly AccountRepository $accounts, private readonly CategoryRepository $categories) {}

    public function get(int $user, int $id): array
    {
        return $this->cards->find($user,$id) ?? throw new DomainException('Cartão não encontrado.');
    }

    public function save(int $user, ?int $id, array $input): int
    {
        if ($id) $this->get($user,$id);
        $name = trim($input['nome'] ?? '');
        $closing = filter_var($input['dia_fechamento'] ?? '', FILTER_VALIDATE_INT);
        $due = filter_var($input['dia_vencimento'] ?? '', FILTER_VALIDATE_INT);
        if ($name === '' || mb_strlen($name) > 80) throw new DomainException('Informe um nome de até 80 caracteres.');
        if (!$closing || !$due || $closing < 1 || $closing > 31 || $due < 1 || $due > 31) throw new DomainException('Fechamento e vencimento devem estar entre 1 e 31.');
        foreach ($this->cards->list($user) as $card) {
            if ((int)$card['id'] !== $id && mb_strtolower($card['nome']) === mb_strtolower($name)) throw new DomainException('Já existe um cartão com esse nome.');
        }
        $account = !empty($input['conta_pagamento_id']) ? (int)$input['conta_pagamento_id'] : null;
        if ($account) $this->account($user,$account);
        $limit = Money::toCents($input['limite'] ?? '');
        return $this->cards->save($user,$id,[$name,trim($input['instituicao'] ?? ''),$limit,$closing,$due,$account]);
    }

    public function account(int $user, int $id): array
    {
        $account = $this->accounts->findById($id,$user);
        if (!$account || !(int)$account['ativo']) throw new DomainException('Selecione uma conta ativa que pertença a você.');
        return $account;
    }

    /** Competência identifies the month of closing. Purchases on closing day enter the next cycle. */
    public function cycle(array $card, string $purchaseDate): string
    {
        Dates::date($purchaseDate);
        $month = substr($purchaseDate,0,7);
        return $purchaseDate >= Dates::day($month,(int)$card['dia_fechamento']) ? Dates::shift($month,1) : $month;
    }

    public function invoiceFor(int $user, array $card, string $month): array
    {
        Dates::month($month);
        $closing = Dates::day($month,(int)$card['dia_fechamento']);
        $dueMonth = (int)$card['dia_vencimento'] <= (int)$card['dia_fechamento'] ? Dates::shift($month,1) : $month;
        return $this->cards->ensureInvoice($user,(int)$card['id'],$month,$closing,Dates::day($dueMonth,(int)$card['dia_vencimento']));
    }

    public function purchase(int $user, array $input): void
    {
        $this->cards->atomic(function () use ($user,$input) {
            if (!$this->cards->once($user,$input,'purchase')) return;
            $card = $this->get($user,(int)($input['cartao_id'] ?? 0));
            if (!(int)$card['ativo']) throw new DomainException('Este cartão está desativado.');
            $category = $this->categories->findSubgroupById((int)($input['subgrupo_id'] ?? 0),$user);
            if (!$category || !(int)$category['ativo'] || !(int)$category['grupo_ativo'] || $category['tipo'] !== 'despesa') throw new DomainException('Selecione uma categoria de despesa ativa.');
            $date = Dates::past($input['data'] ?? '');
            $description = trim($input['descricao'] ?? '');
            $amount = Money::toCents($input['valor'] ?? '');
            $count = filter_var($input['parcelas'] ?? 1,FILTER_VALIDATE_INT);
            if ($description === '' || mb_strlen($description)>160) throw new DomainException('Informe uma descrição de até 160 caracteres.');
            if (!$count || $count < 1 || $count > 120 || $amount < $count) throw new DomainException('Use entre 1 e 120 parcelas, com pelo menos um centavo em cada.');
            $month = !empty($input['primeira_fatura']) ? Dates::month($input['primeira_fatura']) : $this->cycle($card,$date);
            if ($month < substr($date,0,7)) throw new DomainException('A primeira fatura não pode anteceder a compra.');
            $installment = $count > 1 ? $this->cards->installment($user,$description,$amount,$count) : null;
            for ($i=0;$i<$count;$i++) {
                $invoice = $this->invoiceFor($user,$card,Dates::shift($month,$i));
                $existing = $this->cards->invoice($user,(int)$invoice['id']);
                if ($invoice['status'] !== 'aberta' || (int)$existing['pago'] > 0) throw new DomainException('A fatura selecionada já foi fechada ou recebeu pagamentos. Escolha outra primeira fatura.');
                // Budget consumption follows each installment's cycle; a single purchase uses its purchase date.
                $competence = $count === 1 ? $date : Dates::day(Dates::shift($month,$i),(int)substr($date,8,2));
                $this->cards->purchase($user,(int)$category['id'],(int)$card['id'],$invoice,$description,intdiv($amount,$count)+($i < $amount % $count ? 1 : 0),$competence,$installment,$i+1,$date);
            }
        });
    }

    public function pay(int $user, int $id, array $input): void
    {
        $this->cards->atomic(function () use ($user,$id,$input) {
            if (!$this->cards->once($user,$input,'payment:'.$id)) return;
            $invoice = $this->cards->invoice($user,$id) ?? throw new DomainException('Fatura não encontrada.');
            $account = $this->account($user,(int)($input['conta_id'] ?? 0));
            $date = Dates::past($input['data'] ?? '');
            $amount = Money::toCents($input['valor'] ?? '');
            if ($invoice['status'] === 'cancelada' || $amount <= 0 || $amount > (int)$invoice['total']-(int)$invoice['pago']) throw new DomainException('O pagamento deve ser positivo e não pode exceder o saldo da fatura.');
            if ($date < $account['saldo_inicial_em']) throw new DomainException('O pagamento não pode anteceder o saldo inicial da conta.');
            foreach ($this->cards->items($user,$id) as $item) {
                if ($item['data_compra'] && $date < $item['data_compra']) throw new DomainException('O pagamento não pode anteceder as compras da fatura.');
            }
            $this->cards->payment($user,$id,(int)$account['id'],$amount,$date);
            $this->cards->close($user,$id);
        });
    }

    public function close(int $user, int $id): void
    {
        $invoice = $this->cards->invoice($user,$id) ?? throw new DomainException('Fatura não encontrada.');
        if ($invoice['status'] !== 'aberta') throw new DomainException('Esta fatura já está fechada.');
        $this->cards->close($user,$id);
    }

    public function deactivate(int $user, int $id): void
    {
        $this->get($user,$id);
        foreach ($this->cards->invoices($user,$id) as $invoice) {
            if ((int)$invoice['total'] !== (int)$invoice['pago']) throw new DomainException('Quite as faturas e utilize os créditos antes de desativar o cartão.');
        }
        $this->cards->deactivate($user,$id);
    }

    public function carryCredit(int $user,int $id,string $month): void
    {
        $this->cards->atomic(function() use($user,$id,$month) {
            $source=$this->cards->invoice($user,$id) ?? throw new DomainException('Fatura não encontrada.');
            $amount=(int)$source['pago']-(int)$source['total'];
            if($amount<=0 || Dates::month($month)<=$source['competencia']) throw new DomainException('Escolha uma fatura posterior para transportar o crédito disponível.');
            $target=$this->invoiceFor($user,$this->get($user,(int)$source['cartao_id']),$month);
            $full=$this->cards->invoice($user,(int)$target['id']);
            if($target['status']!=='aberta' || (int)$full['pago']>0) throw new DomainException('A fatura de destino precisa estar aberta, sem pagamentos.');
            $this->cards->credit($user,$id,(int)$target['id'],$amount);
            $this->cards->close($user,$id);
        });
    }
}
