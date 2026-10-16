<?php
use App\Core\Html as H;
$origin = $inactive ? 'desativadas' : 'ativas';
$children = [];
foreach ($subgrupos as $sub) $children[(int)$sub['grupo_id']][] = $sub;
?>
<div class="page-heading">
    <div><p class="eyebrow">ORGANIZAÇÃO</p><h1><?= $inactive ? 'Categorias desativadas' : 'Categorias' ?></h1><p class="text-secondary mb-0">Clique em uma categoria para ver suas subcategorias.</p></div>
    <div class="d-flex gap-2"><a class="btn btn-uai-primary" href="<?=H::escape($basePath.'/categorias/limites')?>">Limites mensais</a><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . ($inactive ? '/categorias' : '/categorias/desativadas')) ?>"><?= $inactive ? '← Categorias ativas' : 'Ver desativadas' ?></a></div>
</div>
<?php if ($error!==null): ?><div class="alert alert-danger" role="alert"><?= H::escape($error) ?></div><?php endif; ?>
<?php if ($inactive): ?><p class="small text-secondary mb-4">Reative a categoria para disponibilizar suas subcategorias. As subcategorias desativadas individualmente precisam ser reativadas separadamente.</p><?php endif; ?>
<section class="card"><div class="card-body">
    <div class="section-heading flex-wrap gap-3"><div><h2 class="h5 mb-1"><?= $inactive ? 'Categorias e subcategorias desativadas' : 'Categorias ativas' ?></h2><span class="small text-secondary"><?= count($grupos) ?> categorias</span></div>
        <?php if (!$inactive): ?><button type="button" class="btn btn-uai-primary" data-bs-toggle="modal" data-bs-target="#new-category-modal">+ Nova categoria</button><?php endif; ?>
    </div>
    <div class="table-responsive"><table class="table align-middle category-table category-tree mb-0"><thead><tr><th scope="col">Categoria / subcategoria</th><th scope="col">Subcategorias</th><th scope="col">Tipo</th><th scope="col">Essencial</th><th scope="col">Ações</th></tr></thead>
        <?php foreach ($grupos as $grupo):
            $id=(int)$grupo['id']; $subs=$children[$id] ?? []; $expanded=$selectedGroup===(string)$id;
            $color=$grupo['tipo']==='receita' ? 'success' : 'danger';
        ?>
        <tbody><tr class="category-parent-row" data-category-row>
            <td data-label="Categoria"><button class="category-expand" type="button" aria-expanded="<?= $expanded ? 'true' : 'false' ?>" aria-controls="category-children-<?= $id ?>" data-category-expand><svg viewBox="0 0 16 16" aria-hidden="true"><path d="m6 3 5 5-5 5"/></svg><strong><?= H::escape($grupo['nome']) ?></strong></button><?php if ($inactive && (int)$grupo['ativo']): ?><small class="d-block text-secondary">Categoria ativa · subcategorias desativadas</small><?php endif; ?></td>
            <td data-label="Subcategorias" data-sort-value="<?= count($subs) ?>"><?= count($subs) ?> subcategorias</td>
            <td data-label="Tipo"><span class="badge text-bg-<?= $color ?>"><?= $grupo['tipo']==='receita' ? 'Receita' : 'Despesa' ?></span></td>
            <td data-label="Essencial">
                <?php if ($grupo['tipo']==='despesa'): ?>
                <form method="post" action="<?= H::escape($basePath.'/categorias/grupos/'.$id.'/classificacao') ?>" class="category-essential-form"><?= H::fields($csrfToken) ?><input type="hidden" name="origem" value="<?= $origin ?>"><input class="form-check-input m-0" type="checkbox" name="essencial" value="1" id="essential-<?= $id ?>" data-classification-toggle <?= $grupo['classificacao']==='essencial' ? 'checked' : '' ?>><label class="visually-hidden" for="essential-<?= $id ?>"><?= H::escape('Marcar '.$grupo['nome'].' como essencial') ?></label><noscript><button class="btn btn-sm btn-outline-secondary">Salvar</button></noscript></form>
                <?php else: ?><span class="text-secondary" aria-label="Não se aplica a receitas">—</span><?php endif; ?>
            </td>
            <td data-label="Ações"><div class="category-row-actions">
                <?php if (!$inactive): ?><button type="button" class="btn btn-sm btn-outline-<?= $color ?>" data-bs-toggle="modal" data-bs-target="#new-subcategory-modal" data-parent-id="<?= $id ?>" data-parent-name="<?= H::escape($grupo['nome']) ?>">+ Subcategoria</button><?php endif; ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/categorias/grupos/'.$id.'/editar') ?>">Editar</a>
                <?php if (!$inactive || !(int)$grupo['ativo']): ?><form method="post" action="<?= H::escape($basePath.'/categorias/grupos/'.$id.($inactive ? '/reativar' : '/desativar')) ?>" <?= !$inactive ? 'data-confirm="Desativar esta categoria? Suas subcategorias ficarão indisponíveis para novos lançamentos."' : '' ?>><?= H::fields($csrfToken) ?><input type="hidden" name="origem" value="<?= $origin ?>"><button class="btn btn-sm <?= $inactive ? 'btn-uai-primary' : 'btn-outline-secondary' ?>"><?= $inactive ? 'Reativar' : 'Desativar' ?></button></form><?php endif; ?>
            </div></td>
        </tr></tbody>
        <tbody id="category-children-<?= $id ?>" class="category-children" <?= !$expanded ? 'hidden' : '' ?>>
            <?php if (!$subs): ?><tr class="category-empty-row"><td colspan="5" class="text-secondary">Nenhuma subcategoria nesta categoria.</td></tr><?php endif; ?>
            <?php foreach ($subs as $sub): $subId=(int)$sub['id']; ?>
            <tr class="category-child-row">
                <td data-label="Subcategoria"><div class="category-child-name"><span aria-hidden="true">↳</span><div><strong><?= H::escape($sub['nome']) ?></strong><?php if ($sub['descricao']): ?><small class="d-block text-secondary"><?= H::escape($sub['descricao']) ?></small><?php endif; ?><?php if ($inactive): ?><small class="d-block text-secondary"><?= !(int)$sub['grupo_ativo'] ? 'Categoria desativada' : 'Subcategoria desativada' ?></small><?php endif; ?></div></div></td>
                <td data-label="Subcategorias"><span class="text-secondary">—</span></td>
                <td data-label="Tipo"><span class="text-<?= $color ?>"><?= $sub['tipo']==='receita' ? 'Receita' : 'Despesa' ?></span></td>
                <td data-label="Essencial"><span class="text-secondary">—</span></td>
                <td data-label="Ações"><div class="category-row-actions"><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/categorias/subgrupos/'.$subId.'/editar') ?>">Editar</a>
                    <?php if ($inactive && !(int)$sub['grupo_ativo']): ?><small class="text-secondary">Reative a categoria primeiro.</small>
                    <?php else: ?><form method="post" action="<?= H::escape($basePath.'/categorias/subgrupos/'.$subId.($inactive ? '/reativar' : '/desativar')) ?>" <?= !$inactive ? 'data-confirm="Desativar esta subcategoria?"' : '' ?>><?= H::fields($csrfToken) ?><input type="hidden" name="origem" value="<?= $origin ?>"><button class="btn btn-sm <?= $inactive ? 'btn-uai-primary' : 'btn-outline-secondary' ?>"><?= $inactive ? 'Reativar' : 'Desativar' ?></button></form><?php endif; ?>
                </div></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <?php endforeach; ?>
        <?php if (!$grupos): ?><tbody><tr class="category-empty-row"><td colspan="5" class="empty-state"><?= $inactive ? 'Nenhuma categoria ou subcategoria desativada.' : 'Nenhuma categoria cadastrada. Clique em Nova categoria para começar.' ?></td></tr></tbody><?php endif; ?>
    </table></div>
    <?php if (!$inactive): ?><p class="small text-secondary mt-3 mb-0">O check de essencial é salvo automaticamente.</p><?php endif; ?>
</div></section>
<?php if (!$inactive): require __DIR__.'/create-forms.php'; endif; ?>
