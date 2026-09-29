<?php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\RateLimiter;
use App\Services\PasswordResetService;

$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec('CREATE TABLE usuarios(id INTEGER PRIMARY KEY,email TEXT,ativo INTEGER,senha_hash TEXT,atualizado_em TEXT);
    CREATE TABLE tentativas_acesso(chave TEXT PRIMARY KEY,tentativas INTEGER,inicio INTEGER)');
(require dirname(__DIR__) . '/database/migrations/026_password_resets.php')($pdo);
(require dirname(__DIR__) . '/database/migrations/027_bind_password_reset_links.php')($pdo);
$pdo->prepare('INSERT INTO usuarios VALUES(1,?,1,?,NULL)')->execute(['user@example.test', password_hash('OldPassword123', PASSWORD_DEFAULT)]);
$messages = [];
$service = new PasswordResetService($pdo, new RateLimiter($pdo), static function ($email, $code, $token) use (&$messages): void { $messages[] = [$email, $code, $token]; });
$checks = 0;
function check(bool $condition): void { global $checks; if (!$condition) throw new RuntimeException('Failed check ' . ($checks + 1)); $checks++; }
function rejected(Closure $action): void { try { $action(); } catch (DomainException $e) { check(true); return; } throw new RuntimeException('Expected rejection'); }
$service->request('missing@example.test', 'test');
check(count($messages) === 0);
$service->request('USER@example.test', 'test');
$code = $messages[0][1];
$token = $messages[0][2];
$row = $pdo->query('SELECT * FROM password_resets')->fetch();
check($messages[0][0] === 'user@example.test');
check($row['code_hash'] !== $code && password_verify($code, $row['code_hash']));
check(abs((int)$row['expires_at'] - time() - 600) <= 1);
check($row['token_hash'] === hash('sha256', $token) && $row['token_hash'] !== $token);
check($service->validLink($token));
check($service->validLink($token)); // Email scanners/opening the page must not consume it.
check(!$service->validLink('') && !$service->validLink('user@example.test'));
rejected(fn() => $service->reset(str_repeat('0', 64), $code, 'NewPassword123', 'NewPassword123', 'test'));
rejected(fn() => $service->reset($token, '000000', 'NewPassword123', 'NewPassword123', 'test'));
check((int)$pdo->query('SELECT attempts FROM password_resets')->fetchColumn() === 1);
rejected(fn() => $service->reset($token, $code, 'NewPassword123', 'different', 'test'));
$service->reset($token, $code, 'NewPassword123', 'NewPassword123', 'test');
check(password_verify('NewPassword123', $pdo->query('SELECT senha_hash FROM usuarios')->fetchColumn()));
check((int)$pdo->query('SELECT COUNT(*) FROM password_resets')->fetchColumn() === 0);
check(!$service->validLink($token));
rejected(fn() => $service->reset($token, $code, 'OtherPassword123', 'OtherPassword123', 'test'));
$service->request('user@example.test', 'test');
$code = $messages[1][1];
$token = $messages[1][2];
$pdo->exec('UPDATE password_resets SET expires_at=' . time());
rejected(fn() => $service->reset($token, $code, 'OtherPassword123', 'OtherPassword123', 'test'));
$previousHash = $pdo->query('SELECT code_hash FROM password_resets')->fetchColumn();
$oldToken = $token;
$service->request('user@example.test', 'test');
$code = $messages[2][1];
$token = $messages[2][2];
check($previousHash !== $pdo->query('SELECT code_hash FROM password_resets')->fetchColumn());
check(!$service->validLink($oldToken) && $service->validLink($token));
$pdo->exec('UPDATE password_resets SET attempts=5');
rejected(fn() => $service->reset($token, $code, 'OtherPassword123', 'OtherPassword123', 'test'));
rejected(fn() => $service->request('user@example.test', 'test'));
$pdo->exec('DELETE FROM tentativas_acesso');
$failing = new PasswordResetService($pdo, new RateLimiter($pdo), static function (): void { throw new RuntimeException('transport down'); });
$failing->request('user@example.test', 'test');
check((int)$pdo->query('SELECT COUNT(*) FROM password_resets')->fetchColumn() === 0);
$pdo->exec('UPDATE usuarios SET ativo=0');
$service->request('user@example.test', 'test');
check(count($messages) === 3);

// A code from another account cannot authorize the account bound to this link.
$pdo->exec('DELETE FROM tentativas_acesso; UPDATE usuarios SET ativo=1');
$pdo->prepare('INSERT INTO usuarios VALUES(2,?,1,?,NULL)')->execute(['other@example.test', password_hash('OtherOriginal123', PASSWORD_DEFAULT)]);
$service->request('user@example.test', 'test');
$firstToken = $messages[3][2];
$service->request('other@example.test', 'test');
$secondToken = $messages[4][2];
// Deterministic, distinct codes avoid random collisions in the isolation test.
$pdo->prepare('UPDATE password_resets SET code_hash=? WHERE usuario_id=1')->execute([password_hash('111111', PASSWORD_DEFAULT)]);
$pdo->prepare('UPDATE password_resets SET code_hash=? WHERE usuario_id=2')->execute([password_hash('222222', PASSWORD_DEFAULT)]);
rejected(fn() => $service->reset($firstToken, '222222', 'ChangedPassword123', 'ChangedPassword123', 'test'));
$service->reset($firstToken, '111111', 'ChangedPassword123', 'ChangedPassword123', 'test');
check(password_verify('ChangedPassword123', $pdo->query('SELECT senha_hash FROM usuarios WHERE id=1')->fetchColumn()));
check(password_verify('OtherOriginal123', $pdo->query('SELECT senha_hash FROM usuarios WHERE id=2')->fetchColumn()));
check($service->validLink($secondToken));
echo "OK: {$checks} verificações de recuperação de senha.\n";
