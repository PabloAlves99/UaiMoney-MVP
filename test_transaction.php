<?php

declare(strict_types=1);

require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Database;

$config = require __DIR__ . '/config/database.php';

$database = new Database($config);

$pdo = $database->connect();

try {

    $pdo->beginTransaction();


    /*
     * Usuário
     */

    $stmt = $pdo->prepare("
        INSERT INTO usuarios (
            nome,
            login,
            email,
            senha_hash
        )
        VALUES (
            :nome,
            :login,
            :email,
            :senha_hash
        )
    ");

    $stmt->execute([
        ':nome' => 'Usuário Teste',
        ':login' => 'teste_transacao',
        ':email' => 'transacao@uaimoney.local',
        ':senha_hash' => 'hash_teste'
    ]);

    $usuarioId = (int) $pdo->lastInsertId();


    /*
     * Conta
     */

    $stmt = $pdo->prepare("
        INSERT INTO contas (
            usuario_id,
            nome,
            tipo,
            saldo_inicial_centavos
        )
        VALUES (
            :usuario_id,
            :nome,
            :tipo,
            :saldo
        )
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':nome' => 'Nubank',
        ':tipo' => 'corrente',
        ':saldo' => 100000
    ]);

    $contaId = (int) $pdo->lastInsertId();


    /*
     * Grupo
     */

    $stmt = $pdo->prepare("
        INSERT INTO grupos (
            usuario_id,
            nome,
            tipo
        )
        VALUES (
            :usuario_id,
            :nome,
            :tipo
        )
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':nome' => 'Alimentação',
        ':tipo' => 'despesa'
    ]);

    $grupoId = (int) $pdo->lastInsertId();


    /*
     * Subgrupo
     */

    $stmt = $pdo->prepare("
        INSERT INTO subgrupos (
            grupo_id,
            nome
        )
        VALUES (
            :grupo_id,
            :nome
        )
    ");

    $stmt->execute([
        ':grupo_id' => $grupoId,
        ':nome' => 'Mercado'
    ]);

    $subgrupoId = (int) $pdo->lastInsertId();


    /*
     * Transação
     */

    $stmt = $pdo->prepare("
        INSERT INTO transacoes (
            usuario_id,
            subgrupo_id,
            conta_id,
            descricao,
            valor_centavos,
            data_competencia,
            data_vencimento,
            data_efetivacao,
            status,
            meio_pagamento
        )
        VALUES (
            :usuario_id,
            :subgrupo_id,
            :conta_id,
            :descricao,
            :valor,
            :competencia,
            :vencimento,
            :efetivacao,
            :status,
            :meio_pagamento
        )
    ");

    $stmt->execute([
        ':usuario_id' => $usuarioId,
        ':subgrupo_id' => $subgrupoId,
        ':conta_id' => $contaId,
        ':descricao' => 'Compra no supermercado',
        ':valor' => 25990,
        ':competencia' => '2026-09-01',
        ':vencimento' => '2026-09-12',
        ':efetivacao' => '2026-09-12',
        ':status' => 'efetivada',
        ':meio_pagamento' => 'pix'
    ]);

    $transacaoId = (int) $pdo->lastInsertId();


    /*
     * Consulta completa
     */

    $stmt = $pdo->prepare("
        SELECT
            t.id,
            t.descricao,
            t.valor_centavos,
            t.status,

            g.tipo,
            g.nome AS grupo,

            s.nome AS subgrupo,

            c.nome AS conta

        FROM transacoes t

        INNER JOIN subgrupos s
            ON s.id = t.subgrupo_id

        INNER JOIN grupos g
            ON g.id = s.grupo_id

        LEFT JOIN contas c
            ON c.id = t.conta_id

        WHERE t.id = :id
    ");

    $stmt->execute([
        ':id' => $transacaoId
    ]);

    print_r(
        $stmt->fetch()
    );


    /*
     * Teste não deve persistir dados
     */

    $pdo->rollBack();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo $e->getMessage() . PHP_EOL;
}