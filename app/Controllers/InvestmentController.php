<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\InvestmentRepository;
use App\Services\AuthService;
use App\Services\InvestmentService;

final class InvestmentController extends FinancialController
{
    public function __construct(
        AuthService $auth,
        Csrf $csrf,
        string $basePath,
        private readonly InvestmentRepository $investments,
        private readonly InvestmentService $service
    )
    {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function index(): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->page('investments/index', 'Investimentos', [
            'investments' => $this->investments->list($user),
            'summary' => $this->investments->summary($user),
        ]);
    }

    public function store(): void
    {
        $this->action('/investimentos', fn(int $user) => $this->service->create($user, $_POST), 'Investimento adicionado. Registre aportes, retiradas e rendimentos no histórico.');
    }

    public function show(string $id): void
    {
        $user=(int)$this->requireUser()['id'];$investment=$this->investments->find($user,$this->parseId($id));
        if (!$investment) { http_response_code(404);$this->page('errors/not-found','Investimento não encontrado');return; }
        $this->page('investments/show','Detalhes do investimento',['investment'=>$investment,'events'=>$this->investments->events($user,(int)$investment['id'])]);
    }

    public function update(string $id): void { $this->action('/investimentos/'.$id,fn(int $u)=>$this->service->updateDetails($u,$this->parseId($id),$_POST),'Investimento atualizado.'); }
    public function plan(string $id): void { $this->action('/investimentos/'.$id,fn(int $u)=>$this->service->updateMonthlyPlan($u,$this->parseId($id),$_POST),'Aporte mensal planejado atualizado.'); }
    public function event(string $id): void { $this->action('/investimentos/'.$id,fn(int $u)=>$this->service->recordEvent($u,$this->parseId($id),$_POST),'Movimentação registrada.'); }
    public function deactivateEvent(string $id,string $eventId): void { $this->action('/investimentos/'.$id,fn(int $u)=>$this->service->deactivateEvent($u,$this->parseId($id),$this->parseId($eventId)),'Evento inativado.'); }
    public function reactivateEvent(string $id,string $eventId): void { $this->action('/investimentos/'.$id,fn(int $u)=>$this->service->reactivateEvent($u,$this->parseId($id),$this->parseId($eventId)),'Evento reativado.'); }
    public function reactivate(string $id): void { $this->action('/arquivados',fn(int $u)=>$this->service->reactivate($u,$this->parseId($id)),'Investimento reativado.'); }

    public function deactivate(string $id): void
    {
        $this->action('/investimentos', fn(int $user) => $this->service->deactivate($user, $this->parseId($id)), 'Investimento arquivado.');
    }

    public function updateCurrentValue(string $id): void
    {
        $this->action('/investimentos', fn(int $user) => $this->service->updateCurrentValue($user, $this->parseId($id), $_POST), 'Valor atual do investimento atualizado.');
    }
}
