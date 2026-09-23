<?php

declare(strict_types=1);

use App\Core\Money;


/*
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
*/

$saldoTotal = 0;


foreach ($contas as $conta) {

    $saldoTotal +=
        (int) $conta['saldo_atual_centavos'];
}


/*
|--------------------------------------------------------------------------
| Tipos de conta
|--------------------------------------------------------------------------
*/

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
| Cabeçalho
|--------------------------------------------------------------------------
-->

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

    <div>

        <h1 class="h3 mb-1">
            Contas
        </h1>

        <p class="text-uai-muted mb-0">
            Acompanhe onde seu dinheiro está.
        </p>

    </div>


    <button type="button" class="btn btn-uai-primary" data-bs-toggle="modal" data-bs-target="#novaContaModal">
        + Nova conta
    </button>

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


<!--
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
-->

<div class="row g-3 mb-4">


    <div class="col-md-6 col-xl-4">

        <div class="card uai-account-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Saldo total
                </div>

                <div class="uai-account-total <?= $saldoTotal < 0
                    ? 'text-danger'
                    : ''
                    ?>">

                    <?= Money::format(
                        $saldoTotal
                    ) ?>

                </div>

                <div class="uai-summary-caption">

                    Soma das contas ativas

                </div>

            </div>

        </div>

    </div>


    <div class="col-md-6 col-xl-4">

        <div class="card uai-account-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Contas ativas
                </div>

                <div class="uai-account-total">

                    <?= count($contas) ?>

                </div>

                <div class="uai-summary-caption">

                    Contas acompanhadas pelo UaiMoney

                </div>

            </div>

        </div>

    </div>


</div>


<!--
|--------------------------------------------------------------------------
| Contas
|--------------------------------------------------------------------------
-->

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <h4 class="mb-1">
            Suas contas
        </h4>

        <div class="text-uai-muted small">

            <?= count($contas) ?>

            <?= count($contas) === 1
                ? 'conta cadastrada'
                : 'contas cadastradas'
                ?>

        </div>

    </div>

</div>


<?php if ($contas === []): ?>


    <div class="card">

        <div class="card-body text-center py-5">

            <h5 class="mb-2">
                Nenhuma conta cadastrada
            </h5>

            <p class="text-uai-muted mb-3">
                Cadastre uma conta para começar
                a acompanhar seus saldos.
            </p>

            <button type="button" class="btn btn-uai-primary" data-bs-toggle="modal" data-bs-target="#novaContaModal">
                + Nova conta
            </button>

        </div>

    </div>


<?php else: ?>


    <div class="row g-3">


        <?php foreach ($contas as $conta): ?>


            <?php

            $saldoAtual =
                (int) $conta[
                    'saldo_atual_centavos'
                ];


            $saldoInicial =
                (int) $conta[
                    'saldo_inicial_centavos'
                ];


            $tipoLabel =
                $tiposConta[
                    $conta['tipo']
                ]
                ?? ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        $conta['tipo']
                    )
                );

            ?>


            <div class="col-md-6 col-xl-4">

                <div class="card uai-account-card h-100">

                    <div class="card-body">


                        <!-- Topo -->

                        <div class="d-flex justify-content-between align-items-start gap-3">

                            <div class="min-w-0">

                                <div class="d-flex align-items-center gap-2">

                                    <div class="uai-account-icon">

                                        <?= strtoupper(
                                            substr(
                                                $conta['nome'],
                                                0,
                                                1
                                            )
                                        ) ?>

                                    </div>


                                    <div>

                                        <h5 class="mb-0">

                                            <?= htmlspecialchars(
                                                $conta['nome'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </h5>


                                        <div class="text-uai-muted small mt-1">

                                            <?= htmlspecialchars(
                                                $tipoLabel,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>


                                            <?php if (
                                                !empty(
                                                $conta['instituicao']
                                            )
                                            ): ?>

                                                <span class="mx-1">
                                                    •
                                                </span>

                                                <?= htmlspecialchars(
                                                    $conta['instituicao'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- Ações -->

                            <div class="dropdown">

                                <button type="button" class="btn btn-sm btn-outline-secondary uai-action-button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    •••
                                </button>


                                <ul class="dropdown-menu dropdown-menu-end">

                                    <li>

                                        <a class="dropdown-item" href="<?= htmlspecialchars(
                                            $basePath
                                            . '/contas/'
                                            . (int) $conta['id']
                                            . '/editar',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">
                                            Editar
                                        </a>

                                    </li>


                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>

                                    <li>

                                        <form method="POST" action="<?= htmlspecialchars(
                                            $basePath
                                            . '/contas/'
                                            . (int) $conta['id']
                                            . '/desativar',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>" onsubmit="return confirm('Deseja realmente desativar esta conta?');">

                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                $csrfToken,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                            <button type="submit" class="dropdown-item text-danger">
                                                Desativar
                                            </button>

                                        </form>

                                    </li>

                                </ul>

                            </div>

                        </div>


                        <!-- Saldo -->

                        <div class="uai-account-balance">

                            <div class="uai-summary-label">
                                Saldo atual
                            </div>

                            <div class="uai-account-balance-value <?= $saldoAtual < 0
                                ? 'text-danger'
                                : ''
                                ?>">

                                <?= Money::format(
                                    $saldoAtual
                                ) ?>

                            </div>

                        </div>


                        <!-- Informações -->

                        <div class="uai-account-details">

                            <div>

                                <span class="text-uai-muted">
                                    Saldo inicial
                                </span>

                                <strong>

                                    <?= Money::format(
                                        $saldoInicial
                                    ) ?>

                                </strong>

                            </div>


                            <div>

                                <span class="text-uai-muted">
                                    Desde
                                </span>

                                <strong>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $conta[
                                                'saldo_inicial_em'
                                            ]
                                        )
                                    ) ?>

                                </strong>

                            </div>

                        </div>

                        <div class="mt-4">

                            <a href="<?= htmlspecialchars(
                                $basePath
                                . '/contas/'
                                . (int) $conta['id'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>" class="text-decoration-none fw-semibold">
                                Ver detalhes →
                            </a>

                        </div>


                    </div>

                </div>

            </div>


        <?php endforeach; ?>


    </div>


<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| Modal - Nova conta
|--------------------------------------------------------------------------
-->

<div class="modal fade" id="novaContaModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Nova conta
                    </h5>

                    <div class="text-uai-muted small">
                        Adicione uma conta ao seu controle financeiro.
                    </div>

                </div>


                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>

            </div>


            <form method="POST" action="<?= htmlspecialchars(
                $basePath . '/contas',
                ENT_QUOTES,
                'UTF-8'
            ) ?>">


                <div class="modal-body">


                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">


                    <div class="row g-3">


                        <!-- Nome -->

                        <div class="col-12">

                            <label for="novaContaNome" class="form-label">
                                Nome
                            </label>

                            <input type="text" class="form-control" id="novaContaNome" name="nome"
                                placeholder="Ex.: Nubank" required>

                        </div>


                        <!-- Tipo -->

                        <div class="col-md-6">

                            <label for="novaContaTipo" class="form-label">
                                Tipo
                            </label>

                            <select class="form-select" id="novaContaTipo" name="tipo" required>

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


                        <!-- Instituição -->

                        <div class="col-md-6">

                            <label for="novaContaInstituicao" class="form-label">
                                Instituição
                            </label>

                            <input type="text" class="form-control" id="novaContaInstituicao" name="instituicao"
                                placeholder="Ex.: Nubank">

                        </div>


                        <!-- Saldo inicial -->

                        <div class="col-md-6">

                            <label for="novoSaldoInicial" class="form-label">
                                Saldo inicial
                            </label>

                            <input type="number" class="form-control" id="novoSaldoInicial" name="saldo_inicial"
                                step="0.01" value="0.00" required>

                        </div>


                        <!-- Data -->

                        <div class="col-md-6">

                            <label for="novoSaldoInicialEm" class="form-label">
                                Data do saldo
                            </label>

                            <input type="date" class="form-control" id="novoSaldoInicialEm" name="saldo_inicial_em"
                                value="<?= date('Y-m-d') ?>" required>

                        </div>


                        <div class="col-12">

                            <div class="form-text">

                                O saldo inicial representa
                                o valor existente na conta
                                na data informada.

                            </div>

                        </div>


                    </div>


                </div>


                <div class="modal-footer">

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-uai-primary">
                        Criar conta
                    </button>

                </div>


            </form>


        </div>

    </div>

</div>
<?php if(!empty($inativas)): ?><details class="card mt-4"><summary class="card-body">Contas desativadas</summary><div class="card-body pt-0"><?php foreach($inativas as $inativa): ?><div class="list-row"><span><?= htmlspecialchars($inativa['nome'],ENT_QUOTES,'UTF-8') ?></span><form method="post" action="<?= htmlspecialchars($basePath.'/contas/'.(int)$inativa['id'].'/reativar',ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken,ENT_QUOTES,'UTF-8') ?>"><button class="btn btn-sm btn-outline-secondary">Reativar</button></form></div><?php endforeach; ?></div></details><?php endif; ?>
