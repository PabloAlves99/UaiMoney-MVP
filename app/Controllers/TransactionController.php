<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Services\AuthService;
use App\Services\TransactionService;
use App\Core\Csrf;

final class TransactionController extends BaseController
{
    public function __construct(
        AuthService $authService,
        private readonly TransactionService $transactionService,
        Csrf $csrf,
        string $basePath
    ) {
        parent::__construct(
            $authService,
            $csrf,
            $basePath
        );
    }


    public function index(): void
    {
        $usuario = $this->requireUser();


        $transacoes = $this
            ->transactionService
            ->list(
                (int) $usuario['id']
            );


        View::render(
            'transactions/index',
            [
                'usuario' => $usuario,
                'transacoes' => $transacoes,
                'basePath' => $this->basePath,
                'csrfToken' => $this->csrf->token(),
                'pageTitle' => 'Movimentações - UaiMoney'
            ],
            'layouts/app'
        );
    }
}
