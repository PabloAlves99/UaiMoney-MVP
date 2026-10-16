<?php
use App\Core\Html as H;
use App\Core\Money;
?>
<div class="page-heading"><div><p class="eyebrow">ROTINA FINANCEIRA</p><h1>Para conferir</h1><p class="text-secondary">Resolva as pendências e confira seus saldos em um só lugar.</p></div></div>
<div class="row g-4">
    <div class="col-lg-7"><section class="card h-100"><div class="card-body">
        <div class="section-heading"><h2 class="h4">A pagar e a receber</h2><a href="<?= H::escape($basePath . '/movimentacoes?status=pendente') ?>">Ver movimentações →</a></div>
        <?php if (!$upcoming): ?><p class="empty-state">Nenhuma pendência cadastrada.</p><?php endif; ?>
        <?php foreach ($upcoming as $entry): ?>
            <div class="list-row"><div><a href="<?= H::escape($basePath . '/movimentacoes/' . $entry['id']) ?>"><?= H::escape($entry['descricao']) ?></a><small class="d-block <?= $entry['data_vencimento'] < date('Y-m-d') ? 'text-danger' : 'text-secondary' ?>"><?= H::date($entry['data_vencimento']) ?> · <?= $entry['tipo'] === 'receita' ? 'A receber' : 'A pagar' ?></small></div><div class="text-end"><strong><?= Money::format((int) $entry['valor_centavos']) ?></strong><a class="d-block" href="<?= H::escape($basePath . '/movimentacoes/' . $entry['id'] . '/editar?situacao=efetivada') ?>"><?= $entry['tipo'] === 'receita' ? 'Receber' : 'Pagar' ?> →</a></div></div>
        <?php endforeach; ?>
    </div></section></div>
    <div class="col-lg-5"><section class="card mb-4"><div class="card-body">
        <h2 class="h4">Faturas em aberto</h2>
        <?php if (!$invoices): ?><p class="text-secondary mb-0">Nenhuma fatura a pagar.</p><?php endif; ?>
        <?php foreach ($invoices as $invoice): ?><a class="list-row text-decoration-none" href="<?= H::escape($basePath . '/faturas/' . $invoice['id']) ?>"><span><?= H::escape($invoice['cartao_nome']) ?><small class="d-block text-secondary">Vence <?= H::date($invoice['data_vencimento']) ?></small></span><strong><?= Money::format((int) $invoice['total'] - (int) $invoice['pago']) ?></strong></a><?php endforeach; ?>
    </div></section><section class="card"><div class="card-body">
        <h2 class="h4">Conferir saldos</h2><p class="small text-secondary">Compare cada conta com o extrato do banco. Registre um ajuste apenas se houver diferença.</p>
        <?php foreach ($accounts as $account): ?><a class="list-row text-decoration-none" href="<?= H::escape($basePath . '/contas/' . $account['id']) ?>"><span><?= H::escape($account['nome']) ?></span><strong><?= Money::format((int) $account['saldo_atual_centavos']) ?> →</strong></a><?php endforeach; ?>
    </div></section></div>
</div>
