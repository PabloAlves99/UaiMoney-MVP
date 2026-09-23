# Implementação do MVP financeiro

Atualização de 22 de setembro de 2026. Este documento registra o comportamento implementado sobre o projeto existente e complementa o roadmap inicial.

## Escopo entregue

| Área | Comportamento |
|---|---|
| Acesso | Cadastro público separado do administrativo, login, proteção de sessão, limite de tentativas e isolamento por usuário |
| Primeiro acesso | Conta, categorias sugeridas sem duplicação e primeiro lançamento |
| Interface | Navegação consistente, identidade verde-petróleo, responsividade, temas claro/escuro, foco visível e link para pular navegação |
| Movimentações | Busca, filtros, paginação de 50 itens, detalhes de origem, estorno e duplicação como pendente |
| Exportação | CSV dos lançamentos filtrados, com valores em centavos, vínculos e total estornado; proteção contra fórmulas em descrições |
| Recorrências | Criação direta, filtros por descrição/frequência/situação, processamento manual, pausa, retomada, encerramento e edição do futuro |
| Cartões | Cadastro, edição, limite utilizado/disponível, desativação com saldo quitado, compra simples/parcelada com divisão exata dos centavos |
| Faturas | Histórico, fechamento manual, pagamento integral/parcial, ajuste de fatura da compra, crédito por estorno e transporte de crédito |
| Planejamento | Limite por categoria/mês, realizado/restante/pendente, alertas e cópia do mês anterior sem sobrescrever limites existentes |
| Visão geral | Saldo de hoje, previsão até o fim do mês atual, receitas/despesas por competência, resultado, pendências, faturas e orçamento |
| Análises | Intervalo por competência, agrupamento por categoria, subcategoria, conta, cartão, meio, recorrente/eventual e mês; maiores despesas e comparação mensal |
| Contas | Transferência atômica, extrato de caixa, conferência com ajuste identificado, saldos iniciais negativos, desativação sem saldo e reativação |
| Correções | Estorno total/parcial de movimentações realizadas, mantendo original, motivo e data |
| Operação | Migrations incrementais, erros 404/405, logs internos, backup consistente e testes financeiros/HTTP |

## Regras financeiras

### Consumo e caixa

`consumo` representa receitas/despesas por competência e seus estornos. `movimentos_caixa` representa entradas/saídas efetivas nas contas. As views são consultadas pelos Repositories.

Uma compra no crédito entra nas análises por categoria, mas não debita a conta. O pagamento da fatura debita a conta e não gera nova despesa. Transferências e ajustes de conferência afetam caixa, sem alterar receita ou despesa.

O saldo atual inclui o saldo inicial e eventos desde sua data até hoje. Registros anteriores ao saldo inicial não são somados novamente. Contas inativas ficam fora do saldo consolidado; é preciso zerar o saldo antes de desativar. Para estornar uma movimentação de conta desativada, reative a conta primeiro.

### Cartões e parcelas

- O mês da fatura corresponde ao fechamento. Uma compra no próprio dia do fechamento entra no ciclo seguinte.
- Se o vencimento for anterior ou igual ao dia de fechamento, fica no mês seguinte. Dias 29/30/31 são ajustados ao último dia disponível.
- A primeira fatura pode ser escolhida manualmente. Depois, compras sem estorno podem ser movidas entre faturas abertas e sem pagamentos.
- Uma compra simples usa a data da compra como competência; uma compra parcelada distribui a competência pelos meses de fechamento das parcelas. A data original da compra é preservada separadamente.
- O valor total ainda não pago compromete o limite. Pagamentos liberam o valor pago. Limite excedido é sinalizado, sem impedir o registro de uma compra já realizada.
- O primeiro pagamento fecha a fatura. Pagamentos acima do saldo, de outra pessoa ou anteriores às compras são recusados. Não há cálculo automático de juros/rotativo.
- Alterar datas do cartão não reescreve faturas existentes. O fechamento é manual, para conferir os lançamentos antes de bloqueá-los.
- Estornos entram em uma fatura aberta escolhida. Uma fatura com crédito pode transportá-lo para uma fatura posterior, preservando o histórico.
- Pagamentos confirmados são imutáveis nesta versão; ainda não existe uma tela de reversão de pagamento de fatura.

### Orçamento, histórico e previsão

O orçamento é por categoria, sem limites independentes em subcategorias nesta versão. Realizado e pendente são separados. Estornos reduzem consumo na data do estorno, sem apagar a compra nem modificar silenciosamente meses anteriores.

A previsão do dashboard é sempre do mês corrente, mesmo quando o filtro histórico muda. Usa saldo atual + pendências cadastradas até o fim do mês − saldo das faturas que vencem até essa data, incluindo atrasados. Não inclui recorrências futuras ainda não geradas, nem representa garantia de dinheiro disponível.

Movimentações realizadas devem ser estornadas para preservar histórico. Pendências podem ser editadas/canceladas. Duplicar cria uma nova pendência sem vínculos de recorrência, parcelamento ou fatura.

Registros legados com meio `credito` e sem cartão não são reinterpretados automaticamente: revisar o histórico evita alterar saldos existentes sem contexto. Novos registros de crédito devem usar o fluxo de cartões.

### Reenvios

Formulários novos possuem identificadores de operação. Transferências, compras, pagamentos, estornos e conferências registram esses identificadores na mesma transação que grava o evento. Reenviar o mesmo identificador não repete a operação. Lançamentos, parcelamentos e recorrências também têm proteção contra reenvio na sessão. Reabrir um formulário representa uma operação nova.

## Validação

`php tests/run.php` cobre dinheiro, datas, centavos, transferências, faturas, pagamentos, créditos, estornos, orçamento, recorrências, reenvios e isolamento. `php tests/upgrade.php` verifica a atualização do schema antigo sem modificar registros existentes.

Para testar a interface sem tocar no banco real:

1. Execute `php tests/fixture.php`. Ele cria um arquivo novo em `storage/testing/` e imprime o caminho.
2. Em um terminal de teste, configure `UAIMONEY_DATABASE` com esse caminho e `UAIMONEY_BASE_PATH` com uma string vazia.
3. Execute `php -S 127.0.0.1:8787 -t public tests/router.php`.
4. Execute `python tests/http_smoke.py`. Ele cria usuários e lançamentos fictícios apenas nesse servidor local.

A fixture usa `ana` e `bia`, com senha `UaiTeste!2026`. Nunca use essa base nem essas credenciais em produção. O teste HTTP cobre cadastro, páginas, formulários, CSRF, CSV, reenvios e acesso cruzado. O dashboard também foi inspecionado no navegador em desktop, celular e tema escuro.

## Próximo marco comercial

Antes da oferta por assinatura, faltam provedor de cobrança, planos, webhooks idempotentes, inadimplência, verificação/recuperação de e-mail, documentos do serviço, implantação e monitoramento. Nenhuma cobrança nem publicação foi feita.

Open Finance, importação OFX/CSV, notificações, compartilhamento familiar e reescrita da stack continuam fora deste escopo.
