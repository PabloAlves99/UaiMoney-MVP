<?php

use App\Core\Html as H;
use App\Core\Money;

$spending = array_values(array_filter($categoryRows, fn($row) => (int)$row['despesas'] !== 0));
$scale = max([1, ...array_map(fn($row) => abs((int)$row['despesas']), $spending)]);
$budgetMap = array_column($budgets, null, 'grupo_id');
$previousMap = array_column($previousCategories, 'despesas', 'id');
$subcategories = [];
foreach ($subcategoryRows as $row) {
    if ((int)$row['despesas'] !== 0) $subcategories[$row['grupo_id']][] = $row;
}
$increase = null;
foreach ($spending as $row) {
    $change = (int)$row['despesas'] - (int)($previousMap[$row['id']] ?? 0);
    if ($change > ($increase['change'] ?? 0)) $increase = $row + ['change' => $change];
}
$positive = array_values(array_filter($spending, fn($row) => (int)$row['despesas'] > 0));
$positiveTotal = array_sum(array_column($positive, 'despesas'));
?>
<div class="row g-4">
    <div class="col-xl-8">
        <section class="card h-100" id="categorias-analise">
            <div class="card-body">
                <div class="section-heading">
                    <h2 class="h4">Onde estou gastando?</h2><span class="small text-secondary">Por categoria</span>
                </div>
                <p class="small text-secondary">Abra uma categoria para ver as subcategorias. Selecione um valor para conferir os lançamentos.</p>
                <?php if (!$spending): ?><p class="empty-state">Nenhuma despesa realizada nesta seleção.</p><?php endif; ?>
                <?php foreach ($spending as $row): $budget = $budgetMap[$row['id']] ?? null; ?>
                    <details class="analysis-category" <?= (string)($filters['grupo_id'] ?? '') === (string)$row['id'] ? 'open' : '' ?>>
                        <summary>
                            <span class="analysis-category-heading"><span><?= H::escape($row['nome']) ?></span><strong><?= Money::format((int)$row['despesas']) ?></strong></span>
                            <span class="analysis-track" aria-hidden="true"><span style="width:<?= round(abs((int)$row['despesas']) / $scale * 100, 2) ?>%"></span></span>
                            <span class="analysis-category-meta"><?= (int)$row['despesas'] < 0 ? 'Estornos maiores que os gastos' : ($positiveTotal > 0 ? number_format((int)$row['despesas'] / $positiveTotal * 100, 1, ',', '.') . '% das categorias com gasto positivo' : '') ?> <span aria-hidden="true">⌄</span></span>
                        </summary>
                        <div class="analysis-subcategories">
                            <a class="small" href="<?= H::escape($url(['grupo_id' => $row['id'], 'tipo' => 'despesa', 'conferir' => 1], '#lancamentos')) ?>">Conferir <?= H::escape($row['nome']) ?> →</a>
                            <?php foreach ($subcategories[$row['id']] ?? [] as $sub): ?>
                                <a class="analysis-subcategory" href="<?= H::escape($url(['grupo_id' => $row['id'], 'subgrupo_id' => $sub['id'], 'tipo' => 'despesa', 'conferir' => 1], '#lancamentos')) ?>">
                                    <span><?= H::escape($sub['nome']) ?></span><strong><?= Money::format((int)$sub['despesas']) ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </details>
                    <?php if ($budget): $remaining = (int)$budget['valor_limite_centavos'] - (int)$row['despesas']; ?>
                        <div class="analysis-budget-line"><span>Limite do mês: <?= Money::format((int)$budget['valor_limite_centavos']) ?></span><strong><?= $remaining < 0 ? 'Acima do limite: ' : 'Ainda disponível: ' ?><?= Money::format(abs($remaining)) ?></strong></div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div class="analysis-planning">
                    <p class="small text-secondary mb-2"><?= $budgetAvailable ? 'Os limites são mensais; o gasto considera apenas o período selecionado.' : 'Para comparar com os limites, selecione um mês a partir do dia 1, sem filtros de conta, cartão, subcategoria ou meio de pagamento.' ?></p>
                    <a href="<?= H::escape($basePath . '/planejamento?mes=' . $month) ?>"><?= $budgets ? 'Ajustar limites' : 'Definir limites por categoria' ?> →</a>
                </div>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="card h-100">
            <div class="card-body">
                <h2 class="h4">Vale olhar de perto</h2>
                <?php if ($increase): ?>
                    <div class="analysis-insight">
                        <p class="eyebrow">MAIOR AUMENTO</p><strong><?= H::escape($increase['nome']) ?></strong>
                        <p><?= Money::format($increase['change']) ?> a mais que no período anterior.</p>
                        <a href="<?= H::escape($url(['grupo_id' => $increase['id'], 'tipo' => 'despesa', 'conferir' => 1], '#lancamentos')) ?>">Revisar gastos →</a>
                    </div>
                <?php endif; ?>
                <?php if (count($positive) > 1): $largest = $positive[0]; ?>
                    <div class="analysis-insight">
                        <p class="eyebrow">MAIOR CONCENTRAÇÃO</p><strong><?= H::escape($largest['nome']) ?></strong>
                        <p><?= number_format((int)$largest['despesas'] / $positiveTotal * 100, 1, ',', '.') ?>% dos gastos nas categorias com saldo positivo.</p>
                    </div>
                <?php endif; ?>
                <?php if ((int)$totals['receitas'] > 0): ?>
                    <div class="analysis-insight">
                        <p class="eyebrow">DA RECEITA PARA OS GASTOS</p><strong><?= number_format((int)$totals['despesas'] / (int)$totals['receitas'] * 100, 1, ',', '.') ?>%</strong>
                        <p><?= (int)$totals['despesas'] > (int)$totals['receitas'] ? 'Os gastos passaram do que entrou neste período.' : 'Da receita realizada foi usada para cobrir as despesas.' ?></p>
                    </div>
                <?php endif; ?>
                <div class="analysis-insight">
                    <p class="eyebrow">GASTO MÉDIO POR DIA</p><strong><?= Money::format((int)round((int)$totals['despesas'] / $days)) ?></strong>
                    <p>Considera os <?= $days ?> dias do período, incluindo os dias sem gastos.</p>
                </div>
                <?php if ((int)$totals['pagar'] > 0): ?>
                    <div class="analysis-insight">
                        <p class="eyebrow">AINDA A PAGAR</p><strong><?= Money::format((int)$totals['pagar']) ?></strong>
                        <p>Pendências por competência no período. Não entram nos valores realizados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>