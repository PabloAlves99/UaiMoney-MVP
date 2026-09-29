# Atualização na Hostinger — identidade visual e recuperação de senha

URL pública: https://uaimoney.cloud/UaiMoney-MVP/public/
Botão do e-mail: https://uaimoney.cloud/UaiMoney-MVP/public/redefinir-senha

## Configuração de produção (substitui a orientação anterior de mail.local.php na hospedagem)

Use esta estrutura, preservando os diretórios existentes do domínio:

```text
pasta-do-dominio/
  private/
    uaimoney.php                 ← configuração privada com a senha
  public_html/
    UaiMoney-MVP/                ← aplicação
```

1. Crie a pasta `private` ao lado de `public_html`, não dentro dela.
2. Copie o modelo `config/production.example.php` para `private/uaimoney.php`. Preencha a senha de app somente nessa cópia privada. Ambiente, Gmail, URL e base de rotas já estão preparados para este domínio.
3. O carregador procura a pasta ancestral `public_html` e lê o arquivo irmão `private/uaimoney.php`, tanto pela web quanto pelo terminal. Nenhuma configuração no painel de variáveis é necessária nesse layout. Se o caminho for diferente, defina `UAIMONEY_CONFIG_FILE` com o caminho absoluto do arquivo privado. Não use caminhos relativos.
4. Restrinja a leitura do arquivo ao usuário que executa o PHP (permissão 600, quando compatível com o ambiente). Não coloque a senha no modelo versionado, em ZIP de atualização ou na pasta pública.
5. Na raiz da aplicação, execute `php bin/check_config.php`. Esse comando verifica configuração, URL, rotas e extensões sem enviar e-mail e sem revelar a senha. Depois execute `php bin/test_mail.php` para testar a entrega.

No computador, o `config/mail.local.php` existente continua funcionando. Ele não deve ser enviado para a hospedagem. Em produção, o arquivo externo tem preferência; variáveis explícitas `UAIMONEY_MAIL_PASSWORD`, `UAIMONEY_SITE_URL`, `UAIMONEY_ENV` e `UAIMONEY_BASE_PATH` prevalecem sobre os arquivos. `.env` não é carregado.

## O que mudou

- Identidade v10, logo transparente e temas claro/escuro em azul institucional.
- Link “Esqueci minha senha” no login, solicitação de código, confirmação e formulário de nova senha.
- Após solicitar, a página mostra apenas a confirmação: o acesso à redefinição está no botão do e-mail. O código ainda precisa ser informado; abrir o link não o consome.
- E-mail HTML com logo incorporada, botão, código destacado, instruções e versão em texto. A logo não depende de uma imagem remota, embora clientes possam bloquear imagens.
- Gmail autenticado via PHPMailer/STARTTLS. Código de uso único por 10 minutos, hash no banco, limites por IP/e-mail e cinco tentativas por código.
- Sessões invalidadas após mudança de senha; usuários com sessões anteriores à implantação precisarão entrar novamente.

## Arquivos a substituir ou adicionar

Os caminhos são relativos à raiz do projeto na hospedagem. Envie mantendo a estrutura.

### Recuperação de senha e envio

- `app/Controllers/AuthController.php`
- `app/Services/AuthService.php`
- `app/Services/PasswordResetService.php`
- `app/Services/PasswordResetMailer.php`
- `app/Views/auth/login.php`
- `app/Views/auth/password-reset.php`
- `app/Views/emails/password-reset.php`
- `routes/web.php`
- `public/index.php`
- `config/mail.php`
- `config/app.php`
- `config/production.example.php` (modelo sem senha)
- `app/Core/DeploymentConfig.php`
- `config/.htaccess`
- `database/migrations/026_password_resets.php`
- `composer.json`
- `composer.lock`

### Identidade visual, caso ainda não tenha sido enviada

- `app/Views/layouts/app.php`
- `app/Views/layouts/auth.php`
- `app/Views/layouts/brand.php`
- `app/Views/layouts/public.php`
- `public/css/app.css`
- `public/images/brand/logo-horizontal.png`
- `public/images/brand/logo-light.png`
- `public/images/brand/logo-dark.png`
- `public/images/brand/favicon.png`
- `public/images/brand/icon.png`
- `public/images/brand/v10/` (originais da marca; opcionais para execução)

### Apoio e verificação

- `.gitignore`
- `bin/test_mail.php`
- `bin/check_config.php`
- `tests/deployment_config.php`
- `tests/password_reset.php`
- `tests/password_reset_email.php`
- `docs/password-reset.md`
- `docs/hostinger-update.md`

## Ordem de implantação

1. Faça backup dos arquivos da hospedagem e do banco atual. Preserve `storage/`, o banco SQLite, as sessões e configurações locais; não substitua esses dados pelos do computador.
2. Envie os arquivos listados. Preserve o `.htaccess` da raiz que bloqueia config, storage, vendor e demais pastas internas. Se sua hospedagem usa outra estrutura de diretórios, mantenha o mapeamento existente para `public/`.
3. Na raiz do projeto, execute `composer install --no-dev --optimize-autoloader`. Se não houver terminal, envie também a pasta `vendor/` completa desta instalação (incluindo `vendor/composer/`, não somente PHPMailer). PHP deve ter OpenSSL e PDO SQLite habilitados.
4. Prepare `private/uaimoney.php` fora de `public_html`, conforme a seção de configuração de produção acima. Não envie `config/mail.local.php`.
5. Confirme `site_url` no arquivo privado: `https://uaimoney.cloud/UaiMoney-MVP/public`. Não inclua `/redefinir-senha` nesse valor.
6. Confirme `app.base_path` como `/UaiMoney-MVP/public` e `app.environment` como `production` no arquivo privado. Envie o novo `config/app.php`, que lê esses valores. Execute `php bin/check_config.php` antes de testar o fluxo.
7. Execute `php database/migrate.php` na raiz do projeto para criar a tabela de recuperação. Não substitua o arquivo do banco. Sem terminal/SSH, execute pelo terminal disponível no plano ou peça ao suporte para executar; não exponha migrations pela web.
8. Execute `php bin/test_mail.php` para testar o transporte. Para conferir o novo e-mail HTML, solicite a recuperação com uma conta cadastrada pelo site. A mensagem de teste do terminal é simples e não contém um código real.
9. Confirme: solicitação mostra apenas confirmação; e-mail chega com logo/código/botão; botão abre a URL correta; nova senha funciona; código usado não funciona novamente.

Esta atualização foi preparada localmente. O envio dos arquivos e a migração na hospedagem precisam ser feitos no ambiente da Hostinger.
