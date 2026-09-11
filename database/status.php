<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Database.php';

use App\Core\Database;

$config = require dirname(__DIR__) . '/config/database.php';

$database = new Database($config);

$pdo = $database->connect();


/*
|--------------------------------------------------------------------------
| Verifica se a tabela migrations existe
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT name
    FROM sqlite_master
    WHERE type = 'table'
      AND name = 'migrations'
");

$migrationsTableExists = $stmt->fetchColumn();


if (!$migrationsTableExists) {

    echo "A tabela de migrations ainda não existe." . PHP_EOL;
    echo "Execute: php database/migrate.php" . PHP_EOL;

    exit(0);
}


/*
|--------------------------------------------------------------------------
| Migrations executadas
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
| Arquivos disponíveis
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
| Exibir status
|--------------------------------------------------------------------------
*/

echo PHP_EOL;

echo str_pad(
    'Migration',
    50
);

echo 'Status' . PHP_EOL;

echo str_repeat('-', 65) . PHP_EOL;


foreach ($migrationFiles as $file) {

    $migrationName = basename($file);

    $executed = in_array(
        $migrationName,
        $executedMigrations,
        true
    );

    echo str_pad(
        $migrationName,
        50
    );

    echo $executed
        ? 'Executada'
        : 'Pendente';

    echo PHP_EOL;
}

echo PHP_EOL;