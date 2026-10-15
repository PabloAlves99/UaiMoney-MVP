<?php
use App\Core\Html as H;
$isGroup = $kind==='grupo';
$values = array_replace($record,$old);
$essential = $error!==null ? isset($old['essencial']) : ($record['classificacao'] ?? '')==='essencial';
?>
<div class="page-heading"><div><p class="eyebrow">ORGANIZAÇÃO</p><h1>Editar <?= $isGroup ? 'categoria' : 'subcategoria' ?></h1><p class="text-secondary mb-0"><?= H::escape($record['nome']) ?></p></div><a class="btn btn-outline-secondary" href="<?= H::escape($basePath.$returnPath) ?>">← Voltar</a></div>
<?php if ($error!==null): ?><div class="alert alert-danger" role="alert"><?= H::escape($error) ?></div><?php endif; ?>
<section class="card category-edit-card"><div class="card-body"><form method="post" class="vstack gap-3" action="<?= H::escape($basePath.'/categorias/'.($isGroup ? 'grupos' : 'subgrupos').'/'.(int)$record['id'].'/editar') ?>"><?= H::fields($csrfToken) ?><input type="hidden" name="origem" value="<?= $returnPath==='/categorias/desativadas' ? 'desativadas' : 'ativas' ?>">
    <div><label class="form-label" for="edit-category-name">Nome</label><input class="form-control" id="edit-category-name" name="nome" required maxlength="100" value="<?= H::escape($values['nome']) ?>"></div>
    <?php if ($isGroup): ?>
        <div><label class="form-label" for="edit-category-type">Tipo</label><select class="form-select" id="edit-category-type" name="tipo" <?= $typeLocked ? 'disabled' : '' ?>><option value="receita" <?= $values['tipo']==='receita' ? 'selected' : '' ?>>Receita</option><option value="despesa" <?= $values['tipo']==='despesa' ? 'selected' : '' ?>>Despesa</option></select><?php if ($typeLocked): ?><p class="form-text mb-0">O tipo é mantido porque esta categoria já possui lançamentos, recorrências ou orçamento.</p><?php endif; ?></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="essencial" value="1" id="edit-category-essential" <?= $essential ? 'checked' : '' ?>><label class="form-check-label" for="edit-category-essential">Despesa essencial</label><p class="form-text mb-0">A classificação de essencial se aplica somente às despesas.</p></div>
    <?php else: ?>
        <div><label class="form-label">Categoria</label><p class="mb-0"><?= H::escape($record['grupo_nome']) ?></p></div>
        <div><label class="form-label" for="edit-category-description">Descrição (opcional)</label><textarea class="form-control" id="edit-category-description" name="descricao" rows="3"><?= H::escape($values['descricao'] ?? '') ?></textarea></div>
    <?php endif; ?>
    <div class="d-flex gap-2 flex-wrap"><button class="btn btn-uai-primary">Salvar alterações</button><a class="btn btn-outline-secondary" href="<?= H::escape($basePath.$returnPath) ?>">Cancelar</a></div>
</form></div></section>
