<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\AccountRepository;
use App\Repositories\ReportRepository;
use App\Repositories\MovementRepository;
use App\Services\AuthService;
use App\Services\StatementImportService;
use DomainException;

final class StatementImportController extends FinancialController
{
    public function __construct(AuthService $auth, Csrf $csrf, string $basePath,
        private readonly StatementImportService $imports,
        private readonly AccountRepository $accounts,
        private readonly ReportRepository $reports,
        private readonly MovementRepository $movements) {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function index(): void
    {
        $user = (int) $this->requireUser()['id'];
        $preview = $_SESSION['import_preview'] ?? null;
        if ($preview && $preview['user'] !== $user) $preview = null;
        $this->page('activity/import', 'Importar extrato', [
            'accounts' => $this->accounts->listActive($user),
            'categories' => $this->reports->categories($user),
            'suggestions' => $this->movements->categorySuggestions($user),
            'preview' => $preview,
            'lastBatch' => $this->imports->lastBatch($user),
        ]);
    }

    public function preview(): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->validateCsrf();
        try {
            $account = (int) ($_POST['conta_id'] ?? 0);
            $allowed = array_column($this->accounts->listActive($user), 'id');
            if (!in_array($account, $allowed)) throw new DomainException('Escolha uma conta ativa.');
            $upload = $_FILES['arquivo'] ?? null;
            if (!$upload || $upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 2_000_000 || !is_uploaded_file($upload['tmp_name']))
                throw new DomainException('Selecione um arquivo CSV ou OFX de até 2 MB.');
            $rows = $this->imports->parse((string) $upload['name'], (string) file_get_contents($upload['tmp_name']));
            foreach ($rows as &$row) $row['existente'] = $this->imports->duplicate($user, $account, $row);
            $_SESSION['import_preview'] = ['user' => $user, 'account' => $account, 'rows' => $rows, 'nonce' => bin2hex(random_bytes(16))];
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Extrato lido. Revise as categorias antes de importar.'];
        } catch (DomainException $error) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
        }
        $this->redirect('/movimentacoes/importar');
    }

    public function save(): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->validateCsrf();
        try {
            $preview = $_SESSION['import_preview'] ?? null;
            if (!$preview || $preview['user'] !== $user || !hash_equals($preview['nonce'], (string) ($_POST['nonce'] ?? '')))
                throw new DomainException('Prévia expirada. Envie o arquivo novamente.');
            $selected = is_array($_POST['selecionar'] ?? null) ? $_POST['selecionar'] : [];
            $categories = is_array($_POST['categoria'] ?? null) ? $_POST['categoria'] : [];
            $batch = $this->imports->import($user, $preview['account'], $preview['rows'], $selected, $categories);
            unset($_SESSION['import_preview']);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Extrato importado. Lote ' . $batch . ' disponível para desfazer nesta página.'];
        } catch (DomainException $error) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
        }
        $this->redirect('/movimentacoes/importar');
    }

    public function undo(): void
    {
        $this->action('/movimentacoes/importar', fn($user) => $this->imports->undo($user, (int) ($_POST['lote_id'] ?? 0)), 'Lote desfeito. Lançamentos preservados em Arquivados.');
    }
}
