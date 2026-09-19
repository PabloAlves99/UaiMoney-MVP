<?php

declare(strict_types=1);

use App\Core\Money;


$tiposConta = [
    'corrente' => 'Conta corrente',
    'poupanca' => 'Poupança',
    'dinheiro' => 'Dinheiro',
    'carteira_digital' => 'Carteira digital',
    'outro' => 'Outro'
];

?>


<!--
|--------------------------------------------------------------------------
| Voltar
|--------------------------------------------------------------------------
-->

<div class="mb-3">

    <a href="<?= htmlspecialchars(
        $basePath
        . '/contas/'
        . (int) $conta['id'],
        ENT_QUOTES,
        'UTF-8'
    ) ?>" class="text-decoration-none">
        ← Voltar para a conta
    </a>

</div>


<!--
|--------------------------------------------------------------------------
| Cabeçalho
|--------------------------------------------------------------------------
-->

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">

    <div>

        <h1 class="h3 mb-1">
            Editar conta
        </h1>

        <p class="text-uai-muted mb-0">

            Atualize os dados cadastrais de

            <strong>
                <?= htmlspecialchars(
                    $conta['nome'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </strong>.

        </p>

    </div>

</div>


<?php if ($error !== null): ?>

    <div class="alert alert-danger">

        <?= htmlspecialchars(
            $error,
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </div>

<?php endif; ?>


<div class="row g-4">


    <!--
    |--------------------------------------------------------------------------
    | Formulário
    |--------------------------------------------------------------------------
    -->

    <div class="col-lg-7">

        <div class="card">

            <div class="card-body">

                <h5 class="card-title mb-4">
                    Dados da conta
                </h5>


                <form method="POST" action="<?= htmlspecialchars(
                    $basePath
                    . '/contas/'
                    . (int) $conta['id']
                    . '/editar',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">

                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">


                    <div class="row g-3">


                        <!-- Nome -->

                        <div class="col-12">

                            <label for="contaNome" class="form-label">
                                Nome
                            </label>

                            <input type="text" class="form-control" id="contaNome" name="nome" value="<?= htmlspecialchars(
                                $formData['nome'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>" required>

                        </div>


                        <!-- Tipo -->

                        <div class="col-md-6">

                            <label for="contaTipo" class="form-label">
                                Tipo
                            </label>

                            <select class="form-select" id="contaTipo" name="tipo" required>

                                <?php foreach (
                                    $tiposConta
                                    as $value => $label
                                ): ?>

                                    <option value="<?= htmlspecialchars(
                                        $value,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>" <?= $formData['tipo']
                                         === $value
                                         ? 'selected'
                                         : ''
                                         ?>>

                                        <?= htmlspecialchars(
                                            $label,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- Instituição -->

                        <div class="col-md-6">

                            <label for="contaInstituicao" class="form-label">
                                Instituição
                            </label>

                            <input type="text" class="form-control" id="contaInstituicao" name="instituicao" value="<?= htmlspecialchars(
                                $formData['instituicao']
                                ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>" placeholder="Ex.: Nubank">

                        </div>


                        <div class="col-12 mt-4">

                            <div class="d-flex gap-2">

                                <button type="submit" class="btn btn-uai-primary">
                                    Salvar alterações
                                </button>


                                <a href="<?= htmlspecialchars(
                                    $basePath
                                    . '/contas/'
                                    . (int) $conta['id'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>" class="btn btn-outline-secondary">
                                    Cancelar
                                </a>

                            </div>

                        </div>


                    </div>

                </form>

            </div>

        </div>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | Dados financeiros protegidos
    |--------------------------------------------------------------------------
    -->

    <div class="col-lg-5">

        <div class="card h-100">

            <div class="card-body">

                <h5 class="card-title mb-2">
                    Dados financeiros
                </h5>

                <p class="text-uai-muted small mb-4">
                    Estes dados definem o ponto
                    inicial do histórico da conta.
                </p>


                <div class="mb-4">

                    <div class="uai-summary-label">
                        Saldo atual
                    </div>

                    <div class="fs-4 fw-semibold <?= (int) 
                        $conta['saldo_atual_centavos']
                        < 0
                        ? 'text-danger'
                        : ''
                        ?>">

                        <?= Money::format(
                            (int) 
                            $conta[
                                'saldo_atual_centavos'
                            ]
                        ) ?>

                    </div>

                </div>


                <div class="mb-4">

                    <div class="uai-summary-label">
                        Saldo inicial
                    </div>

                    <div class="fw-semibold">

                        <?= Money::format(
                            (int) 
                            $conta[
                                'saldo_inicial_centavos'
                            ]
                        ) ?>

                    </div>

                </div>


                <div class="mb-4">

                    <div class="uai-summary-label">
                        Data inicial
                    </div>

                    <div class="fw-semibold">

                        <?= date(
                            'd/m/Y',
                            strtotime(
                                $conta[
                                    'saldo_inicial_em'
                                ]
                            )
                        ) ?>

                    </div>

                </div>


                <div class="alert alert-warning mb-0">

                    <strong>
                        Saldo inicial protegido.
                    </strong>

                    <div class="small mt-1">

                        Alterar o saldo ou a data
                        inicial modificaria o cálculo
                        histórico da conta.

                    </div>

                </div>

            </div>

        </div>

    </div>


</div>