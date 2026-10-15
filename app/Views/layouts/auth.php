<!DOCTYPE html>

<html lang="pt-BR" data-bs-theme="light">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="any" href="<?= \App\Core\Html::escape($basePath.'/images/brand/favicon.png?v=10') ?>">

    <title>
        <?= htmlspecialchars(
            $pageTitle ?? 'UaiMoney',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= htmlspecialchars(
        $basePath . '/css/app.css?v=20261015-category-tree',
        ENT_QUOTES,
        'UTF-8'
    ) ?>">

    <?php require __DIR__ . '/theme-head.php'; ?>
</head>


<body>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1000;"><button type="button" class="btn btn-outline-secondary btn-sm theme-toggle" id="themeToggle" aria-label="Alternar tema" title="Alternar tema"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3 6.7 6.7 0 0 0 21 12.8Z"/></svg></button></div>


    <div class="auth-page">

        <div class="card auth-card">

            <div class="card-body p-4 p-md-5">

                <div class="auth-brand mb-4"><?php require __DIR__.'/brand.php'; ?></div>

                <?= $content ?>

            </div>

        </div>

    </div>

    <script src="<?= \App\Core\Html::escape($basePath . '/js/theme.js?v=20261015-graphite-theme') ?>"></script>
</body>

</html>
