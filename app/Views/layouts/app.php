<!DOCTYPE html>

<html lang="pt-BR" data-bs-theme="<?= htmlspecialchars(
                                        $usuario['tema'] ?? 'light',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        <?= htmlspecialchars(
            $pageTitle ?? 'UaiMoney',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </title>


    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= htmlspecialchars(
                                        $basePath . '/css/app.css',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

</head>


<body>

    <nav class="navbar navbar-expand-lg border-bottom">

        <div class="container-fluid">

            <a class="uai-brand" href="<?= htmlspecialchars(
                                            $basePath . '/',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                <span class="uai-brand-mark">
                    U
                </span>

                <span class="uai-brand-name">
                    UaiMoney
                </span>

            </a>

            <a href="<?= htmlspecialchars(
                            $basePath . '/categorias',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>" class="btn btn-sm btn-outline-secondary">
                Categorias
            </a>

            <a href="<?= htmlspecialchars(
                            $basePath . '/contas',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>" class="btn btn-sm btn-outline-secondary">
                Contas
            </a>

            <a
                href="<?= htmlspecialchars(
                            $basePath . '/movimentacoes',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                class="btn btn-sm btn-outline-secondary">
                Movimentações
            </a>


            <div class="d-flex align-items-center gap-2">

                <span class="d-none d-md-inline text-secondary">
                    <?= htmlspecialchars(
                        $usuario['nome'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </span>


                <button type="button" class="btn btn-outline-secondary btn-sm" id="themeToggle">
                    Alternar tema
                </button>


                <form method="POST" action="<?= htmlspecialchars(
                                                $basePath . '/logout',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>" class="m-0">

                    <input type="hidden" id="csrfToken" name="_token" value="<?= htmlspecialchars(
                                                                                    $csrfToken,
                                                                                    ENT_QUOTES,
                                                                                    'UTF-8'
                                                                                ) ?>">

                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        Sair
                    </button>

                </form>

            </div>

        </div>

    </nav>


    <main class="container py-4">

        <?= $content ?>

    </main>


    <script>
        window.UaiMoney = {

            themeUrl: <?= json_encode(
                            $basePath
                                . '/preferencias/tema'
                        ) ?>

        };
    </script>


    <script src="<?= htmlspecialchars(
                        $basePath . '/js/theme.js',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"></script>

</body>

</html>