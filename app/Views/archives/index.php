<?php
use App\Core\Html as H;
use App\Core\Money;
$routes=['categoria'=>'/categorias/grupos/%d/reativar','subcategoria'=>'/categorias/subgrupos/%d/reativar','conta'=>'/contas/%d/reativar','cartao'=>'/cartoes/%d/reativar','investimento'=>'/investimentos/%d/reativar','objetivo'=>'/objetivos/%d/reativar','projecao'=>'/projecoes/%d/reativar','limite'=>'/categorias/limites/%d/reativar','movimentacao'=>'/movimentacoes/%d/restaurar'];
?>
<div class="page-heading"><div><p class="eyebrow">CONTROLE DOS DADOS</p><h1>Arquivados</h1><p class="text-secondary">Registros arquivados ficam apenas aqui. Reative um item para voltar a usá-lo.</p></div></div>
<?php foreach($groups as $title=>$items): ?>
<section class="card mb-3"><div class="card-body"><div class="section-heading"><h2 class="h5 mb-0">Arquivados: <?=H::escape($title)?> <span class="badge text-bg-secondary"><?=count($items)?></span></h2></div>
<?php if(!$items):?><p class="small text-secondary mb-0">Nenhum registro arquivado nesta área.</p><?php else:?>
<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th scope="col">Registro</th><th scope="col">Referência</th><th scope="col" class="text-end">Ação</th></tr></thead><tbody>
<?php foreach($items as $item):
    $type=(string)$item['tipo'];
    $blocked=in_array($type,['subcategoria','evento','reserva','projecao','limite'],true) && isset($item['pai_ativo']) && !(int)$item['pai_ativo'];
    $path=$type==='evento' ? '/investimentos/'.$item['pai_id'].'/eventos/'.$item['id'].'/reativar'
        : ($type==='reserva' ? '/objetivos/'.$item['pai_id'].'/reservas/'.$item['id'].'/reativar' : sprintf($routes[$type],(int)$item['id']));
?>
<tr><td><strong><?=H::escape($item['nome'])?></strong></td><td class="small text-secondary"><?php if($type==='movimentacao'):?><?=H::date($item['data_competencia'])?> · <?=Money::format((int)$item['valor_centavos'])?> · <?=H::escape($item['status_antes_exclusao']??'')?><?php else:?>ID <?= (int)$item['id'] ?><?php endif;?></td><td class="text-end">
<?php if($blocked):?><span class="small text-secondary">Reative o registro principal primeiro</span>
<?php else:?><form method="post" action="<?=H::escape($basePath.$path)?>"><?=H::fields($csrfToken)?>
<?php if($type==='movimentacao'):?><input type="hidden" name="versao" value="<?=(int)$item['versao']?>"><?php endif;?>
<input type="hidden" name="origem" value="desativadas"><button class="btn btn-sm btn-uai-primary">Reativar</button></form><?php endif;?>
</td></tr><?php endforeach; ?>
</tbody></table></div><?php endif;?></div></section>
<?php endforeach; ?>
<p class="small text-secondary">Recorrências pausadas e encerradas têm controles próprios na página Recorrências.</p>
