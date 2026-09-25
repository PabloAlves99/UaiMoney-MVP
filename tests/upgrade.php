<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
date_default_timezone_set('America/Sao_Paulo');
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
$files=glob(dirname(__DIR__).'/database/migrations/*.php');
foreach($files as $file) if((int)basename($file)<=17) (require $file)($pdo);
$pdo->exec("INSERT INTO usuarios(nome,login,email,senha_hash) VALUES('Legado','legado','legado@example.test','hash');
    INSERT INTO contas(usuario_id,nome,tipo,saldo_inicial_centavos,saldo_inicial_em) VALUES(1,'Principal','corrente',100000,'2025-01-01');
    INSERT INTO grupos(usuario_id,nome,tipo) VALUES(1,'Alimentação','despesa');
    INSERT INTO subgrupos(grupo_id,nome) VALUES(1,'Mercado');
    INSERT INTO transacoes(usuario_id,subgrupo_id,conta_id,descricao,valor_centavos,data_competencia,data_vencimento,data_efetivacao,status)
        VALUES(1,1,1,'Compra existente',2500,'2025-01-10','2025-01-10','2025-01-10','efetivada')");
$before=$pdo->query('SELECT * FROM transacoes')->fetchAll();
foreach($files as $file) if((int)basename($file)>17) { $pdo->beginTransaction(); (require $file)($pdo); $pdo->commit(); }
$after=$pdo->query('SELECT * FROM transacoes')->fetchAll();
foreach($before as $index=>$row) foreach($row as $key=>$value) if($after[$index][$key]!==$value) throw new RuntimeException('Registro legado alterado: '.$key);
$account=(new App\Repositories\AccountRepository($pdo))->findById(1,1);
if((int)$account['saldo_atual_centavos']!==97500) throw new RuntimeException('Saldo legado alterado.');
if($pdo->query("SELECT tipo FROM usuarios WHERE id=1")->fetchColumn()!=='admin') throw new RuntimeException('Nenhum administrador foi definido após atualização.');
if($pdo->query('PRAGMA foreign_key_check')->fetchAll()!==[]) throw new RuntimeException('Relacionamento inválido após atualização.');
if($pdo->query('PRAGMA integrity_check')->fetchColumn()!=='ok') throw new RuntimeException('Banco inválido.');
echo "OK: atualização 017 → 022 preserva registros, saldo e integridade.\n";
