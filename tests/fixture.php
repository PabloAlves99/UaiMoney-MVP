<?php
declare(strict_types=1);
use App\Core\FinancialDate;
// A disposable preview database. Never opens or alters the user's database.
require __DIR__.'/run.php';
$db->prepare('UPDATE usuarios SET senha_hash=?')->execute([password_hash('UaiTeste!2026',PASSWORD_DEFAULT)]);
$income=array_values(array_filter($reports->categories(1),fn($c)=>$c['tipo']==='receita'))[0];
for($i=-5;$i<=0;$i++) {
    $m=FinancialDate::shift(date('Y-m'),$i);
    $d=$m.'-01';
    $tx->create(1,(int)$income['id'],$a,'Salário mensal','4800',$d,$d,$d,'efetivada','pix',null);
    $tx->create(1,(int)$expense['id'],$a,'Mercado do mês',(string)(800+$i*30),$d,$d,$d,'efetivada','debito',null);
}
$planning->save(1,['mes'=>date('Y-m'),'grupo_id'=>$expense['grupo_id'],'valor'=>'1200']);
$tx->create(1,(int)$expense['id'],$a,'Aluguel','1400',date('Y-m-d'),date('Y-m-t'),null,'pendente','boleto',null);
$cardService->purchase(1,['cartao_id'=>$card,'subgrupo_id'=>$expense['id'],'data'=>date('Y-m-d'),'descricao'=>'Notebook','valor'=>'2400','parcelas'=>6]);
$dir=dirname(__DIR__).'/storage/testing';
if(!is_dir($dir)) mkdir($dir,0770,true);
$file=$dir.'/preview-'.bin2hex(random_bytes(5)).'.sqlite';
$db->exec('VACUUM INTO '.$db->quote($file));
echo $file.PHP_EOL;
