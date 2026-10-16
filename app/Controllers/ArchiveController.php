<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\ArchiveRepository;
use App\Repositories\CardRepository;
use App\Repositories\ReportRepository;
use App\Services\AuthService;

final class ArchiveController extends FinancialController
{
    public function __construct(AuthService $auth,Csrf $csrf,string $basePath,private readonly ArchiveRepository $archives,private readonly CardRepository $cards,private readonly ReportRepository $reports)
    { parent::__construct($auth,$csrf,$basePath); }

    public function index(): void
    { $this->page('archives/index','Arquivados',['groups'=>$this->archives->items((int)$this->requireUser()['id'])]); }

    public function reactivateCard(string $id): void
    { $this->action('/arquivados',fn(int $u)=>$this->cards->reactivate($u,$this->parseId($id)),'Cartão reativado.'); }

    public function reactivateBudget(string $id): void
    { $this->action('/arquivados',fn(int $u)=>$this->reports->reactivateBudget($u,$this->parseId($id)),'Limite reativado.'); }
}
