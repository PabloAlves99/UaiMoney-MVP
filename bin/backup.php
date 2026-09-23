<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/vendor/autoload.php';
$config = require dirname(__DIR__) . '/config/database.php';
$pdo = (new App\Core\Database($config))->connect();
$dir = dirname(__DIR__) . '/storage/backups';
if (!is_dir($dir))
    mkdir($dir, 0770, true);
$file = $dir . '/uaimoney-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.sqlite';
// VACUUM INTO produces a consistent snapshot, including committed WAL data.
$pdo->exec('VACUUM INTO ' . $pdo->quote($file));
$check = new PDO('sqlite:' . $file);
if ($check->query('PRAGMA integrity_check')->fetchColumn() !== 'ok')
    throw new RuntimeException('Falha ao validar backup.');
echo 'Backup íntegro criado: ' . $file . PHP_EOL;
