<?php
use App\Core\Html as H;
use App\Core\Money;

$positiveGroups = array_values(array_filter($flowGroups, fn($row) => (int)$row['valor_centavos'] > 0));
$negativeGroups = array_values(array_filter($flowGroups, fn($row) => (int)$row['valor_centavos'] < 0));
$subgroupValues = array_values(array_filter($flowSubgroups, fn($row) => (int)$row['valor_centavos'] !== 0));
$pieTotal = array_sum(array_column($positiveGroups, 'valor_centavos'));
$netTotal = array_sum(array_column($flowGroups, 'valor_centavos'));
$barMax = max([1, ...array_map(fn($row) => abs((int)$row['valor_centavos']), $subgroupValues)]);
$colors = $flow === 'receita'
    ? ['#4285b5', '#3eb9ae', '#8c83d5', '#d5ab60', '#6796c7', '#ce829e', '#7ba697', '#9ba8bd']
    : ['#d76a32', '#c94e73', '#d39b20', '#8957bd', '#db8655', '#ae5870', '#b49a4c', '#8c728d'];
$typeCategories = array_values(array_filter($categories, fn($row) => $row['tipo'] === $flow));
$typeGroups = [];
foreach ($typeCategories as $category) $typeGroups[$category['grupo_id']] = $category['grupo_nome'];
$groupKey = $flow . '_grupos';
$subgroupKey = $flow . '_subgrupos';
?>
<div class="row g-4 mb-4 analysis-flow-breakdown" id="<?= $flow === 'receita' ? 'recebimentos' : 'categorias-analise' ?>">
    <div class="col-12">
        <details class="card analysis-selection" <?= $selections['active'][$groupKey] || $selections['active'][$subgroupKey] ? 'open' : '' ?>>
            <summary class="card-body">Escolher grupos e subgrupos de <?= strtolower($flowName) ?></summary>
            <form method="get" class="card-body pt-0">
                <?php foreach ($query as $key => $value): if (str_starts_with($key, $flow . '_')) continue; if (is_array($value)): foreach ($value as $id): ?>
                    <input type="hidden" name="<?= H::escape($key) ?>[]" value="<?= H::escape((string)$id) ?>">
                <?php endforeach; else: ?><input type="hidden" name="<?= H::escape($key) ?>" value="<?= H::escape((string)$value) ?>"><?php endif; endforeach; ?>
                <input type="hidden" name="<?= $groupKey ?>_ativo" value="1">
                <input type="hidden" name="<?= $subgroupKey ?>_ativo" value="1">
                <div class="row g-3">
                    <fieldset class="col-lg-4 analysis-check-list"><legend class="h6">Grupos</legend>
                        <?php foreach ($typeGroups as $id => $name): ?>
                            <label><input type="checkbox" name="<?= $groupKey ?>[]" value="<?= (int)$id ?>" <?= !$selections['active'][$groupKey] || in_array((int)$id, $selections['values'][$groupKey], true) ? 'checked' : '' ?>> <?= H::escape($name) ?></label>
                        <?php endforeach; ?>
                    </fieldset>
                    <fieldset class="col-lg-8 analysis-check-list"><legend class="h6">Subgrupos</legend>
                        <?php foreach ($typeCategories as $category): ?>
                            <label><input type="checkbox" name="<?= $subgroupKey ?>[]" value="<?= (int)$category['id'] ?>" <?= !$selections['active'][$subgroupKey] || in_array((int)$category['id'], $selections['values'][$subgroupKey], true) ? 'checked' : '' ?>> <?= H::escape($category['grupo_nome'] . ' · ' . $category['nome']) ?></label>
                        <?php endforeach; ?>
                    </fieldset>
                </div>
                <button class="btn btn-uai-primary mt-3">Aplicar seleção</button>
            </form>
        </details>
    </div>
    <div class="col-xl-6"><section class="card h-100" id="<?= $flow === 'receita' ? 'recebimentos-por-grupo' : 'despesas-por-grupo' ?>"><div class="card-body">
        <h2 class="h5"><?= $flowName ?> por grupo</h2>
        <p class="small text-secondary">Valores realizados por competência, líquidos de estornos. Clique para conferir os lançamentos.</p>
        <?php if (!$positiveGroups): ?><p class="empty-state">Nenhum valor positivo nesta seleção.</p><?php else: ?>
        <div class="income-pie-layout income-interactive">
            <?php $donutOffset = 0; ?>
            <div class="income-donut">
                <svg viewBox="0 0 240 240" role="group" aria-label="<?= $flowName ?> por grupo. Selecione uma fatia para conferir lançamentos.">
                    <circle class="income-donut-track" cx="120" cy="120" r="94" fill="none" stroke-width="28" />
                    <?php foreach ($positiveGroups as $index => $row): $share = (int)$row['valor_centavos'] / $pieTotal * 100; $gap = count($positiveGroups) > 1 ? min(.9, $share * .15) : 0; $tooltip = $row['nome'] . ': ' . Money::format((int)$row['valor_centavos']) . ' · ' . number_format($share, 1, ',', '.') . '%'; ?>
                        <a href="<?= H::escape($flowLink($row, 'grupo')) ?>" class="income-donut-link" aria-label="<?= H::escape($tooltip . '. Conferir lançamentos') ?>">
                            <title><?= H::escape($tooltip) ?></title>
                            <circle class="income-donut-segment" cx="120" cy="120" r="94" fill="none" stroke="<?= $colors[$index % count($colors)] ?>" stroke-width="28" pathLength="100" stroke-dasharray="<?= $share - $gap ?> <?= 100 - $share + $gap ?>" stroke-dashoffset="<?= -$donutOffset - $gap / 2 ?>" transform="rotate(-90 120 120)" />
                        </a>
                    <?php $donutOffset += $share; endforeach; ?>
                </svg>
                <div class="income-donut-center"><small><?= $flowName ?></small><strong><?= Money::format((int)$pieTotal) ?></strong><span><?= count($positiveGroups) ?> <?= count($positiveGroups) === 1 ? 'grupo' : 'grupos' ?></span></div>
            </div>
            <ul class="income-pie-legend">
                <?php foreach ($positiveGroups as $index => $row): $percent = (int)$row['valor_centavos'] / $pieTotal * 100; ?>
                    <li><span class="income-pie-swatch" style="background:<?= $colors[$index % count($colors)] ?>" aria-hidden="true"></span><div><a href="<?= H::escape($flowLink($row, 'grupo')) ?>"><?= H::escape($row['nome']) ?></a><small><?= Money::format((int)$row['valor_centavos']) ?> · <?= number_format($percent, 1, ',', '.') ?>%</small></div></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php if ($negativeGroups): ?><p class="small text-secondary mt-3">A pizza representa apenas grupos com saldo positivo. Ajustes líquidos negativos:</p><ul class="small"><?php foreach ($negativeGroups as $row): ?><li><?= H::escape($row['nome']) ?>: <span class="text-danger"><?= Money::format((int)$row['valor_centavos']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
        <p class="small text-secondary mt-3 mb-0">Total no filtro: <strong><?= Money::format((int)$netTotal) ?></strong></p>
    </div></section></div>
    <div class="col-xl-6"><section class="card h-100" id="<?= $flow === 'receita' ? 'recebimentos-por-subgrupo' : 'despesas-por-subgrupo' ?>"><div class="card-body">
        <h2 class="h5"><?= $flowName ?> por subgrupo</h2>
        <p class="small text-secondary">Valores por subcategoria, respeitando os mesmos filtros.</p>
        <?php if (!$subgroupValues): ?><p class="empty-state">Nenhum valor nesta seleção.</p><?php else: ?>
        <div class="spending-bar-chart income-subgroup-chart <?= $flow === 'despesa' ? 'expense-subgroup-chart' : '' ?>" role="list" aria-label="Barras de <?= strtolower($flowName) ?> por subgrupo">
            <?php foreach ($subgroupValues as $row): $amount = (int)$row['valor_centavos']; ?>
                <a class="spending-bar-row" role="listitem" href="<?= H::escape($flowLink($row, 'subgrupo')) ?>">
                    <span class="spending-bar-name"><?= H::escape($row['nome']) ?></span>
                    <span class="spending-bar-track" aria-hidden="true"><span class="<?= $amount < 0 ? 'income-bar-negative' : '' ?>" style="width:<?= round(abs($amount) / $barMax * 100, 2) ?>%"></span></span>
                    <span class="spending-bar-value <?= $amount < 0 ? 'text-danger' : ($flow === 'receita' ? 'text-success' : '') ?>"><strong><?= Money::format($amount) ?></strong></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div></section></div>
</div>
