<?php

declare(strict_types=1);
namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AuthService;
use App\Services\CategoryService;
use DomainException;

final class CategoryController extends BaseController
{
    public function __construct(AuthService $authService, private readonly CategoryService $categoryService, Csrf $csrf, string $basePath)
    {
        parent::__construct($authService,$csrf,$basePath);
    }

    public function index(): void { $this->renderIndex($this->requireUser()); }
    public function inactive(): void { $this->renderIndex($this->requireUser(),null,true); }

    private function destination(): string
    {
        return ($_POST['origem'] ?? '') === 'desativadas' ? '/categorias/desativadas' : '/categorias';
    }

    private function action(callable $action): void
    {
        $user = $this->requireUser();
        $this->validateCsrf();
        try {
            $action((int)$user['id']);
            $_SESSION['flash'] = ['type'=>'success','message'=>'Categorias atualizadas.'];
            $this->redirect($this->destination());
        } catch (DomainException $e) {
            http_response_code(422);
            $this->renderIndex($user,$e->getMessage(),$this->destination()==='/categorias/desativadas');
        }
    }

    public function storeGroup(): void
    {
        $this->action(fn($user) => $this->categoryService->createGroup($user,(string)($_POST['nome'] ?? ''),(string)($_POST['tipo'] ?? ''),isset($_POST['essencial']) ? 'essencial' : 'variavel'));
    }

    public function storeSubgroup(): void
    {
        $this->action(function($user) {
            $group = filter_var($_POST['grupo_id'] ?? null,FILTER_VALIDATE_INT);
            if (!$group || $group<1) throw new DomainException('Categoria inválida.');
            $this->categoryService->createSubgroup($user,$group,(string)($_POST['nome'] ?? ''),isset($_POST['descricao']) ? (string)$_POST['descricao'] : null);
        });
    }

    public function updateClassification(string $id): void
    {
        $this->action(fn($user) => $this->categoryService->updateClassification($user,$this->parseId($id),isset($_POST['essencial']) ? 'essencial' : 'variavel'));
    }

    public function deactivateGroup(string $id): void
    {
        $this->action(fn($user) => $this->categoryService->deactivateGroup($user,$this->parseId($id)));
    }

    public function deactivateSubgroup(string $id): void
    {
        $this->action(function($user) use ($id) {
            $parsed = $this->parseId($id);
            $this->categoryService->subgroup($user,$parsed);
            $this->categoryService->deactivateSubgroup($user,$parsed);
        });
    }

    public function reactivateGroup(string $id): void
    {
        $this->action(fn($user) => $this->categoryService->reactivateGroup($user,$this->parseId($id)));
    }

    public function reactivateSubgroup(string $id): void
    {
        $this->action(fn($user) => $this->categoryService->reactivateSubgroup($user,$this->parseId($id)));
    }

    public function editGroup(string $id): void { $this->editForm('grupo',$id); }
    public function editSubgroup(string $id): void { $this->editForm('subgrupo',$id); }
    public function updateGroup(string $id): void { $this->saveEdit('grupo',$id); }
    public function updateSubgroup(string $id): void { $this->saveEdit('subgrupo',$id); }

    private function editForm(string $kind, string $id, ?string $error = null): void
    {
        $user = $this->requireUser();
        try {
            $parsed = $this->parseId($id);
            $record = $kind==='grupo' ? $this->categoryService->group((int)$user['id'],$parsed) : $this->categoryService->subgroup((int)$user['id'],$parsed);
        } catch (DomainException $e) {
            http_response_code(404);
            $this->renderIndex($user,$e->getMessage());
            return;
        }
        $inactive = !(int)$record['ativo'] || ($kind==='subgrupo' && !(int)$record['grupo_ativo']);
        View::render('categories/form',[
            'usuario'=>$user,'kind'=>$kind,'record'=>$record,'error'=>$error,
            'old'=>$error!==null ? $_POST : [],
            'typeLocked'=>$kind==='grupo' && $this->categoryService->groupHasHistory((int)$user['id'],$parsed),
            'returnPath'=>$inactive ? '/categorias/desativadas' : '/categorias',
            'basePath'=>$this->basePath,'csrfToken'=>$this->csrf->token(),'pageTitle'=>'Editar '.($kind==='grupo' ? 'categoria' : 'subcategoria').' - UaiMoney',
        ],'layouts/app');
    }

    private function saveEdit(string $kind, string $id): void
    {
        $user = $this->requireUser();
        $this->validateCsrf();
        try {
            $parsed = $this->parseId($id);
            if ($kind==='grupo') $this->categoryService->editGroup((int)$user['id'],$parsed,$_POST);
            else $this->categoryService->editSubgroup((int)$user['id'],$parsed,$_POST);
            $_SESSION['flash']=['type'=>'success','message'=>'Alterações salvas.'];
            $this->redirect($this->destination());
        } catch (DomainException $e) {
            http_response_code(422);
            $this->editForm($kind,$id,$e->getMessage());
        }
    }

    private function renderIndex(array $usuario, ?string $error = null, bool $inactive = false): void
    {
        $data = $this->categoryService->management((int)$usuario['id']);
        $groups = array_values(array_filter($data['groups'],fn($g) => (bool)$g['ativo'] !== $inactive));
        $subgroups = array_values(array_filter($data['subgroups'],fn($s) => ((bool)$s['ativo'] && (bool)$s['grupo_ativo']) !== $inactive));
        $selectedGroup = (string)($_GET['grupo_id'] ?? '');
        if ($selectedGroup!=='' && ctype_digit($selectedGroup)) $subgroups = array_values(array_filter($subgroups,fn($s) => (int)$s['grupo_id']===(int)$selectedGroup));
        else $selectedGroup='';
        View::render('categories/index',[
            'usuario'=>$usuario,'grupos'=>$groups,'subgrupos'=>$subgroups,'allGroups'=>$data['groups'],
            'inactive'=>$inactive,'selectedGroup'=>$selectedGroup,'error'=>$error,'old'=>$error!==null ? $_POST : [],
            'basePath'=>$this->basePath,'csrfToken'=>$this->csrf->token(),'pageTitle'=>($inactive ? 'Categorias desativadas' : 'Categorias').' - UaiMoney',
        ],'layouts/app');
    }
}
