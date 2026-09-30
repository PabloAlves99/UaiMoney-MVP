<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use DateTimeImmutable;
use DomainException;

final class InvestmentProjectionService
{
    private const MAX_MONTHS = 1200;

    /**
     * Projeta juros compostos mensais com o aporte realizado ao fim de cada mês.
     *
     * @return array<string, mixed>
     */
    public function calculate(array $input): array
    {
        $initial = Money::toCents((string) ($input['valor_inicial'] ?? ''));
        $contribution = Money::toCents((string) ($input['aporte_mensal'] ?? ''));
        $target = Money::toCents((string) ($input['valor_meta'] ?? ''));
        $ratePercent = $this->ratePercent((string) ($input['taxa'] ?? ''));
        $ratePeriod = (string) ($input['periodicidade_taxa'] ?? 'mensal');
        $startMonth = trim((string) ($input['mes_inicio'] ?? date('Y-m')));

        return $this->calculateAmounts($initial, $contribution, $target, $ratePercent, $ratePeriod, $startMonth);
    }

    /** @return array<string, mixed> */
    public function calculateAmounts(int $initial, int $contribution, int $target, float $ratePercent, string $ratePeriod, string $startMonth): array
    {
        if ($initial < 0 || $contribution < 0) {
            throw new DomainException('O valor atual e o aporte não podem ser negativos.');
        }
        if ($ratePercent < 0 || $ratePercent > 100) {
            throw new DomainException('A taxa deve estar entre 0% e 100%.');
        }

        if (!in_array($ratePeriod, ['mensal', 'anual'], true)) {
            throw new DomainException('Selecione se a taxa informada é mensal ou anual.');
        }
        if (!$this->validMonth($startMonth)) {
            throw new DomainException('Informe um mês inicial válido.');
        }
        if ($target <= 0) {
            throw new DomainException('A meta precisa ser maior que zero.');
        }
        if ($initial === 0 && $contribution === 0) {
            throw new DomainException('Informe um valor inicial ou um aporte mensal.');
        }

        $monthlyRate = $ratePeriod === 'anual'
            ? pow(1 + ($ratePercent / 100), 1 / 12) - 1
            : $ratePercent / 100;

        if ($initial < $target && $monthlyRate === 0.0 && $contribution === 0) {
            throw new DomainException('Com aporte e taxa iguais a zero, a meta não será alcançada.');
        }

        $balance = $initial;
        $months = 0;
        $milestones = [];
        $series = [];
        $milestonePercentages = [25, 50, 75, 100];

        $this->captureMilestones($milestones, $milestonePercentages, $balance, $target, $months, $startMonth);
        $series[] = $this->seriesPoint($balance, $initial, $contribution, $months, $startMonth);

        while ($balance < $target && $months < self::MAX_MONTHS) {
            $balance = (int) round($balance * (1 + $monthlyRate)) + $contribution;
            $months++;
            $this->captureMilestones($milestones, $milestonePercentages, $balance, $target, $months, $startMonth);
            if ($months % 12 === 0 || $balance >= $target) {
                $series[] = $this->seriesPoint($balance, $initial, $contribution, $months, $startMonth);
            }
        }

        if ($balance < $target) {
            throw new DomainException('A meta não foi alcançada em até 100 anos. Aumente o aporte ou a taxa esperada.');
        }

        $totalContributed = $initial + ($contribution * $months);
        $monthsWithoutInterest = $initial >= $target
            ? 0
            : ($contribution > 0 ? (int) ceil(($target - $initial) / $contribution) : null);

        return [
            'months' => $months,
            'years' => intdiv($months, 12),
            'remaining_months' => $months % 12,
            'estimated_month' => $this->monthAt($startMonth, $months),
            'initial_cents' => $initial,
            'monthly_contribution_cents' => $contribution,
            'target_cents' => $target,
            'final_balance_cents' => $balance,
            'total_contributed_cents' => $totalContributed,
            'interest_cents' => max(0, $balance - $totalContributed),
            'monthly_rate_percent' => $monthlyRate * 100,
            'annual_rate_percent' => (pow(1 + $monthlyRate, 12) - 1) * 100,
            'future_contributions_cents' => $contribution * $months,
            'current_progress_percent' => min(100, $target > 0 ? $initial / $target * 100 : 0),
            'interest_share_percent' => $balance > 0 ? max(0, ($balance - $totalContributed) / $balance * 100) : 0,
            'months_without_interest' => $monthsWithoutInterest,
            'months_saved' => $monthsWithoutInterest === null ? null : max(0, $monthsWithoutInterest - $months),
            'milestones' => array_values($milestones),
            'series' => $series,
        ];
    }

    public function ratePercent(string $value): float
    {
        $value = str_replace(',', '.', trim($value));
        if (!preg_match('/^\d{1,3}(?:\.\d{1,4})?$/', $value)) {
            throw new DomainException('Informe uma taxa válida, como 1 ou 10,5.');
        }

        $percentage = (float) $value;
        if ($percentage < 0 || $percentage > 100) {
            throw new DomainException('A taxa deve estar entre 0% e 100%.');
        }

        return $percentage;
    }

    /** @return array<string, int|string> */
    private function seriesPoint(int $balance, int $initial, int $contribution, int $months, string $startMonth): array
    {
        $contributed = $initial + ($contribution * $months);
        return [
            'months' => $months,
            'month' => $this->monthAt($startMonth, $months),
            'balance_cents' => $balance,
            'contributed_cents' => $contributed,
            'interest_cents' => max(0, $balance - $contributed),
        ];
    }

    private function validMonth(string $month): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], 1, (int) $matches[1]);
    }

    private function monthAt(string $startMonth, int $offset): string
    {
        return (new DateTimeImmutable($startMonth . '-01'))
            ->modify('+' . $offset . ' months')
            ->format('Y-m');
    }

    /**
     * @param array<int, array<string, int|string>> $captured
     * @param list<int> $percentages
     */
    private function captureMilestones(array &$captured, array $percentages, int $balance, int $target, int $months, string $startMonth): void
    {
        foreach ($percentages as $percentage) {
            if (isset($captured[$percentage])) {
                continue;
            }

            $threshold = (int) ceil($target * ($percentage / 100));
            if ($balance >= $threshold) {
                $captured[$percentage] = [
                    'percentage' => $percentage,
                    'months' => $months,
                    'month' => $this->monthAt($startMonth, $months),
                    'balance_cents' => $balance,
                ];
            }
        }
    }
}
