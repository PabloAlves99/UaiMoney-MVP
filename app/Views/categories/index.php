<?php

declare(strict_types=1);


/*
|--------------------------------------------------------------------------
| Resumo
|--------------------------------------------------------------------------
*/

$totalCategorias = count($grupos);

$totalSubcategorias = 0;
$totalReceitas = 0;
$totalDespesas = 0;


foreach ($grupos as $grupo) {

    $totalSubcategorias +=
        count($grupo['subgrupos']);


    if ($grupo['tipo'] === 'receita') {
        $totalReceitas++;
    }


    if ($grupo['tipo'] === 'despesa') {
        $totalDespesas++;
    }
}


/*
|--------------------------------------------------------------------------
| Separação visual
|--------------------------------------------------------------------------
*/

$gruposReceita = [];
$gruposDespesa = [];


foreach ($grupos as $grupo) {

    if ($grupo['tipo'] === 'receita') {
        $gruposReceita[] = $grupo;
    }

    if ($grupo['tipo'] === 'despesa') {
        $gruposDespesa[] = $grupo;
    }
}

?>


<!--
|--------------------------------------------------------------------------
| Cabeçalho
|--------------------------------------------------------------------------
-->

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">

    <div>

        <h1 class="h3 mb-1">
            Categorias
        </h1>

        <p class="text-uai-muted mb-0">
            Organize suas receitas e despesas.
        </p>

    </div>


    <div class="d-flex gap-2 flex-wrap">

        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
            data-bs-target="#novaSubcategoriaModal" <?= $grupos === []
                ? 'disabled'
                : ''
                ?>>
            + Subcategoria
        </button>


        <button type="button" class="btn btn-uai-primary" data-bs-toggle="modal" data-bs-target="#novaCategoriaModal">
            + Categoria
        </button>

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
| Resumo
|--------------------------------------------------------------------------
-->

<div class="row g-3 mb-4">


    <div class="col-6 col-xl-3">

        <div class="card uai-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Categorias
                </div>

                <div class="uai-summary-value">

                    <?= $totalCategorias ?>

                </div>

                <div class="uai-summary-caption">
                    Grupos ativos
                </div>

            </div>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="card uai-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Subcategorias
                </div>

                <div class="uai-summary-value">

                    <?= $totalSubcategorias ?>

                </div>

                <div class="uai-summary-caption">
                    Classificações disponíveis
                </div>

            </div>

        </div>

    </div>


    <div class="col-6 col-xl-3">

        <div class="card uai-summary-card h-100">

            <div class="card-body">

                <div class="uai-summary-label">
                    Receitas
                </div>

                <div class="uai-summary-value text-success">

                    <?= $totalReceitas ?>

                </div>

                <div class="uai-summary-caption">
                    Categorias de entrada
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

                    <?= $totalDespesas ?>

                </div>

                <div class="uai-summary-caption">
                    Categorias de saída
                </div>

            </div>

        </div>

    </div>


</div>


<!--
|--------------------------------------------------------------------------
| Categorias
|--------------------------------------------------------------------------
-->

<?php if ($grupos === []): ?>

    <div class="card">

        <div class="card-body text-center py-5">

            <h5 class="mb-2">
                Nenhuma categoria cadastrada
            </h5>

            <p class="text-uai-muted mb-3">
                Crie categorias para organizar
                suas movimentações.
            </p>

            <button type="button" class="btn btn-uai-primary" data-bs-toggle="modal" data-bs-target="#novaCategoriaModal">
                + Criar primeira categoria
            </button>

        </div>

    </div>


<?php else: ?>


    <div class="row g-4">


        <!--
        |--------------------------------------------------------------------------
        | Receitas
        |--------------------------------------------------------------------------
        -->

        <div class="col-xl-6">


            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h4 class="mb-1">
                        Receitas
                    </h4>

                    <div class="text-uai-muted small">

                        <?= count($gruposReceita) ?>

                        <?= count($gruposReceita) === 1
                            ? 'categoria'
                            : 'categorias'
                            ?>

                    </div>

                </div>


                <span class="uai-category-type-badge uai-category-income">
                    Entrada
                </span>

            </div>


            <?php if ($gruposReceita === []): ?>

                <div class="card">

                    <div class="card-body text-center py-4">

                        <p class="text-uai-muted mb-0">
                            Nenhuma categoria de receita.
                        </p>

                    </div>

                </div>


            <?php else: ?>


                <div class="d-grid gap-3">


                    <?php foreach (
                        $gruposReceita as $grupo
                    ): ?>

                        <?php
                        $subcategorias =
                            $grupo['subgrupos'];
                        ?>


                        <div class="card uai-category-card">

                            <div class="card-body">


                                <!-- Cabeçalho da categoria -->

                                <div class="d-flex justify-content-between align-items-start gap-3">

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="uai-category-icon uai-category-icon-income">

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    substr(
                                                        $grupo['nome'],
                                                        0,
                                                        1
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>


                                        <div>

                                            <h5 class="mb-1">

                                                <?= htmlspecialchars(
                                                    $grupo['nome'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </h5>


                                            <div class="text-uai-muted small">

                                                <?= count(
                                                    $subcategorias
                                                ) ?>

                                                <?= count(
                                                    $subcategorias
                                                ) === 1
                                                    ? 'subcategoria'
                                                    : 'subcategorias'
                                                    ?>

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

                                                <form method="POST" action="<?= htmlspecialchars(
                                                    $basePath
                                                    . '/categorias/grupos/'
                                                    . (int) $grupo['id']
                                                    . '/desativar',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                    onsubmit="return confirm('Deseja realmente desativar esta categoria?');">

                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                        $csrfToken,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">

                                                    <button type="submit" class="dropdown-item text-danger">
                                                        Desativar categoria
                                                    </button>

                                                </form>

                                            </li>

                                        </ul>

                                    </div>

                                </div>


                                <!-- Subcategorias -->

                                <div class="uai-subcategory-list">


                                    <?php if (
                                        $subcategorias === []
                                    ): ?>

                                        <div class="uai-subcategory-empty">
                                            Nenhuma subcategoria cadastrada.
                                        </div>


                                    <?php else: ?>


                                        <?php foreach (
                                            $subcategorias as $subgrupo
                                        ): ?>

                                            <div class="uai-subcategory-item">

                                                <div>

                                                    <div class="fw-semibold">

                                                        <?= htmlspecialchars(
                                                            $subgrupo['nome'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </div>


                                                    <?php if (
                                                        !empty(
                                                        $subgrupo[
                                                            'descricao'
                                                        ]
                                                    )
                                                    ): ?>

                                                        <div class="text-uai-muted small mt-1">

                                                            <?= htmlspecialchars(
                                                                $subgrupo[
                                                                    'descricao'
                                                                ],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>

                                                        </div>

                                                    <?php endif; ?>

                                                </div>


                                                <div class="dropdown">

                                                    <button type="button" class="btn btn-sm uai-subcategory-action"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        •••
                                                    </button>


                                                    <ul class="dropdown-menu dropdown-menu-end">

                                                        <li>

                                                            <form method="POST" action="<?= htmlspecialchars(
                                                                $basePath
                                                                . '/categorias/subgrupos/'
                                                                . (int) 
                                                                $subgrupo[
                                                                    'id'
                                                                ]
                                                                . '/desativar',
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                                                onsubmit="return confirm('Deseja realmente desativar esta subcategoria?');">

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

                                        <?php endforeach; ?>


                                    <?php endif; ?>


                                </div>


                            </div>

                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>


        <!--
        |--------------------------------------------------------------------------
        | Despesas
        |--------------------------------------------------------------------------
        -->

        <div class="col-xl-6">


            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h4 class="mb-1">
                        Despesas
                    </h4>

                    <div class="text-uai-muted small">

                        <?= count($gruposDespesa) ?>

                        <?= count($gruposDespesa) === 1
                            ? 'categoria'
                            : 'categorias'
                            ?>

                    </div>

                </div>


                <span class="uai-category-type-badge uai-category-expense">
                    Saída
                </span>

            </div>


            <?php if ($gruposDespesa === []): ?>

                <div class="card">

                    <div class="card-body text-center py-4">

                        <p class="text-uai-muted mb-0">
                            Nenhuma categoria de despesa.
                        </p>

                    </div>

                </div>


            <?php else: ?>


                <div class="d-grid gap-3">


                    <?php foreach (
                        $gruposDespesa as $grupo
                    ): ?>

                        <?php
                        $subcategorias =
                            $grupo['subgrupos'];
                        ?>


                        <div class="card uai-category-card">

                            <div class="card-body">


                                <div class="d-flex justify-content-between align-items-start gap-3">

                                    <div class="d-flex align-items-center gap-3">

                                        <div class="uai-category-icon uai-category-icon-expense">

                                            <?= htmlspecialchars(
                                                strtoupper(
                                                    substr(
                                                        $grupo['nome'],
                                                        0,
                                                        1
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </div>


                                        <div>

                                            <h5 class="mb-1">

                                                <?= htmlspecialchars(
                                                    $grupo['nome'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>

                                            </h5>


                                            <div class="text-uai-muted small">

                                                <?= count(
                                                    $subcategorias
                                                ) ?>

                                                <?= count(
                                                    $subcategorias
                                                ) === 1
                                                    ? 'subcategoria'
                                                    : 'subcategorias'
                                                    ?>

                                            </div>

                                        </div>

                                    </div>


                                    <div class="dropdown">

                                        <button type="button" class="btn btn-sm btn-outline-secondary uai-action-button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            •••
                                        </button>


                                        <ul class="dropdown-menu dropdown-menu-end">

                                            <li>

                                                <form method="POST" action="<?= htmlspecialchars(
                                                    $basePath
                                                    . '/categorias/grupos/'
                                                    . (int) $grupo['id']
                                                    . '/desativar',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                                    onsubmit="return confirm('Deseja realmente desativar esta categoria?');">

                                                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                                                        $csrfToken,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">

                                                    <button type="submit" class="dropdown-item text-danger">
                                                        Desativar categoria
                                                    </button>

                                                </form>

                                            </li>

                                        </ul>

                                    </div>

                                </div>


                                <div class="uai-subcategory-list">


                                    <?php if (
                                        $subcategorias === []
                                    ): ?>

                                        <div class="uai-subcategory-empty">
                                            Nenhuma subcategoria cadastrada.
                                        </div>


                                    <?php else: ?>


                                        <?php foreach (
                                            $subcategorias as $subgrupo
                                        ): ?>

                                            <div class="uai-subcategory-item">

                                                <div>

                                                    <div class="fw-semibold">

                                                        <?= htmlspecialchars(
                                                            $subgrupo['nome'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>

                                                    </div>


                                                    <?php if (
                                                        !empty(
                                                        $subgrupo[
                                                            'descricao'
                                                        ]
                                                    )
                                                    ): ?>

                                                        <div class="text-uai-muted small mt-1">

                                                            <?= htmlspecialchars(
                                                                $subgrupo[
                                                                    'descricao'
                                                                ],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>

                                                        </div>

                                                    <?php endif; ?>

                                                </div>


                                                <div class="dropdown">

                                                    <button type="button" class="btn btn-sm uai-subcategory-action"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                        •••
                                                    </button>


                                                    <ul class="dropdown-menu dropdown-menu-end">

                                                        <li>

                                                            <form method="POST" action="<?= htmlspecialchars(
                                                                $basePath
                                                                . '/categorias/subgrupos/'
                                                                . (int) 
                                                                $subgrupo[
                                                                    'id'
                                                                ]
                                                                . '/desativar',
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>"
                                                                onsubmit="return confirm('Deseja realmente desativar esta subcategoria?');">

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

                                        <?php endforeach; ?>


                                    <?php endif; ?>


                                </div>


                            </div>

                        </div>


                    <?php endforeach; ?>


                </div>


            <?php endif; ?>


        </div>


    </div>


<?php endif; ?>


<!--
|--------------------------------------------------------------------------
| Modal - Nova categoria
|--------------------------------------------------------------------------
-->

<div class="modal fade" id="novaCategoriaModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Nova categoria
                    </h5>

                    <div class="text-uai-muted small">
                        Crie um grupo de receita ou despesa.
                    </div>

                </div>


                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>

            </div>


            <form method="POST" action="<?= htmlspecialchars(
                $basePath
                . '/categorias/grupos',
                ENT_QUOTES,
                'UTF-8'
            ) ?>">


                <div class="modal-body">


                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">


                    <div class="mb-3">

                        <label for="novaCategoriaNome" class="form-label">
                            Nome
                        </label>

                        <input type="text" class="form-control" id="novaCategoriaNome" name="nome"
                            placeholder="Ex.: Moradia" required>

                    </div>


                    <div>

                        <label for="novaCategoriaTipo" class="form-label">
                            Tipo
                        </label>

                        <select class="form-select" id="novaCategoriaTipo" name="tipo" required>

                            <option value="">
                                Selecione
                            </option>

                            <option value="receita">
                                Receita
                            </option>

                            <option value="despesa">
                                Despesa
                            </option>

                        </select>

                    </div>


                </div>


                <div class="modal-footer">

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-uai-primary">
                        Criar categoria
                    </button>

                </div>


            </form>


        </div>

    </div>

</div>


<!--
|--------------------------------------------------------------------------
| Modal - Nova subcategoria
|--------------------------------------------------------------------------
-->

<div class="modal fade" id="novaSubcategoriaModal" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <div>

                    <h5 class="modal-title">
                        Nova subcategoria
                    </h5>

                    <div class="text-uai-muted small">
                        Adicione uma classificação
                        dentro de uma categoria.
                    </div>

                </div>


                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>

            </div>


            <form method="POST" action="<?= htmlspecialchars(
                $basePath
                . '/categorias/subgrupos',
                ENT_QUOTES,
                'UTF-8'
            ) ?>">


                <div class="modal-body">


                    <input type="hidden" name="_token" value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">


                    <div class="mb-3">

                        <label for="novaSubcategoriaGrupo" class="form-label">
                            Categoria
                        </label>

                        <select class="form-select" id="novaSubcategoriaGrupo" name="grupo_id" required>

                            <option value="">
                                Selecione
                            </option>


                            <optgroup label="Receitas">

                                <?php foreach (
                                    $gruposReceita as $grupo
                                ): ?>

                                    <option value="<?= (int) 
                                        $grupo['id']
                                        ?>">

                                        <?= htmlspecialchars(
                                            $grupo['nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </optgroup>


                            <optgroup label="Despesas">

                                <?php foreach (
                                    $gruposDespesa as $grupo
                                ): ?>

                                    <option value="<?= (int) 
                                        $grupo['id']
                                        ?>">

                                        <?= htmlspecialchars(
                                            $grupo['nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </optgroup>


                        </select>

                    </div>


                    <div class="mb-3">

                        <label for="novaSubcategoriaNome" class="form-label">
                            Nome
                        </label>

                        <input type="text" class="form-control" id="novaSubcategoriaNome" name="nome"
                            placeholder="Ex.: Energia" required>

                    </div>


                    <div>

                        <label for="novaSubcategoriaDescricao" class="form-label">
                            Descrição
                        </label>

                        <textarea class="form-control" id="novaSubcategoriaDescricao" name="descricao" rows="3"
                            placeholder="Opcional"></textarea>

                    </div>


                </div>


                <div class="modal-footer">

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button type="submit" class="btn btn-uai-primary">
                        Criar subcategoria
                    </button>

                </div>


            </form>


        </div>

    </div>

</div>