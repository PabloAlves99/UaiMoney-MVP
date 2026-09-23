# Operação do UaiMoney

## Atualização local

Preserve o banco existente. Execute:

```text
php bin/backup.php
php database/migrate.php
php database/status.php
php tests/run.php
php tests/upgrade.php
```

As migrations 018 e 019 adicionam caixa/consumo, orçamento, estornos, proteção de reenvios, limitação de tentativas e créditos entre faturas. Não recriam tabelas existentes nem reescrevem lançamentos antigos.

## Backup

`php bin/backup.php` cria um arquivo com data e identificador aleatório em `storage/backups/`. Usa `VACUUM INTO`, incluindo dados confirmados no WAL, e valida o resultado com `PRAGMA integrity_check`. Uma cópia simples do `.sqlite` durante o uso pode perder dados ainda no WAL.

O backup contém dados financeiros e credenciais em hash. Guarde cópias fora do servidor, em armazenamento privado, com retenção definida. O script é manual no ambiente local; agendamento, retenção e cópia externa devem ser configurados no servidor de publicação.

## Restauração

1. Coloque a aplicação em manutenção e pare acessos e processos de recorrências.
2. Faça um backup adicional do banco atual.
3. Valide a integridade do arquivo escolhido.
4. Com todas as conexões fechadas, preserve banco, WAL e SHM atuais em um diretório de recuperação. Substitua o banco pelo backup; não reaproveite WAL/SHM do banco anterior.
5. Ajuste as permissões para a conta que executa o PHP.
6. Execute migrations pendentes e confira login, saldos, faturas e movimentações antes de liberar o acesso.

Ensaie a restauração em ambiente separado antes de depender dela em produção. O código não realiza restauração destrutiva automaticamente.

## Apache e publicação

Use `public/` como DocumentRoot. Se o projeto inteiro estiver em `htdocs`, o `.htaccess` da raiz bloqueia diretórios privados e listagem de arquivos. Isso depende de `AllowOverride` e `mod_rewrite`.

Confira que URLs de `storage/`, `config/`, `vendor/`, `database/`, `tests/`, backups, sessões e `.git/` não são acessíveis. Mantenha o banco fora do diretório público e permita escrita somente onde necessária.

Configure `UAIMONEY_ENV=production`, HTTPS e o prefixo em `UAIMONEY_BASE_PATH`. Cookies são HTTP-only/SameSite e exigem transporte seguro em produção. Sessões ficam em `storage/sessions/`; logs em `storage/logs/`. Exceções não expõem SQL ou caminhos na resposta; argumentos são omitidos dos rastros de exceção.

SQLite usa foreign keys, WAL e busy timeout. Valide com a concorrência esperada e considere PostgreSQL conforme a utilização crescer. Nunca exponha o servidor embutido de testes do PHP à internet.

O cadastro público está em `/criar-conta`; o cadastro administrativo original continua protegido. Limites de tentativas estão implementados. Verificação de e-mail e recuperação de senha ainda precisam de uma etapa própria antes do lançamento comercial.

## Recorrências

No ambiente local, use o botão de processamento em Recorrências. O CLI existente `bin/process_recurring.php` pode ser agendado no servidor após revisar seu escopo e opções. O serviço é idempotente por recorrência/vencimento.

## Conferência de uma entrega

- Cadastro, login, logout e primeiros passos.
- Conta, categoria, receita, despesa e extrato de caixa.
- Parcelamento no dia 31 e divisão de centavos.
- Processamento repetido de recorrência, pausa/retomada e edição do futuro.
- Compra antes/no fechamento; limite comprometido e parcelas nas faturas.
- Pagamento parcial/integral e estorno com crédito em fatura posterior.
- Transferência e ajuste de saldo sem mudança nas despesas.
- Orçamento, filtros, CSV e ausência de dupla contagem.
- IDs de outro usuário e POST sem CSRF.
- Celular, temas e teclado.

Os testes locais não substituem a validação da publicação, do provedor de cobrança e da operação do servidor.
