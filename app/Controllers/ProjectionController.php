<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\ProjectionRepository;
use App\Services\AuthService;
use App\Services\ProjectionService;
use DomainException;

final class ProjectionController extends FinancialController
{
    public function __construct(AuthService $auth, Csrf $csrf, string $basePath, private readonly ProjectionRepository $projections, private readonly ProjectionService $service)
    {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function index(): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->page('projections/index', 'Projeções', ['projections' => $this->service->list($user)]);
    }

    public function show(string $id): void
    {
        $user = (int) $this->requireUser()['id'];
        try {
            $viewMode = ($_GET['visao'] ?? '') === 'completa' ? 'completa' : 'atual';
            $this->page('projections/show', 'Análise da projeção', ['projection' => $this->service->detail($user, $this->parseId($id)), 'viewMode' => $viewMode]);
        } catch (DomainException) {
            http_response_code(404);
            $this->page('errors/not-found', 'Projeção não encontrada');
        }
    }

    public function form(?string $id = null): void
    {
        $user = (int) $this->requireUser()['id'];
        $projection = $id ? $this->projections->find($user, $this->parseId($id)) : null;
        if ($id && !$projection) {
            http_response_code(404);
            $this->page('errors/not-found', 'Projeção não encontrada');
            return;
        }
        $this->page('projections/form', $projection ? 'Editar projeção' : 'Nova projeção', [
            'projection' => $projection,
            'sources' => $this->projections->sources($user),
        ]);
    }

    public function save(?string $id = null): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->validateCsrf();
        $input = array_filter($_POST, fn($value) => is_string($value));
        try {
            $key = (string) ($input['_operation'] ?? '');
            if (!preg_match('/^[a-f0-9]{32}$/', $key)) throw new DomainException('Reabra o formulário e tente novamente.');
            if ($id) {
                $projectionId = $this->parseId($id);
                $this->service->update($user, $projectionId, $input);
            } else {
                $projectionId = $this->service->create($user, $input);
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => $id ? 'Projeção atualizada.' : 'Projeção criada.'];
            $this->redirect('/projecoes/' . $projectionId);
        } catch (DomainException $error) {
            http_response_code(422);
            $_SESSION['form_old'] = array_diff_key($input, array_flip(['_token', '_operation']));
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
            $this->form($id);
        }
    }

    public function delete(string $id): void
    {
        $this->action('/projecoes', fn(int $user) => $this->service->delete($user, $this->parseId($id)), 'Projeção excluída.');
    }
}
