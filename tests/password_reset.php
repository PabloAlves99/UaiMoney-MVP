<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\RateLimiter;
use App\Services\PasswordResetService;

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE usuarios(id INTEGER PRIMARY KEY,email TEXT,ativo INTEGER,senha_hash TEXT,atualizado_em TEXT);
    CREATE TABLE tentativas_acesso(chave TEXT PRIMARY KEY,tentativas INTEGER,inicio INTEGER)');
(require dirname(__DIR__) . '/database/migrations/026_password_resets.php')($pdo);
$pdo->prepare('INSERT INTO usuarios VALUES(1,?,1,?,NULL)')->execute(['user@example.test', password_hash('OldPassword123', PASSWORD_DEFAULT)]);
$messages = [];
$service = new PasswordResetService($pdo, new RateLimiter($pdo), static function ($email, $code) use (&$messages): void { $messages[] = [$email, $code]; });
$checks = 0;
function check(bool $condition): void { global $checks; if (!$condition) throw new RuntimeException('Failed check ' . ($checks + 1)); $checks++; }
function rejected(Closure $action): void { try { $action(); } catch (DomainException $e) { check(true); return; } throw new RuntimeException('Expected rejection'); }
$service->request('missing@example.test', 'test');
check(count($messages) === 0);
$service->request('USER@example.test', 'test');
$code = $messages[0][1];
$row = $pdo->query('SELECT * FROM password_resets')->fetch();
check($messages[0][0] === 'user@example.test');
check($row['code_hash'] !== $code && password_verify($code, $row['code_hash']));
check(abs((int)$row['expires_at'] - time() - 600) <= 1);
rejected(fn() => $service->reset('user@example.test', '000000', 'NewPassword123', 'NewPassword123', 'test'));
check((int)$pdo->query('SELECT attempts FROM password_resets')->fetchColumn() === 1);
rejected(fn() => $service->reset('user@example.test', $code, 'NewPassword123', 'different', 'test'));
$service->reset('user@example.test', $code, 'NewPassword123', 'NewPassword123', 'test');
check(password_verify('NewPassword123', $pdo->query('SELECT senha_hash FROM usuarios')->fetchColumn()));
check((int)$pdo->query('SELECT COUNT(*) FROM password_resets')->fetchColumn() === 0);
rejected(fn() => $service->reset('user@example.test', $code, 'OtherPassword123', 'OtherPassword123', 'test'));
$service->request('user@example.test', 'test');
$code = $messages[1][1];
$pdo->exec('UPDATE password_resets SET expires_at=' . time());
rejected(fn() => $service->reset('user@example.test', $code, 'OtherPassword123', 'OtherPassword123', 'test'));
$previousHash = $pdo->query('SELECT code_hash FROM password_resets')->fetchColumn();
$service->request('user@example.test', 'test');
$code = $messages[2][1];
check($previousHash !== $pdo->query('SELECT code_hash FROM password_resets')->fetchColumn());
$pdo->exec('UPDATE password_resets SET attempts=5');
rejected(fn() => $service->reset('user@example.test', $code, 'OtherPassword123', 'OtherPassword123', 'test'));
rejected(fn() => $service->request('user@example.test', 'test'));
$pdo->exec('DELETE FROM tentativas_acesso');
$failing = new PasswordResetService($pdo, new RateLimiter($pdo), static function (): void { throw new RuntimeException('transport down'); });
$failing->request('user@example.test', 'test');
check((int)$pdo->query('SELECT COUNT(*) FROM password_resets')->fetchColumn() === 0);
$pdo->exec('UPDATE usuarios SET ativo=0');
$service->request('user@example.test', 'test');
check(count($messages) === 3);
echo "OK: {$checks} verificações de recuperação de senha.\n";
