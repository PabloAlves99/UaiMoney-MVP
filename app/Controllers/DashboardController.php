<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Csrf;
use App\Core\FinancialDate as Dates;
use App\Repositories\AccountRepository;
use App\Repositories\CardRepository;
use App\Repositories\ReportRepository;
use App\Services\AuthService;
use App\Services\PlanningService;
use DomainException;

final class DashboardController extends FinancialController
{
    public function __construct(
        AuthService $auth,
        Csrf $csrf,
        string $basePath,
        private readonly ReportRepository $reports,
        private readonly AccountRepository $accounts,
        private readonly CardRepository $cards,
        private readonly PlanningService $planning
    ) {
        parent::__construct($auth, $csrf, $basePath);
    }

    private function month(): string
    {
        try {
            return Dates::month((string) ($_GET['mes'] ?? date('Y-m')));
        } catch (DomainException) {
            return date('Y-m');
        }
    }

    public function index(): void
    {
        $u = (int) $this->requireUser()['id'];
        $month = $this->month();
        $accounts = $this->accounts->listActive($u);
        $invoices = $this->cards->invoices($u);
        $end = date('Y-m-t');
        $debt = 0;
        foreach ($invoices as $f)
            if ($f['status'] !== 'cancelada' && $f['data_vencimento'] <= $end)
                $debt += max(0, (int) $f['total'] - (int) $f['pago']);
        $balance = array_sum(array_column($accounts, 'saldo_atual_centavos'));
        $this->page('dashboard/index', 'Visão geral', [
            'month' => $month,
            'accounts' => $accounts,
            'invoices' => $invoices,
            'balance' => $balance,
            'projection' => $balance + $this->reports->pendingCash($u, $end) - $debt,
            'totals' => $this->reports->totals($u, $month . '-01', Dates::day($month, 31)),
            'upcoming' => $this->reports->upcoming($u),
            'budgets' => $this->reports->budgets($u, $month),
            'categories' => $this->reports->categories($u),
            'series' => $this->reports->breakdown($u, Dates::shift($month, -5) . '-01', Dates::day($month, 31), 'mes'),
        ]);
    }

    public function budget(): void
    {
        $u = (int) $this->requireUser()['id'];
        $month = $this->month();
        $this->page('planning/index', 'Planejamento', ['month' => $month, 'budgets' => $this->reports->budgets($u, $month), 'categories' => $this->reports->categories($u)]);
    }

    public function saveBudget(): void
    {
        $this->action('/planejamento?mes=' . urlencode((string) ($_POST['mes'] ?? date('Y-m'))), fn($u) => $this->planning->save($u, $_POST), 'Orçamento salvo.');
    }

    public function copyBudget(): void
    {
        $month = (string) ($_POST['mes'] ?? '');
        $this->action('/planejamento?mes=' . urlencode($month), fn($u) => $this->planning->copy($u, $month), 'Limites do mês anterior copiados. Os limites já existentes foram mantidos.');
    }

    public function deleteBudget(string $id): void
    {
        $this->action('/planejamento?mes=' . urlencode((string) ($_POST['mes'] ?? date('Y-m'))), fn($u) => $this->reports->deleteBudget($u, $this->parseId($id)), 'Limite removido deste mês.');
    }

    public function analytics(): void
    {
        $u = (int) $this->requireUser()['id'];
        $month = $this->month();
        try {
            $start = Dates::date((string) ($_GET['inicio'] ?? $month . '-01'));
            $end = Dates::date((string) ($_GET['fim'] ?? Dates::day($month, 31)));
        } catch (DomainException) {
            $start = $month . '-01';
            $end = Dates::day($month, 31);
        }
        if ($start > $end)
            [$start, $end] = [$end, $start];
        $filters = array_intersect_key($_GET, array_flip(['conta_id', 'cartao_id', 'grupo_id', 'subgrupo_id', 'tipo', 'meio_pagamento']));
        $dimension = (string) ($_GET['agrupar'] ?? 'categoria');
        $this->page('dashboard/analytics', 'Análises', [
            'month' => $month,
            'start' => $start,
            'end' => $end,
            'filters' => $filters,
            'dimension' => $dimension,
            'totals' => $this->reports->totals($u, $start, $end, $filters),
            'previous' => $this->reports->totals($u, Dates::shift($month, -1) . '-01', Dates::day(Dates::shift($month, -1), 31), $filters),
            'current' => $this->reports->totals($u, $month . '-01', Dates::day($month, 31), $filters),
            'rows' => $this->reports->breakdown($u, $start, $end, $dimension, $filters),
            'top' => $this->reports->top($u, $start, $end, $filters),
            'accounts' => $this->accounts->listActive($u),
            'cards' => $this->cards->list($u),
            'categories' => $this->reports->categories($u),
        ]);
    }
}
