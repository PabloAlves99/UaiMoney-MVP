<?php use App\Core\Html as H; use App\Core\Money; ?>
<a class="back-link" href="<?= H::escape($basePath.'/cartoes') ?>">← Todos os cartões</a>
<div class="page-heading"><div><p class="eyebrow">CARTÃO DE CRÉDITO</p><h1><?= H::escape($card['nome']) ?></h1><p class="text-secondary">Fecha dia <?= (int)$card['dia_fechamento'] ?> · Vence dia <?= (int)$card['dia_vencimento'] ?></p></div><a class="btn btn-uai-primary" href="<?= H::escape($basePath . '/movimentacoes/nova?cartao_id=' . $card['id']) ?>">+ Nova compra</a></div>
<?php
$pending = array_values(array_filter($invoices, fn($invoice) => (int) $invoice['total'] > (int) $invoice['pago']));
usort($pending, fn($a, $b) => strcmp($a['data_vencimento'], $b['data_vencimento']));
$next = $pending[0] ?? null;
$used = (int) ($card['utilizado'] ?? 0);
?>
<div class="row g-3 mb-4">
    <div class="col-md-6"><article class="card metric-card"><div class="card-body"><p>Limite disponível</p><strong class="metric-value"><?= Money::format((int) $card['limite_centavos'] - $used) ?></strong><small><?= Money::format($used) ?> comprometidos de <?= Money::format((int) $card['limite_centavos']) ?></small><p class="small text-secondary mt-3 mb-0">Inclui parcelas futuras. Pagamentos e estornos liberam o limite.</p></div></article></div>
    <div class="col-md-6"><article class="card metric-card featured"><div class="card-body"><p>Próxima fatura a pagar</p><?php if ($next): ?><strong class="metric-value"><?= Money::format((int) $next['total'] - (int) $next['pago']) ?></strong><small>Vence <?= H::date($next['data_vencimento']) ?><?= $next['data_vencimento'] < date('Y-m-d') ? ' · Atrasada' : '' ?></small><a class="btn btn-outline-secondary mt-3" href="<?= H::escape($basePath . '/faturas/' . $next['id']) ?>">Ver fatura e pagar</a><?php else: ?><strong class="h4">Tudo em dia</strong><small>Nenhuma fatura com saldo a pagar.</small><?php endif; ?></div></article></div>
</div><section class="card mb-4"><div class="card-body"><h2 class="h4">Faturas</h2>
<?php if (!$invoices): ?><p class="empty-state">As faturas aparecem após a primeira compra.</p><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Fechamento</th><th>Vencimento</th><th>Total</th><th>Pago</th><th>Saldo / crédito</th><th>Situação</th></tr></thead><tbody>
<?php foreach ($invoices as $f): $remaining=(int)$f['total']-(int)$f['pago']; ?><tr><td><a href="<?= H::escape($basePath.'/faturas/'.$f['id']) ?>"><?= H::date($f['data_fechamento']) ?></a></td><td><?= H::date($f['data_vencimento']) ?></td><td><?= Money::format((int)$f['total']) ?></td><td><?= Money::format((int)$f['pago']) ?></td><td><?= Money::format($remaining) ?></td><td><span class="badge text-bg-<?= $remaining<=0 ? 'success' : 'secondary' ?>"><?= $remaining<0 ? 'Crédito' : ($remaining===0 ? 'Quitada' : ((int)$f['pago']>0 ? 'Pagamento parcial' : ($f['status']==='aberta' ? 'Aberta' : 'Fechada'))) ?></span></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div></section>
<?php if ($card['ativo']): ?>
<form method="post" action="<?= H::escape($basePath.'/cartoes/'.$card['id'].'/desativar') ?>" class="mt-4" data-confirm="Desativar este cartão? O histórico será preservado."><?= H::fields($csrfToken) ?><button class="btn btn-outline-secondary btn-sm">Desativar cartão</button></form>
<?php endif; ?>
