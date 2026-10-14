<?php
use App\Core\Html as H;
$incomeGroups = array_map(fn($row) => ['id'=>$row['id'], 'grupo_id'=>$row['id'], 'nome'=>$row['nome'], 'valor_centavos'=>(int) $row['receitas']], $categoryRows);
$incomeSubgroups = array_map(fn($row) => ['id'=>$row['id'], 'grupo_id'=>$row['grupo_id'], 'nome'=>$row['nome'], 'valor_centavos'=>(int) $row['receitas']], $subcategoryRows);
usort($incomeGroups, fn($a, $b) => $b['valor_centavos'] <=> $a['valor_centavos']);
usort($incomeSubgroups, fn($a, $b) => $b['valor_centavos'] <=> $a['valor_centavos']);
$incomeChartNote = 'Receitas realizadas por competência, descontando estornos. Selecione um grupo ou subgrupo para conferir os lançamentos.';
$incomeChartLink = fn($row, $dimension) => $url(['grupo_id'=>$row['grupo_id'], 'subgrupo_id'=>$dimension === 'subgrupo' ? $row['id'] : null, 'tipo'=>'receita', 'conferir'=>1], '#lancamentos');
require dirname(__DIR__) . '/partials/income-charts.php';
