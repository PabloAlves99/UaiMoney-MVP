<?php
declare(strict_types=1);
require __DIR__ . '/invoice_reopen.php';

$original = $cards->items(1, $id)[0];
$cardService->purchase(1, array_replace($purchase, ['data'=>'2024-02-10', 'descricao'=>'Compra destino', 'valor'=>'40']));
$destination = $cardService->invoiceFor(1, $cardService->get(1, $newCard), '2024-02');
$destinationId = (int)$destination['id'];
$cardService->pay(1, $destinationId, ['conta_id'=>$a, 'data'=>'2024-02-15', 'valor'=>'10']);
$balance = (int)$accounts->findById($a, 1)['saldo_atual_centavos'];
rejects(fn()=> $wallet->movePurchase(2, (int)$original['id'], '2024-02'), 'Cannot move another user purchase');
$wallet->movePurchase(1, (int)$original['id'], '2024-02');
$sourceAfter = $cards->invoice(1, $id);
$targetAfter = $cards->invoice(1, $destinationId);
check((int)$sourceAfter['total']===2000 && (int)$sourceAfter['pago']===3000, 'Source retains payment and shows credit');
check((int)$targetAfter['total']===14000 && (int)$targetAfter['pago']===1000, 'Destination retains payment and receives purchase');
check((int)$accounts->findById($a, 1)['saldo_atual_centavos']===$balance, 'Moving does not change account balance');
$moved = $ledger->transaction(1, (int)$original['id']);
check((int)$moved['fatura_id']===$destinationId && $moved['data_competencia']===$original['data_competencia'], 'Invoice changes and competence remains');
check($sourceAfter['status']==='fechada' && $targetAfter['status']==='fechada', 'Invoice statuses preserved');
echo "OK: movimentação entre faturas pagas, saldos e isolamento.\n";
