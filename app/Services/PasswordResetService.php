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
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare('INSERT INTO password_resets(usuario_id,code_hash,expires_at,attempts) VALUES(?,?,?,0)
            ON CONFLICT(usuario_id) DO UPDATE SET code_hash=excluded.code_hash,expires_at=excluded.expires_at,attempts=0');
        $stmt->execute([$user['id'], $hash, time() + 600]);
        try {
            ($this->sendMail)($user['email'], $code);
        } catch (Throwable $e) {
            $this->pdo->prepare('DELETE FROM password_resets WHERE usuario_id=? AND code_hash=?')->execute([$user['id'], $hash]);
            // Never log the recipient, code or transport exception (which may contain secrets).
            error_log('Password reset email delivery failed. Check mail transport configuration.');
        }
    }

    public function reset(string $email, string $code, string $password, string $confirmation, string $ip): void
    {
        $email = $this->email($email);
        $this->limiter->hit('reset-verify-ip', $ip, 30);
        $this->limiter->hit('reset-verify-email', $email, 10);
        if (mb_strlen($password) < 8) throw new DomainException('A senha deve possuir pelo menos 8 caracteres.');
        if (strlen($password) > 72) throw new DomainException('Esta senha é muito longa. Use uma senha mais curta.');
        if ($password !== $confirmation) throw new DomainException('As senhas não conferem.');

        // Reserve the SQLite writer before reading so concurrent requests cannot reuse a code.
        $this->pdo->exec('BEGIN IMMEDIATE');
        try {
            $stmt = $this->pdo->prepare('SELECT r.*,u.senha_hash FROM password_resets r JOIN usuarios u ON u.id=r.usuario_id WHERE u.email=? COLLATE NOCASE AND u.ativo=1');
            $stmt->execute([$email]);
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
