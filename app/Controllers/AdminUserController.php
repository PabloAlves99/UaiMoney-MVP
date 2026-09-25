<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AuthService;
use App\Services\UserManagementService;
use DomainException;

final class AdminUserController extends BaseController
{
    public function __construct(AuthService $auth, Csrf $csrf, string $basePath, private readonly UserManagementService $users)
    {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function index(?string $id = null): void
    {
        $admin = $this->requireAdmin();
        $editing = $id ? $this->users->get($this->parseId($id)) : null;
        View::render('admin/users', ['usuario' => $admin, 'users' => $this->users->list(), 'editing' => $editing, 'old' => $_SESSION['form_old'] ?? [], 'basePath' => $this->basePath, 'csrfToken' => $this->csrf->token(), 'pageTitle' => 'Usuários - UaiMoney'], 'layouts/app');
        unset($_SESSION['form_old']);
    }

    public function save(?string $id = null): void
    {
        $admin = $this->requireAdmin();
        $this->validateCsrf();
        try {
            if ($id) $this->users->update((int) $admin['id'], $this->parseId($id), $_POST);
            else $this->users->create($_POST);
            $_SESSION['flash'] = ['type' => 'success', 'message' => $id ? 'Usuário atualizado.' : 'Usuário cadastrado.'];
            $this->redirect('/admin/usuarios');
        } catch (DomainException $error) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
            $_SESSION['form_old'] = array_diff_key($_POST, array_flip(['_token', '_operation']));
            $this->index($id);
        }
    }

    private function requireAdmin(): array
    {
        $user = $this->requireUser();
        if (($user['tipo'] ?? '') === 'admin') return $user;
        http_response_code(403);
        View::render('errors/403', ['usuario' => $user, 'basePath' => $this->basePath, 'csrfToken' => $this->csrf->token(), 'pageTitle' => 'Acesso negado - UaiMoney'], 'layouts/app');
        exit;
    }
}
