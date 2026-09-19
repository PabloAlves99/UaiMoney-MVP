<?php

declare(strict_types=1);

use App\Core\Money;


/*
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
|
| Por enquanto calculamos o resumo usando as movimentações já carregadas.
| Quando implementarmos filtros, vamos levar essa agregação para a camada
| de dados para que o resumo acompanhe exatamente o período filtrado.
|
*/

$totalReceitas = 0;
$totalDespesas = 0;
$totalPendentes = 0;


foreach ($transacoes as $item) {

    if ($item['status'] === 'pendente') {
        $totalPendentes++;
    }


    if ($item['status'] !== 'efetivada') {
        continue;
    }


    if ($item['tipo'] === 'receita') {
        $totalReceitas += (int) $item['valor_centavos'];
    }


    if ($item['tipo'] === 'despesa') {
        $totalDespesas += (int) $item['valor_centavos'];
    }
}


$resultado = $totalReceitas - $totalDespesas;


/*
|--------------------------------------------------------------------------
| Meios de pagamento
|--------------------------------------------------------------------------
*/

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


<!--
|--------------------------------------------------------------------------
| Cabeçalho
|--------------------------------------------------------------------------
-->

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

    <div>

        <h1 class="h3 mb-1">
            Movimentações
        </h1>

        <p class="text-uai-muted mb-0">
            Acompanhe suas receitas, despesas e pendências.
        </p>

    </div>


    <button type="button" class="btn btn-uai-primary" data-bs-toggle="modal" data-bs-target="#novaMovimentacaoModal">
        + Nova movimentação
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
| Resumo financeiro
|--------------------------------------------------------------------------
-->

<div class="row g-3 mb-4">


    <div class="col-6 col-xl-3">

        <div class="card uai-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Receitas
                </div>

                <div class="uai-summary-value text-success">

                    <?= Money::format(
                        $totalReceitas
                    ) ?>

                </div>

                <div class="uai-summary-caption">
                    Movimentações efetivadas
                </div>

            </div>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="card uai-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Despesas
                </div>

                <div class="uai-summary-value text-danger">

                    <?= Money::format(
                        $totalDespesas
                    ) ?>

                </div>

                <div class="uai-summary-caption">
                    Movimentações efetivadas
                </div>

            </div>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="card uai-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Resultado
                </div>

                <div class="uai-summary-value <?= $resultado < 0
                    ? 'text-danger'
                    : 'text-success'
                    ?>">

                    <?= Money::format(
                        $resultado
                    ) ?>

                </div>

                <div class="uai-summary-caption">
                    Receitas menos despesas
                </div>

            </div>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="card uai-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Pendentes
                </div>

                <div class="uai-summary-value">

                    <?= $totalPendentes ?>

                </div>

                <div class="uai-summary-caption">
                    Aguardando efetivação
                </div>

            </div>

        </div>

    </div>


</div>


<!--
|--------------------------------------------------------------------------
| Histórico
|--------------------------------------------------------------------------
-->

<?php

$temFiltros =
    $filters['data_inicio'] !== ''
    ||
    $filters['data_fim'] !== ''
    ||
    $filters['status'] !== ''
    ||
    $filters['tipo'] !== ''
    ||
    $filters['conta_id'] !== null
    ||
    $filters['grupo_id'] !== null;

?>

<div class="card uai-history-card">


    <div
        class="card-header bg-transparent d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">

        <div>

            <h5 class="mb-1">
                Histórico
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


        <!--
            A barra de filtros entrará exatamente
            nesta região no próximo passo.
        -->

    </div>



    <div class="uai-filter-bar">

        <form method="GET" action="<?= htmlspecialchars(
            $basePath
            . '/movimentacoes',
            ENT_QUOTES,
            'UTF-8'
        ) ?>">

            <div class="row g-2 align-items-end">


                <!-- Data inicial -->

                <div class="col-6 col-lg">

                    <label for="filterDataInicio" class="form-label uai-filter-label">
                        De
                    </label>

                    <input type="date" class="form-control form-control-sm" id="filterDataInicio" name="data_inicio"
                        value="<?= htmlspecialchars(
                            $filters['data_inicio'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">

                </div>


                <!-- Data final -->

                <div class="col-6 col-lg">

                    <label for="filterDataFim" class="form-label uai-filter-label">
                        Até
                    </label>

                    <input type="date" class="form-control form-control-sm" id="filterDataFim" name="data_fim" value="<?= htmlspecialchars(
                        $filters['data_fim'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

                </div>


                <!-- Tipo -->

                <div class="col-6 col-lg">

                    <label for="filterTipo" class="form-label uai-filter-label">
                        Tipo
                    </label>

                    <select class="form-select form-select-sm" id="filterTipo" name="tipo">

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


                <!-- Status -->

                <div class="col-6 col-lg">

                    <label for="filterStatus" class="form-label uai-filter-label">
                        Status
                    </label>

                    <select class="form-select form-select-sm" id="filterStatus" name="status">

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


                <!-- Conta -->

                <div class="col-6 col-lg">

                    <label for="filterConta" class="form-label uai-filter-label">
                        Conta
                    </label>

                    <select class="form-select form-select-sm" id="filterConta" name="conta_id">

                        <option value="">
                            Todas
                        </option>


                        <?php foreach (
                            $contas as $conta
                        ): ?>

                            <option value="<?= (int) 
                                $conta['id']
                                ?>" <?= (
                                $filters['conta_id']
                                !== null
                                &&
                                (int) 
                                $filters['conta_id']
                                ===
                                (int) 
                                $conta['id']
                            )
                                ? 'selected'
                                : ''
                                ?>>

                                <?= htmlspecialchars(
                                    $conta['nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Categoria -->

                <div class="col-6 col-lg">

                    <label for="filterGrupo" class="form-label uai-filter-label">
                        Categoria
                    </label>

                    <select class="form-select form-select-sm" id="filterGrupo" name="grupo_id">

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
                                    (
                                        $grupo['tipo']
                                        === 'receita'
                                        ? 'Receita - '
                                        : 'Despesa - '
                                    )
                                    . $grupo['nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Botões -->

                <div class="col-12 col-lg-auto">

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-sm btn-uai-primary">
                            Filtrar
                        </button>


                        <?php if ($temFiltros): ?>

                            <a href="<?= htmlspecialchars(
                                $basePath
                                . '/movimentacoes',
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

    <?php if ($transacoes === []): ?>


        <div class="card-body text-center py-5">

            <h5 class="mb-2">
                Nenhuma movimentação cadastrada
            </h5>

            <p class="text-uai-muted mb-3">
                Cadastre sua primeira receita ou despesa.
            </p>

            <button type="button" class="btn btn-uai-primary" data-bs-toggle="modal"
                data-bs-target="#novaMovimentacaoModal">
                + Nova movimentação
            </button>

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

                        'cancelada'
                        => 'uai-status-muted',

                        default
                        => 'uai-status-muted'
                    };


                $meioPagamento =
                    $meiosPagamento[
                        $transacao['meio_pagamento']
                        ?? ''
                    ] ?? null;

                ?>


                <div class="uai-transaction-item">


                    <!-- Data -->

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


                    <!-- Descrição -->

                    <div class="uai-transaction-main">

                        <div class="d-flex align-items-center gap-2 mb-1">

                            <span class="uai-type-indicator <?= $tipo === 'receita'
                                ? 'uai-type-income'
                                : 'uai-type-expense'
                                ?>"></span>


                            <span class="fw-semibold">

                                <?= htmlspecialchars(
                                    $transacao['descricao'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        </div>


                        <div class="uai-transaction-meta">

                            <span>

                                <?= htmlspecialchars(
                                    $transacao['grupo_nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                                &rsaquo;

                                <?= htmlspecialchars(
                                    $transacao['subgrupo_nome'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>


                            <?php if (
                                $transacao['conta_nome']
                                !== null
                            ): ?>

                                <span class="uai-meta-separator">
                                    •
                                </span>

                                <span>

                                    <?= htmlspecialchars(
                                        $transacao['conta_nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>


                            <?php if (
                                $meioPagamento !== null
                            ): ?>

                                <span class="uai-meta-separator">
                                    •
                                </span>

                                <span>

                                    <?= htmlspecialchars(
                                        $meioPagamento,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            <?php endif; ?>

                        </div>


                        <?php if (
                            !empty(
                            $transacao['observacao']
                        )
                        ): ?>

                            <div class="text-uai-muted small mt-1">

                                <?= htmlspecialchars(
                                    $transacao['observacao'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- Status -->

                    <div class="uai-transaction-status">

                        <span class="uai-status <?= $statusClass ?>">

                            <?= htmlspecialchars(
                                $statusLabel,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    </div>


                    <!-- Valor -->

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


                    <!-- Ações -->

                    <div class="uai-transaction-actions">


                        <?php if (
                            $status !== 'cancelada'
                        ): ?>


                            <div class="dropdown">

                                <button class="btn btn-sm btn-outline-secondary uai-action-button" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    •••
                                </button>


                                <ul class="dropdown-menu dropdown-menu-end">


                                    <?php if (
                                        $status === 'pendente'
                                    ): ?>


                                        <li>

                                            <a class="dropdown-item" href="<?= htmlspecialchars(
                                                $basePath
                                                . '/movimentacoes/'
                                                . (int) 
                                                $transacao['id']
                                                . '/editar',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">
                                                Editar
                                            </a>

                                        </li>


                                        <li>

                                            <button type="button" class="dropdown-item" data-bs-toggle="modal"
                                                data-bs-target="#efetivarMovimentacaoModal" data-transaction-id="<?= (int) 
                                                    $transacao['id']
                                                    ?>" data-transaction-description="<?= htmlspecialchars(
                                                    $transacao['descricao'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>" data-account-id="<?= $transacao['conta_id']
                                                     !== null
                                                     ? (int) 
                                                     $transacao['conta_id']
                                                     : ''
                                                     ?>">
                                                Efetivar
                                            </button>

                                        </li>


                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>


                                    <?php endif; ?>


                                    <li>

                                        <form method="POST" action="<?= htmlspecialchars(
                                            $basePath
                                            . '/movimentacoes/'
                                            . (int) 
                                            $transacao['id']
                                            . '/cancelar',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                            onsubmit="return confirm('Deseja realmente cancelar esta movimentação?');">

                                            <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                $csrfToken,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                            <button type="submit" class="dropdown-item text-danger">
                                                Cancelar
                                            </button>

                                        </form>

                                    </li>


                                </ul>

                            </div>


                        <?php else: ?>

                            <span class="text-uai-muted">
                                —
                            </span>

                        <?php endif; ?>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


<!--
|--------------------------------------------------------------------------
| Modal - Nova movimentação
|--------------------------------------------------------------------------
-->

<div class="modal fade" id="novaMovimentacaoModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Nova movimentação
                    </h5>

                    <div class="text-uai-muted small">
                        Cadastre uma receita ou despesa.
                    </div>

                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>

            </div>


            <form method="POST" action="<?= htmlspecialchars(
                $basePath
                . '/movimentacoes',
                ENT_QUOTES,
                'UTF-8'
            ) ?>" id="novaMovimentacaoForm">

                <div class="modal-body">

                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">


                    <?php if ($grupos === []): ?>

                        <div class="alert alert-warning mb-0">

                            Cadastre uma categoria com
                            subcategoria antes de criar
                            movimentações.

                        </div>


                    <?php else: ?>


                        <div class="row g-3">


                            <!-- Descrição -->

                            <div class="col-md-8">

                                <label for="descricao" class="form-label">
                                    Descrição
                                </label>

                                <input type="text" class="form-control" id="descricao" name="descricao"
                                    placeholder="Ex.: Supermercado" autofocus required>

                            </div>


                            <!-- Valor -->

                            <div class="col-md-4">

                                <label for="valor" class="form-label">
                                    Valor
                                </label>

                                <input type="number" class="form-control" id="valor" name="valor" min="0.01" step="0.01"
                                    placeholder="0,00" required>

                            </div>


                            <!-- Categoria -->

                            <div class="col-md-7">

                                <label for="subgrupoId" class="form-label">
                                    Categoria
                                </label>

                                <select class="form-select" id="subgrupoId" name="subgrupo_id" required>

                                    <option value="">
                                        Selecione
                                    </option>


                                    <?php foreach (
                                        ['receita', 'despesa']
                                        as $tipo
                                    ): ?>


                                        <optgroup label="<?= $tipo
                                            === 'receita'
                                            ? 'Receitas'
                                            : 'Despesas'
                                            ?>">


                                            <?php foreach (
                                                $grupos
                                                as $grupo
                                            ): ?>


                                                <?php if (
                                                    $grupo['tipo']
                                                    !== $tipo
                                                ) {
                                                    continue;
                                                } ?>


                                                <?php foreach (
                                                    $grupo[
                                                        'subgrupos'
                                                    ]
                                                    as $subgrupo
                                                ): ?>

                                                    <option value="<?= (int) 
                                                        $subgrupo['id']
                                                        ?>">

                                                        <?= htmlspecialchars(
                                                            $grupo['nome']
                                                            . ' - '
                                                            . $subgrupo[
                                                                'nome'
                                                            ],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </option>

                                                <?php endforeach; ?>


                                            <?php endforeach; ?>


                                        </optgroup>


                                    <?php endforeach; ?>


                                </select>

                            </div>


                            <!-- Status -->

                            <div class="col-md-5">

                                <label for="status" class="form-label">
                                    Status
                                </label>

                                <select class="form-select" id="status" name="status" required>

                                    <option value="pendente">
                                        Pendente
                                    </option>

                                    <option value="efetivada">
                                        Efetivada
                                    </option>

                                </select>

                            </div>


                            <!-- Conta -->

                            <div class="col-md-7">

                                <label for="contaId" class="form-label">
                                    Conta
                                </label>

                                <select class="form-select" id="contaId" name="conta_id">

                                    <option value="">
                                        Definir depois
                                    </option>


                                    <?php foreach (
                                        $contas as $conta
                                    ): ?>

                                        <option value="<?= (int) 
                                            $conta['id']
                                            ?>">

                                            <?= htmlspecialchars(
                                                $conta['nome'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </option>

                                    <?php endforeach; ?>


                                </select>

                                <div class="form-text" id="contaHelp">
                                    Obrigatória para movimentações efetivadas.
                                </div>

                            </div>


                            <!-- Vencimento -->

                            <div class="col-md-5">

                                <label for="dataVencimento" class="form-label">
                                    Vencimento
                                </label>

                                <input type="date" class="form-control" id="dataVencimento" name="data_vencimento" value="<?= date(
                                    'Y-m-d'
                                ) ?>" required>

                            </div>


                            <!-- Mais opções -->

                            <div class="col-12">

                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse"
                                    data-bs-target="#maisOpcoesMovimentacao" aria-expanded="false">
                                    + Mais opções
                                </button>

                            </div>


                            <div class="collapse" id="maisOpcoesMovimentacao">

                                <div class="row g-3 pt-1">


                                    <div class="col-md-4">

                                        <label for="dataCompetencia" class="form-label">
                                            Competência
                                        </label>

                                        <input type="date" class="form-control" id="dataCompetencia" name="data_competencia"
                                            value="<?= date(
                                                'Y-m-d'
                                            ) ?>" required>

                                    </div>


                                    <div class="col-md-4">

                                        <label for="dataEfetivacao" class="form-label">
                                            Efetivação
                                        </label>

                                        <input type="date" class="form-control" id="dataEfetivacao" name="data_efetivacao"
                                            disabled>

                                    </div>


                                    <div class="col-md-4">

                                        <label for="meioPagamento" class="form-label">
                                            Meio de pagamento
                                        </label>

                                        <select class="form-select" id="meioPagamento" name="meio_pagamento">

                                            <option value="">
                                                Não informado
                                            </option>

                                            <?php foreach (
                                                $meiosPagamento
                                                as $valor => $label
                                            ): ?>

                                                <option value="<?= htmlspecialchars(
                                                    $valor,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">

                                                    <?= htmlspecialchars(
                                                        $label,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>


                                    <div class="col-12">

                                        <label for="observacao" class="form-label">
                                            Observação
                                        </label>

                                        <textarea class="form-control" id="observacao" name="observacao" rows="2"
                                            placeholder="Informação adicional..."></textarea>

                                    </div>


                                </div>

                            </div>


                        </div>


                    <?php endif; ?>


                </div>


                <?php if ($grupos !== []): ?>

                    <div class="modal-footer">

                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button type="submit" class="btn btn-uai-primary">
                            Salvar movimentação
                        </button>

                    </div>

                <?php endif; ?>


            </form>


        </div>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Modal - Efetivar movimentação
|--------------------------------------------------------------------------
-->

<div class="modal fade" id="efetivarMovimentacaoModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Efetivar movimentação
                    </h5>

                    <div class="text-uai-muted small" id="efetivarDescricao"></div>

                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

            </div>


            <form method="POST" id="efetivarMovimentacaoForm">

                <div class="modal-body">

                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">


                    <div class="mb-3">

                        <label for="efetivarContaId" class="form-label">
                            Conta
                        </label>

                        <select class="form-select" id="efetivarContaId" name="conta_id" required>

                            <option value="">
                                Selecione
                            </option>


                            <?php foreach (
                                $contas as $conta
                            ): ?>

                                <option value="<?= (int) 
                                    $conta['id']
                                    ?>">

                                    <?= htmlspecialchars(
                                        $conta['nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </option>

                            <?php endforeach; ?>


                        </select>

                    </div>


                    <div>

                        <label for="efetivarData" class="form-label">
                            Data de efetivação
                        </label>

                        <input type="date" class="form-control" id="efetivarData" name="data_efetivacao"
                            value="<?= date('Y-m-d') ?>" required>

                    </div>

                </div>


                <div class="modal-footer">

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-uai-primary">
                        Efetivar
                    </button>

                </div>

            </form>


        </div>

    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        const basePath = <?= json_encode(
            $basePath,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        ) ?>;


        /*
        |--------------------------------------------------------------------------
        | Nova movimentação
        |--------------------------------------------------------------------------
        */

        const status =
            document.getElementById('status');

        const conta =
            document.getElementById('contaId');

        const dataEfetivacao =
            document.getElementById('dataEfetivacao');


        function atualizarStatusMovimentacao() {

            if (
                !status ||
                !conta ||
                !dataEfetivacao
            ) {
                return;
            }


            const efetivada =
                status.value === 'efetivada';


            conta.required =
                efetivada;


            dataEfetivacao.disabled =
                !efetivada;


            dataEfetivacao.required =
                efetivada;


            if (efetivada) {

                if (!dataEfetivacao.value) {

                    dataEfetivacao.value =
                        new Date()
                            .toISOString()
                            .slice(0, 10);

                }

            } else {

                dataEfetivacao.value = '';

            }

        }


        if (status) {

            status.addEventListener(
                'change',
                atualizarStatusMovimentacao
            );

            atualizarStatusMovimentacao();

        }


        /*
        |--------------------------------------------------------------------------
        | Efetivar movimentação
        |--------------------------------------------------------------------------
        */

        const efetivarModal =
            document.getElementById(
                'efetivarMovimentacaoModal'
            );


        if (efetivarModal) {

            efetivarModal.addEventListener(
                'show.bs.modal',
                function (event) {

                    const button =
                        event.relatedTarget;


                    if (!button) {
                        return;
                    }


                    const id =
                        button.getAttribute(
                            'data-transaction-id'
                        );


                    const descricao =
                        button.getAttribute(
                            'data-transaction-description'
                        );


                    const contaId =
                        button.getAttribute(
                            'data-account-id'
                        );


                    const form =
                        document.getElementById(
                            'efetivarMovimentacaoForm'
                        );


                    const descricaoElement =
                        document.getElementById(
                            'efetivarDescricao'
                        );


                    const contaElement =
                        document.getElementById(
                            'efetivarContaId'
                        );


                    form.action =
                        basePath
                        + '/movimentacoes/'
                        + id
                        + '/efetivar';


                    descricaoElement.textContent =
                        descricao;


                    contaElement.value =
                        contaId || '';

                }
            );

        }

    });
</script>