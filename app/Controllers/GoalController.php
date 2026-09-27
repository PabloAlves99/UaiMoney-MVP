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
    public function index(): void {$u=(int)$this->requireUser()['id'];$this->page('planning/goals','Objetivos financeiros',['goals'=>$this->goals->list($u)]);}
    public function store(): void {$this->action('/objetivos',fn(int $u)=>$this->service->create($u,$_POST),'Objetivo financeiro salvo.');}
    public function updateCurrent(string $id): void {$this->action('/objetivos',fn(int $u)=>$this->service->updateCurrent($u,$this->parseId($id),$_POST),'Progresso da meta atualizado.');}
    public function deactivate(string $id): void {$this->action('/objetivos',fn(int $u)=>$this->service->deactivate($u,$this->parseId($id)),'Objetivo arquivado.');}
}
