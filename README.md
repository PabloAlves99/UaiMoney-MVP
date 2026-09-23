# UaiMoney

Controle financeiro pessoal em PHP 8.1+, SQLite, Bootstrap e JavaScript. A versão atual permite cadastro público e uso por várias pessoas, com registros isolados por usuário. A assinatura mensal é um objetivo futuro; cobrança e gestão de planos ainda não estão implementadas.

Contas, categorias, movimentações, parcelamentos, recorrências, cartões, faturas, orçamento, análises, transferências, conferência de saldo e estornos trabalham na mesma base. A interface usa temas claro/escuro e se adapta ao celular.

## Executar

1. Instale PHP 8.1+ com PDO SQLite e mbstring; gere o autoload com `composer install`.
2. Execute `php database/migrate.php`.
3. Configure o Apache para servir `public/`. No ambiente local existente: `http://localhost:8090/uaimoney-mvp/public/`.
4. Use **Criar minha conta** e siga **Primeiros passos**. Novos cadastros recebem o papel `usuario`, nunca administrador.

Em uma instalação existente, execute `php bin/backup.php` antes das migrations. Não exclua nem recrie o banco para atualizar.

As variáveis opcionais `UAIMONEY_DATABASE`, `UAIMONEY_BASE_PATH` e `UAIMONEY_ENV` configuram banco, prefixo da URL e ambiente. Em produção, use `UAIMONEY_ENV=production` e HTTPS. As variáveis são lidas do ambiente do processo; não há carregador de `.env`.

## Verificar

```text
php tests/run.php
php tests/upgrade.php
php database/status.php
```

Os testes financeiros usam SQLite em memória. O teste de atualização parte do schema 017 com lançamentos existentes e verifica preservação de registros, saldo e integridade ao migrar para 019.

- [Implementação, regras e teste HTTP](docs/mvp-implementation.md)
- [Backup, restauração e publicação](docs/operations.md)

A separação Controller → Service → Repository foi mantida. Dinheiro é armazenado em centavos; consumo e caixa possuem consultas próprias para evitar dupla contagem. Não é necessário migrar a stack para validar o produto com poucos usuários.
