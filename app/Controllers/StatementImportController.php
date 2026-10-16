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
        $preview = $this->imports->draft($user);
        $this->page('activity/import', 'Importar extrato', [
            'accounts' => $this->accounts->listActive($user),
            'categories' => $this->reports->categories($user),
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
            $suggested = [];
            foreach ($this->movements->categorySuggestions($user) as $item)
                $suggested[mb_strtolower($item['descricao']) . '|' . $item['tipo']] = (int) $item['subgrupo_id'];
            foreach ($rows as &$row) {
                $row['existente'] = $this->imports->duplicate($user, $account, $row);
                $row['selected'] = !$row['repetida'] && !$row['existente'];
                $type = $row['centavos'] < 0 ? 'despesa' : 'receita';
                $row['category_id'] = $suggested[mb_strtolower($row['descricao']) . '|' . $type] ?? 0;
            }
            unset($row);
            $this->imports->createDraft($user, $account, $rows);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Prévia salva. Você pode sair e continuar depois.'];
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
            $preview = $this->imports->draft($user);
            if (!$preview || !hash_equals($preview['nonce'], (string) ($_POST['nonce'] ?? '')))
                throw new DomainException('Prévia alterada. Recarregue a página.');
            $selected = is_array($_POST['selecionar'] ?? null) ? $_POST['selecionar'] : [];
            if (isset($_POST['somente'])) {
                $index = filter_var($_POST['somente'], FILTER_VALIDATE_INT);
                if ($index === false || !array_key_exists($index, $preview['rows'])) throw new DomainException('Linha inválida.');
                $selected = [$index => '1'];
            }
            $categories = is_array($_POST['categoria'] ?? null) ? $_POST['categoria'] : [];
            $this->imports->saveDraftChoices($user, $preview['nonce'],
                is_array($_POST['selecionar'] ?? null) ? $_POST['selecionar'] : [], $categories);
            $preview = $this->imports->draft($user);
            $batch = $this->imports->import($user, $preview['account'], $preview['rows'], $selected, $categories, $preview['nonce']);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Lançamento(s) importado(s). Lote ' . $batch . ' disponível para desfazer nesta página.'];
        } catch (DomainException $error) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
        }
        $this->redirect('/movimentacoes/importar');
    }

    public function saveDraft(): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->validateCsrf();
        $ajax = ($_POST['_auto'] ?? '') === '1';
        try {
            $this->imports->saveDraftChoices($user, (string) ($_POST['nonce'] ?? ''),
                is_array($_POST['selecionar'] ?? null) ? $_POST['selecionar'] : [],
                is_array($_POST['categoria'] ?? null) ? $_POST['categoria'] : []);
            if ($ajax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => true]);
                return;
            }
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Prévia salva. Continue quando quiser.'];
            $this->redirect(($_POST['destino'] ?? '') === 'categorias' ? '/categorias' : '/movimentacoes/importar');
        } catch (DomainException $error) {
            if ($ajax) {
                http_response_code(409);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'error' => $error->getMessage()]);
                return;
            }
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
            $this->redirect('/movimentacoes/importar');
        }
    }

    public function removeRow(): void
    {
        $this->action('/movimentacoes/importar', function (int $user): void {
            $nonce = (string) ($_POST['nonce'] ?? '');
            $this->imports->saveDraftChoices($user, $nonce,
                is_array($_POST['selecionar'] ?? null) ? $_POST['selecionar'] : [],
                is_array($_POST['categoria'] ?? null) ? $_POST['categoria'] : []);
            $index = filter_var($_POST['indice'] ?? null, FILTER_VALIDATE_INT);
            if ($index === false) throw new DomainException('Linha inválida.');
            $this->imports->removeDraftRow($user, $nonce, $index);
        }, 'Linha excluída da prévia.');
    }

    public function discard(): void
    {
        $this->action('/movimentacoes/importar', fn(int $user) => $this->imports->discardDraft(
            $user, (string) ($_POST['nonce'] ?? '')), 'Prévia descartada.');
    }

    public function undo(): void
    {
        $this->action('/movimentacoes/importar', fn($user) => $this->imports->undo($user, (int) ($_POST['lote_id'] ?? 0)), 'Lote desfeito. Lançamentos preservados em Arquivados.');
    }
}
