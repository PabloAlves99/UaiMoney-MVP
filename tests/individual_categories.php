<?php
declare(strict_types=1);

require __DIR__ . '/default_categories.php';
$db->exec("INSERT INTO grupos(usuario_id,nome,tipo) VALUES(1,'Casa','despesa'),(1,'Moto','despesa'),(1,'Mercado','despesa'),(1,'Juros','despesa');
    INSERT INTO subgrupos(grupo_id,nome) SELECT id,'Utensilios' FROM grupos WHERE usuario_id=1 AND nome='Casa';
    INSERT INTO subgrupos(grupo_id,nome) SELECT id,'Estacionamento' FROM grupos WHERE usuario_id=1 AND nome='Moto';
    INSERT INTO subgrupos(grupo_id,nome) SELECT id,'Compras' FROM grupos WHERE usuario_id=1 AND nome='Mercado';
    INSERT INTO subgrupos(grupo_id,nome) SELECT id,'Fatura' FROM grupos WHERE usuario_id=1 AND nome='Juros';
    INSERT INTO transacoes(usuario_id,subgrupo_id,conta_id,descricao,valor_centavos,data_competencia,data_vencimento,status)
      SELECT 1,s.id,1,'Teste ' || g.nome,1000,'2025-01-01','2025-01-01','pendente' FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=1 AND g.nome IN ('Casa','Moto','Mercado','Juros');
    INSERT INTO recorrencias(usuario_id,subgrupo_id,descricao,valor_centavos,frequencia,data_inicio)
      SELECT 1,s.id,'Compra recorrente',1000,'mensal','2025-01-01' FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=1 AND g.nome='Casa';
    INSERT INTO transacoes(usuario_id,subgrupo_id,conta_id,descricao,valor_centavos,data_competencia,data_vencimento,status)
      SELECT 1,s.id,1,'Compra sem detalhe',1000,'2025-01-01','2025-01-01','pendente' FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=1 AND g.nome='Alimentação' AND s.nome='Restaurantes e delivery'");
$db->beginTransaction();
(require dirname(__DIR__) . '/database/migrations/035_individual_categories.php')($db);
$db->commit();
$pairs = $db->query("SELECT t.descricao,g.nome grupo,s.nome sub FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id JOIN grupos g ON g.id=s.grupo_id WHERE t.descricao LIKE 'Teste %'")->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
foreach (['Teste Casa'=>['Moradia','Utensílios domésticos'], 'Teste Moto'=>['Transporte','Estacionamento'], 'Teste Mercado'=>['Alimentação','Mercado'], 'Teste Juros'=>['Financeiro','Juros de cartão']] as $name => [$group,$sub]) {
    $assert($pairs[$name]['grupo'] === $group && $pairs[$name]['sub'] === $sub, 'Migração incorreta: ' . $name);
}
$assert((int)$db->query("SELECT COUNT(*) FROM grupos WHERE usuario_id=1 AND nome IN ('Casa','Moto','Mercado','Juros') AND ativo=1")->fetchColumn() === 0, 'Grupos antigos ainda ativos');
$assert($db->query("SELECT s.nome FROM recorrencias r JOIN subgrupos s ON s.id=r.subgrupo_id WHERE r.descricao='Compra recorrente'")->fetchColumn() === 'Utensílios domésticos', 'Recorrência não migrada');
$assert($db->query("SELECT s.nome FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id WHERE t.descricao='Compra sem detalhe'")->fetchColumn() === 'Restaurantes e delivery', 'Lançamento ambíguo classificado indevidamente');
$assert((int)$db->query("SELECT COUNT(*) FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=1 AND ((g.nome='Alimentação' AND s.nome='Mercado') OR (g.nome='Transporte' AND s.nome='Manutenção do veículo')) AND s.ativo=1")->fetchColumn() === 2, 'Subcategoria padrão arquivada incorretamente');
$assert((int)$db->query("SELECT COUNT(*) FROM historico_movimentacoes WHERE acao='Categoria ajustada para finalidade individual'")->fetchColumn() === 4, 'Histórico de migração ausente');
$assert($db->query('PRAGMA foreign_key_check')->fetchAll() === [], 'Relacionamento inválido');
echo "OK: finalidades individuais, grupos legados e recorrências.\n";
