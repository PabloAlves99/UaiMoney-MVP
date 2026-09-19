<?php

declare(strict_types=1);

use App\Core\Money;


/*
|--------------------------------------------------------------------------
| Informações
|--------------------------------------------------------------------------
*/

$tiposConta = [
    'corrente' => 'Conta corrente',
    'poupanca' => 'Poupança',
    'dinheiro' => 'Dinheiro',
    'carteira_digital' => 'Carteira digital',
    'outro' => 'Outro'
];


$tipoLabel =
    $tiposConta[$conta['tipo']]
    ?? ucfirst(
        str_replace(
            '_',
            ' ',
            $conta['tipo']
        )
    );


/*
|--------------------------------------------------------------------------
| Resumo do extrato
|--------------------------------------------------------------------------
*/

$totalEntradas = 0;
$totalSaidas = 0;
$totalPendentes = 0;


foreach ($transacoes as $transacao) {

    if (
        $transacao['status']
        === 'pendente'
    ) {
        $totalPendentes++;
    }


    if (
        $transacao['status']
        !== 'efetivada'
    ) {
        continue;
    }


    if (
        $transacao['tipo']
        === 'receita'
    ) {
        $totalEntradas +=
            (int) $transacao[
                'valor_centavos'
            ];
    }


    if (
        $transacao['tipo']
        === 'despesa'
    ) {
        $totalSaidas +=
            (int) $transacao[
                'valor_centavos'
            ];
    }
}


$temFiltros =
    $filters['data_inicio'] !== ''
    ||
    $filters['data_fim'] !== ''
    ||
    $filters['status'] !== ''
    ||
    $filters['tipo'] !== ''
    ||
    $filters['grupo_id'] !== null;


$meiosPagamento = [
    'pix' => 'Pix',
    'dinheiro' => 'Dinheiro',
    'debito' => 'Débito',
    'credito' => 'Crédito',
    'boleto' => 'Boleto',
    'transferencia' => 'Transferência',
    'outro' => 'Outro'
];

?>


<!-- Voltar -->

<div class="mb-3">

    <a href="<?= htmlspecialchars(
        $basePath . '/contas',
        ENT_QUOTES,
        'UTF-8'
    ) ?>" class="text-decoration-none">
        ← Voltar para contas
    </a>

</div>


<!--
|--------------------------------------------------------------------------
| Cabeçalho
|--------------------------------------------------------------------------
-->

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">

    <div class="d-flex align-items-center gap-3">

        <div class="uai-account-icon uai-account-icon-large">

            <?= htmlspecialchars(
                strtoupper(
                    substr(
                        $conta['nome'],
                        0,
                        1
                    )
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>


        <div>

            <h1 class="h3 mb-1">

                <?= htmlspecialchars(
                    $conta['nome'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </h1>


            <div class="text-uai-muted">

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


    <div class="dropdown">

        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="dropdown">
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
                    Editar conta
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

                    <button class="dropdown-item text-danger" type="submit">
                        Desativar conta
                    </button>

                </form>

            </li>

        </ul>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
-->

<div class="row g-3 mb-4">


    <!-- Saldo -->

    <div class="col-6 col-xl-3">

        <div class="card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Saldo atual
                </div>

                <div class="uai-summary-value <?= (int) 
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

                <div class="uai-summary-caption">

                    Inicial:
                    <?= Money::format(
                        (int) 
                        $conta[
                            'saldo_inicial_centavos'
                        ]
                    ) ?>

                </div>

            </div>

        </div>

    </div>


    <!-- Entradas -->

    <div class="col-6 col-xl-3">

        <div class="card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Entradas
                </div>

                <div class="uai-summary-value text-success">

                    <?= Money::format(
                        $totalEntradas
                    ) ?>

                </div>

                <div class="uai-summary-caption">
                    No filtro atual
                </div>

            </div>

        </div>

    </div>


    <!-- Saídas -->

    <div class="col-6 col-xl-3">

        <div class="card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Saídas
                </div>

                <div class="uai-summary-value text-danger">

                    <?= Money::format(
                        $totalSaidas
                    ) ?>

                </div>

                <div class="uai-summary-caption">
                    No filtro atual
                </div>

            </div>

        </div>

    </div>


    <!-- Pendentes -->

    <div class="col-6 col-xl-3">

        <div class="card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Pendentes
                </div>

                <div class="uai-summary-value">

                    <?= $totalPendentes ?>

                </div>

                <div class="uai-summary-caption">
                    No filtro atual
                </div>

            </div>

        </div>

    </div>


</div>


<!--
|--------------------------------------------------------------------------
| Dados da conta
|--------------------------------------------------------------------------
-->

<div class="card mb-4">

    <div class="card-body">

        <div class="row g-3 align-items-center">

            <div class="col-md">

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


            <div class="col-md">

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


            <div class="col-md">

                <div class="uai-summary-label">
                    Instituição
                </div>

                <div class="fw-semibold">

                    <?= htmlspecialchars(
                        $conta['instituicao']
                        ?: 'Não informada',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            </div>


            <div class="col-md">

                <div class="uai-summary-label">
                    Tipo
                </div>

                <div class="fw-semibold">

                    <?= htmlspecialchars(
                        $tipoLabel,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </div>

            </div>

        </div>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Extrato
|--------------------------------------------------------------------------
-->

<div class="card uai-history-card">


    <div class="card-header bg-transparent">

        <h5 class="mb-1">
            Movimentações
        </h5>

        <div class="text-uai-muted small">

            <?= count($transacoes) ?>

            <?= count($transacoes) === 1
                ? 'movimentação encontrada'
                : 'movimentações encontradas'
                ?>


            <?php if ($temFiltros): ?>

                <span class="ms-1">
                    • filtros aplicados
                </span>

            <?php endif; ?>

        </div>

    </div>


    <!-- Filtros -->

    <div class="uai-filter-bar">

        <form method="GET" action="<?= htmlspecialchars(
            $basePath
            . '/contas/'
            . (int) $conta['id'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>">

            <div class="row g-2 align-items-end">


                <div class="col-6 col-lg">

                    <label class="form-label uai-filter-label">
                        De
                    </label>

                    <input type="date" class="form-control form-control-sm" name="data_inicio" value="<?= htmlspecialchars(
                        $filters['data_inicio'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

                </div>


                <div class="col-6 col-lg">

                    <label class="form-label uai-filter-label">
                        Até
                    </label>

                    <input type="date" class="form-control form-control-sm" name="data_fim" value="<?= htmlspecialchars(
                        $filters['data_fim'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

                </div>


                <div class="col-6 col-lg">

                    <label class="form-label uai-filter-label">
                        Tipo
                    </label>

                    <select name="tipo" class="form-select form-select-sm">

                        <option value="">
                            Todos
                        </option>

                        <option value="receita" <?= $filters['tipo']
                            === 'receita'
                            ? 'selected'
                            : ''
                            ?>>
                            Receita
                        </option>

                        <option value="despesa" <?= $filters['tipo']
                            === 'despesa'
                            ? 'selected'
                            : ''
                            ?>>
                            Despesa
                        </option>

                    </select>

                </div>


                <div class="col-6 col-lg">

                    <label class="form-label uai-filter-label">
                        Status
                    </label>

                    <select name="status" class="form-select form-select-sm">

                        <option value="">
                            Todos
                        </option>

                        <option value="pendente" <?= $filters['status']
                            === 'pendente'
                            ? 'selected'
                            : ''
                            ?>>
                            Pendente
                        </option>

                        <option value="efetivada" <?= $filters['status']
                            === 'efetivada'
                            ? 'selected'
                            : ''
                            ?>>
                            Efetivada
                        </option>

                        <option value="cancelada" <?= $filters['status']
                            === 'cancelada'
                            ? 'selected'
                            : ''
                            ?>>
                            Cancelada
                        </option>

                    </select>

                </div>


                <div class="col-6 col-lg">

                    <label class="form-label uai-filter-label">
                        Categoria
                    </label>

                    <select name="grupo_id" class="form-select form-select-sm">

                        <option value="">
                            Todas
                        </option>


                        <?php foreach (
                            $grupos as $grupo
                        ): ?>

                            <option value="<?= (int) 
                                $grupo['id']
                                ?>" <?= (
                                $filters['grupo_id']
                                !== null
                                &&
                                (int) 
                                $filters['grupo_id']
                                ===
                                (int) 
                                $grupo['id']
                            )
                                ? 'selected'
                                : ''
                                ?>>

                                <?= htmlspecialchars(
                                    $grupo['nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-12 col-lg-auto">

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-sm btn-uai-primary">
                            Filtrar
                        </button>


                        <?php if ($temFiltros): ?>

                            <a href="<?= htmlspecialchars(
                                $basePath
                                . '/contas/'
                                . (int) $conta['id'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>" class="btn btn-sm btn-outline-secondary">
                                Limpar
                            </a>

                        <?php endif; ?>

                    </div>

                </div>


            </div>

        </form>

    </div>


    <!-- Lista -->

    <?php if ($transacoes === []): ?>

        <div class="card-body text-center py-5">

            <h5 class="mb-2">
                Nenhuma movimentação encontrada
            </h5>

            <p class="text-uai-muted mb-0">

                <?php if ($temFiltros): ?>

                    Nenhuma movimentação corresponde
                    aos filtros selecionados.

                <?php else: ?>

                    Esta conta ainda não possui
                    movimentações.

                <?php endif; ?>

            </p>

        </div>


    <?php else: ?>


        <div class="uai-transaction-list">


            <?php foreach (
                $transacoes as $transacao
            ): ?>


                <?php

                $tipo =
                    $transacao['tipo'];

                $status =
                    $transacao['status'];


                $dataReferencia =
                    $transacao['data_efetivacao']
                    ?? $transacao['data_vencimento']
                    ?? $transacao['data_competencia'];


                $statusLabel =
                    match ($status) {

                        'efetivada'
                        => 'Efetivada',

                        'pendente'
                        => 'Pendente',

                        'cancelada'
                        => 'Cancelada',

                        default
                        => ucfirst($status)
                    };


                $statusClass =
                    match ($status) {

                        'efetivada'
                        => 'uai-status-success',

                        'pendente'
                        => 'uai-status-warning',

                        default
                        => 'uai-status-muted'
                    };


                $meio =
                    $meiosPagamento[
                        $transacao[
                            'meio_pagamento'
                        ]
                        ?? ''
                    ]
                    ?? null;

                ?>


                <div class="uai-transaction-item">


                    <div class="uai-transaction-date">

                        <div class="fw-semibold">

                            <?= date(
                                'd/m',
                                strtotime(
                                    $dataReferencia
                                )
                            ) ?>

                        </div>

                        <small class="text-uai-muted">

                            <?= date(
                                'Y',
                                strtotime(
                                    $dataReferencia
                                )
                            ) ?>

                        </small>

                    </div>


                    <div class="uai-transaction-main">

                        <div class="d-flex align-items-center gap-2 mb-1">

                            <span class="uai-type-indicator <?= $tipo === 'receita'
                                ? 'uai-type-income'
                                : 'uai-type-expense'
                                ?>"></span>


                            <span class="fw-semibold">

                                <?= htmlspecialchars(
                                    $transacao[
                                        'descricao'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        </div>


                        <div class="uai-transaction-meta">

                            <span>

                                <?= htmlspecialchars(
                                    $transacao[
                                        'grupo_nome'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                &rsaquo;

                                <?= htmlspecialchars(
                                    $transacao[
                                        'subgrupo_nome'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>


                            <?php if (
                                $meio !== null
                            ): ?>

                                <span>
                                    •
                                </span>

                                <span>

                                    <?= htmlspecialchars(
                                        $meio,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="uai-transaction-status">

                        <span class="uai-status <?= $statusClass ?>">

                            <?= htmlspecialchars(
                                $statusLabel,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    </div>


                    <div class="uai-transaction-value <?= $tipo === 'receita'
                        ? 'text-success'
                        : 'text-danger'
                        ?>">

                        <?= $tipo === 'receita'
                            ? '+'
                            : '-'
                            ?>

                        <?= Money::format(
                            (int) 
                            $transacao[
                                'valor_centavos'
                            ]
                        ) ?>

                    </div>


                    <div class="uai-transaction-actions">

                        <a href="<?= htmlspecialchars(
                            $basePath
                            . '/movimentacoes',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>" class="btn btn-sm btn-outline-secondary" title="Abrir movimentações">
                            →
                        </a>

                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>