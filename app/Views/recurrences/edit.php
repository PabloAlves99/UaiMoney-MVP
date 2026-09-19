<?php

declare(strict_types=1);


$meiosPagamento = [
    'pix' => 'Pix',
    'dinheiro' => 'Dinheiro',
    'debito' => 'Débito',
    'credito' => 'Crédito',
    'boleto' => 'Boleto',
    'transferencia' => 'Transferência',
    'outro' => 'Outro'
];


$valor =
    number_format(
        (int) $recorrencia[
            'valor_centavos'
        ] / 100,
        2,
        '.',
        ''
    );


$temQuantidade =
    $recorrencia[
        'total_ocorrencias'
    ] !== null;

?>


<div class="mb-4">

    <a href="<?= htmlspecialchars(
        $basePath
        . '/recorrencias',
        ENT_QUOTES,
        'UTF-8'
    ) ?>" class="text-decoration-none">
        ← Voltar para recorrências
    </a>

</div>


<div class="row justify-content-center">

    <div class="col-xl-8">

        <div class="card">

            <div class="card-header bg-transparent">

                <h1 class="h4 mb-1">
                    Editar recorrência
                </h1>

                <p class="text-uai-muted mb-0">
                    As alterações afetam somente
                    as próximas ocorrências.
                </p>

            </div>


            <form method="POST" action="<?= htmlspecialchars(
                $basePath
                . '/recorrencias/'
                . (int) $recorrencia['id']
                . '/editar',
                ENT_QUOTES,
                'UTF-8'
            ) ?>">

                <input type="hidden" name="_token" value="<?= htmlspecialchars(
                    $csrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">


                <div class="card-body">

                    <div class="row g-3">


                        <div class="col-md-8">

                            <label class="form-label">
                                Descrição
                            </label>

                            <input type="text" name="descricao" class="form-control" value="<?= htmlspecialchars(
                                $recorrencia[
                                    'descricao'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>" required>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Valor
                            </label>

                            <input type="number" name="valor" class="form-control" min="0.01" step="0.01"
                                value="<?= $valor ?>" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Categoria
                            </label>

                            <select name="subgrupo_id" class="form-select" required>

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
                                            $grupos as $grupo
                                        ): ?>

                                            <?php if (
                                                $grupo['tipo']
                                                !== $tipo
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
                                                    (int) 
                                                    $subgrupo['id']
                                                    ===
                                                    (int) 
                                                    $recorrencia[
                                                        'subgrupo_id'
                                                    ]
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


                        <div class="col-md-6">

                            <label class="form-label">
                                Conta
                            </label>

                            <select name="conta_id" class="form-select">

                                <option value="">
                                    Definir ao efetivar
                                </option>


                                <?php foreach (
                                    $contas as $conta
                                ): ?>

                                    <option value="<?= (int) 
                                        $conta['id']
                                        ?>" <?= (
                                        $recorrencia[
                                            'conta_id'
                                        ] !== null
                                        &&
                                        (int) 
                                        $recorrencia[
                                            'conta_id'
                                        ]
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


                        <div class="col-md-4">

                            <label class="form-label">
                                Frequência
                            </label>

                            <select name="frequencia" class="form-select" required>

                                <?php

                                $frequencias = [
                                    'diaria' => 'Diária',
                                    'semanal' => 'Semanal',
                                    'mensal' => 'Mensal',
                                    'anual' => 'Anual'
                                ];

                                ?>

                                <?php foreach (
                                    $frequencias
                                    as $value => $label
                                ): ?>

                                    <option value="<?= $value ?>" <?= $recorrencia[
                                          'frequencia'
                                      ] === $value
                                          ? 'selected'
                                          : ''
                                          ?>>
                                        <?= $label ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                A cada
                            </label>

                            <input type="number" name="intervalo" class="form-control" min="1" max="120" value="<?= (int) 
                                $recorrencia[
                                    'intervalo'
                                ]
                                ?>" required>

                        </div>


                        <div class="col-md-4">

                            <label class="form-label">
                                Próxima ocorrência
                            </label>

                            <input type="date" name="proxima_ocorrencia" class="form-control" value="<?= htmlspecialchars(
                                $recorrencia[
                                    'proxima_ocorrencia'
                                ],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Duração
                            </label>

                            <select class="form-select" id="recurrenceEditDuration" name="tipo_duracao">

                                <option value="indefinida" <?= !$temQuantidade
                                    ? 'selected'
                                    : ''
                                    ?>>
                                    Sem término
                                </option>

                                <option value="quantidade" <?= $temQuantidade
                                    ? 'selected'
                                    : ''
                                    ?>>
                                    Quantidade de ocorrências
                                </option>

                            </select>

                        </div>


                        <div class="col-md-6 <?= !$temQuantidade
                            ? 'd-none'
                            : ''
                            ?>" id="recurrenceEditQuantityContainer">

                            <label class="form-label">
                                Total de ocorrências
                            </label>

                            <input type="number" class="form-control" id="recurrenceEditQuantity"
                                name="total_ocorrencias" min="<?= (int) 
                                    $recorrencia[
                                        'ocorrencias_geradas'
                                    ] + 1
                                    ?>" max="1200" value="<?= $temQuantidade
                                    ? (int) 
                                    $recorrencia[
                                        'total_ocorrencias'
                                    ]
                                    : ''
                                    ?>">

                            <div class="form-text">

                                Já geradas:

                                <?= (int) 
                                    $recorrencia[
                                        'ocorrencias_geradas'
                                    ]
                                    ?>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">
                                Meio de pagamento
                            </label>

                            <select name="meio_pagamento" class="form-select">

                                <option value="">
                                    Não informado
                                </option>


                                <?php foreach (
                                    $meiosPagamento
                                    as $value => $label
                                ): ?>

                                    <option value="<?= $value ?>" <?= $recorrencia[
                                          'meio_pagamento'
                                      ] === $value
                                          ? 'selected'
                                          : ''
                                          ?>>
                                        <?= $label ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="col-12">

                            <label class="form-label">
                                Observação
                            </label>

                            <textarea name="observacao" class="form-control" rows="3"><?= htmlspecialchars(
                                $recorrencia[
                                    'observacao'
                                ] ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>

                        </div>

                    </div>

                </div>


                <div class="card-footer bg-transparent">

                    <div class="d-flex
                               justify-content-end
                               gap-2">

                        <a href="<?= htmlspecialchars(
                            $basePath
                            . '/recorrencias',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>

                        <button type="submit" class="btn btn-uai-primary">
                            Salvar alterações
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            const duration =
                document.getElementById(
                    'recurrenceEditDuration'
                );

            const container =
                document.getElementById(
                    'recurrenceEditQuantityContainer'
                );

            const quantity =
                document.getElementById(
                    'recurrenceEditQuantity'
                );


            function updateDuration() {

                const defined =
                    duration.value
                    === 'quantidade';


                container.classList.toggle(
                    'd-none',
                    !defined
                );


                quantity.required =
                    defined;


                if (!defined) {

                    quantity.value = '';

                }
            }


            duration.addEventListener(
                'change',
                updateDuration
            );


            updateDuration();
        }
    );

</script>