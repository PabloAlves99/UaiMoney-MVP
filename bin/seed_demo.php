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
function movement(PDO $pdo, int $userId, array $categories, int $accountId, string $category, string $description, int $amount, string $date, string $payment): void
{
    insert($pdo, 'INSERT INTO transacoes (usuario_id, subgrupo_id, conta_id, descricao, valor_centavos, data_competencia, data_vencimento, data_efetivacao, status, meio_pagamento)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, \'efetivada\', ?)', [
        $userId, $categories[$category], $accountId, $description, $amount,
        $date, $date, $date, $payment,
    ]);
}

/** @param array<string, int> $categories */
function cardPurchase(PDO $pdo, int $userId, array $categories, int $cardId, int $invoiceId, string $dueDate, string $purchaseDate, string $category, string $description, int $amount): void
{
    insert($pdo, 'INSERT INTO transacoes (usuario_id, subgrupo_id, cartao_id, fatura_id, descricao, valor_centavos, data_competencia, data_vencimento, data_efetivacao, status, meio_pagamento, data_compra)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'efetivada\', \'credito\', ?)', [
        $userId, $categories[$category], $cardId, $invoiceId, $description, $amount,
        $purchaseDate, $dueDate, $purchaseDate, $purchaseDate,
    ]);
}

function deleteDemo(PDO $pdo, int $userId): void
{
    // Ordem compatível com as chaves estrangeiras; todos os filtros são do usuário demo.
    foreach ([
        'DELETE FROM historico_movimentacoes WHERE usuario_id = ?',
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
$firstMonth = $today->modify('first day of this month')->modify('-5 months');
$months = [];
for ($index = 0; $index < 6; $index++) {
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

    foreach ($months as $position => $currentMonth) {
        $monthKey = $currentMonth->format('Y-m');
        foreach (['Moradia' => 165000, 'Mercado' => 95000, 'Transporte' => 42000, 'Lazer' => 38000, 'Assinaturas' => 12990] as $category => $limit) {
            insert($pdo, 'INSERT INTO orcamentos (usuario_id, ano_mes, grupo_id, valor_limite_centavos) VALUES (?, ?, ?, ?)', [$userId, $monthKey, $groups['despesa:' . ($category === 'Mercado' ? 'Alimentação' : ($category === 'Transporte' ? 'Transporte' : ($category === 'Lazer' ? 'Lazer' : ($category === 'Assinaturas' ? 'Assinaturas' : 'Moradia'))))], $limit]);
        }

        movement($pdo, $userId, $categories, $checking, 'Salário', 'Salário mensal', 560000 + ($position % 2 ? 12500 : 0), day($currentMonth, 5), 'transferencia');
        movement($pdo, $userId, $categories, $checking, 'Moradia', 'Aluguel', 158000, day($currentMonth, 8), 'pix');
        movement($pdo, $userId, $categories, $checking, 'Assinaturas', 'Internet residencial', 11990, day($currentMonth, 10), 'boleto');
        movement($pdo, $userId, $categories, $checking, 'Mercado', 'Supermercado do bairro', 42800 + ($position * 900), day($currentMonth, 12), 'debito');
        movement($pdo, $userId, $categories, $checking, 'Transporte', 'Combustível', 24500 + ($position % 3 * 1800), day($currentMonth, 16), 'pix');
        movement($pdo, $userId, $categories, $wallet, 'Restaurantes', 'Almoço de sábado', 6500 + ($position * 300), day($currentMonth, 20), 'dinheiro');
        movement($pdo, $userId, $categories, $checking, 'Saúde', $position % 2 ? 'Farmácia' : 'Academia', $position % 2 ? 8900 : 10990, day($currentMonth, 22), 'debito');
        if ($position === 1 || $position === 4) {
            movement($pdo, $userId, $categories, $checking, 'Freelances', 'Projeto de consultoria', 85000, day($currentMonth, 18), 'pix');
        }
        if ($position % 2 === 0) {
            movement($pdo, $userId, $categories, $digital, 'Educação', 'Curso online', 3990, day($currentMonth, 24), 'pix');
        }
    }

    $cardId = insert($pdo, 'INSERT INTO cartoes (usuario_id, conta_pagamento_id, nome, instituicao, limite_centavos, dia_fechamento, dia_vencimento) VALUES (?, ?, ?, ?, ?, 25, 5)', [$userId, $checking, 'Uai Platinum', 'Banco Uai', 450000]);
    foreach ($months as $position => $currentMonth) {
        $competence = $currentMonth->format('Y-m');
        $dueMonth = month($currentMonth, 1);
        $invoiceId = insert($pdo, 'INSERT INTO faturas (usuario_id, cartao_id, competencia, data_fechamento, data_vencimento, status) VALUES (?, ?, ?, ?, ?, ?)', [
            $userId, $cardId, $competence, day($currentMonth, 25), day($dueMonth, 5), $position === 5 ? 'aberta' : 'fechada',
        ]);
        cardPurchase($pdo, $userId, $categories, $cardId, $invoiceId, day($dueMonth, 5), day($currentMonth, 11), 'Mercado', 'Compra no mercado', 23650 + ($position * 550));
        cardPurchase($pdo, $userId, $categories, $cardId, $invoiceId, day($dueMonth, 5), day($currentMonth, 19), 'Lazer', 'Cinema e jantar', 14200 + ($position * 350));
        cardPurchase($pdo, $userId, $categories, $cardId, $invoiceId, day($dueMonth, 5), day($currentMonth, 23), 'Assinaturas', 'Serviço de streaming', 2790);
        if ($position === 2) {
            cardPurchase($pdo, $userId, $categories, $cardId, $invoiceId, day($dueMonth, 5), day($currentMonth, 24), 'Educação', 'Livro técnico', 7990);
        }
        if ($position < 5) {
            $total = (int) $pdo->query('SELECT SUM(valor_centavos) FROM transacoes WHERE fatura_id = ' . $invoiceId)->fetchColumn();
            insert($pdo, 'INSERT INTO pagamentos_fatura (usuario_id, fatura_id, conta_id, valor_centavos, data_pagamento, observacao) VALUES (?, ?, ?, ?, ?, ?)', [$userId, $invoiceId, $checking, $total, day($dueMonth, 5), 'Pagamento integral da fatura']);
        }
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

echo 'Dados de demonstração carregados para os últimos 6 meses (' . $firstMonth->format('m/Y') . ' a ' . $today->format('m/Y') . ').' . PHP_EOL;
echo 'Login: demonstracao' . PHP_EOL;
echo 'Senha: UaiMoneyDemo2026!' . PHP_EOL;
