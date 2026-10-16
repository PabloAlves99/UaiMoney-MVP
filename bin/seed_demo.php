<?php

declare(strict_types=1);

/*
 * Gera uma base de demonstração isolada para apresentação do produto.
 *
 * Uso:
 *   php bin/seed_demo.php
 *   php bin/seed_demo.php --reset
 *
 * O primeiro comando só cria o usuário quando ele ainda não existe. O segundo
 * recria exclusivamente os dados do usuário demonstracao, sem alterar os
 * demais usuários da instalação.
 */

require_once dirname(__DIR__) . '/vendor/autoload.php';

$reset = in_array('--reset', $argv, true);
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$pdo = $app['pdo'];

if ((int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'migrations'")->fetchColumn() === 0) {
    throw new RuntimeException('Banco ainda não migrado. Execute: php database/migrate.php');
}

$findDemo = $pdo->prepare('SELECT id FROM usuarios WHERE login = :login LIMIT 1');
$findDemo->execute([':login' => 'demonstracao']);
$existingId = $findDemo->fetchColumn();

if ($existingId !== false && !$reset) {
    echo "O usuário de demonstração já existe. Use --reset para recriar somente os dados dele." . PHP_EOL;
    exit(0);
}

/** @param array<string, mixed> $values */
function execute(PDO $pdo, string $sql, array $values = []): void
{
    $statement = $pdo->prepare($sql);
    $statement->execute($values);
}

function insert(PDO $pdo, string $sql, array $values): int
{
    execute($pdo, $sql, $values);
    return (int) $pdo->lastInsertId();
}

function day(DateTimeImmutable $month, int $number): string
{
    return $month->setDate((int) $month->format('Y'), (int) $month->format('m'), min($number, (int) $month->format('t')))->format('Y-m-d');
}

function month(DateTimeImmutable $date, int $offset): DateTimeImmutable
{
    return $date->modify(($offset >= 0 ? '+' : '') . $offset . ' months')->modify('first day of this month');
}

/** @param array<string, int> $categories */
function movement(PDO $pdo, int $userId, array $categories, int $accountId, string $category, string $description, int $amount, string $date, string $payment, string $today): int
{
    $completed = $date <= $today;
    return insert($pdo, 'INSERT INTO transacoes (usuario_id, subgrupo_id, conta_id, descricao, valor_centavos, data_competencia, data_vencimento, data_efetivacao, status, meio_pagamento)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $userId, $categories[$category], $accountId, $description, $amount,
        $date, $date, $completed ? $date : null, $completed ? 'efetivada' : 'pendente', $payment,
    ]);
}

/** @param array<string, int> $categories */
function cardPurchase(PDO $pdo, int $userId, array $categories, int $cardId, int $invoiceId, string $dueDate, string $purchaseDate, string $category, string $description, int $amount, string $today): int
{
    $completed = $purchaseDate <= $today;
    return insert($pdo, 'INSERT INTO transacoes (usuario_id, subgrupo_id, cartao_id, fatura_id, descricao, valor_centavos, data_competencia, data_vencimento, data_efetivacao, status, meio_pagamento, data_compra)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'credito\', ?)', [
        $userId, $categories[$category], $cardId, $invoiceId, $description, $amount,
        $purchaseDate, $dueDate, $completed ? $purchaseDate : null, $completed ? 'efetivada' : 'pendente', $purchaseDate,
    ]);
}

function investmentEvent(PDO $pdo, int $userId, int $investmentId, string $type, int $amount, string $date, string $description): void
{
    insert($pdo, 'INSERT INTO investimento_eventos (usuario_id, investimento_id, tipo, valor_centavos, data, descricao) VALUES (?, ?, ?, ?, ?, ?)',
        [$userId, $investmentId, $type, $amount, $date, $description]);
    $appliedDelta = in_array($type, ['saldo_inicial', 'aporte'], true) ? $amount : ($type === 'retirada' ? $amount : 0);
    execute($pdo, 'UPDATE investimentos SET valor_aplicado_centavos = valor_aplicado_centavos + ?, valor_atual_centavos = valor_atual_centavos + ? WHERE id = ? AND usuario_id = ?',
        [$appliedDelta, $amount, $investmentId, $userId]);
}

function deleteDemo(PDO $pdo, int $userId): void
{
    // Ordem compatível com as chaves estrangeiras; todos os filtros são do usuário demo.
    foreach ([
        'DELETE FROM historico_movimentacoes WHERE usuario_id = ?',
        'DELETE FROM itens_importacao WHERE lote_id IN (SELECT id FROM lotes_importacao WHERE usuario_id = ?)',
        'DELETE FROM lotes_importacao WHERE usuario_id = ?',
        'DELETE FROM projecoes WHERE usuario_id = ?',
        'DELETE FROM objetivo_reservas WHERE usuario_id = ?',
        'DELETE FROM investimento_eventos WHERE usuario_id = ?',
        'DELETE FROM historico_patrimonio WHERE usuario_id = ?',
        'DELETE FROM metas_financeiras WHERE usuario_id = ?',
        'DELETE FROM investimentos WHERE usuario_id = ?',
        'DELETE FROM estornos WHERE usuario_id = ?',
        'DELETE FROM pagamentos_fatura WHERE usuario_id = ?',
        'DELETE FROM creditos_fatura WHERE usuario_id = ?',
        'DELETE FROM compartilhamentos_conta WHERE usuario_id = ?',
        'DELETE FROM operacoes WHERE usuario_id = ?',
        'DELETE FROM orcamentos WHERE usuario_id = ?',
        'DELETE FROM conferencias WHERE usuario_id = ?',
        'DELETE FROM transferencias WHERE usuario_id = ?',
        'DELETE FROM transacoes WHERE usuario_id = ?',
        'DELETE FROM faturas WHERE usuario_id = ?',
        'DELETE FROM cartoes WHERE usuario_id = ?',
        'DELETE FROM parcelamentos WHERE usuario_id = ?',
        'DELETE FROM recorrencias WHERE usuario_id = ?',
        'DELETE FROM contas WHERE usuario_id = ?',
        'DELETE FROM grupos WHERE usuario_id = ?',
        'DELETE FROM usuarios WHERE id = ?',
    ] as $sql) {
        execute($pdo, $sql, [$userId]);
    }
}

$today = new DateTimeImmutable('today');
$todayDate = $today->format('Y-m-d');
$firstMonth = $today->setDate((int) $today->format('Y'), 1, 1);
$months = [];
for ($index = 0; $index < 12; $index++) {
    $months[] = month($firstMonth, $index);
}

$pdo->beginTransaction();

try {
    if ($existingId !== false) {
        deleteDemo($pdo, (int) $existingId);
    }

    $userId = insert($pdo, 'INSERT INTO usuarios (nome, login, email, senha_hash, tipo, tema) VALUES (?, ?, ?, ?, \'usuario\', \'light\')', [
        'Conta Demonstração', 'demonstracao', 'demonstracao@uaimoney.local', password_hash('UaiMoneyDemo2026!', PASSWORD_DEFAULT),
    ]);

    $openingDate = $firstMonth->modify('-1 day')->format('Y-m-d');
    $checking = insert($pdo, 'INSERT INTO contas (usuario_id, nome, tipo, instituicao, saldo_inicial_centavos, saldo_inicial_em) VALUES (?, ?, \'corrente\', ?, ?, ?)', [$userId, 'Conta principal', 'Banco Uai', 385000, $openingDate]);
    $wallet = insert($pdo, 'INSERT INTO contas (usuario_id, nome, tipo, instituicao, saldo_inicial_centavos, saldo_inicial_em) VALUES (?, ?, \'dinheiro\', NULL, ?, ?)', [$userId, 'Carteira', 18000, $openingDate]);
    $digital = insert($pdo, 'INSERT INTO contas (usuario_id, nome, tipo, instituicao, saldo_inicial_centavos, saldo_inicial_em) VALUES (?, ?, \'carteira_digital\', ?, ?, ?)', [$userId, 'Reserva digital', 'Uai Invest', 120000, $openingDate]);

    $definition = [
        'Salário' => ['Receitas', 'receita'], 'Freelances' => ['Receitas', 'receita'],
        'Moradia' => ['Moradia', 'despesa'], 'Mercado' => ['Alimentação', 'despesa'],
        'Restaurantes' => ['Alimentação', 'despesa'], 'Transporte' => ['Transporte', 'despesa'],
        'Saúde' => ['Saúde', 'despesa'], 'Lazer' => ['Lazer', 'despesa'],
        'Assinaturas' => ['Assinaturas', 'despesa'], 'Educação' => ['Educação', 'despesa'],
        'Viagem' => ['Viagens', 'despesa'], 'Seguros' => ['Proteção', 'despesa'],
    ];
    $groups = [];
    $categories = [];
    foreach ($definition as $name => [$groupName, $type]) {
        $groupKey = $type . ':' . $groupName;
        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = insert($pdo, 'INSERT INTO grupos (usuario_id, nome, tipo) VALUES (?, ?, ?)', [$userId, $groupName, $type]);
        }
        $categories[$name] = insert($pdo, 'INSERT INTO subgrupos (grupo_id, nome, descricao) VALUES (?, ?, ?)', [$groups[$groupKey], $name, 'Categoria da conta de demonstração']);
    }
    foreach (['Moradia' => 'essencial', 'Alimentação' => 'essencial', 'Transporte' => 'essencial', 'Saúde' => 'essencial', 'Assinaturas' => 'variavel', 'Lazer' => 'variavel', 'Educação' => 'variavel'] as $groupName => $classification) {
        execute($pdo, 'UPDATE grupos SET classificacao=? WHERE id=?', [$classification, $groups['despesa:' . $groupName]]);
    }

    foreach ($months as $position => $currentMonth) {
        $monthKey = $currentMonth->format('Y-m');
        foreach (['Moradia' => 165000, 'Mercado' => 95000, 'Transporte' => 42000, 'Lazer' => 38000, 'Assinaturas' => 12990] as $category => $limit) {
            insert($pdo, 'INSERT INTO orcamentos (usuario_id, ano_mes, grupo_id, valor_limite_centavos) VALUES (?, ?, ?, ?)', [$userId, $monthKey, $groups['despesa:' . ($category === 'Mercado' ? 'Alimentação' : ($category === 'Transporte' ? 'Transporte' : ($category === 'Lazer' ? 'Lazer' : ($category === 'Assinaturas' ? 'Assinaturas' : 'Moradia'))))], $limit]);
        }

        movement($pdo, $userId, $categories, $checking, 'Salário', 'Salário mensal', 560000 + ($position % 2 ? 12500 : 0), day($currentMonth, 5), 'transferencia', $todayDate);
        movement($pdo, $userId, $categories, $checking, 'Moradia', 'Aluguel', 158000, day($currentMonth, 8), 'pix', $todayDate);
        movement($pdo, $userId, $categories, $checking, 'Assinaturas', 'Internet residencial', 11990, day($currentMonth, 10), 'boleto', $todayDate);
        $marketId = movement($pdo, $userId, $categories, $checking, 'Mercado', 'Supermercado do bairro', 42800 + ($position * 900), day($currentMonth, 12), 'debito', $todayDate);
        movement($pdo, $userId, $categories, $checking, 'Transporte', 'Combustível', 24500 + ($position % 3 * 1800), day($currentMonth, 16), 'pix', $todayDate);
        movement($pdo, $userId, $categories, $wallet, 'Restaurantes', 'Almoço de sábado', 6500 + ($position * 300), day($currentMonth, 20), 'dinheiro', $todayDate);
        movement($pdo, $userId, $categories, $checking, 'Saúde', $position % 2 ? 'Farmácia' : 'Academia', $position % 2 ? 8900 : 10990, day($currentMonth, 22), 'debito', $todayDate);
        if ($position === 1 || $position === 4) {
            movement($pdo, $userId, $categories, $checking, 'Freelances', 'Projeto de consultoria', 85000, day($currentMonth, 18), 'pix', $todayDate);
        }
        if ($position % 2 === 0) {
            movement($pdo, $userId, $categories, $digital, 'Educação', 'Curso online', 3990, day($currentMonth, 24), 'pix', $todayDate);
        }
        if ($position === 2 && day($currentMonth, 12) <= $todayDate) {
            $lot = insert($pdo, 'INSERT INTO lotes_importacao (usuario_id) VALUES (?)', [$userId]);
            execute($pdo, 'INSERT INTO itens_importacao (lote_id, transacao_id) VALUES (?, ?)', [$lot, $marketId]);
        }
        if ($position === 3 && day($currentMonth, 12) <= $todayDate) {
            insert($pdo, 'INSERT INTO estornos (usuario_id, transacao_id, valor_centavos, data, motivo) VALUES (?, ?, ?, ?, ?)', [$userId, $marketId, 3500, day($currentMonth, 15), 'Produto devolvido']);
        }
    }
    if (day($months[2], 21) <= $todayDate) {
        insert($pdo, 'INSERT INTO transferencias (usuario_id, origem_id, destino_id, valor_centavos, data, descricao) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $checking, $digital, 50000, day($months[2], 21), 'Reforço da reserva digital']);
    }
    $archivedMovement = movement($pdo, $userId, $categories, $checking, 'Lazer', 'Compra duplicada para restaurar', 3900, day($firstMonth, 17), 'pix', $todayDate);
    execute($pdo, "UPDATE transacoes SET status_antes_exclusao=status,status='cancelada',excluida_em=CURRENT_TIMESTAMP WHERE id=?", [$archivedMovement]);

    $cardId = insert($pdo, 'INSERT INTO cartoes (usuario_id, conta_pagamento_id, nome, instituicao, limite_centavos, dia_fechamento, dia_vencimento) VALUES (?, ?, ?, ?, ?, 25, 5)', [$userId, $checking, 'Uai Platinum', 'Banco Uai', 450000]);
    insert($pdo, 'INSERT INTO cartoes (usuario_id, conta_pagamento_id, nome, instituicao, limite_centavos, dia_fechamento, dia_vencimento, ativo) VALUES (?, ?, ?, ?, ?, 20, 1, 0)', [$userId, $checking, 'Cartão antigo', 'Banco Uai', 100000]);
    foreach ($months as $position => $currentMonth) {
        $competence = $currentMonth->format('Y-m');
        $dueMonth = month($currentMonth, 1);
        $invoiceId = insert($pdo, 'INSERT INTO faturas (usuario_id, cartao_id, competencia, data_fechamento, data_vencimento, status) VALUES (?, ?, ?, ?, ?, ?)', [
            $userId, $cardId, $competence, day($currentMonth, 25), day($dueMonth, 5), day($currentMonth, 25) <= $todayDate ? 'fechada' : 'aberta',
        ]);
        cardPurchase($pdo, $userId, $categories, $cardId, $invoiceId, day($dueMonth, 5), day($currentMonth, 11), 'Mercado', 'Compra no mercado', 23650 + ($position * 550), $todayDate);
        cardPurchase($pdo, $userId, $categories, $cardId, $invoiceId, day($dueMonth, 5), day($currentMonth, 19), 'Lazer', 'Cinema e jantar', 14200 + ($position * 350), $todayDate);
        cardPurchase($pdo, $userId, $categories, $cardId, $invoiceId, day($dueMonth, 5), day($currentMonth, 23), 'Assinaturas', 'Serviço de streaming', 2790, $todayDate);
        if ($position === 2) {
            cardPurchase($pdo, $userId, $categories, $cardId, $invoiceId, day($dueMonth, 5), day($currentMonth, 24), 'Educação', 'Livro técnico', 7990, $todayDate);
        }
        if (day($dueMonth, 5) <= $todayDate) {
            $total = (int) $pdo->query('SELECT SUM(valor_centavos) FROM transacoes WHERE fatura_id = ' . $invoiceId)->fetchColumn();
            insert($pdo, 'INSERT INTO pagamentos_fatura (usuario_id, fatura_id, conta_id, valor_centavos, data_pagamento, observacao) VALUES (?, ?, ?, ?, ?, ?)', [$userId, $invoiceId, $checking, $total, day($dueMonth, 5), 'Pagamento integral da fatura']);
        }
    }

    $investments = [];
    foreach ([
        ['Tesouro Selic 2029', 'renda_fixa', 'Tesouro Direto', 'Reserva para imprevistos', 145000, 20000, 8500],
        ['ETF de ações globais', 'renda_variavel', 'Corretora Uai', 'Patrimônio de longo prazo', 56000, 10000, 6500],
        ['Fundo de renda fixa', 'fundo', 'Banco Uai', 'Viagem planejada', 18000, 5000, 7000],
        ['Previdência antiga', 'previdencia', 'Banco Uai', 'Exemplo arquivado', 10000, 0, 0],
    ] as [$name, $type, $institution, $purpose, $opening, $monthly, $rate]) {
        $id = insert($pdo, 'INSERT INTO investimentos (usuario_id, nome, tipo, instituicao, objetivo, data_inicio, aporte_mensal_centavos, taxa_referencia_quatro_casas) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $name, $type, $institution, $purpose, $firstMonth->format('Y-m-d'), $monthly, $rate]);
        investmentEvent($pdo, $userId, $id, 'saldo_inicial', $opening, day($firstMonth, 2), 'Posição inicial fictícia');
        $investments[$name] = $id;
    }
    execute($pdo, 'UPDATE investimentos SET ativo=0 WHERE id=?', [$investments['Previdência antiga']]);
    foreach ($months as $position => $currentMonth) {
        foreach (['Tesouro Selic 2029' => 20000, 'ETF de ações globais' => 10000, 'Fundo de renda fixa' => 5000] as $name => $amount) {
            if ($position > 0 && day($currentMonth, 7) <= $todayDate) {
                investmentEvent($pdo, $userId, $investments[$name], 'aporte', $amount, day($currentMonth, 7), 'Aporte mensal confirmado');
            }
            if (day($currentMonth, 28) <= $todayDate) {
                $yield = $name === 'ETF de ações globais' && $position === 5 ? -2200 : ($name === 'Tesouro Selic 2029' ? 1550 + 70 * $position : 450 + 35 * $position);
                investmentEvent($pdo, $userId, $investments[$name], 'rendimento', $yield, day($currentMonth, 28), 'Rendimento mensal confirmado');
            }
        }
    }
    if (day($months[6], 14) <= $todayDate) {
        investmentEvent($pdo, $userId, $investments['Fundo de renda fixa'], 'retirada', -4000, day($months[6], 14), 'Retirada para despesas de viagem');
    }
    if (day($months[8], 18) <= $todayDate) {
        investmentEvent($pdo, $userId, $investments['Tesouro Selic 2029'], 'aporte', 12000, day($months[8], 18), 'Aporte extra');
    }

    $goals = [];
    foreach ([
        ['Seguro desemprego', 'reserva', 1800000, 'alta', 'medio', 'Proteção de seis meses de despesas', 50000, 24],
        ['Viagem de férias', 'viagem', 180000, 'media', 'curto', 'Viagem anual sem comprometer a reserva', 15000, 10],
        ['Entrada do imóvel', 'imovel', 3000000, 'media', 'longo', 'Planejar a entrada do primeiro imóvel', 45000, 36],
        ['Curso concluído', 'educacao', 15000, 'baixa', 'curto', 'Exemplo de objetivo arquivado', 0, 1],
    ] as [$name, $type, $target, $priority, $horizon, $purpose, $monthly, $deadline]) {
        $goals[$name] = insert($pdo, 'INSERT INTO metas_financeiras (usuario_id, nome, tipo, valor_meta_centavos, data_alvo, prioridade, horizonte, finalidade, aporte_mensal_centavos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $name, $type, $target, $today->modify('+' . $deadline . ' months')->format('Y-m-d'), $priority, $horizon, $purpose, $monthly]);
    }
    foreach ([
        [$goals['Seguro desemprego'], 'investimento', $investments['Tesouro Selic 2029'], 120000],
        [$goals['Viagem de férias'], 'investimento', $investments['Fundo de renda fixa'], 18000],
        [$goals['Entrada do imóvel'], 'investimento', $investments['ETF de ações globais'], 50000],
        [$goals['Seguro desemprego'], 'conta', $digital, 100000],
    ] as [$goalId, $sourceType, $sourceId, $amount]) {
        insert($pdo, 'INSERT INTO objetivo_reservas (usuario_id, objetivo_id, fonte_tipo, fonte_id, valor_centavos) VALUES (?, ?, ?, ?, ?)', [$userId, $goalId, $sourceType, $sourceId, $amount]);
        execute($pdo, 'UPDATE metas_financeiras SET valor_atual_centavos=valor_atual_centavos+? WHERE id=?', [$amount, $goalId]);
    }
    execute($pdo, 'UPDATE metas_financeiras SET ativo=0 WHERE id=?', [$goals['Curso concluído']]);

    foreach ([
        ['Quando alcanço a reserva?', 'objetivo', null, $goals['Seguro desemprego'], 50000, 8500, 'mensal', null, 0],
        ['Viagem: prazo estimado', 'objetivo', null, $goals['Viagem de férias'], 15000, 7000, 'mensal', null, 0],
        ['ETF até R$ 50 mil', 'investimento', $investments['ETF de ações globais'], null, 10000, 8500, 'mensal', 5000000, 0],
        ['Simulação livre para notebook', 'livre', null, null, 25000, 6000, 'mensal', 150000, 20000],
    ] as [$name, $source, $investmentId, $goalId, $monthly, $rate, $period, $target, $initial]) {
        insert($pdo, 'INSERT INTO projecoes (usuario_id, nome, origem_tipo, investimento_id, objetivo_id, aporte_mensal_centavos, taxa_quatro_casas, periodicidade_taxa, valor_meta_centavos, valor_inicial_livre_centavos) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $name, $source, $investmentId, $goalId, $monthly, $rate, $period, $target, $initial]);
    }

    insert($pdo, 'INSERT INTO recorrencias (usuario_id, subgrupo_id, conta_id, descricao, valor_centavos, frequencia, intervalo, data_inicio, meio_pagamento, proxima_ocorrencia, agenda_base) VALUES (?, ?, ?, ?, ?, \'mensal\', 1, ?, ?, ?, ?)',
        [$userId, $categories['Seguros'], $checking, 'Seguro residencial', 8900, day($firstMonth, 15), 'boleto', day($months[min(11, (int) $today->format('n'))], 15), day($firstMonth, 15)]);
    insert($pdo, 'INSERT INTO recorrencias (usuario_id, subgrupo_id, conta_id, descricao, valor_centavos, frequencia, intervalo, data_inicio, meio_pagamento, ativo, proxima_ocorrencia, agenda_base) VALUES (?, ?, ?, ?, ?, \'mensal\', 1, ?, ?, 0, NULL, ?)',
        [$userId, $categories['Assinaturas'], $checking, 'Revista digital pausada', 2990, day($firstMonth, 20), 'debito', day($firstMonth, 20)]);

    execute($pdo, 'UPDATE orcamentos SET ativo=0 WHERE usuario_id=? AND ano_mes=? AND grupo_id=?', [$userId, $months[0]->format('Y-m'), $groups['despesa:Lazer']]);
    execute($pdo, 'UPDATE grupos SET ativo=0 WHERE id=?', [$groups['despesa:Viagens']]);
    execute($pdo, 'UPDATE subgrupos SET ativo=0 WHERE id=?', [$categories['Viagem']]);

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

echo 'Dados de demonstração carregados para o ano inteiro (' . $firstMonth->format('Y') . '), com lançamentos futuros pendentes.' . PHP_EOL;
echo 'Login: demonstracao' . PHP_EOL;
echo 'Senha: UaiMoneyDemo2026!' . PHP_EOL;
