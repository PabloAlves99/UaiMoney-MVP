<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;
use PHPMailer\PHPMailer\PHPMailer;

final class PasswordResetMailer
{
    public function __construct(private readonly array $config) {}

    public function send(string $recipient, string $code): void
    {
        $mail = $this->configuredMailer();
        $mail->addAddress($recipient);
        $content = $this->content($code);
        $mail->Subject = 'UaiMoney - Código para redefinir sua senha';
        $mail->isHTML(true);
        $mail->addEmbeddedImage(dirname(__DIR__, 2) . '/public/images/brand/logo-horizontal.png', 'uaimoney-logo', 'uaimoney.png');
        $mail->Body = $content['html'];
        $mail->AltBody = $content['text'];
        $mail->send();
    }

    public function content(string $code): array
    {
        if (!preg_match('/^[0-9]{6}$/D', $code)) throw new RuntimeException('Invalid reset code.');
        $siteUrl = rtrim((string) ($this->config['site_url'] ?? ''), '/');
        $parts = parse_url($siteUrl);
        if (!$parts || !filter_var($siteUrl, FILTER_VALIDATE_URL)
            || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || (($parts['scheme'] ?? '') !== 'https' && !in_array($parts['host'] ?? '', ['localhost', '127.0.0.1'], true))) {
            throw new RuntimeException('Configure a valid HTTPS site_url for password reset emails.');
        }
        // No code or personal data in the URL; opening a link does not consume the code.
        $resetUrl = $siteUrl . '/redefinir-senha';
        ob_start();
        try {
            require dirname(__DIR__) . '/Views/emails/password-reset.php';
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        return [
            'html' => $html,
            'text' => "UaiMoney | Redefinição de senha\r\n\r\nRecebemos uma solicitação para redefinir a senha da sua conta.\r\n\r\nSeu código: {$code}\r\nVálido por 10 minutos a partir da solicitação. Uso único.\r\n\r\nAbra {$resetUrl} e informe seu e-mail, o código acima e sua nova senha.\r\n\r\nSe você não solicitou esta alteração, ignore esta mensagem. Sua senha permanece a mesma. Não compartilhe este código.\r\n\r\nUaiMoney — Controle financeiro",
        ];
    }

    public function configuredMailer(): PHPMailer
    {
        $password = preg_replace('/\s+/', '', (string) ($this->config['password'] ?? ''));
        if ($password === '') throw new RuntimeException('Senha de app ausente na configuração privada de e-mail.');
        foreach (['username', 'from'] as $key) {
            if (!filter_var($this->config[$key] ?? '', FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Invalid mail identity.');
        }
        if (($this->config['host'] ?? '') !== 'smtp.gmail.com' || (int) ($this->config['port'] ?? 0) !== 587) {
            throw new RuntimeException('Gmail requires the configured STARTTLS endpoint.');
        }
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->Port = (int) $this->config['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $this->config['username'];
        $mail->Password = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPDebug = 0;
        $mail->Timeout = 20;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($this->config['from'], $this->config['from_name']);
        return $mail;
    }
}
