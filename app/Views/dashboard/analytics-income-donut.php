<?php
use App\Core\Html as H;
use App\Core\Money;
$donutOffset = 0;
?>
<div class="income-donut">
    <svg viewBox="0 0 240 240" role="group" aria-label="Recebimentos por grupo. Selecione uma fatia para filtrar a análise.">
        <circle class="income-donut-track" cx="120" cy="120" r="94" fill="none" stroke-width="28" />
        <?php foreach ($positiveGroups as $index => $row):
            $share = (int) $row['valor_centavos'] / $pieTotal * 100;
            $gap = count($positiveGroups) > 1 ? min(.9, $share * .15) : 0;
            $href = $incomeChartLink($row, 'grupo');
            $tooltip = $row['nome'] . ': ' . Money::format((int) $row['valor_centavos']) . ' · ' . number_format($share, 1, ',', '.') . '%';
        ?>
        <a href="<?= H::escape($href) ?>" class="income-donut-link" aria-label="<?= H::escape($tooltip . '. Filtrar análise') ?>">
            <title><?= H::escape($tooltip) ?></title>
            <circle class="income-donut-segment" cx="120" cy="120" r="94" fill="none" stroke="<?= $colors[$index % count($colors)] ?>" stroke-width="28" pathLength="100" stroke-dasharray="<?= $share - $gap ?> <?= 100 - $share + $gap ?>" stroke-dashoffset="<?= -$donutOffset - $gap / 2 ?>" transform="rotate(-90 120 120)" />
        </a>
        <?php $donutOffset += $share; endforeach; ?>
    </svg>
    <div class="income-donut-center"><small>Recebimentos</small><strong><?= Money::format((int) $pieTotal) ?></strong><span><?= count($positiveGroups) ?> <?= count($positiveGroups) === 1 ? 'grupo' : 'grupos' ?></span></div>
</div>
