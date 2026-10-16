<?php
declare(strict_types=1);
require __DIR__ . '/run.php';

$import = new \App\Services\StatementImportService($db, $tx);
$income = array_values(array_filter($reports->categories(1), static fn(array $category): bool => $category['tipo'] === 'receita'))[0];
$rows = $import->parse('extrato.csv', "Data;Descrição;Valor\n10/04/2025;Café importado;-12,50\n11/04/2025;Receita importada;50,00\n");
check(count($rows) === 2 && $rows[0]['centavos'] === -1250 && $rows[1]['centavos'] === 5000, 'CSV preview and signed amounts');
$ofx = $import->parse('extrato.ofx', '<STMTTRN><DTPOSTED>20250412<TRNAMT>-7.25<NAME>Mercado OFX</STMTTRN>');
check(count($ofx) === 1 && $ofx[0]['data'] === '2025-04-12' && $ofx[0]['centavos'] === -725, 'OFX preview');
$batch = $import->import(1, $a, $rows, [0 => '1', 1 => '1'], [0 => $expense['id'], 1 => $income['id']]);
check((int) $import->lastBatch(1)['id'] === $batch, 'Import batch saved');
check($import->duplicate(1, $a, $rows[0]), 'Existing transaction detected');
rejects(fn() => $import->import(2, $foreign, [$rows[0]], [0 => '1'], [0 => $expense['id']]), 'Foreign category rejected');
check($import->lastBatch(2) === null, 'Failed import rolled back');
$import->undo(1, $batch);
check($import->lastBatch(1) === null && !$import->duplicate(1, $a, $rows[0]), 'Import undo restores balances and duplicate availability');
rejects(fn() => $import->undo(2, $batch), 'Foreign undo rejected');
echo "OK: importação CSV/OFX, isolamento, duplicatas e desfazer.\n";
