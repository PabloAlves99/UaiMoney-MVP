<?php
use App\Core\Html as H;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$relative = $basePath !== '' && str_starts_with($path, $basePath) ? substr($path, strlen($basePath)) : $path;
$planningLinks = ['/planejamento' => 'Planejamento', '/investimentos' => 'Investimentos', '/objetivos' => 'Objetivos', '/projecoes' => 'Projeções'];
$moreLinks = ['/contas' => 'Contas', '/cartoes' => 'Cartões', '/categorias' => 'Categorias', '/recorrencias' => 'Recorrências', '/estrategias' => 'Dicas e estratégias', '/comecar' => 'Primeiros passos'];
if (($usuario['tipo'] ?? '') === 'admin') $moreLinks['/admin/usuarios'] = 'Administração de usuários';
$isActive = fn(string $url): bool => $url === '/' ? ($relative === '' || $relative === '/') : ($relative === $url || str_starts_with($relative, $url . '/'));
$navigation = [
    ['label' => 'Visão geral', 'url' => '/'],
    ['label' => 'Movimentações', 'url' => '/movimentacoes'],
    ['label' => 'Análises', 'url' => '/analises'],
    ['label' => 'Planejar', 'items' => $planningLinks],
    ['label' => 'Mais', 'items' => $moreLinks],
];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="pt-BR" data-bs-theme="<?= H::escape(($usuario['tema'] ?? 'light') === 'dark' ? 'dark' : 'light') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?php require __DIR__ . '/theme-head.php'; ?>
    <title><?= H::escape($pageTitle ?? 'UaiMoney') ?></title>
    <link rel="icon" type="image/png" sizes="any" href="<?= H::escape($basePath . '/images/brand/favicon.png?v=10') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= H::escape($basePath . '/css/app.css?v=20261015-sortable-tables') ?>" rel="stylesheet">
</head>
<body class="top-navigation-layout">
    <a class="skip-link" href="#main-content">Pular para o conteúdo</a>
    <header class="site-header" data-navigation>
        <div class="site-header-bar">
            <a class="uai-brand site-brand" href="<?= H::escape($basePath . '/') ?>"><?php require __DIR__ . '/brand.php'; ?></a>
            <nav class="top-navigation" id="primary-navigation" aria-label="Menu principal">
                <?php foreach ($navigation as $entry): ?>
                    <?php if (isset($entry['url'])): $active = $isActive($entry['url']); ?>
                        <a class="top-navigation-link<?= $active ? ' active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> href="<?= H::escape($basePath . $entry['url']) ?>"><?= H::escape($entry['label']) ?></a>
                    <?php else: $sectionActive = count(array_filter(array_keys($entry['items']), $isActive)) > 0; ?>
                        <div class="dropdown nav-dropdown">
                            <button type="button" class="top-navigation-link dropdown-toggle<?= $sectionActive ? ' active' : '' ?>" data-bs-toggle="dropdown" aria-expanded="false"><?= H::escape($entry['label']) ?></button>
                            <ul class="dropdown-menu navigation-dropdown">
                                <?php foreach ($entry['items'] as $url => $label): $active = $isActive($url); ?>
                                    <?php if ($url === '/admin/usuarios'): ?><li><hr class="dropdown-divider"></li><?php endif; ?>
                                    <li><a class="dropdown-item<?= $active ? ' active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> href="<?= H::escape($basePath . $url) ?>"><?= H::escape($label) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
            <div class="site-header-actions">
                <a class="btn btn-uai-primary btn-sm header-new-entry" href="<?= H::escape($basePath . '/movimentacoes/nova') ?>">+ <span class="new-entry-full">Novo lançamento</span><span class="new-entry-short">Novo</span></a>
                <button type="button" class="btn btn-outline-secondary btn-sm theme-toggle" id="themeToggle" aria-label="Alternar tema" title="Alternar tema"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.7 6.7 0 0 0 21 12.8Z"/></svg></button>
                <div class="dropdown account-dropdown">
                    <button class="account-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= H::escape('Menu de ' . $usuario['nome']) ?>"><span class="user-avatar" aria-hidden="true"><?= H::escape(mb_substr($usuario['nome'], 0, 1)) ?></span></button>
                    <ul class="dropdown-menu dropdown-menu-end navigation-dropdown account-menu">
                        <li class="account-menu-name"><?= H::escape($usuario['nome']) ?><small><?= ($usuario['tipo'] ?? '') === 'admin' ? 'Administrador' : 'Minha conta' ?></small></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="post" action="<?= H::escape($basePath . '/logout') ?>" class="m-0"><input type="hidden" id="csrfToken" name="_token" value="<?= H::escape($csrfToken) ?>"><button class="dropdown-item account-logout" type="submit">Sair da conta</button></form></li>
                    </ul>
                </div>
                <button type="button" class="compact-menu-toggle" data-compact-menu-toggle aria-controls="primary-navigation" aria-expanded="false" aria-label="Abrir menu"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
            </div>
        </div>
    </header>
    <div class="app-workspace">
        <main id="main-content" class="app-main" tabindex="-1">
            <?php if ($flash): ?>
                <div class="alert alert-<?= in_array($flash['type'], ['danger', 'success', 'warning'], true) ? $flash['type'] : 'info' ?>" role="status"><?= H::escape($flash['message']) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </main>
        <footer class="app-footer">UaiMoney · Organize hoje. Planeje o amanhã.</footer>
    </div>
    <script>window.UaiMoney = { themeUrl: <?= json_encode($basePath . '/preferencias/tema', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> };</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= H::escape($basePath . '/js/app.js?v=20261015-async-sort') ?>"></script>
    <script src="<?= H::escape($basePath . '/js/financial-overview.js?v=20261014') ?>"></script>
    <script src="<?= \App\Core\Html::escape($basePath . '/js/theme.js?v=20261015-graphite-theme') ?>"></script>
    <script src="<?= \App\Core\Html::escape($basePath . '/js/table-sort.js?v=20261015-async') ?>"></script>
</body>
</html>
