<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Login - UaiMoney</title>

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

            <div class="card-body p-4 p-md-5">

                <div class="text-center mb-4">

                    <div class="auth-logo">
                        UaiMoney
                    </div>

                    <div class="auth-subtitle mt-2">
                        Controle suas finanças de forma simples.
                    </div>

                </div>


                <h4 class="mb-4">
                    Entrar
                </h4>


                <?php if ($error !== null): ?>

                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                <?php endif; ?>


                <form method="POST" action="<?= htmlspecialchars(
                    $basePath . '/login',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">

                    <div class="mb-3">

                        <label for="identifier" class="form-label">
                            Login ou e-mail
                        </label>

                        <input type="text" class="form-control form-control-lg" id="identifier" name="identifier" value="<?= htmlspecialchars(
                            $identifier,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>" required autofocus>

                    </div>


                    <div class="mb-4">

                        <label for="senha" class="form-label">
                            Senha
                        </label>

                        <input type="password" class="form-control form-control-lg" id="senha" name="senha" required>

                    </div>


                    <button type="submit" class="btn btn-success btn-lg w-100">
                        Entrar
                    </button>

                </form>


                <div class="text-center mt-4">

                    <span class="text-secondary">
                        Ainda não possui conta?
                    </span>

                    <a href="<?= htmlspecialchars(
                        $basePath . '/register',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>" class="text-decoration-none fw-semibold">
                        Criar conta
                    </a>

                </div>

            </div>

        </div>

    </div>

</body>

</html>