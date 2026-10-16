# Dossiê funcional — UaiMoney

Atualizado em 16/10/2026. Este documento descreve o que o código da aplicação oferece hoje. Os exemplos financeiros são informativos; o produto não presta consultoria financeira nem sincroniza automaticamente com bancos ou corretoras.

## 1. Proposta e organização

O UaiMoney é um controle financeiro pessoal com usuários separados. O fluxo central é registrar movimentações, acompanhar o dinheiro disponível, entender gastos e organizar o futuro em objetivos, investimentos e projeções. A navegação reúne visão geral, movimentações, pendências, planejamento e contas; funções complementares ficam em Explorar e Mais.

| Área | O que o usuário consegue fazer | Regra importante |
| --- | --- | --- |
| Visão geral | Ver saldos, receitas, despesas, cartão, alertas e evolução | Mostra dados do usuário autenticado |
| Conferir | Revisar pendências e compromissos | Lançamento futuro não entra no saldo realizado |
| Categorias | Criar, editar, classificar e inativar categorias e subcategorias; definir limites mensais | Limites pertencem a categorias de despesa |
| Meu Plano | Ver o panorama de limites, objetivos e investimentos | Reúne informações; os limites são editados em Categorias |
| Movimentações | Criar, editar, efetivar, cancelar, duplicar, inativar e restaurar; ver detalhes e exportar | Excluir arquiva a movimentação, preservando histórico |
| Contas e carteira | Manter contas, ver extrato, transferir, conferir saldo e exportar | Transferência muda duas contas e não cria receita ou despesa |
| Cartões | Manter cartões, compras, faturas, pagamentos, créditos e estornos | Compra é despesa; pagamento da fatura é saída de caixa, sem segunda despesa |
| Investimentos | Registrar posição, aportes, retiradas e rendimento confirmado | Taxa planejada nunca vira rendimento real automaticamente |
| Objetivos | Definir valor, finalidade, prioridade, horizonte e prazo; reservar fontes | Reserva identifica dinheiro já existente, sem movimentá-lo |
| Projeções | Simular prazo para uma meta com aporte e taxa, ligada a investimento, objetivo ou livre | Simulação não altera saldos nem cria lançamentos |
| Arquivados | Consultar e reativar itens inativados | O tratamento de cada tipo respeita seus vínculos financeiros |

## 2. Registro financeiro

### Contas e saldo

Cada conta possui tipo, nome, instituição e saldo inicial com data de referência. O saldo resulta do saldo inicial mais movimentações efetivadas, transferências, pagamentos de fatura, conferências e estornos pertinentes até a data consultada. Existem contas correntes, dinheiro e carteiras digitais. Contas podem ser editadas, inativadas e reativadas. O extrato pode ser exportado em PDF ou Excel.

O usuário pode criar um link secreto de leitura do extrato, revogá-lo e exigir senha adicional. A página compartilhada usa filtros e apresenta apenas dados do extrato e um gráfico mensal. Esse compartilhamento não concede edição.

### Categorias, lançamentos e caixa

Categorias separam receita de despesa, contêm subcategorias e podem classificar a despesa como essencial ou variável. Um lançamento registra descrição, valor em centavos, categoria, competência, vencimento, meio de pagamento e, quando aplicável, data de efetivação, conta, cartão, fatura, parcela e recorrência. Lançamentos podem ficar pendentes, efetivados ou cancelados. A exclusão funcional marca a linha como inativa; restauração recupera o estado anterior. O histórico de alterações fica associado às movimentações.

O sistema separa **consumo por competência** de **movimento de caixa**. Isso permite ver a despesa no período correto sem confundir uma compra no crédito com a data em que a conta bancária pagou a fatura. Estornos reduzem o consumo original e ajustam o caixa quando correspondem a lançamentos de conta.

O formulário sugere categorias com base em descrições já usadas, deixando a decisão final com o usuário. Há importação de extratos CSV e OFX com prévia persistente por usuário, tipo padrão ajustável por linha, associação de categoria, sinalização de possíveis duplicatas, seleção em massa, envio de uma linha ou das selecionadas e exclusão permanente dos lotes importados quando seus lançamentos e faturas permitem. Linhas não enviadas permanecem no rascunho para continuar depois de criar categorias; o usuário pode remover linhas ou descartar toda a prévia. O usuário também pode filtrar e exportar movimentações.

### Compromissos automáticos

Parcelamentos distribuem uma compra em parcelas. Recorrências admitem frequência diária, semanal, mensal ou anual, intervalo e eventual limite de ocorrências; podem ser editadas, pausadas, retomadas ou encerradas. O processamento gera lançamentos conforme a agenda e protege contra duplicação da mesma ocorrência. A geração de recorrências depende de acionar o processamento disponível na aplicação ou de agendá-lo operacionalmente; o cadastro sozinho não significa que um banco externo será debitado.

### Cartões e faturas

O cartão guarda limite, dias de fechamento e vencimento e pode ter uma conta de pagamento associada. Compras integram faturas mensais; estas podem ser abertas, fechadas e pagas. Pagamentos parciais, créditos e estornos possuem registros próprios. O limite disponível considera o comprometimento do cartão. Os relatórios tratam a compra como consumo e o pagamento como caixa para evitar contagem dupla.

## 3. Planejamento conectado

### Limites por categoria

O usuário cria limites mensais em **Categorias → Limites** para grupos de despesa. É possível copiar limites do mês anterior e inativá-los. O acompanhamento compara o valor previsto com o consumo registrado. **Meu Plano** apresenta o panorama, inclusive objetivos e investimentos, para que a gestão das categorias fique no lugar onde elas são mantidas.

### Investimentos

Um investimento descreve tipo, instituição, propósito, posição inicial, plano de aporte mensal e taxa de referência. Seu histórico contém eventos datados de **aporte**, **retirada**, **rendimento** e ajuste de saldo. Vários aportes e retiradas podem ocorrer no mesmo mês. O valor aplicado acompanha entradas e saídas; o valor atual incorpora também rendimentos e ajustes. Eventos podem ser inativados e reativados, com recálculo da posição.

O rendimento do mês precisa de confirmação humana. Pode ser informado como valor em reais, percentual aplicado ao saldo ou saldo atual observado. Rendimentos negativos são possíveis; há no máximo um rendimento ativo por investimento em cada mês. A taxa de referência serve para planejar e sugerir, sem lançar juros por conta própria. Investimentos representam patrimônio informado e não debitam automaticamente contas bancárias; aportes e retiradas de caixa devem ser registrados também na área financeira quando ocorrerem.

### Objetivos e reserva de valores

Um objetivo tem nome, tipo, finalidade, valor alvo, data opcional, prioridade, horizonte curto/médio/longo e aporte mensal planejado. A pessoa pode reservar valores de contas ou investimentos existentes para um objetivo, indicando a origem do progresso. O sistema impede reservar a mesma fonte simultaneamente acima do disponível e sinaliza quando uma fonte passa a ter menos que o reservado. Uma reserva pode ser inativada e reativada. A reserva não transfere dinheiro, não bloqueia movimentações e não garante liquidez do investimento.

Objetivos antigos podem conservar um progresso legado, distinguido do valor agora vinculado a fontes. Objetivos também podem ser editados, arquivados e reativados.

### Projeções

Uma projeção pode usar a posição atual de um investimento, o progresso de um objetivo ou um valor inicial livre. A pessoa informa aporte mensal, taxa mensal ou anual e meta. O cálculo aplica juros compostos ao saldo e acrescenta o aporte no fim de cada mês, até atingir a meta, com horizonte máximo de 1.200 meses. Apresenta mês estimado, aportes futuros, juros projetados, marcos de 25/50/75/100% e comparação com cenário de 0% de juros quando aplicável. Projeções podem ser salvas, editadas, inativadas e reativadas.

O valor inicial de projeções ligadas acompanha a fonte atual. Taxa constante é uma hipótese de simulação: não incorpora inflação, impostos, taxas, volatilidade ou rentabilidade futura assegurada. Nenhuma projeção contabiliza rendimento real.

## 4. Análise e apoio à decisão

As telas de análise usam os registros de consumo, caixa, categorias, limites e patrimônio para mostrar tendências e distribuição de gastos. **Estratégias** explica formas de organizar orçamento, como 50·30·20, base zero, pague-se primeiro e envelopes. Essas recomendações são métodos de organização, não decisões automáticas sobre o dinheiro do usuário.

A aplicação usa regras determinísticas para sugerir categoria, detectar duplicatas de extrato, calcular saldo, comparar orçamento, conferir disponibilidade de reservas e estimar prazo de metas. Não há modelo de IA, conexão bancária em tempo real nem execução automática de investimentos.

## 5. Controle, segurança e operação

Administradores cadastram e editam usuários; não existe cadastro público. O aplicativo usa login, recuperação de senha, proteção CSRF, limitação de tentativas e consultas filtradas pelo usuário. As operações persistentes usam SQLite; valores monetários são guardados em centavos. Há preferência de tema claro/escuro e interface responsiva.

O módulo **Arquivados** reúne em tabelas categorias e subcategorias, contas, cartões, investimentos e seus eventos, objetivos e reservas, projeções, limites e movimentações para consulta e reativação. Itens arquivados deixam as telas de operação; registros dependentes aparecem em Arquivados quando o principal está inativo e só podem voltar após ele. Movimentações canceladas ficam ocultas por padrão e podem ser vistas pela opção **Exibir cancelados**; cancelar e arquivar são ações distintas. Recorrências têm controles próprios de pausar, retomar e encerrar. Arquivar preserva o registro no banco, mas altera sua participação nos cálculos conforme a regra da entidade. A ferramenta de backup e o guia de publicação estão em [operations.md](operations.md).

## 6. Demonstração e reprodução

`php bin/seed_demo.php` cria o usuário `demonstracao` se ele ainda não existir. `php bin/seed_demo.php --reset` recria apenas esse usuário e seus dados. O cenário cobre janeiro a dezembro do ano corrente, com receitas e despesas por diferentes meios de pagamento, lançamentos futuros pendentes, limites, importação, estorno, transferência, cartões e faturas, recorrências, investimentos com eventos mensais, objetivos com reservas, projeções ligadas e livres, além de itens arquivados para testar reativação. Dados passados representam operações concluídas; datas futuras são planejadas.

Antes de executar o seed em um banco novo, rode `php database/migrate.php`. Em banco já existente, siga o backup recomendado no [README](../README.md) e aplique as migrations pendentes; não é preciso recriar a base dos outros usuários. Para validação de regressão, use `php tests/run.php`, `php tests/upgrade.php` e `php tests/statement_import.php`.
