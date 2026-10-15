<?php
use App\Core\Html as H;
$pagePath = $inactive ? '/categorias/desativadas' : '/categorias';
$origin = $inactive ? 'desativadas' : 'ativas';
$activeGroups = array_values(array_filter($allGroups,fn($g) => (int)$g['ativo']===1));
?>
<div class="page-heading">
    <div><p class="eyebrow">ORGANIZAÇÃO</p><h1><?= $inactive ? 'Categorias desativadas' : 'Categorias' ?></h1><p class="text-secondary mb-0"><?= $inactive ? 'Edite e reative suas categorias e subcategorias.' : 'Organize suas receitas, despesas e subcategorias em um só lugar.' ?></p></div>
    <a class="btn btn-outline-secondary" href="<?= H::escape($basePath . ($inactive ? '/categorias' : '/categorias/desativadas')) ?>"><?= $inactive ? '← Categorias ativas' : 'Ver desativadas' ?></a>
</div>
<?php if ($error!==null): ?><div class="alert alert-danger" role="alert"><?= H::escape($error) ?></div><?php endif; ?>
<?php if (!$inactive): require __DIR__ . '/create-forms.php'; else: ?>
<p class="small text-secondary mb-4">Ao reativar uma categoria, suas subcategorias que não foram desativadas individualmente voltam a ficar disponíveis. Subcategorias desativadas individualmente precisam ser reativadas separadamente.</p>
<?php endif; ?>
<section class="card mb-4"><div class="card-body">
    <div class="section-heading"><h2 class="h5 mb-0"><?= $inactive ? 'Categorias desativadas' : 'Categorias ativas' ?></h2><span class="small text-secondary"><?= count($grupos) ?> categorias</span></div>
    <?php if (!$grupos): ?><p class="empty-state"><?= $inactive ? 'Nenhuma categoria desativada.' : 'Nenhuma categoria cadastrada. Use o formulário acima para começar.' ?></p><?php else: ?>
    <div class="table-responsive"><table class="table align-middle category-table mb-0"><thead><tr><th scope="col">Categoria</th><th scope="col"><?= $inactive ? 'Subcategorias' : 'Subcategorias ativas' ?></th><th scope="col">Tipo</th><th scope="col">Essencial</th><th scope="col">Ações</th></tr></thead><tbody>
        <?php foreach ($grupos as $grupo): $id=(int)$grupo['id']; ?>
        <tr>
            <td data-label="Categoria"><strong><?= H::escape($grupo['nome']) ?></strong></td>
            <td data-label="Subcategorias"><a href="<?= H::escape($basePath.$pagePath.'?grupo_id='.$id.'#subcategorias') ?>"><?= (int)$grupo[$inactive ? 'subcategorias_total' : 'subcategorias_ativas'] ?> subcategorias</a></td>
            <td data-label="Tipo"><span class="badge <?= $grupo['tipo']==='receita' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $grupo['tipo']==='receita' ? 'Receita' : 'Despesa' ?></span></td>
            <td data-label="Essencial">
                <?php if ($grupo['tipo']==='despesa'): ?>
                <form method="post" action="<?= H::escape($basePath.'/categorias/grupos/'.$id.'/classificacao') ?>" class="category-essential-form"><?= H::fields($csrfToken) ?><input type="hidden" name="origem" value="<?= $origin ?>"><input class="form-check-input m-0" type="checkbox" name="essencial" value="1" id="essential-<?= $id ?>" data-classification-toggle <?= $grupo['classificacao']==='essencial' ? 'checked' : '' ?>><label class="visually-hidden" for="essential-<?= $id ?>"><?= H::escape('Marcar '.$grupo['nome'].' como essencial') ?></label><noscript><button class="btn btn-sm btn-outline-secondary">Salvar</button></noscript></form>
                <?php else: ?><span class="text-secondary" aria-label="Não se aplica a receitas">—</span><?php endif; ?>
            </td>
            <td data-label="Ações"><div class="category-row-actions"><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/categorias/grupos/'.$id.'/editar') ?>">Editar</a><form method="post" action="<?= H::escape($basePath.'/categorias/grupos/'.$id.($inactive ? '/reativar' : '/desativar')) ?>" <?= !$inactive ? 'data-confirm="Desativar esta categoria? Suas subcategorias ficarão indisponíveis para novos lançamentos."' : '' ?>><?= H::fields($csrfToken) ?><input type="hidden" name="origem" value="<?= $origin ?>"><button class="btn btn-sm <?= $inactive ? 'btn-uai-primary' : 'btn-outline-secondary' ?>"><?= $inactive ? 'Reativar' : 'Desativar' ?></button></form></div></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
    <?php if (!$inactive): ?><p class="small text-secondary mt-3 mb-0">Marque o check para identificar uma despesa essencial. A alteração é salva automaticamente.</p><?php endif; ?>
</div></section>
<section class="card" id="subcategorias"><div class="card-body">
    <div class="section-heading flex-wrap gap-3"><div><h2 class="h5 mb-1"><?= $inactive ? 'Subcategorias desativadas ou indisponíveis' : 'Subcategorias ativas' ?></h2><span class="small text-secondary"><?= count($subgrupos) ?> subcategorias</span></div>
        <form method="get" action="<?= H::escape($basePath.$pagePath) ?>" class="d-flex gap-2 category-subgroup-filter"><label class="visually-hidden" for="group-filter">Filtrar subcategorias por categoria</label><select class="form-select form-select-sm" id="group-filter" name="grupo_id"><option value="">Todas as categorias</option><?php foreach ($allGroups as $g): ?><option value="<?= (int)$g['id'] ?>" <?= $selectedGroup===(string)$g['id'] ? 'selected' : '' ?>><?= H::escape($g['nome']) ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-outline-secondary">Filtrar</button></form>
    </div>
    <?php if (!$subgrupos): ?><p class="empty-state">Nenhuma subcategoria nesta seleção.</p><?php else: ?>
    <div class="table-responsive"><table class="table align-middle category-table mb-0"><thead><tr><th scope="col">Subcategoria</th><th scope="col">Categoria</th><th scope="col">Tipo</th><?php if ($inactive): ?><th scope="col">Situação</th><?php endif; ?><th scope="col">Ações</th></tr></thead><tbody>
        <?php foreach ($subgrupos as $sub): $id=(int)$sub['id']; ?>
        <tr>
            <td data-label="Subcategoria"><strong><?= H::escape($sub['nome']) ?></strong><?php if ($sub['descricao']): ?><small class="d-block text-secondary"><?= H::escape($sub['descricao']) ?></small><?php endif; ?></td>
            <td data-label="Categoria"><?= H::escape($sub['grupo_nome']) ?></td>
            <td data-label="Tipo"><?= $sub['tipo']==='receita' ? 'Receita' : 'Despesa' ?></td>
            <?php if ($inactive): ?><td data-label="Situação"><span class="small text-secondary"><?= !(int)$sub['grupo_ativo'] ? 'Categoria desativada' : 'Subcategoria desativada' ?></span></td><?php endif; ?>
            <td data-label="Ações"><div class="category-row-actions"><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/categorias/subgrupos/'.$id.'/editar') ?>">Editar</a>
                <?php if ($inactive && !(int)$sub['grupo_ativo']): ?><form method="post" action="<?= H::escape($basePath.'/categorias/grupos/'.(int)$sub['grupo_id'].'/reativar') ?>"><?= H::fields($csrfToken) ?><input type="hidden" name="origem" value="desativadas"><button class="btn btn-sm btn-uai-primary">Reativar categoria</button></form>
                <?php else: ?><form method="post" action="<?= H::escape($basePath.'/categorias/subgrupos/'.$id.($inactive ? '/reativar' : '/desativar')) ?>" <?= !$inactive ? 'data-confirm="Desativar esta subcategoria?"' : '' ?>><?= H::fields($csrfToken) ?><input type="hidden" name="origem" value="<?= $origin ?>"><button class="btn btn-sm <?= $inactive ? 'btn-uai-primary' : 'btn-outline-secondary' ?>"><?= $inactive ? 'Reativar' : 'Desativar' ?></button></form><?php endif; ?>
            </div></td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</div></section>
