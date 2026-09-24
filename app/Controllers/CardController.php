<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\AccountRepository;
use App\Repositories\CardRepository;
use App\Repositories\ReportRepository;
use App\Services\AuthService;
use App\Services\CardService;
use DomainException;

final class CardController extends FinancialController
{
    public function __construct(
        AuthService $auth,
        Csrf $csrf,
        string $basePath,
        private readonly CardRepository $cards,
        private readonly CardService $service,
        private readonly AccountRepository $accounts,
        private readonly ReportRepository $reports
    ) {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function index(): void
    {
        $u = (int) $this->requireUser()['id'];
        $editing = isset($_GET['editar']) ? $this->cards->find($u, (int) $_GET['editar']) : null;
        $this->page('cards/index', 'Cartões', ['cards' => $this->cards->list($u), 'accounts' => $this->accounts->listActive($u), 'editing' => $editing]);
    }

    public function save(): void
    {
        $this->action('/cartoes', fn($u) => $this->service->save($u, !empty($_POST['id']) ? $this->parseId($_POST['id']) : null, $_POST), 'Cartão salvo. As datas de faturas existentes foram preservadas.');
    }

    public function deactivate(string $id): void
    {
        $this->action('/cartoes', fn($u) => $this->service->deactivate($u, $this->parseId($id)), 'Cartão desativado. Histórico preservado.');
    }

    public function show(string $id): void
    {
        $u = (int) $this->requireUser()['id'];
        $card = $this->cards->find($u, $this->parseId($id));
        if (!$card) {
            http_response_code(404);
            $this->page('errors/not-found', 'Cartão não encontrado');
            return;
        }
        foreach ($this->cards->list($u) as $item) {
            if ((int) $item['id'] === (int) $id) $card = $item;
        }
        $this->page('cards/show', $card['nome'], ['card' => $card, 'invoices' => $this->cards->invoices($u, (int) $id), 'categories' => $this->reports->categories($u)]);
    }

    public function purchase(): void
    {
        $id = $this->parseId((string) ($_POST['cartao_id'] ?? '0'));
        $this->action('/cartoes/' . $id, fn($u) => $this->service->purchase($u, $_POST), 'Compra registrada e distribuída nas faturas.');
    }

    public function invoice(string $id): void
    {
        $u = (int) $this->requireUser()['id'];
        $invoice = $this->cards->invoice($u, $this->parseId($id));
        if (!$invoice) {
            http_response_code(404);
            $this->page('errors/not-found', 'Fatura não encontrada');
            return;
        }
        $this->page('cards/invoice', 'Fatura ' . $invoice['competencia'], ['invoice' => $invoice, 'items' => $this->cards->items($u, (int) $id), 'payments' => $this->cards->payments($u, (int) $id), 'credits' => $this->cards->credits($u, (int) $id), 'accounts' => $this->accounts->listActive($u)]);
    }

    public function pay(string $id): void
    {
        $this->action('/faturas/' . $this->parseId($id), fn($u) => $this->service->pay($u, (int) $id, $_POST), 'Pagamento registrado na conta, sem duplicar despesas.');
    }

    public function close(string $id): void
    {
        $this->action('/faturas/' . $this->parseId($id), fn($u) => $this->service->close($u, (int) $id), 'Fatura fechada.');
    }

    public function carry(string $id): void
    {
        $this->action('/faturas/' . $this->parseId($id), fn($u) => $this->service->carryCredit($u, (int) $id, (string) ($_POST['mes'] ?? '')), 'Crédito transportado para a próxima fatura escolhida.');
    }
}
