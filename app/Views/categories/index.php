<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h1 class="h3 mb-1">
            Categorias
        </h1>

        <p class="text-uai-muted mb-0">
            Organize receitas e despesas
            em categorias e subcategorias.
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

    <!-- Nova categoria -->

    <div class="col-lg-6">

        <div class="card h-100">

            <div class="card-body">

                <h5 class="card-title mb-4">
                    Nova categoria
                </h5>

                <form
                    method="POST"
                    action="<?= htmlspecialchars(
                                $basePath
                                    . '/categorias/grupos',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">

                    <input
                        type="hidden"
                        name="_token"
                        value="<?= htmlspecialchars(
                                    $csrfToken,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">


                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="grupoNome">
                            Nome
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="grupoNome"
                            name="nome"
                            required>

                    </div>


                    <div class="mb-3">

                        <label
                            class="form-label"
                            for="grupoTipo">
                            Tipo
                        </label>

                        <select
                            class="form-select"
                            id="grupoTipo"
                            name="tipo"
                            required>

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


                    <button
                        type="submit"
                        class="btn btn-uai-primary">
                        Criar categoria
                    </button>

                </form>

            </div>

        </div>

    </div>


    <!-- Nova subcategoria -->

    <div class="col-lg-6">

        <div class="card h-100">

            <div class="card-body">

                <h5 class="card-title mb-4">
                    Nova subcategoria
                </h5>


                <?php if ($grupos === []): ?>

                    <p class="text-uai-muted">
                        Crie primeiro uma categoria.
                    </p>

                <?php else: ?>

                    <form
                        method="POST"
                        action="<?= htmlspecialchars(
                                    $basePath
                                        . '/categorias/subgrupos',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">

                        <input
                            type="hidden"
                            name="_token"
                            value="<?= htmlspecialchars(
                                        $csrfToken,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>">


                        <div class="mb-3">

                            <label
                                class="form-label"
                                for="grupoId">
                                Categoria
                            </label>

                            <select
                                class="form-select"
                                id="grupoId"
                                name="grupo_id"
                                required>

                                <option value="">
                                    Selecione
                                </option>


                                <?php foreach ($grupos as $grupo): ?>

                                    <option
                                        value="<?= (int) $grupo['id'] ?>">
                                        <?= htmlspecialchars(
                                            ucfirst($grupo['tipo'])
                                                . ' - '
                                                . $grupo['nome'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label
                                class="form-label"
                                for="subgrupoNome">
                                Nome
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="subgrupoNome"
                                name="nome"
                                required>

                        </div>


                        <div class="mb-3">

                            <label
                                class="form-label"
                                for="descricao">
                                Descrição
                            </label>

                            <textarea
                                class="form-control"
                                id="descricao"
                                name="descricao"
                                rows="2"></textarea>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-uai-primary">
                            Criar subcategoria
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </div>

    </div>

</div>


<hr class="my-5">


<div class="row g-4">

    <?php foreach (
        ['receita', 'despesa'] as $tipo
    ): ?>

        <div class="col-lg-6">

            <h4 class="mb-3">

                <?= $tipo === 'receita'
                    ? 'Receitas'
                    : 'Despesas'
                ?>

            </h4>


            <?php

            $encontrou = false;

            ?>


            <?php foreach ($grupos as $grupo): ?>

                <?php if ($grupo['tipo'] !== $tipo) {
                    continue;
                } ?>

                <?php $encontrou = true; ?>


                <div class="card mb-3">

                    <div class="card-body">

                        <div
                            class="d-flex justify-content-between align-items-start">

                            <div>

                                <h5 class="mb-1">

                                    <?= htmlspecialchars(
                                        $grupo['nome'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </h5>

                                <span class="text-uai-muted small">

                                    <?= count(
                                        $grupo['subgrupos']
                                    ) ?>

                                    subcategoria(s)

                                </span>

                            </div>


                            <form
                                method="POST"
                                action="<?= htmlspecialchars(
                                            $basePath
                                                . '/categorias/grupos/'
                                                . (int) $grupo['id']
                                                . '/desativar',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>">

                                <input
                                    type="hidden"
                                    name="_token"
                                    value="<?= htmlspecialchars(
                                                $csrfToken,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                <button
                                    type="submit"
                                    class="btn btn-sm btn-outline-danger">
                                    Desativar
                                </button>

                            </form>

                        </div>


                        <?php if (
                            $grupo['subgrupos'] !== []
                        ): ?>

                            <div class="mt-3">

                                <?php foreach (
                                    $grupo['subgrupos']
                                    as $subgrupo
                                ): ?>

                                    <div
                                        class="d-flex justify-content-between align-items-center border-top border-uai py-2">

                                        <div>

                                            <div>
                                                <?= htmlspecialchars(
                                                    $subgrupo['nome'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </div>


                                            <?php if (
                                                $subgrupo['descricao']
                                                !== null
                                            ): ?>

                                                <small
                                                    class="text-uai-muted">
                                                    <?= htmlspecialchars(
                                                        $subgrupo['descricao'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>


                                        <form
                                            method="POST"
                                            action="<?= htmlspecialchars(
                                                        $basePath
                                                            . '/categorias/subgrupos/'
                                                            . (int) $subgrupo['id']
                                                            . '/desativar',
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>">

                                            <input
                                                type="hidden"
                                                name="_token"
                                                value="<?= htmlspecialchars(
                                                            $csrfToken,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>">

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger">
                                                Desativar
                                            </button>

                                        </form>

                                    </div>

                                <?php endforeach; ?>

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>


            <?php if (!$encontrou): ?>

                <div class="card">

                    <div class="card-body text-uai-muted">

                        Nenhuma categoria cadastrada.

                    </div>

                </div>

            <?php endif; ?>

        </div>

    <?php endforeach; ?>

</div>