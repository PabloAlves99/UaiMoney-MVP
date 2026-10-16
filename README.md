# UaiMoney

Controle financeiro pessoal em PHP 8.1+, SQLite, Bootstrap e JavaScript. A versão atual permite uso por várias pessoas, com registros isolados por usuário. Administradores cadastram novos usuários; não há cadastro público. A assinatura mensal é um objetivo futuro; cobrança e gestão de planos ainda não estão implementadas.

Contas, categorias, movimentações, parcelamentos, recorrências, cartões, faturas, orçamento, análises, transferências, conferência de saldo e estornos trabalham na mesma base. A navegação prioriza visão geral, movimentações, pendências, planejamento e contas; recursos avançados ficam em Explorar e Mais.

Em **Movimentações → Importar extrato**, arquivos CSV (Data, Descrição, Valor) e OFX têm prévia salva no banco por usuário. O tipo escolhido no topo é aplicado inicialmente a todas as linhas; cada linha pode depois ser destinada a uma conta, com meio de pagamento escolhido, ou registrada como compra em um cartão. Compras no cartão entram na fatura sem debitar a conta. Há seleção de categorias, detecção de duplicatas, envio de uma linha ou das selecionadas e exclusão permanente de lotes importados. As linhas ainda não enviadas permanecem no rascunho para continuar depois de criar categorias. O formulário de lançamento sugere categorias a partir de descrições já usadas, sempre permitindo revisão antes de salvar.

O extrato de uma conta pode ser compartilhado em modo somente leitura por um link secreto revogável, com senha adicional opcional. A página pública mostra apenas datas, descrições, entradas, saídas e um gráfico mensal, sempre respeitando os filtros escolhidos.

## Executar

1. Instale PHP 8.1+ com PDO SQLite e mbstring; gere o autoload com `composer install`.
2. Execute `php database/migrate.php`.
3. Configure o Apache para servir `public/`. No ambiente local existente: `http://localhost:8090/uaimoney-mvp/public/`.
4. Entre com um usuário administrador, cadastre os demais em **Administração de usuários** e siga **Primeiros passos** ao configurar cada conta.

Em uma instalação existente, execute `php bin/backup.php` antes das migrations. Não exclua nem recrie o banco para atualizar.

## Dados de demonstração

Para criar um demonstrador com dados fictícios de janeiro a dezembro do ano corrente, execute:

```text
php bin/seed_demo.php
```

O comando cria o usuário `demonstracao` (senha `UaiMoneyDemo2026!`) com contas, categorias, limites, lançamentos, cartão, faturas, investimentos com aportes e rendimentos confirmados, objetivos com reservas e projeções. Datas posteriores à execução ficam pendentes. Para recriar somente esse usuário e renovar os dados, use `php bin/seed_demo.php --reset`; os dados dos demais usuários não são alterados.

O menu **Investimentos** permite registrar a posição atual de aplicações, sem alterar o saldo das contas. Em **Estratégias**, há métodos de organização financeira como 50·30·20, orçamento base zero, pague-se primeiro e envelopes.

As variáveis opcionais `UAIMONEY_DATABASE`, `UAIMONEY_BASE_PATH` e `UAIMONEY_ENV` configuram banco, prefixo da URL e ambiente. Em produção, use `UAIMONEY_ENV=production` e HTTPS. As variáveis são lidas do ambiente do processo; não há carregador de `.env`.

## Verificar

```text
php tests/run.php
php tests/upgrade.php
php database/status.php
```

Os testes financeiros usam SQLite em memória. O teste de atualização parte do schema 017 com lançamentos existentes e verifica preservação de registros, saldo e integridade ao migrar para 033. `php tests/statement_import.php` cobre importação sem usar o banco real.

- [Implementação, regras e teste HTTP](docs/mvp-implementation.md)
- [Dossiê de funcionalidades e regras do produto](docs/dossie-funcionalidades.md)
- [Backup, restauração e publicação](docs/operations.md)

A separação Controller → Service → Repository foi mantida. Dinheiro é armazenado em centavos; consumo e caixa possuem consultas próprias para evitar dupla contagem. Não é necessário migrar a stack para validar o produto com poucos usuários.
