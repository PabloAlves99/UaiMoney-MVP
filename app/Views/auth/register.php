<div class="text-center mb-4">

    <div class="auth-subtitle mt-2">
        Comece a organizar sua vida financeira.
    </div>

</div>


<h4 class="mb-4">
    Criar conta
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
    $basePath . ($registrationPath ?? '/register'),
    ENT_QUOTES,
    'UTF-8'
) ?>">

    <input type="hidden" name="_token" value="<?= htmlspecialchars(
        $csrfToken,
        ENT_QUOTES,
        'UTF-8'
    ) ?>">


    <div class="mb-3">

        <label for="nome" class="form-label">
            Nome
        </label>

        <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars(
            $nome,
            ENT_QUOTES,
            'UTF-8'
        ) ?>" required autofocus>

    </div>


    <div class="mb-3">

        <label for="login" class="form-label">
            Login
        </label>

        <input type="text" class="form-control" id="login" name="login" value="<?= htmlspecialchars(
            $login,
            ENT_QUOTES,
            'UTF-8'
        ) ?>" required>

    </div>


    <div class="mb-3">

        <label for="email" class="form-label">
            E-mail
        </label>

        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars(
            $email,
            ENT_QUOTES,
            'UTF-8'
        ) ?>" required>

    </div>


    <div class="mb-3">

        <label for="senha" class="form-label">
            Senha
        </label>

        <input type="password" class="form-control" id="senha" name="senha" autocomplete="new-password" minlength="8" required>

        <div class="form-text">
            Mínimo de 8 caracteres.
        </div>

    </div>


    <div class="mb-4">

        <label for="confirmacao_senha" class="form-label">
            Confirmar senha
        </label>

        <input type="password" class="form-control" id="confirmacao_senha" name="confirmacao_senha" autocomplete="new-password" minlength="8"
            required>

    </div>


    <button type="submit" class="btn btn-success w-100">
        Criar conta
    </button>

</form>


<div class="text-center mt-4">

    <span class="text-secondary">
        Já possui conta?
    </span>

    <a href="<?= htmlspecialchars(
        $basePath . '/login',
        ENT_QUOTES,
        'UTF-8'
    ) ?>" class="text-decoration-none fw-semibold">
        Entrar
    </a>

</div>
