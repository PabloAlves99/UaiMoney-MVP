<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$pdo=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pdo->exec('PRAGMA foreign_keys=ON');
$dir=dirname(__DIR__).'/database/migrations/';
foreach(glob($dir.'*.php') as $file)if((int)basename($file)<=30){$pdo->beginTransaction();(require $file)($pdo);$pdo->commit();}
$pdo->exec("INSERT INTO usuarios(nome,login,email,senha_hash) VALUES('Teste','teste','teste@example.test','hash');
    INSERT INTO investimentos(usuario_id,nome,tipo,valor_aplicado_centavos,valor_atual_centavos,data_inicio) VALUES(1,'Legado','renda_fixa',100000,105000,'2025-01-01');
    INSERT INTO metas_financeiras(usuario_id,nome,tipo,valor_meta_centavos,valor_atual_centavos) VALUES(1,'Reserva','reserva',500000,25000);
    INSERT INTO projecoes(usuario_id,nome,origem_tipo,investimento_id,aporte_mensal_centavos,taxa_quatro_casas,periodicidade_taxa,valor_meta_centavos) VALUES(1,'Cenário','investimento',1,10000,10000,'mensal',500000)");
$pdo->beginTransaction();(require $dir.'031_connect_financial_plan.php')($pdo);$pdo->commit();
$investment=$pdo->query('SELECT valor_aplicado_centavos,valor_atual_centavos FROM investimentos WHERE id=1')->fetch();
$events=$pdo->query('SELECT SUM(valor_centavos) AS saldo,COUNT(*) AS total FROM investimento_eventos WHERE investimento_id=1')->fetch();
$goal=$pdo->query('SELECT valor_atual_centavos,valor_legado_centavos FROM metas_financeiras WHERE id=1')->fetch();
$projection=$pdo->query('SELECT nome,ativo FROM projecoes WHERE id=1')->fetch();
if((int)$investment['valor_atual_centavos']!==105000||(int)$events['saldo']!==105000||(int)$events['total']!==2||(int)$goal['valor_legado_centavos']!==25000||$projection['nome']!=='Cenário'||(int)$projection['ativo']!==1)throw new RuntimeException('Migração alterou registros legados.');
if($pdo->query('PRAGMA foreign_key_check')->fetchAll()!==[])throw new RuntimeException('Chaves estrangeiras inválidas.');
echo "OK: saldos e cenários antigos preservados na migração 031.\n";
