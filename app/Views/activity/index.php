<?php

use App\Core\Html as H;
use App\Core\Money;

$trash = ($filters['lixeira'] ?? '') === '1';
$returnPath = '/movimentacoes?' . http_build_query($filters + ['pagina' => $page]);
$context = 'voltar=' . urlencode($returnPath);
$filterUrl = fn(array $changes) => $basePath . '/movimentacoes?' . http_build_query(array_filter(array_replace($filters, $changes), fn($value) => $value !== null && $value !== ''));
$groups = [];
foreach ($categories as $category) $groups[$category['grupo_id']] = $category['grupo_nome'];
$selects = [
    'status' => ['Situação', ['pendente' => 'Pendente', 'efetivada' => 'Realizada', 'cancelada' => 'Cancelada']],
    'tipo' => ['Tipo', ['receita' => 'Receita', 'despesa' => 'Despesa']],
    'conta_id' => ['Conta', array_column($accounts, 'nome', 'id')],
    'cartao_id' => ['Cartão', array_column($cards, 'nome', 'id')],
    'grupo_id' => ['Categoria', $groups],
    'subgrupo_id' => ['Subcategoria', array_column(array_map(fn($category) => ['id' => $category['id'], 'nome' => $category['grupo_nome'] . ' · ' . $category['nome']], $categories), 'nome', 'id')],
];
?>
<div class="page-heading">
    <div><p class="eyebrow">SEU DIA A DIA</p><h1><?= $trash ? 'Lixeira' : 'Movimentações' ?></h1><p class="text-secondary"><?= $trash ? 'Restaure um lançamento excluído quando precisar.' : 'Registre, encontre e organize o que entrou e saiu.' ?></p></div>
    <a class="btn btn-uai-primary" href="<?= H::escape($basePath . '/movimentacoes/nova?' . $context) ?>">+ Novo lançamento</a>
</div>
<nav class="quick-filters mb-4" aria-label="Situação dos lançamentos">
    <?php foreach (['Todas' => ['status' => null, 'tipo' => null, 'lixeira' => null], 'A pagar' => ['status' => 'pendente', 'tipo' => 'despesa', 'lixeira' => null], 'A receber' => ['status' => 'pendente', 'tipo' => 'receita', 'lixeira' => null], 'Realizadas' => ['status' => 'efetivada', 'tipo' => null, 'lixeira' => null], 'Lixeira' => ['status' => null, 'tipo' => null, 'lixeira' => '1']] as $label => $changes):
        $active = true;
        foreach ($changes as $key => $value) if (($filters[$key] ?? null) !== $value) $active = false;
    ?>
        <a class="<?= $active ? 'active' : '' ?>" <?= $active ? 'aria-current="page"' : '' ?> href="<?= H::escape($filterUrl($changes)) ?>"><?= $label ?></a>
    <?php endforeach; ?>
</nav>
<form method="get" class="card mb-4">
    <div class="card-body">
        <?php if ($trash): ?><input type="hidden" name="lixeira" value="1"><?php endif; ?>
        <div class="row g-3">
            <div class="col-md-6"><label for="activity-search" class="form-label">Buscar</label><input id="activity-search" name="q" class="form-control" placeholder="Mercado, salário, aluguel..." value="<?= H::escape($filters['q'] ?? '') ?>"></div>
            <div class="col-sm-6 col-md-3"><label for="activity-from" class="form-label">De</label><input id="activity-from" name="data_inicio" type="date" class="form-control" value="<?= H::escape($filters['data_inicio'] ?? '') ?>"></div>
            <div class="col-sm-6 col-md-3"><label for="activity-to" class="form-label">Até</label><input id="activity-to" name="data_fim" type="date" class="form-control" value="<?= H::escape($filters['data_fim'] ?? '') ?>"></div>
        </div>
        <details class="mt-3" <?= array_intersect_key($filters, $selects) ? 'open' : '' ?>>
            <summary>Mais filtros</summary>
            <div class="row g-3 mt-1">
                <?php foreach ($selects as $key => [$label, $options]): ?>
                    <div class="col-sm-6 col-lg-4"><label for="filter-<?= $key ?>" class="form-label"><?= $label ?></label><select id="filter-<?= $key ?>" name="<?= $key ?>" class="form-select"><option value="">Todos</option><?php foreach ($options as $id => $name): ?><option value="<?= H::escape($id) ?>" <?= (string) ($filters[$key] ?? '') === (string) $id ? 'selected' : '' ?>><?= H::escape($name) ?></option><?php endforeach; ?></select></div>
                <?php endforeach; ?>
                <div class="col-sm-6 col-lg-4"><label for="filter-method" class="form-label">Meio de pagamento</label><select id="filter-method" name="meio_pagamento" class="form-select"><option value="">Todos</option><?php foreach (['pix' => 'Pix', 'dinheiro' => 'Dinheiro', 'debito' => 'Débito', 'credito' => 'Crédito', 'boleto' => 'Boleto', 'transferencia' => 'Transferência', 'outro' => 'Outro'] as $key => $label): ?><option value="<?= $key ?>" <?= ($filters['meio_pagamento'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
            </div>
        </details>
        <div class="filter-actions mt-3"><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . '/movimentacoes') ?>">Limpar</a><button class="btn btn-uai-primary">Aplicar filtros</button></div>
    </div>
</form>
<section class="card" id="lista">
    <div class="card-body">
        <div class="section-heading"><h2 class="h5"><?= $total ?> <?= $total === 1 ? 'lançamento' : 'lançamentos' ?></h2><a href="<?= H::escape($basePath . '/movimentacoes/exportar?' . http_build_query($filters)) ?>">Exportar CSV</a></div>
        <?php if (!$entries): ?><div class="empty-state"><h2 class="h5"><?= $trash ? 'A lixeira está vazia' : 'Nenhum lançamento encontrado' ?></h2><p><?= $trash ? 'Lançamentos excluídos aparecerão aqui.' : 'Altere os filtros ou registre um novo lançamento.' ?></p></div><?php endif; ?>
        <?php foreach (array_slice($entries, 0, 50) as $entry):
            $detailUrl = $basePath . '/movimentacoes/' . $entry['id'] . '?' . $context;
            $editUrl = $basePath . '/movimentacoes/' . $entry['id'] . '/editar?' . $context;
            $label = $entry['status'] === 'efetivada' ? ($entry['tipo'] === 'receita' ? 'Recebido' : ($entry['cartao_id'] ? 'No cartão' : 'Pago')) : ($entry['status'] === 'cancelada' ? 'Cancelada' : ($entry['tipo'] === 'receita' ? 'A receber' : 'A pagar'));
        ?>
            <article class="movement-row" id="movimento-<?= (int) $entry['id'] ?>">
                <div class="movement-main">
                    <?php if ($trash): ?><strong><?= H::escape($entry['descricao']) ?></strong><?php else: ?><a class="movement-title" href="<?= H::escape($detailUrl) ?>"><?= H::escape($entry['descricao']) ?></a><?php endif; ?>
                    <small><?= H::escape($entry['grupo_nome'] . ' · ' . $entry['subgrupo_nome']) ?></small>
                    <small><?= H::date($entry['data_competencia']) ?> · <?= H::escape($entry['cartao_nome'] ?? $entry['conta_nome'] ?? 'Conta a definir') ?><?= $entry['parcelamento_id'] ? ' · Parcela ' . (int) $entry['numero_parcela'] . '/' . (int) $entry['total_parcelas'] : ($entry['recorrencia_id'] ? ' · Recorrente' : '') ?></small>
                    <?php if (!$trash && $entry['status'] === 'pendente' && $entry['data_vencimento'] < date('Y-m-d')): ?><small class="text-danger">Venceu em <?= H::date($entry['data_vencimento']) ?></small><?php endif; ?>
                </div>
                <div class="movement-value"><strong class="<?= $entry['tipo'] === 'receita' ? 'text-success' : '' ?>"><?= $entry['tipo'] === 'receita' ? '+' : '−' ?> <?= Money::format((int) $entry['valor_centavos']) ?></strong><span class="status-pill"><?= $trash ? 'Excluída' : $label ?></span><?php if ((int) $entry['estornado'] > 0): ?><small>Estorno: <?= Money::format((int) $entry['estornado']) ?></small><?php endif; ?></div>
                <div class="movement-actions">
                    <?php if ($trash): ?>
                        <form method="post" action="<?= H::escape($basePath . '/movimentacoes/' . $entry['id'] . '/restaurar') ?>" data-confirm="Restaurar este lançamento e seus efeitos nos saldos?"><?= H::fields($csrfToken) ?><input type="hidden" name="versao" value="<?= (int) $entry['versao'] ?>"><button class="btn btn-sm btn-outline-secondary">Restaurar</button></form>
                    <?php else: ?>
                        <?php if ($entry['status'] === 'pendente' && !$entry['cartao_id']): ?><a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($editUrl . '&situacao=efetivada') ?>"><?= $entry['tipo'] === 'receita' ? 'Marcar recebido' : 'Marcar pago' ?></a><?php endif; ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($detailUrl) ?>">Ver</a>
                        <a class="btn btn-sm btn-outline-secondary" href="<?= H::escape($editUrl) ?>">Editar</a>
                        <form method="post" action="<?= H::escape($basePath . '/movimentacoes/' . $entry['id'] . '/excluir') ?>" data-confirm="Enviar este lançamento para a lixeira? Ele e seus estornos sairão dos saldos. As outras parcelas e recorrências serão mantidas."><?= H::fields($csrfToken) ?><input type="hidden" name="versao" value="<?= (int) $entry['versao'] ?>"><input type="hidden" name="_retorno" value="<?= H::escape($returnPath) ?>"><button class="btn btn-sm btn-outline-danger">Excluir</button></form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        <nav class="pagination-links" aria-label="Páginas de movimentações">
            <?php if ($page > 1): ?><a href="<?= H::escape($filterUrl(['pagina' => $page - 1])) ?>#lista">← Anterior</a><?php endif; ?>
            <span>Página <?= $page ?> de <?= max(1, (int) ceil($total / 50)) ?></span>
            <?php if (count($entries) > 50): ?><a href="<?= H::escape($filterUrl(['pagina' => $page + 1])) ?>#lista">Próxima →</a><?php endif; ?>
        </nav>
    </div>
</section>
