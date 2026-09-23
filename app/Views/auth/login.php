<div class="text-center mb-4">

    <div class="text-center mb-4">

        <div class="d-flex justify-content-center mb-3">

            <span class="uai-brand-mark">
                U
            </span>

        </div>

        <div class="auth-logo">
            UaiMoney
        </div>

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

    <input type="hidden" name="_token" value="<?= htmlspecialchars(
        $csrfToken,
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
<p class="text-center mt-4 mb-0">Primeira vez aqui? <a href="<?= htmlspecialchars($basePath.'/criar-conta',ENT_QUOTES,'UTF-8') ?>">Criar minha conta</a></p>
