<?php
use App\Core\Html as H;
use App\Core\Money;
$suggested = [];
foreach ($suggestions as $suggestion) $suggested[mb_strtolower($suggestion['descricao']) . '|' . $suggestion['tipo']] = (int) $suggestion['subgrupo_id'];
?>
<a class="back-link" href="<?= H::escape($basePath . '/movimentacoes') ?>">← Movimentações</a>
<div class="page-heading"><div><p class="eyebrow">MENOS DIGITAÇÃO</p><h1>Importar extrato</h1><p class="text-secondary">Envie CSV com colunas Data, Descrição e Valor, ou um arquivo OFX. Valores negativos são despesas.</p></div></div>
<section class="card mb-4"><div class="card-body">
    <form method="post" action="<?= H::escape($basePath . '/movimentacoes/importar/previa') ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
        <?= H::fields($csrfToken) ?>
        <div class="col-md-4"><label for="import-account" class="form-label">Conta do extrato</label><select id="import-account" class="form-select" name="conta_id" required><option value="">Selecione</option><?php foreach ($accounts as $account): ?><option value="<?= (int) $account['id'] ?>" <?= (int) ($preview['account'] ?? 0) === (int) $account['id'] ? 'selected' : '' ?>><?= H::escape($account['nome']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-5"><label for="import-file" class="form-label">Arquivo CSV ou OFX</label><input id="import-file" name="arquivo" type="file" accept=".csv,.ofx,text/csv" class="form-control" required></div>
        <div class="col-md-3"><button class="btn btn-uai-primary w-100">Conferir arquivo</button></div>
    </form>
    <p class="small text-secondary mt-3 mb-0">Até 200 linhas e 2 MB por vez. Nenhum lançamento é criado antes da confirmação. Confira se o extrato inclui transferências ou pagamentos de fatura: registre esses eventos pelo fluxo próprio para evitar dupla contagem.</p>
</div></section>
<?php if ($preview): ?>
<form method="post" action="<?= H::escape($basePath . '/movimentacoes/importar') ?>" class="card mb-4">
    <div class="card-body">
        <?= H::fields($csrfToken) ?><input type="hidden" name="nonce" value="<?= H::escape($preview['nonce']) ?>">
        <div class="section-heading"><h2 class="h4">Prévia do extrato</h2><button class="btn btn-uai-primary">Importar selecionados</button></div>
        <p class="small text-secondary">Escolha a categoria de cada linha. Possíveis duplicatas ficam desmarcadas; marque-as somente se tiver certeza de que são lançamentos diferentes.</p>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Importar</th><th>Data</th><th>Descrição</th><th>Valor</th><th>Categoria</th></tr></thead><tbody>
        <?php foreach ($preview['rows'] as $index => $row): $type = $row['centavos'] < 0 ? 'despesa' : 'receita'; $duplicate = $row['repetida'] || $row['existente']; ?>
            <tr><td><input type="checkbox" class="form-check-input" name="selecionar[<?= $index ?>]" value="1" <?= $duplicate ? '' : 'checked' ?> aria-label="Importar <?= H::escape($row['descricao']) ?>"></td><td><?= H::date($row['data']) ?></td><td><?= H::escape($row['descricao']) ?><?= $duplicate ? '<small class="d-block text-secondary">Possível duplicata</small>' : '' ?></td><td class="<?= $type === 'receita' ? 'text-success' : 'text-danger' ?>"><?= Money::format($row['centavos']) ?></td><td><select class="form-select form-select-sm" name="categoria[<?= $index ?>]" aria-label="Categoria de <?= H::escape($row['descricao']) ?>"><option value="">Selecione</option><?php foreach ($categories as $category): if ($category['tipo'] !== $type) continue; ?><option value="<?= (int) $category['id'] ?>" <?= ($suggested[mb_strtolower($row['descricao']) . '|' . $type] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= H::escape($category['nome'] . ' · ' . $category['grupo_nome']) ?></option><?php endforeach; ?></select></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <button class="btn btn-uai-primary">Importar selecionados</button>
    </div>
</form>
<?php endif; ?>
<?php if ($lastBatch): ?><section class="card"><div class="card-body"><h2 class="h5">Última importação</h2><p><?= (int) $lastBatch['quantidade'] ?> lançamentos · lote <?= (int) $lastBatch['id'] ?></p><form method="post" action="<?= H::escape($basePath . '/movimentacoes/importar/desfazer') ?>" data-confirm="Desfazer este lote? Os lançamentos irão para a lixeira."><?= H::fields($csrfToken) ?><input type="hidden" name="lote_id" value="<?= (int) $lastBatch['id'] ?>"><button class="btn btn-outline-danger">Desfazer importação</button></form></div></section><?php endif; ?>
