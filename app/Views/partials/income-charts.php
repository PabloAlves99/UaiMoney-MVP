<?php
use App\Core\Html as H;
use App\Core\Money;

$positiveGroups = array_values(array_filter($incomeGroups, fn($row) => (int) $row['valor_centavos'] > 0));
$negativeGroups = array_values(array_filter($incomeGroups, fn($row) => (int) $row['valor_centavos'] < 0));
$subgroupValues = array_values(array_filter($incomeSubgroups, fn($row) => (int) $row['valor_centavos'] !== 0));
$pieTotal = array_sum(array_column($positiveGroups, 'valor_centavos'));
$incomeNetTotal = array_sum(array_column($incomeGroups, 'valor_centavos'));
$barMax = max([1, ...array_map(fn($row) => abs((int) $row['valor_centavos']), $subgroupValues)]);
$colors = ['#198754', '#2463b5', '#d39b20', '#8957bd', '#d76a32', '#1696a3', '#c94e73', '#657b38'];
if (!empty($incomeInteractiveDonut)) $colors = ['#4285b5', '#3eb9ae', '#8c83d5', '#d5ab60', '#6796c7', '#ce829e', '#7ba697', '#9ba8bd'];
$stops = [];
$angle = 0;
foreach ($positiveGroups as $index => $row) {
    $nextAngle = $angle + (int) $row['valor_centavos'] / $pieTotal * 360;
    $stops[] = $colors[$index % count($colors)] . ' ' . round($angle, 6) . 'deg ' . round($nextAngle, 6) . 'deg';
    $angle = $nextAngle;
}
?>
<div class="row g-4 mb-4 income-breakdown">
    <div class="col-xl-6"><section class="card h-100" id="recebimentos-por-grupo"><div class="card-body">
        <h2 class="h5">Recebimentos por grupo</h2>
        <p class="small text-secondary"><?= H::escape($incomeChartNote) ?></p>
        <?php if (!$positiveGroups): ?><p class="empty-state">Nenhum recebimento positivo nesta seleção.</p><?php else: ?>
        <div class="income-pie-layout<?= !empty($incomeInteractiveDonut) ? ' income-interactive' : '' ?>">
            <?php if (!empty($incomeInteractiveDonut)): require dirname(__DIR__) . '/dashboard/analytics-income-donut.php'; else: ?>
            <div class="income-pie" role="img" aria-label="Pizza de recebimentos por grupo. Valores e percentuais na legenda." style="background:conic-gradient(<?= H::escape(implode(',', $stops)) ?>)"></div>
            <?php endif; ?>
            <ul class="income-pie-legend">
                <?php foreach ($positiveGroups as $index => $row): $percent = (int) $row['valor_centavos'] / $pieTotal * 100; $href = $incomeChartLink($row, 'grupo'); ?>
                <li><span class="income-pie-swatch" style="background:<?= $colors[$index % count($colors)] ?>" aria-hidden="true"></span><div><?php if ($href): ?><a href="<?= H::escape($href) ?>"><?= H::escape($row['nome']) ?></a><?php else: ?><span><?= H::escape($row['nome']) ?></span><?php endif; ?><small><?= Money::format((int) $row['valor_centavos']) ?> · <?= number_format($percent, 1, ',', '.') ?>%</small></div></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php if ($negativeGroups): ?><p class="small text-secondary mt-3">A pizza representa apenas grupos com saldo positivo. Ajustes líquidos negativos:</p><ul class="small"><?php foreach ($negativeGroups as $row): ?><li><?= H::escape($row['nome']) ?>: <span class="text-danger"><?= Money::format((int) $row['valor_centavos']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
        <p class="small text-secondary mt-3 mb-0">Total de recebimentos no filtro: <strong><?= Money::format((int) $incomeNetTotal) ?></strong></p>
    </div></section></div>
    <div class="col-xl-6"><section class="card h-100" id="recebimentos-por-subgrupo"><div class="card-body">
        <h2 class="h5">Recebimentos por subgrupo</h2>
        <p class="small text-secondary">Valores por subcategoria, respeitando os mesmos filtros.</p>
        <?php if (!$subgroupValues): ?><p class="empty-state">Nenhum recebimento nesta seleção.</p><?php else: ?>
        <div class="spending-bar-chart income-subgroup-chart" role="list" aria-label="Barras de recebimentos por subgrupo">
            <?php foreach ($subgroupValues as $row): $amount = (int) $row['valor_centavos']; $href = $incomeChartLink($row, 'subgrupo'); ?>
            <<?= $href ? 'a' : 'div' ?> class="spending-bar-row" role="listitem" <?= $href ? 'href="' . H::escape($href) . '"' : '' ?>>
                <span class="spending-bar-name"><?= H::escape($row['nome']) ?></span>
                <span class="spending-bar-track" aria-hidden="true"><span class="<?= $amount < 0 ? 'income-bar-negative' : '' ?>" style="width:<?= round(abs($amount) / $barMax * 100, 2) ?>%"></span></span>
                <span class="spending-bar-value <?= $amount < 0 ? 'text-danger' : 'text-success' ?>"><strong><?= Money::format($amount) ?></strong></span>
            </<?= $href ? 'a' : 'div' ?>>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div></section></div>
</div>
