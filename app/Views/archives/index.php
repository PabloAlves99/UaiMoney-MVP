<?php
use App\Core\Html as H;
$routes=['categoria'=>'/categorias/grupos/%d/reativar','subcategoria'=>'/categorias/subgrupos/%d/reativar','conta'=>'/contas/%d/reativar','cartao'=>'/cartoes/%d/reativar','investimento'=>'/investimentos/%d/reativar','objetivo'=>'/objetivos/%d/reativar','projecao'=>'/projecoes/%d/reativar','limite'=>'/categorias/limites/%d/reativar','movimentacao'=>'/movimentacoes/%d/restaurar'];
?>
<div class="page-heading"><div><p class="eyebrow">CONTROLE DOS DADOS</p><h1>Arquivados</h1><p class="text-secondary">Consulte e reative registros inativos. O histórico financeiro é preservado.</p></div></div>
<?php foreach($groups as $title=>$items): if(!$items)continue; ?>
<section class="card mb-3"><div class="card-body"><h2 class="h5"><?=H::escape($title)?> <span class="badge text-bg-secondary"><?=count($items)?></span></h2><div class="table-responsive"><table class="table align-middle mb-0"><tbody>
<?php foreach($items as $item):
    $type=(string)$item['tipo'];
    $blocked=in_array($type,['subcategoria','evento','reserva'],true) && !(int)$item['pai_ativo'];
    $path=$type==='evento' ? '/investimentos/'.$item['pai_id'].'/eventos/'.$item['id'].'/reativar'
        : ($type==='reserva' ? '/objetivos/'.$item['pai_id'].'/reservas/'.$item['id'].'/reativar' : sprintf($routes[$type],(int)$item['id']));
?>
<tr><td><?=H::escape($item['nome'])?></td><td class="text-end">
<?php if($blocked):?><span class="small text-secondary">Reative o registro principal primeiro</span>
<?php else:?><form method="post" action="<?=H::escape($basePath.$path)?>"><?=H::fields($csrfToken)?>
<?php if($type==='movimentacao'):?><input type="hidden" name="versao" value="<?=(int)$item['versao']?>"><?php endif;?>
<input type="hidden" name="origem" value="desativadas"><button class="btn btn-sm btn-uai-primary">Reativar</button></form><?php endif;?>
</td></tr><?php endforeach; ?>
</tbody></table></div></div></section>
<?php endforeach; ?>
<?php if(!array_filter($groups)):?><div class="empty-state">Nenhum registro arquivado.</div><?php endif; ?>
<p class="small text-secondary">Recorrências pausadas e encerradas têm controles próprios na página Recorrências.</p>
