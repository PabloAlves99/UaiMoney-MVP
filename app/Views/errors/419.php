<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Sessão expirada - UaiMoney</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="<?= htmlspecialchars(
        $basePath . '/css/app.css',
        ENT_QUOTES,
        'UTF-8'
    ) ?>">
</head>

<body>

    <div class="auth-page">

        <div class="card auth-card">

            <div class="card-body p-5 text-center">

                <h1 class="h3 mb-3">
                    Sessão expirada
                </h1>

                <p class="text-secondary">
                    Não foi possível validar a requisição.
                    Atualize a página e tente novamente.
                </p>

                <a href="<?= htmlspecialchars(
                    $basePath . '/login',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>" class="btn btn-success">
                    Voltar ao login
                </a>

            </div>

        </div>

    </div>

</body>

</html>