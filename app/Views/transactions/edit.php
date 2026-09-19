<?php

declare(strict_types=1);

?>


<div class="mb-4">

    <a href="<?= htmlspecialchars(
        $basePath . '/movimentacoes',
        ENT_QUOTES,
        'UTF-8'
    ) ?>" class="text-decoration-none">
        ← Voltar para movimentações
    </a>

</div>


<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="h3 mb-1">
            Editar movimentação
        </h1>

        <p class="text-uai-muted mb-0">
            Altere os dados da movimentação pendente.
        </p>

    </div>

    <span class="badge text-bg-warning">
        Pendente
    </span>

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


<div class="card">

    <div class="card-body">

        <form method="POST" action="<?= htmlspecialchars(
            $basePath
            . '/movimentacoes/'
            . (int) $transacao['id']
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


                <!-- Descrição -->

                <div class="col-md-6">

                    <label for="descricao" class="form-label">
                        Descrição
                    </label>

                    <input type="text" class="form-control" id="descricao" name="descricao" value="<?= htmlspecialchars(
                        $transacao['descricao'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>" required>

                </div>


                <!-- Valor -->

                <div class="col-md-3">

                    <label for="valor" class="form-label">
                        Valor
                    </label>

                    <input type="number" class="form-control" id="valor" name="valor" min="0.01" step="0.01" value="<?= number_format(
                        (
                            (int) 
                            $transacao['valor_centavos']
                        ) / 100,
                        2,
                        '.',
                        ''
                    ) ?>" required>

                </div>


                <!-- Status -->

                <div class="col-md-3">

                    <label class="form-label">
                        Status
                    </label>

                    <input type="text" class="form-control" value="Pendente" disabled>

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
                                            ?>" <?= (
                                            (int) $subgrupo['id']
                                            ===
                                            (int) $transacao['subgrupo_id']
                                        )
                                            ? 'selected'
                                            : ''
                                            ?>>

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


                        <?php foreach (
                            $contas as $conta
                        ): ?>

                            <option value="<?= (int) 
                                $conta['id']
                                ?>" <?= (
                                $transacao['conta_id'] !== null
                                &&
                                (int) $transacao['conta_id']
                                ===
                                (int) $conta['id']
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


                <!-- Competência -->

                <div class="col-md-6">

                    <label for="dataCompetencia" class="form-label">
                        Competência
                    </label>

                    <input type="date" class="form-control" id="dataCompetencia" name="data_competencia" value="<?= htmlspecialchars(
                        $transacao[
                            'data_competencia'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>" required>

                </div>


                <!-- Vencimento -->

                <div class="col-md-6">

                    <label for="dataVencimento" class="form-label">
                        Vencimento
                    </label>

                    <input type="date" class="form-control" id="dataVencimento" name="data_vencimento" value="<?= htmlspecialchars(
                        $transacao[
                            'data_vencimento'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>" required>

                </div>


                <!-- Meio -->

                <div class="col-md-4">

                    <label for="meioPagamento" class="form-label">
                        Meio de pagamento
                    </label>

                    <select class="form-select" id="meioPagamento" name="meio_pagamento">

                        <option value="">
                            Não informado
                        </option>


                        <?php

                        $meios = [
                            'pix' => 'Pix',
                            'dinheiro' => 'Dinheiro',
                            'debito' => 'Débito',
                            'credito' => 'Crédito',
                            'boleto' => 'Boleto',
                            'transferencia' => 'Transferência',
                            'outro' => 'Outro'
                        ];

                        ?>


                        <?php foreach (
                            $meios as $valor => $label
                        ): ?>

                            <option value="<?= htmlspecialchars(
                                $valor,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>" <?= (
                                 $transacao[
                                     'meio_pagamento'
                                 ] === $valor
                             )
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


                <!-- Observação -->

                <div class="col-md-8">

                    <label for="observacao" class="form-label">
                        Observação
                    </label>

                    <input type="text" class="form-control" id="observacao" name="observacao" value="<?= htmlspecialchars(
                        $transacao['observacao']
                        ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

                </div>


                <div class="col-12 mt-4">

                    <div class="d-flex gap-2">

                        <button type="submit" class="btn btn-uai-primary">
                            Salvar alterações
                        </button>


                        <a href="<?= htmlspecialchars(
                            $basePath
                            . '/movimentacoes',
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