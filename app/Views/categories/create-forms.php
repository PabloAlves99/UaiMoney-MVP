<?php use App\Core\Html as H; ?>
<div class="row g-3 mb-4">
    <div class="col-lg-6"><details class="card category-create"><summary>+ Nova categoria</summary><div class="card-body pt-0"><form method="post" action="<?= H::escape($basePath.'/categorias/grupos') ?>" class="vstack gap-3"><?= H::fields($csrfToken) ?>
        <div><label class="form-label" for="new-group-name">Nome</label><input class="form-control" id="new-group-name" name="nome" maxlength="100" required value="<?= H::escape($old['nome'] ?? '') ?>"></div>
        <div><label class="form-label" for="new-group-type">Tipo</label><select class="form-select" id="new-group-type" name="tipo"><option value="despesa" <?= ($old['tipo'] ?? '')==='despesa' ? 'selected' : '' ?>>Despesa</option><option value="receita" <?= ($old['tipo'] ?? '')==='receita' ? 'selected' : '' ?>>Receita</option></select></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="essencial" value="1" id="new-group-essential" <?= isset($old['essencial']) ? 'checked' : '' ?>><label class="form-check-label" for="new-group-essential">Despesa essencial</label></div>
        <div><button class="btn btn-uai-primary">Criar categoria</button></div>
    </form></div></details></div>
    <div class="col-lg-6"><details class="card category-create"><summary>+ Nova subcategoria</summary><div class="card-body pt-0">
    <?php if (!$activeGroups): ?><p class="text-secondary mb-0">Cadastre ou reative uma categoria antes de adicionar subcategorias.</p><?php else: ?>
    <form method="post" action="<?= H::escape($basePath.'/categorias/subgrupos') ?>" class="vstack gap-3"><?= H::fields($csrfToken) ?>
        <div><label class="form-label" for="new-subgroup-parent">Categoria</label><select class="form-select" id="new-subgroup-parent" name="grupo_id" required><option value="">Selecione</option><?php foreach ($activeGroups as $g): ?><option value="<?= (int)$g['id'] ?>" <?= (string)($old['grupo_id'] ?? '')===(string)$g['id'] ? 'selected' : '' ?>><?= H::escape($g['nome'].' · '.($g['tipo']==='receita' ? 'Receita' : 'Despesa')) ?></option><?php endforeach; ?></select></div>
        <div><label class="form-label" for="new-subgroup-name">Nome</label><input class="form-control" id="new-subgroup-name" name="nome" maxlength="100" required value="<?= H::escape($old['nome'] ?? '') ?>"></div>
        <div><label class="form-label" for="new-subgroup-description">Descrição (opcional)</label><textarea class="form-control" id="new-subgroup-description" name="descricao" rows="2"><?= H::escape($old['descricao'] ?? '') ?></textarea></div>
        <div><button class="btn btn-uai-primary">Criar subcategoria</button></div>
    </form><?php endif; ?></div></details></div>
</div>
