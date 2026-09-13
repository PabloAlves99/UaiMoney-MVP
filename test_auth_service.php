<?php

declare(strict_types=1);

use App\Repositories\UserRepository;
use App\Services\AuthService;

$app = require __DIR__ . '/app/bootstrap.php';

$pdo = $app['pdo'];
$session = $app['session'];

$userRepository = new UserRepository(
    $pdo
);

$authService = new AuthService(
    $userRepository,
    $session
);

try {

    $pdo->beginTransaction();


    /*
     * Cadastro
     */

    $usuarioId = $authService->register(
        'Usuário Teste',
        'usuario_teste',
        'usuario@teste.local',
        'Senha123',
        'Senha123'
    );


    /*
     * Login
     */

    $usuario = $authService->login(
        'usuario_teste',
        'Senha123'
    );


    /*
     * Capturamos os dados enquanto
     * a sessão ainda está autenticada.
     */

    $usuarioAtual = $authService->currentUser();

    $autenticado = $authService->isAuthenticated();


    /*
     * Logout ainda antes de qualquer saída.
     */

    $authService->logout();


    /*
     * Rollback do usuário de teste.
     */

    $pdo->rollBack();


    /*
     * Agora podemos imprimir.
     */

    echo "Usuário criado: {$usuarioId}"
        . PHP_EOL;

    echo PHP_EOL;
    echo "Login realizado:" . PHP_EOL;

    print_r(
        $usuario
    );

    echo PHP_EOL;
    echo "Usuário atual:" . PHP_EOL;

    print_r(
        $usuarioAtual
    );

    echo PHP_EOL;

    echo $autenticado
        ? 'Autenticado'
        : 'Não autenticado';

    echo PHP_EOL;


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo $e->getMessage()
        . PHP_EOL;
}