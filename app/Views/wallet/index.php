<?php

use App\Core\Html as H;
use App\Core\Money;

$accountId = (int) ($account['id'] ?? 0);
?>
<a class="back-link" href="<?= H::escape($basePath . '/contas') ?>">← Todas as contas</a>
<div class="page-heading">
    <div><p class="eyebrow">SEU DINHEIRO</p><h1><?= H::escape($account['nome'] ?? 'Contas') ?></h1><p class="text-secondary">Saldo, extrato e conferência no mesmo lugar.</p></div>
    <?php if ($account): ?><div class="detail-actions"><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/contas/' . $accountId . '/compartilhar') ?>">Compartilhar extrato</a><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/contas/' . $accountId . '/editar') ?>">Editar conta</a></div><?php endif; ?>
</div>
<?php if ($account): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6"><section class="card metric-card"><div class="card-body"><p>Saldo disponível hoje</p><strong class="metric-value"><?= Money::format((int) $account['saldo_atual_centavos']) ?></strong><small>Movimentações confirmadas até hoje</small></div></section></div>
        <div class="col-md-6"><section class="card"><div class="card-body"><p class="small text-secondary">Saldo inicial em <?= H::date($account['saldo_inicial_em']) ?></p><strong><?= Money::format((int) $account['saldo_inicial_centavos']) ?></strong><div class="detail-actions mt-3"><a class="btn btn-uai-primary" href="<?= H::escape($basePath . '/movimentacoes/nova?conta_id=' . $accountId) ?>">+ Novo lançamento</a><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/movimentacoes?conta_id=' . $accountId) ?>">Ver lançamentos</a></div></div></section></div>
    </div>
<?php endif; ?>
<?php if ($account && (int) $account['ativo']): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <details class="card account-operation" <?= isset($old['origem_id']) ? 'open' : '' ?>>
                <summary>Transferir entre minhas contas</summary>
                <div class="card-body pt-0">
                    <p class="small text-secondary">Atualiza as duas contas. Não conta como receita ou despesa.</p>
                    <form method="post" action="<?= H::escape($basePath . '/carteira/transferir') ?>" class="row g-3">
                        <?= H::fields($csrfToken) ?>
                        <input type="hidden" name="origem_id" value="<?= $accountId ?>">
                        <div class="col-12"><label for="transfer-to" class="form-label">Para qual conta?</label><select id="transfer-to" name="destino_id" class="form-select" required><option value="">Selecione</option><?php foreach ($accounts as $item): if ((int) $item['id'] === $accountId) continue; ?><option value="<?= (int) $item['id'] ?>" <?= (int) ($old['destino_id'] ?? 0) === (int) $item['id'] ? 'selected' : '' ?>><?= H::escape($item['nome']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-sm-6"><label for="transfer-value" class="form-label">Valor (R$)</label><input id="transfer-value" name="valor" inputmode="decimal" class="form-control" required value="<?= H::escape($old['valor'] ?? '') ?>"></div>
                        <div class="col-sm-6"><label for="transfer-date" class="form-label">Data</label><input id="transfer-date" name="data" type="date" class="form-control" max="<?= date('Y-m-d') ?>" required value="<?= H::escape($old['data'] ?? date('Y-m-d')) ?>"></div>
                        <div class="col-12"><button class="btn btn-uai-primary">Registrar transferência</button></div>
                    </form>
                </div>
            </details>
        </div>
        <div class="col-lg-6">
            <details class="card account-operation" <?= isset($old['saldo']) ? 'open' : '' ?>>
                <summary>Conferir saldo com o banco</summary>
                <div class="card-body pt-0">
                    <p class="small text-secondary">Informe o saldo de hoje. Uma diferença será registrada como ajuste no extrato.</p>
                    <form method="post" action="<?= H::escape($basePath . '/carteira/conferir') ?>" class="vstack gap-3" data-confirm="Registrar esta conferência? A diferença será registrada como ajuste de saldo.">
                        <?= H::fields($csrfToken) ?>
                        <input type="hidden" name="conta_id" value="<?= $accountId ?>">
                        <div><label for="reconcile-balance" class="form-label">Saldo no banco (R$)</label><input id="reconcile-balance" name="saldo" class="form-control" inputmode="decimal" required value="<?= H::escape($old['saldo'] ?? '') ?>"></div>
                        <div><label for="reconcile-reason" class="form-label">Motivo da conferência</label><input id="reconcile-reason" name="motivo" maxlength="200" class="form-control" required value="<?= H::escape($old['motivo'] ?? '') ?>"></div>
                        <button class="btn btn-uai-primary">Registrar conferência</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
<?php endif; ?>
<section class="card" id="extrato">
    <div class="card-body">
        <div class="section-heading"><h2 class="h4">Extrato da conta</h2><form method="get" action="<?= H::escape($basePath . '/carteira') ?>" class="d-flex gap-2 statement-switch"><label class="visually-hidden" for="statement-account">Conta</label><select id="statement-account" name="conta" class="form-select"><?php foreach ($accounts as $item): ?><option value="<?= (int) $item['id'] ?>" <?= (int) $item['id'] === $accountId ? 'selected' : '' ?>><?= H::escape($item['nome']) ?></option><?php endforeach; ?></select><button class="btn btn-outline-secondary">Ver</button></form></div>
        <p class="small text-secondary">Inclui pagamentos de fatura, transferências, estornos e ajustes. Valores anteriores ao saldo inicial não alteram o saldo atual.</p>
        <?php if (!$entries): ?><p class="empty-state">Nenhuma movimentação de caixa nesta conta.</p><?php endif; ?>
        <?php foreach (array_slice($entries, 0, 50) as $entry): ?>
            <div class="list-row"><div><?php if ($entry['origem'] === 'lancamento'): ?><a href="<?= H::escape($basePath . '/movimentacoes/' . $entry['origem_id']) ?>"><?= H::escape($entry['descricao']) ?></a><?php else: ?><?= H::escape($entry['descricao']) ?><?php endif; ?><small class="d-block text-secondary"><?= H::date($entry['data']) ?> · <?= H::escape(['lancamento' => 'Lançamento', 'transferencia' => 'Transferência', 'fatura' => 'Fatura', 'conferencia' => 'Conferência', 'estorno' => 'Estorno'][$entry['origem']] ?? $entry['origem']) ?></small></div><strong><?= Money::format((int) $entry['valor_centavos']) ?></strong></div>
        <?php endforeach; ?>
<?php if ($account): ?>
    <details class="mt-3"><summary>Exportar lançamentos da conta</summary><p class="small text-secondary mt-2">Exportações dos lançamentos cadastrados. Transferências, pagamentos de fatura e ajustes aparecem no extrato acima.</p><div class="detail-actions"><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath . '/contas/' . $accountId . '/exportar/pdf') ?>">PDF</a><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath . '/contas/' . $accountId . '/exportar/excel') ?>">Excel</a><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($basePath . '/movimentacoes/exportar?conta_id=' . $accountId) ?>">CSV</a></div></details>
<?php endif; ?>        <nav class="pagination-links" aria-label="Páginas do extrato"><?php if ($page > 1): ?><a href="<?= H::escape($basePath . '/contas/' . $accountId . '?pagina=' . ($page - 1)) ?>#extrato">← Anterior</a><?php endif; ?><span>Página <?= $page ?></span><?php if (count($entries) > 50): ?><a href="<?= H::escape($basePath . '/contas/' . $accountId . '?pagina=' . ($page + 1)) ?>#extrato">Próxima →</a><?php endif; ?></nav>
    </div>
</section>
