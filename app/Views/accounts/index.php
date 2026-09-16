<?php

use App\Core\Money;

?>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="h3 mb-1">
            Contas
        </h1>

        <p class="text-uai-muted mb-0">
            Organize onde seu dinheiro está.
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


    <!-- Cadastro -->

    <div class="col-lg-5">

        <div class="card">

            <div class="card-body">

                <h5 class="card-title mb-4">
                    Nova conta
                </h5>


                <form method="POST" action="<?= htmlspecialchars(
                                                $basePath . '/contas',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                                    $csrfToken,
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">


                    <div class="mb-3">

                        <label class="form-label" for="nome">
                            Nome
                        </label>

                        <input type="text" class="form-control" id="nome" name="nome" placeholder="Ex.: Nubank"
                            required>

                    </div>


                    <div class="mb-3">

                        <label class="form-label" for="tipo">
                            Tipo
                        </label>

                        <select class="form-select" id="tipo" name="tipo" required>

                            <option value="">
                                Selecione
                            </option>

                            <option value="corrente">
                                Conta corrente
                            </option>

                            <option value="poupanca">
                                Poupança
                            </option>

                            <option value="dinheiro">
                                Dinheiro
                            </option>

                            <option value="carteira_digital">
                                Carteira digital
                            </option>

                            <option value="outro">
                                Outro
                            </option>

                        </select>

                    </div>


                    <div class="mb-3">

                        <label class="form-label" for="instituicao">
                            Instituição
                        </label>

                        <input type="text" class="form-control" id="instituicao" name="instituicao"
                            placeholder="Ex.: Nubank">

                    </div>


                    <div class="mb-3">

                        <label class="form-label" for="saldoInicial">
                            Saldo inicial
                        </label>

                        <input type="number" class="form-control" id="saldoInicial" name="saldo_inicial" step="0.01"
                            min="0" value="0.00" required>

                    </div>


                    <div class="mb-4">

                        <label class="form-label" for="saldoInicialEm">
                            Data do saldo inicial
                        </label>

                        <input type="date" class="form-control" id="saldoInicialEm" name="saldo_inicial_em"
                            value="<?= date('Y-m-d') ?>" required>

                    </div>


                    <button type="submit" class="btn btn-uai-primary">
                        Criar conta
                    </button>

                </form>

            </div>

        </div>

    </div>


    <!-- Contas cadastradas -->

    <div class="col-lg-7">

        <?php if ($contas === []): ?>

            <div class="card">

                <div class="card-body text-center py-5">

                    <h5>
                        Nenhuma conta cadastrada
                    </h5>

                    <p class="text-uai-muted mb-0">
                        Cadastre sua primeira conta
                        para começar.
                    </p>

                </div>

            </div>


        <?php else: ?>


            <div class="row g-3">


                <?php foreach ($contas as $conta): ?>

                    <div class="col-12">

                        <div class="card">

                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-start">

                                    <div>

                                        <h5 class="mb-1">

                                            <?= htmlspecialchars(
                                                $conta['nome'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </h5>


                                        <div class="text-uai-muted small">

                                            <?= htmlspecialchars(
                                                $conta['instituicao']
                                                    ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>

                                    </div>


                                    <form method="POST" action="<?= htmlspecialchars(
                                                                    $basePath
                                                                        . '/contas/'
                                                                        . (int) $conta['id']
                                                                        . '/desativar',
                                                                    ENT_QUOTES,
                                                                    'UTF-8'
                                                                ) ?>">

                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                                                        $csrfToken,
                                                                                        ENT_QUOTES,
                                                                                        'UTF-8'
                                                                                    ) ?>">

                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Desativar
                                        </button>

                                    </form>

                                </div>


                                <?php

                                $saldoAtual =
                                    (int) $conta['saldo_atual_centavos'];

                                $saldoInicial =
                                    (int) $conta['saldo_inicial_centavos'];

                                ?>


                                <div class="mt-4">

                                    <small class="text-uai-muted">
                                        Saldo atual
                                    </small>

                                    <div
                                        class="fs-3 fw-semibold <?= $saldoAtual < 0
                                                                    ? 'text-danger'
                                                                    : ''
                                                                ?>">

                                        <?= Money::format(
                                            $saldoAtual
                                        ) ?>

                                    </div>

                                </div>


                                <div class="mt-3">

                                    <small class="text-uai-muted">
                                        Saldo inicial
                                    </small>

                                    <div class="fw-semibold">

                                        <?= Money::format(
                                            $saldoInicial
                                        ) ?>

                                    </div>

                                </div>


                                <div class="mt-2 small text-uai-muted">

                                    Saldo inicial informado em

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $conta['saldo_inicial_em']
                                        )
                                    ) ?>

                                </div>


                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>


            </div>

        <?php endif; ?>

    </div>

</div>