<?php use App\Core\Html as H; use App\Core\Money; $form=$old ?: ($editing ?? []); ?>
<div class="page-heading"><div><p class="eyebrow">SEU DIA A DIA</p><h1>Cartões de crédito</h1><p class="text-secondary">Faturas organizadas. Limites sempre à vista.</p></div><a class="btn btn-uai-primary" href="#card-form">+ Novo cartão</a></div>
<div class="row g-4 mb-4">
<?php foreach ($cards as $card): $used=max(0,(int)$card['utilizado']); $limit=(int)$card['limite_centavos']; ?>
<div class="col-md-6 col-xl-4"><article class="card h-100"><div class="card-body">
<div class="d-flex justify-content-between"><span class="eyebrow"><?= H::escape($card['instituicao'] ?: 'CARTÃO') ?></span><span class="badge text-bg-<?= $card['ativo'] ? 'success' : 'secondary' ?>"><?= $card['ativo'] ? 'Ativo' : 'Arquivado' ?></span></div>
<h2 class="h4 mt-3"><a href="<?= H::escape($basePath.'/cartoes/'.$card['id']) ?>"><?= H::escape($card['nome']) ?></a></h2>
<p class="text-secondary mb-1">Limite disponível</p><strong class="metric-value"><?= Money::format($limit-$used) ?></strong>
<progress class="uai-progress my-3" max="<?= max(1,$limit) ?>" value="<?= min($used,max(1,$limit)) ?>" aria-label="Limite utilizado"></progress>
<p class="small text-secondary"><?= Money::format($used) ?> utilizados de <?= Money::format($limit) ?><?= $used>$limit ? ' · Limite excedido' : '' ?></p>
<div class="d-flex justify-content-between small"><span>Fecha dia <?= (int)$card['dia_fechamento'] ?></span><span>Vence dia <?= (int)$card['dia_vencimento'] ?></span></div>
<div class="d-flex gap-2 mt-4"><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/cartoes/'.$card['id']) ?>">Ver faturas</a><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath.'/cartoes?editar='.$card['id'].'#card-form') ?>">Editar</a></div>
</div></article></div>
<?php endforeach; ?>
<?php if (!$cards): ?><div class="col"><div class="empty-state"><h2 class="h4">Seu primeiro cartão começa aqui</h2><p>Cadastre limite e datas. Depois, registre compras à vista ou parceladas.</p></div></div><?php endif; ?>
</div>
<section class="card" id="card-form"><div class="card-body"><h2 class="h4"><?= $editing ? 'Editar cartão' : 'Novo cartão' ?></h2><p class="text-secondary">Alterações de datas valem para novas faturas. O histórico é preservado.</p>
<form action="<?= H::escape($basePath.'/cartoes') ?>" method="post" class="row g-3">
<?= H::fields($csrfToken) ?><input type="hidden" name="id" value="<?= H::escape($form['id'] ?? '') ?>">
<div class="col-md-6"><label class="form-label" for="card-name">Nome do cartão</label><input id="card-name" class="form-control" name="nome" maxlength="80" required value="<?= H::escape($form['nome'] ?? '') ?>" placeholder="Ex.: Cartão principal"></div>
<div class="col-md-6"><label class="form-label" for="card-bank">Instituição</label><input id="card-bank" class="form-control" name="instituicao" maxlength="80" value="<?= H::escape($form['instituicao'] ?? '') ?>"></div>
<div class="col-md-4"><label class="form-label" for="card-limit">Limite total (R$)</label><input id="card-limit" class="form-control" name="limite" inputmode="decimal" required value="<?= H::escape($form['limite'] ?? (isset($form['limite_centavos']) ? number_format($form['limite_centavos']/100,2,'.','') : '')) ?>"></div>
<div class="col-md-4"><label class="form-label" for="card-close">Dia do fechamento</label><input id="card-close" class="form-control" type="number" name="dia_fechamento" min="1" max="31" required value="<?= H::escape($form['dia_fechamento'] ?? '') ?>"></div>
<div class="col-md-4"><label class="form-label" for="card-due">Dia do vencimento</label><input id="card-due" class="form-control" type="number" name="dia_vencimento" min="1" max="31" required value="<?= H::escape($form['dia_vencimento'] ?? '') ?>"></div>
<div class="col-md-8"><label class="form-label" for="card-account">Conta padrão para pagar</label><select id="card-account" class="form-select" name="conta_pagamento_id"><option value="">Escolher no pagamento</option><?php foreach ($accounts as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (string)($form['conta_pagamento_id'] ?? '')===(string)$a['id'] ? 'selected' : '' ?>><?= H::escape($a['nome']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4 d-flex align-items-end"><button class="btn btn-uai-primary w-100">Salvar cartão</button></div>
</form></div></section>
