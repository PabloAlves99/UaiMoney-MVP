<?php
declare(strict_types=1);
require __DIR__ . '/run.php';

$newCard = $cardService->save(1, null, ['nome'=>'Teste reabertura', 'limite'=>'1000', 'dia_fechamento'=>20, 'dia_vencimento'=>5, 'conta_pagamento_id'=>$a]);
$purchase = ['cartao_id'=>$newCard, 'subgrupo_id'=>$expense['id'], 'data'=>'2024-01-10', 'descricao'=>'Compra original', 'valor'=>'100', 'parcelas'=>1];
$cardService->purchase(1, $purchase);
$invoice = $cards->invoices(1, $newCard)[0];
$id = (int)$invoice['id'];
rejects(fn()=> $cardService->reopen(1, $id), 'Cannot reopen an open invoice');
rejects(fn()=> $cardService->reopen(2, $id), 'Cannot reopen another user invoice');
$cardService->pay(1, $id, ['conta_id'=>$a, 'data'=>'2024-01-15', 'valor'=>'30']);
$balance = (int)$accounts->findById($a, 1)['saldo_atual_centavos'];
$cardService->reopen(1, $id);
check($cards->invoice(1, $id)['status']==='aberta', 'Paid invoice reopened');
check((int)$cards->invoice(1, $id)['pago']===3000 && (int)$accounts->findById($a, 1)['saldo_atual_centavos']===$balance, 'Payment and account balance preserved');
$cardService->purchase(1, array_replace($purchase, ['descricao'=>'Compra esquecida', 'valor'=>'20']));
check((int)$cards->invoice(1, $id)['total']===12000 && (int)$cards->invoice(1, $id)['pago']===3000, 'Reopened invoice accepts purchase and preserves payment');
rejects(fn()=> $cardService->purchase(1, array_replace($purchase, ['data'=>'2024-01-16'])), 'Purchase cannot postdate an existing payment');
$cardService->close(1, $id);
rejects(fn()=> $cardService->purchase(1, $purchase), 'Reclosed invoice rejects purchase');
echo "OK: reabertura de fatura, pagamentos preservados e isolamento.\n";
