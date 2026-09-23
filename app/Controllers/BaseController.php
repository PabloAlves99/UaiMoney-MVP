<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AuthService;

abstract class BaseController
{
    public function __construct(
        protected readonly AuthService $authService,
        protected readonly Csrf $csrf,
        protected readonly string $basePath
    ) {
    }


    protected function requireUser(): array
    {
        $usuario = $this
            ->authService
            ->currentUser();

        if ($usuario === null) {
            $this->redirect('/login');
        }

        return $usuario;
    }


    protected function validateCsrf(): void
    {
        $token = $_POST['_token']
            ?? null;

        if ($this->csrf->validate($token)) {
            $key = (string) ($_POST['_operation'] ?? '');
            if (isset($_SESSION['submitted_forms'][$key])) {
                $this->redirect($_SESSION['submitted_forms'][$key]);
            }
            return;
        }

        http_response_code(419);

        View::render(
            'errors/419',
            [
                'basePath' => $this->basePath,
                'pageTitle' => 'Sessão expirada - UaiMoney'
            ],
            'layouts/auth'
        );

        exit;
    }


    protected function parseId(
        string $id
    ): int {
        $parsedId = filter_var(
            $id,
            FILTER_VALIDATE_INT
        );

        if (
            $parsedId === false ||
            $parsedId <= 0
        ) {
            http_response_code(404);

            echo '404 - Recurso não encontrado';

            exit;
        }

        return $parsedId;
    }


    protected function redirect(
        string $path
    ): never {
        $key = (string) ($_POST['_operation'] ?? '');
        if (
            ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && preg_match('/^[a-f0-9]{32}$/', $key)
            && http_response_code() < 400
            && ($_SESSION['flash']['type'] ?? '') !== 'danger'
        ) {
            $_SESSION['submitted_forms'][$key] = $path;
            $_SESSION['submitted_forms'] = array_slice($_SESSION['submitted_forms'], -200, null, true);
        }
        $query = parse_url($path, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (isset($params['erro'])) {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => (string) $params['erro']];
                $path = (string) parse_url($path, PHP_URL_PATH);
            } elseif (isset($params['acao'])) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Recorrência ' . (string) $params['acao'] . '.'];
                $path = (string) parse_url($path, PHP_URL_PATH);
            }
        }
        header(
            'Location: '
            . $this->basePath
            . '/'
            . ltrim($path, '/')
        );

        exit;
    }
}
