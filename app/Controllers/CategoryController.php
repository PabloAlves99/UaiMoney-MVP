<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AuthService;
use App\Services\CategoryService;
use DomainException;

final class CategoryController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly CategoryService $categoryService,
        private readonly Csrf $csrf,
        private readonly string $basePath
    ) {
    }


    public function index(): void
    {
        $usuario = $this->requireUser();

        $this->renderIndex(
            $usuario
        );
    }


    public function storeGroup(): void
    {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        try {

            $this->categoryService
                ->createGroup(
                    (int) $usuario['id'],
                    $_POST['nome'] ?? '',
                    $_POST['tipo'] ?? ''
                );


            $this->redirect(
                '/categorias'
            );


        } catch (DomainException $e) {

            http_response_code(422);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );
        }
    }


    public function storeSubgroup(): void
    {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $grupoId = filter_var(
            $_POST['grupo_id'] ?? null,
            FILTER_VALIDATE_INT
        );


        if (
            $grupoId === false ||
            $grupoId <= 0
        ) {
            http_response_code(422);

            $this->renderIndex(
                $usuario,
                'Categoria inválida.'
            );

            return;
        }


        try {

            $this->categoryService
                ->createSubgroup(
                    (int) $usuario['id'],
                    $grupoId,
                    $_POST['nome'] ?? '',
                    $_POST['descricao'] ?? null
                );


            $this->redirect(
                '/categorias'
            );


        } catch (DomainException $e) {

            http_response_code(422);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );
        }
    }


    public function deactivateGroup(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $grupoId = $this->parseId(
            $id
        );


        try {

            $this->categoryService
                ->deactivateGroup(
                    (int) $usuario['id'],
                    $grupoId
                );


            $this->redirect(
                '/categorias'
            );


        } catch (DomainException $e) {

            http_response_code(404);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );
        }
    }


    public function deactivateSubgroup(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $subgrupoId = $this->parseId(
            $id
        );


        $this->categoryService
            ->deactivateSubgroup(
                (int) $usuario['id'],
                $subgrupoId
            );


        $this->redirect(
            '/categorias'
        );
    }


    private function renderIndex(
        array $usuario,
        ?string $error = null
    ): void {
        $grupos = $this
            ->categoryService
            ->list(
                (int) $usuario['id']
            );


        View::render(
            'categories/index',
            [
                'usuario' => $usuario,
                'grupos' => $grupos,
                'error' => $error,
                'basePath' => $this->basePath,
                'csrfToken' => $this->csrf->token(),
                'pageTitle' => 'Categorias - UaiMoney'
            ],
            'layouts/app'
        );
    }


    private function requireUser(): array
    {
        $usuario = $this
            ->authService
            ->currentUser();


        if ($usuario === null) {
            $this->redirect(
                '/login'
            );
        }


        return $usuario;
    }


    private function validateCsrf(): void
    {
        $token = $_POST['_token']
            ?? null;


        if ($this->csrf->validate($token)) {
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


    private function parseId(
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


    private function redirect(
        string $path
    ): never {
        header(
            'Location: '
            . $this->basePath
            . '/'
            . ltrim($path, '/')
        );

        exit;
    }
}