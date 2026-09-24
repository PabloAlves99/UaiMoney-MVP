<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\ActivityRepository;
use App\Repositories\AccountRepository;
use App\Repositories\CardRepository;
use App\Repositories\ReportRepository;
use App\Services\AuthService;

final class ActivityController extends FinancialController
{
    public function __construct(
        AuthService $auth,
        Csrf $csrf,
        string $basePath,
        private readonly ActivityRepository $activity,
        private readonly AccountRepository $accounts,
        private readonly CardRepository $cards,
        private readonly ReportRepository $reports
    ) {
        parent::__construct($auth, $csrf, $basePath);
    }

    private function filters(): array
    {
        return array_filter(array_intersect_key($_GET, array_flip(['q', 'status', 'tipo', 'conta_id', 'cartao_id', 'grupo_id', 'subgrupo_id', 'meio_pagamento', 'data_inicio', 'data_fim', 'lixeira'])), fn($v) => is_string($v) && $v !== '');
    }

    public function index(): void
    {
        $u = (int) $this->requireUser()['id'];
        $filters = $this->filters();
        $page = max(1, min(100000, (int) ($_GET['pagina'] ?? 1)));
        $this->page('activity/index', 'Movimentações', ['entries' => $this->activity->list($u, $filters, $page), 'total' => $this->activity->count($u, $filters), 'filters' => $filters, 'page' => $page, 'accounts' => $this->accounts->listActive($u), 'cards' => $this->cards->list($u), 'categories' => $this->reports->categories($u)]);
    }

    public function export(): void
    {
        $u = (int) $this->requireUser()['id'];
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="uaimoney-movimentacoes-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['ID', 'Descrição', 'Tipo', 'Categoria', 'Subcategoria', 'Valor (centavos)', 'Estornado (centavos)', 'Competência', 'Vencimento', 'Efetivação', 'Status', 'Conta', 'Cartão', 'Meio', 'Parcelamento', 'Recorrência', 'Fatura'], ';', '"', '');
        foreach ($this->activity->export($u, $this->filters()) as $row) {
            $row = array_map(static function ($value) {
                $value = (string) ($value ?? '');
                return preg_match('/^[\s]*[=+@-]/u', $value) ? "'" . $value : $value;
            }, $row);
            fputcsv($out, $row, ';', '"', '');
        }
        fclose($out);
    }

    public function installment(): void
    {
        $this->requireUser();
        $this->redirect('/movimentacoes/nova?modo=parcelado');
    }
}
