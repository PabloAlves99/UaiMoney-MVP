<!DOCTYPE html>

<html lang="pt-BR" data-bs-theme="light">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" sizes="any" href="<?= \App\Core\Html::escape($basePath.'/images/brand/favicon.svg?v=3') ?>">

    <title>
        <?= htmlspecialchars(
            $pageTitle ?? 'UaiMoney',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>


    <script>
        (() => {

            const theme = localStorage.getItem(
                'uaimoney_theme'
            );

            if (
                theme === 'light' ||
                theme === 'dark'
            ) {
                document.documentElement.setAttribute(
                    'data-bs-theme',
                    theme
                );
            }

        })();
    </script>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= htmlspecialchars(
        $basePath . '/css/app.css?v=20260925-brand',
        ENT_QUOTES,
        'UTF-8'
    ) ?>">

</head>


<body>

    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1000;">

        <button type="button" class="btn btn-outline-secondary btn-sm" id="themeToggle">
            Alternar tema
        </button>

    </div>


    <div class="auth-page">

        <div class="card auth-card">

            <div class="card-body p-4 p-md-5">

                <div class="auth-brand mb-4"><?php require __DIR__.'/brand.php'; ?></div>

                <?= $content ?>

            </div>

        </div>

    </div>


    <script src="<?= htmlspecialchars(
        $basePath . '/js/theme.js',
        ENT_QUOTES,
        'UTF-8'
    ) ?>"></script>

</body>

</html>
