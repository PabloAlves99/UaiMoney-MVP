<?php

declare(strict_types=1);
require_once __DIR__ . '/app/Core/Database.php';

use App\Core\Database;

$config = require __DIR__ . '/config/database.php';

$database = new Database($config);

$pdo = $database->connect();
echo 'Conexão realizada com sucesso.';