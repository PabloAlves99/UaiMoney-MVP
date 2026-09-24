<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\MovementReturn;
use App\Repositories\AccountRepository;
use App\Repositories\CardRepository;
use App\Repositories\MovementRepository;
use App\Repositories\ReportRepository;
use App\Services\AuthService;
use App\Services\MovementService;
use DomainException;

final class MovementController extends FinancialController
{
    public function __construct(
        AuthService $auth,
        Csrf $csrf,
        string $basePath,
        private readonly MovementService $service,
        private readonly MovementRepository $movements,
        private readonly AccountRepository $accounts,
        private readonly CardRepository $cards,
        private readonly ReportRepository $reports
    ) {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function form(?string $id = null): void
    {
        $user = (int) $this->requireUser()['id'];
        $movement = $id ? $this->movements->find($user, $this->parseId($id)) : null;
        if ($id && (!$movement || $movement['excluida_em'])) {
            http_response_code(404);
            $this->page('errors/not-found', 'Movimentação não encontrada');
            return;
        }
        $accounts = $this->accounts->listActive($user);
        $categories = $this->reports->categories($user);
        if ($movement) {
            if ($movement['conta_id'] && !in_array($movement['conta_id'], array_column($accounts, 'id'))) {
                $account = $this->accounts->findById((int) $movement['conta_id'], $user);
                if ($account) $accounts[] = $account;
            }
            if (!in_array($movement['subgrupo_id'], array_column($categories, 'id'))) {
                $categories[] = ['id' => $movement['subgrupo_id'], 'nome' => $movement['subgrupo_nome'], 'grupo_nome' => $movement['grupo_nome'], 'tipo' => $movement['tipo']];
            }
        }
        $this->page('activity/form', $movement ? 'Editar movimentação' : 'Novo lançamento', [
            'movement' => $movement,
            'accounts' => $accounts,
            'cards' => array_values(array_filter($this->cards->list($user), fn($card) => (int) $card['ativo'] === 1)),
            'categories' => $categories,
            'returnPath' => MovementReturn::path($_POST['_retorno'] ?? $_GET['voltar'] ?? ''),
        ]);
    }

    public function save(?string $id = null): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->validateCsrf();
        $input = array_filter($_POST, fn($value) => is_string($value));
        $return = MovementReturn::path($input['_retorno'] ?? '');
        try {
            $key = (string) ($input['_operation'] ?? '');
            if (!preg_match('/^[a-f0-9]{32}$/', $key)) throw new DomainException('Reabra o formulário e tente novamente.');
            if (!isset($_SESSION['completed_operations'][$key])) {
                if ($id) $this->service->update($user, $this->parseId($id), $input);
                else $this->service->create($user, $input);
                $_SESSION['completed_operations'][$key] = time();
                $_SESSION['completed_operations'] = array_slice($_SESSION['completed_operations'], -200, null, true);
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => $id ? 'Movimentação atualizada.' : 'Lançamento salvo.'];
            if (!$id && ($input['modo'] ?? '') === 'recorrente') {
                $_SESSION['flash']['message'] = 'Recorrência criada. Use Atualizar previstas para gerar as pendências já vencidas.';
                $return = '/recorrencias';
            }
            $this->redirect($return . '#lista');
        } catch (DomainException $error) {
            http_response_code(422);
            $_SESSION['form_old'] = array_diff_key($input, array_flip(['_token', '_operation']));
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
            $this->form($id);
        }
    }

    public function delete(string $id): void
    {
        $return = MovementReturn::path($_POST['_retorno'] ?? '');
        $this->action($return . '#lista', fn($user) => $this->service->delete($user, $this->parseId($id), $_POST), 'Movimentação movida para a lixeira. Você pode restaurá-la.');
    }

    public function restore(string $id): void
    {
        $this->action('/movimentacoes?lixeira=1#lista', fn($user) => $this->service->restore($user, $this->parseId($id), $_POST), 'Movimentação restaurada. Os saldos foram recalculados.');
    }
}
