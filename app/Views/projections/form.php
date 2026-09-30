<?php
use App\Core\Html as H;
use App\Core\Money;
$editing=is_array($projection);
$origin=$editing ? $projection['origem_tipo'].':'.($projection['investimento_id']?:$projection['objetivo_id']) : '';
$rate=$editing ? rtrim(rtrim(number_format((int)$projection['taxa_quatro_casas']/10000,4,',',''),'0'),',') : '';
$values=[
  'nome'=>$projection['nome']??'', 'origem'=>$origin,
  'aporte_mensal'=>$editing?number_format((int)$projection['aporte_mensal_centavos']/100,2,',','.'):'',
  'taxa'=>$rate, 'periodicidade_taxa'=>$projection['periodicidade_taxa']??'mensal',
  'valor_meta'=>$editing&&$projection['valor_meta_centavos']!==null?number_format((int)$projection['valor_meta_centavos']/100,2,',','.'):'',
];
$values=array_replace($values,$old);
?>
<a class="back-link" href="<?=H::escape($basePath.($editing?'/projecoes/'.$projection['id']:'/projecoes'))?>">← Voltar</a>
<div class="page-heading"><div><p class="eyebrow">CENÁRIO SALVO</p><h1><?=$editing?'Editar projeção':'Nova projeção'?></h1><p class="text-secondary">O valor inicial acompanha automaticamente o valor atual do investimento ou objetivo escolhido.</p></div></div>
<div class="row g-4"><div class="col-xl-8"><section class="card"><div class="card-body">
  <form method="post" action="<?=H::escape($basePath.($editing?'/projecoes/'.$projection['id'].'/editar':'/projecoes'))?>" class="vstack gap-4">
    <?=H::fields($csrfToken)?>
    <div><label class="form-label" for="projection-name">Nome do cenário</label><input id="projection-name" name="nome" class="form-control" required maxlength="100" placeholder="Ex.: Independência financeira" value="<?=H::escape($values['nome'])?>"></div>
    <div><label class="form-label" for="projection-source">Investimento ou objetivo</label><select id="projection-source" name="origem" class="form-select" required><option value="">Selecione</option><?php foreach(['investimento'=>'Investimentos','objetivo'=>'Objetivos'] as $type=>$label):?><optgroup label="<?=$label?>"><?php foreach($sources as $source): if($source['tipo']!==$type || (!(int)$source['ativo'] && $values['origem']!==$type.':'.$source['id'])) continue; $key=$type.':'.$source['id'];?><option value="<?=H::escape($key)?>" <?=$values['origem']===$key?'selected':''?>><?=H::escape($source['nome'])?> · atual <?=Money::format((int)$source['atual_centavos'])?><?= $type==='objetivo'?' · meta '.Money::format((int)$source['meta_centavos']):'' ?></option><?php endforeach;?></optgroup><?php endforeach;?></select><div class="form-text">A projeção será recalculada quando o valor atual desse cadastro mudar.</div></div>
    <div class="row g-3"><div class="col-md-6"><label class="form-label" for="projection-contribution">Aporte mensal (R$)</label><input id="projection-contribution" name="aporte_mensal" class="form-control" inputmode="decimal" required placeholder="1.000,00" value="<?=H::escape($values['aporte_mensal'])?>"></div><div class="col-md-6"><label class="form-label" for="projection-target">Meta para investimento (R$)</label><input id="projection-target" name="valor_meta" class="form-control" inputmode="decimal" placeholder="1.000.000,00" value="<?=H::escape($values['valor_meta'])?>"><div class="form-text">Para objetivos, a meta já cadastrada será utilizada.</div></div></div>
    <div class="row g-3"><div class="col-md-6"><label class="form-label" for="projection-rate">Taxa esperada (%)</label><input id="projection-rate" name="taxa" class="form-control" inputmode="decimal" required placeholder="1,00" value="<?=H::escape($values['taxa'])?>"></div><div class="col-md-6"><label class="form-label" for="projection-period">Periodicidade</label><select id="projection-period" name="periodicidade_taxa" class="form-select" required><option value="mensal" <?=$values['periodicidade_taxa']==='mensal'?'selected':''?>>Ao mês</option><option value="anual" <?=$values['periodicidade_taxa']==='anual'?'selected':''?>>Ao ano</option></select></div></div>
    <div class="d-flex flex-wrap gap-2"><button class="btn btn-uai-primary"><?=$editing?'Salvar alterações':'Criar projeção'?></button><a class="btn btn-outline-secondary" href="<?=H::escape($basePath.'/projecoes')?>">Cancelar</a></div>
  </form>
</div></section></div><div class="col-xl-4"><aside class="strategy-note"><strong>Como calculamos</strong><p class="mt-2 mb-0">Juros compostos mensais e aportes ao fim de cada mês. Taxas anuais são convertidas para sua equivalente mensal. A projeção não desconta inflação, impostos ou custos.</p></aside></div></div>
