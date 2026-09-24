<?php
declare(strict_types=1);

use App\Controllers\ActivityController;
use App\Controllers\CardController;
use App\Controllers\DashboardController;
use App\Controllers\WalletController;
use App\Repositories\ActivityRepository;
use App\Repositories\CardRepository;
use App\Repositories\LedgerRepository;
use App\Repositories\ReportRepository;
use App\Services\CardService;
use App\Services\LedgerService;
use App\Services\PlanningService;

// Loaded from the composition root after repositories and authentication are ready.
$cards = new CardRepository($pdo);
$ledger = new LedgerRepository($pdo);
$reports = new ReportRepository($pdo);
$cardService = new CardService($cards, $accountRepository, $categoryRepository);
$ledgerService = new LedgerService($ledger, $accountRepository, $cards, $cardService);
$cardController = new CardController($authService, $csrf, $basePath, $cards, $cardService, $accountRepository, $reports);
$dashboardController = new DashboardController($authService, $csrf, $basePath, $reports, $accountRepository, $cards, new PlanningService($reports));
$walletController = new WalletController($authService, $csrf, $basePath, $ledger, $ledgerService, $accountRepository, $reports, $transactionService);
$activityController = new ActivityController($authService, $csrf, $basePath, new ActivityRepository($pdo), $accountRepository, $cards, $reports);

$movementRepository = new \App\Repositories\MovementRepository($pdo);
$movementService = new \App\Services\MovementService(
    $movementRepository, $categoryRepository, $accountRepository,
    $transactionService, $cardService, $installmentService, $recurrenceService
);
$movementController = new \App\Controllers\MovementController(
    $authService, $csrf, $basePath, $movementService, $movementRepository,
    $accountRepository, $cards, $reports
);

$router->get('/movimentacoes/nova', [$movementController, 'form']);
$router->post('/movimentacoes', [$movementController, 'save']);
$router->get('/movimentacoes/{id}/editar', [$movementController, 'form']);
$router->post('/movimentacoes/{id}/editar', [$movementController, 'save']);
$router->post('/movimentacoes/{id}/excluir', [$movementController, 'delete']);
$router->post('/movimentacoes/{id}/restaurar', [$movementController, 'restore']);
$router->get('/', [$dashboardController, 'index']);
$router->get('/movimentacoes', [$activityController, 'index']);
$router->get('/movimentacoes/exportar', [$activityController, 'export']);
$router->get('/movimentacoes/avancado', [$activityController, 'installment']);
$router->get('/movimentacoes/{id}', [$walletController, 'detail']);
$router->post('/movimentacoes/{id}/estornar', [$walletController, 'refund']);
$router->post('/movimentacoes/{id}/duplicar', [$walletController, 'duplicate']);
$router->post('/movimentacoes/{id}/fatura', [$walletController, 'move']);
$router->get('/cartoes', [$cardController, 'index']);
$router->post('/cartoes', [$cardController, 'save']);
$router->post('/cartoes/compras', [$cardController, 'purchase']);
$router->get('/cartoes/{id}', [$cardController, 'show']);
$router->post('/cartoes/{id}/desativar', [$cardController, 'deactivate']);
$router->get('/faturas/{id}', [$cardController, 'invoice']);
$router->post('/faturas/{id}/pagar', [$cardController, 'pay']);
$router->post('/faturas/{id}/fechar', [$cardController, 'close']);
$router->post('/faturas/{id}/credito', [$cardController, 'carry']);
$router->get('/planejamento', [$dashboardController, 'budget']);
$router->post('/planejamento', [$dashboardController, 'saveBudget']);
$router->post('/planejamento/copiar', [$dashboardController, 'copyBudget']);
$router->post('/planejamento/{id}/remover', [$dashboardController, 'deleteBudget']);
$router->get('/analises', [$dashboardController, 'analytics']);
$router->get('/contas/{id}', [$walletController, 'account']);
$router->get('/carteira', [$walletController, 'index']);
$router->post('/carteira/transferir', [$walletController, 'transfer']);
$router->post('/carteira/conferir', [$walletController, 'reconcile']);
$router->get('/comecar', [$walletController, 'start']);
$router->post('/comecar/categorias', [$walletController, 'seed']);
