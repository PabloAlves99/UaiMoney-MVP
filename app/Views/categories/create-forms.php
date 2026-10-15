<?php use App\Core\Html as H; ?>
<div class="modal fade" id="new-category-modal" tabindex="-1" aria-labelledby="new-category-title" aria-hidden="true" <?= $error!==null && isset($old['nome']) && !isset($old['grupo_id']) ? 'data-category-reopen' : '' ?>>
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title h5" id="new-category-title">Nova categoria</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <form method="post" action="<?= H::escape($basePath.'/categorias/grupos') ?>"><?= H::fields($csrfToken) ?>
            <div class="modal-body vstack gap-3">
                <?php if ($error!==null && !isset($old['grupo_id'])): ?><div class="alert alert-danger mb-0" role="alert"><?= H::escape($error) ?></div><?php endif; ?>
                <div><label class="form-label" for="new-group-name">Nome</label><input class="form-control" id="new-group-name" name="nome" maxlength="100" required value="<?= H::escape($old['nome'] ?? '') ?>"></div>
                <div><label class="form-label" for="new-group-type">Tipo</label><select class="form-select" id="new-group-type" name="tipo"><option value="despesa" <?= ($old['tipo'] ?? '')==='despesa' ? 'selected' : '' ?>>Despesa</option><option value="receita" <?= ($old['tipo'] ?? '')==='receita' ? 'selected' : '' ?>>Receita</option></select></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="essencial" value="1" id="new-group-essential" <?= isset($old['essencial']) ? 'checked' : '' ?>><label class="form-check-label" for="new-group-essential">Despesa essencial</label></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-uai-primary">Criar categoria</button></div>
        </form>
    </div></div>
</div>
<div class="modal fade" id="new-subcategory-modal" tabindex="-1" aria-labelledby="new-subcategory-title" aria-hidden="true" <?= $error!==null && isset($old['grupo_id']) ? 'data-category-reopen' : '' ?>>
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title h5" id="new-subcategory-title">Nova subcategoria</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
        <form method="post" action="<?= H::escape($basePath.'/categorias/subgrupos') ?>"><?= H::fields($csrfToken) ?><input type="hidden" name="grupo_id" id="new-subgroup-parent" value="<?= H::escape($old['grupo_id'] ?? '') ?>">
            <div class="modal-body vstack gap-3">
                <?php if ($error!==null && isset($old['grupo_id'])): ?><div class="alert alert-danger mb-0" role="alert"><?= H::escape($error) ?></div><?php endif; ?>
                <p class="small text-secondary mb-0">Categoria: <strong data-subcategory-parent-label><?php foreach ($allGroups as $g) if ((string)$g['id']===(string)($old['grupo_id'] ?? '')) echo H::escape($g['nome']); ?></strong></p>
                <div><label class="form-label" for="new-subgroup-name">Nome</label><input class="form-control" id="new-subgroup-name" name="nome" maxlength="100" required value="<?= H::escape($old['nome'] ?? '') ?>"></div>
                <div><label class="form-label" for="new-subgroup-description">Descrição (opcional)</label><textarea class="form-control" id="new-subgroup-description" name="descricao" rows="2"><?= H::escape($old['descricao'] ?? '') ?></textarea></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-uai-primary">Criar subcategoria</button></div>
        </form>
    </div></div>
</div>
