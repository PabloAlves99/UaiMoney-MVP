<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Csrf;
use App\Repositories\GoalRepository;
use App\Services\AuthService;
use App\Services\GoalService;

final class GoalController extends FinancialController
{
    public function __construct(AuthService $auth,Csrf $csrf,string $basePath,private readonly GoalRepository $goals,private readonly GoalService $service){parent::__construct($auth,$csrf,$basePath);}
    public function index(): void {$u=(int)$this->requireUser()['id'];$this->page('planning/goals','Objetivos financeiros',['goals'=>$this->goals->list($u),'sources'=>$this->goals->sources($u)]);}
    public function show(string $id): void {$u=(int)$this->requireUser()['id'];$goal=$this->goals->find($u,$this->parseId($id));if(!$goal){http_response_code(404);$this->page('errors/not-found','Objetivo não encontrado');return;}$this->page('planning/goal-detail','Detalhes do objetivo',['goal'=>$goal,'sources'=>$this->goals->sources($u),'reservations'=>$this->goals->reservations($u,(int)$goal['id']),'warnings'=>$this->goals->reservationWarnings($u,(int)$goal['id'])]);}
    public function store(): void {$this->action('/objetivos',fn(int $u)=>$this->service->create($u,$_POST),'Objetivo financeiro salvo.');}
    public function updateCurrent(string $id): void {$this->action('/objetivos',fn(int $u)=>$this->service->updateCurrent($u,$this->parseId($id),$_POST),'Progresso da meta atualizado.');}
    public function deactivate(string $id): void {$this->action('/objetivos',fn(int $u)=>$this->service->deactivate($u,$this->parseId($id)),'Objetivo arquivado.');}
    public function update(string $id): void {$this->action('/objetivos/'.$id,fn(int $u)=>$this->service->update($u,$this->parseId($id),$_POST),'Objetivo atualizado.');}
    public function reserve(string $id): void {$this->action('/objetivos/'.$id,fn(int $u)=>$this->service->reserve($u,$this->parseId($id),$_POST),'Valor reservado atualizado.');}
    public function release(string $id,string $reservation): void {$this->action('/objetivos/'.$id,fn(int $u)=>$this->service->release($u,$this->parseId($id),$this->parseId($reservation)),'Reserva inativada.');}
    public function reactivateReservation(string $id,string $reservation): void {$this->action('/arquivados',fn(int $u)=>$this->service->reactivateReservation($u,$this->parseId($id),$this->parseId($reservation)),'Reserva reativada.');}
    public function reactivate(string $id): void {$this->action('/arquivados',fn(int $u)=>$this->service->reactivate($u,$this->parseId($id)),'Objetivo reativado.');}
}
