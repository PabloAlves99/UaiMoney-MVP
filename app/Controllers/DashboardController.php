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
        $input = array_filter($_GET, fn($value) => is_string($value));
        $period = \App\Services\AnalyticsService::period($input, date('Y-m-d'));
        extract($period);
        $filters = array_filter(array_intersect_key($input, array_flip(['conta_id', 'cartao_id', 'grupo_id', 'subgrupo_id', 'tipo', 'meio_pagamento'])), fn($value) => $value !== '');
        $month = substr($end, 0, 7);
        $page = max(1, min(100000, (int)($input['pagina'] ?? 1)));
        $audit = ($input['conferir'] ?? '') === '1';
        $budgetAvailable = $singleMonth && substr($start, 8) === '01' && !array_intersect_key($filters, array_flip(['conta_id', 'cartao_id', 'subgrupo_id', 'meio_pagamento'])) && ($filters['tipo'] ?? '') !== 'receita';
        $this->page('dashboard/analytics', 'Análises', $period + [
            'month' => $month,
            'filters' => $filters,
            'totals' => $this->reports->totals($u, $start, $end, $filters),
            'previous' => $this->reports->totals($u, $previousStart, $previousEnd, $filters),
            'categoryRows' => $this->reports->breakdown($u, $start, $end, 'categoria', $filters),
            'previousCategories' => $this->reports->breakdown($u, $previousStart, $previousEnd, 'categoria', $filters),
            'subcategoryRows' => $this->reports->breakdown($u, $start, $end, 'subcategoria', $filters),
            'monthlyRows' => $this->reports->monthly($u, $seriesStart, $end, $filters),
            'budgets' => $budgetAvailable ? $this->reports->budgets($u, $month) : [],
            'budgetAvailable' => $budgetAvailable,
            'audit' => $audit,
            'auditPage' => $page,
            'entries' => $audit ? $this->reports->entries($u, $start, $end, $filters, $page) : [],
            'accounts' => $this->accounts->listActive($u),
            'cards' => $this->cards->list($u),
            'categories' => $this->reports->categories($u),
        ]);
    }
}
