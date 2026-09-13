<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="<?= htmlspecialchars(
    $usuario['tema'],
    ENT_QUOTES,
    'UTF-8'
) ?>">

<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= htmlspecialchars(
        $basePath . '/css/app.css',
        ENT_QUOTES,
        'UTF-8'
    ) ?>">

    <title>UaiMoney</title>
</head>

<body>
    <button type="button" class="btn btn-outline-secondary" id="themeToggle">
        Alternar tema
    </button>

    <h1>
        UaiMoney
    </h1>

    <p>
        Bem-vindo,
        <strong>
            <?= htmlspecialchars(
                $usuario['nome'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </strong>
    </p>


    <p>
        Seu login é:

        <?= htmlspecialchars(
            $usuario['login'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </p>


    <form method="POST" action="<?= htmlspecialchars(
        $basePath . '/logout',
        ENT_QUOTES,
        'UTF-8'
    ) ?>">

        <input type="hidden" id="csrfToken" name="_token" value="<?= htmlspecialchars(
            $csrfToken,
            ENT_QUOTES,
            'UTF-8'
        ) ?>">

        <button type="submit">
            Sair
        </button>

    </form>

    <script>
        window.UaiMoney = {
            themeUrl: <?= json_encode(
                $basePath . '/preferencias/tema'
            ) ?>
        };
    </script>

    <script src="<?= htmlspecialchars(
        $basePath . '/js/theme.js',
        ENT_QUOTES,
        'UTF-8'
    ) ?>">
    </script>
</body>

</html>