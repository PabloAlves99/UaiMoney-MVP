<?php

use App\Core\FinancialDate as Dates;
use App\Core\Html as H;
use App\Core\Money;

$values = [0, 100];
foreach ($monthlyRows as $row) {
    $values[] = (int)$row['receitas'];
    $values[] = (int)$row['despesas'];
}
$low = min($values);
$high = max($values);
$range = max(1, $high - $low);
$count = count($monthlyRows);
$series = ['receitas' => ['income', 'Receitas', 'receita'], 'despesas' => ['expense', 'Despesas', 'despesa']];
if (!empty($filters['tipo'])) $series = array_filter($series, fn($item) => $item[2] === $filters['tipo']);
$x = fn($index) => 70 + ($index + .5) * 670 / max(1, $count);
$y = fn($value) => 265 - (($value - $low) / $range * 225);
$monthUrl = fn($row, $type = null) => $url([
    'inicio' => max($seriesStart, $row['nome'] . '-01'),
    'fim' => min($end, Dates::day($row['nome'], 31)),
    'tipo' => $type ?? ($filters['tipo'] ?? null),
    'conferir' => 1,
], '#lancamentos');
?>
<div class="analysis-legend"><?php foreach ($series as [$color, $label]): ?><span class="<?= $color ?>"><?= $label ?></span><?php endforeach; ?></div>
<div class="analysis-line-chart" data-analysis-chart>
    <svg class="analysis-plot" viewBox="0 0 760 310" role="group" aria-label="Evolução mensal de receitas e despesas" data-chart-count="<?= $count ?>">
        <?php for ($tick = 0; $tick <= 4; $tick++): $value = $low + $range * $tick / 4;
            $py = $y($value); ?>
            <line class="plot-grid" x1="65" y1="<?= $py ?>" x2="745" y2="<?= $py ?>" />
            <text class="plot-label" x="55" y="<?= $py + 4 ?>" text-anchor="end"><?= number_format($value / 100, abs($value) < 1000 && $value != 0 ? 2 : 0, ',', '.') ?></text>
        <?php endfor; ?>
        <text class="plot-label" x="55" y="20" text-anchor="end">R$</text>
        <line class="plot-zero" x1="65" y1="<?= $y(0) ?>" x2="745" y2="<?= $y(0) ?>" />
        <?php foreach ($series as $key => [$color, $label, $type]): ?>
            <polyline class="plot-line <?= $color ?>" points="<?= implode(' ', array_map(fn($index) => $x($index) . ',' . $y((int)$monthlyRows[$index][$key]), array_keys($monthlyRows))) ?>" />
            <?php foreach ($monthlyRows as $index => $row): $tip = substr($row['nome'], 5, 2) . '/' . substr($row['nome'], 0, 4) . ' · ' . $label . ': ' . Money::format((int)$row[$key]); ?>
                <a href="<?= H::escape($monthUrl($row, $type)) ?>" class="plot-link <?= $color ?>" data-chart-tip="<?= H::escape($tip) ?>" aria-label="<?= H::escape($tip . '. Conferir lançamentos') ?>">
                    <title><?= H::escape($tip) ?></title>
                    <circle class="plot-hit" cx="<?= $x($index) ?>" cy="<?= $y((int)$row[$key]) ?>" r="13" data-chart-index="<?= $index ?>" />
                    <circle class="plot-point <?= $color ?>" cx="<?= $x($index) ?>" cy="<?= $y((int)$row[$key]) ?>" r="4" data-chart-index="<?= $index ?>" />
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php foreach ($monthlyRows as $index => $row): ?>
            <text class="plot-label plot-month" x="<?= $x($index) ?>" y="295" text-anchor="middle" data-chart-index="<?= $index ?>"><?= H::escape(substr($row['nome'], 5, 2) . '/' . substr($row['nome'], 2, 2)) ?></text>
        <?php endforeach; ?>
    </svg>
    <p class="analysis-chart-readout" aria-live="polite">Selecione um ponto para conferir os lançamentos do mês.</p>
</div>
<details class="analysis-data">
    <summary>Ver valores por mês</summary>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Mês</th>
                    <th scope="col">Receitas</th>
                    <th scope="col">Despesas</th>
                    <th scope="col">Resultado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlyRows as $row): ?>
                    <tr>
                        <th scope="row"><a href="<?= H::escape($monthUrl($row)) ?>"><?= H::escape(substr($row['nome'], 5, 2) . '/' . substr($row['nome'], 0, 4)) ?></a></th>
                        <td><?= Money::format((int)$row['receitas']) ?></td>
                        <td><?= Money::format((int)$row['despesas']) ?></td>
                        <td><?= Money::format((int)$row['receitas'] - (int)$row['despesas']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</details>