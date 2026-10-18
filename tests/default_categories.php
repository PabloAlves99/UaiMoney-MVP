<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
foreach (glob(dirname(__DIR__) . '/database/migrations/*.php') as $file) {
    if ((int) basename($file) < 34) (require $file)($db);
}
$db->exec("INSERT INTO usuarios(nome,login,email,senha_hash) VALUES('Teste','teste','teste@example.test','hash');
    INSERT INTO grupos(usuario_id,nome,tipo) VALUES(1,'Alimentação','despesa'),(1,'Personalizada','despesa'),(1,'Saúde','despesa');
    INSERT INTO subgrupos(grupo_id,nome,ativo) VALUES(1,'Supermercado',1),(2,'Especial',1),(3,'Consulta',0);
    INSERT INTO contas(usuario_id,nome,tipo,saldo_inicial_centavos,saldo_inicial_em) VALUES(1,'Conta','corrente',0,'2020-01-01');
    INSERT INTO transacoes(usuario_id,subgrupo_id,conta_id,descricao,valor_centavos,data_competencia,data_vencimento,status) VALUES(1,1,1,'Compra',1000,'2025-01-01','2025-01-01','pendente'),(1,2,1,'Personalizada',2000,'2025-01-01','2025-01-01','pendente');
    INSERT INTO orcamentos(usuario_id,ano_mes,grupo_id,valor_limite_centavos) VALUES(1,'2025-01',1,5000)");
$db->beginTransaction();
(require dirname(__DIR__) . '/database/migrations/034_default_categories.php')($db);
$db->commit();
$assert = static function (bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); };
$assert($db->query("SELECT s.nome FROM transacoes t JOIN subgrupos s ON s.id=t.subgrupo_id WHERE t.id=1")->fetchColumn() === 'Mercado', 'Movimentação não migrada');
$assert((int) $db->query('SELECT subgrupo_id FROM transacoes WHERE id=2')->fetchColumn() === 2, 'Categoria personalizada alterada');
$assert((int) $db->query('SELECT COUNT(*) FROM historico_movimentacoes WHERE transacao_id=1')->fetchColumn() === 1, 'Histórico ausente');
$assert((int) $db->query('SELECT valor_limite_centavos FROM orcamentos WHERE grupo_id=1')->fetchColumn() === 5000, 'Limite alterado');
$assert((int) $db->query("SELECT s.ativo FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.nome='Saúde' AND s.nome='Consulta'")->fetchColumn() === 0, 'Consulta arquivada reativada');
$assert((int) $db->query("SELECT COUNT(*) FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.nome='Saúde' AND s.nome='Exame' AND s.ativo=1")->fetchColumn() === 1, 'Exame ausente');
$repo = new App\Repositories\UserRepository($db);
$new = $repo->create('Novo', 'novo', 'novo@example.test', 'hash');
$stmt = $db->prepare("SELECT COUNT(*) FROM subgrupos s JOIN grupos g ON g.id=s.grupo_id WHERE g.usuario_id=? AND g.nome='Saúde' AND s.nome IN ('Consulta','Exame')");
$stmt->execute([$new]);
$assert((int) $stmt->fetchColumn() === 2, 'Novos usuários sem padrões');
$assert($db->query('PRAGMA foreign_key_check')->fetchAll() === [], 'Chave estrangeira inválida');
echo "OK: padrões, migração, histórico, limites e cadastro.\n";
