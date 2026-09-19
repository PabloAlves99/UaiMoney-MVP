<?php

declare(strict_types=1);

use App\Core\Money;

?>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="h3 mb-1">
            Movimentações
        </h1>

        <p class="text-uai-muted mb-0">
            Acompanhe suas receitas e despesas.
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


<!--
|--------------------------------------------------------------------------
| Nova movimentação
|--------------------------------------------------------------------------
-->

<div class="card mb-4">

    <div class="card-body">

        <h5 class="card-title mb-4">
            Nova movimentação
        </h5>


        <?php if ($grupos === []): ?>

            <div class="alert alert-warning mb-0">

                Você precisa cadastrar pelo menos
                uma categoria com subcategoria antes
                de criar uma movimentação.

            </div>


        <?php elseif ($contas === []): ?>

            <div class="alert alert-warning mb-0">

                Você precisa cadastrar pelo menos
                uma conta antes de criar uma movimentação.

            </div>


        <?php else: ?>

            <form method="POST" action="<?= htmlspecialchars(
                $basePath . '/movimentacoes',
                ENT_QUOTES,
                'UTF-8'
            ) ?>">

                <input type="hidden" name="_token" value="<?= htmlspecialchars(
                    $csrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">


                <div class="row g-3">


                    <!-- Descrição -->

                    <div class="col-md-6">

                        <label for="descricao" class="form-label">
                            Descrição
                        </label>

                        <input type="text" class="form-control" id="descricao" name="descricao"
                            placeholder="Ex.: Conta de energia" required>

                    </div>


                    <!-- Valor -->

                    <div class="col-md-3">

                        <label for="valor" class="form-label">
                            Valor
                        </label>

                        <input type="number" class="form-control" id="valor" name="valor" min="0.01" step="0.01"
                            placeholder="0,00" required>

                    </div>


                    <!-- Status -->

                    <div class="col-md-3">

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


                    <!-- Categoria -->

                    <div class="col-md-6">

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


                                <optgroup label="<?= $tipo === 'receita'
                                    ? 'Receitas'
                                    : 'Despesas'
                                    ?>">


                                    <?php foreach (
                                        $grupos as $grupo
                                    ): ?>


                                        <?php if (
                                            $grupo['tipo'] !== $tipo
                                        ) {
                                            continue;
                                        } ?>


                                        <?php foreach (
                                            $grupo['subgrupos']
                                            as $subgrupo
                                        ): ?>

                                            <option value="<?= (int) 
                                                $subgrupo['id']
                                                ?>">

                                                <?= htmlspecialchars(
                                                    $grupo['nome']
                                                    . ' - '
                                                    . $subgrupo['nome'],
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


                    <!-- Conta -->

                    <div class="col-md-6">

                        <label for="contaId" class="form-label">
                            Conta
                        </label>

                        <select class="form-select" id="contaId" name="conta_id">

                            <option value="">
                                Nenhuma / definir depois
                            </option>


                            <?php foreach ($contas as $conta): ?>

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

                        <div class="form-text">

                            Movimentações efetivadas
                            precisam ter uma conta.

                        </div>

                    </div>


                    <!-- Competência -->

                    <div class="col-md-4">

                        <label for="dataCompetencia" class="form-label">
                            Competência
                        </label>

                        <input type="date" class="form-control" id="dataCompetencia" name="data_competencia"
                            value="<?= date('Y-m-d') ?>" required>

                    </div>


                    <!-- Vencimento -->

                    <div class="col-md-4">

                        <label for="dataVencimento" class="form-label">
                            Vencimento
                        </label>

                        <input type="date" class="form-control" id="dataVencimento" name="data_vencimento"
                            value="<?= date('Y-m-d') ?>" required>

                    </div>


                    <!-- Efetivação -->

                    <div class="col-md-4">

                        <label for="dataEfetivacao" class="form-label">
                            Efetivação
                        </label>

                        <input type="date" class="form-control" id="dataEfetivacao" name="data_efetivacao">

                        <div class="form-text">

                            Obrigatória quando o status
                            for Efetivada.

                        </div>

                    </div>


                    <!-- Meio de pagamento -->

                    <div class="col-md-4">

                        <label for="meioPagamento" class="form-label">
                            Meio de pagamento
                        </label>

                        <select class="form-select" id="meioPagamento" name="meio_pagamento">

                            <option value="">
                                Não informado
                            </option>

                            <option value="pix">
                                Pix
                            </option>

                            <option value="dinheiro">
                                Dinheiro
                            </option>

                            <option value="debito">
                                Débito
                            </option>

                            <option value="credito">
                                Crédito
                            </option>

                            <option value="boleto">
                                Boleto
                            </option>

                            <option value="transferencia">
                                Transferência
                            </option>

                            <option value="outro">
                                Outro
                            </option>

                        </select>

                    </div>


                    <!-- Observação -->

                    <div class="col-md-8">

                        <label for="observacao" class="form-label">
                            Observação
                        </label>

                        <input type="text" class="form-control" id="observacao" name="observacao"
                            placeholder="Informação adicional...">

                    </div>


                    <!-- Botão -->

                    <div class="col-12">

                        <button type="submit" class="btn btn-uai-primary">
                            Salvar movimentação
                        </button>

                    </div>

                </div>

            </form>

        <?php endif; ?>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
-->

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>

        <h4 class="mb-0">
            Histórico
        </h4>

    </div>

    <?php if ($transacoes !== []): ?>

        <span class="text-uai-muted small">

            <?= count($transacoes) ?>

            movimentação(ões)

        </span>

    <?php endif; ?>

</div>


<?php if ($transacoes === []): ?>

    <div class="card">

        <div class="card-body text-center py-5">

            <h5 class="mb-2">
                Nenhuma movimentação cadastrada
            </h5>

            <p class="text-uai-muted mb-0">
                Suas receitas e despesas aparecerão aqui.
            </p>

        </div>

    </div>


<?php else: ?>

    <div class="card">

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead>

                    <tr>

                        <th>
                            Data
                        </th>

                        <th>
                            Descrição
                        </th>

                        <th>
                            Categoria
                        </th>

                        <th>
                            Conta
                        </th>

                        <th>
                            Status
                        </th>

                        <th class="text-end">
                            Valor
                        </th>

                        <th class="text-end">
                            Ações
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach (
                        $transacoes as $transacao
                    ): ?>


                        <?php

                        $tipo =
                            $transacao['tipo'];


                        /*
                         * Para a listagem usamos
                         * uma data de referência:
                         *
                         * efetivação
                         * vencimento
                         * competência
                         */

                        $dataReferencia =
                            $transacao['data_efetivacao']
                            ?? $transacao['data_vencimento']
                            ?? $transacao['data_competencia'];


                        /*
                         * Classe visual do status.
                         */

                        $status =
                            $transacao['status'];


                        $statusClass =
                            match ($status) {

                                'efetivada'
                                => 'text-bg-success',

                                'pendente'
                                => 'text-bg-warning',

                                'cancelada'
                                => 'text-bg-secondary',

                                default
                                => 'text-bg-secondary'
                            };


                        /*
                         * Nome amigável do
                         * meio de pagamento.
                         */

                        $meioPagamento =
                            match ($transacao['meio_pagamento'] ?? null) {

                                'pix'
                                => 'Pix',

                                'dinheiro'
                                => 'Dinheiro',

                                'debito'
                                => 'Débito',

                                'credito'
                                => 'Crédito',

                                'boleto'
                                => 'Boleto',

                                'transferencia'
                                => 'Transferência',

                                'outro'
                                => 'Outro',

                                default
                                => null
                            };

                        ?>


                        <tr>


                            <!-- Data -->

                            <td>

                                <?= htmlspecialchars(
                                    date(
                                        'd/m/Y',
                                        strtotime(
                                            $dataReferencia
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <!-- Descrição -->

                            <td>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $transacao['descricao'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>


                                <?php if (
                                    $meioPagamento !== null
                                ): ?>

                                    <small class="text-uai-muted">

                                        <?= htmlspecialchars(
                                            $meioPagamento,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </small>

                                <?php endif; ?>


                                <?php if (
                                    !empty($transacao['observacao'])
                                ): ?>

                                    <div>

                                        <small class="text-uai-muted">

                                            <?= htmlspecialchars(
                                                $transacao['observacao'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </small>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- Categoria -->

                            <td>

                                <div>

                                    <?= htmlspecialchars(
                                        $transacao['grupo_nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                                <small class="text-uai-muted">

                                    <?= htmlspecialchars(
                                        $transacao['subgrupo_nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </small>

                            </td>


                            <!-- Conta -->

                            <td>

                                <?php if (
                                    $transacao['conta_nome'] !== null
                                ): ?>

                                    <?= htmlspecialchars(
                                        $transacao['conta_nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>


                                <?php else: ?>

                                    <span class="text-uai-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <!-- Status -->

                            <td>

                                <span class="badge <?= $statusClass ?>">

                                    <?= htmlspecialchars(
                                        ucfirst($status),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <!-- Valor -->

                            <td class="text-end fw-semibold">


                                <?php if (
                                    $tipo === 'receita'
                                ): ?>

                                    <span class="text-success">

                                        +
                                        <?= Money::format(
                                            (int) 
                                            $transacao['valor_centavos']
                                        ) ?>

                                    </span>


                                <?php else: ?>

                                    <span class="text-danger">

                                        -
                                        <?= Money::format(
                                            (int) 
                                            $transacao['valor_centavos']
                                        ) ?>

                                    </span>

                                <?php endif; ?>


                            </td>

                            <td class="text-end">

                                <?php if ($status === 'pendente'): ?>

                                    <form method="POST" action="<?= htmlspecialchars(
                                        $basePath
                                        . '/movimentacoes/'
                                        . (int) $transacao['id']
                                        . '/efetivar',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>" class="mb-2">

                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                            $csrfToken,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">


                                        <div class="d-flex gap-2 justify-content-end flex-wrap">

                                            <select name="conta_id" class="form-select form-select-sm" style="max-width: 170px;"
                                                required>

                                                <option value="">
                                                    Conta
                                                </option>


                                                <?php foreach (
                                                    $contas as $conta
                                                ): ?>

                                                    <option value="<?= (int) $conta['id'] ?>" <?= (
                                                           $transacao['conta_id'] !== null
                                                           &&
                                                           (int) $transacao['conta_id']
                                                           === (int) $conta['id']
                                                       )
                                                           ? 'selected'
                                                           : ''
                                                           ?>
                            >

                                                        <?= htmlspecialchars(
                                                            $conta['nome'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>


                                            <input type="date" name="data_efetivacao" class="form-control form-control-sm"
                                                value="<?= date('Y-m-d') ?>" style="max-width: 150px;" required>


                                            <button type="submit" class="btn btn-sm btn-success">
                                                Efetivar
                                            </button>

                                        </div>

                                    </form>

                                <?php endif; ?>


                                <?php if (
                                    $status === 'pendente'
                                    ||
                                    $status === 'efetivada'
                                ): ?>

                                    <form method="POST" action="<?= htmlspecialchars(
                                        $basePath
                                        . '/movimentacoes/'
                                        . (int) $transacao['id']
                                        . '/cancelar',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">

                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                            $csrfToken,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            Cancelar
                                        </button>

                                    </form>

                                <?php endif; ?>


                                <?php if (
                                    $status === 'cancelada'
                                ): ?>

                                    <span class="text-uai-muted small">
                                        Sem ações
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>


                    <?php endforeach; ?>


                </tbody>

            </table>

        </div>

    </div>

<?php endif; ?>