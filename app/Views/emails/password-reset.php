<?php use App\Core\Html as H; ?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Redefina sua senha — UaiMoney</title></head>
<body style="margin:0;padding:0;background:#f4f7fa;color:#172b3a;font-family:Arial,Helvetica,sans-serif;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Seu código de recuperação do UaiMoney é válido por 10 minutos.</div>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fa;">
<tr><td align="center" style="padding:32px 16px;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #e0e6ec;border-radius:12px;">
<tr><td style="padding:32px 28px 24px;border-bottom:1px solid #e0e6ec;">
<img src="cid:uaimoney-logo" alt="UaiMoney — Controle Financeiro" width="240" style="display:block;width:240px;max-width:100%;height:auto;border:0;">
</td></tr>
<tr><td style="padding:32px 28px;">
<p style="margin:0 0 12px;font-size:11px;letter-spacing:2px;font-weight:bold;color:#627286;">ACESSO À SUA CONTA</p>
<h1 style="margin:0 0 20px;font-size:26px;line-height:1.25;font-weight:600;color:#123e63;">Redefina sua senha</h1>
<p style="margin:0 0 24px;font-size:15px;line-height:1.7;">Recebemos uma solicitação para redefinir a senha da sua conta no UaiMoney. Use o código abaixo para continuar.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef2f6;border:1px solid #e0e6ec;border-radius:8px;">
<tr><td align="center" style="padding:20px 12px;">
<p style="margin:0 0 8px;font-size:12px;color:#627286;">SEU CÓDIGO DE VERIFICAÇÃO</p>
<p style="margin:0;font-family:Consolas,monospace;font-size:34px;letter-spacing:7px;font-weight:bold;color:#123e63;"><?= H::escape($code) ?></p>
<p style="margin:10px 0 0;font-size:12px;color:#627286;">Válido por 10 minutos · Uso único</p>
</td></tr></table>
<p style="margin:24px 0;font-size:15px;line-height:1.7;">Clique no botão e informe seu e-mail, este código e a nova senha. A validade começa no momento da solicitação.</p>
<table role="presentation" cellspacing="0" cellpadding="0"><tr><td bgcolor="#123e63" style="border-radius:6px;text-align:center;">
<a href="<?= H::escape($resetUrl) ?>" style="display:inline-block;padding:15px 24px;background:#123e63;border:1px solid #123e63;border-radius:6px;color:#ffffff;font-size:15px;font-weight:bold;text-decoration:none;">Redefinir minha senha</a>
</td></tr></table>
<p style="margin:24px 0 0;font-size:12px;line-height:1.7;color:#627286;">Se o botão não funcionar, copie e cole este endereço no navegador:<br><a href="<?= H::escape($resetUrl) ?>" style="color:#123e63;word-break:break-all;"><?= H::escape($resetUrl) ?></a></p>
</td></tr>
<tr><td style="padding:24px 28px;border-top:1px solid #e0e6ec;font-size:13px;line-height:1.7;color:#627286;">Não solicitou esta alteração? Ignore este e-mail. Sua senha permanece a mesma. Para proteger sua conta, não compartilhe este código.</td></tr>
</table>
<p style="margin:20px 0 0;font-size:12px;color:#627286;">UaiMoney · Controle financeiro</p>
</td></tr></table>
</body></html>
