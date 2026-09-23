<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\FinancialDate as Dates;
use App\Core\Money;
use App\Repositories\ReportRepository;
use DomainException;

final class PlanningService
{
    public function __construct(private readonly ReportRepository $reports) {}

    public function save(int $user, array $input): void
    {
        $month = Dates::month($input['mes'] ?? '');
        $group = (int)($input['grupo_id'] ?? 0);
        $valid = false;
        foreach ($this->reports->categories($user) as $category) {
            if ((int)$category['grupo_id']===$group && $category['tipo']==='despesa') $valid=true;
        }
        if (!$valid) throw new DomainException('Selecione uma categoria de despesa ativa.');
        $amount = Money::toCents($input['valor'] ?? '');
        if ($amount<=0) throw new DomainException('O limite deve ser positivo.');
        $this->reports->saveBudget($user,$month,$group,$amount);
    }

    public function copy(int $user, string $month): void
    {
        Dates::month($month);
        $this->reports->copyBudgets($user,Dates::shift($month,-1),$month);
    }
}
