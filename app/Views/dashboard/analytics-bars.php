<?php
foreach (['receita' => 'Recebimentos', 'despesa' => 'Despesas'] as $flow => $flowName) {
    $column = $flow === 'receita' ? 'receitas' : 'despesas';
    $flowGroups = array_map(fn($row) => ['id' => $row['id'], 'grupo_id' => $row['id'], 'nome' => $row['nome'], 'valor_centavos' => (int)$row[$column]], $categoryRows);
    $flowSubgroups = array_map(fn($row) => ['id' => $row['id'], 'grupo_id' => $row['grupo_id'], 'nome' => $row['nome'], 'valor_centavos' => (int)$row[$column]], $subcategoryRows);
    usort($flowGroups, fn($a, $b) => $b['valor_centavos'] <=> $a['valor_centavos']);
    usort($flowSubgroups, fn($a, $b) => $b['valor_centavos'] <=> $a['valor_centavos']);
    $flowLink = fn($row, $dimension) => $url(['grupo_id' => $row['grupo_id'], 'subgrupo_id' => $dimension === 'subgrupo' ? $row['id'] : null, 'tipo' => $flow, 'conferir' => 1]);
    require __DIR__ . '/analytics-flow.php';
}
