<?php

use App\Core\Html as H;
use App\Core\Money;

$query = array_merge($filters, ['periodo' => 'personalizado', 'inicio' => $start, 'fim' => $end]);
$url = fn(array $changes = [], string $anchor = '') => $basePath . '/analises?' . http_build_query(array_filter(array_replace($query, $changes), fn($value) => $value !== null && $value !== '')) . $anchor;
$groups = [];
foreach ($categories as $category) $groups[$category['grupo_id']] = $category['grupo_nome'];
$filterOptions = [
    'conta_id' => ['Conta', array_column($accounts, 'nome', 'id')],
    'cartao_id' => ['Cartão', array_column($cards, 'nome', 'id')],
    'grupo_id' => ['Categoria', $groups],
    'subgrupo_id' => ['Subcategoria', array_column(array_map(fn($c) => ['id' => $c['id'], 'nome' => $c['grupo_nome'] . ' · ' . $c['nome']], $categories), 'nome', 'id')],
    'tipo' => ['Tipo', ['receita' => 'Receitas', 'despesa' => 'Despesas']],
    'meio_pagamento' => ['Meio de pagamento', ['pix' => 'Pix', 'dinheiro' => 'Dinheiro', 'debito' => 'Débito', 'credito' => 'Crédito', 'boleto' => 'Boleto', 'transferencia' => 'Transferência', 'outro' => 'Outro']],
];
$totals['resultado'] = (int)$totals['receitas'] - (int)$totals['despesas'];
$previous['resultado'] = (int)$previous['receitas'] - (int)$previous['despesas'];
?>
<div class="page-heading">
    <div>
        <p class="eyebrow">SEU DINHEIRO, COM CLAREZA</p>
        <h1>Análises</h1>
        <p class="text-secondary">Entenda seus gastos e veja onde pode melhorar.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= H::escape($url(['conferir' => 1], '#lancamentos')) ?>">Conferir lançamentos</a>
</div>
<form method="get" class="card mb-4 analysis-filters">
    <div class="card-body">
        <div class="analysis-toolbar">
            <div class="analysis-presets" aria-label="Período da análise">
                <?php foreach (['mes' => 'Este mês', 'anterior' => 'Mês passado', 'semestre' => 'Últimos 6 meses'] as $key => $name): ?>
                    <a class="<?= $preset === $key ? 'active' : '' ?>" <?= $preset === $key ? 'aria-current="true"' : '' ?> href="<?= H::escape($url(['periodo' => $key, 'inicio' => null, 'fim' => null])) ?>"><?= $name ?></a>
                <?php endforeach; ?>
            </div>
            <span class="small text-secondary"><?= H::date($start) ?> a <?= H::date($end) ?><?= $partial ? ' · Mês parcial' : '' ?></span>
        </div>
        <input type="hidden" name="periodo" value="<?= H::escape($preset) ?>" data-analysis-period>
        <div class="analysis-filter-panels">
            <details <?= $preset === 'personalizado' ? 'open' : '' ?>>
                <summary>Personalizar período</summary>
                <div class="row g-3 mt-1">
                    <div class="col-sm-6"><label for="analysis-start" class="form-label">Início</label><input id="analysis-start" type="date" name="inicio" class="form-control" value="<?= H::escape($start) ?>" required data-analysis-date></div>
                    <div class="col-sm-6"><label for="analysis-end" class="form-label">Fim</label><input id="analysis-end" type="date" name="fim" class="form-control" value="<?= H::escape($end) ?>" required data-analysis-date></div>
                </div>
            </details>
            <details>
                <summary>Mais filtros<?= $filters ? ' (' . count($filters) . ')' : '' ?></summary>
                <div class="row g-3 mt-1">
                    <?php foreach ($filterOptions as $key => [$label, $options]): ?>
                        <div class="col-md-6">
                            <label for="analysis-<?= $key ?>" class="form-label"><?= $label ?></label>
                            <select id="analysis-<?= $key ?>" name="<?= $key ?>" class="form-select">
                                <option value="">Todos</option>
                                <?php if (!empty($filters[$key]) && !isset($options[$filters[$key]])): ?><option value="<?= H::escape($filters[$key]) ?>" selected>Filtro selecionado</option><?php endif; ?>
                                <?php foreach ($options as $id => $name): ?><option value="<?= H::escape($id) ?>" <?= (string)($filters[$key] ?? '') === (string)$id ? 'selected' : '' ?>><?= H::escape($name) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            </details>
        </div>
        <div class="analysis-filter-footer">
            <div class="analysis-chips" aria-label="Filtros ativos">
                <?php foreach ($filters as $key => $value): [$label, $options] = $filterOptions[$key]; ?>
                    <a href="<?= H::escape($url([$key => null, ...($key === 'grupo_id' ? ['subgrupo_id' => null] : [])])) ?>" aria-label="Remover filtro <?= H::escape($label) ?>"><?= H::escape($label . ': ' . ($options[$value] ?? 'Selecionado')) ?><span aria-hidden="true"> ×</span></a>
                <?php endforeach; ?>
            </div>
            <div class="analysis-filter-actions"><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/analises') ?>">Limpar</a><button class="btn btn-uai-primary">Aplicar filtros</button></div>
        </div>
    </div>
</form>
<div class="row g-3 mb-3">
    <?php foreach (['receitas' => 'Quanto entrou', 'despesas' => 'Quanto saiu', 'resultado' => 'Quanto sobrou'] as $key => $label): $difference = (int)$totals[$key] - (int)$previous[$key]; ?>
        <div class="col-md-4">
            <section class="card metric-card <?= $key === 'resultado' ? 'analysis-result' : '' ?>">
                <div class="card-body">
                    <p><?= $label ?></p>
                    <strong class="metric-value"><?= Money::format((int)$totals[$key]) ?></strong>
                    <small><?= $difference === 0 ? 'Sem variação em relação ao período anterior' : Money::format(abs($difference)) . ($difference > 0 ? ' a mais' : ' a menos') . ' que no período anterior' ?></small>
                </div>
            </section>
        </div>
    <?php endforeach; ?>
</div>
<p class="analysis-context">Por competência, líquido de estornos. Comparação com <?= H::date($previousStart) ?> a <?= H::date($previousEnd) ?>, com os mesmos filtros. Transferências e pagamentos de fatura não entram nos gastos.</p>
<section class="card mb-4">
    <div class="card-body">
        <div class="section-heading">
            <h2 class="h4">Evolução mensal</h2><span class="small text-secondary">Receitas e despesas</span>
        </div>
        <p class="small text-secondary"><?= H::date($seriesStart) ?> a <?= H::date($end) ?> · os meses nas extremidades podem estar incompletos.</p>
        <?php require __DIR__ . '/analytics-plot.php'; ?>
    </div>
</section>
<?php require __DIR__ . '/analytics-charts.php'; ?>
<section class="card mt-4" id="lancamentos">
    <div class="card-body">
        <div class="section-heading">
            <h2 class="h4">Conferir lançamentos</h2><span class="small text-secondary">Mesmo período e filtros dos totais</span>
        </div>
        <?php if (!$audit): ?>
            <p class="text-secondary">Veja quais lançamentos e estornos compõem os valores desta análise.</p>
            <a class="btn btn-outline-secondary" href="<?= H::escape($url(['conferir' => 1], '#lancamentos')) ?>">Ver lançamentos</a>
        <?php else: ?>
            <?php if (!$entries): ?><p class="empty-state">Nenhum lançamento realizado nesta seleção.</p><?php endif; ?>
            <div class="analysis-entries">
                <?php foreach (array_slice($entries, 0, 30) as $entry): ?>
                    <a class="analysis-entry" href="<?= H::escape($basePath . '/movimentacoes/' . $entry['transacao_id']) ?>">
                        <div><strong><?= H::escape($entry['descricao']) ?></strong><small><?= H::date($entry['data']) ?> · <?= H::escape($entry['categoria'] . ' · ' . $entry['subcategoria']) ?><br><?= H::escape($entry['cartao'] ?? $entry['conta'] ?? 'Sem conta') ?></small></div>
                        <div class="text-end"><strong><?= Money::format((int)$entry['valor_centavos']) ?></strong><small><?= $entry['tipo'] === 'receita' ? 'Receita' : 'Despesa' ?><?= (int)$entry['valor_centavos'] < 0 ? ' · Estorno' : '' ?></small></div>
                    </a>
                <?php endforeach; ?>
            </div>
            <nav class="analysis-pagination" aria-label="Páginas de lançamentos">
                <?php if ($auditPage > 1): ?><a href="<?= H::escape($url(['conferir' => 1, 'pagina' => $auditPage - 1], '#lancamentos')) ?>">← Anterior</a><?php endif; ?>
                <span class="small text-secondary">Página <?= $auditPage ?></span>
                <?php if (count($entries) > 30): ?><a href="<?= H::escape($url(['conferir' => 1, 'pagina' => $auditPage + 1], '#lancamentos')) ?>">Próxima →</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>