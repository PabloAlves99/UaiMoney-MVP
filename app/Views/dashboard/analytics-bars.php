<?php

use App\Core\Html as H;
use App\Core\Money;

$spendingGroups = array_values(array_filter($categoryRows, fn(array $row): bool => (int) $row['despesas'] > 0));
$spendingTotal = array_sum(array_map(fn(array $row): int => (int) $row['despesas'], $spendingGroups));
$largestSpending = max([1, ...array_map(fn(array $row): int => (int) $row['despesas'], $spendingGroups)]);
?>
<section class="card mb-4" id="gastos-por-grupo">
    <div class="card-body">
        <div class="section-heading">
            <div><h2 class="h4">Gastos por grupo</h2><p class="small text-secondary mb-0">Quanto maior a barra, maior é a participação no total gasto.</p></div>
            <span class="small text-secondary">Clique para detalhar</span>
        </div>
        <?php if (!$spendingGroups): ?>
            <p class="empty-state">Nenhuma despesa realizada nesta seleção.</p>
        <?php else: ?>
            <div class="spending-bar-chart" role="list" aria-label="Gastos por grupo">
                <?php foreach ($spendingGroups as $row): $amount = (int) $row['despesas']; $percent = $spendingTotal > 0 ? $amount / $spendingTotal * 100 : 0; ?>
                    <a class="spending-bar-row" role="listitem" href="<?= H::escape($url(['grupo_id' => $row['id'], 'tipo' => 'despesa', 'conferir' => 1], '#lancamentos')) ?>" aria-label="<?= H::escape($row['nome']) ?>: <?= H::escape(Money::format($amount)) ?>, <?= number_format($percent, 1, ',', '.') ?>% dos gastos. Conferir lançamentos.">
                        <span class="spending-bar-name"><?= H::escape($row['nome']) ?></span>
                        <span class="spending-bar-track" aria-hidden="true"><span style="width: <?= round($amount / $largestSpending * 100, 2) ?>%"></span></span>
                        <span class="spending-bar-value"><strong><?= Money::format($amount) ?></strong><small><?= number_format($percent, 1, ',', '.') ?>%</small></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <p class="small text-secondary mb-0 mt-3">Total de despesas no período: <?= Money::format($spendingTotal) ?>. Transferências e pagamentos de fatura não são contados novamente.</p>
        <?php endif; ?>
    </div>
</section>
