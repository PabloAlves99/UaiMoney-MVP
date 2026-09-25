<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <link rel="icon" type="image/svg+xml" sizes="any" href="<?= \App\Core\Html::escape($basePath.'/images/brand/favicon.svg?v=3') ?>">
    <title><?= \App\Core\Html::escape($pageTitle ?? 'UaiMoney') ?></title>
    <script>(()=>{const t=localStorage.getItem('uaimoney_theme');if(t==='light'||t==='dark')document.documentElement.setAttribute('data-bs-theme',t)})();</script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= \App\Core\Html::escape($basePath . '/css/app.css?v=20260925-brand') ?>">
</head>
<body class="public-share-page">
    <header class="public-share-header"><div class="container d-flex justify-content-between align-items-center gap-3"><?php require __DIR__.'/brand.php'; ?><button type="button" class="btn btn-outline-secondary btn-sm" id="themeToggle">Alternar tema</button></div></header>
    <main class="container py-4 py-md-5"><?= $content ?></main>
    <script src="<?= \App\Core\Html::escape($basePath . '/js/theme.js') ?>"></script>
</body>
</html>
