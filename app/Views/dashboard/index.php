<?php

use App\Core\Html as H;
use App\Core\Money;

$unpaid = array_values(array_filter($invoices, fn($invoice) => (int) $invoice['total'] > (int) $invoice['pago']));
usort($unpaid, fn($a, $b) => strcmp($a['data_vencimento'], $b['data_vencimento']));
?>
<div class="page-heading">
    <div><p class="eyebrow">SEU DINHEIRO, COM CLAREZA</p><h1>Visão geral</h1><p class="text-secondary">Olá, <?= H::escape(explode(' ', $usuario['nome'])[0]) ?>. Veja o que precisa da sua atenção.</p></div>
    <a class="btn btn-uai-primary" href="<?= H::escape($basePath . '/movimentacoes/nova') ?>">+ Novo lançamento</a>
</div>
<?php if (!$accounts || !$categories): ?>
    <div class="welcome-strip mb-4"><div><h2 class="h5 mb-1">Vamos organizar seu começo?</h2><span>Cadastre uma conta e suas categorias para registrar o primeiro lançamento.</span></div><a class="btn btn-uai-primary" href="<?= H::escape($basePath . '/comecar') ?>">Primeiros passos →</a></div>
<?php endif; ?>
<section aria-labelledby="today-title" class="mb-4">
    <h2 class="h5 mb-3" id="today-title">Hoje · <?= date('d/m') ?></h2>
    <div class="row g-3">
        <div class="col-md-6"><article class="card metric-card featured"><div class="card-body"><p>Saldo disponível nas contas</p><strong class="metric-value"><?= Money::format((int) $balance) ?></strong><small>Contas ativas · valores confirmados</small></div></article></div>
        <div class="col-md-6"><article class="card metric-card"><div class="card-body"><p>Previsão até <?= date('t/m') ?></p><strong class="metric-value"><?= Money::format((int) $projection) ?></strong><small>Saldo + valores a receber − valores e faturas a pagar</small></div></article></div>
    </div>
    <p class="small text-secondary mt-3">A previsão considera os compromissos cadastrados até o fim deste mês, incluindo atrasados. Recorrências ainda não geradas ficam de fora.</p>
</section>
<div class="row g-4 mb-4">
    <div class="col-xl-7"><section class="card h-100"><div class="card-body">
        <div class="section-heading"><h2 class="h4">Próximos compromissos</h2><a href="<?= H::escape($basePath . '/movimentacoes?status=pendente') ?>">Ver todos →</a></div>
        <?php if (!$upcoming): ?><p class="empty-state">Nenhuma pendência cadastrada.</p><?php endif; ?>
        <?php foreach ($upcoming as $entry): ?>
            <article class="movement-row">
                <div class="movement-main"><a class="movement-title" href="<?= H::escape($basePath . '/movimentacoes/' . $entry['id']) ?>"><?= H::escape($entry['descricao']) ?></a><small class="<?= $entry['data_vencimento'] < date('Y-m-d') ? 'text-danger' : '' ?>"><?= H::date($entry['data_vencimento']) ?> · <?= $entry['data_vencimento'] < date('Y-m-d') ? 'Atrasado' : ($entry['tipo'] === 'receita' ? 'A receber' : 'A pagar') ?></small></div>
                <div class="movement-value"><strong><?= Money::format((int) $entry['valor_centavos']) ?></strong><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath . '/movimentacoes/' . $entry['id'] . '/editar?situacao=efetivada') ?>"><?= $entry['tipo'] === 'receita' ? 'Receber' : 'Pagar' ?></a></div>
            </article>
        <?php endforeach; ?>
    </div></section></div>
    <div class="col-xl-5"><section class="card h-100"><div class="card-body">
        <div class="section-heading"><h2 class="h4">Faturas a pagar</h2><a href="<?= H::escape($basePath . '/cartoes') ?>">Ver cartões →</a></div>
        <?php if (!$unpaid): ?><p class="empty-state">Nenhuma fatura a pagar.</p><?php endif; ?>
        <?php foreach (array_slice($unpaid, 0, 4) as $invoice): ?>
            <a class="list-row text-decoration-none" href="<?= H::escape($basePath . '/faturas/' . $invoice['id']) ?>"><div><?= H::escape($invoice['cartao_nome']) ?><small class="d-block <?= $invoice['data_vencimento'] < date('Y-m-d') ? 'text-danger' : 'text-secondary' ?>">Vence <?= H::date($invoice['data_vencimento']) ?></small></div><strong><?= Money::format((int) $invoice['total'] - (int) $invoice['pago']) ?></strong></a>
        <?php endforeach; ?>
    </div></section></div>
</div>
<section class="card"><div class="card-body">
    <div class="section-heading"><div><h2 class="h4">Resumo do mês</h2><p class="small text-secondary mb-0">Receitas e despesas realizadas, pela data usada nas análises.</p></div><form method="get" class="d-flex gap-2"><label class="visually-hidden" for="dash-month">Mês do resumo</label><input id="dash-month" type="month" name="mes" class="form-control" value="<?= H::escape($month) ?>" required><button class="btn btn-outline-secondary">Ver</button></form></div>
    <div class="row g-3 my-2">
        <?php foreach (['Receitas' => (int) $totals['receitas'], 'Despesas' => (int) $totals['despesas'], 'Resultado' => (int) $totals['receitas'] - (int) $totals['despesas']] as $label => $amount): ?>
            <div class="col-md-4"><p class="text-secondary mb-1"><?= $label ?></p><strong class="h4 <?= $label === 'Receitas' ? 'text-success' : ($amount < 0 ? 'text-danger' : '') ?>"><?= Money::format($amount) ?></strong></div>
        <?php endforeach; ?>
    </div>
    <p class="small text-secondary">Neste mês: <?= Money::format((int) $totals['receber']) ?> a receber e <?= Money::format((int) $totals['pagar']) ?> a pagar. Esses valores ainda não entram no resultado acima.</p>
    <?php foreach (array_slice($budgets, 0, 4) as $budget): ?>
        <div class="py-2"><div class="d-flex justify-content-between small mb-2"><strong><?= H::escape($budget['nome']) ?></strong><span><?= Money::format((int) $budget['realizado']) ?> de <?= Money::format((int) $budget['valor_limite_centavos']) ?></span></div><progress class="uai-progress" max="<?= (int) $budget['valor_limite_centavos'] ?>" value="<?= min(max(0, (int) $budget['realizado']), (int) $budget['valor_limite_centavos']) ?>" aria-label="Consumo de <?= H::escape($budget['nome']) ?>"></progress></div>
    <?php endforeach; ?>
    <div class="detail-actions mt-3"><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/analises?mes=' . $month) ?>">Explorar análises</a><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/planejamento?mes=' . $month) ?>">Ajustar planejamento</a></div>
</div></section>
