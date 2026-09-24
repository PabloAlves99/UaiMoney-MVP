<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\FinancialDate as Dates;
use App\Core\Money;
use App\Repositories\AccountRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\MovementRepository;
use DomainException;

final class MovementService
{
    public function __construct(
        private readonly MovementRepository $movements,
        private readonly CategoryRepository $categories,
        private readonly AccountRepository $accounts,
        private readonly TransactionService $transactions,
        private readonly CardService $cards,
        private readonly InstallmentService $installments,
        private readonly RecurrenceService $recurrences
    ) {
    }

    public function get(int $user, int $id): array
    {
        return $this->movements->find($user, $id)
            ?? throw new DomainException('Movimentação não encontrada.');
    }

    public function create(int $user, array $input): void
    {
        $category = $this->categories->findSubgroupById((int) ($input['subgrupo_id'] ?? 0), $user);
        if (!$category || !(int) $category['ativo'] || !(int) $category['grupo_ativo']) {
            throw new DomainException('Selecione uma categoria ativa.');
        }
        if (($input['tipo'] ?? '') !== $category['tipo']) {
            throw new DomainException('Selecione uma categoria do mesmo tipo do lançamento.');
        }
        $description = trim((string) ($input['descricao'] ?? '')) ?: $category['nome'];
        $date = Dates::date((string) ($input['data'] ?? ''));
        $amount = (string) ($input['valor'] ?? '');
        $mode = (string) ($input['modo'] ?? 'simples');
        $account = !empty($input['conta_id']) ? (int) $input['conta_id'] : null;
        $method = (string) ($input['meio_pagamento'] ?? '');
        $notes = trim((string) ($input['observacao'] ?? ''));
        if (mb_strlen($description) > 160 || mb_strlen($notes) > 500) {
            throw new DomainException('Use até 160 caracteres na descrição e 500 nas observações.');
        }
        if ($method === 'credito') {
            if ($mode === 'recorrente' || $category['tipo'] !== 'despesa') {
                throw new DomainException('Para crédito, registre uma despesa avulsa ou parcelada.');
            }
            $this->cards->purchase($user, array_replace($input, [
                'descricao' => $description,
                'parcelas' => $mode === 'parcelado' ? ($input['parcelas'] ?? 1) : 1,
            ]));
            return;
        }
        if ($mode === 'parcelado') {
            $this->installments->create(
                $user, (int) $category['id'], $account, $description, $amount,
                (int) ($input['parcelas'] ?? 0), $date, $method, $notes
            );
            return;
        }
        if ($mode === 'recorrente') {
            $this->recurrences->create(
                $user, (int) $category['id'], $account, $description, $amount,
                (string) ($input['frequencia'] ?? 'mensal'), 1, $date,
                !empty($input['ocorrencias']) ? (int) $input['ocorrencias'] : null,
                $method, $notes
            );
            return;
        }
        if ($mode !== 'simples') throw new DomainException('Selecione uma forma de lançamento válida.');
        $status = (string) ($input['status'] ?? 'efetivada');
        $competence = !empty($input['data_competencia']) ? Dates::date($input['data_competencia']) : $date;
        if ($status === 'efetivada' && $account) {
            $selected = $this->accounts->findById($account, $user);
            if ($selected && $date < $selected['saldo_inicial_em']) {
                throw new DomainException('A data não pode anteceder o saldo inicial da conta.');
            }
        }
        $this->movements->atomic(function () use ($user, $input, $category, $account, $description, $amount, $competence, $date, $status, $method, $notes) {
            if (!$this->movements->once($user, $input, 'movement:create')) return;
            $this->transactions->create(
                $user, (int) $category['id'], $account, $description, $amount,
                $competence, $date, $status === 'efetivada' ? $date : null,
                $status, $method, $notes
            );
        });
    }

    private function current(int $user, int $id, array $input): array
    {
        $movement = $this->get($user, $id);
        if ((int) ($input['versao'] ?? 0) !== (int) $movement['versao']) {
            throw new DomainException('Este lançamento mudou desde que você abriu a página. Atualize antes de continuar.');
        }
        return $movement;
    }

    public function update(int $user, int $id, array $input): void
    {
        $this->movements->atomic(function () use ($user, $id, $input) {
            if (!$this->movements->once($user, $input, 'movement:update:' . $id)) return;
            $movement = $this->current($user, $id, $input);
            if ($movement['excluida_em']) throw new DomainException('Restaure o lançamento antes de editar.');
            $category = $this->categories->findSubgroupById((int) ($input['subgrupo_id'] ?? 0), $user);
            if (!$category || ((int) $category['id'] !== (int) $movement['subgrupo_id'] && (!(int) $category['ativo'] || !(int) $category['grupo_ativo']))) {
                throw new DomainException('Selecione uma categoria válida.');
            }
            if (($input['tipo'] ?? '') !== $category['tipo']) {
                throw new DomainException('Selecione uma categoria do mesmo tipo do lançamento.');
            }
            $description = trim((string) ($input['descricao'] ?? ''));
            $notes = trim((string) ($input['observacao'] ?? ''));
            $amount = Money::toCents((string) ($input['valor'] ?? ''));
            $status = (string) ($input['status'] ?? '');
            if ($description === '' || mb_strlen($description) > 160 || mb_strlen($notes) > 500) {
                throw new DomainException('Informe uma descrição de até 160 caracteres e observações de até 500.');
            }
            if ($amount <= 0 || $amount < (int) $movement['estornado']) {
                throw new DomainException('O valor precisa ser positivo e cobrir os estornos já registrados.');
            }
            if (!in_array($status, ['pendente', 'efetivada', 'cancelada'], true)) {
                throw new DomainException('Selecione uma situação válida.');
            }
            $competence = Dates::date((string) ($input['data_competencia'] ?? ''));
            $due = Dates::date((string) ($input['data_vencimento'] ?? ''));
            $paid = $status === 'efetivada' ? Dates::past((string) ($input['data_efetivacao'] ?? '')) : null;
            $account = !empty($input['conta_id']) ? (int) $input['conta_id'] : null;
            $method = trim((string) ($input['meio_pagamento'] ?? ''));
            if ($movement['cartao_id']) {
                if ($category['tipo'] !== 'despesa' || $status === 'pendente') {
                    throw new DomainException('Uma compra no cartão deve ser uma despesa registrada ou cancelada.');
                }
                $account = null;
                $method = 'credito';
                $due = $movement['data_vencimento'];
                $paid = $status === 'efetivada' ? ($movement['data_compra'] ?? $movement['data_efetivacao']) : null;
            } else {
                if (!in_array($method, ['', 'pix', 'dinheiro', 'debito', 'boleto', 'transferencia', 'outro'], true)) {
                    throw new DomainException('Selecione um meio de pagamento válido.');
                }
                $selected = $account ? $this->accounts->findById($account, $user) : null;
                if (($account && (!$selected || (!(int) $selected['ativo'] && $account !== (int) $movement['conta_id']))) || ($status === 'efetivada' && !$selected)) {
                    throw new DomainException('Selecione uma conta válida para o lançamento.');
                }
                if ($paid && $selected && $paid < $selected['saldo_inicial_em']) {
                    throw new DomainException('O pagamento não pode anteceder o saldo inicial da conta.');
                }
            }
            if ((int) $movement['estornado'] > 0 && ($status !== $movement['status'] || $category['tipo'] !== $movement['tipo'] || $account !== ($movement['conta_id'] ? (int) $movement['conta_id'] : null) || $paid !== $movement['data_efetivacao'])) {
                throw new DomainException('Com estornos, mantenha o tipo, a conta, a situação e a data do pagamento. Descrição, categoria do mesmo tipo e valor podem ser corrigidos.');
            }
            $this->movements->record($user, $movement, 'Edição');
            $this->movements->update($user, $id, [
                $category['id'], $account, $description, $amount, $competence, $due,
                $paid, $status, $method ?: null, $notes ?: null,
            ]);
        });
    }

    public function delete(int $user, int $id, array $input): void
    {
        $this->movements->atomic(function () use ($user, $id, $input) {
            if (!$this->movements->once($user, $input, 'movement:delete:' . $id)) return;
            $movement = $this->current($user, $id, $input);
            if ($movement['excluida_em']) return;
            $this->movements->record($user, $movement, 'Exclusão');
            $this->movements->delete($user, $id);
        });
    }

    public function restore(int $user, int $id, array $input): void
    {
        $this->movements->atomic(function () use ($user, $id, $input) {
            if (!$this->movements->once($user, $input, 'movement:restore:' . $id)) return;
            $movement = $this->current($user, $id, $input);
            if (!$movement['excluida_em']) return;
            $this->movements->record($user, $movement, 'Restauração');
            $this->movements->restore($user, $id);
        });
    }
}
