> Atualização de 22/09/2026: a implementação atual está documentada em [mvp-implementation.md](mvp-implementation.md). O conteúdo abaixo registra a evolução anterior do projeto.

# UaiMoney MVP — Relatório de Evolução

## 1. Objetivo do projeto

O UaiMoney está sendo reconstruído como um MVP de controle financeiro pessoal, com foco inicial em:

* validar regras de negócio;
* criar uma estrutura de dados consistente;
* tornar o sistema útil para uso real;
* construir uma arquitetura organizada;
* preparar o domínio para uma futura migração tecnológica;
* futuramente transformar o projeto em um produto comercial.

A estratégia definida foi:

```text
PHP + SQLite
      ↓
MVP funcional
      ↓
Validação do produto
      ↓
Python + API
      ↓
React
      ↓
PostgreSQL
      ↓
Produto online
```

A decisão foi **não evoluir diretamente o sistema antigo**, mas reconstruí-lo corretamente, mantendo o projeto legado apenas como referência e eventual fonte de dados para migração.

---

# 2. Projeto legado

O UaiMoney antigo possuía principalmente:

```text
usuarios
grupos
subgrupos
transacoes
```

O principal problema estrutural era a falta de relacionamentos reais.

Exemplo:

```text
grupos.nome = Alimentação

subgrupos.grupo = Alimentação

transacoes.grupo = Alimentação
```

As informações eram repetidas como texto.

Isso permitia inconsistências, como:

```text
grupos:
Renda fixa

transacoes:
Renda Fixa
```

ou:

```text
subgrupo Faculdade
pertencendo atualmente a Conhecimento

transação antiga:
Essencial > Faculdade
```

A nova arquitetura elimina esse problema utilizando IDs e Foreign Keys.

---

# 3. Repositório Git

Foi criado um novo projeto:

```text
UaiMoney-MVP
```

Também foi criado um repositório público no GitHub:

```text
https://github.com/PabloAlves99/UaiMoney-MVP
```

A branch principal foi alterada para:

```text
main
```

O projeto já está sincronizado com o GitHub através de:

```bash
git push
```

Foi adotada uma convenção de commits próxima ao Conventional Commits:

```text
feat:
fix:
refactor:
chore:
docs:
test:
style:
```

Exemplos utilizados:

```text
feat: adiciona conexão com SQLite

feat: adiciona infraestrutura de migrations

feat: adiciona contas e estrutura de categorias

feat: adiciona estrutura de transacoes

feat: adiciona estrutura de parcelamentos

feat: adiciona estrutura de recorrencias

feat: adiciona cartoes e controle de faturas
```

---

# 4. Estrutura atual do projeto

A estrutura definida é:

```text
UaiMoney-MVP/
│
├── app/
│   ├── Controllers/
│   ├── Core/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   └── Views/
│
├── config/
│   ├── app.php
│   └── database.php
│
├── database/
│   ├── migrations/
│   ├── seeds/
│   ├── migrate.php
│   └── status.php
│
├── public/
│   ├── css/
│   ├── js/
│   ├── media/
│   └── index.php
│
├── routes/
│
├── storage/
│   ├── database/
│   └── logs/
│
├── .gitignore
├── README.md
└── estrutura.md
```

---

# 5. Organização arquitetural planejada

A aplicação está sendo organizada para seguir aproximadamente este fluxo:

```text
Request
   ↓
Controller
   ↓
Service
   ↓
Repository
   ↓
Database
```

Responsabilidades:

## Controllers

Receber requisições HTTP e coordenar a resposta.

```text
app/Controllers/
```

---

## Services

Conter as regras de negócio.

Exemplos futuros:

```text
AuthService
TransactionService
InstallmentService
RecurrenceService
InvoiceService
```

---

## Repositories

Responsáveis pelo acesso ao banco.

Exemplos:

```text
UserRepository
AccountRepository
TransactionRepository
```

O objetivo é evitar SQL espalhado em:

```text
views
controllers
services
```

---

## Models

Representações das entidades da aplicação.

---

## Core

Infraestrutura principal da aplicação.

Atualmente contém:

```text
Database.php
```

Futuramente deverá conter elementos como:

```text
Router
Request
Response
Session
Auth
Validator
```

---

# 6. Configuração da aplicação

Foi criado:

```text
config/app.php
```

Com configurações gerais como:

```text
nome da aplicação
ambiente
debug
timezone
```

Foi adotado:

```php
declare(strict_types=1);
```

nos arquivos PHP.

Objetivo:

* melhorar previsibilidade de tipos;
* reduzir conversões implícitas;
* ajudar na manutenção do código.

---

# 7. Configuração do banco

Foi criado:

```text
config/database.php
```

O banco utilizado no MVP é:

```text
SQLite
```

O arquivo será criado em:

```text
storage/database/uaimoney.sqlite
```

Esse banco não será versionado pelo Git.

---

# 8. Segurança do repositório

Foi criado `.gitignore` para impedir versionamento de:

```text
bancos SQLite
logs
.env
credenciais
node_modules
vendor
arquivos temporários
backups
configurações locais de IDE
```

O banco real não deve ser enviado ao GitHub.

---

# 9. Ambiente PHP corrigido

Foi identificado que as extensões:

```text
pdo_mysql
pdo_sqlite
sqlite3
```

estavam sendo carregadas duas vezes pelo:

```text
C:\php\php.ini
```

As duplicidades foram removidas.

O PHP CLI foi validado com sucesso.

---

# 10. Classe Database

Foi criada:

```text
app/Core/Database.php
```

Responsável exclusivamente pela conexão com o banco.

Ela utiliza:

```text
PDO
```

Configurações atuais:

```text
ERRMODE_EXCEPTION
FETCH_ASSOC
foreign_keys = ON
journal_mode = WAL
busy_timeout = 5000
```

Fluxo:

```text
database.php
     ↓
Database
     ↓
PDO
     ↓
SQLite
```

A classe mantém uma conexão por instância durante a execução.

---

# 11. Foreign Keys

Foi ativado:

```sql
PRAGMA foreign_keys = ON;
```

Isso permite ao SQLite proteger relacionamentos entre tabelas.

O projeto antigo não possuía Foreign Keys reais.

---

# 12. WAL

Foi configurado:

```sql
PRAGMA journal_mode = WAL;
```

WAL significa:

```text
Write-Ahead Logging
```

Ajuda o SQLite a lidar melhor com leituras e escritas concorrentes.

---

# 13. Sistema de migrations

Foi criado:

```text
database/migrate.php
```

O objetivo é substituir o antigo modelo de:

```text
criar_banco.php
```

As alterações estruturais agora são versionadas.

Exemplo:

```text
001_create_users_table.php
002_create_accounts_table.php
003_create_groups_table.php
...
```

O banco possui uma tabela interna:

```text
migrations
```

que registra quais migrations já foram executadas.

---

# 14. Controle por batch

As migrations recebem um:

```text
batch
```

Isso permitirá futuramente implementar:

```text
rollback
```

por grupo de migrations executadas na mesma rodada.

---

# 15. Status das migrations

Foi criado:

```text
database/status.php
```

Permite executar:

```bash
php database/status.php
```

e visualizar:

```text
Migration                              Status
------------------------------------------------
001_create_users_table.php             Executada
002_create_accounts_table.php          Executada
...
```

---

# 16. Modelo financeiro criado

O modelo atual está aproximadamente assim:

```text
                        USUARIOS
                           │
        ┌──────────────────┼─────────────────────┐
        │                  │                     │
        ▼                  ▼                     ▼
      CONTAS             CARTOES               GRUPOS
                            │                     │
                            ▼                     ▼
                          FATURAS              SUBGRUPOS
                            │                     │
                            │                     ▼
                            │                TRANSACOES
                            │                     │
                            ▼                     │
                  PAGAMENTOS FATURA              │
                                                  │
                               ┌──────────────────┴─────────────┐
                               ▼                                ▼
                        PARCELAMENTOS                     RECORRENCIAS
```

---

# 17. Migration 001 — Usuários

Criada:

```text
001_create_users_table.php
```

Tabela:

```text
usuarios
```

Campos principais:

```text
id
nome
login
email
senha_hash
tipo
ativo
criado_em
atualizado_em
```

Regras:

```text
login único
email único
comparação sem diferenciar maiúscula/minúscula
tipo = usuario ou admin
ativo = 0 ou 1
```

Senha será armazenada somente através de:

```php
password_hash()
```

e validada por:

```php
password_verify()
```

Senha pura nunca será armazenada.

---

# 18. Migration 002 — Contas

Criada:

```text
002_create_accounts_table.php
```

Tabela:

```text
contas
```

Campos:

```text
id
usuario_id
nome
tipo
instituicao
saldo_inicial_centavos
saldo_inicial_em
ativo
criado_em
atualizado_em
```

Tipos iniciais:

```text
corrente
poupanca
dinheiro
carteira_digital
outro
```

Relacionamento:

```text
usuarios
   │
   └── contas
```

---

# 19. Saldo das contas

Não será armazenado:

```text
saldo_atual
```

Será armazenado somente:

```text
saldo_inicial
```

O saldo atual será calculado.

Conceito:

```text
saldo atual
=
saldo inicial
+ receitas efetivadas
- despesas efetivadas
- pagamentos de fatura
± transferências
```

Isso evita múltiplas fontes de verdade.

---

# 20. Valores financeiros

Foi decidido não utilizar:

```text
REAL
```

para valores financeiros no SQLite.

Os valores serão armazenados como:

```text
INTEGER
```

em centavos.

Exemplos:

```text
R$ 259,90
→ 25990

R$ 3.800,00
→ 380000

R$ 1,05
→ 105
```

Isso evita problemas de precisão de ponto flutuante.

---

# 21. Migration 003 — Grupos

Criada:

```text
003_create_groups_table.php
```

Tabela:

```text
grupos
```

Campos:

```text
id
usuario_id
nome
tipo
ativo
criado_em
atualizado_em
```

Tipos:

```text
receita
despesa
```

Grupos pertencem ao usuário.

---

# 22. Migration 004 — Subgrupos

Criada:

```text
004_create_subgroups_table.php
```

Tabela:

```text
subgrupos
```

Campos:

```text
id
grupo_id
nome
descricao
ativo
criado_em
atualizado_em
```

Agora não existem mais:

```text
grupo TEXT
tipo TEXT
```

dentro de `subgrupos`.

O relacionamento é:

```text
subgrupo
   ↓
grupo
   ↓
tipo
```

---

# 23. Normalização das categorias

Anteriormente:

```text
transacao
grupo = Alimentação
subgrupo = Mercado
```

Agora:

```text
transacao
subgrupo_id = 7
```

E:

```text
7
↓
Mercado
↓
Alimentação
↓
despesa
```

Assim, renomear o grupo não quebra o histórico.

---

# 24. Migration 005 — Transações

Criada:

```text
005_create_transactions_table.php
```

Tabela:

```text
transacoes
```

Campos iniciais:

```text
id
usuario_id
subgrupo_id
conta_id
descricao
valor_centavos
data_competencia
data_vencimento
data_efetivacao
status
meio_pagamento
observacao
criado_em
atualizado_em
```

---

# 25. Tipo da transação

`transacoes` não possui:

```text
tipo
```

O tipo é obtido por:

```text
transacao
↓
subgrupo
↓
grupo
↓
tipo
```

Isso evita inconsistência entre categoria e transação.

---

# 26. Status das transações

Estados definidos:

```text
pendente
efetivada
cancelada
```

A interface futuramente poderá apresentar:

## Receita

```text
pendente → A receber
efetivada → Recebida
```

## Despesa

```text
pendente → Pendente
efetivada → Paga
```

---

# 27. Transação vencida

`vencida` não será armazenado.

Será calculado através de:

```text
status = pendente
+
data_vencimento < hoje
```

Assim o estado muda automaticamente com o passar do tempo.

---

# 28. Datas financeiras

Foram separadas:

```text
data_competencia
data_vencimento
data_efetivacao
```

Permitindo diferenciar:

```text
quando pertence financeiramente
quando deveria acontecer
quando realmente aconteceu
```

---

# 29. Índices

Foram criados índices para consultas frequentes, principalmente por:

```text
usuario
data
status
subgrupo
conta
cartao
fatura
recorrencia
parcelamento
```

Objetivo:

melhorar performance futura sem indexar indiscriminadamente todas as colunas.

---

# 30. Migration 006 — Parcelamentos

Criada:

```text
006_create_installments_table.php
```

Tabela:

```text
parcelamentos
```

Campos:

```text
id
usuario_id
descricao
valor_total_centavos
total_parcelas
criado_em
atualizado_em
```

Parcelamento representa o agrupamento.

As transações representam as parcelas reais.

---

# 31. Migration 007 — Parcelamento nas transações

Adicionados:

```text
parcelamento_id
numero_parcela
```

Exemplo:

```text
Notebook
R$ 3.600
12x
```

```text
parcelamento 1
    ├── parcela 1
    ├── parcela 2
    ├── parcela 3
    └── ...
```

Não existe mais uma transação-pai artificial entrando em totais.

---

# 32. Arredondamento de parcelas

Exemplo:

```text
R$ 100 / 3
```

Em centavos:

```text
10000 / 3
```

Resultado planejado:

```text
3334
3333
3333
```

Total:

```text
10000
```

Essa regra será implementada futuramente no Service.

---

# 33. Migration 008 — Recorrências

Criada:

```text
008_create_recurrences_table.php
```

Tabela:

```text
recorrencias
```

Campos principais:

```text
usuario_id
subgrupo_id
conta_id
descricao
valor_centavos
frequencia
intervalo
data_inicio
data_fim
meio_pagamento
observacao
ativo
```

Frequências:

```text
diaria
semanal
mensal
anual
```

---

# 34. Recorrência x parcelamento

Parcelamento:

```text
valor total conhecido
quantidade de parcelas conhecida
fim conhecido
```

Recorrência:

```text
regra de repetição
pode não possuir data final
```

São entidades separadas.

---

# 35. Migration 009 — Recorrência nas transações

Foi adicionado:

```text
recorrencia_id
```

em:

```text
transacoes
```

Uma transação pode ser:

```text
normal
parcelada
recorrente
```

Regra futura:

```text
parcelamento_id
e
recorrencia_id
```

não poderão estar preenchidos simultaneamente.

---

# 36. Migration 010 — Cartões

Criada:

```text
010_create_cards_table.php
```

Tabela:

```text
cartoes
```

Campos principais:

```text
id
usuario_id
conta_pagamento_id
nome
instituicao
limite_centavos
dia_fechamento
dia_vencimento
ativo
criado_em
atualizado_em
```

---

# 37. Conta x cartão

Conta representa:

```text
dinheiro disponível
```

Cartão representa:

```text
crédito / dívida
```

Uma compra no cartão não reduz imediatamente o saldo bancário.

Exemplo:

```text
Conta:
R$ 2.000

Compra no cartão:
R$ 300

Conta:
continua R$ 2.000

Fatura:
R$ 300
```

---

# 38. Migration 011 — Cartão nas transações

Foi adicionado:

```text
cartao_id
```

em:

```text
transacoes
```

Compra via PIX:

```text
conta_id preenchida
cartao_id NULL
```

Compra no crédito:

```text
conta_id NULL
cartao_id preenchido
```

---

# 39. Limite do cartão

Não será armazenado:

```text
limite_disponivel
```

Será calculado:

```text
limite disponível
=
limite total
-
compras em aberto
```

Evita duplicidade de informação.

---

# 40. Migration 012 — Faturas

Criada:

```text
012_create_invoices_table.php
```

Tabela:

```text
faturas
```

Campos:

```text
id
usuario_id
cartao_id
competencia
data_fechamento
data_vencimento
status
criado_em
atualizado_em
```

Estados:

```text
aberta
fechada
cancelada
```

---

# 41. Valor da fatura

A fatura não armazena:

```text
valor_total
```

O valor será calculado pelas transações associadas.

Exemplo:

```text
Mercado      300
Netflix       60
Notebook     300
----------------
Fatura       660
```

---

# 42. Migration 013 — Fatura nas transações

Foi adicionado:

```text
fatura_id
```

em:

```text
transacoes
```

Compra no cartão passa a ter:

```text
cartao_id
fatura_id
```

---

# 43. Inteligência da fatura

Cartão:

```text
fecha dia 5
vence dia 12
```

Compra:

```text
03/09
```

entra na fatura atual.

Compra:

```text
08/09
```

entra na próxima fatura.

Essa regra será implementada no futuro:

```text
InvoiceService
```

---

# 44. Migration 014 — Pagamentos de fatura

Criada:

```text
014_create_invoice_payments_table.php
```

Tabela:

```text
pagamentos_fatura
```

Campos:

```text
id
usuario_id
fatura_id
conta_id
valor_centavos
data_pagamento
observacao
criado_em
```

---

# 45. Pagamento da fatura não é nova despesa

Exemplo:

```text
Mercado no cartão
R$ 300
```

Já representa:

```text
Despesa = R$ 300
```

Pagamento da fatura:

```text
Conta Nubank
- R$ 300
```

não deve representar:

```text
nova despesa
```

Caso contrário teríamos:

```text
Despesa:
300 + 300 = 600 ❌
```

O correto é:

```text
Despesa = 300

Saída da conta = 300
```

São métricas diferentes.

---

# 46. Pagamento parcial de fatura

A arquitetura suporta:

```text
Fatura:
R$ 1.000

Pagamento 1:
R$ 400

Pagamento 2:
R$ 600
```

O status financeiro da fatura poderá ser derivado:

```text
0 pago
→ Não paga

parte paga
→ Parcial

total pago
→ Paga
```

---

# 47. Soft delete

Para entidades utilizadas historicamente como:

```text
contas
grupos
subgrupos
cartoes
recorrencias
```

foi adotado o conceito:

```text
ativo = 0
```

em vez de apagá-las normalmente.

Isso preserva o histórico financeiro.

---

# 48. Estado atual do banco

O domínio principal já possui:

```text
usuarios

contas

grupos
subgrupos

transacoes

parcelamentos

recorrencias

cartoes
faturas
pagamentos_fatura
```

Isso já forma uma base sólida para iniciar a aplicação.

---

# 49. O que ainda falta no banco

Algumas entidades podem ser adicionadas futuramente.

## Transferências entre contas

Ainda precisamos modelar:

```text
transferencias
```

Exemplo:

```text
Nubank
- R$ 500

Itaú
+ R$ 500
```

Isso não é:

```text
receita
despesa
```

É apenas movimentação patrimonial entre contas do mesmo usuário.

---

## Orçamentos

Planejado:

```text
orcamentos
```

Exemplo:

```text
Alimentação
Limite mensal: R$ 1.000
```

Permitirá:

```text
R$ 750 / R$ 1.000
75%
```

---

## Metas financeiras

Possível evolução:

```text
metas
```

Exemplo:

```text
Reserva de emergência

Meta:
R$ 15.000

Atual:
R$ 8.500
```

Não é prioridade imediata.

---

# 50. Próxima etapa técnica

A modelagem do banco já está suficientemente madura.

O próximo passo não será continuar criando tabelas indefinidamente.

Vamos começar a construir a aplicação.

---

# 51. Composer

Próximo passo planejado:

```text
composer.json
```

Será configurado autoload usando:

```text
PSR-4
```

Objetivo:

parar de fazer:

```php
require_once 'app/Core/Database.php';
```

e permitir:

```php
use App\Core\Database;
use App\Repositories\UserRepository;
use App\Services\AuthService;
```

com carregamento automático.

---

# 52. Autoload planejado

Mapeamento:

```text
App\
↓
app/
```

Exemplo:

```text
App\Core\Database
```

será encontrado automaticamente em:

```text
app/Core/Database.php
```

---

# 53. Primeira funcionalidade completa

Após configurar o Composer, começaremos pelo:

```text
CADASTRO + LOGIN
```

Fluxo planejado:

```text
View
 ↓
Controller
 ↓
AuthService
 ↓
UserRepository
 ↓
Database
```

---

# 54. UserRepository

Será responsável por operações como:

```text
buscarPorId
buscarPorEmail
buscarPorLogin
criar
atualizar
```

Somente acesso ao banco.

Não deverá conter regra de negócio.

---

# 55. AuthService

Responsável por regras como:

```text
validar login
validar email
criar hash da senha
verificar senha
verificar usuário ativo
autenticar usuário
```

---

# 56. AuthController

Responsável por receber:

```text
POST /login
POST /register
POST /logout
```

e chamar o Service apropriado.

---

# 57. Sessão

Será implementada uma camada responsável por:

```text
session_start
login
logout
usuario autenticado
proteção de rotas
```

Com atenção futura para:

```text
session_regenerate_id
cookie httponly
cookie secure
SameSite
CSRF
```

---

# 58. Interface planejada

Menu principal definido inicialmente:

```text
UaiMoney

Início
Movimentações
Contas
Cartões
Planejamento
Análises
Categorias
Configurações
```

---

# 59. Tela inicial

A Home deverá responder:

> Como está minha situação financeira agora?

Planejado:

```text
Saldo atual

Receitas do mês

Despesas do mês

Resultado do mês

Saldo previsto
```

Além de:

```text
últimas movimentações
próximos pagamentos
maiores gastos
resumo das contas
resumo das faturas
```

---

# 60. Movimentações

Tela única planejada para:

```text
receitas
despesas
parcelamentos
recorrências
```

Filtros previstos:

```text
período
tipo
status
grupo
subgrupo
conta
cartão
forma de pagamento
descrição
valor
```

---

# 61. Tela de análises

Será uma tela separada da Home.

Objetivo:

> Entender por que o dinheiro está indo para determinados lugares.

Filtros globais controlarão todos os gráficos.

Planejado:

```text
data inicial/final
conta
grupo
subgrupo
cartão
status
```

---

# 62. Gráficos planejados

Inicialmente:

```text
Receitas x despesas

Evolução do saldo

Despesas por grupo

Despesas por subgrupo

Evolução de categoria

Gastos por conta

Gastos por forma de pagamento

Comparação mensal

Top maiores despesas
```

---

# 63. Planejamento financeiro

Será criado após movimentações e dashboards básicos.

Planejado:

```text
orçamento mensal por grupo

limite de gasto

percentual utilizado

alertas de limite
```

Exemplo:

```text
Alimentação

R$ 750 / R$ 1.000

75%
```

---

# 64. Previsão financeira

Planejado:

```text
saldo atual
+
receitas futuras
-
despesas futuras
=
saldo previsto
```

Objetivo:

responder:

> Quanto provavelmente terei ao final do mês?

---

# 65. Cartões

Tela planejada:

```text
Cartão Nubank

Limite total
Limite utilizado
Limite disponível

Fatura atual
Próxima fatura
Compras parceladas
```

---

# 66. Faturas

Tela planejada:

```text
Fatura Setembro

Mercado
Netflix
Notebook 3/12

Total

Valor pago
Saldo em aberto

Vencimento
Status
```

---

# 67. Recorrências

Tela futura:

```text
Netflix
Spotify
Internet
Faculdade
```

Mostrando:

```text
valor
frequência
próxima ocorrência
ativo/inativo
```

---

# 68. Importação do sistema antigo

O banco legado deverá ser preservado.

Não será necessário cadastrar tudo manualmente.

Depois do novo sistema estar estável, será criado um migrador:

```text
banco antigo
     ↓
normalização
     ↓
mapeamento
     ↓
novo banco
```

Ele poderá corrigir inconsistências antigas antes de inserir.

---

# 69. Segurança necessária antes da comercialização

Antes do produto ser publicado, ainda será necessário implementar:

```text
isolamento completo por usuario_id

autorização por recurso

proteção CSRF

cookies seguros

HTTPS

rate limiting

recuperação segura de senha

logs

backup

validação server-side

controle de sessão

proteção contra enumeração de usuários

tratamento de erros sem expor stack trace

LGPD

exportação de dados

exclusão de conta/dados
```

---

# 70. Roadmap atualizado

## Etapa 1 — Base técnica ✅

Concluído:

```text
estrutura do projeto
Git/GitHub
configuração
Database
SQLite
migrations
status das migrations
```

---

## Etapa 2 — Modelo de dados ✅

Concluído:

```text
usuarios
contas
grupos
subgrupos
transacoes
parcelamentos
recorrencias
cartoes
faturas
pagamentos_fatura
```

Ainda pode receber ajustes conforme a aplicação for construída.

---

## Etapa 3 — Infraestrutura PHP

Próximo:

```text
Composer
PSR-4
autoload
bootstrap
configuração central
router
request/response
sessão
```

---

## Etapa 4 — Autenticação

Planejado:

```text
cadastro
login
logout
sessão
proteção de páginas
perfil
```

---

## Etapa 5 — Categorias

Planejado:

```text
CRUD grupos
CRUD subgrupos
ativar/desativar
```

---

## Etapa 6 — Contas

Planejado:

```text
CRUD contas
saldo inicial
saldo atual
extrato por conta
```

---

## Etapa 7 — Movimentações

Planejado:

```text
nova receita
nova despesa
edição
cancelamento
consulta
filtros
status
```

---

## Etapa 8 — Parcelamentos

Planejado:

```text
criar parcelamento
gerar parcelas
distribuir centavos
editar parcelas
consultar parcelas restantes
```

---

## Etapa 9 — Recorrências

Planejado:

```text
criar regra
gerar ocorrências
editar regra
ativar/desativar
janela futura de geração
```

---

## Etapa 10 — Cartões e faturas

Planejado:

```text
CRUD cartão
geração automática da fatura
fechamento
vencimento
compras
pagamentos
pagamento parcial
```

---

## Etapa 11 — Transferências

Planejado:

```text
conta origem
conta destino
valor
data
```

Sem contabilizar como receita/despesa.

---

## Etapa 12 — Home

Planejado:

```text
saldo
receitas
despesas
resultado
previsão
últimas movimentações
contas
faturas
```

---

## Etapa 13 — Análises

Planejado:

```text
filtros globais
cards
gráficos
comparações
drill-down
```

---

## Etapa 14 — Planejamento

Planejado:

```text
orçamento por categoria
limites
comparações
alertas
```

---

## Etapa 15 — Segurança e acabamento

Planejado:

```text
CSRF
sessão segura
validação
logs
backup
tratamento de erros
responsividade
mobile
UX
```

---

## Etapa 16 — MVP 1.0

Objetivo:

> Utilizar o UaiMoney diariamente sem depender de planilha ou sistema paralelo.

O MVP deverá ser funcional antes da migração tecnológica.

---

# 71. Fase 2

Somente após o domínio e a experiência estarem validados.

Arquitetura pretendida:

```text
React
   ↓
API REST
   ↓
Python
   ↓
Services
   ↓
Repositories
   ↓
PostgreSQL
```

Possíveis tecnologias:

```text
React
TypeScript

Python
FastAPI

PostgreSQL
```

A decisão final será feita apenas quando chegarmos a essa etapa.

---

# 72. Objetivo da Fase 2

A Fase 2 não deverá redefinir o produto.

Ela deverá modernizar:

```text
tecnologia
interface
API
deploy
escalabilidade
segurança
```

preservando regras de negócio já validadas no MVP.

---

# 73. Princípio principal do projeto

A regra arquitetural definida para o UaiMoney é:

> O banco representa corretamente o domínio financeiro.
> A camada de negócio protege suas regras.
> A interface apenas apresenta e manipula essas regras.

Fluxo:

```text
Banco consistente
        ↓
Regras de negócio
        ↓
Aplicação funcional
        ↓
Experiência do usuário
        ↓
Produto
```

---

# 74. Próximo passo imediato

O próximo passo técnico definido é:

```text
Composer + PSR-4
```

Depois:

```text
Bootstrap da aplicação
        ↓
Repository
        ↓
Service
        ↓
Controller
        ↓
View
```

O primeiro fluxo completo será:

```text
Cadastro de usuário
        ↓
Login
        ↓
Sessão
        ↓
Área autenticada
```

A partir daí começaremos a transformar a modelagem já criada em um UaiMoney efetivamente utilizável.
