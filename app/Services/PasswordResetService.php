<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\RateLimiter;
use Closure;
use DomainException;
use PDO;
use Throwable;

final class PasswordResetService
{
    public function __construct(private readonly PDO $pdo, private readonly RateLimiter $limiter, private readonly Closure $sendMail) {}

    public function request(string $email, string $ip): void
    {
        $email = $this->email($email);
        $this->limiter->hit('reset-send-ip', $ip, 20);
        $this->limiter->hit('reset-send-email', $email, 3);
        $stmt = $this->pdo->prepare('SELECT id,email FROM usuarios WHERE email=? COLLATE NOCASE AND ativo=1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return;

        $code = (string) random_int(100000, 999999);
        $token = bin2hex(random_bytes(32));
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO password_resets(usuario_id,code_hash,expires_at,attempts,token_hash) VALUES(?,?,?,0,?)
            ON CONFLICT(usuario_id) DO UPDATE SET code_hash=excluded.code_hash,expires_at=excluded.expires_at,attempts=0,token_hash=excluded.token_hash');
        $stmt->execute([$user['id'], $hash, time() + 600, hash('sha256', $token)]);
        try {
            ($this->sendMail)($user['email'], $code, $token);
        } catch (Throwable $e) {
            $this->pdo->prepare('DELETE FROM password_resets WHERE usuario_id=? AND code_hash=?')->execute([$user['id'], $hash]);
            // Never log the recipient, code or transport exception (which may contain secrets).
            error_log('Password reset email delivery failed. Check mail transport configuration.');
        }
    }

    public function validLink(string $token): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
        $stmt = $this->pdo->prepare('SELECT 1 FROM password_resets r JOIN usuarios u ON u.id=r.usuario_id
            WHERE r.token_hash=? AND r.expires_at>? AND r.attempts<5 AND u.ativo=1');
        $stmt->execute([hash('sha256', $token), time()]);
        return (bool) $stmt->fetchColumn();
    }

    public function reset(string $token, string $code, string $password, string $confirmation, string $ip): void
    {
        $this->limiter->hit('reset-verify-ip', $ip, 30);
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) throw new DomainException('Link inválido ou expirado. Solicite um novo e-mail.');
        $this->limiter->hit('reset-verify-link', $token, 10);
        if (mb_strlen($password) < 8) throw new DomainException('A senha deve possuir pelo menos 8 caracteres.');
        if (strlen($password) > 72) throw new DomainException('Esta senha é muito longa. Use uma senha mais curta.');
        if ($password !== $confirmation) throw new DomainException('As senhas não conferem.');

        // Reserve the SQLite writer before reading so concurrent requests cannot reuse a code.
        $this->pdo->exec('BEGIN IMMEDIATE');
        try {
            $stmt = $this->pdo->prepare('SELECT r.* FROM password_resets r JOIN usuarios u ON u.id=r.usuario_id WHERE r.token_hash=? AND u.ativo=1');
            $stmt->execute([hash('sha256', $token)]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            $valid = $reset && (int) $reset['expires_at'] > time() && (int) $reset['attempts'] < 5;
            if ($valid) {
                $this->pdo->prepare('UPDATE password_resets SET attempts=attempts+1 WHERE usuario_id=?')->execute([$reset['usuario_id']]);
                $valid = preg_match('/^[0-9]{6}$/D', $code) && password_verify($code, $reset['code_hash']);
            }
            if (!$valid) {
                $this->pdo->exec('COMMIT');
                throw new DomainException('Código inválido ou expirado. Solicite um novo código se necessário.');
            }
            $this->pdo->prepare('UPDATE usuarios SET senha_hash=?,atualizado_em=CURRENT_TIMESTAMP WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['usuario_id']]);
            $this->pdo->prepare('DELETE FROM password_resets WHERE usuario_id=?')->execute([$reset['usuario_id']]);
            $this->pdo->exec('COMMIT');
        } catch (Throwable $e) {
            // SQL-started transactions are not consistently reported by PDO::inTransaction.
            if (!$e instanceof DomainException) $this->pdo->exec('ROLLBACK');
            throw $e;
        }
    }

    private function email(string $email): string
    {
        $email = strtolower(trim($email));
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new DomainException('Informe um e-mail válido.');
        return $email;
    }
}
