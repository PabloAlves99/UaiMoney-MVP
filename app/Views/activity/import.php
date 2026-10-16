<?php
use App\Core\Html as H;
use App\Core\Money;
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
    <?php if ($preview): ?><p class="small text-secondary mt-2 mb-0">Enviar outro arquivo substituirá a prévia salva abaixo.</p><?php endif; ?>
</div></section>
<?php if ($preview): ?>
<form method="post" action="<?= H::escape($basePath . '/movimentacoes/importar') ?>" data-import-draft data-draft-save-url="<?= H::escape($basePath . '/movimentacoes/importar/rascunho') ?>" class="card mb-4">
    <div class="card-body">
        <?= H::fields($csrfToken) ?><input type="hidden" name="nonce" value="<?= H::escape($preview['nonce']) ?>">
        <div class="section-heading"><div><h2 class="h4 mb-1">Prévia salva</h2><p class="small text-secondary mb-0"><?= count($preview['rows']) ?> <?= count($preview['rows']) === 1 ? 'linha pendente' : 'linhas pendentes' ?>. Você pode sair e continuar depois.</p></div><button class="btn btn-uai-primary">Enviar selecionadas</button></div>
        <p class="small text-secondary">Escolha as categorias e as linhas que deseja enviar. Possíveis duplicatas começam desmarcadas. As linhas não enviadas permanecem aqui.</p>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><button type="button" class="btn btn-sm btn-outline-secondary" data-select-all>Selecionar todos</button><button type="button" class="btn btn-sm btn-outline-secondary" data-select-none>Desmarcar todos</button><span class="small text-secondary ms-auto" data-draft-status aria-live="polite">Alterações salvas automaticamente</span></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Enviar</th><th>Data</th><th>Descrição</th><th>Valor</th><th>Categoria</th><th class="text-end">Ações</th></tr></thead><tbody>
        <?php foreach ($preview['rows'] as $index => $row): $type = $row['centavos'] < 0 ? 'despesa' : 'receita'; $duplicate = $row['repetida'] || $row['existente']; ?>
            <tr><td><input type="checkbox" class="form-check-input" name="selecionar[<?= $index ?>]" value="1" <?= !empty($row['selected']) ? 'checked' : '' ?> data-import-select aria-label="Enviar <?= H::escape($row['descricao']) ?>"></td><td><?= H::date($row['data']) ?></td><td><?= H::escape($row['descricao']) ?><?= $duplicate ? '<small class="d-block text-secondary">Possível duplicata</small>' : '' ?></td><td class="<?= $type === 'receita' ? 'text-success' : 'text-danger' ?>"><?= Money::format($row['centavos']) ?></td><td><select class="form-select form-select-sm" name="categoria[<?= $index ?>]" aria-label="Categoria de <?= H::escape($row['descricao']) ?>"><option value="">Selecione</option><?php foreach ($categories as $category): if ($category['tipo'] !== $type) continue; ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($row['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= H::escape($category['nome'] . ' · ' . $category['grupo_nome']) ?></option><?php endforeach; ?></select></td><td class="text-end"><div class="d-flex justify-content-end gap-2"><button class="btn btn-sm btn-outline-secondary" name="somente" value="<?= $index ?>">Enviar esta linha</button><button class="btn btn-sm btn-outline-danger" formaction="<?= H::escape($basePath . '/movimentacoes/importar/linha/remover') ?>" name="indice" value="<?= $index ?>" formnovalidate data-confirm="Excluir esta linha da prévia?">Excluir</button></div></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <div class="d-flex flex-wrap gap-2 mt-3"><button class="btn btn-uai-primary">Enviar selecionadas</button><button class="btn btn-outline-secondary" formaction="<?= H::escape($basePath . '/movimentacoes/importar/rascunho') ?>" formnovalidate>Salvar prévia</button><button class="btn btn-outline-secondary" formaction="<?= H::escape($basePath . '/movimentacoes/importar/rascunho') ?>" name="destino" value="categorias" formnovalidate>Salvar e criar categoria</button><button class="btn btn-outline-danger ms-auto" formaction="<?= H::escape($basePath . '/movimentacoes/importar/descartar') ?>" formnovalidate data-confirm="Descartar toda a prévia? Nenhuma linha será importada.">Descartar prévia</button></div>
    </div>
</form>
<script src="<?= H::escape($basePath . '/js/import-draft.js?v=20261016') ?>" defer></script>
<?php endif; ?>
<?php if ($lastBatch): ?><section class="card"><div class="card-body"><h2 class="h5">Última importação</h2><p><?= (int) $lastBatch['quantidade'] ?> lançamentos · lote <?= (int) $lastBatch['id'] ?></p><form method="post" action="<?= H::escape($basePath . '/movimentacoes/importar/desfazer') ?>" data-confirm="Desfazer este lote? Os lançamentos irão para Arquivados."><?= H::fields($csrfToken) ?><input type="hidden" name="lote_id" value="<?= (int) $lastBatch['id'] ?>"><button class="btn btn-outline-danger">Desfazer importação</button></form></div></section><?php endif; ?>
