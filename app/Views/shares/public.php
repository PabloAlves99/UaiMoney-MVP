<?php
use App\Core\Html as H;
use App\Core\Money;
$query = array_filter($filters, fn($value) => $value !== '');
$max = max([1, ...array_map(fn($row) => max((int) $row['entradas'], (int) $row['saidas']), $monthly)]);
?>
<div class="public-share-title mb-4"><p class="eyebrow">EXTRATO COMPARTILHADO</p><h1><?= H::escape($share['conta_nome']) ?></h1><p class="text-secondary mb-0">Visualização somente de leitura · atualizado em <?= date('d/m/Y H:i') ?></p></div>
<section class="card mb-4"><div class="card-body">
    <form method="get" class="row g-3 align-items-end">
        <div class="col-md-4"><label class="form-label" for="q">Buscar descrição</label><input class="form-control" id="q" name="q" value="<?= H::escape($filters['q']) ?>" maxlength="100"></div>
        <div class="col-6 col-md-2"><label class="form-label" for="inicio">De</label><input class="form-control" id="inicio" name="inicio" type="date" value="<?= H::escape($filters['inicio']) ?>"></div>
        <div class="col-6 col-md-2"><label class="form-label" for="fim">Até</label><input class="form-control" id="fim" name="fim" type="date" value="<?= H::escape($filters['fim']) ?>"></div>
        <div class="col-md-2"><label class="form-label" for="tipo">Tipo</label><select class="form-select" id="tipo" name="tipo"><option value="">Todos</option><option value="entrada" <?= $filters['tipo']==='entrada'?'selected':'' ?>>Entradas</option><option value="saida" <?= $filters['tipo']==='saida'?'selected':'' ?>>Saídas</option></select></div>
        <div class="col-md-2 d-flex gap-2"><button class="btn btn-uai-primary flex-fill">Filtrar</button><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/compartilhado/' . $token) ?>">Limpar</a></div>
    </form>
</div></section>
<div class="row g-3 mb-4">
    <div class="col-md-4"><section class="card metric-card h-100"><div class="card-body"><p>Saldo disponível hoje</p><strong class="metric-value <?= $balance < 0 ? 'text-danger' : '' ?>"><?= Money::format($balance) ?></strong><small>Saldo atual da conta, independente do filtro</small></div></section></div>
    <div class="col-md-4"><section class="card metric-card h-100"><div class="card-body"><p>Entradas no período</p><strong class="metric-value text-success"><?= Money::format((int) $totals['entradas']) ?></strong><small>Respeita os filtros selecionados</small></div></section></div>
    <div class="col-md-4"><section class="card metric-card h-100"><div class="card-body"><p>Saídas no período</p><strong class="metric-value text-danger"><?= Money::format((int) $totals['saidas']) ?></strong><small>Respeita os filtros selecionados</small></div></section></div>
</div>
<section class="card mb-4"><div class="card-body"><h2 class="h5 mb-4">Evolução de entradas e saídas</h2>
    <?php if (!$monthly): ?><p class="empty-state">Não há dados para o filtro selecionado.</p><?php else: ?><div class="share-chart" role="img" aria-label="Gráfico de entradas e saídas por mês">
    <?php foreach ($monthly as $row):
        $incomeHeight=(int)$row['entradas']>0?max(2,round(((int)$row['entradas']/$max)*100)):0;
        $expenseHeight=(int)$row['saidas']>0?max(2,round(((int)$row['saidas']/$max)*100)):0;
        $monthLabel=substr($row['mes'],5,2).'/'.substr($row['mes'],0,4);
        $incomeTooltip=$monthLabel.' · Entradas · '.Money::format((int)$row['entradas']);
        $expenseTooltip=$monthLabel.' · Saídas · '.Money::format((int)$row['saidas']);
    ?><div class="share-chart-group"><div class="share-chart-bars"><span class="share-bar share-bar-income" tabindex="0" style="height:<?= $incomeHeight ?>%" data-tooltip="<?= H::escape($incomeTooltip) ?>" aria-label="<?= H::escape($incomeTooltip) ?>"></span><span class="share-bar share-bar-expense" tabindex="0" style="height:<?= $expenseHeight ?>%" data-tooltip="<?= H::escape($expenseTooltip) ?>" aria-label="<?= H::escape($expenseTooltip) ?>"></span></div><small><?= H::escape($monthLabel) ?></small></div><?php endforeach; ?>
    </div><div class="share-chart-legend"><span><i class="income"></i> Entradas</span><span><i class="expense"></i> Saídas</span></div><?php endif; ?>
</div></section>
<section class="card"><div class="card-body"><div class="section-heading"><h2 class="h5 mb-0">Movimentações</h2><div class="detail-actions"><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/compartilhado/'.$token.'/exportar/pdf?'.http_build_query($query)) ?>">PDF</a><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/compartilhado/'.$token.'/exportar/excel?'.http_build_query($query)) ?>">Excel</a><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/compartilhado/'.$token.'/exportar/csv?'.http_build_query($query)) ?>">CSV</a></div></div>
    <?php if (!$entries): ?><p class="empty-state">Nenhuma movimentação encontrada.</p><?php else: ?><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Data</th><th>Descrição</th><th>Tipo</th><th class="text-end">Valor</th></tr></thead><tbody>
    <?php foreach (array_slice($entries,0,50) as $entry): $income=(int)$entry['valor_centavos']>0; ?><tr><td class="text-nowrap"><?= H::date($entry['data']) ?></td><td><?= H::escape($entry['descricao']) ?></td><td><span class="badge <?= $income?'text-bg-success':'text-bg-danger' ?>"><?= $income?'Entrada':'Saída' ?></span></td><td class="text-end text-nowrap fw-semibold <?= $income?'text-success':'text-danger' ?>"><?= Money::format(abs((int)$entry['valor_centavos'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
    <nav class="pagination-links"><?php if($page>1): ?><a href="?<?= H::escape(http_build_query($query+['pagina'=>$page-1])) ?>">← Anterior</a><?php else:?><span></span><?php endif; ?><span>Página <?= $page ?></span><?php if(count($entries)>50): ?><a href="?<?= H::escape(http_build_query($query+['pagina'=>$page+1])) ?>">Próxima →</a><?php endif; ?></nav>
</div></section>
<p class="small text-secondary text-center mt-4">Este extrato foi compartilhado pelo proprietário da conta no UaiMoney.</p>
<script>
(()=>{
    const bars=[...document.querySelectorAll('.share-bar[data-tooltip]')];
    if(!bars.length)return;
    const tip=document.createElement('div');
    tip.id='share-chart-tooltip';tip.className='share-chart-tooltip';tip.setAttribute('role','tooltip');
    document.body.appendChild(tip);
    let active=null;
    const show=bar=>{
        active=bar;tip.textContent=bar.dataset.tooltip;tip.classList.add('visible');bar.setAttribute('aria-describedby',tip.id);
        const rect=bar.getBoundingClientRect(),box=tip.getBoundingClientRect(),gap=9;
        const left=Math.min(window.innerWidth-box.width-8,Math.max(8,rect.left+(rect.width-box.width)/2));
        const top=rect.top-box.height-gap>=8?rect.top-box.height-gap:Math.min(window.innerHeight-box.height-8,rect.bottom+gap);
        tip.style.left=`${left}px`;tip.style.top=`${top}px`;
    };
    const hide=bar=>{if(active!==bar)return;bar.removeAttribute('aria-describedby');active=null;tip.classList.remove('visible');};
    bars.forEach(bar=>{
        bar.addEventListener('mouseenter',()=>show(bar));bar.addEventListener('mouseleave',()=>hide(bar));
        bar.addEventListener('focus',()=>show(bar));bar.addEventListener('blur',()=>hide(bar));
        bar.addEventListener('click',event=>{event.stopPropagation();active===bar?hide(bar):show(bar);});
    });
    document.addEventListener('click',()=>active&&hide(active));
    window.addEventListener('resize',()=>active&&show(active));
    document.querySelector('.share-chart')?.addEventListener('scroll',()=>active&&show(active));
})();
</script>
