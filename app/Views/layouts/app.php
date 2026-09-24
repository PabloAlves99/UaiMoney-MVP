<?php
use App\Core\Html as H;
$path=parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH);
$relative=$basePath!=='' && str_starts_with($path,$basePath) ? substr($path,strlen($basePath)) : $path;
$navigation=['/'=>'Visão geral','/movimentacoes'=>'Movimentações','/contas'=>'Contas','/cartoes'=>'Cartões','/recorrencias'=>'Recorrências','/planejamento'=>'Planejamento','/analises'=>'Análises','/categorias'=>'Categorias'];
$flash=$_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="pt-BR" data-bs-theme="<?= H::escape($usuario['tema'] ?? 'light') ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark"><title><?= H::escape($pageTitle ?? 'UaiMoney') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= H::escape($basePath.'/css/app.css') ?>" rel="stylesheet">
</head>
<body class="has-mobile-nav">
<a class="skip-link" href="#main-content">Pular para o conteúdo</a>
<aside class="app-sidebar">
<a class="uai-brand" href="<?= H::escape($basePath.'/') ?>"><span class="uai-brand-mark">U</span><span class="uai-brand-name">UaiMoney<span class="brand-caption">Seu dinheiro, bem cuidado.</span></span></a>
<p class="sidebar-label">MEU ESPAÇO</p>
<nav class="sidebar-nav" aria-label="Navegação principal">
<?php foreach($navigation as $url=>$label): $active=$url==='/' ? ($relative==='' || $relative==='/') : ($relative===$url || str_starts_with($relative,$url.'/')); ?>
<a class="<?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> href="<?= H::escape($basePath.$url) ?>"><span class="nav-dot" aria-hidden="true"></span><?= $label ?></a>
<?php endforeach; ?>
</nav>
<div class="sidebar-footer"><a href="<?= H::escape($basePath.'/comecar') ?>">Primeiros passos →</a><p class="small text-secondary mt-2 mb-0">Um passo de cada vez.<br>Mais clareza todos os dias.</p></div>
</aside>
<div class="app-workspace">
<header class="app-topbar"><div class="d-flex align-items-center gap-2"><span class="user-avatar" aria-hidden="true"><?= H::escape(mb_substr($usuario['nome'],0,1)) ?></span><span class="d-none d-md-inline"><?= H::escape($usuario['nome']) ?></span></div>
<div class="d-flex align-items-center gap-2"><a class="btn btn-uai-primary btn-sm" href="<?= H::escape($basePath.'/movimentacoes/nova') ?>">+ Novo lançamento</a><button type="button" class="btn btn-outline-secondary btn-sm" id="themeToggle" aria-label="Alternar tema claro e escuro">Alternar tema</button>
<form method="post" action="<?= H::escape($basePath.'/logout') ?>" class="m-0"><input type="hidden" id="csrfToken" name="_token" value="<?= H::escape($csrfToken) ?>"><button class="btn btn-outline-secondary btn-sm">Sair</button></form></div></header>
<main id="main-content" class="app-main" tabindex="-1">
<?php if($flash): ?><div class="alert alert-<?= in_array($flash['type'],['danger','success','warning'],true) ? $flash['type'] : 'info' ?>" role="status"><?= H::escape($flash['message']) ?></div><?php endif; ?>
<?= $content ?>
</main><footer class="app-footer">UaiMoney · Organize hoje. Planeje o amanhã.</footer>
</div>
<nav class="mobile-nav" aria-label="Navegação rápida">
    <?php foreach (['/' => 'Início', '/movimentacoes' => 'Movimentos', '/movimentacoes/nova' => '+ Novo', '/analises' => 'Análises', '/contas' => 'Contas'] as $url => $label): ?>
        <a href="<?= H::escape($basePath . $url) ?>" class="<?= $relative === $url ? 'active' : '' ?>" <?= $relative === $url ? 'aria-current="page"' : '' ?>><?= $label ?></a>
    <?php endforeach; ?>
</nav><script>window.UaiMoney={themeUrl:<?= json_encode($basePath.'/preferencias/tema',JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>};</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= H::escape($basePath.'/js/theme.js') ?>"></script>
<script src="<?= H::escape($basePath.'/js/app.js') ?>"></script>
</body></html>
