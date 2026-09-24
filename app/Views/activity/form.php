<?php

use App\Core\Html as H;

$editing = $movement !== null;
$values = array_replace($movement ?? [], $old);
$value = fn($key, $default = '') => $values[$key] ?? $default;
$isCard = $editing && !empty($movement['cartao_id']);
$method = $value('meio_pagamento', !empty($_GET['cartao_id']) ? 'credito' : 'pix');
$mode = $value('modo', $_GET['modo'] ?? 'simples');
$type = $value('tipo', 'despesa');
$status = $value('status', 'efetivada');
if ($editing && !$old && ($_GET['situacao'] ?? '') === 'efetivada' && !$isCard) $status = 'efetivada';
$amount = $old['valor'] ?? ($editing ? number_format((int) $movement['valor_centavos'] / 100, 2, ',', '') : '');
?>
<a class="back-link" href="<?= H::escape($basePath . $returnPath . '#lista') ?>">← Voltar às movimentações</a>
<div class="page-heading">
    <div>
        <p class="eyebrow">SEU CONTROLE, DO SEU JEITO</p>
        <h1><?= $editing ? 'Editar movimentação' : 'Novo lançamento' ?></h1>
        <p class="text-secondary"><?= $editing ? 'Corrija as informações deste lançamento.' : 'Registre o que entrou ou saiu, sem complicar.' ?></p>
    </div>
</div>
<form method="post" action="<?= H::escape($basePath . '/movimentacoes' . ($editing ? '/' . $movement['id'] . '/editar' : '')) ?>" class="card movement-form" data-movement-form data-editing="<?= $editing ? '1' : '0' ?>" data-card="<?= $isCard ? '1' : '0' ?>">
    <?= H::fields($csrfToken) ?>
    <input type="hidden" name="_retorno" value="<?= H::escape($returnPath) ?>">
    <?php if ($editing): ?><input type="hidden" name="versao" value="<?= (int) $value('versao') ?>"><?php endif; ?>
    <div class="card-body">
        <?php if ($editing && ($movement['parcelamento_id'] || $movement['recorrencia_id'])): ?>
            <div class="alert alert-info">A alteração vale apenas para <?= $movement['parcelamento_id'] ? 'esta parcela' : 'esta ocorrência' ?>. As demais permanecem como foram cadastradas.</div>
        <?php endif; ?>
        <?php if ($isCard): ?><p class="small text-secondary">Compra em <?= H::escape($movement['cartao_nome']) ?>. Correções atualizam o total da fatura; pagamentos já registrados são preservados. Para trocar a fatura, use o detalhe do lançamento.</p><?php endif; ?>
        <div class="row g-3">
            <div class="col-sm-4">
                <label for="movement-type" class="form-label">O que você vai registrar?</label>
                <select id="movement-type" name="tipo" class="form-select" data-movement-type>
                    <option value="despesa" <?= $type === 'despesa' ? 'selected' : '' ?>>Despesa</option>
                    <?php if (!$isCard): ?><option value="receita" <?= $type === 'receita' ? 'selected' : '' ?>>Receita</option><?php endif; ?>
                </select>
            </div>
            <div class="col-sm-4">
                <label for="movement-value" class="form-label">Valor<?= !$editing && $mode === 'parcelado' ? ' total' : '' ?> (R$)</label>
                <input id="movement-value" name="valor" class="form-control movement-amount" inputmode="decimal" required value="<?= H::escape($amount) ?>" placeholder="0,00" autofocus>
            </div>
            <?php if (!$editing): ?>
                <div class="col-sm-4">
                    <label for="movement-mode" class="form-label">Como lançar?</label>
                    <select id="movement-mode" name="modo" class="form-select" data-movement-mode>
                        <?php foreach (['simples' => 'Uma vez', 'parcelado' => 'Parcelado', 'recorrente' => 'Repetir'] as $key => $label): ?><option value="<?= $key ?>" <?= $mode === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-6">
                <label for="movement-category" class="form-label">Categoria</label>
                <select id="movement-category" name="subgrupo_id" class="form-select" required data-movement-category>
                    <option value="">Selecione</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>" data-type="<?= H::escape($category['tipo']) ?>" <?= (int) $value('subgrupo_id', 0) === (int) $category['id'] ? 'selected' : '' ?>><?= H::escape($category['grupo_nome'] . ' · ' . $category['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="movement-description" class="form-label">Descrição<?= !$editing ? ' (opcional)' : '' ?></label>
                <input id="movement-description" name="descricao" class="form-control" maxlength="160" <?= $editing ? 'required' : '' ?> value="<?= H::escape($value('descricao')) ?>" placeholder="Ex.: Almoço de hoje">
                <?php if (!$editing): ?><small class="form-text">Se deixar em branco, usaremos o nome da subcategoria.</small><?php endif; ?>
            </div>
            <?php if (!$isCard): ?>
                <div class="col-md-6">
                    <label for="movement-method" class="form-label">Meio de pagamento</label>
                    <select id="movement-method" name="meio_pagamento" class="form-select" data-movement-method>
                        <?php foreach (['pix' => 'Pix', 'debito' => 'Débito', 'dinheiro' => 'Dinheiro', 'credito' => 'Cartão de crédito', 'boleto' => 'Boleto', 'transferencia' => 'Transferência para terceiros', 'outro' => 'Outro'] as $key => $label): if ($editing && $key === 'credito') continue; ?><option value="<?= $key ?>" <?= $method === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6" data-movement-section="account">
                    <label for="movement-account" class="form-label">Conta</label>
                    <select id="movement-account" name="conta_id" class="form-select">
                        <option value="">Selecione</option>
                        <?php foreach ($accounts as $account): ?><option value="<?= (int) $account['id'] ?>" <?= (int) $value('conta_id', $_GET['conta_id'] ?? 0) === (int) $account['id'] ? 'selected' : '' ?>><?= H::escape($account['nome']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <?php if (!$editing): ?>
                <div class="col-md-6" data-movement-section="card">
                    <label for="movement-card" class="form-label">Cartão</label>
                    <select id="movement-card" name="cartao_id" class="form-select"><option value="">Selecione</option><?php foreach ($cards as $card): ?><option value="<?= (int) $card['id'] ?>" <?= (int) $value('cartao_id', $_GET['cartao_id'] ?? 0) === (int) $card['id'] ? 'selected' : '' ?>><?= H::escape($card['nome']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="col-sm-6" data-movement-section="installment"><label for="movement-count" class="form-label">Número de parcelas</label><input id="movement-count" name="parcelas" type="number" min="2" max="120" class="form-control" value="<?= H::escape($value('parcelas', 2)) ?>"><small class="form-text">Informe o valor total da compra acima.</small></div>
                <div class="col-sm-6" data-movement-section="recurring"><label for="movement-frequency" class="form-label">Repetir a cada</label><select id="movement-frequency" name="frequencia" class="form-select"><?php foreach (['mensal' => 'Mês', 'semanal' => 'Semana', 'anual' => 'Ano'] as $key => $label): ?><option value="<?= $key ?>" <?= $value('frequencia', 'mensal') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="col-sm-6" data-movement-section="recurring"><label for="movement-occurrences" class="form-label">Quantidade de ocorrências (opcional)</label><input id="movement-occurrences" name="ocorrencias" type="number" min="1" class="form-control" value="<?= H::escape($value('ocorrencias')) ?>" placeholder="Sem data para terminar"></div>
                <div class="col-md-6"><label for="movement-date" class="form-label" data-movement-date-label>Data</label><input id="movement-date" name="data" type="date" class="form-control" required value="<?= H::escape($value('data', date('Y-m-d'))) ?>"></div>
            <?php endif; ?>
            <div class="col-md-6" data-movement-section="status">
                <label for="movement-status" class="form-label">Situação</label>
                <select id="movement-status" name="status" class="form-select" data-movement-status>
                    <option value="efetivada" <?= $status === 'efetivada' ? 'selected' : '' ?>><?= $isCard ? 'Compra registrada' : ($type === 'receita' ? 'Já recebi' : 'Já paguei') ?></option>
                    <?php if (!$isCard): ?><option value="pendente" <?= $status === 'pendente' ? 'selected' : '' ?>><?= $type === 'receita' ? 'Ainda vou receber' : 'Ainda vou pagar' ?></option><?php endif; ?>
                    <?php if ($editing): ?><option value="cancelada" <?= $status === 'cancelada' ? 'selected' : '' ?>>Cancelada</option><?php endif; ?>
                </select>
            </div>
            <?php if ($editing): ?>
                <div class="col-md-4"><label for="movement-competence" class="form-label">Data usada nas análises</label><input id="movement-competence" name="data_competencia" type="date" required class="form-control" value="<?= H::escape($value('data_competencia')) ?>"></div>
                <div class="col-md-4"><label for="movement-due" class="form-label">Vencimento</label><input id="movement-due" name="data_vencimento" type="date" required <?= $isCard ? 'readonly' : '' ?> class="form-control" value="<?= H::escape($value('data_vencimento')) ?>"></div>
                <div class="col-md-4"><label for="movement-paid" class="form-label"><?= $isCard ? 'Data original da compra' : 'Data do pagamento ou recebimento' ?></label><input id="movement-paid" name="data_efetivacao" type="date" max="<?= date('Y-m-d') ?>" <?= $isCard ? 'readonly' : '' ?> class="form-control" value="<?= H::escape($value('data_efetivacao') ?: date('Y-m-d')) ?>"></div>
            <?php endif; ?>
            <div class="col-12">
                <details <?= $old ? 'open' : '' ?>>
                    <summary>Mais opções</summary>
                    <div class="row g-3 mt-1">
                        <?php if (!$editing): ?>
                            <div class="col-md-6" data-movement-section="competence"><label for="movement-competence" class="form-label">Data usada nas análises (opcional)</label><input id="movement-competence" name="data_competencia" type="date" class="form-control" value="<?= H::escape($value('data_competencia')) ?>"><small class="form-text">Deixe em branco para usar a data do lançamento.</small></div>
                            <div class="col-md-6" data-movement-section="card"><label for="movement-invoice" class="form-label">Primeira fatura (opcional)</label><input id="movement-invoice" name="primeira_fatura" type="month" class="form-control" value="<?= H::escape($value('primeira_fatura')) ?>"><small class="form-text">Mês do fechamento. Deixe em branco para calcular pelo cartão.</small></div>
                        <?php endif; ?>
                        <div class="col-12"><label for="movement-notes" class="form-label">Observações</label><textarea id="movement-notes" name="observacao" maxlength="500" class="form-control" rows="2"><?= H::escape($value('observacao')) ?></textarea></div>
                    </div>
                </details>
            </div>
        </div>
        <p class="small text-secondary mt-3" data-movement-help><?= $editing ? 'Os saldos e as análises acompanham as alterações salvas.' : 'Você pode revisar todos os dados antes de salvar.' ?></p>
    </div>
    <div class="form-actions"><a class="btn btn-outline-secondary" href="<?= H::escape($basePath . $returnPath . '#lista') ?>">Cancelar</a><button class="btn btn-uai-primary"><?= $editing ? 'Salvar alterações' : 'Salvar lançamento' ?></button></div>
</form>
