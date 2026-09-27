<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\InvestmentRepository;
use App\Services\AuthService;
use App\Services\InvestmentService;

final class InvestmentController extends FinancialController
{
    public function __construct(AuthService $auth, Csrf $csrf, string $basePath, private readonly InvestmentRepository $investments, private readonly InvestmentService $service)
    {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function index(): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->page('investments/index', 'Investimentos', ['investments' => $this->investments->list($user), 'summary' => $this->investments->summary($user)]);
    }

    public function store(): void
    {
        $this->action('/investimentos', fn(int $user) => $this->service->create($user, $_POST), 'Investimento adicionado. Atualize o valor atual sempre que quiser acompanhar sua posição.');
    }

    public function deactivate(string $id): void
    {
        $this->action('/investimentos', fn(int $user) => $this->service->deactivate($user, $this->parseId($id)), 'Investimento arquivado.');
    }

    public function updateCurrentValue(string $id): void
    {
        $this->action('/investimentos', fn(int $user) => $this->service->updateCurrentValue($user, $this->parseId($id), $_POST), 'Valor atual do investimento atualizado.');
    }
}
