<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>UaiMoney</title>
</head>

<body>

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

        <button type="submit">
            Sair
        </button>

    </form>

</body>

</html>