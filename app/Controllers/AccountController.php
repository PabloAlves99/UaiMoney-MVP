<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\View;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Services\CategoryService;
use App\Services\TransactionService;
use App\Services\AccountExportService;
use DomainException;

final class AccountController extends BaseController
{
    public function __construct(
        AuthService $authService,
        private readonly AccountService $accountService,
        private readonly TransactionService $transactionService,
        private readonly CategoryService $categoryService,
        private readonly AccountExportService $accountExportService,
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

        $this->renderIndex(
            $usuario
        );
    }

    public function reactivate(string $id): void
    {
        $usuario = $this->requireUser();
        $this->validateCsrf();
        $this->accountService->reactivate((int)$usuario['id'], $this->parseId($id));
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Conta reativada.'];
        $this->redirect('/contas');
    }

    public function show(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $contaId = $this->parseId(
            $id
        );

        $usuarioId =
            (int) $usuario['id'];


        try {

            $conta = $this
                ->accountService
                ->get(
                    $usuarioId,
                    $contaId
                );
        } catch (DomainException $e) {

            http_response_code(404);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );

            return;
        }


        /*
         * Aproveitamos exatamente os mesmos
         * filtros de Movimentações.
         */

        $filters = $this
            ->transactionService
            ->normalizeFilters(
                $_GET
            );


        /*
         * A conta da URL é soberana.
         *
         * Mesmo que alguém envie:
         *
         * ?conta_id=999
         *
         * ignoramos e usamos a conta atual.
         */

        $filters['conta_id'] =
            $contaId;


        $transacoes = $this
            ->transactionService
            ->list(
                $usuarioId,
                $filters
            );


        $grupos = $this
            ->categoryService
            ->list(
                $usuarioId
            );


        View::render(
            'accounts/show',
            [
                'usuario' => $usuario,
                'conta' => $conta,
                'transacoes' => $transacoes,
                'grupos' => $grupos,
                'filters' => $filters,
                'basePath' => $this->basePath,
                'csrfToken' => $this->csrf->token(),
                'pageTitle'
                => $conta['nome']
                    . ' - UaiMoney'
            ],
            'layouts/app'
        );
    }

    public function exportPdf(
        string $id
    ): void {
        $context =
            $this->buildExportContext(
                $id
            );


        if ($context === null) {
            return;
        }


        $content =
            $this->accountExportService
            ->generatePdf(
                $context['conta'],
                $context['transacoes'],
                $context['filters'],
                $context['grupos']
            );


        $this->sendDownload(
            $content,
            'application/pdf',
            $this->accountExportService
                ->filename(
                    $context['conta'],
                    'pdf'
                )
        );
    }


    public function exportExcel(
        string $id
    ): void {
        $context =
            $this->buildExportContext(
                $id
            );


        if ($context === null) {
            return;
        }


        $content =
            $this->accountExportService
            ->generateExcel(
                $context['conta'],
                $context['transacoes'],
                $context['filters'],
                $context['grupos']
            );


        $this->sendDownload(
            $content,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $this->accountExportService
                ->filename(
                    $context['conta'],
                    'xlsx'
                )
        );
    }

    public function store(): void
    {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        try {

            $this->accountService
                ->create(
                    (int) $usuario['id'],
                    $_POST['nome'] ?? '',
                    $_POST['tipo'] ?? '',
                    $_POST['instituicao'] ?? null,
                    $_POST['saldo_inicial'] ?? '',
                    $_POST['saldo_inicial_em'] ?? ''
                );


            $this->redirect(
                '/contas'
            );
        } catch (DomainException $e) {

            http_response_code(422);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );
        }
    }


    public function deactivate(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $contaId = $this->parseId(
            $id
        );


        try {

            $this->accountService
                ->deactivate(
                    (int) $usuario['id'],
                    $contaId
                );


            $this->redirect(
                '/contas'
            );
        } catch (DomainException $e) {

            http_response_code(404);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );
        }
    }


    private function renderIndex(
        array $usuario,
        ?string $error = null
    ): void {
        $contas = $this
            ->accountService
            ->list(
                (int) $usuario['id']
            );


        View::render(
            'accounts/index',
            [
                'usuario' => $usuario,
                'contas' => $contas,
                'inativas' => $this->accountService->inactive((int)$usuario['id']),
                'error' => $error,
                'basePath' => $this->basePath,
                'csrfToken'
                => $this->csrf->token(),
                'pageTitle'
                => 'Contas - UaiMoney'
            ],
            'layouts/app'
        );
    }

    public function edit(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $contaId = $this->parseId(
            $id
        );


        try {

            $conta = $this
                ->accountService
                ->get(
                    (int) $usuario['id'],
                    $contaId
                );
        } catch (DomainException $e) {

            http_response_code(404);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );

            return;
        }


        $this->renderEdit(
            $usuario,
            $conta
        );
    }

    public function update(
        string $id
    ): void {
        $usuario = $this->requireUser();

        $this->validateCsrf();


        $contaId = $this->parseId(
            $id
        );


        try {

            $this->accountService
                ->update(
                    (int) $usuario['id'],
                    $contaId,
                    $_POST['nome'] ?? '',
                    $_POST['tipo'] ?? '',
                    $_POST['instituicao'] ?? null
                );


            $this->redirect(
                '/contas/' . $contaId
            );
        } catch (DomainException $e) {

            try {

                $conta = $this
                    ->accountService
                    ->get(
                        (int) $usuario['id'],
                        $contaId
                    );
            } catch (DomainException) {

                http_response_code(404);

                $this->renderIndex(
                    $usuario,
                    'Conta não encontrada.'
                );

                return;
            }


            http_response_code(422);


            $this->renderEdit(
                $usuario,
                $conta,
                $e->getMessage(),
                [
                    'nome' =>
                    $_POST['nome']
                        ?? $conta['nome'],

                    'tipo' =>
                    $_POST['tipo']
                        ?? $conta['tipo'],

                    'instituicao' =>
                    $_POST['instituicao']
                        ?? $conta['instituicao']
                ]
            );
        }
    }

    private function renderEdit(
        array $usuario,
        array $conta,
        ?string $error = null,
        ?array $formData = null
    ): void {
        if ($formData === null) {

            $formData = [
                'nome' =>
                $conta['nome'],

                'tipo' =>
                $conta['tipo'],

                'instituicao' =>
                $conta['instituicao']
            ];
        }


        View::render(
            'accounts/edit',
            [
                'usuario' => $usuario,
                'conta' => $conta,
                'formData' => $formData,
                'error' => $error,
                'basePath' => $this->basePath,
                'csrfToken' =>
                $this->csrf->token(),
                'pageTitle' =>
                'Editar '
                    . $conta['nome']
                    . ' - UaiMoney'
            ],
            'layouts/app'
        );
    }

    private function buildExportContext(
        string $id
    ): ?array {
        $usuario =
            $this->requireUser();


        $usuarioId =
            (int) $usuario['id'];


        $contaId =
            $this->parseId(
                $id
            );


        /*
     * Continua respeitando usuario_id.
     *
     * Portanto um usuário não consegue
     * exportar conta de outro usuário
     * simplesmente alterando a URL.
     */

        try {

            $conta =
                $this->accountService
                ->get(
                    $usuarioId,
                    $contaId
                );
        } catch (DomainException $e) {

            http_response_code(404);

            $this->renderIndex(
                $usuario,
                $e->getMessage()
            );

            return null;
        }


        /*
     * Mesma normalização usada
     * pela tela de detalhe.
     */

        $filters =
            $this->transactionService
            ->normalizeFilters(
                $_GET
            );


        /*
     * A conta da URL continua soberana.
     */

        $filters['conta_id'] =
            $contaId;


        return [

            'conta' =>
            $conta,

            'transacoes' =>
            $this->transactionService
                ->list(
                    $usuarioId,
                    $filters
                ),

            'filters' =>
            $filters,

            'grupos' =>
            $this->categoryService
                ->list(
                    $usuarioId
                )

        ];
    }


    private function sendDownload(
        string $content,
        string $contentType,
        string $filename
    ): never {
        /*
     * Qualquer espaço/BOM enviado antes
     * poderia corromper XLSX ou PDF.
     */

        while (
            ob_get_level() > 0
        ) {
            ob_end_clean();
        }


        header(
            'Content-Type: '
                . $contentType
        );

        header(
            'Content-Disposition: attachment; filename="'
                . $filename
                . '"'
        );

        header(
            'Cache-Control: private, no-store, max-age=0'
        );

        header(
            'X-Content-Type-Options: nosniff'
        );


        echo $content;

        exit;
    }
}
