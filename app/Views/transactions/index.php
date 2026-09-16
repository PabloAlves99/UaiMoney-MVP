<?php

use App\Core\Money;

?>


<div
    class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="h3 mb-1">
            Movimentações
        </h1>

        <p class="text-uai-muted mb-0">
            Acompanhe suas receitas e despesas.
        </p>

    </div>

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

            <table
                class="table align-middle mb-0">

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

                    </tr>

                </thead>


                <tbody>

                    <?php foreach (
                        $transacoes as $transacao
                    ): ?>

                        <?php

                        $tipo = $transacao['tipo'];

                        $dataReferencia =
                            $transacao['data_efetivacao']
                            ?? $transacao['data_vencimento']
                            ?? $transacao['data_competencia'];

                        ?>


                        <tr>

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


                            <td>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $transacao['descricao'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>


                                <?php if (
                                    !empty($transacao['meio_pagamento'])
                                ): ?>

                                    <small
                                        class="text-uai-muted">

                                        <?= htmlspecialchars(
                                            $transacao['meio_pagamento'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <td>

                                <div>

                                    <?= htmlspecialchars(
                                        $transacao['grupo_nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>

                                <small
                                    class="text-uai-muted">

                                    <?= htmlspecialchars(
                                        $transacao['subgrupo_nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </small>

                            </td>


                            <td>

                                <?php if (
                                    $transacao['conta_nome']
                                    !== null
                                ): ?>

                                    <?= htmlspecialchars(
                                        $transacao['conta_nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                <?php else: ?>

                                    <span
                                        class="text-uai-muted">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php

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

                                ?>

                                <span
                                    class="badge <?= $statusClass ?>">

                                    <?= htmlspecialchars(
                                        ucfirst($status),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>

                            </td>


                            <td
                                class="text-end fw-semibold">

                                <?php if (
                                    $tipo === 'receita'
                                ): ?>

                                    <span
                                        class="text-success">
                                        +
                                        <?= Money::format(
                                            (int)
                                            $transacao['valor_centavos']
                                        ) ?>
                                    </span>


                                <?php else: ?>

                                    <span
                                        class="text-danger">
                                        -
                                        <?= Money::format(
                                            (int)
                                            $transacao['valor_centavos']
                                        ) ?>
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