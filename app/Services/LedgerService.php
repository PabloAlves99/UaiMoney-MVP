<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\FinancialDate as Dates;
use App\Core\Money;
use App\Repositories\LedgerRepository;
use App\Repositories\AccountRepository;
use App\Repositories\CardRepository;
use DomainException;

final class LedgerService
{
    public function __construct(private readonly LedgerRepository $ledger, private readonly AccountRepository $accounts, private readonly CardRepository $cards, private readonly CardService $cardService)
    {
    }

    public function transfer(int $user, array $input): void
    {
        $this->ledger->atomic(function () use ($user, $input) {
            if (!$this->ledger->once($user, $input, 'transfer'))
                return;
            $from = $this->cardService->account($user, (int) ($input['origem_id'] ?? 0));
            $to = $this->cardService->account($user, (int) ($input['destino_id'] ?? 0));
            $amount = Money::toCents($input['valor'] ?? '');
            $date = Dates::past($input['data'] ?? '');
            if ($from['id'] === $to['id'] || $amount <= 0)
                throw new DomainException('Selecione contas diferentes e um valor positivo.');
            if ($date < max($from['saldo_inicial_em'], $to['saldo_inicial_em']))
                throw new DomainException('A transferência deve ser posterior ou igual ao saldo inicial das duas contas.');
            $this->ledger->transfer($user, (int) $from['id'], (int) $to['id'], $amount, $date, 'Transferência · ' . $from['nome'] . ' → ' . $to['nome']);
        });
    }

    public function reconcile(int $user, array $input): void
    {
        $this->ledger->atomic(function () use ($user, $input) {
            if (!$this->ledger->once($user, $input, 'reconcile'))
                return;
            $account = $this->cardService->account($user, (int) ($input['conta_id'] ?? 0));
            if ($account['saldo_inicial_em'] > date('Y-m-d'))
                throw new DomainException('O saldo inicial desta conta está no futuro.');
            $raw = trim($input['saldo'] ?? '');
            $amount = Money::toSignedCents($raw);
            $reason = trim($input['motivo'] ?? '');
            if ($reason === '' || mb_strlen($reason) > 200)
                throw new DomainException('Explique a conferência em até 200 caracteres.');
            $this->ledger->reconcile($user, (int) $account['id'], (int) $account['saldo_atual_centavos'], $amount, $reason);
        });
    }

    public function refund(int $user, int $id, array $input): void
    {
        $this->ledger->atomic(function () use ($user, $id, $input) {
            if (!$this->ledger->once($user, $input, 'refund:' . $id))
                return;
            $t = $this->ledger->transaction($user, $id) ?? throw new DomainException('Movimentação não encontrada.');
            $amount = Money::toCents($input['valor'] ?? '');
            $date = Dates::past($input['data'] ?? '');
            $reason = trim($input['motivo'] ?? '');
            if ($t['status'] !== 'efetivada' || $amount <= 0 || $amount > (int) $t['valor_centavos'] - (int) $t['estornado'])
                throw new DomainException('O estorno deve respeitar o valor ainda não estornado de uma movimentação efetivada.');
            if ($reason === '' || mb_strlen($reason) > 200)
                throw new DomainException('Informe o motivo do estorno em até 200 caracteres.');
            if (!$t['cartao_id'] && $date < $t['data_efetivacao'])
                throw new DomainException('O estorno não pode anteceder a efetivação.');
            if (!$t['cartao_id']) {
                $account = $this->accounts->findById((int) $t['conta_id'], $user);
                if (!$account || !(int) $account['ativo'])
                    throw new DomainException('Reative a conta de origem em Contas antes de registrar o estorno.');
            }
            if ($t['cartao_id'] && $t['data_compra'] && $date < $t['data_compra'])
                throw new DomainException('O estorno não pode anteceder a compra.');
            $invoiceId = null;
            if ($t['cartao_id']) {
                $card = $this->cardService->get($user, (int) $t['cartao_id']);
                $month = !empty($input['fatura_mes']) ? Dates::month($input['fatura_mes']) : $this->cardService->cycle($card, $date);
                $invoice = $this->cardService->invoiceFor($user, $card, $month);
                $full = $this->cards->invoice($user, (int) $invoice['id']);
                if ($invoice['status'] !== 'aberta' || (int) $full['pago'] > 0)
                    throw new DomainException('Escolha uma fatura aberta, sem pagamentos, para receber o crédito.');
                $invoiceId = (int) $invoice['id'];
            }
            $this->ledger->refund($user, $id, $invoiceId, $amount, $date, $reason);
        });
    }

    public function movePurchase(int $user, int $id, string $month): void
    {
        $this->ledger->atomic(function () use ($user, $id, $month) {
            $t = $this->ledger->transaction($user, $id) ?? throw new DomainException('Compra não encontrada.');
            if (!$t['cartao_id'] || $t['status'] === 'cancelada' || (int) $t['estornado'] > 0)
                throw new DomainException('Esta compra não pode ser movida.');
            $source = $this->cards->invoice($user, (int) $t['fatura_id']);
            $target = $this->cardService->invoiceFor($user, $this->cardService->get($user, (int) $t['cartao_id']), $month);
            $full = $this->cards->invoice($user, (int) $target['id']);
            if ($source['status'] !== 'aberta' || $target['status'] !== 'aberta' || (int) $source['pago'] > 0 || (int) $full['pago'] > 0)
                throw new DomainException('Só é possível mover entre faturas abertas e sem pagamentos.');
            $this->cards->move($user, $id, $target);
        });
    }
}
