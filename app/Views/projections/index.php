<?php
use App\Core\Html as H;
use App\Core\Money;
$duration = static function (int $months): string {
    if ($months === 0) return 'Meta alcançada';
    $years=intdiv($months,12); $rest=$months%12; $parts=[];
    if($years) $parts[]=$years.' '.($years===1?'ano':'anos');
    if($rest) $parts[]=$rest.' '.($rest===1?'mês':'meses');
    return implode(' e ',$parts);
};
$month = static fn(string $value): string => substr($value,5,2).'/'.substr($value,0,4);
?>
<div class="page-heading">
  <div><p class="eyebrow">CENÁRIOS FINANCEIROS</p><h1>Projeções</h1><p class="text-secondary">Transforme seus investimentos e objetivos em planos mensuráveis, com prazo, aportes e juros compostos.</p></div>
  <a class="btn btn-uai-primary" href="<?=H::escape($basePath.'/projecoes/nova')?>">+ Nova projeção</a>
</div>
<?php if(!$projections): ?>
  <section class="card"><div class="empty-state"><h2 class="h4">Crie seu primeiro cenário</h2><p>Escolha um investimento ou objetivo, defina o aporte e a rentabilidade esperada. O valor atual será usado automaticamente.</p><a class="btn btn-uai-primary" href="<?=H::escape($basePath.'/projecoes/nova')?>">Criar projeção</a></div></section>
<?php else: ?>
  <div class="projection-grid">
  <?php foreach($projections as $item): $result=$item['result']; $target=(int)$item['meta_calculada_centavos']; $current=(int)$item['valor_inicial_centavos']; ?>
    <article class="card projection-card"><div class="card-body">
      <div class="d-flex justify-content-between align-items-start gap-3"><div><span class="projection-origin"><?=H::escape($item['origem_tipo']==='investimento'?'Investimento':'Objetivo')?> · <?=H::escape($item['origem_nome'])?></span><h2 class="h5 mt-2 mb-1"><a href="<?=H::escape($basePath.'/projecoes/'.$item['id'])?>"><?=H::escape($item['nome'])?></a></h2></div><?php if(!(int)$item['origem_ativa']):?><span class="badge text-bg-secondary">Origem arquivada</span><?php endif;?></div>
      <div class="projection-card-values"><div><span>Hoje</span><strong><?=Money::format($current)?></strong></div><div><span>Meta</span><strong><?=Money::format($target)?></strong></div></div>
      <progress class="uai-progress mt-3" max="<?=$target?>" value="<?=min($current,$target)?>" aria-label="Progresso da projeção"></progress>
      <?php if($result): ?><div class="projection-card-deadline"><strong><?=$duration((int)$result['months'])?></strong><span>estimativa para <?=$month($result['estimated_month'])?></span></div><div class="projection-card-meta"><span><?=Money::format((int)$item['aporte_mensal_centavos'])?>/mês</span><span><?=number_format((int)$item['taxa_quatro_casas']/10000,4,',','.')?>% a<?= $item['periodicidade_taxa']==='mensal'?'o mês':'o ano' ?></span></div><?php else:?><div class="alert alert-warning mt-3 mb-0"><?=H::escape($item['error'])?></div><?php endif;?>
      <div class="d-flex gap-2 mt-3"><a class="btn btn-sm btn-uai-primary" href="<?=H::escape($basePath.'/projecoes/'.$item['id'])?>">Ver análise</a><a class="btn btn-sm btn-outline-secondary" href="<?=H::escape($basePath.'/projecoes/'.$item['id'].'/editar')?>">Editar</a></div>
    </div></article>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
