<?php

declare(strict_types=1);

use App\Core\Money;


$ativas = 0;
$pausadas = 0;
$encerradas = 0;


foreach ($recorrencias as $item) {

    if ((int) $item['ativo'] === 1) {

        $ativas++;

        continue;
    }


    if (
        $item['proxima_ocorrencia']
        !== null
    ) {

        $pausadas++;

        continue;
    }


    $encerradas++;
}

?>


<div class="d-flex flex-column flex-md-row
           justify-content-between
           align-items-md-center
           gap-3 mb-4">

    <div>

        <h1 class="h3 mb-1">
            Recorrências
        </h1>

        <p class="text-uai-muted mb-0">
            Gerencie suas movimentações recorrentes.
        </p>

    </div>


    <div class="d-flex gap-2 flex-wrap">

        <form method="POST" action="<?= htmlspecialchars(
            $basePath
            . '/recorrencias/processar',
            ENT_QUOTES,
            'UTF-8'
        ) ?>" class="m-0">

            <input type="hidden" name="_token" value="<?= htmlspecialchars(
                $csrfToken,
                ENT_QUOTES,
                'UTF-8'
            ) ?>">

            <button type="submit" class="btn btn-outline-secondary">
                ↻ Atualizar previstas
            </button>

        </form>


        <a href="<?= htmlspecialchars(
            $basePath
            . '/movimentacoes/nova?modo=recorrente',
            ENT_QUOTES,
            'UTF-8'
        ) ?>" class="btn btn-uai-primary">
            + Nova recorrência
        </a>

    </div>

</div>


<?php if (
    isset($_GET['processado'])
): ?>

    <div class="alert alert-success">

        Processamento concluído.

        <strong>
            <?= (int) (
                $_GET['criadas']
                ?? 0
            ) ?>
        </strong>

        movimentação(ões) criada(s).

    </div>

<?php endif; ?>


<?php if (
    isset($_GET['acao'])
): ?>

    <div class="alert alert-success">

        <?php

        echo match (
        $_GET['acao']
        ) {

            'pausada' =>
                'Recorrência pausada.',

            'retomada' =>
                'Recorrência retomada.',

            'encerrada' =>
                'Recorrência encerrada.',

            default =>
                'Operação realizada.'
        };

        ?>

    </div>

<?php endif; ?>


<?php if (
    isset($_GET['erro'])
): ?>

    <div class="alert alert-danger">

        <?= htmlspecialchars(
            $_GET['erro'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </div>

<?php endif; ?>


<div class="row g-3 mb-4">

    <div class="col-4">

        <div class="card h-100">

            <div class="card-body">

                <div class="text-uai-muted small">
                    Ativas
                </div>

                <div class="h4 mb-0">
                    <?= $ativas ?>
                </div>

            </div>

        </div>

    </div>


    <div class="col-4">

        <div class="card h-100">

            <div class="card-body">

                <div class="text-uai-muted small">
                    Pausadas
                </div>

                <div class="h4 mb-0">
                    <?= $pausadas ?>
                </div>

            </div>

        </div>

    </div>


    <div class="col-4">

        <div class="card h-100">

            <div class="card-body">

                <div class="text-uai-muted small">
                    Encerradas
                </div>

                <div class="h4 mb-0">
                    <?= $encerradas ?>
                </div>

            </div>

        </div>

    </div>

</div>


<form method="get" class="card mb-4"><div class="card-body row g-3">
<div class="col-md-4"><label for="recurrence-q" class="form-label">Buscar descrição</label><input id="recurrence-q" name="q" class="form-control" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></div>
<div class="col-md-3"><label for="recurrence-status-filter" class="form-label">Situação</label><select id="recurrence-status-filter" name="status" class="form-select"><option value="">Todas</option><?php foreach(['ativa'=>'Ativa','pausada'=>'Pausada','encerrada'=>'Encerrada'] as $key=>$label): ?><option value="<?= $key ?>" <?= ($_GET['status'] ?? '')===$key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="col-md-3"><label for="recurrence-frequency-filter" class="form-label">Frequência</label><select id="recurrence-frequency-filter" name="frequencia" class="form-select"><option value="">Todas</option><?php foreach(['diaria'=>'Diária','semanal'=>'Semanal','mensal'=>'Mensal','anual'=>'Anual'] as $key=>$label): ?><option value="<?= $key ?>" <?= ($_GET['frequencia'] ?? '')===$key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-uai-primary">Filtrar</button></div>
<div class="col-12"><a href="<?= htmlspecialchars($basePath . '/movimentacoes/nova?modo=recorrente', ENT_QUOTES, 'UTF-8') ?>">+ Nova recorrência</a></div></div></form>
<div class="card">

    <div class="card-header bg-transparent">

        <h5 class="mb-0">
            Recorrências cadastradas
        </h5>

    </div>


    <?php if (
        $recorrencias === []
    ): ?>

        <div class="card-body
                   text-center py-5">

            <h5>
                Nenhuma recorrência cadastrada
            </h5>

            <p class="text-uai-muted mb-0">
                Use Nova recorrência para programar seu primeiro lançamento.
            </p>

        </div>


    <?php else: ?>

        <div class="table-responsive">

            <table class="table
                       align-middle
                       mb-0">

                <thead>

                    <tr>

                        <th>
                            Descrição
                        </th>

                        <th>
                            Categoria
                        </th>

                        <th>
                            Valor
                        </th>

                        <th>
                            Frequência
                        </th>

                        <th>
                            Próxima
                        </th>

                        <th>
                            Progresso
                        </th>

                        <th>
                            Status
                        </th>

                        <th></th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach (
                        $recorrencias
                        as $recorrencia
                    ): ?>

                        <?php

                        $ativa =
                            (int) $recorrencia[
                                'ativo'
                            ] === 1;


                        $encerrada =
                            !$ativa
                            &&
                            $recorrencia[
                                'proxima_ocorrencia'
                            ] === null;


                        $pausada =
                            !$ativa
                            &&
                            !$encerrada;


                        $statusLabel =
                            $ativa
                            ? 'Ativa'
                            : (
                                $pausada
                                ? 'Pausada'
                                : 'Encerrada'
                            );


                        $statusClass =
                            $ativa
                            ? 'text-bg-success'
                            : (
                                $pausada
                                ? 'text-bg-warning'
                                : 'text-bg-secondary'
                            );


                        $frequencia =
                            match (
                            $recorrencia[
                                'frequencia'
                            ]
                            ) {

                                'diaria' =>
                                    'Diária',

                                'semanal' =>
                                    'Semanal',

                                'mensal' =>
                                    'Mensal',

                                'anual' =>
                                    'Anual',

                                'editada' =>
                                    'Recorrência atualizada.',

                                default =>
                                    ucfirst(
                                        $recorrencia[
                                            'frequencia'
                                        ]
                                    )
                            };

                        ?>


                        <tr>

                            <td>

                                <div class="fw-semibold">

                                    <?= htmlspecialchars(
                                        $recorrencia[
                                            'descricao'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </div>


                                <?php if (
                                    $recorrencia[
                                        'conta_nome'
                                    ] !== null
                                ): ?>

                                    <small class="text-uai-muted">

                                        <?= htmlspecialchars(
                                            $recorrencia[
                                                'conta_nome'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </small>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $recorrencia[
                                        'grupo_nome'
                                    ]
                                    . ' › '
                                    . $recorrencia[
                                        'subgrupo_nome'
                                    ],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </td>


                            <td class="fw-semibold">

                                <?= Money::format(
                                    (int) 
                                    $recorrencia[
                                        'valor_centavos'
                                    ]
                                ) ?>

                            </td>


                            <td>

                                <?= $frequencia ?>

                                <?php if (
                                    (int) $recorrencia[
                                        'intervalo'
                                    ] > 1
                                ): ?>

                                    <div class="text-uai-muted small">

                                        A cada

                                        <?= (int) 
                                            $recorrencia[
                                                'intervalo'
                                            ]
                                            ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <td>

                                <?php if (
                                    $recorrencia[
                                        'proxima_ocorrencia'
                                    ] !== null
                                ): ?>

                                    <?= date(
                                        'd/m/Y',
                                        strtotime(
                                            $recorrencia[
                                                'proxima_ocorrencia'
                                            ]
                                        )
                                    ) ?>

                                <?php else: ?>

                                    —

                                <?php endif; ?>

                            </td>


                            <td>

                                <strong>

                                    <?= (int) 
                                        $recorrencia[
                                            'ocorrencias_geradas'
                                        ]
                                        ?>

                                </strong>

                                <?php if (
                                    $recorrencia[
                                        'total_ocorrencias'
                                    ] !== null
                                ): ?>

                                    /

                                    <?= (int) 
                                        $recorrencia[
                                            'total_ocorrencias'
                                        ]
                                        ?>

                                <?php else: ?>

                                    <span class="text-uai-muted">
                                        / ∞
                                    </span>

                                <?php endif; ?>

                            </td>


                            <td>

                                <span class="badge
                                       <?= $statusClass ?>">

                                    <?= $statusLabel ?>

                                </span>

                            </td>


                            <td class="text-end">

                                <?php if (
                                    !$encerrada
                                ): ?>

                                    <div class="dropdown">

                                        <button type="button" class="btn btn-sm
                                               btn-outline-secondary" data-bs-toggle="dropdown">
                                            •••
                                        </button>


                                        <ul class="dropdown-menu
                                               dropdown-menu-end">

                                            <?php if (
                                                $ativa
                                            ): ?>

                                                <li>

                                                    <a class="dropdown-item" href="<?= htmlspecialchars(
                                                        $basePath
                                                        . '/recorrencias/'
                                                        . (int) $recorrencia['id']
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
                                                        . '/recorrencias/'
                                                        . (int) 
                                                        $recorrencia['id']
                                                        . '/pausar',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">

                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                            $csrfToken,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">

                                                        <button type="submit" class="dropdown-item">
                                                            Pausar
                                                        </button>

                                                    </form>

                                                </li>


                                            <?php else: ?>

                                                <li>

                                                    <form method="POST" action="<?= htmlspecialchars(
                                                        $basePath
                                                        . '/recorrencias/'
                                                        . (int) 
                                                        $recorrencia['id']
                                                        . '/retomar',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">

                                                        <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                            $csrfToken,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">

                                                        <button type="submit" class="dropdown-item">
                                                            Retomar
                                                        </button>

                                                    </form>

                                                </li>

                                            <?php endif; ?>


                                            <li>

                                                <hr class="dropdown-divider">

                                            </li>


                                            <li>

                                                <form method="POST" action="<?= htmlspecialchars(
                                                    $basePath
                                                    . '/recorrencias/'
                                                    . (int) 
                                                    $recorrencia['id']
                                                    . '/encerrar',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>" onsubmit="return confirm(
                                                    'Deseja realmente encerrar esta recorrência?'
                                                );">

                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                        $csrfToken,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">

                                                    <button type="submit" class="dropdown-item
                                                           text-danger">
                                                        Encerrar
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

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>
