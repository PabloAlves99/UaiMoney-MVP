<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Database;

$config = require dirname(__DIR__) . '/config/database.php';

$database = new Database($config);

$pdo = $database->connect();


/*
|--------------------------------------------------------------------------
| Tabela de controle das migrations
|--------------------------------------------------------------------------
*/

$pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL UNIQUE,
                batch INTEGER NOT NULL,
                executed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
");


/*
|--------------------------------------------------------------------------
| Migrations já executadas
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT migration
    FROM migrations
    ORDER BY id
");

$executedMigrations = $stmt->fetchAll(
    PDO::FETCH_COLUMN
);


/*
|--------------------------------------------------------------------------
| Buscar arquivos de migration
|--------------------------------------------------------------------------
*/

$migrationFiles = glob(
    __DIR__ . '/migrations/*.php'
);

if ($migrationFiles === false) {
    throw new RuntimeException(
        'Não foi possível localizar as migrations.'
    );
}

sort($migrationFiles, SORT_STRING);


/*
|--------------------------------------------------------------------------
| Definir o batch atual
|--------------------------------------------------------------------------
*/

$lastBatch = (int) $pdo
    ->query("
        SELECT COALESCE(MAX(batch), 0)
        FROM migrations
    ")
    ->fetchColumn();

$currentBatch = $lastBatch + 1;


/*
|--------------------------------------------------------------------------
| Executar migrations pendentes
|--------------------------------------------------------------------------
*/

$executedCount = 0;

foreach ($migrationFiles as $file) {

    $migrationName = basename($file);

    if (
        in_array(
            $migrationName,
            $executedMigrations,
            true
        )
    ) {
        continue;
    }

    echo "Executando: {$migrationName}" . PHP_EOL;

    $migration = require $file;

    if (!is_callable($migration)) {
        throw new RuntimeException(
            "A migration {$migrationName} não é executável."
        );
    }

    try {

        $pdo->beginTransaction();

        $migration($pdo);

        $stmt = $pdo->prepare("
            INSERT INTO migrations (
                migration,
                batch
            )
            VALUES (
                :migration,
                :batch
            )
        ");

        $stmt->execute([
            ':migration' => $migrationName,
            ':batch' => $currentBatch
        ]);

        $pdo->commit();

        $executedCount++;

        echo "Concluída: {$migrationName}" . PHP_EOL;

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo PHP_EOL;
        echo "Erro na migration: {$migrationName}" . PHP_EOL;
        echo $e->getMessage() . PHP_EOL;

        exit(1);
    }
}


/*
|--------------------------------------------------------------------------
| Resultado
|--------------------------------------------------------------------------
*/

if ($executedCount === 0) {

    echo "Nenhuma migration pendente." . PHP_EOL;

} else {

    echo PHP_EOL;

    echo "{$executedCount} migration(s) executada(s) com sucesso."
        . PHP_EOL;
}