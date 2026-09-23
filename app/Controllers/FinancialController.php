<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\View;
use DomainException;

abstract class FinancialController extends BaseController
{
    protected function page(string $view, string $title, array $data = []): void
    {
        $usuario = $this->requireUser();
        $old = $_SESSION['form_old'] ?? [];
        unset($_SESSION['form_old']);
        View::render($view, array_merge($data, [
            'usuario' => $usuario,
            'basePath' => $this->basePath,
            'csrfToken' => $this->csrf->token(),
            'pageTitle' => $title . ' - UaiMoney',
            'old' => $old,
        ]), 'layouts/app');
    }

    protected function action(string $return, callable $work, string $message = 'Alterações salvas.'): void
    {
        $user = $this->requireUser();
        $this->validateCsrf();
        $key = (string) ($_POST['_operation'] ?? '');
        try {
            if (!preg_match('/^[a-f0-9]{32}$/', $key))
                throw new DomainException('Reabra o formulário e tente novamente.');
            // PHP holds an exclusive session lock: a double click cannot execute twice.
            if (!isset($_SESSION['completed_operations'][$key])) {
                $work((int) $user['id']);
                $_SESSION['completed_operations'][$key] = time();
                $_SESSION['completed_operations'] = array_slice($_SESSION['completed_operations'], -200, null, true);
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => $message];
        } catch (DomainException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $e->getMessage()];
            $_SESSION['form_old'] = array_diff_key($_POST, array_flip(['_token', '_operation']));
        }
        $this->redirect($return);
    }
}
