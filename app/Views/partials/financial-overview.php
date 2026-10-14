<?php
use App\Core\Html as H;
use App\Core\Money;
$periodResult = (int) $totals['entradas'] - (int) $totals['saidas'];
$max = max([1, ...array_map(fn($row) => max((int) $row['entradas'], (int) $row['saidas']), $monthly)]);
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><section class="card metric-card h-100"><div class="card-body"><p>Movimentações no filtro</p><strong class="metric-value"><?= (int) $totals['quantidade'] ?></strong><small>Todos os resultados, em todas as páginas</small></div></section></div>
    <div class="col-sm-6 col-xl-3"><section class="card metric-card h-100"><div class="card-body"><p>Entradas no período</p><strong class="metric-value text-success"><?= Money::format((int) $totals['entradas']) ?></strong><small><?= isset($totals['receber']) ? 'A receber: ' . Money::format((int) $totals['receber']) : 'Respeita os filtros selecionados' ?></small></div></section></div>
    <div class="col-sm-6 col-xl-3"><section class="card metric-card h-100"><div class="card-body"><p>Saídas no período</p><strong class="metric-value text-danger"><?= Money::format((int) $totals['saidas']) ?></strong><small><?= isset($totals['pagar']) ? 'A pagar: ' . Money::format((int) $totals['pagar']) : 'Respeita os filtros selecionados' ?></small></div></section></div>
    <div class="col-sm-6 col-xl-3"><section class="card metric-card h-100"><div class="card-body"><p>Resultado do período</p><strong class="metric-value <?= $periodResult < 0 ? 'text-danger' : 'text-success' ?>"><?= Money::format($periodResult) ?></strong><small>Entradas menos saídas no filtro</small></div></section></div>
</div>
<section class="card mb-4"><div class="card-body"><h2 class="h5 mb-4">Evolução de entradas e saídas</h2>
    <?php if (isset($totals['receber'])): ?><p class="small text-secondary">Valores por competência, incluindo efetuados e pendentes. Cancelados não compõem os totais nem o gráfico.</p><?php endif; ?>
    <?php if (!$monthly): ?><p class="empty-state">Não há dados para o filtro selecionado.</p><?php else: ?><div class="share-chart" role="img" aria-label="Gráfico de entradas e saídas por mês">
    <?php foreach ($monthly as $row):
        $incomeHeight = (int) $row['entradas'] > 0 ? max(2, round(((int) $row['entradas'] / $max) * 100)) : 0;
        $expenseHeight = (int) $row['saidas'] > 0 ? max(2, round(((int) $row['saidas'] / $max) * 100)) : 0;
        $monthLabel = substr($row['mes'], 5, 2) . '/' . substr($row['mes'], 0, 4);
        $incomeTooltip = $monthLabel . ' · Entradas · ' . Money::format((int) $row['entradas']);
        $expenseTooltip = $monthLabel . ' · Saídas · ' . Money::format((int) $row['saidas']);
    ?><div class="share-chart-group"><div class="share-chart-bars"><span class="share-bar share-bar-income" tabindex="0" style="height:<?= $incomeHeight ?>%" data-tooltip="<?= H::escape($incomeTooltip) ?>" aria-label="<?= H::escape($incomeTooltip) ?>"></span><span class="share-bar share-bar-expense" tabindex="0" style="height:<?= $expenseHeight ?>%" data-tooltip="<?= H::escape($expenseTooltip) ?>" aria-label="<?= H::escape($expenseTooltip) ?>"></span></div><small><?= H::escape($monthLabel) ?></small></div><?php endforeach; ?>
    </div><div class="share-chart-legend"><span><i class="income"></i> Entradas</span><span><i class="expense"></i> Saídas</span></div><?php endif; ?>
</div></section>
