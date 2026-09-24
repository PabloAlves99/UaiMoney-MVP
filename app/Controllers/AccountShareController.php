<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\RateLimiter;
use App\Core\View;
use App\Repositories\AccountRepository;
use App\Repositories\AccountShareRepository;
use App\Services\AuthService;
use App\Services\PublicAccountExportService;
use DomainException;

final class AccountShareController extends BaseController
{
    public function __construct(
        AuthService $auth,
        Csrf $csrf,
        string $basePath,
        private readonly AccountRepository $accounts,
        private readonly AccountShareRepository $shares,
        private readonly RateLimiter $limiter,
        private readonly PublicAccountExportService $exports
    ) {
        parent::__construct($auth, $csrf, $basePath);
    }

    public function manage(string $id): void
    {
        $user = (int) $this->requireUser()['id'];
        $accountId = $this->parseId($id);
        $account = $this->accounts->findById($accountId, $user);
        if (!$account) {
            http_response_code(404);
            View::render('errors/not-found', ['basePath'=>$this->basePath, 'pageTitle'=>'Conta não encontrada - UaiMoney'], 'layouts/auth');
            return;
        }
        $newToken = $_SESSION['new_share_tokens'][$accountId] ?? null;
        unset($_SESSION['new_share_tokens'][$accountId]);
        $flash = $_SESSION['share_flash'] ?? null;
        unset($_SESSION['share_flash']);
        View::render('shares/manage', [
            'account'=>$account,
            'share'=>$this->shares->findForOwner($user, $accountId),
            'newToken'=>$newToken,
            'flash'=>$flash,
            'basePath'=>$this->basePath,
            'csrfToken'=>$this->csrf->token(),
            'pageTitle'=>'Compartilhar conta - UaiMoney',
            'usuario'=>$this->authService->currentUser(),
            'old'=>[],
        ], 'layouts/app');
    }

    public function create(string $id): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->validateCsrf();
        $accountId = $this->parseId($id);
        if (!$this->accounts->findById($accountId, $user)) {
            http_response_code(404);
            exit;
        }
        $password = (string) ($_POST['senha'] ?? '');
        if ($password !== '' && mb_strlen($password) < 6) {
            $_SESSION['share_flash'] = ['type'=>'danger', 'message'=>'A senha deve ter pelo menos 6 caracteres.'];
            $this->redirect('/contas/' . $accountId . '/compartilhar');
        }
        if (mb_strlen($password) > 200) {
            $_SESSION['share_flash'] = ['type'=>'danger', 'message'=>'A senha é muito longa.'];
            $this->redirect('/contas/' . $accountId . '/compartilhar');
        }
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $this->shares->save(
            $user,
            $accountId,
            hash('sha256', $token),
            substr($token, -8),
            $password === '' ? null : password_hash($password, PASSWORD_DEFAULT)
        );
        $_SESSION['new_share_tokens'][$accountId] = $token;
        $_SESSION['share_flash'] = ['type'=>'success', 'message'=>'Novo link criado. O link anterior deixou de funcionar.'];
        $this->redirect('/contas/' . $accountId . '/compartilhar');
    }

    public function disable(string $id): void
    {
        $user = (int) $this->requireUser()['id'];
        $this->validateCsrf();
        $accountId = $this->parseId($id);
        if (!$this->accounts->findById($accountId, $user)) {
            http_response_code(404);
            exit;
        }
        $this->shares->disable($user, $accountId);
        $_SESSION['share_flash'] = ['type'=>'success', 'message'=>'Compartilhamento desativado. O link não dá mais acesso ao extrato.'];
        $this->redirect('/contas/' . $accountId . '/compartilhar');
    }

    public function publicPage(string $token): void
    {
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
            $this->publicNotFound();
            return;
        }
        $share = $this->shares->findByToken($token);
        if (!$share) {
            $this->publicNotFound();
            return;
        }
        $accessKey = 'share_access_' . hash('sha256', $token);
        $authorized = empty($share['senha_hash']) || !empty($_SESSION[$accessKey]);
        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !$authorized) {
            if (!$this->csrf->validate($_POST['_token'] ?? null)) {
                http_response_code(419);
                $error = 'A página expirou. Atualize e tente novamente.';
            } else {
                try {
                    $identity = $share['id'] . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
                    $this->limiter->hit('public-share', $identity, 8, 900);
                    if (!password_verify((string) ($_POST['senha'] ?? ''), (string) $share['senha_hash'])) {
                        throw new DomainException('Senha incorreta.');
                    }
                    $this->limiter->clear('public-share', $identity);
                    $_SESSION[$accessKey] = true;
                    $this->redirect('/compartilhado/' . $token);
                } catch (DomainException $exception) {
                    $error = $exception->getMessage();
                }
            }
        }
        if (!$authorized) {
            View::render('shares/password', [
                'share'=>$share, 'error'=>$error, 'csrfToken'=>$this->csrf->token(),
                'basePath'=>$this->basePath, 'pageTitle'=>'Extrato protegido - UaiMoney'
            ], 'layouts/auth');
            return;
        }
        $filters = $this->filters($_GET);
        $page = max(1, min(100000, (int) ($_GET['pagina'] ?? 1)));
        $account = $this->accounts->findById((int) $share['conta_id'], (int) $share['usuario_id']);
        if (!$account) {
            $this->publicNotFound();
            return;
        }
        $this->shares->touch((int) $share['id']);
        View::render('shares/public', [
            'share'=>$share,
            'balance'=>(int) $account['saldo_atual_centavos'],
            'entries'=>$this->shares->entries($share, $filters, $page),
            'totals'=>$this->shares->totals($share, $filters),
            'monthly'=>$this->shares->monthly($share, $filters),
            'filters'=>$filters, 'page'=>$page, 'token'=>$token,
            'basePath'=>$this->basePath,
            'pageTitle'=>'Extrato de ' . $share['conta_nome'] . ' - UaiMoney'
        ], 'layouts/public');
    }

    public function export(string $token, string $format): void
    {
        if (!preg_match('/^[A-Za-z0-9_-]{43}$/', $token)) {
            $this->publicNotFound();
            return;
        }
        $share = $this->shares->findByToken($token);
        if (!$share) {
            $this->publicNotFound();
            return;
        }
        $accessKey = 'share_access_' . hash('sha256', $token);
        if (!empty($share['senha_hash']) && empty($_SESSION[$accessKey])) {
            $this->redirect('/compartilhado/' . $token);
        }
        if (!in_array($format, ['pdf', 'excel', 'csv'], true)) {
            $this->publicNotFound();
            return;
        }
        $filters = $this->filters($_GET);
        $entries = $this->shares->exportEntries($share, $filters);
        if (count($entries) > 10000) {
            http_response_code(422);
            View::render('errors/http', [
                'code'=>422, 'title'=>'Refine o período para exportar até 10.000 movimentações',
                'basePath'=>$this->basePath, 'pageTitle'=>'Exportação muito grande - UaiMoney'
            ], 'layouts/auth');
            return;
        }
        $totals = $this->shares->totals($share, $filters);
        $description = $this->filterDescription($filters);
        $filename = 'uaimoney-extrato-compartilhado-' . date('Y-m-d');
        if ($format === 'pdf') {
            $this->download($this->exports->pdf((string)$share['conta_nome'], $entries, $totals, $description), 'application/pdf', $filename.'.pdf');
        }
        if ($format === 'excel') {
            $this->download($this->exports->excel((string)$share['conta_nome'], $entries, $totals, $description), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $filename.'.xlsx');
        }
        $this->download($this->exports->csv($entries), 'text/csv; charset=UTF-8', $filename.'.csv');
    }

    private function filters(array $input): array
    {
        $filters = ['inicio'=>'', 'fim'=>'', 'tipo'=>'', 'q'=>''];
        foreach (['inicio', 'fim'] as $key) {
            $value = (string) ($input[$key] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) $filters[$key] = $value;
        }
        $type = (string) ($input['tipo'] ?? '');
        if (in_array($type, ['entrada', 'saida'], true)) $filters['tipo'] = $type;
        $filters['q'] = mb_substr(trim((string) ($input['q'] ?? '')), 0, 100);
        return $filters;
    }

    private function filterDescription(array $filters): string
    {
        $parts = [];
        if ($filters['inicio'] !== '') $parts[] = 'de ' . date('d/m/Y', strtotime($filters['inicio']));
        if ($filters['fim'] !== '') $parts[] = 'até ' . date('d/m/Y', strtotime($filters['fim']));
        if ($filters['tipo'] !== '') $parts[] = $filters['tipo'] === 'entrada' ? 'somente entradas' : 'somente saídas';
        if ($filters['q'] !== '') $parts[] = 'busca: ' . $filters['q'];
        return $parts === [] ? 'sem filtros' : implode(' · ', $parts);
    }

    private function download(string $content, string $contentType, string $filename): never
    {
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));
        echo $content;
        exit;
    }

    private function publicNotFound(): void
    {
        http_response_code(404);
        View::render('errors/http', ['code'=>404, 'title'=>'Link indisponível', 'basePath'=>$this->basePath, 'pageTitle'=>'Link indisponível - UaiMoney'], 'layouts/auth');
    }
}
