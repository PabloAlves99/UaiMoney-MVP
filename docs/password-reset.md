# Recuperação de senha

O link **Esqueci minha senha** no login abre `/esqueci-senha`. O código enviado ao e-mail cadastrado tem seis dígitos, expira em 600 segundos, permite cinco tentativas e é consumido na troca da senha. Um reenvio substitui o código anterior. Há limites adicionais por e-mail e IP. Códigos são armazenados somente como hash.

Depois da solicitação, o site mostra apenas “Confira seu e-mail”. A mensagem HTML inclui a logo incorporada, o código e um botão para `https://uaimoney.cloud/UaiMoney-MVP/public/redefinir-senha?token=...`. O link contém um identificador aleatório de 256 bits, armazenado apenas como hash e vinculado à conta solicitante. A tela pede somente código e nova senha; nenhum e-mail ou ID enviado pelo formulário determina a conta. Configure o endereço público em `site_url` no `config/mail.php` ou em `UAIMONEY_SITE_URL`.

A migration `027_bind_password_reset_links.php` é obrigatória. Links anteriores à atualização deixam de funcionar e exigem uma nova solicitação. Reenvio, expiração e consumo invalidam o link; abrir o link não o consome. Não compartilhe links nem registre a query string de recuperação em ferramentas de analytics ou logs de acesso.

Para atualizar a hospedagem, consulte `docs/hostinger-update.md`, com a lista completa de arquivos e a ordem de implantação.

## Instalação

**Hostinger/produção:** siga `docs/hostinger-update.md`. A configuração deve ficar em `private/uaimoney.php`, fora de `public_html`, usando o modelo `config/production.example.php`. As instruções de `mail.local.php` abaixo aplicam-se ao computador de desenvolvimento.

1. Execute `composer install` e `php database/migrate.php` em uma nova instalação.
2. O remetente é `PabloHAlves99@gmail.com`. Abra `config/mail.local.php` e cole a nova senha de app do Google entre as aspas de `password`. Salve. Espaços são removidos automaticamente. Nunca use a senha normal da conta.
3. O envio usa PHPMailer com autenticação SMTP e STARTTLS em `smtp.gmail.com:587`, com validação de certificado. O arquivo local está fora do Git e o acesso à pasta config é bloqueado pelo Apache. Em outro servidor, mantenha essa proteção e sirva somente public/. Use HTTPS na aplicação.
4. Execute `php bin/test_mail.php` para enviar uma mensagem de teste ao próprio remetente. Depois solicite um código com uma conta cadastrada e confirme a troca da senha. A senha local é lida a cada requisição.

Como alternativa, configure `UAIMONEY_MAIL_PASSWORD` no ambiente; variáveis explícitas têm precedência sobre os arquivos. Um `.env` não é carregado automaticamente. Nunca versione credenciais de e-mail. Falhas de envio são registradas sem endereço nem código no log PHP; o navegador mantém a mesma mensagem genérica para evitar revelar contas cadastradas. A aceitação pelo transporte não garante entrega final.

Sessões autenticadas são vinculadas à versão da senha e perdem acesso após a troca. Sessões anteriores à implantação desta proteção precisarão entrar novamente.

Teste isolado, sem envio de e-mail real: `php tests/password_reset.php`.
