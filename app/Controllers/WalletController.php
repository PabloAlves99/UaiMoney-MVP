<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\AccountRepository;
use App\Repositories\LedgerRepository;
use App\Repositories\ReportRepository;
use App\Services\AuthService;
use App\Services\LedgerService;
use App\Services\TransactionService;
use DomainException;

final class WalletController extends FinancialController
{
    public function __construct(
        AuthService $auth,
        Csrf $csrf,
        string $basePath,
        private readonly LedgerRepository $ledger,
        private readonly LedgerService $service,
        private readonly AccountRepository $accounts,
        private readonly ReportRepository $reports,
        private readonly TransactionService $transactions
    ) {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function index(): void
    {
        $u = (int) $this->requireUser()['id'];
        $accounts = $this->accounts->listActive($u);
        $id = (int) ($_GET['conta'] ?? ($accounts[0]['id'] ?? 0));
        $account = $id ? $this->accounts->findById($id, $u) : null;
        $page = max(1, (int) ($_GET['pagina'] ?? 1));
        $this->page('wallet/index', 'Conferir e transferir', ['accounts' => $accounts, 'account' => $account, 'entries' => $account ? $this->ledger->statement($u, $id, $page) : [], 'page' => $page]);
    }

    public function transfer(): void
    {
        $this->action('/carteira', fn($u) => $this->service->transfer($u, $_POST), 'Transferência registrada nas duas contas.');
    }

    public function reconcile(): void
    {
        $this->action('/carteira?conta=' . (int) ($_POST['conta_id'] ?? 0), fn($u) => $this->service->reconcile($u, $_POST), 'Conferência registrada. O ajuste pode ser consultado no histórico.');
    }

    public function start(): void
    {
        $u = (int) $this->requireUser()['id'];
        $this->page('wallet/start', 'Primeiros passos', ['accounts' => $this->accounts->listActive($u), 'categories' => $this->reports->categories($u)]);
    }

    public function seed(): void
    {
        $this->action('/comecar', fn($u) => $this->ledger->atomic(fn() => $this->ledger->startCategories($u)), 'Categorias sugeridas adicionadas. Você pode personalizá-las.');
    }

    public function detail(string $id): void
    {
        $u = (int) $this->requireUser()['id'];
        $t = $this->ledger->transaction($u, $this->parseId($id));
        if (!$t) {
            http_response_code(404);
            $this->page('errors/not-found', 'Movimentação não encontrada');
            return;
        }
        $this->page('wallet/detail', 'Detalhe da movimentação', ['t' => $t, 'refunds' => $this->ledger->refunds($u, (int) $id)]);
    }

    public function refund(string $id): void
    {
        $this->action('/movimentacoes/' . $this->parseId($id), fn($u) => $this->service->refund($u, (int) $id, $_POST), 'Estorno registrado. O lançamento original foi preservado.');
    }

    public function move(string $id): void
    {
        $this->action('/movimentacoes/' . $this->parseId($id), fn($u) => $this->service->movePurchase($u, (int) $id, (string) ($_POST['mes'] ?? '')), 'Fatura da compra atualizada.');
    }

    public function duplicate(string $id): void
    {
        $this->action('/movimentacoes', function ($u) use ($id) {
            $t = $this->ledger->transaction($u, $this->parseId($id)) ?? throw new DomainException('Movimentação não encontrada.');
            if ($t['cartao_id'])
                throw new DomainException('Para repetir uma compra no cartão, use Nova compra no cartão.');
            $this->transactions->create($u, (int) $t['subgrupo_id'], $t['conta_id'] ? (int) $t['conta_id'] : null, $t['descricao'], number_format((int) $t['valor_centavos'] / 100, 2, '.', ''), date('Y-m-d'), date('Y-m-d'), null, 'pendente', $t['meio_pagamento'], $t['observacao']);
        }, 'Cópia criada como pendente, sem vínculos de parcelamento ou recorrência.');
    }
}
