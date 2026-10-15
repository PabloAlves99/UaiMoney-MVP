<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <link rel="icon" type="image/png" sizes="any" href="<?= \App\Core\Html::escape($basePath.'/images/brand/favicon.png?v=10') ?>">
    <title><?= \App\Core\Html::escape($pageTitle ?? 'UaiMoney') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= \App\Core\Html::escape($basePath . '/css/app.css?v=20261015-sortable-tables') ?>">
    <?php require __DIR__ . '/theme-head.php'; ?>
</head>
<body class="public-share-page">
    <header class="public-share-header"><div class="container d-flex justify-content-between align-items-center gap-3"><?php require __DIR__.'/brand.php'; ?><button type="button" class="btn btn-outline-secondary btn-sm theme-toggle" id="themeToggle" aria-label="Alternar tema" title="Alternar tema"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.7 6.7 0 0 0 21 12.8Z"/></svg></button></div></header>
    <main class="container py-4 py-md-5"><?= $content ?></main>
    <script src="<?= \App\Core\Html::escape($basePath . '/js/financial-overview.js?v=20261014') ?>"></script>
    <script src="<?= \App\Core\Html::escape($basePath . '/js/theme.js?v=20261015-graphite-theme') ?>"></script>
    <script src="<?= \App\Core\Html::escape($basePath . '/js/table-sort.js?v=20261015') ?>"></script>
</body>
</html>
