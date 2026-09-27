<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\FinancialDate;
use App\Core\Money;
use App\Repositories\InvestmentRepository;
use DomainException;

final class InvestmentService
{
    private const TYPES = ['renda_fixa', 'renda_variavel', 'fundo', 'previdencia', 'cripto', 'outro'];

    public function __construct(private readonly InvestmentRepository $investments)
    {
    }

    public function create(int $user, array $input): void
    {
        $name = trim((string) ($input['nome'] ?? ''));
        $type = (string) ($input['tipo'] ?? '');
        $institution = trim((string) ($input['instituicao'] ?? ''));
        $goal = trim((string) ($input['objetivo'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            throw new DomainException('Informe o nome do investimento com até 100 caracteres.');
        }
        if (!in_array($type, self::TYPES, true)) {
            throw new DomainException('Selecione um tipo de investimento válido.');
        }
        if (mb_strlen($institution) > 100 || mb_strlen($goal) > 160) {
            throw new DomainException('Instituição e objetivo estão longos demais.');
        }
        $applied = Money::toCents((string) ($input['valor_aplicado'] ?? ''));
        $current = Money::toCents((string) ($input['valor_atual'] ?? ''));
        if ($applied <= 0 || $current < 0) {
            throw new DomainException('Informe valores válidos para o investimento.');
        }
        $this->investments->create($user, [$name, $type, $institution ?: null, $goal ?: null, $applied, $current, FinancialDate::date((string) ($input['data_inicio'] ?? ''))]);
    }

    public function deactivate(int $user, int $id): void
    {
        $this->investments->deactivate($user, $id);
    }

    public function updateCurrentValue(int $user, int $id, array $input): void
    {
        $amount = Money::toCents((string) ($input['valor_atual'] ?? ''));
        if (!$this->investments->updateCurrentValue($user, $id, $amount)) {
            throw new DomainException('Investimento ativo não encontrado.');
        }
    }
}
