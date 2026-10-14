<?php
use App\Core\Html as H;
use App\Core\Money;
$query = array_filter($filters, fn($value) => $value !== '');
?>
<div class="public-share-title mb-4"><p class="eyebrow">EXTRATO COMPARTILHADO</p><h1><?= H::escape($share['conta_nome']) ?></h1><p class="text-secondary mb-0">Visualização somente de leitura · atualizado em <?= date('d/m/Y H:i') ?></p></div>
<section class="card mb-4"><div class="card-body">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-6"><label class="form-label" for="q">Buscar descrição</label><input class="form-control" id="q" name="q" value="<?= H::escape($filters['q']) ?>" maxlength="100"></div>
        <div class="col-6 col-md-3"><label class="form-label" for="inicio">De</label><input class="form-control" id="inicio" name="inicio" type="date" value="<?= H::escape($filters['inicio']) ?>"></div>
        <div class="col-6 col-md-3"><label class="form-label" for="fim">Até</label><input class="form-control" id="fim" name="fim" type="date" value="<?= H::escape($filters['fim']) ?>"></div>
        <div class="col-sm-6 col-lg-3"><label class="form-label" for="tipo">Tipo</label><select class="form-select" id="tipo" name="tipo"><option value="">Todos</option><option value="entrada" <?= $filters['tipo']==='entrada'?'selected':'' ?>>Entradas</option><option value="saida" <?= $filters['tipo']==='saida'?'selected':'' ?>>Saídas</option></select></div>
        <?php require dirname(__DIR__) . '/partials/category-filters.php'; ?>
        <div class="col-sm-6 col-lg-3 d-flex gap-2"><button class="btn btn-uai-primary flex-fill">Filtrar</button><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/compartilhado/' . $token . '?' . http_build_query(array_replace($query, ['inicio'=>date('Y-m-01'), 'fim'=>date('Y-m-t'), 'periodo'=>'mes']))) ?>">Mês atual</a></div>
    </form>
    <div class="filter-actions mt-3"><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/compartilhado/' . $token) ?>">Limpar</a><a class="small" href="<?= H::escape($basePath . '/compartilhado/' . $token . '?' . http_build_query(array_replace($query, ['inicio'=>'', 'fim'=>'', 'periodo'=>'tudo']))) ?>">Ver todo o período</a></div>
</div></section>
<?php require dirname(__DIR__) . '/partials/financial-overview.php'; ?>
<?php
$incomeChartNote = 'Entradas efetivadas na conta, incluindo estornos recebidos e entradas sem categoria.';
$incomeChartLink = fn($row, $dimension) => $row['id'] === null ? null : $basePath . '/compartilhado/' . $token . '?' . http_build_query(array_replace($query, ['grupo_id'=>$row['grupo_id'], 'subgrupo_id'=>$dimension === 'subgrupo' ? $row['id'] : '', 'tipo'=>'entrada']));
require dirname(__DIR__) . '/partials/income-charts.php';
?>
<section class="card"><div class="card-body"><div class="section-heading"><h2 class="h5 mb-0">Movimentações</h2><div class="detail-actions"><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/compartilhado/'.$token.'/exportar/pdf?'.http_build_query($query)) ?>">PDF</a><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/compartilhado/'.$token.'/exportar/excel?'.http_build_query($query)) ?>">Excel</a><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/compartilhado/'.$token.'/exportar/csv?'.http_build_query($query)) ?>">CSV</a></div></div>
    <?php if (!$entries): ?><p class="empty-state">Nenhuma movimentação encontrada.</p><?php else: ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Data</th><th>Descrição</th><th>Tipo</th><th class="text-end">Valor</th></tr></thead><tbody>
    <?php foreach (array_slice($entries,0,50) as $entry): $income=(int)$entry['valor_centavos']>0; ?><tr><td class="text-nowrap"><?= H::date($entry['data']) ?></td><td class="activity-description"><strong><?= H::escape($entry['descricao']) ?></strong><small><?= H::escape($entry['grupo_nome'] ? $entry['grupo_nome'] . ' · ' . $entry['subgrupo_nome'] : 'Sem categoria · Sem subcategoria') ?></small></td><td><span class="badge <?= $income?'text-bg-success':'text-bg-danger' ?>"><?= $income?'Entrada':'Saída' ?></span></td><td class="text-end text-nowrap fw-semibold <?= $income?'text-success':'text-danger' ?>"><?= Money::format(abs((int)$entry['valor_centavos'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
    <nav class="pagination-links"><?php if($page>1): ?><a href="?<?= H::escape(http_build_query($query+['pagina'=>$page-1])) ?>">← Anterior</a><?php else:?><span></span><?php endif; ?><span>Página <?= $page ?></span><?php if(count($entries)>50): ?><a href="?<?= H::escape(http_build_query($query+['pagina'=>$page+1])) ?>">Próxima →</a><?php endif; ?></nav>
</div></section>
<p class="small text-secondary text-center mt-4">Este extrato foi compartilhado pelo proprietário da conta no UaiMoney.</p>
