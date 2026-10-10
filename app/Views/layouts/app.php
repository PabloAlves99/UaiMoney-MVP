<?php
use App\Core\Html as H;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$relative = $basePath !== '' && str_starts_with($path, $basePath) ? substr($path, strlen($basePath)) : $path;
$navigation = [
    'Cadastrar e editar' => ['/' => 'Visão geral', '/movimentacoes' => 'Movimentações', '/contas' => 'Contas', '/cartoes' => 'Cartões', '/categorias' => 'Categorias', '/recorrencias' => 'Recorrências'],
    'Planejar' => ['/planejamento' => 'Planejamento', '/investimentos' => 'Investimentos', '/objetivos' => 'Objetivos'],
    'Analisar' => ['/analises' => 'Análises'],
    'Dicas' => ['/estrategias' => 'Estratégias'],
];
if (($usuario['tipo'] ?? '') === 'admin') $navigation['/admin/usuarios'] = 'Usuários';
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="pt-BR" data-bs-theme="<?= H::escape($usuario['tema'] ?? 'light') ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <title><?= H::escape($pageTitle ?? 'UaiMoney') ?></title>
    <link rel="icon" type="image/png" sizes="any" href="<?= H::escape($basePath . '/images/brand/favicon.png?v=10') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= H::escape($basePath . '/css/app.css?v=20261009-budget-icons') ?>" rel="stylesheet">
</head>

<body class="has-mobile-nav">
    <a class="skip-link" href="#main-content">Pular para o conteúdo</a>
    <div class="mobile-menu-backdrop" data-mobile-menu-close hidden></div>
    <aside class="app-sidebar" id="app-sidebar" aria-label="Menu principal">
        <div class="sidebar-brand-row"><a class="uai-brand" href="<?= H::escape($basePath . '/') ?>"><?php require __DIR__ . '/brand.php'; ?></a><button class="sidebar-toggle" type="button" aria-controls="app-sidebar" aria-expanded="true" title="Recolher menu"><svg class="sidebar-toggle-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg><span class="visually-hidden">Recolher menu</span></button></div>
        <span class="brand-caption">Seu dinheiro, bem cuidado.</span>
        <div class="sidebar-scroll">
            <?php foreach ($navigation as $section => $items): ?>
                <section class="sidebar-section"><p class="sidebar-label"><?= H::escape($section) ?></p><nav class="sidebar-nav" aria-label="<?= H::escape($section) ?>">
                    <?php foreach ($items as $url => $label): $active = $url === '/' ? ($relative === '' || $relative === '/') : ($relative === $url || str_starts_with($relative, $url . '/')); ?>
                        <a class="<?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> href="<?= H::escape($basePath . $url) ?>"><span class="nav-dot" aria-hidden="true"></span><span class="nav-label"><?= H::escape($label) ?></span></a>
                    <?php endforeach; ?>
                </nav></section>
            <?php endforeach; ?>
        </div>
        <div class="sidebar-footer"><a href="<?= H::escape($basePath . '/comecar') ?>">Primeiros passos →</a>
            <p class="small text-secondary mt-2 mb-0">Um passo de cada vez.<br>Mais clareza todos os dias.</p>
        </div>
    </aside>
    <div class="app-workspace">
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-2"><button class="mobile-menu-toggle" type="button" aria-controls="app-sidebar" aria-expanded="false" aria-label="Abrir menu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg></button><span class="user-avatar"
                    aria-hidden="true"><?= H::escape(mb_substr($usuario['nome'], 0, 1)) ?></span><span
                    class="d-none d-md-inline"><?= H::escape($usuario['nome']) ?></span></div>
            <div class="d-flex align-items-center gap-2"><a class="btn btn-uai-primary btn-sm"
                    href="<?= H::escape($basePath . '/movimentacoes/nova') ?>">+ Novo lançamento</a><button type="button"
                    class="btn btn-outline-secondary btn-sm theme-toggle" id="themeToggle"
                    aria-label="Ativar tema escuro" title="Ativar tema escuro"></button>
                <form method="post" action="<?= H::escape($basePath . '/logout') ?>" class="m-0"><input type="hidden"
                        id="csrfToken" name="_token" value="<?= H::escape($csrfToken) ?>"><button
                        class="btn btn-outline-secondary btn-sm">Sair</button></form>
            </div>
        </header>
        <main id="main-content" class="app-main" tabindex="-1">
            <?php if ($flash): ?>
                <div class="alert alert-<?= in_array($flash['type'], ['danger', 'success', 'warning'], true) ? $flash['type'] : 'info' ?>"
                    role="status"><?= H::escape($flash['message']) ?></div><?php endif; ?>
            <?= $content ?>
        </main>
        <footer class="app-footer">UaiMoney · Organize hoje. Planeje o amanhã.</footer>
    </div>
    <nav class="mobile-nav" aria-label="Navegação rápida">
        <?php foreach (['/' => 'Início', '/movimentacoes' => 'Movimentos', '/movimentacoes/nova' => '+ Novo', '/analises' => 'Análises', '/categorias' => 'Categorias'] as $url => $label): ?>
            <a href="<?= H::escape($basePath . $url) ?>" class="<?= $relative === $url ? 'active' : '' ?><?= $url === '/movimentacoes/nova' ? ' mobile-nav-action' : '' ?>"
                <?= $relative === $url ? 'aria-current="page"' : '' ?>><?= $label ?></a>
        <?php endforeach; ?>
    </nav>
    <script>window.UaiMoney = { themeUrl: <?= json_encode($basePath . '/preferencias/tema', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> };</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= H::escape($basePath . '/js/theme.js?v=20261009-icons') ?>"></script>
    <script src="<?= H::escape($basePath . '/js/app.js?v=20260926-mobile-menu') ?>"></script>
</body>

</html>
