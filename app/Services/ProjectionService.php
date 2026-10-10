<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use App\Repositories\ProjectionRepository;
use DomainException;

final class ProjectionService
{
    public function __construct(
        private readonly ProjectionRepository $projections,
        private readonly InvestmentProjectionService $calculator
    ) {}

    public function list(int $user): array
    {
        return array_map(function (array $projection): array {
            try {
                $projection['result'] = $this->calculateRow($projection);
                $projection['error'] = null;
            } catch (DomainException $error) {
                $projection['result'] = null;
                $projection['error'] = $error->getMessage();
            }
            return $projection;
        }, $this->projections->list($user));
    }

    public function detail(int $user, int $id): array
    {
        $projection = $this->projections->find($user, $id);
        if (!$projection) throw new DomainException('Projeção não encontrada.');
        $projection['result'] = $this->calculateRow($projection);
        $sourceId = (int) ($projection['investimento_id'] ?: $projection['objetivo_id']);
        $projection['history'] = $this->projections->history($user, (string) $projection['origem_tipo'], $sourceId);
        return $projection;
    }

    public function create(int $user, array $input): int
    {
        $data = $this->validated($user, $input);
        return $this->projections->create($user, $data);
    }

    public function update(int $user, int $id, array $input): void
    {
        if (!$this->projections->find($user, $id)) throw new DomainException('Projeção não encontrada.');
        if (!$this->projections->update($user, $id, $this->validated($user, $input))) {
            throw new DomainException('A projeção não pôde ser atualizada.');
        }
    }

    public function delete(int $user, int $id): void
    {
        if (!$this->projections->delete($user, $id)) throw new DomainException('Projeção não encontrada.');
    }

    private function validated(int $user, array $input): array
    {
        $name = trim((string) ($input['nome'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) throw new DomainException('Informe um nome de cenário com até 100 caracteres.');

        $origin = explode(':', (string) ($input['origem'] ?? ''), 2);
        $type = $origin[0] ?? '';
        $sourceId = filter_var($origin[1] ?? null, FILTER_VALIDATE_INT);
        if (!in_array($type, ['investimento', 'objetivo'], true) || !$sourceId || $sourceId < 1) {
            throw new DomainException('Selecione um investimento ou objetivo válido.');
        }
        $source = $this->projections->source($user, $type, (int) $sourceId);
        if (!$source || !(int) $source['ativo']) throw new DomainException('A origem selecionada não está ativa ou não pertence a você.');

        $contribution = Money::toCents((string) ($input['aporte_mensal'] ?? ''));
        $rate = $this->calculator->ratePercent((string) ($input['taxa'] ?? ''));
        $period = (string) ($input['periodicidade_taxa'] ?? '');
        if (!in_array($period, ['mensal', 'anual'], true)) throw new DomainException('Selecione a periodicidade da taxa.');

        $target = $type === 'objetivo'
            ? (int) $source['meta_centavos']
            : Money::toCents((string) ($input['valor_meta'] ?? ''));
        $storedTarget = $type === 'investimento' ? $target : null;

        $this->calculator->calculateAmounts((int) $source['atual_centavos'], $contribution, $target, $rate, $period, date('Y-m'));

        return [
            $name,
            $type,
            $type === 'investimento' ? (int) $sourceId : null,
            $type === 'objetivo' ? (int) $sourceId : null,
            $contribution,
            (int) round($rate * 10000),
            $period,
            $storedTarget,
        ];
    }

    private function calculateRow(array $projection): array
    {
        return $this->calculator->calculateAmounts(
            (int) $projection['valor_inicial_centavos'],
            (int) $projection['aporte_mensal_centavos'],
            (int) $projection['meta_calculada_centavos'],
            (int) $projection['taxa_quatro_casas'] / 10000,
            (string) $projection['periodicidade_taxa'],
            date('Y-m')
        );
    }
}
